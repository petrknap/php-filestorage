<?php

namespace PetrKnap\Php\FileStorage\Test\FileSystemTest;

use PetrKnap\Php\FileStorage\Test\FileSystemTestCase;

class ListContentsTest extends FileSystemTestCase
{
    private static $fileSystem;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$fileSystem = self::getFileSystem(self::getTemporaryDirectory());

        for ($d1 = 0; $d1 < 20; $d1++) {
            for ($f = 0; $f < 2; $f++) {
                self::$fileSystem->write(
                    "/Directory #{$d1}/File #{$f}.txt",
                    "This is file!"
                );
                for ($d2 = 0; $d2 < 5; $d2++) {
                    self::$fileSystem->write(
                        "/Directory #{$d1}/Directory #{$d2}/File #{$f}.txt",
                        "This is file!"
                    );
                }
            }
        }
    }

    public function testListContentsBackwardWorks()
    {
        $files = $this->invokePrivateMethod(
            self::$fileSystem,
            "listContentsBackward",
            [
                "/",
                true
            ]
        );
        $fileCounter = 0;
        foreach($files as $file) {
            $this->assertEquals("file", $file["type"]);
            $this->assertStringMatchesFormat("/Directory #%s/File #%d.txt", $file["path"]);
            $this->assertEquals(time(), $file["timestamp"], "", 120);
            $this->assertEquals(13, $file["size"]);
            $this->assertStringMatchesFormat("/Directory #%s", $file["dirname"]);
            $this->assertStringMatchesFormat("File #%d.txt", $file["basename"]);
            $this->assertEquals("txt", $file["extension"]);
            $this->assertStringMatchesFormat("File #%d", $file["filename"]);
            $fileCounter++;
        }
        $this->assertEquals(240, $fileCounter);
    }

    /**
     * @dataProvider listContentsForwardWorksDataAdapter
     * @param string $directory
     * @param bool $recursive
     * @param string $dirnamePattern
     * @param int $countOfFiles
     */
    public function testListContentsForwardWorks($directory, $recursive, $dirnamePattern, $countOfFiles)
    {
        $files = $this->invokePrivateMethod(
            self::$fileSystem,
            "listContentsForward",
            [
                $directory,
                $recursive
            ]
        );
        $fileCounter = 0;
        foreach ($files as $file) {
            $this->assertEquals("file", $file["type"]);
            $this->assertStringMatchesFormat("$dirnamePattern/File #%d.txt", $file["path"]);
            $this->assertEquals(time(), $file["timestamp"], "", 120);
            $this->assertEquals(13, $file["size"]);
            $this->assertStringMatchesFormat("$dirnamePattern", $file["dirname"]);
            $this->assertStringMatchesFormat("File #%d.txt", $file["basename"]);
            $this->assertEquals("txt", $file["extension"]);
            $this->assertStringMatchesFormat("File #%d", $file["filename"]);
            $fileCounter++;
        }
        $this->assertEquals($countOfFiles, $fileCounter);
    }

    public function listContentsForwardWorksDataAdapter()
    {
        return [
            ["/", false, "/Directory #%d", 0],
            ["/", true, "/Directory #%s", 240],
            ["/Directory #1", false, "/Directory #%d", 2],
            ["/Directory #1", true, "/Directory #%s", 12],
            ["/Directory #1/Directory #1", false, "/Directory #1/Directory #%d", 2],
            ["/Directory #1/Directory #1", true, "/Directory #1/Directory #%s", 2],
            ["/Nonexistent directory", false, "", 0],
            ["/Nonexistent directory", true, "", 0]
        ];
    }

    public function testListContentsWorks()
    {
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());
        $fileSystem->write("/File #1.txt", null);
        $fileSystem->write("/File #2.txt", null);

        $files = $fileSystem->listContents();

        $this->assertInternalType("array", $files);
        $this->assertCount(2, $files);
    }
}
