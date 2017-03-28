<?php

namespace PetrKnap\Php\FileStorage\MigrationTool;

use PetrKnap\Php\MigrationTool\SqlMigrationTool;

class PDOMigrationTool extends SqlMigrationTool
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return string
     */
    protected function getPathToDirectoryWithMigrationFiles()
    {
        return __DIR__ . "/../../migrations";
    }

    /**
     * @return \PDO
     */
    protected function getPhpDataObject()
    {
        return $this->pdo;
    }

    /**
     * @return string
     */
    protected function getNameOfMigrationTable()
    {
        return "migrations";
    }
}
