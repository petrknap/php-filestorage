<?php

namespace PetrKnap\Php\FileStorage\Test\File\FileTest;

use League\Flysystem\FilesystemInterface;
use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManagerInterface;
use PetrKnap\Php\FileStorage\VisibilityEnum;

class StorageManager implements StorageManagerInterface
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @param FilesystemInterface $filesystem
     */
    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultVisibility()
    {
        return VisibilityEnum::VISIBLE();
    }

    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * @inheritdoc
     */
    public function getPathToFile(FileInterface $file)
    {
        return DIRECTORY_SEPARATOR . sha1($file->getPath());
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
