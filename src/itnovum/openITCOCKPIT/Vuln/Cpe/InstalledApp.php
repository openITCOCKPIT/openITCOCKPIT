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

namespace itnovum\openITCOCKPIT\Vuln\Cpe;

/**
 * An application installed on a Windows or macOS host, as reported by the agent.
 */
readonly class InstalledApp {

    /**
     * @param string $name Name as shown by the operating system, e.g. "Mozilla Firefox (x64 de)"
     * @param string $version
     * @param string|null $publisher Windows only
     * @param string|null $bundleId macOS only, e.g. "com.google.Chrome"
     */
    public function __construct(
        public string  $name,
        public string  $version,
        public ?string $publisher = null,
        public ?string $bundleId = null
    ) {
    }

    /**
     * @param string $field One of the CpeCondition::FIELD_* constants
     * @return string|null null if the agent does not report this field
     */
    public function valueOf(string $field): ?string {
        return match ($field) {
            CpeCondition::FIELD_NAME      => $this->name,
            CpeCondition::FIELD_PUBLISHER => $this->publisher,
            CpeCondition::FIELD_BUNDLE_ID => $this->bundleId,
        };
    }
}
