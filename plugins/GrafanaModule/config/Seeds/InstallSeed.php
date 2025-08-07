<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
//
// This file is licensed under the terms of the openITCOCKPIT Enterprise Edition license agreement.
// The license agreement and license key were sent with the order confirmation.

declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Class InstallSeed
 *
 * Created:
 * oitc4 bake seed -p GrafanaModule --table commands --data Install
 *
 * Apply:
 * oitc4 migrations seed -p GrafanaModule
 */
class InstallSeed extends \Migrations\BaseSeed {
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/3/en/index.html#seed-seeding-your-database
     *
     * @return void
     */
    public function run(): void {
        //Cronjobs
        $table = $this->table('cronjobs');

        $data = [
            [
                'task'     => 'GrafanaDashboard',
                'plugin'   => 'GrafanaModule',
                'interval' => '720',
                'enabled'  => '1',
            ]
        ];

        //Check if records exists
        foreach ($data as $index => $record) {
            $QueryBuilder = $this->getAdapter()->getQueryBuilder();

            $stm = $QueryBuilder->select('*')
                ->from($table->getName())
                ->where([
                    'plugin' => $record['plugin'],
                    'task'   => $record['task']
                ])
                ->execute();
            $result = $stm->fetchAll();

            if (empty($result)) {
                $table->insert($record)->save();
            }
        }
    }
}
