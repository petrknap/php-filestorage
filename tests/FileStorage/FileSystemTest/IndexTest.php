<?php

namespace PetrKnap\Php\FileStorage\Test\FileSystemTest;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Test\FileSystemTestCase;

class IndexTestCase extends FileSystemTestCase
{
    /**
     * @dataProvider getPathsToIndexFilesDataProvider
     * @param string[] $expectedPathsToIndexFiles
     * @param string $innerPath
     */
    public function testGetPathsToIndexFilesWorks($expectedPathsToIndexFiles, $innerPath)
    {
        $fileSystem = $this->getFileSystem($this->getTemporaryDirectory());

        $this->assertEquals(
            $expectedPathsToIndexFiles,
            $this->invokePrivateMethod($fileSystem, "getPathsToIndexFiles", [$innerPath])
        );
    }

    public function getPathsToIndexFilesDataProvider()
    {
        return [
            [
                [
                    FileSystem::PATH_TO_INDEXES . "/a/b/c/d/" . FileSystem::INDEX_FILE,
                    FileSystem::PATH_TO_INDEXES . "/a/b/c/" . FileSystem::INDEX_FILE,
                    FileSystem::PATH_TO_INDEXES . "/a/b/" . FileSystem::INDEX_FILE,
                    FileSystem::PATH_TO_INDEXES . "/a/" . FileSystem::INDEX_FILE
                ],
                "/a/b/c/d/e-f.g"
            ]
        ];
    }

    /**
     * @dataProvider addPathToIndexAndRemoveDataFromIndexDataProvider
     * @param string $root
     * @param string $pathToIndex
     * @param string $expectedIndexContent
     * @param string $unused
     * @param string $path
     * @param string $innerPath
     */
    public function testAddPathToIndexWorks($root, $pathToIndex, $expectedIndexContent, $unused, $path, $innerPath)
    {
        $fileSystem = $this->getFileSystem($root);
        $adapter = new Local($root);

        $this->invokePrivateMethod($fileSystem, "addPathToIndex", [$path, $innerPath]);

        $this->assertTrue($adapter->has($pathToIndex));
        $this->assertEquals($expectedIndexContent, $adapter->read($pathToIndex)["contents"]);
    }

    /**
     * @dataProvider addPathToIndexAndRemoveDataFromIndexDataProvider
     * @param $root
     * @param string $pathToIndex
     * @param string $initIndexContent
     * @param string $expectedIndexContent
     * @param string $path
     * @param string $innerPath
     */
    public function testRemovePathFromIndexWorks($root, $pathToIndex, $initIndexContent, $expectedIndexContent, $path, $innerPath)
    {
        $fileSystem = $this->getFileSystem($root);
        $adapter = new Local($root);
        if (!$adapter->has($pathToIndex)) {
            $adapter->write($pathToIndex, $initIndexContent, new Config());
        } else {
            $adapter->update($pathToIndex, $initIndexContent, new Config());
        }

        $this->invokePrivateMethod($fileSystem, "removePathFromIndex", [$path, $innerPath]);

        $this->assertEquals($expectedIndexContent, $adapter->read($pathToIndex)["contents"]);
    }

    public function addPathToIndexAndRemoveDataFromIndexDataProvider()
    {
        $root = $this->getTemporaryDirectory();
        $path = "/this is long filename.txt";
        $innerPath = "/a/b/c/d/e-f.g";
        return [
            [
                $root,
                FileSystem::PATH_TO_INDEXES . "/a/b/c/d/" . FileSystem::INDEX_FILE,
                '{"files":{"\/this is long filename.txt":1}}',
                '{"files":[]}',
                $path,
                $innerPath
            ],
            [
                $root,
                FileSystem::PATH_TO_INDEXES . "/a/b/c/" . FileSystem::INDEX_FILE,
                '{"files":{"\/this is lon":2}}',
                '{"files":{"\/this is lon":1}}',
                $path,
                $innerPath
            ],
            [
                $root,
                FileSystem::PATH_TO_INDEXES . "/a/b/" . FileSystem::INDEX_FILE,
                '{"files":{"\/this is":3}}',
                '{"files":{"\/this is":2}}',
                $path,
                $innerPath
            ],
            [
                $root,
                FileSystem::PATH_TO_INDEXES . "/a/" . FileSystem::INDEX_FILE,
                '{"files":{"\/thi":4}}',
                '{"files":{"\/thi":3}}',
                $path,
                $innerPath
            ]
        ];
    }
}
