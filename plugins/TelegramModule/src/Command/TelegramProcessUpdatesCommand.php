<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace TelegramModule\Command;

use Cake\Console\Arguments;
use Cake\Console\BaseCommand;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use itnovum\openITCOCKPIT\Exceptions\HostNotFoundException;
use itnovum\openITCOCKPIT\Exceptions\ServiceNotFoundException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramModule\Lib\TelegramActions;

/**
 * TelegramModule command, used as cronjob for one-way update processing.
 * Trigger it manually by running: oitc cronjobs -f -t TelegramProcessUpdates
 */
class TelegramProcessUpdatesCommand extends BaseCommand {
    /**
     * @param ConsoleOptionParser $parser
     * @return ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOptions([
            'api-token' => ['help' => __d('oitc_console', 'Overwrites the stored Telegram bot HTTP API token')],
        ]);

        return $parser;
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io): void {
        $io->out('-------------------------------------------------------------------------------');

        if ($args->hasOption('api-token') && $args->getOption('api-token') != '') {
            $token = $args->getOption('api-token');
        }

        try {
            $telegramActions = new TelegramActions(!empty($token) ? $token : null);
            if ($telegramActions->isTwoWayWebhookEnabled()) {
                $io->out('Telegram api requests are disabled (and not needed) because webhooks are configured!');
            } else {
                $this->processOneWayUpdates($telegramActions, $io);
            }
        } catch (\Exception $exception) {
            $io->err($exception->getMessage());
        }
    }

    /**
     * @throws ServiceNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws HostNotFoundException
     */
    private function processOneWayUpdates(TelegramActions $telegramActions, ConsoleIo $io): void {
        $updates = $telegramActions->getUpdates();
        if ($updates instanceof \Exception) {
            if ($updates instanceof Exception && $updates->getCode() == 409) {
                // Conflict: can't use getUpdates method while webhook is active;
                // use bot->deleteWebhook() within $telegramActions->disableWebhook() to delete the webhook first
                $telegramActions->disableWebhook();
                $io->out('Disable possibly misconfigured Telegram api webhook.');

                // fetch updates again after disabling the webhook
                $updates = $telegramActions->getUpdates();
            } else {
                $io->err(sprintf('BotApi error: %d, %s', $updates->getCode(), $updates->getMessage()));
            }
        }

        if (!($updates instanceof \Exception) && sizeof($updates) > 0) {
            $telegramActions->processUpdates($updates);
            $io->out(sizeof($updates) . ' Telegram updates processed.');
        } else {
            $io->out('No Telegram updates processed.');
        }
    }
}
