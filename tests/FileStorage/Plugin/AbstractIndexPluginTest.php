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
     * @var FileSystem
     */
    private $fileSystem;

    public function setUp()
    {
        parent::setUp();
        $this->fileSystem = $this->getFileSystemWithIndexPlugin($this->getTemporaryDirectory());
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
     * @param array $files
     * @param string $directory
     * @param bool $recursive
     * @param array $expected
     */
    public function testGetMetadataFromIndexWorks($files, $directory, $recursive, $expected)
    {
        foreach ($files as $file) {
            $this->fileSystem->write($file, null);
        }
        $this->assertArrayEquals($expected, array_map(function ($v) {
            return $v["path"];
        }, $this->fileSystem->listContents($directory, $recursive)));
    }

    public function dataGetMetadataFromIndexWorks()
    {
        $files = ["/file.ext", "/dir/file.ext", "/dir/sub-dir/file.ext"];
        return [
            [$files, "/", true, ["/file.ext", "/dir/file.ext", "/dir/sub-dir/file.ext"]],
            [$files, "/", false, ["/file.ext"]],
            [$files, "/dir", true, ["/dir/file.ext", "/dir/sub-dir/file.ext"]],
            [$files, "/dir", false, ["/dir/file.ext"]],
            [$files, "/dir/sub-dir", true, ["/dir/sub-dir/file.ext"]],
            [$files, "/dir/sub-dir", false, ["/dir/sub-dir/file.ext"]]
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
