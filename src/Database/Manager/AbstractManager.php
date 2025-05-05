<?php

namespace Xima\XimaDeployerTools\Database\Manager;


use Xima\XimaDeployerTools\Utility\VarUtility;
use function Deployer\get;
use function Deployer\run;
use function Deployer\runExtended;
use function Deployer\test;

abstract class AbstractManager {

    public function run(string $command, $useDoubleQuotes = true): string
    {
        $databaseUser = get('database_user');
        $databaseHost = get('database_host');
        $databasePort = get('database_port');
        $databasePassword = VarUtility::getDatabasePassword();
        $quote = $useDoubleQuotes ? '"' : '\'';

        return runExtended(get('mysql') . " -u$databaseUser -p'%secret%' -h$databaseHost -P$databasePort -e {$quote}$command{$quote}", [],null,null, $databasePassword);
    }

    /**
     * Generate a database name
     * @param ?string $feature
     * @return string
     */
    public function getDatabaseName(?string $feature = null): string
    {
        $feature = $feature ?: input()->getOption('feature');
        $project = get('project');
        return substr(getFeatureName("{$project}--{$feature}"),0,63);
    }
}