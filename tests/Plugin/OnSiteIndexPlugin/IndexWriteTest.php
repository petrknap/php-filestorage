<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use PetrKnap\Php\FileStorage\Plugin\Exception\IndexWriteException;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;

class IndexWriteTest extends OnSiteIndexPluginTestCase
{
    const INDEX_FILE = OnSiteIndexPlugin::INDEX_FILE;

    public function testWriteIndexWorksWithExistentIndexFile()
    {
        $expected = ["key" => "value"];
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);
        $innerFileSystem->write(self::INDEX_FILE, json_encode(["rewrite" => "me"]));

        $this->invokePrivateMethod($plugin, "writeIndex", [self::INDEX_FILE, $expected]);
        $this->assertEquals(
            $expected,
            json_decode($innerFileSystem->read(self::INDEX_FILE), true)
        );
    }

    public function testReadIndexWorksWithNonexistentIndexFile()
    {
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);

        $this->invokePrivateMethod($plugin, "writeIndex", [self::INDEX_FILE, ["te" => "st"]]);
        $this->assertEquals(
            '{"te":"st"}',
            $innerFileSystem->read(self::INDEX_FILE)
        );
    }

    public function testWriteIndexDoesNotWorkWithInaccessibleIndexFile()
    {
        $tempDir = $this->getTemporaryDirectory();
        $innerFileSystem = $this->getInnerFileSystem($this->getAdapter($tempDir));
        $innerFileSystem->write(self::INDEX_FILE, null);
        $plugin = $this->getPlugin($this->getAdapter("/mnt/read-only/{$tempDir}"));

        $this->setExpectedException(IndexWriteException::class);
        $this->invokePrivateMethod($plugin, "writeIndex", [self::INDEX_FILE, []]);
    }
}
