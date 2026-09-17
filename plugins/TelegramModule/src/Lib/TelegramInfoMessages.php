<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

namespace TelegramModule\Lib;

class TelegramInfoMessages {
    // since php versions < 8.1 have to be supported, and they have no idea about enums, here's the workaround:
    const MESSAGE_WELCOME = 0;
    const MESSAGE_SUCCESSFULLY_ENABLED = 1;
    const MESSAGE_SUCCESSFULLY_DISABLED = 2;
    const MESSAGE_AUTH = 3;
    const MESSAGE_AUTH_SUCCESSFUL = 4;
    const MESSAGE_AUTH_UNSUCCESSFUL = 5;
    const MESSAGE_DELETE_SUCCESSFUL = 6;
    const MESSAGE_DELAY = 7;
    const MESSAGE_HELP = 8;
    const MESSAGE_TEST = 9;

    /**
     * @param int $messageType use TelegramInfoMessages constants as message types
     * @return string
     */
    public static function getText(int $messageType) {
        switch ($messageType) {
            case self::MESSAGE_WELCOME:
                return __d('oitc_console', "Nice to see you %s %s");
            case self::MESSAGE_SUCCESSFULLY_ENABLED:
                return __d('oitc_console', "You have successfully enabled openITCOCKPIT notifications in this chat.");
            case self::MESSAGE_SUCCESSFULLY_DISABLED:
                return __d('oitc_console', "You have successfully disabled openITCOCKPIT notifications in this chat.");
            case self::MESSAGE_AUTH:
                return __d(
                    'oitc_console',
                    "If you want to enable openITCOCKPIT notifications in this chat, you have to authorize yourself with the (in openITCOCKPIT) configured API access key.
Use `/auth xxx` to authorize yourself. Replace xxx with the right API access key."
                );
            case self::MESSAGE_AUTH_SUCCESSFUL:
                return __d('oitc_console', 'The authorization was successful. You are now able to use this bot :)');
            case self::MESSAGE_AUTH_UNSUCCESSFUL:
                return __d('oitc_console', 'Unfortunately the authorization was unsuccessful.');
            case self::MESSAGE_DELETE_SUCCESSFUL:
                return __d(
                    'oitc_console',
                    'Connection successfully deleted. To use this bot again, you will need to re-authorize it.'
                );
            case self::MESSAGE_DELAY:
                return __d(
                    'oitc_console',
                    '_Note: Interactions with this bot are only processed every minute due to the missing webhook configuration. As a result, there may be slight delays in executing commands._'
                );
            case self::MESSAGE_HELP:
                return __d(
                    'oitc_console',
                    "Here are some instructions and commands for using this bot.

*Bot control commands*:

`/auth xxx` authorizes yourself to activate the bot usage
`/start` enables openITCOCKPIT notifications
`/stop` disables openITCOCKPIT notifications
`/help` shows this help text again
`/delete` deletes this bot connection in openITCOCKPIT"
                );
            case self::MESSAGE_TEST:
                return __d('oitc_console', "Test message from Telegram Module configuration.");
            default:
                return '';
        }
    }
}
