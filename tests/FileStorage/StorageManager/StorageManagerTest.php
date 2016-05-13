<?php

namespace PetrKnap\Php\FileStorage\Test\StorageManager;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
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
     * @return Filesystem
     */
    private function getLocalFilesystem()
    {
        return new Filesystem(new Local($this->getTemporaryDirectory()));
    }

    /**
     * @param Filesystem $filesystem
     * @return StorageManager
     */
    private function getStorageManager(Filesystem $filesystem)
    {
        return new StorageManager($filesystem);
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

    public function testAssignFileWorks()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $file->create();

        $storageManager->assignFile($file);
        $this->assertCount(1, $this->toArray($storageManager->getFiles()));
    }

    public function testAssignFileWorksWithNonexistentFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $this->setExpectedException(AssignException::class);
        $storageManager->assignFile($file);
    }

    public function testAssignFileWorksWithInaccessibleStorage()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $file->create();
        /** @noinspection PhpUndefinedMethodInspection */
        mkdir(dirname($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))), 0777, true);
        /** @noinspection PhpUndefinedMethodInspection */
        chmod(dirname($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))), 0111);

        $this->setExpectedException(IndexWriteException::class);
        $storageManager->assignFile($file);
    }

    public function testUnassignFileWorks()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());

        $storageManager->unassignFile($file);
        $this->assertCount(0, $this->toArray($storageManager->getFiles()));
    }

    public function testUnassignFileWorksWithInaccessibleStorage()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());
        /** @noinspection PhpUndefinedMethodInspection */
        chmod(dirname($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))) . "/" . StorageManager::INDEX_FILE, 0000);

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
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
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
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $storageManager->assignFile($file->create());
        /** @noinspection PhpUndefinedMethodInspection */
        file_put_contents(dirname($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))) . "/" . StorageManager::INDEX_FILE, "");

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
