<?php

namespace PetrKnap\Php\FileStorage\Test\StorageManager;

use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManager\Exception\AssignException;
use PetrKnap\Php\FileStorage\StorageManager\Exception\IndexDecodeException;
use PetrKnap\Php\FileStorage\StorageManager\Exception\IndexReadException;
use PetrKnap\Php\FileStorage\StorageManager\Exception\IndexWriteException;
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

    public function testGetStoragePermissionsWorks()
    {
        $storageBasePermissions = 0654;
        $storageManager = new StorageManager($this->getTemporaryDirectory(), $storageBasePermissions);

        $this->assertEquals($storageBasePermissions, $storageManager->getStoragePermissions());
    }

    public function testAssignFileWorks()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $file->create();

        $storageManager->assignFile($file);
        $this->assertCount(1, $this->toArray($storageManager->getFiles()));
    }

    public function testAssignFileWorksWithNonexistentFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->setExpectedException(AssignException::class);
        $storageManager->assignFile($file);
    }

    public function testAssignFileWorksWithInaccessibleStorage()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $file->create();
        mkdir(dirname($storageManager->getPathToFile($file)), 0777, true);
        chmod(dirname($storageManager->getPathToFile($file)), 0111);

        $this->setExpectedException(IndexWriteException::class);
        $storageManager->assignFile($file);
    }

    public function testUnassignFileWorks()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());

        $storageManager->unassignFile($file);
        $this->assertCount(0, $this->toArray($storageManager->getFiles()));
    }

    public function testUnassignFileWorksWithInaccessibleStorage()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());
        chmod(dirname($storageManager->getPathToFile($file)) . "/" . StorageManager::INDEX_FILE, 0000);

        $this->setExpectedException(IndexReadException::class);
        $storageManager->unassignFile($file);
    }

    /**
     * @dataProvider countOfFilesDataProvider
     *
     * @param int $countOfFiles
     */
    public function testGetFilesWorks($countOfFiles)
    {
        $storageManager = $this->getStorageManager();
        for ($i = 0; $i < $countOfFiles; $i++) {
            $file = $this->getFile($storageManager);
            $storageManager->assignFile($file->create());
        }

        $counter = 0;
        foreach ($storageManager->getFiles() as $file) {
            $counter++;
            $this->assertInstanceOf(FileInterface::class, $file);
        }
        $this->assertEquals($countOfFiles, $counter);
    }

    public function testGetFilesWorksWithCorruptedIndexFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());
        file_put_contents(dirname($storageManager->getPathToFile($file)) . "/" . StorageManager::INDEX_FILE, "");

        $this->setExpectedException(IndexDecodeException::class);
        $storageManager->getFiles()->current();
    }

    public function countOfFilesDataProvider()
    {
        return [
            [0], [1], [2], [3], [5], [8], [13], [21]
        ];
    }
}
