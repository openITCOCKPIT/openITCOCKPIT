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

namespace itnovum\openITCOCKPIT\Resolver;

use App\Model\Table\HostsTable;
use App\Model\Table\ServicesTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\UUID;

/**
 * Class NameToUuidResolver
 * This class can resolve a name like "localhost" or "PING" into the corresponding UUID.
 */
class NameToUuidResolver {

    /**
     * @var HostsTable
     */
    private $HostsTable;

    /**
     * @var ServicesTable
     */
    private $ServicesTable;

    public function __construct(HostsTable $HostsTable, ServicesTable $ServicesTable) {
        $this->HostsTable = $HostsTable;
        $this->ServicesTable = $ServicesTable;
    }

    /**
     * @return self
     */
    public static function createResolver(): self {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ServicesTable $ServicesTable */
        $ServicesTable = TableRegistry::getTableLocator()->get('Services');

        return new self($HostsTable, $ServicesTable);
    }

    /**
     * If a UUID gets passed, the method will return the UUID.
     * If a name like "default host" gets passed, the method will return the UUID of the first host with the name "default host".
     * If the name does not exist, the method will return false.
     *
     * @param string $hostNameOrUuid
     * @return false|string
     */
    public function resolveHostname(string $hostNameOrUuid) {
        if (UUID::is_valid($hostNameOrUuid)) {
            // UUID is already given - nothing to do
            return $hostNameOrUuid;
        }

        $query = $this->HostsTable->find()
            ->select(['uuid'])
            ->where(['name' => $hostNameOrUuid])
            ->limit(1);

        $result = $query->first();

        if (empty($result)) {
            // Did not find a host with the given name
            return false;
        }

        return $result->uuid;
    }

    /**
     * This method will resolve a service name like "PING" into the corresponding UUID.
     * If a UUID gets passed, the method will return the UUID.
     *
     * @param string $hostUuid
     * @param string $serviceNameOrUuid
     * @return false|string
     */
    public function resolveServicename(string $hostUuid, string $serviceNameOrUuid) {
        if (UUID::is_valid($serviceNameOrUuid)) {
            // UUID is already given - nothing to do
            return $serviceNameOrUuid;
        }

        $query = $this->ServicesTable->find();
        $query
            ->select([
                'Services.uuid',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
            ])
            ->contain([
                'Hosts',
                'Servicetemplates'
            ])
            ->where([
                'Hosts.uuid' => $hostUuid
            ])
            ->having([
                'servicename' => $serviceNameOrUuid
            ])
            ->limit(1);

        $result = $query->first();

        if (empty($result)) {
            // Did not find a host with the given name
            return false;
        }

        return $result->uuid;
    }


    /**
     * This function can resolve a given host and service name into the corresponding UUIDs.
     * It is possible to mix UUIDs and names. The result of the function is an array with the UUIDs of the host and service.
     *
     * This function can resolve the host uuid and service uuid in a single query and should be used instead of calling resolveHostname and resolveServicename separately.
     *
     * @param string $hostNameOrUuid
     * @param string $serviceNameOrUuid
     * @return array|false[]|string[]
     */
    public function resolveHostAndServicename(string $hostNameOrUuid, string $serviceNameOrUuid): array {
        $hostUuid = false;
        $serviceUuid = false;

        if (UUID::is_valid($hostNameOrUuid)) {
            $hostUuid = $hostNameOrUuid;
        }

        if (UUID::is_valid($serviceNameOrUuid)) {
            $serviceUuid = $serviceNameOrUuid;
        }

        if ($hostUuid && $serviceUuid) {
            // Host and services got passed as UUIDs - nothing to do
            return [
                'hostUuid'    => $hostUuid,
                'serviceUuid' => $serviceUuid
            ];
        }

        if ($hostUuid === false && $serviceUuid !== false) {
            // Only the service got passed as UUID - try to resolve the host
            return [
                'hostUuid'    => $this->resolveHostname($hostNameOrUuid),
                'serviceUuid' => $serviceUuid
            ];
        }

        if ($hostUuid !== false && $serviceUuid === false) {
            // Host get passed as UUID - service as name
            // Only the service got passed as UUID - try to resolve the host
            return [
                'hostUuid'    => $hostUuid,
                'serviceUuid' => $this->resolveServicename($hostUuid, $serviceNameOrUuid)
            ];

        }

        // Host and service got passed by name - resolve both
        $query = $this->ServicesTable->find();
        $query
            ->select([
                'Hosts.uuid',
                'Services.uuid',
                'servicename' => $query->newExpr('IF((Services.name IS NULL OR Services.name=""), Servicetemplates.name, Services.name)'),
            ])
            ->contain([
                'Hosts',
                'Servicetemplates'
            ])
            ->where([
                'Hosts.name' => $hostNameOrUuid
            ])
            ->having([
                'servicename' => $serviceNameOrUuid
            ])
            ->limit(1);

        $result = $query->first();
        if (empty($result)) {
            // Did not find a host and service with the given name
            return [
                'hostUuid'    => false,
                'serviceUuid' => false,
            ];
        }

        return [
            'hostUuid'    => $result->host->uuid,
            'serviceUuid' => $result->uuid
        ];
    }

}
