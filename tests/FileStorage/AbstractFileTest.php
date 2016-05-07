<?php

namespace PetrKnap\Php\FileStorage\Test;

use PetrKnap\Php\FileStorage\FileExistsException;
use PetrKnap\Php\FileStorage\FileNotFoundException;
use PetrKnap\Php\FileStorage\Test\AbstractTestCase\TestFile;
use PetrKnap\Php\Profiler\SimpleProfiler;

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

    private function getFile($id)
    {
        return new TestFile("/{$id}.file");
    }

    public function testCanCreateNewFile()
    {
        $this->getFile(1)->create();
        $this->assertTrue($this->getFile(1)->exists());

        $this->setExpectedException(FileExistsException::class);
        $this->getFile(1)->create();
    }

    public function testCanDeleteFile()
    {
        $this->getFile(1)->create();
        $this->getFile(1)->delete();
        $this->assertFalse($this->getFile(1)->exists());

        $this->setExpectedException(FileNotFoundException::class);
        $this->getFile(2)->delete();
    }

    public function testCanReadFromFile()
    {
        $content = __METHOD__;

        $this->getFile(1)->create();
        file_put_contents($this->getFile(1)->getRealPathToFile(), $content);
        $this->assertEquals($content, $this->getFile(1)->read());

        $this->setExpectedException(FileNotFoundException::class);
        $this->getFile(2)->delete();
        $this->getFile(2)->read();
    }

    public function testCanWriteToFile()
    {
        $content = __METHOD__;

        $this->getFile(1)->create();
        $this->getFile(1)->write($content);
        $this->assertEquals($content, file_get_contents($this->getFile(1)->getRealPathToFile()));
        $this->getFile(1)->write($content, true);
        $this->assertEquals($content . $content, file_get_contents($this->getFile(1)->getRealPathToFile()));

        $this->setExpectedException(FileNotFoundException::class);
        $this->getFile(2)->delete();
        $this->getFile(2)->write($content);
    }

    public function testCanClearFile()
    {
        $content = __METHOD__;

        $this->getFile(1)->create();
        $this->getFile(1)->write($content);
        $this->getFile(1)->clear();
        $this->assertEmpty($this->getFile(1)->read());

        $this->setExpectedException(FileNotFoundException::class);
        $this->getFile(2)->delete();
        $this->getFile(2)->clear();
    }

    /**
     * @dataProvider performanceIsNotIntrusiveDataProvider
     * @param string $directory
     * @param int $from
     * @param int $to
     */
    public function testPerformanceIsNotIntrusive($directory, $from, $to)
    {
        TestFile::setStorageDirectory($directory);

        $profilerWasEnabled = SimpleProfiler::start();
        if(!$profilerWasEnabled) {
            SimpleProfiler::enable();
        }

        for ($i = $from; $i < $to; $i++) {
            SimpleProfiler::start();

            $file = $this->getFile($i);
            if ($file->exists()) {
                $file->delete();
            }
            $file->create();
            $file->write(sha1($i, true));
            $file->write(md5($i, true), FILE_APPEND);
            $file->read();

            $profile = SimpleProfiler::finish();
            $this->assertLessThanOrEqual(250, $profile->absoluteDuration);
        }

        if ($profilerWasEnabled) {
            SimpleProfiler::disable();
        }
        SimpleProfiler::finish();
    }

    public function performanceIsNotIntrusiveDataProvider()
    {
        srand(1462607969);
        $iMax = 16384;
        $step = 128;
        $output = [];
        $directory = $this->getTemporaryDirectory();
        for ($i = 0; $i < $iMax; $i += $step)
        {
            $output[] = [$directory, $i, $i + $step + rand(0, 16)];
        }
        return $output;
    }
}
