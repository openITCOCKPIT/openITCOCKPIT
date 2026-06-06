<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

use App\Model\Table\ContactsTable;
use Cake\ORM\TableRegistry;
use Migrations\BaseMigration;
use TelegramModule\Model\Table\TelegramChatsTable;

/**
 * Class MultiContactsSupport
 */
class MultiContactsSupport extends BaseMigration {

    public bool $autoId = false;

    public function up(): void {
        if (!$this->hasTable('telegram_contacts_access_keys')) {
            $this->table('telegram_contacts_access_keys')
                ->addColumn('contact_uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('access_key', 'string', [
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
                ->addPrimaryKey('contact_uuid')
                ->addForeignKey('contact_uuid', 'contacts', 'uuid', [
                    'delete' => 'cascade'
                ])
                ->create();
        }
        if ($this->hasTable('telegram_settings')) {
            $this->table('telegram_settings')
                ->removeColumn('access_key')
                ->save();
        }
        if ($this->hasTable('telegram_chats')) {
            $this->table('telegram_chats')
                ->addColumn('contact_uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->save();
        }

        /** @var $ContactsTable ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Contacts');
        /** @var $TelegramChatsTable TelegramChatsTable */
        $TelegramChatsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramChats');

        $chats = $TelegramChatsTable->getTelegramChats();
        $infoContacts = $ContactsTable->getAllInfoContacts();
        if (isset($infoContacts[0])) {
            foreach ($chats as $chat) {
                $Chat = $TelegramChatsTable->get($chat['id']);
                $Chat->set('contact_uuid', $infoContacts[0]['uuid']);
                $TelegramChatsTable->save($Chat);
            }
        }
    }

    public function down(): void {
        if ($this->hasTable('telegram_contacts_access_keys')) {
            $this->table('telegram_contacts_access_keys')->drop()->save();
        }
        if ($this->hasTable('telegram_settings')) {
            $this->table('telegram_settings')
                ->addColumn('access_key', 'string', [
                    'default' => '',
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->save();
        }
        if ($this->hasTable('telegram_chats')) {
            $this->table('telegram_chats')
                ->removeColumn('contact_uuid')
                ->save();
        }
    }
}
