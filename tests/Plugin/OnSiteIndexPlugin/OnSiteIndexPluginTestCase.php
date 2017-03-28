<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin\OnSiteIndexPlugin;

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem as FlyFileSystem;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;
use PetrKnap\Php\FileStorage\Test\AbstractTestCase;

abstract class OnSiteIndexPluginTestCase extends AbstractTestCase
{
    protected static function getAdapter($root)
    {
        return new Local($root);
    }

    protected static function getInnerFileSystem(AdapterInterface $adapter)
    {
        return new FlyFileSystem($adapter);
    }

    protected static function getOuterFileSystem(AdapterInterface $adapter)
    {
        return new FileSystem($adapter);
    }

    protected static function getPlugin(AdapterInterface $adapter)
    {
        $plugin = new OnSiteIndexPlugin(null, self::getInnerFileSystem($adapter));
        $plugin->setFilesystem(self::getOuterFileSystem($adapter));

        return $plugin;
    }
}
