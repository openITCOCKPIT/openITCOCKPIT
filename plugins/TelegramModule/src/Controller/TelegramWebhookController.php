<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace TelegramModule\Controller;

use itnovum\openITCOCKPIT\Exceptions\HostNotFoundException;
use itnovum\openITCOCKPIT\Exceptions\ServiceNotFoundException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramModule\Lib\TelegramActions;

/**
 * Class TelegramWebhookController
 * @package TelegramModule\Controller
 */
class TelegramWebhookController extends AppController {
    /**
     * @throws ServiceNotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws HostNotFoundException
     */
    public function notify() {
        $this->set('successful', false);
        $telegramActions = new TelegramActions();

        if ($telegramActions->isTwoWayWebhookEnabled()) {
            $update = $telegramActions->parseUpdate($this->request->getData());

            if ($update !== false) {
                $telegramActions->processUpdate($update);
                $this->set('successful', true);
            }
        }

        $this->viewBuilder()->setOption('serialize', [
            'successful'
        ]);
    }
}
