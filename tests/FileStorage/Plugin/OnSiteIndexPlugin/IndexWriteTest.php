<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use PetrKnap\Php\FileStorage\Exception\IndexWriteException;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;
use PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPluginTestCase;

class IndexWriteTest extends OnSiteIndexPluginTestCase
{
    const INDEX_FILE = OnSiteIndexPlugin::INDEX_FILE;

    public function testWriteIndexWorksWithExistentIndexFile()
    {
        $expected = ["key" => "value"];
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());
        $plugin = $this->getPlugin($fileSystem);
        $fileSystem->write(self::INDEX_FILE, json_encode(["rewrite" => "me"]));

        $this->invokePrivateMethod($plugin, "writeIndex", [self::INDEX_FILE, $expected]);
        $this->assertEquals(
            $expected,
            json_decode($fileSystem->read(self::INDEX_FILE), true)
        );
    }

    public function testReadIndexWorksWithNonexistentIndexFile()
    {
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());
        $plugin = $this->getPlugin($fileSystem);

        $this->invokePrivateMethod($plugin, "writeIndex", [self::INDEX_FILE, ["te" => "st"]]);
        $this->assertEquals(
            '{"te":"st"}',
            $fileSystem->read(self::INDEX_FILE)
        );
    }

    public function testWriteIndexDoesNotWorkWithInaccessibleIndexFile()
    {
        $tempDir = $this->getTemporaryDirectory();
        $fileSystem = $this->getFileSystem($tempDir);
        $plugin = $this->getPlugin($fileSystem);

        $fileSystem->write(self::INDEX_FILE, null);
        chmod("{$tempDir}/". self::INDEX_FILE, 0000);
        $this->setExpectedException(IndexWriteException::class);
        $this->invokePrivateMethod($plugin, "writeIndex", [self::INDEX_FILE, []]);
    }
}
