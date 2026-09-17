<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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
    private $oldAptRepository = 'packages.openitcockpit.io';

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
     * @var string
     */
    private $deb822Sources = '/etc/apt/sources.list.d/openitcockpit.sources';

    /**
     * Checks if the system is already using the new deb822 sources list format.
     * https://manpages.debian.org/trixie/dpkg-dev/deb822.5.en.html
     * @return bool
     */
    public function isDeb822Present(): bool {
        if (!file_exists($this->deb822Sources)) {
            return false;
        }

        return true;
    }

    public function isOldSourceListPresent(): bool {
        if (!file_exists($this->sourcesList)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true on success, throws an exception if the file is not readable.
     * @return bool
     * @throws \Exception
     */
    public function isReadable(): bool {
        $isDeb882 = $this->isDeb822Present();
        $isDeb882Readable = false;
        if ($isDeb882) {
            $isDeb882Readable = is_readable($this->deb822Sources);
        }

        $isOldSourceList = $this->isOldSourceListPresent();
        $isOldSourceListReadable = false;
        if ($isOldSourceList) {
            $isOldSourceListReadable = is_readable($this->sourcesList);
        }

        if (!$isDeb882Readable && !$isOldSourceListReadable) {
            throw new \Exception(sprintf('File %s not readable', $this->deb822Sources));
        }
        return true;
    }

    /**
     * Checks if at least one of the sources.list files exists. Throws an exception if none of them exist.
     * @return bool
     * @throws \Exception
     */
    public function exists(): bool {
        $isDeb882 = $this->isDeb822Present();
        $isOldSourceList = $this->isOldSourceListPresent();


        if (!$isDeb882 && !$isOldSourceList) {
            throw new \Exception(sprintf('File %s not found', $this->deb822Sources));
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

        $isDeb882 = $this->isDeb822Present();
        if ($isDeb882) {
            // Probably overkill as nobody should use the old repository in the new deb822 format
            foreach (file($this->deb822Sources) as $line) {
                if (strstr(trim($line), $this->oldAptRepository)) {
                    return true;
                }
            }
        }

        $isOldSourceList = $this->isOldSourceListPresent();
        if ($isOldSourceList) {
            foreach (file($this->sourcesList) as $line) {
                if (strstr(trim($line), $this->oldAptRepository)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDeb822Source(): string {
        return $this->deb822Sources;
    }

    /**
     * @return string
     */
    public function getSourcesList(): string {
        return $this->sourcesList;
    }

    /**
     * @return string
     */
    public function getRepoKey(): string {
        return $this->newAptKey;
    }
}
