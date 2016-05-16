<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;
use PetrKnap\Php\FileStorage\Test\AbstractTestCase;

abstract class OnSiteIndexPluginTestCase extends AbstractTestCase
{
    protected static function getFileSystem($pathToTemporaryTestDir) {
        return new Filesystem(new Local($pathToTemporaryTestDir));
    }

    protected static function getPlugin(FilesystemInterface $fileSystem)
    {
        $plugin = new OnSiteIndexPlugin(null, $fileSystem);
        $plugin->setFilesystem($fileSystem);

        return $plugin;
    }
}
