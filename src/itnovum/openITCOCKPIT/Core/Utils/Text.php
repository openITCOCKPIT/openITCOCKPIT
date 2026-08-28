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

namespace App\itnovum\openITCOCKPIT\Core\Utils;

class Text {

    /**
     * This method will truncate a text in the middle according to the max length.
     * So "a very long text" will become "a very...text"
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function truncateMiddle(string $text, int $length = 100): string {
        if ($length <= 0) {
            return '';
        }

        $textLength = mb_strlen($text);
        if ($textLength <= $length) {
            return $text;
        }

        $remaining = $length - 3; // 3 is the length of the ellipsis '...'
        $leftLength = (int)ceil($remaining / 2);
        $rightLength = (int)floor($remaining / 2);

        $left = mb_substr($text, 0, $leftLength);
        $right = $rightLength > 0 ? mb_substr($text, -$rightLength) : '';

        return $left . '...' . $right;
    }

}
