<?php

namespace PetrKnap\Php\FileStorage;

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
     * Returns path to storage
     *
     * @return string
     */
    public function getPathToStorage();

    /**
     * Returns storage permissions for files
     *
     * NOTE: Value between 0600 and 0666, add 0111 for directory permissions.
     *
     * @return int
     */
    public function getStoragePermissions();

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
     * @return \Iterator|FileInterface[]
     */
    public function getFiles();
}
