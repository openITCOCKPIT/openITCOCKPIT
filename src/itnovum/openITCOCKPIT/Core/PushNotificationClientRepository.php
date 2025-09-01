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

namespace itnovum\openITCOCKPIT\Core;


class PushNotificationClientRepository {

    /**
     * @var array
     */
    private $clients = [];

    /**
     * @param int $userId
     * @param string $browserUuid
     * @param string $uuid
     */
    public function addClient($userId, $browserUuid, $uuid) {
        $userId = (int)$userId;
        $this->clients[$userId][] = [
            'userId'      => $userId,
            'browserUuid' => $browserUuid,
            'uuid'        => $uuid
        ];
    }

    /**
     * @param string $uuid
     */
    public function removeClientByUuid($uuid) {
        $tmpClients = [];
        foreach ($this->clients as $userId => $clients) {
            foreach ($clients as $client) {
                if ($client['uuid'] !== $uuid) {
                    $tmpClients[$userId][] = $client;
                }
            }
        }

        $this->clients = $tmpClients;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public function hasUserIdConnectedClients($userId) {
        $userId = (int)$userId;
        return !empty($this->clients[$userId]);
    }

    /**
     * Filter multiple tabs in same browser window
     * @param int $userId
     * @return array
     */
    public function getUniqueClientsForNotificationByUserId($userId) {
        $userId = (int)$userId;
        if ($this->hasUserIdConnectedClients($userId) === false) {
            return [];
        }

        $existingBrowserUuids = [];
        $clientsForNotification = [];
        foreach ($this->clients[$userId] as $client) {
            if (!in_array($client['browserUuid'], $existingBrowserUuids, true)) {
                $existingBrowserUuids[] = $client['browserUuid'];
                $clientsForNotification[] = $client;
            }

        }
        return $clientsForNotification;
    }

}
