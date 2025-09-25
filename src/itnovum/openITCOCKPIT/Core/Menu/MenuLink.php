<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

declare(strict_types=1);

namespace itnovum\openITCOCKPIT\Core\Menu;


class MenuLink {

    private $name;

    private $state;

    private $controller;

    private $action;

    private $plugin;

    private $tags = [];

    private $order;

    private $icon;

    private $isAngular;

    private $angularUrl;

    public function __construct(string $name, string $state, string $controller, string $action, string $plugin = '', array $icon = [], array $tags = [], int $order = 0, bool $isAngular = false, string $angularUrl = '') {
        $this->name = $name;
        $this->state = $state;
        $this->controller = $controller;
        $this->action = $action;
        $this->plugin = $plugin;
        $this->icon = $icon;
        foreach ($tags as $tag) {
            $this->tags[] = strtolower($tag);
        }
        $this->order = $order;
        $this->isAngular = $isAngular;
        $this->angularUrl = $angularUrl;
    }

    /**
     * @return int
     */
    public function getOrder(): int {
        return $this->order;
    }

    /**
     * @return bool
     */
    public function isModule() {
        return $this->plugin !== '';
    }

    /**
     * @return string
     */
    public function getLowerController(): string {
        return strtolower($this->controller);
    }

    /**
     * @return string
     */
    public function getLowerAction(): string {
        return strtolower($this->action);
    }

    /**
     * @return string
     */
    public function getLowerPlugin(): string {
        return strtolower($this->plugin);
    }

    public function isAngular(): bool {
        return $this->isAngular;
    }

    public function getAngularUrl(): string {
        return $this->angularUrl;
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return [
            'name'       => $this->name,
            'state'      => $this->state,
            'controller' => $this->controller,
            'action'     => $this->action,
            'plugin'     => $this->plugin,
            'tags'       => $this->tags,
            'order'      => $this->order,
            'icon'       => $this->icon,
            'isAngular'  => $this->isAngular,
            'angularUrl' => $this->angularUrl
        ];
    }

}
