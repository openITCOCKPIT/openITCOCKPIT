<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace MattermostModule\Command;

use App\Model\Table\HostsTable;
use App\Model\Table\ProxiesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use GuzzleHttp\Client;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\HoststatusIcon;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;
use MattermostModule\Model\Table\MattermostSettingsTable;
use Spatie\Emoji\Emoji;

/**
 * MattermostNotification command.
 *
 * Docs:
 * https://docs.mattermost.com/developer/webhooks-incoming.html
 * https://docs.mattermost.com/developer/interactive-messages.html#message-buttons
 */
class MattermostNotificationCommand extends Command {

    /**
     * @var bool
     */
    private $useProxy = false;

    private $type = 'host';

    private $hostUuid = '';

    private $serviceUuid = '';

    /**
     * 0 = Up, 1 = Down, 2 = Unreachable
     * 0 = Ok, 1 = Warning, 2 = Critical, 3 = Unknown
     *
     * @var null|int
     */
    private $state = null;

    private $output = '';

    /**
     * PROBLEM", "RECOVERY", "ACKNOWLEDGEMENT", "FLAPPINGSTART", "FLAPPINGSTOP",
     * "FLAPPINGDISABLED", "DOWNTIMESTART", "DOWNTIMEEND", or "DOWNTIMECANCELLED"
     *
     * @var string
     */
    private $notificationtype = '';

    private $ackAuthor = false;

    private $ackComment = false;

    /**
     * @var array
     */
    private $hostColors = [
        0 => '#5cb85c', //Up
        1 => '#d9534f', //Down
        2 => '#adadad' //Unreachable
    ];

    /**
     * @var array
     */
    private $serviceColors = [
        0 => '#5cb85c', //Ok
        1 => '#f0ad4e', //Warning
        2 => '#d9534f', //Critical
        3 => '#adadad' //Unknown
    ];

    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @var array
     */
    private $mattermostSettings = [];

    /**
     * @var bool
     */
    private $noEmoji = false;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOptions([
            'type'             => ['short' => 't', 'help' => __d('oitc_console', 'Type of the notification host or service')],
            'notificationtype' => ['help' => __d('oitc_console', 'Notification type of monitoring engine => $NOTIFICATIONTYPE$ ')],
            'hostuuid'         => ['help' => __d('oitc_console', 'Host uuid you want to send a notification => $HOSTNAME$')],
            'serviceuuid'      => ['help' => __d('oitc_console', 'Service uuid you want to send a notification => $SERVICEDESC$')],
            'state'            => ['help' => __d('oitc_console', 'current host state => $HOSTSTATEID$/$SERVICESTATEID$')],
            'output'           => ['help' => __d('oitc_console', 'host output => $HOSTOUTPUT$/$SERVICEOUTPUT$')],
            'ackauthor'        => ['help' => __d('oitc_console', 'host acknowledgement author => $NOTIFICATIONAUTHOR$')],
            'ackcomment'       => ['help' => __d('oitc_console', 'host acknowledgement comment => $NOTIFICATIONCOMMENT$')],
            'no-emoji'         => ['help' => __d('oitc_console', 'Disable emojis in subject'), 'boolean' => true, 'default' => false]
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->validateOptions($args);


        /** @var MattermostSettingsTable $MattermostSettingsTable */
        $MattermostSettingsTable = TableRegistry::getTableLocator()->get('MattermostModule.MattermostSettings');
        $this->mattermostSettings = $MattermostSettingsTable->getMattermostSettings();

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $result = $SystemsettingsTable->getSystemsettingByKey('SYSTEM.ADDRESS');
        $this->baseUrl = sprintf('https://%s', $result->get('value'));

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');

        try {
            $host = $HostsTable->getHostByUuid($this->hostUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new \RuntimeException(sprintf('Host with uuid "%s" could not be found!', $this->hostUuid));
        }

        $Host = new Host($host);

        if ($this->type === 'service') {
            /** @var ServicesTable $ServicesTable */
            $ServicesTable = TableRegistry::getTableLocator()->get('Services');

            try {
                $service = $ServicesTable->getServiceByUuid($this->serviceUuid, false);
            } catch (RecordNotFoundException $e) {
                throw new \RuntimeException(sprintf('Service with uuid "%s" could not be found!', $this->serviceUuid));
            }

            $Service = new Service($service);
            $this->sendServiceNotification($Host, $Service);
            exit(0);
        }


        $this->sendHostNotification($Host);
        exit(0);
    }

    private function sendHostNotification(Host $Host) {
        $HoststatusIcon = new HoststatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for [%s](%s/a/hosts/browser/%s) (%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $HoststatusIcon->getHumanState()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::speechBalloon() . ' ' . $title;
            }

            return $this->sendDefaultMessage($title);
        }

        if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Downtime started for [%s](%s/a/hosts/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::zzz() . ' ' . $title;
            }
            return $this->sendDefaultMessage($title);
        }

        if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Downtime end for [%s](%s/a/hosts/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::eightSpokedAsterisk() . ' ' . $title;
            }

            return $this->sendDefaultMessage($title);
        }

        if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Downtime cancelled for [%s](%s/a/hosts/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::wastebasket() . ' ' . $title;
            }
            return $this->sendDefaultMessage($title);
        }

        if ($this->isFlappingStart()) {
            $title = sprintf(
                'Flapping started on [%s](%s/a/hosts/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid()
            );

            return $this->sendDefaultMessage($title);
        }

        if ($this->isFlappingStop()) {
            $title = sprintf(
                'Flapping stopped on [%s](%s/a/hosts/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid()
            );

            return $this->sendDefaultMessage($title);
        }

        if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Disabled flap detection for [%s](%s/a/hosts/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid()
            );

            return $this->sendDefaultMessage($title);
        }

        //Default notification
        $title = sprintf(
            '%s: [%s](%s/a/hosts/browser/%s) is %s!',
            $this->notificationtype,
            $Host->getHostname(),
            $this->baseUrl,
            $Host->getUuid(),
            $HoststatusIcon->getHumanState()
        );

        if ($this->noEmoji === false) {
            $title = $HoststatusIcon->getEmoji() . ' ' . $title;
        }

        $attachments = [
            [
                'text'            => $title . "\n" . $this->output,
                "mrkdwn_in"       => ["text"],
                'color'           => $this->hostColors[$HoststatusIcon->getState()],
                'callback_id'     => 'info',
                'attachment_type' => 'default'
            ]
        ];

        if ($HoststatusIcon->getState() !== 0 && $this->mattermostSettings['two_way'] === true) {
            $attachments[] = [
                'text'            => "Click to acknowledge this issue.",
                'fallback'        => 'You are unable to acknowledge this',
                'color'           => $this->hostColors[$HoststatusIcon->getState()],
                'callback_id'     => 'ack_host',
                'attachment_type' => 'default',
                'actions'         => [
                    [
                        'name'        => 'Acknowledge',
                        'integration' => [
                            'url' => sprintf(
                                '%s/mattermost_module/acknowledge/host.json?uuid=%s&apikey=%s',
                                $this->baseUrl,
                                $Host->getUuid(),
                                $this->mattermostSettings['apikey']
                            )
                        ]
                    ]
                ]
            ];
        }

        $Client = $this->getClient([
            'json' => [
                'text'        => '',
                'attachments' => $attachments
            ]
        ]);

        $Client->request('post');
    }

    private function sendServiceNotification(Host $Host, Service $Service) {
        $ServicestatusIcon = new ServicestatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for service [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s) (%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid(),
                $ServicestatusIcon->getHumanState()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::speechBalloon() . ' ' . $title;
            }

            return $this->sendDefaultMessage($title);
        }

        if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Downtime start for service [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::zzz() . ' ' . $title;
            }

            return $this->sendDefaultMessage($title);
        }

        if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Downtime end for service [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::eightSpokedAsterisk() . ' ' . $title;
            }

            return $this->sendDefaultMessage($title);
        }

        if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Downtime cancelled for service [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::wastebasket() . ' ' . $title;
            }

            return $this->sendDefaultMessage($title);
        }

        if ($this->isFlappingStart()) {
            $title = sprintf(
                'Flapping started on [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid()
            );

            return $this->sendDefaultMessage($title);
        }

        if ($this->isFlappingStop()) {
            $title = sprintf(
                'Flapping stopped on [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid()
            );

            return $this->sendDefaultMessage($title);
        }

        if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Disabled flap detection for [%s](%s/a/hosts/browser/%s)/[%s](%s/a/services/browser/%s)',
                $Host->getHostname(),
                $this->baseUrl,
                $Host->getUuid(),
                $Service->getServicename(),
                $this->baseUrl,
                $Service->getUuid()
            );

            return $this->sendDefaultMessage($title);
        }

        //Default notification
        $title = sprintf(
            '%s: [%s](%s/a/services/browser/%s) on [%s](%s/a/hosts/browser/%s) is %s!',
            $this->notificationtype,
            $Service->getServicename(),
            $this->baseUrl,
            $Service->getUuid(),
            $Host->getHostname(),
            $this->baseUrl,
            $Host->getUuid(),
            $ServicestatusIcon->getHumanState()
        );

        if ($this->noEmoji === false) {
            $title = $ServicestatusIcon->getEmoji() . ' ' . $title;
        }

        $attachments = [
            [
                'text'            => $title . "\n" . $this->output,
                "mrkdwn_in"       => ["text"],
                'color'           => $this->serviceColors[$ServicestatusIcon->getState()],
                'callback_id'     => 'info',
                'attachment_type' => 'default'
            ]
        ];

        if ($ServicestatusIcon->getState() !== 0 && $this->mattermostSettings['two_way'] === true) {
            $attachments[] = [
                'text'            => "Click to acknowledge this issue.",
                'fallback'        => 'You are unable to acknowledge this',
                'color'           => $this->serviceColors[$ServicestatusIcon->getState()],
                'callback_id'     => 'ack_host',
                'attachment_type' => 'default',
                'actions'         => [
                    [
                        'name'        => 'Acknowledge',
                        'integration' => [
                            'url' => sprintf(
                                '%s/mattermost_module/acknowledge/service.json?hostuuid=%s&serviceuuid=%s&apikey=%s',
                                $this->baseUrl,
                                $Host->getUuid(),
                                $Service->getUuid(),
                                $this->mattermostSettings['apikey']
                            )
                        ]
                    ]
                ]
            ];
        }

        $Client = $this->getClient([
            'json' => [
                'text'        => '',
                'attachments' => $attachments
            ]
        ]);

        $Client->request('post');
    }

    private function sendDefaultMessage($title) {
        $Client = $this->getClient([
            'json' => [
                'text'        => '',
                'attachments' => [
                    [
                        'text'            => $title,
                        "mrkdwn_in"       => ["text"],
                        'color'           => '#1895f5', //Info color
                        'callback_id'     => 'info',
                        'attachment_type' => 'default'
                    ]
                ]
            ]
        ]);

        $Client->request('post');

    }

    /**
     * @param $options
     * @return Client
     */
    private function getClient($options) {
        $defaults = [
            'base_uri' => $this->mattermostSettings['webhook_url'],
            'headers'  => [
                'Accept' => 'application/json'
            ]
        ];
        $options = Hash::merge($defaults, $options);

        if ($this->mattermostSettings['use_proxy']) {
            /** @var ProxiesTable $ProxiesTable */
            $ProxiesTable = TableRegistry::getTableLocator()->get('Proxies');
            $proxy = $ProxiesTable->getSettings();

            $options['proxy'] = [
                'http'  => sprintf('%s:%s', $proxy['ipaddress'], $proxy['port']),
                'https' => sprintf('%s:%s', $proxy['ipaddress'], $proxy['port'])
            ];
        } else {
            $options['proxy'] = [
                'http'  => false,
                'https' => false
            ];
        }

        return new Client($options);
    }

    private function isAcknowledgement() {
        return $this->notificationtype === 'ACKNOWLEDGEMENT';
    }

    private function isFlappingStart() {
        return $this->notificationtype === 'FLAPPINGSTART';
    }

    private function isFlappingStop() {
        return $this->notificationtype === 'FLAPPINGSTOP';
    }

    private function isFlappingDisabled() {
        return $this->notificationtype === 'FLAPPINGDISABLED';
    }

    private function isDowntimeStart() {
        return $this->notificationtype === 'DOWNTIMESTART';
    }

    private function isDowntimeEnd() {
        return $this->notificationtype === 'DOWNTIMEEND';
    }

    private function isDowntimeCancelled() {
        return $this->notificationtype === 'DOWNTIMECANCELLED';
    }

    /**
     * @param Arguments $args
     * @throws \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions
     */
    private function validateOptions(Arguments $args) {
        $this->useProxy = $args->getOption('proxy');

        if ($args->getOption('type') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --type is missing'
            );
        }

        $this->state = 2;
        $this->type = strtolower($args->getOption('type'));

        if ($args->getOption('notificationtype') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --notificationtype is missing'
            );
        }
        $this->notificationtype = $args->getOption('notificationtype');

        if ($args->getOption('hostuuid') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --hostuuid is missing'
            );
        }
        $this->hostUuid = $args->getOption('hostuuid');

        if ($this->type === 'service') {
            $this->state = 3;
            if ($args->getOption('serviceuuid') === '') {
                throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                    'Option --serviceuuid is missing'
                );
            }
            $this->serviceUuid = $args->getOption('serviceuuid');
        }

        if ($args->getOption('state') !== '') {
            //Not all notifications have a state like ack or downtime messages.
            $this->state = (int)$args->getOption('state');
        }


        if ($args->getOption('output') === '') {
            throw new \itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions(
                'Option --output is missing'
            );
        }
        $this->output = $args->getOption('output');

        if ($args->getOption('ackauthor') === '') {
            $this->ackAuthor = $args->getOption('ackauthor');
        }

        if ($args->getOption('ackcomment') === '') {
            $this->ackComment = $args->getOption('ackcomment');
        }

        $this->noEmoji = $args->getOption('no-emoji');
    }
}
