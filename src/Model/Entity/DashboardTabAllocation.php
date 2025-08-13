<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * DashboardTabAllocation Entity
 *
 * @property int $id
 * @property string $name
 * @property int $dashboard_tab_id
 * @property int $container_id
 * @property int $user_id
 * @property int $pinned
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\DashboardTab $dashboard_tab
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\User $author
 * @property \App\Model\Entity\User[] $users
 * @property \App\Model\Entity\Usergroup[] $usergroups
 */
class DashboardTabAllocation extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name'             => true,
        'dashboard_tab_id' => true,
        'container_id'     => true,
        'user_id'          => true,
        'pinned'           => true,
        'created'          => true,
        'modified'         => true,
        'dashboard_tab'    => true,
        'container'        => true,
        'author'           => true,
        'users'            => true,
        'usergroups'       => true,
    ];
}
