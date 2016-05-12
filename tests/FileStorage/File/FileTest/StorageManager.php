<?php

namespace PetrKnap\Php\FileStorage\Test\File\FileTest;

use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManagerInterface;

class StorageManager implements StorageManagerInterface
{
    /**
     * @var string
     */
    private $pathToStorage;

    /**
     * @param string $pathToStorage
     */
    public function __construct($pathToStorage)
    {
        $this->pathToStorage = $pathToStorage;
    }

    /**
     * @inheritdoc
     */
    public function getStoragePermissions()
    {
        return 0666;
    }

    /**
     * @inheritdoc
     */
    public function getPathToFile(FileInterface $file)
    {
        return $this->pathToStorage . DIRECTORY_SEPARATOR . sha1($file->getPath());
    }

    /**
     * @inheritdoc
     */
    public function assignFile(FileInterface $file)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unassignFile(FileInterface $file)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFiles()
    {
        throw new \Exception("Not implemented");
    }
}
