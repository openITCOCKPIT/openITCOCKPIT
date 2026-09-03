<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

namespace TelegramModule\Lib;


use itnovum\openITCOCKPIT\Core\Menu\MenuCategory;
use itnovum\openITCOCKPIT\Core\Menu\MenuHeadline;
use itnovum\openITCOCKPIT\Core\Menu\MenuInterface;
use itnovum\openITCOCKPIT\Core\Menu\MenuLink;

class Menu implements MenuInterface {
    /**
     * @return array
     */
    public function getHeadlines(): array {
        $menuHeadline = new MenuHeadline(\itnovum\openITCOCKPIT\Core\Menu\Menu::MENU_CONFIGURATION);
        $menuHeadline
            ->addCategory(
                (new MenuCategory(
                    'api_settings',
                    __('APIs')
                ))
                    ->addLink(
                        match (OPENITCOCKPIT_VERSION[0]) {
                            '4' => $this->v4MenuLink(),
                            default => $this->v5MenuLink()
                        }
                    )
            );

        return [$menuHeadline];
    }

    protected function v4MenuLink(): MenuLink {
        return new MenuLink(
            __('Telegram'),
            'TelegramSettingsIndex',
            'TelegramSettings',
            'index',
            'TelegramModule',
            'fab fa-telegram',
            [],
            1
        );
    }

    protected function v5MenuLink(): MenuLink {
        return new MenuLink(
            __('Telegram'),
            'TelegramSettingsIndex',
            'TelegramSettings',
            'index',
            'TelegramModule',
            ['fab', 'telegram'],
            [],
            1,
            true,
            '/telegram_module/TelegramSettings/index'
        );
    }
}
