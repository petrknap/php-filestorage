<?php

namespace PetrKnap\Php\FileStorage\Test\FileSystemTest;

use PetrKnap\Php\FileStorage\Exception\IndexWriteException;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Test\FileSystemTestCase;

class IndexWriteTestCase extends FileSystemTestCase
{
    const INDEX_FILE = FileSystem::INDEX_FILE;
    const INNER_INDEX_FILE = "/71/82/20/7c/ea/19/51/62/77/f7/14/56/32/4b/f1/3d/fa/c3/1b/79-9e743aa713dcae5405f290e3db88178b.json";

    public function testWriteIndexWorksWithExistentIndexFile()
    {
        $expected = ["key" => "value"];
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());
        $fileSystem->write(self::INDEX_FILE, json_encode(["rewrite" => "me"]));

        $this->invokePrivateMethod($fileSystem, "writeIndex", [self::INNER_INDEX_FILE, $expected]);
        $this->assertEquals(
            $expected,
            json_decode($fileSystem->read(self::INDEX_FILE), true)
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

    public function testWriteIndexDoesNotWorkWithInaccessibleIndexFile()
    {
        $tempDir = $this->getTemporaryDirectory();
        $fileSystem = $this->getFileSystem($tempDir);
        $fileSystem->write(self::INDEX_FILE, null);
        chmod("{$tempDir}/". self::INNER_INDEX_FILE, 0000);

        $this->setExpectedException(IndexWriteException::class);
        $this->invokePrivateMethod($fileSystem, "writeIndex", [self::INNER_INDEX_FILE, []]);
    }
}
