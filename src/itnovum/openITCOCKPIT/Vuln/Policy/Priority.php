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

namespace itnovum\openITCOCKPIT\Vuln\Policy;

/**
 * Priority of a CVE together with its origin, see PriorityResolver.
 */
readonly class Priority {

    /**
     * @param Severity|null $severity null if nobody rates the CVE
     * @param PrioritySource $source
     * @param float|null $cvssScore Only for CVSS sources
     * @param string|null $vendorLabel Term of the vendor as written in the feed, e.g. "Important"
     */
    public function __construct(
        public ?Severity      $severity,
        public PrioritySource $source,
        public ?float         $cvssScore = null,
        public ?string        $vendorLabel = null
    ) {
    }

    public static function unknown(): self {
        return new self(null, PrioritySource::Unknown);
    }

    /**
     * @return int|null Value for the severity columns, null if unknown
     */
    public function level(): ?int {
        return $this->severity?->value;
    }
}
