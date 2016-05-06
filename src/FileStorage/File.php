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
abstract class File
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
        $ext = explode(".", $pathToFile);
        $ext = array_pop($ext);

        $dirs = str_split($sha1, 2);

        $sha1Prefix = array_pop($dirs);

        $fileName = "{$sha1Prefix}-{$md5}.{$ext}";

        $realPath = "{$this->getStorageDirectory()}";

        foreach($dirs as $dir) {
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
     * @throws FileException if file does not exist
     */
    private function checkIfFileExists()
    {
        if (!$this->exists()) {
            throw new FileException(
                "File {$this->pathToFile} stored as {$this->realPathToFile} not found.",
                FileException::FileNotFoundException
            );
        }
    }

    /**
     * Creates file
     *
     * @return $this
     *
     * @throws FileException if can not create
     */
    public function create()
    {
        if ($this->exists()) {
            throw new FileException(
                "File {$this->pathToFile} stored as {$this->realPathToFile} exists.",
                FileException::FileExistsException
            );
        }

        $path = dirname($this->realPathToFile);
        if(!file_exists($path)) {
            @mkdir($path, 0744, true);
        }

        $return = touch($this->realPathToFile);

        if ($return === false) {
            throw new FileException(
                "Couldn't create {$this->pathToFile} stored as {$this->realPathToFile}.",
                FileException::AccessException
            );
        }

        return $this;
    }

    /**
     * Reads from file
     *
     * @return mixed
     *
     * @throws FileException if can not read
     */
    public function read()
    {
        $this->checkIfFileExists();

        $file = fopen($this->realPathToFile, "rb");

        if($file === false) {
            throw new FileException(
                "Couldn't open file {$this->pathToFile} stored as {$this->realPathToFile} for read.",
                FileException::AccessException
            );
        }

        $return = stream_get_contents($file);

        if(fclose($file) === false) {
            throw new FileException(
                "Couldn't close file {$this->pathToFile} stored as {$this->realPathToFile}.",
                FileException::AccessException
            );
        }

        if ($return === false) {
            throw new FileException(
                "Couldn't read from {$this->pathToFile} stored as {$this->realPathToFile}.",
                FileException::AccessException
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
     * @throws FileException if can not write
     */
    public function write($data, $append = false)
    {
        $this->checkIfFileExists();

        $append = ($append === true || $append === FILE_APPEND);

        $file = fopen($this->realPathToFile, $append ? "ab": "wb");

        if($file === false) {
            throw new FileException(
                "Couldn't open file {$this->pathToFile} stored as {$this->realPathToFile} for write.",
                FileException::AccessException
            );
        }

        $return = fwrite($file, $data);

        if(fclose($file) === false) {
            throw new FileException(
                "Couldn't close file {$this->pathToFile} stored as {$this->realPathToFile}.",
                FileException::AccessException
            );
        }

        if ($return === false) {
            throw new FileException(
                "Couldn't write to {$this->pathToFile} stored as {$this->realPathToFile}.",
                FileException::AccessException
            );
        }

        return $this;
    }

    /**
     * Clears file
     *
     * @param resource $context a valid context resource created with stream_context_create
     * @return $this
     *
     * @throws FileException if can not clear
     */
    public function clear($context = null)
    {
        return $this->write(null, null, $context);
    }

    /**
     * Deletes file
     *
     * @return $this
     *
     * @throws FileException if can not delete
     */
    public function delete()
    {
        $this->checkIfFileExists();

        if(!@unlink($this->realPathToFile)) {
            throw new FileException(
                "Couldn't delete {$this->pathToFile} stored as {$this->realPathToFile}.",
                FileException::AccessException
            );
        }

        return $this;
    }
}
