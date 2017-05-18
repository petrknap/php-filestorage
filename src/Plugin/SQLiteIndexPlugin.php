<?php

namespace PetrKnap\Php\FileStorage\Plugin;

use League\Flysystem\FilesystemInterface;
use Nunzion\Expect;
use PetrKnap\Php\FileStorage\Plugin\Exception\IndexReadException;
use PetrKnap\Php\FileStorage\Plugin\Exception\IndexWriteException;
use PetrKnap\Php\MigrationTool\SqlMigrationTool;

/**
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2016-06-06
 * @category FileStorage
 * @package  PetrKnap\Php\FileStorage\Plugin
 * @license  https://github.com/petrknap/php-filestorage/blob/master/LICENSE MIT
 */
class SQLiteIndexPlugin extends AbstractIndexPlugin
{
    const /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        INSERT = "INSERT INTO file_storage_index (path, inner_path) VALUES (:path, :inner_path)";

    const /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        SELECT = "SELECT path, inner_path FROM file_storage_index WHERE path LIKE :path";

    const /** @noinspection SqlDialectInspection */
        /** @noinspection SqlNoDataSourceInspection */
        DELETE = "DELETE FROM file_storage_index WHERE path = :path";

    /**
     * @param FilesystemInterface $outerFileSystem
     * @param string $pathToDatabase
     */
    public static function register(FilesystemInterface $outerFileSystem, $pathToDatabase)
    {
        Expect::that($pathToDatabase)->isString();

        parent::register($outerFileSystem, $pathToDatabase);
    }

    public function __construct($method, $secondArgument)
    {
        $pdo = new \PDO("sqlite:{$secondArgument}");

        $migrationTool = new SqlMigrationTool(__DIR__ . "/../../migrations", $pdo);
        @$migrationTool->migrate();

        parent::__construct($method, $pdo);
    }

    /**
     * @return \PDO
     */
    private function getPDO()
    {
        return $this->secondArgument;
    }

    /**
     * @param string $message
     * @return IndexWriteException
     */
    private function writeExceptionFactory($message)
    {
        return new IndexWriteException($message, 0, new \Exception(
            implode(" ", $this->getPDO()->errorInfo())
        ));
    }

    /**
     * @param string $message
     * @return IndexReadException
     */
    private function readExceptionFactory($message)
    {
        return new IndexReadException($message, 0, new \Exception(
            implode(" ", $this->getPDO()->errorInfo())
        ));
    }

    /**
     * @inheritdoc
     */
    public function addPathToIndex($path, $innerPath)
    {
        $statement = $this->getPDO()->prepare(self::INSERT);

        if ($statement === false) {
            throw $this->writeExceptionFactory("Could not add path to index");
        }

        if ($statement->execute(["path" => $path, "inner_path" => $innerPath]) === false) {
            throw $this->writeExceptionFactory("Could not add path to index");
        }
    }

    /**
     * @inheritdoc
     */
    public function removePathFromIndex($path, $innerPath)
    {
        $statement = $this->getPDO()->prepare(self::DELETE);

        if ($statement === false) {
            throw $this->writeExceptionFactory("Could not remove path from index");
        }

        if ($statement->execute(["path" => $path]) === false) {
            throw $this->writeExceptionFactory("Could not remove path from index");
        }
    }

    /**
     * @inheritdoc
     */
    public function getMetadataFromIndex($directory, $recursive)
    {
        $statement = $this->getPDO()->prepare(self::SELECT);

        if ($statement === false) {
            throw $this->readExceptionFactory("Could not read paths from index");
        }

        if ($statement->execute(["path" => "{$directory}%"]) === false) {
            throw $this->readExceptionFactory("Could not read paths from index");
        }

        $output = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $path = @$row["path"];
            $metadata = $this->fileSystem->getMetadata($path);
            if (!$recursive && $metadata["dirname"] != $directory) {
                continue;
            }
            $output[] = $metadata;
        }
        return $output;
    }
}
