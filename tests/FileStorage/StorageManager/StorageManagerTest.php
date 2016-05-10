<?php

namespace PetrKnap\Php\FileStorage\Test\StorageManager;

use PetrKnap\Php\FileStorage\StorageManager\Exception\AssignException;
use PetrKnap\Php\FileStorage\StorageManager\StorageManager;
use PetrKnap\Php\FileStorage\Test\StorageManager\StorageManagerTest\File;
use PetrKnap\Php\FileStorage\Test\TestCase;

class StorageManagerTest extends TestCase
{
    /**
     * @return StorageManager
     */
    private function getStorageManager()
    {
        return new StorageManager($this->getTemporaryDirectory(), 0666);
    }

    /**
     * @param StorageManager $storageManager
     * @return File
     */
    private function getFile(StorageManager $storageManager)
    {
        static $countOfFiles = 0;

        return new File($storageManager, "/file_" . ($countOfFiles++) . ".test");
    }

    /**
     * @param mixed $input
     * @return array
     */
    private function toArray($input)
    {
        $output = [];
        foreach ($input as $item) {
            $output[] = $item;
        }
        return $output;
    }

    public function testCanGetPathToStorage()
    {
        $storageBasePath = $this->getTemporaryDirectory();
        $storageManager = new StorageManager($storageBasePath);

        $this->assertEquals($storageBasePath, $storageManager->getPathToStorage());
    }

    public function testCanGetStoragePermissions()
    {
        $storageBasePermissions = 0654;
        $storageManager = new StorageManager($this->getTemporaryDirectory(), $storageBasePermissions);

        $this->assertEquals($storageBasePermissions, $storageManager->getStoragePermissions());
    }

    public function testCanAssignFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $file->create();
        $storageManager->assignFile($file);
        $this->assertCount(1, $this->toArray($storageManager->getFiles()));

        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->setExpectedException(AssignException::class);
        $storageManager->assignFile($file);
    }

    public function testCanUnassignFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());

        $storageManager->unassignFile($file);
        $this->assertCount(0, $this->toArray($storageManager->getFiles()));
    }
}
