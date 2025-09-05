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
 * Automap Entity
 *
 * @property int $id
 * @property string $name
 * @property int $container_id
 * @property string|null $description
 * @property string|null $host_regex
 * @property string|null $service_regex
 * @property bool $show_ok
 * @property bool $show_warning
 * @property bool $show_critical
 * @property bool $show_unknown
 * @property bool $show_acknowledged
 * @property bool $show_downtime
 * @property bool $show_label
 * @property bool $group_by_host
 * @property string|null $font_size
 * @property bool $recursive
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 */
class Instantreport extends Entity {
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
        'name'           => true,
        'container_id'   => true,
        'evaluation'     => true,
        'type'           => true,
        'timeperiod_id'  => true,
        'reflection'     => true,
        'downtimes'      => true,
        'summary'        => true,
        'send_email'     => true,
        'send_interval'  => true,
        'last_send_date' => true,
        'hostgroups'     => true,
        'hosts'          => true,
        'servicegroups'  => true,
        'services'       => true,
        'users'          => true,
        'created'        => true,
        'modified'       => true
    ];

    /**
     * @return bool
     */
    public function hasToBeSend() {
        $now = time();
        $hasToBeSend = false;
        $lastSendDate = $this->get('last_send_date');
        $sendInterval = $this->get('send_interval');
        if ($lastSendDate == '0000-00-00 00:00:00') {
            return true;
        }
        $lastSendTimestamp = 0;
        if ($lastSendDate) {
            $lastSendTimestamp = strtotime($lastSendDate);
        }
        switch ($sendInterval) {
            case 1: //daily
                if (intval(date('Ymd', $now)) > intval(date('Ymd', $lastSendTimestamp))) {
                    $hasToBeSend = true;
                }
                break;
            case 2: //weekly
                if (intval(date('oW', $now)) > intval(date('oW', $lastSendTimestamp))) {
                    $hasToBeSend = true;
                }
                break;
            case 3: //monthly
                if (intval(date('Ym', $now)) > intval(date('Ym', $lastSendTimestamp))) {
                    $hasToBeSend = true;
                }
                break;
            case 4: //yearly
                if (intval(date('Y', $now)) > intval(date('Y', $lastSendTimestamp))) {
                    $hasToBeSend = true;
                }
                break;
        }

        return $hasToBeSend;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function reportStartTime() {
        $sendInterval = $this->get('send_interval');
        $now = $this->reportEndTime();
        $dateNow = new \DateTime(date('d.m.Y H:i:s', $now));
        switch ($sendInterval) {
            case 2: //weekly
                $dateNow->modify('last Monday');
                break;
            case 3: //monthly
                $dateNow->modify('first day of this month');
                break;
            case 4: //yearly
                $dateNow->modify('first day of this year');
                break;
        }
        $dateNow->setTime(0, 0, 0);
        return $dateNow->getTimestamp();
    }

    /**
     * @param $sendInterval
     * @return int
     * @throws \Exception
     */
    public function reportEndTime() {
        $dateNow = new \DateTime(date('d.m.o H:i', time()));
        $sendInterval = $this->get('send_interval');
        switch ($sendInterval) {
            case 1: //daily
                $dateNow->modify('yesterday');
                break;
            case 2: //weekly
                $dateNow->modify('last Sunday');
                break;
            case 3: //monthly
                $dateNow->modify('last day of last month');
                break;
            case 4: //yearly
                $dateNow->modify('31 December last year');
                break;
        }

        $dateNow->setTime(23, 59, 59);
        return $dateNow->getTimestamp();
    }
}
