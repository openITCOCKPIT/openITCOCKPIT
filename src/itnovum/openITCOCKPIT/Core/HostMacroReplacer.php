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


class HostMacroReplacer {

    /**
     * @var array
     */
    private $host;

    /**
     * @var array
     */
    private $hoststatus;

    /**
     * @var array
     */
    private $mapping = [
        'basic'  => [
            '$HOSTID$'          => 'id', //Not a real macro
            '$HOSTNAME$'        => 'uuid',
            '$HOSTDISPLAYNAME$' => 'name',
            '$HOSTADDRESS$'     => 'address',
        ],
        'status' => [
            '$HOSTSTATEID$'     => 'current_state',
            '$LASTHOSTSTATEID$' => 'last_hard_state',
            '$HOSTOUTPUT$'      => 'output',
        ],
    ];

    /**
     * HostMacroReplacer constructor.
     *
     * @param array $host result of CakePHPs find()
     * @param array $hoststatus result of CakePHPs find()
     */

    public function __construct($host, $hoststatus = []) {
        if (isset($host['id']) && isset($host['uuid'])) {
            //Cake4 result...
            $host = [
                'Host' => $host
            ];
        }
        $this->host = $host;
        $this->hoststatus = $hoststatus;
    }

    /**
     * Replace the folowing Macros:
     * - $HOSTID$ => Host.id
     * - $HOSTNAME$ => Host.uuid
     * - $HOSTDISPLAYNAME$ => Host.name
     * - $HOSTADDRESS$ => Host.address
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
     * - $HOSTSTATEID$ => Hoststatus.current_state,
     * - $LASTHOSTSTATEID$ => Hoststatus.last_hard_state
     * - $HOSTOUTPUT$ => Hoststatus.output
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
            if (isset($this->host['Host'][$databaseField])) {
                $mapping['replace'][] = $this->host['Host'][$databaseField];
                $findReplacement = true;
            }

            //Check if this is a status field
            if (isset($this->hoststatus['Hoststatus'][$databaseField])) {
                $mapping['replace'][] = $this->hoststatus['Hoststatus'][$databaseField];
                $findReplacement = true;
            }

            if ($findReplacement === false) {
                //Field not set in given __construct data
                $mapping['replace'][] = $macroName;
            }
        }

        return $mapping;
    }
}
