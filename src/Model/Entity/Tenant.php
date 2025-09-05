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

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tenant Entity
 *
 * @property int $id
 * @property int $container_id
 * @property string|null $description
 * @property int $is_active
 * @property int $number_users
 * @property int $max_users
 * @property int $number_hosts
 * @property int $number_services
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $street
 * @property int|null $zipcode
 * @property string|null $city
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 */
class Tenant extends Entity {

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
        'container_id'    => true,
        'description'     => true,
        'is_active'       => true,
        'number_users'    => true,
        'max_users'       => true,
        'number_hosts'    => true,
        'number_services' => true,
        'firstname'       => true,
        'lastname'        => true,
        'street'          => true,
        'zipcode'         => true,
        'city'            => true,
        'created'         => true,
        'modified'        => true,
        'container'       => true
    ];
}
