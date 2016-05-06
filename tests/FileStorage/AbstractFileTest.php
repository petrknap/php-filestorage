<?php

namespace PetrKnap\Php\FileStorage\Test;

use PetrKnap\Php\FileStorage\AbstractFile;
use PetrKnap\Php\FileStorage\FileException;
use PetrKnap\Php\FileStorage\Test\AbstractFileTest\TestFile;

class AbstractFileTest extends \PHPUnit_Framework_TestCase
{
    const TEST_FILE = "/test.file";

    /**
     * @var AbstractFile
     */
    private $file;

    /**
     * @var string
     */
    private $pathToStorageDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->pathToStorageDirectory = tempnam(__DIR__, __CLASS__);

        unlink($this->pathToStorageDirectory);

        TestFile::setStorageDirectory($this->pathToStorageDirectory);
    }

    public function __destruct()
    {
        if(file_exists($this->pathToStorageDirectory)) {
            exec("rm {$this->pathToStorageDirectory} -fr");
        }
    }

    public function setUp()
    {
        $this->file = new TestFile(self::TEST_FILE);

        if ($this->file->exists()) {
            $this->file->delete();
        }
    }

    public function testCanCreateNewFile()
    {
        $this->assertFalse($this->file->exists());

        $this->file->create();

        $this->assertTrue($this->file->exists());

        try {
            $this->file->create();
            $this->fail("Can create existing file.");
        }
        catch(FileException $fe) {
            $this->assertEquals(FileException::FileExistsException, $fe->getCode());
        }
    }

    public function testCanReadFromFile()
    {
        try {
            $this->file->read();
            $this->fail("Can read from nothing.");
        }
        catch (FileException $fe) {
            $this->assertEquals(FileException::FileNotFoundException, $fe->getCode());
        }

        $this->file->create();

        $this->assertEmpty($this->file->read());
    }

    public function testCanWriteToFile()
    {
        $data = __METHOD__;

        try {
            $this->file->write($data);
            $this->fail("Can write to nothing.");
        }
        catch (FileException $fe) {
            $this->assertEquals(FileException::FileNotFoundException, $fe->getCode());
        }

        $this->file->create();

        $this->file->write($data);

        $this->assertEquals($data, $this->file->read());
    }

    public function testCanClearFile()
    {
        try {
            $this->file->clear();
            $this->fail("Can clear nothing.");
        }
        catch (FileException $fe) {
            $this->assertEquals(FileException::FileNotFoundException, $fe->getCode());
        }

        $this->file->create();

        $this->file->write(__METHOD__);

        $this->file->clear();

        $this->assertEmpty($this->file->read());
    }

    public function testCanDeleteFile()
    {
        try {
            $this->file->delete();
            $this->fail("Can delete nothing.");
        }
        catch (FileException $fe) {
            $this->assertEquals(FileException::FileNotFoundException, $fe->getCode());
        }

        $this->file->create();

        $this->assertTrue($this->file->exists());

        $this->file->delete();

        $this->assertFalse($this->file->exists());
    }

    public function testPerformanceCheck()
    {
        $data = __METHOD__;

        $times = array();

        for($i = 0; $i < 100; $i++) {
            $begin = microtime(true);

            $this->file->create();

            $this->file->write($data);

            $this->assertEquals($data, $this->file->read());

            $this->file->write($data, FILE_APPEND);

            $this->assertEquals($data . $data, $this->file->read());

            $this->file->clear();

            $this->assertEmpty($this->file->read());

            $this->file->delete();

            $this->assertFalse($this->file->exists());

            $end = microtime(true);

            array_push($times, intval(round(($end - $begin) * 1000)));
        }

        $sum = 0;
        $count = count($times);

        foreach($times as $time) {
            $sum += $time;
        }

        $avg = $sum / $count;

        $this->assertLessThanOrEqual(250, $avg);
    }
}
