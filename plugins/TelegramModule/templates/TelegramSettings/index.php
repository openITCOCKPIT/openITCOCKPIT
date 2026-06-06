<?php
// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT
?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fas fa-puzzle-piece"></i> <?php echo __('Telegram Module'); ?>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="TelegramSettingsIndex">
            <i class="fa fa-gears"></i> <?php echo __('Configuration'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fa fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Telegram'); ?>
                    <span class="fw-300"><i><?php echo __('Configuration'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <button class="btn btn-xs btn-default mr-1 shadow-0" data-toggle="modal" data-target="#howtoModal">
                        <i class="fas fa-question-circle"></i> <?php echo __('HowTo'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" class="form-horizontal">
                        <div class="form-group required" ng-class="{'has-error':errors.webhook_url}">
                            <label class="control-label">
                                <?php echo __('Token'); ?>
                            </label>
                            <input
                                    class="form-control"
                                    type="text"
                                    placeholder="123456789:YYKKKSNNIBBBBAAXXXXATCCC234567...."
                                    ng-model="telegramSettings.token">
                            <div ng-repeat="error in errors.token">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                            <div class="help-block">
                                <?= __('Telegram bot token'); ?>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.two_way}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.two_way}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="enableTwoWayWebook"
                                       ng-model="telegramSettings.two_way">
                                <label class="custom-control-label" for="enableTwoWayWebook">
                                    <?php echo __('Enable two-way webhook integration'); ?>
                                </label>
                                <div class="help-block">
                                    <?php echo __('If this option is activated, this openITCOCKPIT instance must be accessible via the Internet.'); ?>
                                    <br>
                                    <?php echo __('By using the two-way webhook integration, interactions in Telegram are sent directly to this openITCOCKPIT instance. Otherwise these can only be queried every minute. Please read the documentation to make this setup successful.'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="panel-container show" ng-if="telegramSettings.two_way">
                            <div class="panel-content">
                                <div class="form-group required"
                                     ng-class="{'has-error':errors.external_webhook_domain}">
                                    <label class="control-label">
                                        <?php echo __('External webhook domain'); ?>
                                    </label>
                                    <input
                                            class="form-control"
                                            type="text"
                                            placeholder="https://demo.openitcockpit.io"
                                            ng-model="telegramSettings.external_webhook_domain">
                                    <div ng-repeat="error in errors.external_webhook_domain">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                    <div class="help-block">
                                        <?= __('Enter the externally accessible domain name of this openITCOCKPIT instance.'); ?>
                                        <br>
                                        <?= __('The webhook will be automatically set up for the bot based on the given bot token.'); ?>
                                    </div>
                                </div>

                                <div class="form-group required" ng-class="{'has-error':errors.webhook_api_key}">
                                    <label class="control-label">
                                        <?php echo __('Webhook api key'); ?>
                                    </label>
                                    <input
                                            class="form-control"
                                            type="text"
                                            placeholder=""
                                            ng-model="telegramSettings.webhook_api_key">
                                    <div ng-repeat="error in errors.webhook_api_key">
                                        <div class="help-block text-danger">{{ error }}</div>
                                    </div>
                                    <div class="help-block">
                                        <a href="javascript:void(0);" data-toggle="modal"
                                           data-target="#ApiKeyOverviewModal">
                                            <?= __('API Key') ?>
                                        </a>
                                        <?= __('used by Telegram for authentication against openITCOCKPIT.'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" ng-class="{'has-error': errors.use_proxy}">
                            <div class="custom-control custom-checkbox  margin-bottom-10"
                                 ng-class="{'has-error': errors.use_proxy}">

                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="use_proxy"
                                       ng-model="telegramSettings.use_proxy">
                                <label class="custom-control-label" for="use_proxy">
                                    <?php echo __('Use Proxy'); ?>
                                </label>
                                <div class="help-block">
                                    <?php
                                    if ($this->Acl->hasPermission('index', 'proxy', '')):
                                        echo __('Determine if the <a ui-sref="ProxyIndex">configured proxy</a> should be used.');
                                    else:
                                        echo __('Determine if the configured proxy should be used.');
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <input class="btn btn-primary" type="submit"
                                           value="<?= __('Save configuration') ?>">&nbsp;
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <div>
                    <h2>
                        <?php echo __('Authentication codes for Telegram notification contacts'); ?>
                    </h2>
                    <div class="help-block margin-bottom-10">
                        <?= __('Generate authentication codes for openITCOCKPIT Contacts to use them to authenticate yourself in the Telegram chat using the connected Telegram bot.'); ?>
                        <br>
                        <?= __('As soon as a chat has been authenticated, the key can be deleted to prevent misuse.') ?>
                    </div>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="card margin-top-2" ng-repeat="contact in contacts">
                        <div class="card-body">
                            <div class="form-group">
                                <span style="min-width: 350px;display: inline-block;">
                                    {{contact.name}}
                                </span>
                                <span ng-if="isContactAccessKeyGenerated(contact.uuid) == false">
                                    <a class="btn btn-xs btn-success mr-1 shadow-0"
                                       ng-click="generateAccessKeyForContact(contact.uuid)"
                                       href="javascript:void(0);">
                                        <?= __('Generate key') ?>
                                    </a>
                                </span>
                                <span ng-if="isContactAccessKeyGenerated(contact.uuid) != false">
                                    <code>{{isContactAccessKeyGenerated(contact.uuid)}}</code>
                                    <a class="btn btn-xs btn-danger mr-1 shadow-0 margin-left-5"
                                       ng-click="removeAccessKeyForContact(contact.uuid)"
                                       href="javascript:void(0);">
                                        <?= __('Delete key') ?>
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <div>
                    <h2>
                        <?php echo __('Registered Telegram chats'); ?>
                    </h2>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="card margin-top-2" ng-repeat="chat in chats">
                        <div class="card-body">
                            <div class="form-group">
                                <span style="min-width: 350px;display: inline-block;">
                                    Contact:
                                    <?php if ($this->Acl->hasPermission('edit', 'contacts')): ?>
                                        <a ui-sref="ContactsEdit({id: getContactForUuid(chat.contact_uuid).id})">
                                            {{getContactForUuid(chat.contact_uuid).name}}
                                        </a>
                                    <?php else: ?>
                                        {{getContactForUuid(chat.contact_uuid).name}}
                                    <?php endif; ?>
                                    &nbsp;| Telegram user: {{chat.started_from_username}}
                                </span>
                                <span>
                                    <button class="btn btn-xs btn-success mr-1 shadow-0"
                                            ng-click="sendTestChatMessage(chat.id)">
                                        <i class="fas fa-message"></i> <?php echo __('Send test chat message'); ?>
                                    </button>
                                    <a class="btn btn-xs btn-danger mr-1 shadow-0"
                                       ng-click="deleteChat(chat.id)"
                                       href="javascript:void(0);">
                                        <?= __('Delete chat connection / Revoke authorization') ?>
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HowTo modal -->
<div id="howtoModal" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle"></i>
                    <?php echo __('Telegram module - HowTo'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <h4>
                                <?= __('Configuration') ?>
                            </h4>
                            <ul>
                                <li>
                                    <?php echo sprintf(__('Open %s in Telegram.'), '<a href="https://t.me/botfather" target="_blank">BotFather</a>'); ?>
                                </li>
                                <li>
                                    <?php echo sprintf(__('Run %s'), '<code>/newbot</code>'); ?>
                                    <ul>
                                        <li><?= __('define e.g. openITCOCKPIT as name') ?></li>
                                        <li><?= __('define e.g. oitcTGBot as username') ?></li>
                                    </ul>
                                </li>
                                <li>
                                    <?php echo sprintf(__('Your new bot is now located at %s.'), '<a href="https://t.me/oitcTGBot" target="_blank">t.me/oitcTGBot</a>'); ?>
                                </li>
                                <li>
                                    <?= __('Copy the HTTP API token which was generated by the BotFather.') ?>
                                </li>
                                <li>
                                    <b><?= __('Keep your token secure and store it safely, it can be used by anyone to control your bot!') ?></b>
                                </li>
                                <li>
                                    <?= __('Open the openITCOCKPIT Telegram configuration, enter the copied bot token and save the configuration.') ?>
                                </li>
                                <li>
                                    <?= __("Add the 'host-notify-by-telegram' and 'service-notify-by-telegram' commands to your notification contact.") ?>
                                </li>
                                <li>
                                    <?= __("To apply the changed notification contact (with the Telegram notification commands), run an export in openITCOCKPIT.") ?>
                                </li>
                            </ul>

                            <b><?= __('If your openITCOCKPIT is reachable from the Internet:') ?></b>
                            <br><br>
                            <ul>
                                <li>
                                    <?= __('Open the openITCOCKPIT Telegram configuration and check the optional field "Enable two-way webhook integration".') ?>
                                </li>
                                <li>
                                    <?= __('Make sure the configured "External webhook domain" is correct and openITCOCKPIT is reachable from the Internet using this domain.') ?>
                                </li>
                                <li>
                                    <?= __('Create a new openITCOCKPIT API Key for your user and insert it into the "Webhook api key" field.') ?>
                                </li>
                                <li>
                                    <?= __('Save the changes and try it out.') ?>
                                    <br>
                                    <?= __('openITCOCKPIT automatically set up the webhook for the bot with the given token.') ?>
                                    <br>
                                    <?= __('This connection can be removed by saving with an unchecked "Enable two-way webhook integration" field.') ?>
                                </li>
                            </ul>
                        </div>

                        <div class="col-12">
                            <h4>
                                <?= __('Usage') ?>
                            </h4>
                            <ul>
                                <li>
                                    <?= __('Notifications sent by the openITCOCKPIT Telegram Module can be received by any chat with a real telegram user or by channels.') ?>
                                </li>
                                <li>
                                    <?= __('A Telegram chat gets only the notifications for a defined openITCOCKPIT contact. (identified by custom authentication codes)') ?>
                                </li>
                                <li>
                                    <?= __('To start the interaction, search the bot username in the Telegram global search field and start a chat with your bot.') ?>
                                </li>
                                <li>
                                    <?= __('After starting the chat you need to authorize yourself with an authentication code for a openITCOCKPIT Contact, that can be manually generated in the openITCOCKPIT Telegram configuration for Contacts containing a Telegram notification command. (type <code>/auth xxx</code> in the chat with your key as xxx)') ?>
                                </li>
                                <li>
                                    <?= __('If the authorization was successful, use the bot control command <code>/start</code> to enable openITCOCKPIT notifications. Otherwise you will not receive any notifications.') ?>
                                </li>
                                <li>
                                    <?= __('With enabled two-way integration issues can be acknowledged by simply clicking the button for an action provided by the bot message.') ?>
                                </li>
                                <li>
                                    <?= __('If your openITCOCKPIT is reachable from the Internet and you configured the two-way webhook integration, interactions with the bot are processed within seconds.') ?>

                                </li>
                                <li>
                                    <?= __('If your openITCOCKPIT is not reachable from the Internet, the built-in basic one-way integration calls up interactions cached by Telegram and processes them every minute. (Therefore, interactions with the bot can take up to a minute to process!)') ?>
                                </li>
                                <li>
                                    <?php echo sprintf(__('Run %s in bot chat to get more information about how to control the bot.'), '<code>/help</code>'); ?>
                                </li>
                            </ul>
                        </div>

                        <div class="col-12">
                            <h4>
                                <?= __('Troubleshooting') ?>
                            </h4>
                            <ul>
                                <li>
                                    <?= __('If you aren\'t using the two-way integration or switching from two-way to one-way, it could happen that the cronjob is not going to be executed by openITCOCKPIT.') ?>
                                    <ul>
                                        <li><?= sprintf(__('That problem should be solved after running it once manually: %s'), '<code>oitc cronjobs -f -t TelegramProcessUpdates</code>') ?></li>
                                        <li><?= __('Note that the cron job is not needed if you use a two-way setup of this plugin') ?></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php echo $this->element('apikey_help'); ?>
