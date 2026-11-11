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
 * One condition of a CPE rule, e.g. "name starts with 7-Zip ".
 *
 * The comparison is case-insensitive. The value is used exactly as entered, also with spaces at the end:
 * "starts with '7-Zip '" does not match "7-Zip-zstd". There are no regular expressions on purpose:
 * they are hard to read for users, and a bad expression can slow down every evaluation.
 */
readonly class CpeCondition {

    public const FIELD_NAME = 'name';
    public const FIELD_PUBLISHER = 'publisher';
    public const FIELD_BUNDLE_ID = 'bundle_id';

    public const MATCH_EQUALS = 'equals';
    public const MATCH_STARTS_WITH = 'starts_with';
    public const MATCH_CONTAINS = 'contains';
    public const MATCH_ENDS_WITH = 'ends_with';

    /**
     * @param string $field One of the FIELD_* constants
     * @param string $match One of the MATCH_* constants
     * @param string $value
     */
    public function __construct(
        public string $field,
        public string $match,
        public string $value
    ) {
    }

    public function matches(InstalledApp $app): bool {
        $actual = $app->valueOf($this->field);

        // The agent does not report this field, e.g. no bundle identifier
        if ($actual === null || $actual === '') {
            return false;
        }

        $actual = mb_strtolower($actual);
        $expected = mb_strtolower($this->value);

        return match ($this->match) {
            self::MATCH_EQUALS      => $actual === $expected,
            self::MATCH_STARTS_WITH => str_starts_with($actual, $expected),
            self::MATCH_CONTAINS    => str_contains($actual, $expected),
            self::MATCH_ENDS_WITH   => str_ends_with($actual, $expected),
        };
    }
}
