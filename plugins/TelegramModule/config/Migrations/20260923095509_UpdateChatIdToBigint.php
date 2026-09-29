<?php

// SPDX-FileCopyrightText: 2021-2026 Avendis GmbH
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Class MultiContactsSupport
 */
class UpdateChatIdToBigint extends BaseMigration {

    public function up(): void {
        if ($this->hasTable('telegram_chats')) {
            $this->table('telegram_chats')
                ->changeColumn('chat_id', 'biginteger', [
                    'default' => null,
                    'limit'   => 20,
                    'null'    => false,
                    'signed'  => false,
                ])
                ->update();
        }
    }

    public function down(): void {
        if ($this->hasTable('telegram_chats')) {
            $this->table('telegram_chats')
                ->changeColumn('chat_id', 'integer', [
                    'default' => null,
                    'limit'   => 11,
                    'null'    => false,
                    'signed'  => true,
                ])
                ->update();
        }
    }
}
