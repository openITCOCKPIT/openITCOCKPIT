<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace MapModule\Model\Entity;

use App\Model\Entity\Container;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use DistributeModule\Model\Entity\Satellite;

/**
 * Map Entity
 *
 * @property int $id
 * @property string $name
 * @property string $title
 * @property string|null $background
 * @property int $background_x
 * @property int $background_y
 * @property int $background_size_x
 * @property int $background_size_y
 * @property int $refresh_interval
 * @property string|null $json_data
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property Mapgadget[] $mapgadgets
 * @property Mapicon[] $mapicons
 * @property Mapitem[] $mapitems
 * @property Mapline[] $maplines
 * @property MapsToContainer[] $maps_to_containers
 * @property MapsToRotation[] $maps_to_rotations
 * @property Mapsummaryitem[] $mapsummaryitems
 * @property Maptext[] $maptexts
 * @property Satellite[] $satellites
 * @property Container[] $containers
 */
class Map extends Entity {
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
        'name'               => true,
        'title'              => true,
        'background'         => true,
        'background_x'       => true,
        'background_y'       => true,
        'background_size_x'  => true,
        'background_size_y'  => true,
        'refresh_interval'   => true,
        'json_data'          => true,
        'created'            => true,
        'modified'           => true,
        'mapgadgets'         => true,
        'mapicons'           => true,
        'mapitems'           => true,
        'maplines'           => true,
        'maps_to_containers' => true,
        'maps_to_rotations'  => true,
        'mapsummaryitems'    => true,
        'maptexts'           => true,
        'containers'         => true,
        'satellites'         => true
    ];

    /**
     * @return array
     */
    public function getContainerIds() {
        foreach ($this->containers as $container) {
            /** @var Container $container */
            $containerIds[] = $container->get('id');
        }

        return array_unique($containerIds);
    }
}
