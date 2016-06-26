<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin;

use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Test\AbstractTestCase;

abstract class AbstractIndexPluginTest extends AbstractTestCase
{
    private function assertArrayEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10)
    {
        $this->assertEquals($expected, $actual, $message, $delta, $maxDepth, true);
    }

    /**
     * @param string $directory
     * @return FileSystem
     */
    abstract protected function getFileSystemWithIndexPlugin($directory);

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    public function setUp()
    {
        parent::setUp();
        $this->tempDir = $this->getTemporaryDirectory();
        $this->fileSystem = $this->getFileSystemWithIndexPlugin($this->tempDir);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function indexContainsPath($path)
    {
        foreach ($this->fileSystem->listContents(dirname($path), false) as $metadata)
        {
            if ($metadata["path"] == $path) {
                return true;
            }
        }
        return false;
    }

    /**
     * @dataProvider dataPaths
     * @param string $path
     */
    public function testAddPathToIndexWorks($path)
    {
        $this->fileSystem->write($path, null);
        $this->assertTrue($this->indexContainsPath($path));
    }

    /**
     * @dataProvider dataPaths
     * @param string $path
     */
    public function testRemovePathFromIndexWorks($path)
    {
        $this->testAddPathToIndexWorks($path);
        $this->fileSystem->delete($path);
        $this->assertFalse($this->indexContainsPath($path));
    }

    /**
     * @dataProvider dataGetMetadataFromIndexWorks
     * @param FileSystem $fileSystem
     * @param string $directory
     * @param bool $recursive
     * @param array $expected
     */
    public function testGetMetadataFromIndexWorks($fileSystem, $directory, $recursive, $expected)
    {
        $this->assertArrayEquals($expected, array_map(function ($v) {
            return $v["path"];
        }, $fileSystem->listContents($directory, $recursive)));
    }

    public function dataGetMetadataFromIndexWorks()
    {
        $fileSystem = $this->getFileSystemWithIndexPlugin($this->getTemporaryDirectory());
        foreach ($this->dataPaths() as $path) {
            $fileSystem->write($path[0], null);
        }
        return [
            [$fileSystem, "/", true, ["/file.ext", "/dir/file.ext", "/dir/sub-dir/file.ext"]],
            [$fileSystem, "/", false, ["/file.ext"]],
            [$fileSystem, "/dir", true, ["/dir/file.ext", "/dir/sub-dir/file.ext"]],
            [$fileSystem, "/dir", false, ["/dir/file.ext"]],
            [$fileSystem, "/dir/sub-dir", true, ["/dir/sub-dir/file.ext"]],
            [$fileSystem, "/dir/sub-dir", false, ["/dir/sub-dir/file.ext"]]
        ];
    }

    public function dataPaths()
    {
        return [
            ["/file.ext"],
            ["/dir/file.ext"],
            ["/dir/sub-dir/file.ext"]
        ];
    }
}
