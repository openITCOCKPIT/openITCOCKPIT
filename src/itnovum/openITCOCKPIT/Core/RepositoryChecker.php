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


class RepositoryChecker {

    /**
     * @var string
     */
    private $oldAptRepository = 'apt.open-itcockpit.com';

    /**
     * @var string
     */
    private $newAptRepository = 'packages5.openitcockpit.io';

    /**
     * @var string
     */
    private $newAptKey = '1148DA8E';

    /**
     * @var string
     */
    private $sourcesList = '/etc/apt/sources.list.d/openitcockpit.list';

    /**
     * @return bool
     * @throws \Exception
     */
    public function isReadable() {
        if (!is_readable($this->sourcesList)) {
            throw new \Exception(sprintf('File %s not readable', $this->sourcesList));
        }
        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function exists() {
        if (!file_exists($this->sourcesList)) {
            throw new \Exception(sprintf('File %s not found', $this->sourcesList));
        }
        return true;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isOldRepositoryInUse() {
        try {
            $this->exists();
        } catch (\Exception $e) {
            throw new \Exception('Could not detect repository state.');
        }

        try {
            $this->isReadable();
        } catch (\Exception $e) {
            throw new \Exception('Could not detect repository state.');
        }

        foreach (file($this->sourcesList) as $line) {
            if (strstr(trim($line), $this->oldAptRepository)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getSourcesList() {
        return $this->sourcesList;
    }

    /**
     * @return string
     */
    public function getRepoKey() {
        return $this->newAptKey;
    }
}
