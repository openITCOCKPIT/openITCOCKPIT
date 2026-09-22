<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use itnovum\openITCOCKPIT\Core\UUID;
use Migrations\BaseSeed;

/**
 * Class InstallSeed
 *
 * Created:
 * oitc bake seed -p TelegramModule --table commands --data Install
 *
 * Apply:
 * oitc migrations seed -p TelegramModule
 */
class InstallSeed extends BaseSeed {
    private const COMMANDS_TABLE_NAME = 'commands';
    private const CRONJOBS_TABLE_NAME = 'cronjobs';

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
        /** @var Connection $connection */
        $connection = ConnectionManager::get('default');

        //Migrate wrong typed host notification command from host-notifiy-by-telegram to host-notify-by-telegram
        $connection->updateQuery(self::COMMANDS_TABLE_NAME)
            ->set('name', 'host-notify-by-telegram')
            ->where([
                'command_type' => NOTIFICATION_COMMAND,
                'name'         => 'host-notifiy-by-telegram'
            ])
            ->execute();

        $data = [
            [
                'name'         => 'host-notify-by-telegram',
                'command_line' => '/opt/openitc/frontend/bin/cake TelegramModule.TelegramNotification --type Host --notificationtype $NOTIFICATIONTYPE$ --hostuuid "$HOSTNAME$" --state "$HOSTSTATEID$" --output "$HOSTOUTPUT$" --ackauthor "$NOTIFICATIONAUTHOR$" --ackcomment "$NOTIFICATIONCOMMENT$" --contactuuid "$CONTACTNAME$"',
                'command_type' => NOTIFICATION_COMMAND,
                'human_args'   => null,
                'uuid'         => UUID::v4(),
                'description'  => 'Send host notifications to Telegram',
            ],
            [
                'name'         => 'service-notify-by-telegram',
                'command_line' => '/opt/openitc/frontend/bin/cake TelegramModule.TelegramNotification --type Service --notificationtype $NOTIFICATIONTYPE$ --hostuuid "$HOSTNAME$" --serviceuuid "$SERVICEDESC$" --state "$SERVICESTATEID$" --output "$SERVICEOUTPUT$" --ackauthor "$NOTIFICATIONAUTHOR$" --ackcomment "$NOTIFICATIONCOMMENT$" --contactuuid "$CONTACTNAME$"',
                'command_type' => NOTIFICATION_COMMAND,
                'human_args'   => null,
                'uuid'         => UUID::v4(),
                'description'  => 'Send service notifications to Telegram',
            ]
        ];

        //Check if records exists
        foreach ($data as $index => $record) {
            $stm = $connection->selectQuery(['id'], self::COMMANDS_TABLE_NAME)
                ->where([
                    'command_type' => NOTIFICATION_COMMAND,
                    'name'         => $record['name']
                ])
                ->execute();
            $result = $stm->fetchAll();

            if (empty($result)) {
                $connection->insertQuery(self::COMMANDS_TABLE_NAME, $record)->execute();
            } else {
                $connection->updateQuery(self::COMMANDS_TABLE_NAME)
                    ->where([
                        'command_type' => NOTIFICATION_COMMAND,
                        'name'         => $record['name']
                    ])
                    ->set('command_line', $record['command_line'])
                    ->execute();
            }
        }

        //Cronjob
        $data = [
            [
                'task'     => 'TelegramProcessUpdates',
                'plugin'   => 'TelegramModule',
                'interval' => '1',
                'enabled'  => '1',
            ]
        ];

        //Check if records exists
        foreach ($data as $index => $record) {
            $stm = $connection->selectQuery(['id'], self::CRONJOBS_TABLE_NAME)
                ->where([
                    'plugin' => $record['plugin'],
                    'task'   => $record['task']
                ])
                ->execute();
            $result = $stm->fetchAll();

            if (empty($result)) {
                $connection->insertQuery(self::CRONJOBS_TABLE_NAME, $record)->execute();
            }
        }
    }
}
