<?php

namespace PetrKnap\Php\FileStorage\Test;

use League\Flysystem\Adapter\Local;
use PetrKnap\Php\FileStorage\FileSystem;

abstract class FileSystemTestCase extends AbstractTestCase
{
    protected static function getFileSystem($pathToTemporaryTestDir)
    {
        return new FileSystem(new Local($pathToTemporaryTestDir));
    }
}
