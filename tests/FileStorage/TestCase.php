<?php

namespace PetrKnap\Php\FileStorage\Test;

abstract class TestCase extends \PHPUnit_Framework_TestCase
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
    protected function getTemporaryDirectory()
    {
        $temporaryDirectory = tempnam(self::$tempDir, self::$tempPrefix);

        unlink($temporaryDirectory);

        return $temporaryDirectory;
    }

    private static function removeDirectory($directory)
    {
        $directoryIterator = new \DirectoryIterator($directory);
        $itemIterator = new \IteratorIterator($directoryIterator);
        foreach ($itemIterator as $item) {
            if ($item->isDir() && preg_match('|^' . self::$tempPrefix . '|', $item->getBaseName())) {
                $cmd = sprintf(
                    "rsync -a --delete %s %s; rm -rf %s",
                    escapeshellcmd(__DIR__ . "/TestCase/empty_directory/"),
                    escapeshellcmd($item->getRealPath() . "/"),
                    escapeshellcmd($item->getRealPath() . "/")
                );
                fwrite(STDERR, PHP_EOL . $cmd);
                exec($cmd);
            }
        }
        fwrite(STDERR, PHP_EOL);
    }

    public static function tearDownAfterClass()
    {
        self::removeDirectory(self::$tempDir);

        parent::tearDownAfterClass();
    }
}

TestCase::setTempDir(__DIR__ . "/../../temp");
TestCase::setTempPrefix("test_");
