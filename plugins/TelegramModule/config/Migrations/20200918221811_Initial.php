<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Class Initial
 *
 * Created via:
 * oitc migrations create -p TelegramModule Initial
 */
class Initial extends BaseMigration {

    public function up(): void {
        if (!$this->hasTable('telegram_settings')) {
            $this->table('telegram_settings')
                ->addColumn('token', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('access_key', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('last_update_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => true,
                ])
                ->addColumn('two_way', 'boolean', [
                    'default' => 0
                ])
                ->addColumn('external_webhook_domain', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('webhook_api_key', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('use_proxy', 'boolean', [
                    'default' => 0
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'null'    => false,
                ])->addColumn('modified', 'datetime', [
                    'default' => null,
                    'null'    => false,
                ])
                ->create();
        }
        if (!$this->hasTable('telegram_chats')) {
            $this->table('telegram_chats')
                ->addColumn('chat_id', 'integer', [
                    'default' => null,
                    'null'    => false,
                ])
                ->addColumn('enabled', 'boolean', [
                    'default' => 1
                ])
                ->addColumn('started_from_username', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'null'    => false,
                ])->addColumn('modified', 'datetime', [
                    'default' => null,
                    'null'    => false,
                ])
                ->create();
        }
    }

    public function down(): void {
        if ($this->hasTable('telegram_settings')) {
            $this->table('telegram_settings')->drop()->save();
        }
        if ($this->hasTable('telegram_chats')) {
            $this->table('telegram_chats')->drop()->save();
        }
    }
}
