<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use League\Flysystem\AdapterInterface;

class GetMetadataFromIndexTest extends OnSiteIndexPluginTestCase
{
    /**
     * @var AdapterInterface
     */
    private static $adapter;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$adapter = self::getAdapter(self::getTemporaryDirectory());

        $outerFileSystem = self::getOuterFileSystem(self::$adapter);

        for ($d1 = 0; $d1 < 20; $d1++) {
            for ($f = 0; $f < 2; $f++) {
                $path = "/Directory #{$d1}/File #{$f}.txt";
                $outerFileSystem->write($path, "This is file!");
                for ($d2 = 0; $d2 < 5; $d2++) {
                    $path = "/Directory #{$d1}/Directory #{$d2}/File #{$f}.txt";
                    $outerFileSystem->write($path, "This is file!");
                }
            }
        }
    }

    public function testGetMetadataFromIndexBackwardWorks()
    {
        $files = $this->invokePrivateMethod(
            $this->getPlugin(self::$adapter),
            "getMetadataFromIndexBackward",
            [
                "/",
                true
            ]
        );
        $fileCounter = 0;
        foreach ($files as $file) {
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
     * @dataProvider getMetadataFromIndexForwardWorksDataAdapter
     * @param string $directory
     * @param bool $recursive
     * @param string $dirnamePattern
     * @param int $countOfFiles
     */
    public function testGetMetadataFromIndexForwardWorks($directory, $recursive, $dirnamePattern, $countOfFiles)
    {
        $files = $this->invokePrivateMethod(
            $this->getPlugin(self::$adapter),
            "getMetadataFromIndexForward",
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

    public function getMetadataFromIndexForwardWorksDataAdapter()
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

    public function testGetMetadataFromIndexWorks()
    {
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $outerFileSystem = $this->getOuterFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);
        for ($i = 0; $i < 5; $i++) {
            $path = "/File #{$i}.txt";
            $outerFileSystem->write($path, null);
        }

        $files = $plugin->getMetadataFromIndex("/", false);
        $this->assertInternalType("array", $files);
        $this->assertCount(5, $files);

        $files = $plugin->getMetadataFromIndex("/", true);
        $this->assertInternalType("array", $files);
        $this->assertCount(5, $files);
    }
}
