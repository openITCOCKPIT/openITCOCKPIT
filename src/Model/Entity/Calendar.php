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

use App\Model\Table\ContainersTable;
use App\Model\Table\TimeperiodsTable;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Calendar Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property int $container_id
 * @property DateTime $created
 * @property DateTime $modified
 *
 * @property CalendarHoliday[] $calendar_holidays
 */
class Calendar extends Entity {
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
        'uuid'              => true,
        'name'              => true,
        'description'       => true,
        'container_id'      => true,
        'created'           => true,
        'modified'          => true,
        'calendar_holidays' => true
    ];

    /**
     * I will check where this calendar is in use while the containerId is fixed within a connection.
     * If you want to change the containerId, I may return a connection that will get broken as soon as the container is changed.
     * @return array[]
     */
    public function canMoveToContainer(int $newContainerId): bool {
        if (empty($this->getTimePeriods())) {
            return true;
        }

        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');
        return $ContainersTable->isNewContainerInPathOfOldContainer($newContainerId, $this->container_id);
    }


    /**
     * I will solely return the array of TimePeriods this Calendar is used with.
     * @return array
     */
    private function getTimePeriods(): array {
        /** @var TimeperiodsTable $TimePeriodsTable */
        $TimePeriodsTable = TableRegistry::getTableLocator()->get('Timeperiods');

        return $TimePeriodsTable->find()->where(['calendar_id' => $this->id])->toArray();
    }
}
