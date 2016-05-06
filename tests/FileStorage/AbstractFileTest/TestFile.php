<?php

namespace PetrKnap\Php\FileStorage\Test\AbstractFileTest;

use PetrKnap\Php\FileStorage\AbstractFile;

class TestFile extends AbstractFile
{
    static private $storageDirectory;

    protected function getStorageDirectory()
    {
        if(!self::$storageDirectory) {
            throw new \Exception("Unknown storage directory.");
        }
        return self::$storageDirectory;
    }

    public static function setStorageDirectory($pathToDirectory)
    {
        self::$storageDirectory = $pathToDirectory;
    }

    public static function getClassName()
    {
        return __CLASS__;
    }
}
