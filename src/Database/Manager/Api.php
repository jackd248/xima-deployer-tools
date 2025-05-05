<?php

namespace Xima\XimaDeployerTools\Database\Manager;

use function Deployer\get;
use function Deployer\run;
use function Deployer\test;

/**
 * Database Management "API"
 *
 * ToDo
 */
class Api extends AbstractManager implements ManagerInterface {

    public function create(): void
    {
        throw new \RuntimeException('Not implemented yet.');
    }

    public function delete(string $feature): void
    {
        throw new \RuntimeException('Not implemented yet.');
    }
}