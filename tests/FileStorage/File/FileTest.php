<?php

namespace PetrKnap\Php\FileStorage\Test\File;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use PetrKnap\Php\FileStorage\File\Exception\FileAccessException;
use PetrKnap\Php\FileStorage\File\Exception\FileExistsException;
use PetrKnap\Php\FileStorage\File\Exception\FileNotFoundException;
use PetrKnap\Php\FileStorage\File\File;
use PetrKnap\Php\FileStorage\Test\File\FileTest\StorageManager;
use PetrKnap\Php\FileStorage\Test\TestCase;

class FileTest extends TestCase
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
     * @param StorageManager $storageManager
     * @return File
     */
    private function getInaccessibleFile(StorageManager $storageManager) {
        $file = $this->getFile($storageManager)->create();

        /** @noinspection PhpUndefinedMethodInspection */
        chmod(
            $storageManager->getFilesystem()->getAdapter()
                ->applyPathPrefix($storageManager->getPathToFile($file)),
            0000
        );

        return $file;
    }

    public function testGetPathToFileWorks()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $this->assertStringMatchesFormat("/file_%d.test", $file->getPath());
    }

    public function testExistsWorks()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $this->assertFalse($file->exists());

        /** @noinspection PhpUndefinedMethodInspection */
        $realPath = $filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file));
        @mkdir(dirname($realPath), 0777, true);
        touch($realPath);
        $this->assertTrue($file->exists());
    }

    public function testCreateWorks()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $file->create();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertFileExists($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file)));
    }

    public function testCreateWorksWithExistentFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $file->create();

        $this->setExpectedException(FileExistsException::class);
        $file->create();
    }

    public function testCreateWorksWithInaccessibleStorage()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        /** @noinspection PhpUndefinedMethodInspection */
        chmod(dirname($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))), 0111);

        $this->setExpectedException(FileAccessException::class);
        $file->create();
    }

    /**
     * @dataProvider contentDataProvider
     * @param mixed $content
     */
    public function testReadWorks($content)
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $file->create();

        /** @noinspection PhpUndefinedMethodInspection */
        file_put_contents($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file)), $content);
        $this->assertEquals($content, $file->read());
    }

    public function testReadWorksWithNonexistentFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $this->setExpectedException(FileNotFoundException::class);
        $file->read();
    }

    public function testReadWorksWithInaccessibleFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
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
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);
        $file->create();

        $file->write($content);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals($content, file_get_contents($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))));

        $file->write($content, true);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals($content . $content, file_get_contents($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))));

        $file->write($content, false);
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals($content, file_get_contents($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))));
    }

    public function testWriteWorksWithNonexistentFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $this->setExpectedException(FileNotFoundException::class);
        $file->write(null);
    }

    public function testWriteWorksWithInaccessibleFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getInaccessibleFile($storageManager);

        $this->setExpectedException(FileAccessException::class);
        $file->write(null);
    }

    public function testDeleteWorks()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        /** @noinspection PhpUndefinedMethodInspection */
        $realPath = $filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file));
        @mkdir(dirname($realPath), 0777, true);
        touch($realPath);

        $file->delete();
        $this->assertFileNotExists($realPath);
    }

    public function testDeleteWorksWithNonexistentFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getFile($storageManager);

        $this->setExpectedException(FileNotFoundException::class);
        $file->delete();
    }

    public function testDeleteWorksWithInaccessibleFile()
    {
        $filesystem = $this->getLocalFilesystem();
        $storageManager = $this->getStorageManager($filesystem);
        $file = $this->getInaccessibleFile($storageManager);
        /** @noinspection PhpUndefinedMethodInspection */
        chmod(dirname($filesystem->getAdapter()->applyPathPrefix($storageManager->getPathToFile($file))), 0111);

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
