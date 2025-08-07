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
 * oitc4 bake seed -p MattermostModule --table commands --data Install
 *
 * Apply:
 * oitc4 migrations seed -p MattermostModule
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

        //Commands
        $table = $this->table('commands');

        $data = [
            [
                'name'         => 'host-notifiy-by-mattermost',
                'command_line' => '/opt/openitc/frontend/bin/cake MattermostModule.mattermost_notification --type Host --notificationtype $NOTIFICATIONTYPE$ --hostuuid "$HOSTNAME$" --state "$HOSTSTATEID$" --output "$HOSTOUTPUT$" --ackauthor "$NOTIFICATIONAUTHOR$" --ackcomment "$NOTIFICATIONCOMMENT$"',
                'command_type' => NOTIFICATION_COMMAND,
                'human_args'   => null,
                'uuid'         => \itnovum\openITCOCKPIT\Core\UUID::v4(),
                'description'  => 'Send host notifications to Mattermost',
            ],
            [
                'name'         => 'service-notify-by-mattermost',
                'command_line' => '/opt/openitc/frontend/bin/cake MattermostModule.mattermost_notification --type Service --notificationtype $NOTIFICATIONTYPE$ --hostuuid "$HOSTNAME$" --serviceuuid "$SERVICEDESC$" --state "$SERVICESTATEID$" --output "$SERVICEOUTPUT$" --ackauthor "$NOTIFICATIONAUTHOR$" --ackcomment "$NOTIFICATIONCOMMENT$"',
                'command_type' => NOTIFICATION_COMMAND,
                'human_args'   => null,
                'uuid'         => \itnovum\openITCOCKPIT\Core\UUID::v4(),
                'description'  => 'Send service notifications to Mattermost',
            ]
        ];

        //Check if records exists
        foreach ($data as $index => $record) {
            $QueryBuilder = $this->getAdapter()->getQueryBuilder();

            $stm = $QueryBuilder->select('*')
                ->from($table->getName())
                ->where([
                    'command_type' => NOTIFICATION_COMMAND,
                    'name'         => $record['name']
                ])
                ->execute();
            $result = $stm->fetchAll();

            if (empty($result)) {
                $table->insert($record)->save();
            }
        }
    }
}
