<?php

namespace PetrKnap\Php\FileStorage;

/**
 * Simple PHP class for work with virtual file system
 *
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2015-05-08
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
abstract class AbstractFile
{
    /**
     * @var string
     */
    private $pathToFile;

    /**
     * @var string
     */
    private $realPathToFile;

    /**
     * Crates new instance
     *
     * @param string $pathToFile user-friendly (readable) path to file
     */
    public function __construct($pathToFile)
    {
        $this->pathToFile = $pathToFile;
        $this->realPathToFile = $this->generateRealPathToFile($pathToFile);
    }

    /**
     * Returns real path to file
     *
     * @return string
     */
    public function getRealPathToFile()
    {
        return $this->realPathToFile;
    }

    /**
     * Returns path to file
     *
     * @return string
     */
    public function getPathToFile()
    {
        return $this->pathToFile;
    }

    /**
     * Returns path to storage directory
     *
     * @return string
     */
    abstract protected function getStorageDirectory();

    /**
     * Returns real path to file based on virtual path to file
     *
     * @param string $pathToFile user-friendly (readable) path to file
     * @return string
     */
    private function generateRealPathToFile($pathToFile)
    {
        $sha1 = sha1($pathToFile);
        $md5 = md5($pathToFile);
        $lastDotPosition = strrpos($pathToFile, ".");
        $ext = $lastDotPosition === false ? "" : substr($pathToFile, $lastDotPosition);

        $dirs = str_split($sha1, 2);

        $sha1Prefix = array_pop($dirs);

        $fileName = "{$sha1Prefix}-{$md5}{$ext}";

        $realPath = "{$this->getStorageDirectory()}";

        foreach ($dirs as $dir) {
            $realPath = "{$realPath}/{$dir}";
        }
        $realPath = "{$realPath}/{$fileName}";

        return $realPath;
    }

    /**
     * @return bool true if file does exist
     */
    public function exists()
    {
        return file_exists($this->realPathToFile);
    }

    /**
     * Throws exception if file does not exist
     *
     * @throws FileNotFoundException if file does not exist
     */
    private function checkIfFileExists()
    {
        if (!$this->exists()) {
            throw new FileNotFoundException(
                "File {$this->pathToFile} stored as {$this->realPathToFile} not found."
            );
        }
    }

    /**
     * Creates file
     *
     * @return $this
     *
     * @throws FileExistsException|FileAccessException if can not create
     */
    public function create()
    {
        if ($this->exists()) {
            throw new FileExistsException(
                "File {$this->pathToFile} stored as {$this->realPathToFile} exists."
            );
        }

        $path = dirname($this->realPathToFile);
        if (!file_exists($path)) {
            @mkdir($path, 0744, true);
        }

        $return = touch($this->realPathToFile);

        if ($return === false) {
            throw new FileAccessException(
                "Couldn't create {$this->pathToFile} stored as {$this->realPathToFile}."
            );
        }

        return $this;
    }

    /**
     * Reads from file
     *
     * @return mixed
     *
     * @throws FileAccessException if can not read
     */
    public function read()
    {
        $this->checkIfFileExists();

        $file = fopen($this->realPathToFile, "rb");

        if ($file === false) {
            throw new FileAccessException(
                "Couldn't open file {$this->pathToFile} stored as {$this->realPathToFile} for read."
            );
        }

        $return = stream_get_contents($file);

        if (fclose($file) === false) {
            throw new FileAccessException(
                "Couldn't close file {$this->pathToFile} stored as {$this->realPathToFile}."
            );
        }

        if ($return === false) {
            throw new FileAccessException(
                "Couldn't read from {$this->pathToFile} stored as {$this->realPathToFile}."
            );
        }

        return $return;
    }

    /**
     * Write to file
     *
     * @param mixed $data the data to write
     * @param int|bool $append true or FILE_APPEND for appending file instead of overwriting
     * @return $this
     *
     * @throws FileAccessException if can not write
     */
    public function write($data, $append = false)
    {
        $this->checkIfFileExists();

        $append = ($append === true || $append === FILE_APPEND);

        $file = fopen($this->realPathToFile, $append ? "ab" : "wb");

        if ($file === false) {
            throw new FileAccessException(
                "Couldn't open file {$this->pathToFile} stored as {$this->realPathToFile} for write."
            );
        }

        $return = fwrite($file, $data);

        if (fclose($file) === false) {
            throw new FileAccessException(
                "Couldn't close file {$this->pathToFile} stored as {$this->realPathToFile}."
            );
        }

        if ($return === false) {
            throw new FileAccessException(
                "Couldn't write to {$this->pathToFile} stored as {$this->realPathToFile}."
            );
        }

        return $this;
    }

    /**
     * Clears file
     *
     * @return $this
     *
     * @throws FileAccessException if can not clear
     */
    public function clear()
    {
        return $this->write(null);
    }

    /**
     * Deletes file
     *
     * @return $this
     *
     * @throws FileNotFoundException|FileAccessException if can not delete
     */
    public function delete()
    {
        $this->checkIfFileExists();

        if (!@unlink($this->realPathToFile)) {
            throw new FileAccessException(
                "Couldn't delete {$this->pathToFile} stored as {$this->realPathToFile}."
            );
        }

        return $this;
    }
}
