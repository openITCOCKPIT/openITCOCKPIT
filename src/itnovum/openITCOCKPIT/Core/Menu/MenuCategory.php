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


class MenuCategory {

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var int
     */
    private $order;

    /**
     * @var array
     */
    private $icon;

    /**
     * @var array
     */
    private $links = [];

    /**
     * MenuCategory constructor.
     * @param string $name
     * @param int $order
     * @param array $icon
     * @return $this
     */
    public function __construct(string $name, string $alias = '', int $order = 0, array $icon = []) {
        $this->name = $name;
        $this->alias = $alias;
        $this->order = $order;
        $this->icon = $icon;
        return $this;
    }

    /**
     * @param MenuLink $MenuLink
     * @return $this
     */
    public function addLink(MenuLink $MenuLink) {
        $links = $this->links;
        $links[] = $MenuLink;

        $this->links = [];

        $indexesToOrder = [];
        foreach ($links as $index => $link) {
            /** @var MenuLink $link */
            $indexesToOrder[$index] = $link->getOrder();
        }

        asort($indexesToOrder);

        foreach ($indexesToOrder as $index => $orderNumber) {
            $this->links[] = $links[$index];
        }

        return $this;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function removeMenuLinkByIndex(int $index): bool {
        if (isset($this->links[$index])) {
            unset($this->links[$index]);
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getOrder(): int {
        return $this->order;
    }

    /**
     * @return array
     */
    public function getIcon(): array {
        return $this->icon;
    }

    /**
     * @return array
     */
    public function getLinks(): array {
        return $this->links;
    }

    /**
     * @return bool
     */
    public function hasLinks(): bool {
        return !empty($this->links);
    }

    /**
     * @return array
     */
    public function toArray(): array {
        $asArray = [
            'name'  => $this->name,
            'alias' => $this->alias,
            'order' => $this->order,
            'icon'  => $this->icon,
            'items' => []
        ];

        foreach ($this->getLinks() as $link) {
            $asArray['items'][] = $link->toArray();
        }

        return $asArray;
    }

}
