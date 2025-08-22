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


class MenuHeadline {

    private $name;

    private $alias;

    private $order;

    private $items = [];

    /**
     * menuHeadline constructor.
     * @param string $name
     * @param int $order
     */
    public function __construct(string $name, string $alias = '', int $order = 0) {
        $this->name = $name;
        $this->alias = $alias;
        $this->order = $order;
    }

    /**
     * @param MenuLink $MenuLink
     * @return $this
     */
    public function addLink(MenuLink $MenuLink) {
        $items = $this->items;
        $items[] = $MenuLink;

        $this->items = [];

        $indexesToOrder = [];
        foreach ($items as $index => $item) {
            /** @var MenuCategory|MenuLink */
            $indexesToOrder[$index] = $item->getOrder();
        }

        asort($indexesToOrder);

        foreach ($indexesToOrder as $index => $orderNumber) {
            $this->items[] = $items[$index];
        }

        return $this;
    }

    /**
     * @param MenuCategory $MenuCategory
     * @return $this
     */
    public function addCategory(MenuCategory $MenuCategory) {
        $items = $this->items;
        $items[] = $MenuCategory;

        $this->items = [];

        //Merge categories with the same name together
        $names = [];
        $categoriesToRemove = [];
        foreach ($items as $index => $item) {
            if ($item instanceof MenuCategory) {
                /** @var MenuCategory $item */
                if (isset($names[$item->getName()])) {
                    // Merge with existing category
                    /** @var MenuCategory $targetCategory */
                    $targetCategory = $names[$item->getName()];
                    $categoriesToRemove = [$index];
                    foreach ($item->getLinks() as $MenuLink) {
                        $targetCategory->addLink($MenuLink);
                    }
                } else {
                    //Save name
                    $names[$item->getName()] = $item;
                }

            }


        }

        //Drop merged categories to remove duplicates
        foreach ($categoriesToRemove as $index) {
            unset($items[$index]);
        }

        $indexesToOrder = [];
        foreach ($items as $index => $item) {
            /** @var MenuCategory|MenuLink */
            $indexesToOrder[$index] = $item->getOrder();
        }

        asort($indexesToOrder);

        foreach ($indexesToOrder as $index => $orderNumber) {
            $this->items[] = $items[$index];
        }

        return $this;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function removeMenuLinkByIndex(int $index): bool {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            return true;
        }

        return false;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function removeMenuCategoryByIndex(int $index): bool {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
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
     * @return string
     */
    public function getAlias(): string {
        return $this->alias;
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
    public function getItems(): array {
        return $this->items;
    }

    /**
     * @return bool
     */
    public function hasItems(): bool {
        return !empty($this->items);
    }

    public function toArray(): array {
        $asArray = [
            'name'  => $this->name,
            'alias' => $this->alias,
            'order' => $this->order,
            'items' => []
        ];

        foreach ($this->items as $item) {
            $asArray['items'][] = $item->toArray();
        }

        return $asArray;
    }

}
