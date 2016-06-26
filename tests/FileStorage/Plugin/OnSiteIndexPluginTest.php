<?php

namespace PetrKnap\Php\FileStorage\Test\Plugin;

use League\Flysystem\Adapter\Local;
use PetrKnap\Php\FileStorage\FileSystem;

class OnSiteIndexPluginTest extends AbstractIndexPluginTest
{
    /**
     * @inheritdoc
     */
    protected function getFileSystemWithIndexPlugin($directory)
    {
        return new FileSystem(new Local($directory));
    }
}
