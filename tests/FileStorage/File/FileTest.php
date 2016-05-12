<?php

namespace PetrKnap\Php\FileStorage\Test\File;

use PetrKnap\Php\FileStorage\File\Exception\FileAccessException;
use PetrKnap\Php\FileStorage\File\Exception\FileExistsException;
use PetrKnap\Php\FileStorage\File\Exception\FileNotFoundException;
use PetrKnap\Php\FileStorage\File\File;
use PetrKnap\Php\FileStorage\Test\File\FileTest\StorageManager;
use PetrKnap\Php\FileStorage\Test\TestCase;

class FileTest extends TestCase
{
    /**
     * @return StorageManager
     */
    public function getStorageManager()
    {
        return new StorageManager($this->getTemporaryDirectory());
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
     * @param StorageManager $storageManager
     * @return File
     */
    private function getInaccessibleFile(StorageManager $storageManager) {
        $file = $this->getFile($storageManager)->create();

        $this->assertTrue(chmod($storageManager->getPathToFile($file), 0000));

        return $file;
    }

    public function testGetPathToFileWorks()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->assertStringMatchesFormat("/file_%d.test", $file->getPath());
    }

    public function testExistsWorks()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->assertFalse($file->exists());

        $realPath = $storageManager->getPathToFile($file);
        @mkdir(dirname($realPath), $storageManager->getStoragePermissions() + 0111, true);
        touch($realPath);
        $this->assertTrue($file->exists());
    }

    public function testCreateWorks()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $file->create();
        $this->assertFileExists($storageManager->getPathToFile($file));
    }

    public function testCreateWorksWithExistentFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $file->create();

        $this->setExpectedException(FileExistsException::class);
        $file->create();
    }

    public function testCreateWorksWithInaccessibleStorage()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->assertTrue(mkdir(dirname($storageManager->getPathToFile($file)), 0111, true));

        $this->setExpectedException(FileAccessException::class);
        $file->create();
    }

    /**
     * @dataProvider contentDataProvider
     * @param mixed $content
     */
    public function testReadWorks($content)
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $file->create();

        file_put_contents($storageManager->getPathToFile($file), $content);
        $this->assertEquals($content, $file->read());
    }

    public function testReadWorksWithNonexistentFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->setExpectedException(FileNotFoundException::class);
        $file->read();
    }

    public function testReadWorksWithInaccessibleFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getInaccessibleFile($storageManager);

        $this->setExpectedException(FileAccessException::class);
        $file->read();
    }

    /**
     * @dataProvider contentDataProvider
     * @param mixed $content
     */
    public function testWriteWorks($content)
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);
        $file->create();

        $file->write($content);
        $this->assertEquals($content, file_get_contents($storageManager->getPathToFile($file)));

        $file->write($content, true);
        $this->assertEquals($content . $content, file_get_contents($storageManager->getPathToFile($file)));

        $file->write($content, false);
        $this->assertEquals($content, file_get_contents($storageManager->getPathToFile($file)));
    }

    public function testWriteWorksWithNonexistentFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->setExpectedException(FileNotFoundException::class);
        $file->write(null);
    }

    public function testWriteWorksWithInaccessibleFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getInaccessibleFile($storageManager);

        $this->setExpectedException(FileAccessException::class);
        $file->write(null);
    }

    public function testDeleteWorks()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $realPath = $storageManager->getPathToFile($file);
        @mkdir(dirname($realPath), $storageManager->getStoragePermissions() + 0111, true);
        touch($realPath);

        $file->delete();
        $this->assertFileNotExists($realPath);
    }

    public function testDeleteWorksWithNonexistentFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getFile($storageManager);

        $this->setExpectedException(FileNotFoundException::class);
        $file->delete();
    }

    public function testDeleteWorksWithInaccessibleFile()
    {
        $storageManager = $this->getStorageManager();
        $file = $this->getInaccessibleFile($storageManager);
        chmod(dirname($storageManager->getPathToFile($file)), 0111);

        $this->setExpectedException(FileAccessException::class);
        $file->delete();
    }

    public function contentDataProvider()
    {
        return [
            [null], [true], [2], ["string"], [0b10101100]
        ];
    }
}
