<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin;

use League\Flysystem\Adapter\Local;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Plugin\SQLiteIndexPlugin;

class SQLiteIndexPluginTest extends AbstractIndexPluginTest
{
    /**
     * @inheritdoc
     */
    protected function getFileSystemWithIndexPlugin($directory)
    {
        $fileSystem = new FileSystem(new Local($directory));

        SQLiteIndexPlugin::register($fileSystem, "{$directory}/index.sqlite");

        return $fileSystem;
    }
}
