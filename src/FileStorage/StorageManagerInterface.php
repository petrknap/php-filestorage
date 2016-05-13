<?php

namespace PetrKnap\Php\FileStorage;

use League\Flysystem\FilesystemInterface;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-05-09
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
interface StorageManagerInterface
{
    /**
     * Returns filesystem object
     *
     * @return FileSystemInterface
     */
    public function getFilesystem();

    /**
     * Returns path to file
     *
     * @param FileInterface $file
     * @return string
     */
    public function getPathToFile(FileInterface $file);

    /**
     * Assigns file to storage
     *
     * @param FileInterface $file
     * @return $this
     */
    public function assignFile(FileInterface $file);

    /**
     * Unassigns file from storage
     *
     * @param FileInterface $file
     * @return $this
     */
    public function unassignFile(FileInterface $file);

    /**
     * Returns all stored files
     *
     * @return \Generator|FileInterface[]
     */
    public function getFiles();
}
