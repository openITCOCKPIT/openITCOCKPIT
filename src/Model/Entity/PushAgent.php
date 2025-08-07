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
 * PushAgent Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $agentconfig_id
 * @property string $password
 * @property string|null $hostname
 * @property string|null $ipaddress
 * @property string|null $remote_address
 * @property string|null $http_x_forwarded_for
 * @property string $checkresults
 * @property \Cake\I18n\DateTime $last_update
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Agentconfig $agentconfig
 */
class PushAgent extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'uuid'                 => true,
        'agentconfig_id'       => true,
        'password'             => true,
        'hostname'             => true,
        'ipaddress'            => true,
        'remote_address'       => true,
        'http_x_forwarded_for' => true,
        'checkresults'         => true,
        'last_update'          => true,
        'created'              => true,
        'modified'             => true,
        'agentconfig'          => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected array $_hidden = [
        'password',
    ];
}
