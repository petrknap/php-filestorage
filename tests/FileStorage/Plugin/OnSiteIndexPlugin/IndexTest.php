<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;

class IndexTest extends OnSiteIndexPluginTestCase
{
    /**
     * @dataProvider getPathsToIndexFilesDataProvider
     * @param string[] $expectedPathsToIndexFiles
     * @param string $innerPath
     */
    public function testGetPathsToIndexFilesWorks($expectedPathsToIndexFiles, $innerPath)
    {
        $adapter = $this->getAdapter($this->getTemporaryDirectory());
        $plugin = $this->getPlugin($adapter);

        $this->assertEquals(
            $expectedPathsToIndexFiles,
            $this->invokePrivateMethod($plugin, "getPathsToIndexFiles", [$innerPath])
        );
    }

    public function getPathsToIndexFilesDataProvider()
    {
        return [
            [
                [
                    OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/b/c/d/" . OnSiteIndexPlugin::INDEX_FILE,
                    OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/b/c/" . OnSiteIndexPlugin::INDEX_FILE,
                    OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/b/" . OnSiteIndexPlugin::INDEX_FILE,
                    OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/" . OnSiteIndexPlugin::INDEX_FILE
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
        $adapter = $this->getAdapter($root);
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);

        $plugin->addPathToIndex($path, $innerPath);

        $this->assertTrue($innerFileSystem->has($pathToIndex));
        $this->assertEquals($expectedIndexContent, $innerFileSystem->read($pathToIndex));
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
        $adapter = $this->getAdapter($root);
        $innerFileSystem = $this->getInnerFileSystem($adapter);
        $plugin = $this->getPlugin($adapter);
        if (!$innerFileSystem->has($pathToIndex)) {
            $innerFileSystem->write($pathToIndex, $initIndexContent);
        } else {
            $innerFileSystem->update($pathToIndex, $initIndexContent);
        }

        $plugin->removePathFromIndex($path, $innerPath);

        $this->assertEquals($expectedIndexContent, $innerFileSystem->read($pathToIndex));
    }

    public function addPathToIndexAndRemoveDataFromIndexDataProvider()
    {
        $root = $this->getTemporaryDirectory();
        $path = "/this is long filename.txt";
        $innerPath = "/a/b/c/d/e-f.g";
        return [
            [
                $root,
                OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/b/c/d/" . OnSiteIndexPlugin::INDEX_FILE,
                '{"files":{"\/this is long filename.txt":1}}',
                '{"files":[]}',
                $path,
                $innerPath
            ],
            [
                $root,
                OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/b/c/" . OnSiteIndexPlugin::INDEX_FILE,
                '{"files":{"\/this is lon":2}}',
                '{"files":{"\/this is lon":1}}',
                $path,
                $innerPath
            ],
            [
                $root,
                OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/b/" . OnSiteIndexPlugin::INDEX_FILE,
                '{"files":{"\/this is":3}}',
                '{"files":{"\/this is":2}}',
                $path,
                $innerPath
            ],
            [
                $root,
                OnSiteIndexPlugin::PATH_TO_INDEXES . "/a/" . OnSiteIndexPlugin::INDEX_FILE,
                '{"files":{"\/thi":4}}',
                '{"files":{"\/thi":3}}',
                $path,
                $innerPath
            ]
        ];
    }
}
