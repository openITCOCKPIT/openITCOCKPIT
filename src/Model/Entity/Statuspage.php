<?php
// Copyright (C) <2015>  <it-novum GmbH>
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

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * Statuspage Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $public_title
 * @property bool $public
 * @property bool $show_downtimes
 * @property bool $show_downtime_comments
 * @property bool $show_acknowledgements
 * @property bool $show_acknowledgement_comments
 * @property FrozenTime $created
 * @property FrozenTime $modified
 *
 * @property \App\Model\Entity\StatuspagesToContainer[] $statuspages_to_containers
 * @property \App\Model\Entity\StatuspagesToHostgroup[] $statuspages_to_hostgroups
 * @property \App\Model\Entity\StatuspagesToHost[] $statuspages_to_hosts
 * @property \App\Model\Entity\StatuspagesToServicegroup[] $statuspages_to_servicegroups
 * @property \App\Model\Entity\StatuspagesToService[] $statuspages_to_services
 */
class Statuspage extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'container_id'                  => true,
        'name'                          => true,
        'description'                   => true,
        'public_title'                  => true,
        'public'                        => true,
        'show_downtimes'                => true,
        'show_downtime_comments'        => true,
        'show_acknowledgements'         => true,
        'show_acknowledgement_comments' => true,
        'hosts'                         => true,
        'services'                      => true,
        'hostgroups'                    => true,
        'servicegroups'                 => true,
        'created'                       => true,
        'modified'                      => true,
    ];
}
