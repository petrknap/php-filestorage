<?php

namespace PetrKnap\Php\FileStorage;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Filesystem as FlyFileSystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Handler;
use League\Flysystem\Plugin\PluggableTrait;
use Nunzion\Expect;
use PetrKnap\Php\FileStorage\Exception\IndexDecodeException;
use PetrKnap\Php\FileStorage\Exception\IndexReadException;
use PetrKnap\Php\FileStorage\Exception\IndexWriteException;

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

    const PATH_TO_INDEXES = "/indexes";
    const INDEX_FILE = "index.json";
    const INDEX_FILE__DATA = "files";

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
    private function getInnerPath($path)
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
     * Loads index file
     *
     * @param string $pathToIndex
     * @return array
     * @throws IndexDecodeException
     * @throws IndexReadException
     */
    private function readIndex($pathToIndex)
    {
        if ($this->fileSystem->has($pathToIndex)) {
            $contentOfIndex = @$this->fileSystem->read($pathToIndex);

            if ($contentOfIndex === false) {
                throw new IndexReadException("Could not read index file '{$pathToIndex}'");
            }

            $decodedIndex = json_decode($contentOfIndex, true);

            if ($decodedIndex === null) {
                throw new IndexDecodeException("Could not decode index file '{$pathToIndex}'");
            }

            return $decodedIndex;
        } else {
            return [];
        }
    }

    /**
     * Saves index file
     *
     * @param string $pathToIndex
     * @param array $data
     * @throws IndexWriteException
     */
    private function writeIndex($pathToIndex, array $data)
    {
        if (!$this->fileSystem->has($pathToIndex)) {
            $return = @$this->fileSystem->write($pathToIndex, json_encode($data));
        } else {
            $return = @$this->fileSystem->update($pathToIndex, json_encode($data));
        }

        if ($return === false) {
            throw new IndexWriteException("Could not read index file '{$pathToIndex}'");
        }
    }

    /**
     * Returns paths to index files
     *
     * @param string $innerPath
     * @return string[]
     */
    private function getPathsToIndexFiles($innerPath)
    {
        $innerPath = explode("/", $innerPath);
        $pathsToIndexes = [];

        while(array_pop($innerPath)) {
            $indexPath = implode("/", $innerPath);
            if (!empty($indexPath)) {
                $pathsToIndexes[] = self::PATH_TO_INDEXES . $indexPath . "/" . self::INDEX_FILE;
            }
        }

        return $pathsToIndexes;
    }

    /**
     * Adds path to indexes
     *
     * @param string $path
     * @param string $innerPath
     */
    private function addPathToIndex($path, $innerPath)
    {
        $indexes = $this->getPathsToIndexFiles($innerPath);
        $countOfIndexes = count($indexes);
        $blockSize = 4;

        for ($i = 0; $i < $countOfIndexes; $i++) {
            $index = $this->readIndex($indexes[$i]);
            $indexData = &$index[self::INDEX_FILE__DATA];
            if (!$indexData) {
                $indexData = [];
            }
            $blockData = &$indexData[substr($path, 0, $i == 0 ? strlen($path) : ($countOfIndexes - $i) * $blockSize)];
            $blockData++;
            $this->writeIndex($indexes[$i], $index);
        }
    }

    /**
     * Removes path from indexes
     *
     * @param string $path
     * @param string $innerPath
     */
    private function removePathFromIndex($path, $innerPath)
    {
        $indexes = $this->getPathsToIndexFiles($innerPath);
        $countOfIndexes = count($indexes);
        $keyBlockSize = 4;

        for ($i = 0; $i < $countOfIndexes; $i++) {
            $index = $this->readIndex($indexes[$i]);
            $indexData = &$index[self::INDEX_FILE__DATA];
            if (!$indexData) {
                $indexData = [];
            }
            $keyBlock = substr($path, 0, $i == 0 ? strlen($path) : ($countOfIndexes - $i) * $keyBlockSize);
            $key = &$indexData[$keyBlock];
            $key--;
            if ($key <= 0) {
                unset($indexData[$keyBlock]);
            }
            $this->writeIndex($indexes[$i], $index);
        }
    }

    /**
     * Lists contents forward (index lookup first)
     *
     * @param string $directory
     * @param bool $recursive
     * @return \Generator
     */
    private function listContentsForward($directory, $recursive)
    {
        $browseIndexTree = function($path, $deep = 1) use ($directory, $recursive, &$browseIndexTree) {
            $subPaths = [];
            $directoryFound = false;
            foreach ($this->fileSystem->listContents($path, false) as $content) {
                if (strlen($content["basename"]) == 2) {
                    $subPaths[] = $content["path"];
                } else {
                    $index = $this->readIndex($content["path"]);
                    if ($deep == 20) {
                        yield $index;
                    } else {
                        foreach ($index[self::INDEX_FILE__DATA] as $knownPath => $unused) {
                            if (strlen($directory) >= strlen($knownPath)) {
                                $directoryFound = strpos($directory, $knownPath) === 0;
                            } else {
                                $slashedDirectory = str_replace("//", "/", "$directory/");
                                if (strpos($knownPath, $slashedDirectory) === 0)
                                {
                                    $directoryFound = $recursive || strrpos("/", $knownPath) == strrpos("/", $slashedDirectory);
                                }
                            }
                            if ($directoryFound) {
                                break;
                            }
                        }
                    }
                }
            };
            if ($directoryFound || $deep == 1) {
                foreach ($subPaths as $subPath) {
                    foreach($browseIndexTree($subPath, $deep + 1) as $index) {
                        yield $index;
                    }
                }
            }
        };

        foreach($browseIndexTree(self::PATH_TO_INDEXES) as $index) {
            foreach ($index[self::INDEX_FILE__DATA] as $path => $unused) {
                $metadata = $this->getMetadata($path);
                if (!$recursive && $metadata["dirname"] != $directory) {
                    continue;
                }
                yield $metadata;
            }
        }
    }

    /**
     * Lists contents backward (storage lookup first)
     *
     * @param string $directory
     * @param bool $recursive
     * @return \Generator
     */
    private function listContentsBackward($directory, $recursive)
    {
        Expect::that($directory)->equals("/");
        Expect::that($recursive)->equals(true);

        $index = [
            "targetDirname" => null,
            "content" => null
        ];
        foreach ($this->fileSystem->listContents("/", false) as $directory) {
            if ("/{$directory["path"]}" == self::PATH_TO_INDEXES) {
                continue;
            }
            foreach ($this->fileSystem->listContents("/{$directory["path"]}", true) as $content) {
                if (strlen($content["basename"]) == 2) {
                    continue;
                }
                if ($index["targetDirname"] != $content["dirname"]) {
                    $index["targetDirname"] = $content["dirname"];
                    $index["content"] = $this->readIndex(
                        self::PATH_TO_INDEXES . "/{$content["dirname"]}/" . self::INDEX_FILE
                    );
                }
                foreach ($index["content"][self::INDEX_FILE__DATA] as $knownPath => $unused) {
                    if ("/{$content["path"]}" == $this->getInnerPath($knownPath)) {
                        yield $this->getMetadata($knownPath);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param AdapterInterface $adapter
     * @param Config|array $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $this->fileSystem = new FlyFileSystem($adapter, $config);
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
        return $this->fileSystem->read($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        return $this->fileSystem->readStream($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = "/", $recursive = false)
    {
        if ($directory === "/" && $recursive) {
            return iterator_to_array($this->listContentsBackward($directory, $recursive));
        } else {
            return iterator_to_array($this->listContentsForward($directory, $recursive));
        }
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        $return = $this->fileSystem->getMetadata($this->getInnerPath($path));

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
        return $this->fileSystem->getMimetype($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->fileSystem->getTimestamp($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        return $this->fileSystem->getVisibility($this->getInnerPath($path));
    }

    /**
     * @inheritdoc
     */
    public function write($path, $contents, array $config = [])
    {
        $innerPath = $this->getInnerPath($path);

        $return = $this->fileSystem->write($innerPath, $contents, $config);

        if ($return !== false) {
            $this->addPathToIndex($path, $innerPath);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function writeStream($path, $resource, array $config = [])
    {
        $innerPath = $this->getInnerPath($path);

        $return = $this->fileSystem->writeStream($innerPath, $resource, $config);

        if ($return !== false) {
            $this->addPathToIndex($path, $innerPath);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, array $config = [])
    {
        return $this->fileSystem->update($this->getInnerPath($path), $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, array $config = [])
    {
        return $this->fileSystem->updateStream($this->getInnerPath($path), $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newPath)
    {
        $innerPath = $this->getInnerPath($path);
        $newInnerPath = $this->getInnerPath($newPath);

        $return = $this->fileSystem->rename($innerPath, $newInnerPath);

        if ($return !== false) {
            $this->addPathToIndex($newPath, $newInnerPath);
            $this->removePathFromIndex($path, $innerPath);
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

        $return = $this->fileSystem->copy($innerPath, $newInnerPath);

        if ($return !== false) {
            $this->addPathToIndex($newPath, $newInnerPath);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function delete($path)
    {
        $innerPath = $this->getInnerPath($path);

        $return = $this->fileSystem->delete($innerPath);

        if ($return !== false) {
            $this->removePathFromIndex($path, $innerPath);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname)
    {
        $innerDirname = $this->getInnerPath($dirname);

        $return = $this->fileSystem->deleteDir($innerDirname);

        if ($return !== false) {
            $this->removePathFromIndex($dirname, $innerDirname);
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
            $this->addPathToIndex($dirname, $innerDirname);
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
            $this->addPathToIndex($path, $innerPath);
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
            $this->addPathToIndex($path, $innerPath);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function readAndDelete($path)
    {
        $innerPath = $this->getInnerPath($path);

        $return = $this->fileSystem->readAndDelete($innerPath);

        if ($return !== false) {
            $this->removePathFromIndex($path, $innerPath);
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
}
