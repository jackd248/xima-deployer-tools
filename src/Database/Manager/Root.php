<?php

namespace Xima\XimaDeployerTools\Database\Manager;

use function Deployer\debug;
use function Deployer\get;
use function Deployer\run;
use function Deployer\test;

class Root extends AbstractManager implements ManagerInterface {

    public function create(): void
    {
        debug('Creating database');
        $databaseName = $this->getDatabaseName();
        $additionalParams = '';

        if (has('database_collation')) {
            $additionalParams .= ' COLLATE ' . get('database_collation');
        }

        if (has('database_charset')) {
            $additionalParams .= ' CHARACTER SET ' . get('database_charset');
        }

        $this->run("CREATE DATABASE IF NOT EXISTS `$databaseName`{$additionalParams};", false);
    }

    public function delete(string $feature): void
    {
        debug('Deleting database');
        $databaseName = $this->getDatabaseName($feature);
        $databaseRemoveCommand = "DROP DATABASE IF EXISTS `$databaseName`;";
        $this->run($databaseRemoveCommand);
    }
}