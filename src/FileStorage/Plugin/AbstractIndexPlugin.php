<?php

namespace PetrKnap\Php\FileStorage\Plugin;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use Nunzion\Expect;
use PetrKnap\Php\FileStorage\FileSystem;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-05-17
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage\Plugin
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
abstract class AbstractIndexPlugin implements PluginInterface
{
    /**
     * @var mixed
     */
    protected $secondArgument;

    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @var string
     */
    private $method;

    /**
     * @param string $method
     * @param mixed $secondArgument
     */
    public function __construct($method, $secondArgument)
    {
        $this->method = $method;
        $this->secondArgument = $secondArgument;
    }

    public static function register(FilesystemInterface $outerFileSystem, $secondArgument)
    {
        foreach (["addPathToIndex", "removePathFromIndex", "getMetadataFromIndex"] as $method) {
            $outerFileSystem->addPlugin(new static($method, $secondArgument));
        }
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    public function handle()
    {
        return call_user_func_array([$this, $this->method], func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function setFilesystem(FilesystemInterface $fileSystem)
    {
        Expect::that($fileSystem)->isInstanceOf(FileSystem::class);

        $this->fileSystem = $fileSystem;
    }

    /**
     * Adds path to indexes
     *
     * @param string $path
     * @param string $innerPath
     */
    abstract public function addPathToIndex($path, $innerPath);

    /**
     * Removes path from indexes
     *
     * @param string $path
     * @param string $innerPath
     */
    abstract public function removePathFromIndex($path, $innerPath);

    /**
     * @see FilesystemInterface::listContents
     * @param string $directory
     * @param bool $recursive
     * @return array
     */
    abstract public function getMetadataFromIndex($directory, $recursive);
}
