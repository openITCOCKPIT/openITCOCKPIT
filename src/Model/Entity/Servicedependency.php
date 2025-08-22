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
 * Timeperiod Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $container_id
 * @property int $timeperiod_id
 * @property int $inherits_parent
 * @property int $execution_fail_on_ok
 * @property int $execution_fail_on_warning
 * @property int $execution_fail_on_critical
 * @property int $execution_fail_on_unknown
 * @property int $execution_fail_on_pending
 * @property int $execution_none
 * @property int $notification_fail_on_ok
 * @property int $notification_fail_on_warning
 * @property int $notification_fail_on_critical
 * @property int $notification_fail_on_unknown
 * @property int $notification_fail_on_pending
 * @property int $notification_none
 * @property Service $services
 * @property Timeperiod $timeperiods
 * @property Servicegroup $servicegroups
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\TimeperiodTimerange[] $timeperiod_timeranges
 */
class Servicedependency extends Entity {

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
        'uuid'                          => true,
        'container_id'                  => true,
        'timeperiod_id'                 => true,
        'inherits_parent'               => true,
        'execution_fail_on_ok'          => true,
        'execution_fail_on_warning'     => true,
        'execution_fail_on_critical'    => true,
        'execution_fail_on_unknown'     => true,
        'execution_fail_on_pending'     => true,
        'execution_none'                => true,
        'notification_fail_on_ok'       => true,
        'notification_fail_on_warning'  => true,
        'notification_fail_on_critical' => true,
        'notification_fail_on_unknown'  => true,
        'notification_fail_on_pending'  => true,
        'notification_none'             => true,
        'services'                      => true,
        'servicegroups'                 => true,
        'timeperiods'                   => true,
        'created'                       => true,
        'modified'                      => true
    ];

    /**
     * @return string
     */
    public function getExecutionFailureCriteriaForCfg() {
        $cfgValues = [];
        $fields = [
            'execution_fail_on_ok'       => 'o',
            'execution_fail_on_warning'  => 'w',
            'execution_fail_on_critical' => 'c',
            'execution_fail_on_unknown'  => 'u',
            'execution_fail_on_pending'  => 'p',
            'execution_none'             => 'n'
        ];
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        return implode(',', $cfgValues);
    }

    /**
     * @return string
     */
    public function getNotificationFailureCriteriaForCfg() {
        $cfgValues = [];
        $fields = [
            'notification_fail_on_ok'       => 'o',
            'notification_fail_on_warning'  => 'w',
            'notification_fail_on_critical' => 'c',
            'notification_fail_on_unknown'  => 'u',
            'notification_fail_on_pending'  => 'p',
            'notification_none'             => 'n'
        ];

        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        return implode(',', $cfgValues);
    }
}
