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
 * Severity of a vulnerability. The value is stored in the database (severity columns).
 */
enum Severity: int {

    case None = 0;
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    /**
     * Translates the terms of the vendors, e.g. "Important" of Red Hat or "unimportant" of Debian.
     *
     * @param string $label
     * @return self|null null if the term is unknown
     */
    public static function fromLabel(string $label): ?self {
        switch (strtolower(trim($label))) {
            case 'critical':
                return self::Critical;

            case 'high':
            case 'important':
                return self::High;

            case 'medium':
            case 'moderate':
                return self::Medium;

            case 'low':
                return self::Low;

            case 'negligible':
            case 'unimportant':
                return self::None;

            default:
                return null;
        }
    }

    /**
     * Bands of CVSS v3 and v4: from 9.0 critical, from 7.0 high, from 4.0 medium, below low.
     *
     * @param float $score
     * @return self
     */
    public static function fromCvssScore(float $score): self {
        if ($score >= 9.0) {
            return self::Critical;
        }
        if ($score >= 7.0) {
            return self::High;
        }
        if ($score >= 4.0) {
            return self::Medium;
        }
        return self::Low;
    }

    public function isHighOrAbove(): bool {
        return $this->value >= self::High->value;
    }

    public function isHigherThan(self $other): bool {
        return $this->value > $other->value;
    }
}
