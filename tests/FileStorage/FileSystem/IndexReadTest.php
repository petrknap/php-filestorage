<?php

namespace PetrKnap\Php\FileStorage\Test\FileSystem;

use PetrKnap\Php\FileStorage\Exception\IndexDecodeException;
use PetrKnap\Php\FileStorage\Exception\IndexReadException;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Test\FileSystemTestCase;

class IndexReadTest extends FileSystemTestCase
{
    const INDEX_FILE = FileSystem::INDEX_FILE;
    const INNER_INDEX_FILE = "/71/82/20/7c/ea/19/51/62/77/f7/14/56/32/4b/f1/3d/fa/c3/1b/79-9e743aa713dcae5405f290e3db88178b.json";

    public function testReadIndexWorksWithExistentIndexFile()
    {
        $expected = ["key" => "value"];
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());

        $fileSystem->write(self::INDEX_FILE, json_encode($expected));
        $this->assertEquals(
            $expected,
            $this->invokePrivateMethod($fileSystem, "readIndex", [self::INNER_INDEX_FILE])
        );
    }

    public function testReadIndexWorksWithNonexistentIndexFile()
    {
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());

        $this->assertEquals(
            [],
            $this->invokePrivateMethod($fileSystem, "readIndex", [self::INDEX_FILE])
        );
    }

    public function testReadIndexDoesNotWorkWithCorruptedIndexFile()
    {
        $tempDir = $this->getTemporaryDirectory();
        $fileSystem = $this->getFileSystem($tempDir);
        $fileSystem->write(self::INDEX_FILE, null);

        $this->setExpectedException(IndexDecodeException::class);
        $this->invokePrivateMethod($fileSystem, "readIndex", [self::INNER_INDEX_FILE]);
    }

    public function testReadIndexDoesNotWorkWithInaccessibleIndexFile()
    {
        $tempDir = $this->getTemporaryDirectory();
        $fileSystem = $this->getFileSystem($tempDir);
        $fileSystem->write(self::INDEX_FILE, null);
        chmod("{$tempDir}/". self::INNER_INDEX_FILE, 0000);

        $this->setExpectedException(IndexReadException::class);
        $this->invokePrivateMethod($fileSystem, "readIndex", [self::INNER_INDEX_FILE]);
    }
}
