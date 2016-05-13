<?php

namespace PetrKnap\Php\FileStorage\File;

use Nunzion\Expect;
use PetrKnap\Php\FileStorage\File\Exception\FileAccessException;
use PetrKnap\Php\FileStorage\File\Exception\FileExistsException;
use PetrKnap\Php\FileStorage\File\Exception\FileNotFoundException;
use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManagerInterface;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2015-05-08
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage\File
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
class File implements FileInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $realPathToFile;

    /**
     * @var StorageManagerInterface
     */
    private $storageManager;

    /**
     * @param StorageManagerInterface $storageManager
     * @param string $path user-friendly (readable) path to file
     */
    public function __construct(StorageManagerInterface $storageManager, $path)
    {
        Expect::that($path)->isString();

        $this->path = $path;
        $this->storageManager = $storageManager;
        $this->realPathToFile = $this->storageManager->getPathToFile($this);
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function exists()
    {
        return $this->storageManager->getFilesystem()->has($this->realPathToFile);
    }

    /**
     * @throws FileNotFoundException if file does not exist
     */
    private function checkIfFileExists()
    {
        if (!$this->exists()) {
            throw new FileNotFoundException("File {$this} not found");
        }
    }

    /**
     * @inheritdoc
     */
    public function create()
    {
        if ($this->exists()) {
            throw new FileExistsException("File {$this} exists");
        }

        $return = @$this->storageManager->getFilesystem()
            ->write($this->realPathToFile, null);

        if ($return === false) {
            throw new FileAccessException("Could not create file {$this}");
        }

        $this->storageManager->assignFile($this);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        $this->checkIfFileExists();

        $return = @$this->storageManager->getFilesystem()
            ->read($this->realPathToFile);

        if ($return === false) {
            throw new FileAccessException("Could not read from file {$this}");
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function write($data, $append = false)
    {
        if (!is_bool($append)) {
            Expect::that($append)->_("must be boolean");
        }

        $this->checkIfFileExists();

        if ($append) {
            $data = $this->read() . $data;
        }
        $return = @$this->storageManager->getFilesystem()
            ->update($this->realPathToFile, $data);

        if ($return === false) {
            throw new FileAccessException("Could not write to {$this}");
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        $this->checkIfFileExists();

        if (!@$this->storageManager->getFilesystem()->delete($this->realPathToFile)) {
            throw new FileAccessException("Could not delete file {$this}");
        }

        $this->storageManager->unassignFile($this);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "'{$this->path}' stored as '{$this->realPathToFile}'";
    }
}
