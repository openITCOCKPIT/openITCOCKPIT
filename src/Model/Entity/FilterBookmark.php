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

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FilterBokkmark Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string $plugin
 * @property string $controller
 * @property string $action
 * @property string $name
 * @property string $filter
 * @property bool $favorite
 **/
class FilterBookmark extends Entity {
    protected array $_accessible = [
        'uuid'       => true,
        'plugin'     => true,
        'controller' => true,
        'action'     => true,
        'name'       => true,
        'user_id'    => true,
        'filter'     => true,
        'favorite'   => true,
    ];

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        $data = $this->extract($this->getVisible());
        $data['fav_group'] = __('Filters');
        if ($this->favorite) {
            $data['fav_group'] = __('Favorites');
        }
        return $data;
    }

}
