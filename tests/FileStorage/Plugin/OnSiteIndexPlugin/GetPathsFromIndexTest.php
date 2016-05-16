<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use League\Flysystem\FilesystemInterface;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;
use PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPluginTestCase;

class GetPathsFromIndexTest extends OnSiteIndexPluginTestCase
{
    /**
     * @var FilesystemInterface
     */
    private static $fileSystem;

    /**
     * @var OnSiteIndexPlugin
     */
    private static $plugin;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$fileSystem = self::getFileSystem(self::getTemporaryDirectory());
        self::$plugin = self::getPlugin(self::$fileSystem);

        for ($d1 = 0; $d1 < 20; $d1++) {
            for ($f = 0; $f < 2; $f++) {
                $path = "/Directory #{$d1}/File #{$f}.txt";
                self::$fileSystem->write($path, "This is file!");
                self::invokePrivateMethod(self::$plugin, "addPathToIndex", [$path, $path]);
                for ($d2 = 0; $d2 < 5; $d2++) {
                    $path = "/Directory #{$d1}/Directory #{$d2}/File #{$f}.txt";
                    self::$fileSystem->write($path, "This is file!");
                    self::invokePrivateMethod(self::$plugin, "addPathToIndex", [$path, $path]);
                }
            }
        }
    }

    public function testGetPathsFromIndexBackwardWorks()
    {
        $files = $this->invokePrivateMethod(
            self::$plugin,
            "getPathsFromIndexBackward",
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
     * @dataProvider getPathsFromIndexForwardWorksDataAdapter
     * @param string $directory
     * @param bool $recursive
     * @param string $dirnamePattern
     * @param int $countOfFiles
     */
    public function testGetPathsFromIndexForwardWorks($directory, $recursive, $dirnamePattern, $countOfFiles)
    {
        $files = $this->invokePrivateMethod(
            self::$plugin,
            "getPathsFromIndexForward",
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

    public function getPathsFromIndexForwardWorksDataAdapter()
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

    public function testGetPathsFromIndexWorks()
    {
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());
        $plugin = $this->getPlugin($fileSystem);
        for ($i = 0; $i < 5; $i++) {
            $path = "/File #{$i}.txt";
            $fileSystem->write($path, null);
            $plugin->addPathToIndex($path, $path);
        }

        $files = $plugin->getPathsFromIndex("", false);

        $this->assertInternalType("array", $files);
        $this->assertCount(5, $files);
    }
}
