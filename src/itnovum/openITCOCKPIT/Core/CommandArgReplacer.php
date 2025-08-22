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


class CommandArgReplacer {

    /**
     * @var array
     */
    private $commandargumentvalues;

    /**
     * CommandArgReplacer constructor.
     * @param array $commandargumentvalues
     */
    public function __construct($commandargumentvalues) {
        $this->commandargumentvalues = $commandargumentvalues;
    }

    /**
     * Replace the folowing Macros:
     * - $ARG1$
     * - $ARG2$
     * - $ARGn$
     *
     * @param string $msg
     * @return string
     */
    public function replace($cmdStr) {
        if (is_null($cmdStr)) {
            return $cmdStr;
        }

        $mapping = $this->buildMapping('basic');

        return str_replace($mapping['search'], $mapping['replace'], $cmdStr);
    }

    /**
     * @return array
     */
    private function buildMapping() {
        $mapping = [
            'search'  => [],
            'replace' => [],
        ];

        foreach ($this->commandargumentvalues as $commandargumentvalue) {
            $argn = $commandargumentvalue['commandargument']['name'];
            $value = $commandargumentvalue['value'];

            $mapping['search'][] = $argn;
            $mapping['replace'][] = $value;
        }
        return $mapping;
    }
}
