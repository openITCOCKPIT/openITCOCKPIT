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

declare(strict_types=1);

namespace itnovum\openITCOCKPIT\Vuln\Vuls;

use Cake\I18n\DateTime;

/**
 * The Vuls database that is currently used: an identifier and its date.
 *
 * The identifier is built from size and modification time of vuls.db. Downloading a new database writes a new
 * file, so the identifier changes and all package states get checked again with the next run.
 * The digest of the database image that Vuls itself uses is only part of the output of "vuls report", which
 * would need a container of its own.
 */
class VulsFeed {

    /**
     * Directory that contains vuls.db. Hardcoded for now, like the address in VulsClient.
     * The Vuls container of the prototype uses this directory.
     */
    private string $databaseDirectory = '/root/CVE/vuls/state';

    /**
     * @return string e.g. /root/CVE/vuls/state/vuls.db
     */
    public function getDatabaseFile(): string {
        return $this->databaseDirectory . '/vuls.db';
    }

    /**
     * @return string e.g. "vulsdb:2c26b46b68ffc68f..."
     * @throws VulsException If the database file does not exist
     */
    public function getDigest(): string {
        [$size, $modified] = $this->getFileInfo();

        return 'vulsdb:' . hash('sha256', $size . ':' . $modified);
    }

    /**
     * @return DateTime When the database file was written
     * @throws VulsException If the database file does not exist
     */
    public function getFeedDate(): DateTime {
        [, $modified] = $this->getFileInfo();

        return DateTime::createFromTimestamp($modified);
    }

    /**
     * @return array{0: int, 1: int} Size in bytes and modification time
     */
    private function getFileInfo(): array {
        $file = $this->getDatabaseFile();
        clearstatcache(true, $file);

        $size = @filesize($file);
        $modified = @filemtime($file);
        if ($size === false || $modified === false) {
            throw new VulsException('The Vuls database does not exist or is not accessible: ' . $file);
        }

        return [$size, $modified];
    }
}
