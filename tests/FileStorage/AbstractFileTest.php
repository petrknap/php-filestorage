<?php

namespace PetrKnap\Php\FileStorage\Test;

use PetrKnap\Php\FileStorage\FileExistsException;
use PetrKnap\Php\FileStorage\FileNotFoundException;
use PetrKnap\Php\FileStorage\Test\AbstractTestCase\TestFile;

class AbstractFileTest extends AbstractTestCase
{
    /**
     * @var string
     */
    private $pathToStorageDirectory;

    public function setUp()
    {
        parent::setUp();

        $this->pathToStorageDirectory = $this->getTemporaryDirectory();

        TestFile::setStorageDirectory($this->pathToStorageDirectory);
    }

    public function testCanCreateNewFile()
    {
        $file = $this->getFile();
        $file->create();
        $this->assertTrue($file->exists());

        $file = $this->getFile();
        $file->create();
        $this->setExpectedException(FileExistsException::class);
        $file->create();
    }

    public function testCanDeleteFile()
    {
        $file = $this->getFile();
        $file->create();
        $file->delete();
        $this->assertFalse($file->exists());

        $file = $this->getFile();
        $this->setExpectedException(FileNotFoundException::class);
        $file->delete();
    }

    public function testCanReadFromFile()
    {
        $content = __METHOD__;

        $file = $this->getFile();
        $file->create();
        file_put_contents($file->getRealPathToFile(), $content);
        $this->assertEquals($content, $file->read());

        $file = $this->getFile();
        $this->setExpectedException(FileNotFoundException::class);
        $file->read();
    }

    public function testCanWriteToFile()
    {
        $content = __METHOD__;

        $file = $this->getFile();
        $file->create();
        $file->write($content);
        $this->assertEquals($content, file_get_contents($file->getRealPathToFile()));
        $file->write($content, true);
        $this->assertEquals($content . $content, file_get_contents($file->getRealPathToFile()));

        $file = $this->getFile();
        $this->setExpectedException(FileNotFoundException::class);
        $file->write($content);
    }

    public function testCanClearFile()
    {
        $content = __METHOD__;

        $file = $this->getFile();
        $file->create();
        $file->write($content);
        $file->clear();
        $this->assertEmpty($file->read());

        $file = $this->getFile();
        $this->setExpectedException(FileNotFoundException::class);
        $file->clear();
    }
}
