<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use PetrKnap\Php\FileStorage\Plugin\Exception\IndexDecodeException;
use PetrKnap\Php\FileStorage\Plugin\Exception\IndexReadException;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;

class IndexReadTest extends OnSiteIndexPluginTestCase
{
    const INDEX_FILE = OnSiteIndexPlugin::INDEX_FILE;

    public function testReadIndexWorksWithExistentIndexFile()
    {
        $expected = ["key" => "value"];
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);

        $innerFileSystem->write(self::INDEX_FILE, json_encode($expected));
        $this->assertEquals(
            $expected,
            $this->invokePrivateMethod($plugin, "readIndex", [self::INDEX_FILE])
        );
    }

    public function testReadIndexWorksWithNonexistentIndexFile()
    {
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $plugin = $this->getPlugin($adapter);

        $this->assertEquals(
            [],
            $this->invokePrivateMethod($plugin, "readIndex", [self::INDEX_FILE])
        );
    }

    public function testReadIndexDoesNotWorkWithCorruptedIndexFile()
    {
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);

        $innerFileSystem->write(self::INDEX_FILE, null);
        $this->setExpectedException(IndexDecodeException::class);
        $this->invokePrivateMethod($plugin, "readIndex", [self::INDEX_FILE]);
    }

    public function testReadIndexDoesNotWorkWithInaccessibleIndexFile()
    {
        $tempDir = $this->getTemporaryDirectory();
        $adapter = $this->getAdapter($tempDir);
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);

        $innerFileSystem->write(self::INDEX_FILE, null);
        chmod("{$tempDir}/" . self::INDEX_FILE, 0000);
        $this->setExpectedException(IndexReadException::class);
        $this->invokePrivateMethod($plugin, "readIndex", [self::INDEX_FILE]);
    }
}
