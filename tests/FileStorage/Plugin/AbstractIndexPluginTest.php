<?php

namespace PetrKnap\Php\FileStorage\Test;

use PetrKnap\Php\FileStorage\FileSystem;

abstract class AbstractIndexPluginTest extends AbstractTestCase
{
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

    public function testGetMetadataFromIndexWorks()
    {
        $expected = [];
        foreach ($this->dataPaths() as $path) {
            $this->fileSystem->write($path[0], null);
            $expected[] = $path[0];
        }

        $actual = [];
        foreach ($this->fileSystem->listContents("/", true) as $content) {
            $actual[] = $content["path"];
        }

        $this->assertEquals($expected, $actual, "", 0, 0, true);
    }

    public function dataPaths()
    {
        foreach (["/file.ext", "/dir/file.ext", "/dir/sub-dir/file.ext"] as $path) {
            yield [$path];
        }
    }
}
