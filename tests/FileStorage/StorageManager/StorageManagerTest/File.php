<?php

namespace PetrKnap\Php\FileStorage\Test\StorageManager\StorageManagerTest;

use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManager\StorageManager;

class File implements FileInterface
{
    /**
     * @var StorageManager
     */
    private $storageManager;

    /**
     * @var string
     */
    private $pathToFile;

    /**
     * @var bool
     */
    private $exists = false;

    /**
     * @param StorageManager $storageManager
     * @param string $pathToFile
     */
    public function __construct(StorageManager $storageManager, $pathToFile)
    {
        $this->storageManager = $storageManager;
        $this->pathToFile = $pathToFile;
    }

    /**
     * @inheritdoc
     */
    public function getPathToFile()
    {
        return $this->pathToFile;
    }

    /**
     * @inheritdoc
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * @inheritdoc
     */
    public function create()
    {
        $this->exists = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        throw new \Exception("Not implemented");
    }

    /**
     * @inheritdoc
     */
    public function write($data, $append = false)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->exists = false;

        return $this;
    }
}
