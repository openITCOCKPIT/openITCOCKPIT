<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace TelegramModule\Command;

// require manual autoload for core-called files, since openitcockpit core does not autoload plugin dependencies
require_once dirname(__FILE__) . '/../../vendor/autoload.php';

use App\Model\Table\HostsTable;
use App\Model\Table\ProxiesTable;
use App\Model\Table\ServicesTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions;
use itnovum\openITCOCKPIT\Core\Views\Host;
use itnovum\openITCOCKPIT\Core\Views\HoststatusIcon;
use itnovum\openITCOCKPIT\Core\Views\Service;
use itnovum\openITCOCKPIT\Core\Views\ServicestatusIcon;
use itnovum\openITCOCKPIT\Exceptions\HostNotFoundException;
use itnovum\openITCOCKPIT\Exceptions\ServiceNotFoundException;
use Spatie\Emoji\Emoji;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use TelegramBot\Api\Types\Message;
use TelegramModule\Lib\TelegramInfoMessages;
use TelegramModule\Model\Table\TelegramChatsTable;
use TelegramModule\Model\Table\TelegramSettingsTable;

/**
 * TelegramModule command.
 */
class TelegramNotificationCommand extends BaseCommand {

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

    private $ackAuthor = '';

    private $ackComment = '';

    private $contactUuid = '';

    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @var bool
     */
    private $noEmoji = false;

    /**
     * @var BotApi
     */
    private $bot = BotApi::class;

    /**
     * @var ResultSetInterface
     */
    private $telegramChats;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param ConsoleOptionParser $parser The parser to be defined
     * @return ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOptions([
            'type'             => [
                'short' => 't',
                'help'  => __d('oitc_console', 'Type of the notification host or service')
            ],
            'notificationtype' => [
                'help' => __d(
                    'oitc_console',
                    'Notification type of monitoring engine => $NOTIFICATIONTYPE$ '
                )
            ],
            'hostuuid'         => [
                'help' => __d(
                    'oitc_console',
                    'Host uuid you want to send a notification => $HOSTNAME$'
                )
            ],
            'serviceuuid'      => [
                'help' => __d(
                    'oitc_console',
                    'Service uuid you want to send a notification => $SERVICEDESC$'
                )
            ],
            'state'            => [
                'help' => __d(
                    'oitc_console',
                    'current host state => $HOSTSTATEID$/$SERVICESTATEID$'
                )
            ],
            'output'           => ['help' => __d('oitc_console', 'host output => $HOSTOUTPUT$/$SERVICEOUTPUT$')],
            'ackauthor'        => [
                'help' => __d(
                    'oitc_console',
                    'host acknowledgement author => $NOTIFICATIONAUTHOR$'
                )
            ],
            'ackcomment'       => [
                'help' => __d(
                    'oitc_console',
                    'host acknowledgement comment => $NOTIFICATIONCOMMENT$'
                )
            ],
            'contactuuid'      => [
                'help' => __d(
                    'oitc_console',
                    'Send notification to all Telegram chats with the given Contact uuid => $CONTACTNAME$'
                )
            ],
            'no-emoji'         => [
                'help'    => __d('oitc_console', 'Disable emojis in subject'),
                'boolean' => true,
                'default' => false
            ]
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param Arguments $args The command arguments.
     * @param ConsoleIo $io The console io
     * @return void The exit code or null for success
     * @throws MissingParameterExceptions
     * @throws HostNotFoundException
     * @throws ServiceNotFoundException
     * @throws \Exception
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->validateOptions($args);


        /** @var TelegramSettingsTable $telegramSettingsTable */
        $telegramSettingsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramSettings');
        $telegramSettings = $telegramSettingsTable->getTelegramSettings();

        /** @var TelegramChatsTable $telegramChatsTable */
        $telegramChatsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramChats');
        $this->telegramChats = $telegramChatsTable->getTelegramChats();

        /** @var SystemsettingsTable $systemsettingsTable */
        $systemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        /** @var ProxiesTable $proxiesTable */
        $proxiesTable = TableRegistry::getTableLocator()->get('Proxies');
        $proxySettings = $proxiesTable->getSettings();

        $result = $systemsettingsTable->getSystemsettingByKey('SYSTEM.ADDRESS');
        $this->baseUrl = sprintf('https://%s', $result->get('value'));

        /** @var HostsTable $hostsTable */
        $hostsTable = TableRegistry::getTableLocator()->get('Hosts');

        try {
            $host = $hostsTable->getHostByUuid($this->hostUuid, false);
        } catch (RecordNotFoundException $e) {
            throw new HostNotFoundException(sprintf('Host with uuid "%s" could not be found!', $this->hostUuid));
        }

        $this->bot = new BotApi($telegramSettings->get('token'));
        if ($telegramSettings->get('use_proxy') && $proxySettings['enabled'] == 1) {
            $this->bot->setProxy(sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port']));
        }

        $hostView = new Host($host);

        if ($this->type === 'service') {
            /** @var ServicesTable $servicesTable */
            $servicesTable = TableRegistry::getTableLocator()->get('Services');

            try {
                $service = $servicesTable->getServiceByUuid($this->serviceUuid, false);
            } catch (RecordNotFoundException $e) {
                throw new ServiceNotFoundException(
                    sprintf('Service with uuid "%s" could not be found!', $this->serviceUuid)
                );
            }

            $serviceView = new Service($service);
            $this->sendServiceNotification($hostView, $serviceView);
            exit(0);
        }

        $this->sendHostNotification($hostView);
        exit(0);
    }

    /**
     * @param int $chat_id
     * @return Message
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public static function sendTestChatMessage(int $chat_id): Message {
        /** @var TelegramSettingsTable $telegramSettingsTable */
        $telegramSettingsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramSettings');
        $telegramSettings = $telegramSettingsTable->getTelegramSettings();

        /** @var ProxiesTable $proxiesTable */
        $proxiesTable = TableRegistry::getTableLocator()->get('Proxies');
        $proxySettings = $proxiesTable->getSettings();

        $bot = new BotApi($telegramSettings->get('token'));
        if ($telegramSettings->get('use_proxy') && $proxySettings['enabled'] == 1) {
            $bot->setProxy(sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port']));
        }

        return $bot->sendMessage(
            $chat_id,
            TelegramInfoMessages::getText(TelegramInfoMessages::MESSAGE_TEST),
            "Markdown"
        );
    }

    private function sendHostNotification(Host $hostView) {
        $hostStatusIcon = new HoststatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for [%s](%s/#!/hosts/browser/%s) (%s) by %s (Comment: %s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $hostStatusIcon->getHumanState(),
                $this->ackAuthor,
                $this->ackComment
            );

            if ($this->noEmoji === false) {
                $title = Emoji::speechBalloon() . ' ' . $title;
            }

            $this->sendMessage($title);
        } else if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Downtime started for [%s](%s/#!/hosts/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::zzz() . ' ' . $title;
            }
            $this->sendMessage($title);
        } else if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Downtime end for [%s](%s/#!/hosts/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::eightSpokedAsterisk() . ' ' . $title;
            }

            $this->sendMessage($title);
        } else if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Downtime cancelled for [%s](%s/#!/hosts/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::wastebasket() . ' ' . $title;
            }
            $this->sendMessage($title);
        } else if ($this->isFlappingStart()) {
            $title = sprintf(
                'Flapping started on [%s](%s/#!/hosts/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid()
            );

            $this->sendMessage($title);
        } else if ($this->isFlappingStop()) {
            $title = sprintf(
                'Flapping stopped on [%s](%s/#!/hosts/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid()
            );

            $this->sendMessage($title);
        } else if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Disabled flap detection for [%s](%s/#!/hosts/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid()
            );

            $this->sendMessage($title);
        } else {
            //Default notification
            $title = sprintf(
                '%s: [%s](%s/#!/hosts/browser/%s) is %s!',
                $this->notificationtype,
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $hostStatusIcon->getHumanState()
            );

            if ($this->noEmoji === false) {
                $title = $hostStatusIcon->getEmoji() . ' ' . $title;
            }

            $text = $title . "\n" . $this->output;

            $inlineKeyboardMarkup = new InlineKeyboardMarkup([]);
            if ($hostStatusIcon->getState() !== 0) {
                $inlineKeyboardMarkup->setInlineKeyboard([
                    [
                        [
                            'text'          => __d(
                                'oitc_console',
                                "Click to acknowledge this issue."
                            ),
                            'callback_data' => "ack_host_" . $hostView->getUuid()
                        ]
                    ]
                ]);
            }

            $this->sendMessage($text, $inlineKeyboardMarkup);
        }
    }

    private function sendServiceNotification(Host $hostView, Service $serviceView) {
        $servicestatusIcon = new ServicestatusIcon($this->state);

        if ($this->isAcknowledgement()) {
            $title = sprintf(
                'Acknowledgement for service [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s) (%s) by %s (Comment: %s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid(),
                $servicestatusIcon->getHumanState(),
                $this->ackAuthor,
                $this->ackComment
            );

            if ($this->noEmoji === false) {
                $title = Emoji::speechBalloon() . ' ' . $title;
            }

            $this->sendMessage($title);
        } else if ($this->isDowntimeStart()) {
            $title = sprintf(
                'Downtime start for service [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::zzz() . ' ' . $title;
            }

            $this->sendMessage($title);
        } else if ($this->isDowntimeEnd()) {
            $title = sprintf(
                'Downtime end for service [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::eightSpokedAsterisk() . ' ' . $title;
            }

            $this->sendMessage($title);
        } else if ($this->isDowntimeCancelled()) {
            $title = sprintf(
                'Downtime cancelled for service [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid()
            );

            if ($this->noEmoji === false) {
                $title = Emoji::wastebasket() . ' ' . $title;
            }

            $this->sendMessage($title);
        } else if ($this->isFlappingStart()) {
            $title = sprintf(
                'Flapping started on [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid()
            );

            $this->sendMessage($title);
        } else if ($this->isFlappingStop()) {
            $title = sprintf(
                'Flapping stopped on [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid()
            );

            $this->sendMessage($title);
        } else if ($this->isFlappingDisabled()) {
            $title = sprintf(
                'Disabled flap detection for [%s](%s/#!/hosts/browser/%s)/[%s](%s/#!/services/browser/%s)',
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid()
            );

            $this->sendMessage($title);
        } else {
            //Default notification
            $title = sprintf(
                '%s: [%s](%s/#!/services/browser/%s) on [%s](%s/#!/hosts/browser/%s) is %s!',
                $this->notificationtype,
                $serviceView->getServicename(),
                $this->baseUrl,
                $serviceView->getUuid(),
                $hostView->getHostname(),
                $this->baseUrl,
                $hostView->getUuid(),
                $servicestatusIcon->getHumanState()
            );

            if ($this->noEmoji === false) {
                $title = $servicestatusIcon->getEmoji() . ' ' . $title;
            }

            $text = $title . "\n" . $this->output;

            $inlineKeyboardMarkup = new InlineKeyboardMarkup([]);
            if ($servicestatusIcon->getState() !== 0) {
                $inlineKeyboardMarkup->setInlineKeyboard([
                    [
                        [
                            'text'          => __d(
                                'oitc_console',
                                "Click to acknowledge this issue."
                            ),
                            'callback_data' => "ack_service_" . $serviceView->getUuid()
                        ]
                    ]
                ]);
            }

            $this->sendMessage($text, $inlineKeyboardMarkup);
        }
    }

    private function sendMessage($text, InlineKeyboardMarkup $inlineKeyboardMarkup = null) {
        if ($this->telegramChats->count() > 0) {
            $this->telegramChats->each(function ($chat, $key) use ($text, $inlineKeyboardMarkup) {
                if (is_array($chat)) {
                    if ($chat['enabled'] && $chat['contact_uuid'] == $this->contactUuid) {
                        $this->bot->sendMessage(
                            $chat['chat_id'],
                            $text,
                            "Markdown",
                            false,
                            null,
                            $inlineKeyboardMarkup
                        );
                    }
                } else {
                    if ($chat->get('enabled') && $chat->get('contact_uuid') == $this->contactUuid) {
                        $this->bot->sendMessage(
                            $chat->get('chat_id'),
                            $text,
                            "Markdown",
                            false,
                            null,
                            $inlineKeyboardMarkup
                        );
                    }
                }
            });
        }
    }

    private function isAcknowledgement(): bool {
        return $this->notificationtype === 'ACKNOWLEDGEMENT';
    }

    private function isFlappingStart(): bool {
        return $this->notificationtype === 'FLAPPINGSTART';
    }

    private function isFlappingStop(): bool {
        return $this->notificationtype === 'FLAPPINGSTOP';
    }

    private function isFlappingDisabled(): bool {
        return $this->notificationtype === 'FLAPPINGDISABLED';
    }

    private function isDowntimeStart(): bool {
        return $this->notificationtype === 'DOWNTIMESTART';
    }

    private function isDowntimeEnd(): bool {
        return $this->notificationtype === 'DOWNTIMEEND';
    }

    private function isDowntimeCancelled(): bool {
        return $this->notificationtype === 'DOWNTIMECANCELLED';
    }

    /**
     * @param Arguments $args
     * @throws MissingParameterExceptions
     */
    private function validateOptions(Arguments $args) {
        if ($args->getOption('type') === '') {
            throw new MissingParameterExceptions(
                'Option --type is missing'
            );
        }

        $this->state = 2;
        $this->type = strtolower($args->getOption('type'));

        if ($args->getOption('notificationtype') === '') {
            throw new MissingParameterExceptions(
                'Option --notificationtype is missing'
            );
        }
        $this->notificationtype = $args->getOption('notificationtype');

        if ($args->getOption('hostuuid') === '') {
            throw new MissingParameterExceptions(
                'Option --hostuuid is missing'
            );
        }
        $this->hostUuid = $args->getOption('hostuuid');

        if ($this->type === 'service') {
            $this->state = 3;
            if ($args->getOption('serviceuuid') === '') {
                throw new MissingParameterExceptions(
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
            throw new MissingParameterExceptions(
                'Option --output is missing'
            );
        }
        $this->output = $args->getOption('output');

        if ($args->getOption('ackauthor') !== '') {
            $this->ackAuthor = $args->getOption('ackauthor');
        }

        if ($args->getOption('ackcomment') !== '') {
            $this->ackComment = $args->getOption('ackcomment');
        }

        if ($args->getOption('contactuuid') !== '') {
            $this->contactUuid = $args->getOption('contactuuid');
        }

        $this->noEmoji = $args->getOption('no-emoji');
    }
}
