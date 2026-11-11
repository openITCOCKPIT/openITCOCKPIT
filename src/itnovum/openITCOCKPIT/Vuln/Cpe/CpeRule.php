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
 * Maps installed applications to a CPE product: if all conditions match, the application is that product.
 *
 * Example: name equals "Google Chrome" and publisher contains "Google" => google:chrome
 */
readonly class CpeRule {

    /**
     * @param string $vendor Vendor part of the CPE, e.g. google
     * @param string $product Product part of the CPE, e.g. chrome
     * @param string $platform windows or macos
     * @param CpeCondition $firstCondition
     * @param CpeCondition|null $secondCondition Optional, both conditions have to match
     */
    public function __construct(
        public string        $vendor,
        public string        $product,
        public string        $platform,
        public CpeCondition  $firstCondition,
        public ?CpeCondition $secondCondition = null
    ) {
    }

    public function matches(InstalledApp $app): bool {
        if (!$this->firstCondition->matches($app)) {
            return false;
        }

        if ($this->secondCondition === null) {
            return true;
        }

        return $this->secondCondition->matches($app);
    }

    /**
     * @return string e.g. "google:chrome"
     */
    public function cpeKey(): string {
        return $this->vendor . ':' . $this->product;
    }
}
