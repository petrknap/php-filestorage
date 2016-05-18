<?php

namespace PetrKnap\Php\FileStorage\Test;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private static $tempDir;

    /**
     * @var string
     */
    private static $tempPrefix;

    /**
     * @param string $tempDir
     * @throws \Exception
     */
    public static function setTempDir($tempDir)
    {
        if (self::$tempDir) {
            throw new \Exception("\$tempDir is read-only.");
        }
        self::$tempDir = $tempDir;
    }

    /**
     * @param string $tempPrefix
     * @throws \Exception
     */
    public static function setTempPrefix($tempPrefix)
    {
        if (self::$tempPrefix) {
            throw new \Exception("\$tempPrefix is read-only.");
        }
        self::$tempPrefix = $tempPrefix;
    }

    /**
     * @return string
     */
    protected static function getTemporaryDirectory()
    {
        $temporaryDirectory = tempnam(self::$tempDir, self::$tempPrefix);

        unlink($temporaryDirectory);

        return $temporaryDirectory;
    }

    private static function removeDirectory($directory)
    {
        $removeDirectoryRecursively = function ($directory) use (&$removeDirectoryRecursively) {
            chmod($directory, 0777);
            $directoryIterator = new \DirectoryIterator($directory);
            $itemIterator = new \IteratorIterator($directoryIterator);
            foreach ($itemIterator as $item) {
                if ($item->isDir() && !$item->isDot()) {
                    $removeDirectoryRecursively($item->getPathname());
                } elseif ($item->isFile()) {
                    unlink($item->getPathname());
                }
            }
            rmdir($directory);
        };

        $directoryIterator = new \DirectoryIterator($directory);
        $itemIterator = new \IteratorIterator($directoryIterator);
        foreach ($itemIterator as $item) {
            if ($item->isDir() && preg_match('|^' . self::$tempPrefix . '|', $item->getBaseName())) {
                fwrite(STDERR, PHP_EOL . "Removing '{$item->getPathname()}'...");
                $removeDirectoryRecursively($item->getPathname());
                fwrite(STDERR, " done");
            }
        }
        fwrite(STDERR, PHP_EOL);
    }

    public static function tearDownAfterClass()
    {
        self::removeDirectory(self::$tempDir);

        parent::tearDownAfterClass();
    }

    protected static function invokePrivateMethod($object, $method, array $arguments = [])
    {
        $objectReflection = new \ReflectionClass($object);
        $methodReflection = $objectReflection->getMethod($method);

        $methodReflection->setAccessible(true);
        return $methodReflection->invokeArgs($object, $arguments);
    }
}

AbstractTestCase::setTempDir(__DIR__ . "/../../temp");
AbstractTestCase::setTempPrefix("test_");
