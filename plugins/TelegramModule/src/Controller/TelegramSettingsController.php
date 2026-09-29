<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace TelegramModule\Controller;

use App\Model\Table\CommandsTable;
use App\Model\Table\ContactsTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramModule\Command\TelegramNotificationCommand;
use TelegramModule\Lib\TelegramActions;
use TelegramModule\Model\Entity\TelegramSetting;
use TelegramModule\Model\Table\TelegramChatsTable;
use TelegramModule\Model\Table\TelegramContactsAccessKeysTable;
use TelegramModule\Model\Table\TelegramSettingsTable;

/**
 * TelegramSettings Controller
 *
 * @method TelegramSetting[]|ResultSetInterface paginate($object = null, array $settings = [])
 */
class TelegramSettingsController extends AppController {

    /**
     * Show Telegram Settings / Configuration page.
     * @throws Exception
     * @throws \Exception
     */
    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship html template
            return;
        }

        /** @var TelegramSettingsTable $telegramSettingsTable */
        $telegramSettingsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramSettings');
        $telegramSettings = $telegramSettingsTable->getTelegramSettings();

        /** @var TelegramContactsAccessKeysTable $telegramContactsAccessKeysTable */
        $telegramContactsAccessKeysTable = TableRegistry::getTableLocator()->get(
            'TelegramModule.TelegramContactsAccessKeys'
        );
        $contactsAccessKeys = $telegramContactsAccessKeysTable->getAllAsArray();

        /** @var TelegramChatsTable $telegramChatsTable */
        $telegramChatsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramChats');
        $chats = $telegramChatsTable->getTelegramChats();

        /** @var ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Contacts');
        $contacts = [];
        /*$contactsQuery = $ContactsTable->find()
            ->select([
                'Contacts.name',
                'Contacts.uuid'
            ])
            ->disableHydration()
            ->all();

        if ($contactsQuery !== null) {
            $contacts = $contactsQuery->toArray();
        }*/

        $allContacts = $contactsTable->find()
            ->contain([
                'Containers',
                'HostCommands',
                'ServiceCommands',
                'Customvariables'
            ])
            ->disableHydration()
            ->all();

        /** @var CommandsTable $commandsTable */
        $commandsTable = TableRegistry::getTableLocator()->get('Commands');
        $telegramHostNotificationCommand = $commandsTable->getCommandByName('host-notify-by-telegram', false, false);
        $telegramServiceNotificationCommand = $commandsTable->getCommandByName(
            'service-notify-by-telegram',
            false,
            false
        );

        foreach ($allContacts as $contact) {
            if (in_array($telegramHostNotificationCommand[0]['id'], Hash::extract($contact, 'host_commands.{n}.id')) ||
                in_array(
                    $telegramServiceNotificationCommand[0]['id'],
                    Hash::extract($contact, 'service_commands.{n}.id')
                )) {
                $contacts[] = $contact;
            }
        }

        if ($telegramSettings->get('external_webhook_domain') == "") {
            /** @var SystemsettingsTable $systemsettingsTable */
            $systemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $addressSystemSetting = $systemsettingsTable->getSystemsettingByKey('SYSTEM.ADDRESS');
            $telegramSettings->set(
                'external_webhook_domain',
                sprintf('https://%s', $addressSystemSetting->get('value'))
            );
        }

        if ($this->request->is('get')) {
            $this->set('telegramSettings', $telegramSettings);
            $this->set('contacts', $contacts);
            $this->set('contactsAccessKeys', $contactsAccessKeys);
            $this->set('chats', $chats);
            $this->viewBuilder()->setOption('serialize', [
                'telegramSettings',
                'contacts',
                'contactsAccessKeys',
                'chats'
            ]);
            return;
        }

        if ($this->request->is('post')) {
            $entity = $telegramSettingsTable->getTelegramSettingsEntity();
            $originalTwoWaySetting = $entity->get('two_way');
            $entity = $telegramSettingsTable->patchEntity($entity, $this->request->getData(null, []));

            if (
                ($originalTwoWaySetting || $originalTwoWaySetting == 1) &&
                (!$entity->get('two_way') || $entity->get('two_way') == 0) &&
                $entity->get('token') != ""
            ) {
                //disable Telegram bot webhook
                $telegramActions = new TelegramActions($entity->get('token'));
                $telegramActions->disableWebhook();
            } else if (
                ($entity->get('two_way') || $entity->get('two_way') == 1) &&
                $entity->get('token') != "" &&
                $entity->get('external_webhook_domain') != "" &&
                $entity->get('webhook_api_key') != ""
            ) {
                //enable/update Telegram bot webhook
                $telegramActions = new TelegramActions($entity->get('token'));
                $webhookUrl = sprintf(
                    '%s/telegram_module/telegram_webhook/notify.json?apikey=%s',
                    $entity->get('external_webhook_domain'),
                    $entity->get('webhook_api_key')
                );
                $addressSystemSetting = $telegramActions->enableWebhook($webhookUrl);

                if (!$addressSystemSetting || trim($addressSystemSetting) === '') {
                    $entity->set('two_way', false);
                }
            }

            $telegramSettingsTable->save($entity);
            if ($entity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $this->set('telegramSettings', $entity);
            $this->viewBuilder()->setOption('serialize', [
                'telegramSettings'
            ]);
        }
    }

    /**
     * Generates an access key for the given contact.
     * @return void
     */
    public function genKey() {
        if ($this->isAngularJsRequest() && $this->request->is('post')) {
            $contact_uuid = $this->request->getData('contact_uuid');
            if ($contact_uuid !== null) {
                /** @var ContactsTable $contactsTable */
                $contactsTable = TableRegistry::getTableLocator()->get('Contacts');

                $query = $contactsTable->find()
                    ->where([
                        'Contacts.uuid' => $contact_uuid
                    ])
                    ->disableHydration()
                    ->first();

                if ($query !== null) {
                    /** @var TelegramContactsAccessKeysTable $telegramContactsAccessKeysTable */
                    $telegramContactsAccessKeysTable = TableRegistry::getTableLocator()->get(
                        'TelegramModule.TelegramContactsAccessKeys'
                    );
                    $contactsAccessKey = $telegramContactsAccessKeysTable->getNewOrExistingAccessKeyByContactUuid(
                        $contact_uuid
                    );
                    $telegramContactsAccessKeysTable->saveOrFail($contactsAccessKey);

                    $contactsAccessKeys = $telegramContactsAccessKeysTable->getAllAsArray();
                    $this->set('contactsAccessKeys', $contactsAccessKeys);
                    $this->viewBuilder()->setOption('serialize', [
                        'contactsAccessKeys'
                    ]);
                }
            }
        }
    }

    /**
     * Removes the access key for the given contact.
     * @return void
     */
    public function rmKey() {
        if ($this->isAngularJsRequest() && $this->request->is('post')) {
            $contact_uuid = $this->request->getData('contact_uuid');
            if ($contact_uuid !== null) {
                /** @var ContactsTable $contactsTable */
                $contactsTable = TableRegistry::getTableLocator()->get('Contacts');

                $query = $contactsTable->find()
                    ->where([
                        'Contacts.uuid' => $contact_uuid
                    ])
                    ->disableHydration()
                    ->first();

                if ($query !== null) {
                    /** @var TelegramContactsAccessKeysTable $telegramContactsAccessKeysTable */
                    $telegramContactsAccessKeysTable = TableRegistry::getTableLocator()->get(
                        'TelegramModule.TelegramContactsAccessKeys'
                    );
                    $contactsAccessKey = $telegramContactsAccessKeysTable->getNewOrExistingAccessKeyByContactUuid(
                        $contact_uuid
                    );
                    $telegramContactsAccessKeysTable->deleteOrFail($contactsAccessKey);

                    $contactsAccessKeys = $telegramContactsAccessKeysTable->getAllAsArray();
                    $this->set('contactsAccessKeys', $contactsAccessKeys);
                    $this->viewBuilder()->setOption('serialize', [
                        'contactsAccessKeys'
                    ]);
                }
            }
        }
    }

    /**
     * Delete / revoke authorization for an already connected Telegram chat.
     * @return void
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function rmChat() {
        if ($this->isAngularJsRequest() && $this->request->is('post')) {
            $id = $this->request->getData('id');
            if ($id !== null) {
                /** @var TelegramChatsTable $telegramChatsTable */
                $telegramChatsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramChats');
                $chat = $telegramChatsTable->get($id);

                /** @var TelegramSettingsTable $telegramSettingsTable */
                $telegramSettingsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramSettings');

                if ($chat !== null) {
                    $telegramSettingsEntity = $telegramSettingsTable->getTelegramSettingsEntity();
                    $telegramActions = new TelegramActions($telegramSettingsEntity->get('token'));
                    $telegramActions->notifyChatAboutDeauthorization($chat->chat_id);

                    $telegramChatsTable->deleteOrFail($chat);

                    $chats = $telegramChatsTable->getTelegramChats();
                    $this->set('chats', $chats);
                    $this->viewBuilder()->setOption('serialize', [
                        'chats'
                    ]);
                }
            }
        }
    }

    /**
     * @return void
     */
    public function sendTestChatMessage() {
        if ($this->isAngularJsRequest() && $this->request->is('post')) {
            $success = false;
            $responseMessage = __d(
                'oitc_console',
                'Chat id required'
            );

            $id = $this->request->getData('id');
            if ($id !== null) {
                /** @var TelegramChatsTable $telegramChatsTable */
                $telegramChatsTable = TableRegistry::getTableLocator()->get('TelegramModule.TelegramChats');
                $chat = $telegramChatsTable->get($id);

                if ($chat !== null) {
                    if ($chat->enabled) {
                        try {
                            TelegramNotificationCommand::sendTestChatMessage($chat->chat_id);
                            $responseMessage = __d(
                                'oitc_console',
                                'Test message successfully sent.'
                            );
                            $success = true;
                        } catch (\Exception $e) {
                            $responseMessage = $e->getMessage();
                        }
                    } else {
                        $responseMessage = __d(
                            'oitc_console',
                            'Notifications are not enabled for this chat. Enter /start to enable.'
                        );
                    }
                } else {
                    $responseMessage = __d(
                        'oitc_console',
                        'Chat could not be found'
                    );
                }
            }

            $this->set('responseMessage', $responseMessage);
            $this->set('success', $success);
            $this->viewBuilder()->setOption('serialize', [
                'responseMessage',
                'success'
            ]);
        }
    }
}
