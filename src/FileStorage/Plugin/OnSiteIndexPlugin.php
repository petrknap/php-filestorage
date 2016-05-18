<?php

namespace PetrKnap\Php\FileStorage\Plugin;

use League\Flysystem\FilesystemInterface;
use Nunzion\Expect;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Plugin\Exception\IndexDecodeException;
use PetrKnap\Php\FileStorage\Plugin\Exception\IndexReadException;
use PetrKnap\Php\FileStorage\Plugin\Exception\IndexWriteException;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-05-17
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage\Plugin
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
class OnSiteIndexPlugin extends AbstractIndexPlugin
{
    const PATH_TO_INDEXES = "/indexes";
    const INDEX_FILE = "index.json";
    const INDEX_FILE__DATA = "files";

    /**
     * @param FilesystemInterface $outerFileSystem
     * @param FilesystemInterface $innerFileSystem
     */
    public static function register(FilesystemInterface $outerFileSystem, $innerFileSystem)
    {
        Expect::that($innerFileSystem)->isInstanceOf(FilesystemInterface::class);

        parent::register($outerFileSystem, $innerFileSystem);
    }

    /**
     * @return FilesystemInterface
     */
    private function getOuterFileSystem()
    {
        return $this->fileSystem;
    }

    /**
     * @return FilesystemInterface
     */
    private function getInnerFileSystem()
    {
        return $this->secondArgument;
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
        if ($this->getInnerFileSystem()->has($pathToIndex)) {
            $contentOfIndex = @$this->getInnerFileSystem()->read($pathToIndex);

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
        if (!$this->getInnerFileSystem()->has($pathToIndex)) {
            $return = @$this->getInnerFileSystem()->write($pathToIndex, json_encode($data));
        } else {
            $return = @$this->getInnerFileSystem()->update($pathToIndex, json_encode($data));
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

        while (array_pop($innerPath)) {
            $indexPath = implode("/", $innerPath);
            if (!empty($indexPath)) {
                $pathsToIndexes[] = self::PATH_TO_INDEXES . $indexPath . "/" . self::INDEX_FILE;
            }
        }

        return $pathsToIndexes;
    }

    /**
     * Lists contents forward (index lookup first)
     *
     * @param string $directory
     * @param bool $recursive
     * @return \Generator
     */
    private function getMetadataFromIndexForward($directory, $recursive)
    {
        $browseIndexTree = function ($path, $deep = 1) use ($directory, $recursive, &$browseIndexTree) {
            $subPaths = [];
            $directoryFound = false;
            foreach ($this->getInnerFileSystem()->listContents($path, false) as $content) {
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
                                if (strpos($knownPath, $slashedDirectory) === 0) {
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
                    foreach ($browseIndexTree($subPath, $deep + 1) as $index) {
                        yield $index;
                    }
                }
            }
        };

        foreach ($browseIndexTree(self::PATH_TO_INDEXES) as $index) {
            foreach ($index[self::INDEX_FILE__DATA] as $path => $unused) {
                $metadata = $this->getOuterFileSystem()->getMetadata($path);
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
    private function getMetadataFromIndexBackward($directory, $recursive)
    {
        Expect::that($directory)->equals("/");
        Expect::that($recursive)->equals(true);

        $index = [
            "targetDirname" => null,
            "content" => null
        ];
        foreach ($this->getInnerFileSystem()->listContents("/", false) as $directory) {
            if ("/{$directory["path"]}" == self::PATH_TO_INDEXES) {
                continue;
            }
            foreach ($this->getInnerFileSystem()->listContents("/{$directory["path"]}", true) as $content) {
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
                    if ("/{$content["path"]}" == FileSystem::getInnerPath($knownPath)) {
                        yield $this->getOuterFileSystem()->getMetadata($knownPath);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param string $path
     * @param int $countOfIndexes
     * @param int $index
     * @return string
     */
    private function getIndexKey($path, $countOfIndexes, $index)
    {
        return substr($path, 0, $index == 0 ? strlen($path) : ($countOfIndexes - $index) * 4);
    }

    /**
     * @inheritdoc
     */
    public function addPathToIndex($path, $innerPath)
    {
        $indexes = $this->getPathsToIndexFiles($innerPath);
        $countOfIndexes = count($indexes);

        for ($i = 0; $i < $countOfIndexes; $i++) {
            $index = $this->readIndex($indexes[$i]);
            $indexData = &$index[self::INDEX_FILE__DATA];
            if (!$indexData) {
                $indexData = [];
            }
            $indexKey = $this->getIndexKey($path, $countOfIndexes, $i);
            $key = &$indexData[$indexKey];
            $key++;
            $this->writeIndex($indexes[$i], $index);
        }
    }

    /**
     * @inheritdoc
     */
    public function removePathFromIndex($path, $innerPath)
    {
        $indexes = $this->getPathsToIndexFiles($innerPath);
        $countOfIndexes = count($indexes);

        for ($i = 0; $i < $countOfIndexes; $i++) {
            $index = $this->readIndex($indexes[$i]);
            $indexData = &$index[self::INDEX_FILE__DATA];
            if (!$indexData) {
                $indexData = [];
            }
            $indexKey = $this->getIndexKey($path, $countOfIndexes, $i);
            $key = &$indexData[$indexKey];
            $key--;
            if ($key <= 0) {
                unset($indexData[$indexKey]);
            }
            $this->writeIndex($indexes[$i], $index);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMetadataFromIndex($directory, $recursive)
    {
        Expect::that($directory)->isString();
        Expect::that($directory)->isNotEmpty();

        if ($directory == "/" && $recursive) {
            return iterator_to_array($this->getMetadataFromIndexBackward($directory, $recursive));
        } else {
            return iterator_to_array($this->getMetadataFromIndexForward($directory, $recursive));
        }
    }
}
