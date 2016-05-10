<?php

namespace PetrKnap\Php\FileStorage\StorageManager;

use Nunzion\Expect;
use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManager\Exception\AssignException;
use PetrKnap\Php\FileStorage\StorageManagerInterface;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-05-09
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage\StorageManager
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
class StorageManager implements StorageManagerInterface
{
    const INDEX_FILE = "index.json";

    /**
     * @var string
     */
    private $pathToStorage;

    /**
     * @var int
     */
    private $storagePermissions;

    /**
     * @param string $storagePath path to storage
     * @param int $storagePermissions
     */
    public function __construct($storagePath, $storagePermissions = 0644)
    {
        Expect::that($storagePath)->isString();
        Expect::that($storagePermissions)->isBetween(0600, 0666);

        $this->pathToStorage = $storagePath;
        $this->storagePermissions = $storagePermissions;
    }

    /**
     * @inheritdoc
     */
    public function getPathToStorage()
    {
        return $this->pathToStorage;
    }

    /**
     * @inheritdoc
     */
    public function getStoragePermissions()
    {
        return $this->storagePermissions;
    }

    /**
     * @inheritdoc
     */
    public function getPathToFile(FileInterface $file)
    {
        $pathToFile = $file->getPath();

        $sha1 = sha1($pathToFile);
        $md5 = md5($pathToFile);
        $lastDotPosition = strrpos($pathToFile, ".");
        $ext = $lastDotPosition === false ? "" : substr($pathToFile, $lastDotPosition);

        $dirs = str_split($sha1, 2);

        $sha1Prefix = array_pop($dirs);

        $fileName = "{$sha1Prefix}-{$md5}{$ext}";

        $realPath = "{$this->pathToStorage}";

        foreach ($dirs as $dir) {
            $realPath = "{$realPath}/{$dir}";
        }
        $realPath = "{$realPath}/{$fileName}";

        return $realPath;
    }

    /**
     * Returns path to index file
     *
     * @param FileInterface $file
     * @return string
     */
    private function getPathToIndex(FileInterface $file)
    {
        return dirname($this->getPathToFile($file)) . "/" . self::INDEX_FILE;
    }

    /**
     * Loads index file
     *
     * @param FileInterface $file
     * @return array
     */
    private function readIndex(FileInterface $file)
    {
        $pathToIndex = $this->getPathToIndex($file);
        if (file_exists($pathToIndex)) {
            return json_decode(file_get_contents($pathToIndex), true);
        } else {
            return [];
        }
    }

    /**
     * Saves index file
     *
     * @param FileInterface $file
     * @param array $data
     */
    private function writeIndex(FileInterface $file, array $data)
    {
        $pathToIndex = $this->getPathToIndex($file);

        $dirName = dirname($pathToIndex);
        if (!file_exists($dirName)) {
            @mkdir($dirName, $this->storagePermissions + 0111, true);
        }

        file_put_contents($pathToIndex, json_encode($data));
        chmod($pathToIndex, $this->storagePermissions);
    }

    /**
     * @inheritdoc
     */
    public function assignFile(FileInterface $file)
    {
        if (!$file->exists()) {
            throw new AssignException("Could not assign nonexistent file");
        }

        $data = $this->readIndex($file);
        $files = &$data["files"][basename($this->getPathToFile($file))];
        $files = [
            "pathToFile" => $file->getPath()
        ];
        $this->writeIndex($file, $data);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function unassignFile(FileInterface $file)
    {
        $data = $this->readIndex($file);
        unset($data["files"][basename($this->getPathToFile($file))]);
        $this->writeIndex($file, $data);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFiles()
    {
        $directoryIterator = new \RecursiveDirectoryIterator($this->pathToStorage);
        $itemIterator = new \RecursiveIteratorIterator($directoryIterator);
        foreach ($itemIterator as $item) {
            if ($item->isFile() && $item->getBaseName() == self::INDEX_FILE) {
                $index = json_decode(file_get_contents($item->getRealPath()), true);
                foreach($index["files"] as $file => $metaData) {
                    yield new static($metaData["pathToFile"]);
                }
            }
        }
    }
}
