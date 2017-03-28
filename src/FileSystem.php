<?php

namespace PetrKnap\Php\FileStorage;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as FlyFileSystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Handler;
use League\Flysystem\Plugin\PluggableTrait;
use League\Flysystem\RootViolationException;
use Nunzion\Expect;
use PetrKnap\Php\FileStorage\Plugin\OnSiteIndexPlugin;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-05-14
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
class FileSystem implements FilesystemInterface
{
    use PluggableTrait;

    /**
     * @var FlyFileSystem
     */
    private $fileSystem;

    /**
     * Returns inner path
     *
     * @param string $path
     * @return string
     */
    public static function getInnerPath($path)
    {
        Expect::that($path)->isString();

        $sha1 = sha1($path);
        $md5 = md5($path);
        $lastDotPosition = strrpos($path, ".");
        $ext = $lastDotPosition === false ? "" : substr($path, $lastDotPosition);

        if (!preg_match('/^\.[a-zA-Z0-9]+$/', $ext) || basename($path) == $ext) {
            $ext = "";
        }

        $dirs = str_split($sha1, 2);

        $sha1Prefix = array_pop($dirs);

        $fileName = "{$sha1Prefix}-{$md5}{$ext}";

        $innerPath = "";
        foreach ($dirs as $dir) {
            $innerPath = "{$innerPath}/{$dir}";
        }
        $innerPath = "{$innerPath}/{$fileName}";

        return $innerPath;
    }

    /**
     * @param AdapterInterface $adapter
     * @param Config|array $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $this->fileSystem = new FlyFileSystem($adapter, $config);
        OnSiteIndexPlugin::register($this, $this->fileSystem);
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        return $this->fileSystem->has($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        try {
            return $this->fileSystem->read($this->getInnerPath($path));
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        try {
            return $this->fileSystem->readStream($this->getInnerPath($path));
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = "/", $recursive = false)
    {
        return $this->invokePlugin("getMetadataFromIndex", [$directory, $recursive], $this);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        try {
            $return = $this->fileSystem->getMetadata($this->getInnerPath($path));
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        if ($return !== false) {
            $return = array_merge($return, pathinfo($path), ["path" => $path]);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->fileSystem->getSize($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        try {
            return $this->fileSystem->getMimetype($this->getInnerPath($path));
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        try {
            return $this->fileSystem->getTimestamp($this->getInnerPath($path));
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        try {
            return $this->fileSystem->getVisibility($this->getInnerPath($path));
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, array $config = [])
    {
        $innerPath = $this->getInnerPath($path);

        try {
            $return = $this->fileSystem->write($innerPath, $contents, $config);
        } catch(FileExistsException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        if ($return !== false) {
            $this->invokePlugin("addPathToIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, array $config = [])
    {
        $innerPath = $this->getInnerPath($path);

        try {
            $return = $this->fileSystem->writeStream($innerPath, $resource, $config);
        } catch(FileExistsException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        if ($return !== false) {
            $this->invokePlugin("addPathToIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, array $config = [])
    {
        try {
            return $this->fileSystem->update($this->getInnerPath($path), $contents, $config);
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, array $config = [])
    {
        try {
            return $this->fileSystem->updateStream($this->getInnerPath($path), $resource, $config);
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newPath)
    {
        $innerPath = $this->getInnerPath($path);
        $newInnerPath = $this->getInnerPath($newPath);

        try {
            $return = $this->fileSystem->rename($innerPath, $newInnerPath);
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        } catch(FileExistsException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        if ($return !== false) {
            $this->invokePlugin("addPathToIndex", [$newPath, $newInnerPath], $this);
            $this->invokePlugin("removePathFromIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newPath)
    {
        $innerPath = $this->getInnerPath($path);
        $newInnerPath = $this->getInnerPath($newPath);

        try {
            $return = $this->fileSystem->copy($innerPath, $newInnerPath);
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        } catch(FileExistsException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        if ($return !== false) {
            $this->invokePlugin("addPathToIndex", [$newPath, $newInnerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        try {
            $innerPath = $this->getInnerPath($path);
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        $return = $this->fileSystem->delete($innerPath);

        if ($return !== false) {
            $this->invokePlugin("removePathFromIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        $innerDirname = $this->getInnerPath($dirname);

        try {
            $return = $this->fileSystem->deleteDir($innerDirname);
        } catch(RootViolationException $e) {
            throw $this->exceptionWrapper($e, $dirname);
        }

        if ($return !== false) {
            $this->invokePlugin("removePathFromIndex", [$dirname, $innerDirname], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, array $config = [])
    {
        $innerDirname = $this->getInnerPath($dirname);

        $return = $this->fileSystem->createDir($innerDirname, $config);

        if ($return !== false) {
            $this->invokePlugin("addPathToIndex", [$dirname, $innerDirname], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        return $this->fileSystem->setVisibility($this->getInnerPath($path), $visibility);
    }

    /**
     * @inheritdoc
     */
    public function put($path, $contents, array $config = [])
    {
        $addToIndex = !$this->has($path);
        $innerPath = $this->getInnerPath($path);

        $return = $this->fileSystem->put($innerPath, $contents, $config);

        if ($return !== false && $addToIndex) {
            $this->invokePlugin("addPathToIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function putStream($path, $resource, array $config = [])
    {
        $addToIndex = !$this->has($path);
        $innerPath = $this->getInnerPath($path);

        $return = $this->fileSystem->putStream($innerPath, $resource, $config);

        if ($return !== false && $addToIndex) {
            $this->invokePlugin("addPathToIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function readAndDelete($path)
    {
        $innerPath = $this->getInnerPath($path);

        try {
            $return = $this->fileSystem->readAndDelete($innerPath);
        } catch(FileNotFoundException $e) {
            throw $this->exceptionWrapper($e, $path);
        }

        if ($return !== false) {
            $this->invokePlugin("removePathFromIndex", [$path, $innerPath], $this);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function get($path, Handler $handler = null)
    {
        return $this->fileSystem->get($this->getInnerPath($path), $handler);
    }

    /**
     * @param \Exception $exception
     * @param string $path
     * @return \Exception
     */
    private function exceptionWrapper(\Exception $exception, $path)
    {
        $message = str_replace(substr($this->getInnerPath($path), 1), $path, $exception->getMessage());
        $exceptionClass = get_class($exception);
        return new $exceptionClass($message, $exception->getCode(), $exception);
    }
}
