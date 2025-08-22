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

namespace itnovum\openITCOCKPIT\Core\Timeline;


class Groups {

    private $groups = [];

    public function __construct() {

        //group->id gets rendered as order to the frontend
        $this->groups = [
            'acknowledgements'    => [
                'id'   => 2,
                'icon' => ' <i class=\'fas fa-commenting\'></i>',
                'text' => __('Acknowledgements')
            ],
            'downtimes'           => [
                'id'   => 3,
                'icon' => '<i class=\'fas fa-power-off\'></i>',
                'text' => __('Downtimes')
            ],
            'notifications'       => [
                'id'   => 1,
                'icon' => '<i class=\'fas fa-envelope\'></i>',
                'text' => __('Notifications')
            ],
            'servicestatehistory' => [
                'id'   => 4,
                'icon' => '<i class=\'fas fa-cog\'></i>',
                'text' => __('State history service')
            ],
            'hoststatehistory'    => [
                'id'   => 5,
                'icon' => '<i class=\'fas fa-desktop\'></i>',
                'text' => __('State history host')
            ]
        ];
    }

    public function getStatehistoryHostId() {
        return $this->groups['hoststatehistory']['id'];
    }

    public function getStatehistoryServiceId() {
        return $this->groups['servicestatehistory']['id'];
    }

    public function getAcknowledgementsId() {
        return $this->groups['acknowledgements']['id'];
    }

    public function getDowntimesId() {
        return $this->groups['downtimes']['id'];
    }

    public function getNotificationsId() {
        return $this->groups['notifications']['id'];
    }

    /**
     * @param bool $isHost
     * @return array
     */
    public function serialize($isHost = false) {
        $groups = [];
        foreach ($this->groups as $key => $group) {
            if ($isHost === true && $key === 'servicestatehistory') {
                continue;
            }

            $groups[] = [
                'id'      => $group['id'],
                'content' => sprintf('%s %s', $group['icon'], $group['text'])
            ];
        }
        return $groups;
    }
}
