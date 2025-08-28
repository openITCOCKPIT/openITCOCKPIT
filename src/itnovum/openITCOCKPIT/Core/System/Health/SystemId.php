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

namespace itnovum\openITCOCKPIT\Core\System\Health;


use itnovum\openITCOCKPIT\Core\UUID;

class SystemId {

    /**
     * @var string|null
     */
    private $systemId = null;

    /**
     * @var string
     */
    private $basePath = '/opt/openitc/etc';

    public function __construct() {
        if (IS_CONTAINER) {
            // This is by default a docker volume so the ID will be persistent
            $this->basePath = '/opt/openitc/var';
        }

        if (file_exists($this->basePath . DS . 'system-id')) {
            $this->systemId = trim(file_get_contents($this->basePath . DS . 'system-id'));
            return;
        } else {
            if (is_writable($this->basePath)) {
                $file = fopen($this->basePath . DS . 'system-id', 'w+');
                $this->systemId = UUID::v4();
                fwrite($file, $this->systemId);
                fclose($file);
                return;
            }
        }

        //Fallback Xenial
        if (file_exists('/etc/machine-id')) {
            $this->systemId = trim(file_get_contents('/etc/machine-id'));
            return;
        }

        //Fallback Trusty
        if (file_exists('/sys/class/dmi/id/product_uuid')) {
            $this->systemId = trim(file_get_contents('/sys/class/dmi/id/product_uuid'));
            return;
        }
    }

    public function getSystemId() {
        return $this->systemId;
    }
}
