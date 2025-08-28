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


class ServiceMacroReplacer {

    /**
     * @var array
     */
    private $service;

    /**
     * @var array
     */
    private $servicestatus;

    /**
     * @var array
     */
    private $mapping = [
        'basic'  => [
            '$SERVICEID$'          => 'id', //Not a real macro
            '$SERVICEDESC$'        => 'uuid',
            '$SERVICEDISPLAYNAME$' => 'name',
        ],
        'status' => [
            '$SERVICESTATEID$'     => 'current_state',
            '$LASTSERVICESTATEID$' => 'last_hard_state',
            '$SERVICEOUTPUT$'      => 'output',
        ],
    ];

    /**
     * HostMacroReplacer constructor.
     *
     * @param array $service result of CakePHPs find()
     * @param array $servicestatus result of CakePHPs find()
     */

    public function __construct($service, $servicestatus = []) {
        if (isset($service['id']) && isset($service['uuid'])) {
            //Cake4 result...
            $service = [
                'Service' => $service
            ];

            if(isset($service['Service']['servicetemplate']['name'])){
                if($service['Service']['name'] === null || $service['Service']['name'] === ''){
                    $service['Service']['name'] = $service['Service']['servicetemplate']['name'];
                }
            }
        }

        $this->service = $service;
        $this->servicestatus = $servicestatus;
    }

    /**
     * Replace the folowing Macros:
     * - $SERVICEID$ => Service.id //Not a real macro
     * - $SERVICEDESC$ => Service.uuid
     * - $SERVICEDISPLAYNAME$ => Service.name
     *
     * @param string $msg
     */
    public function replaceBasicMacros($msg) {
        if(is_null($msg)){
            return $msg;
        }

        $mapping = $this->buildMapping('basic');

        return str_replace($mapping['search'], $mapping['replace'], $msg);
    }

    /**
     * Replace the folowing Macros:
     * - $SERVICESTATEID$ => Servicestatus.current_state,
     * - $LASTSERVICESTATEID$ => Servicestatus.last_hard_state
     * - $SERVICEOUTPUT$ => Servicestatus.output
     *
     * @param string $msg
     */
    public function replaceStatusMacros($msg) {
        if(is_null($msg)){
            return $msg;
        }

        $mapping = $this->buildMapping('status');

        return str_replace($mapping['search'], $mapping['replace'], $msg);
    }

    /**
     * Try to replace all known macros
     *
     * @param $msg
     *
     * @return string mixed
     */
    public function replaceAllMacros($msg) {
        $msg = $this->replaceBasicMacros($msg);
        $msg = $this->replaceStatusMacros($msg);

        return $msg;
    }

    /**
     * @param string $type a key of $this->mapping
     */
    private function buildMapping($type) {
        $recordsToMap = $this->mapping[$type];
        $mapping = [
            'search'  => [],
            'replace' => [],
        ];
        foreach ($recordsToMap as $macroName => $databaseField) {
            $mapping['search'][] = $macroName;
            $findReplacement = false;

            if ($macroName === '$SERVICEDISPLAYNAME$') {
                $servicename = null;
                if (isset($this->service['Service']['name'])) {
                    $servicename = $this->service['Service']['name'];
                }
                if ($servicename === '' || $servicename === null) {
                    if (isset($this->service['Servicetemplate']['name'])) {
                        $servicename = $this->service['Servicetemplate']['name'];
                    }
                }
                if ($servicename === null) {
                    $servicename = '$SERVICEDISPLAYNAME$';
                }
                $mapping['replace'][] = $servicename;
            } else {
                if (isset($this->service['Service'][$databaseField])) {
                    $mapping['replace'][] = $this->service['Service'][$databaseField];
                    $findReplacement = true;
                }

                //Check if this is a status field
                if (isset($this->servicestatus['Servicestatus'][$databaseField])) {
                    $mapping['replace'][] = $this->servicestatus['Servicestatus'][$databaseField];
                    $findReplacement = true;
                }

                if ($findReplacement === false) {
                    //Field not set in given __construct data
                    $mapping['replace'][] = $macroName;
                }
            }
        }

        return $mapping;
    }
}
