<?php

namespace PetrKnap\Php\FileStorage;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-05-09
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
interface FileInterface
{
    /**
     * Returns path to file
     *
     * @return string
     */
    public function getPathToFile();

    /**
     * Returns true if file does exist
     *
     * @return bool
     */
    public function exists();

    /**
     * Creates file
     *
     * @return $this
     */
    public function create();

    /**
     * Reads from file
     *
     * @return mixed
     */
    public function read();

    /**
     * Writes data to file
     *
     * @param mixed $data
     * @param bool $append
     * @return $this
     */
    public function write($data, $append = false);

    /**
     * Deletes file
     *
     * @return $this
     */
    public function delete();
}
