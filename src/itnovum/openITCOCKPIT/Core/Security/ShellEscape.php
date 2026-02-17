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

namespace App\itnovum\openITCOCKPIT\Core\Security;

class ShellEscape {

    /**
     * Escapes dangerous shell characters in a string for bash/sh
     * It will replace all double quotes with single quotes first.
     * Then it will escape all dangerous characters with a backslash, but only if they are not already escaped.
     *
     * A single quote will be escaped with '\'' which is the only way to escape a single quote in a single quoted string in bash/sh.
     *
     * @param string $value
     * @return string
     */
    public static function escape(string $value): string {
        // Remove null bytes and control characters
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        $isInDoubleQuotes = false;
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $isInDoubleQuotes = true;
        }

        // We do not want any double quotes in the string, as they allow execution of commands via $() or ``.
        // So we replace them with single quotes
        if ($isInDoubleQuotes) {
            // Remove the surrounding double quotes
            $value = substr($value, 1, -1);

            // Replace all escaped double quotes wit unescaped single quotes
            $value = str_replace('\\"', "'", $value);

            // Add single quotes as the string was in quotes before, and we want to keep that
            $value = "'" . $value . "'";
        }

        // Check if the string is in single quotes, if so we do not want to escape any characters as they are already safe, but we need to escape ' if not already escaped
        $isInSingleQuote = false;
        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $isInSingleQuote = true;
        }

        if ($isInSingleQuote) {
            // First we remove the surrounding single quotes, then we escape ' if not already escaped, and finally we add the single quotes back
            $value = substr($value, 1, -1);

            // Replace all escaped single quotes with unescaped single quotes
            $value = str_replace("\\'", "'", $value);

            //return "'" . str_replace("'", "'\''", $value) . "'";

            var_dump($value);
            return escapeshellarg($value);
        }

        // We are not in quotes, so we need to escape all dangerous characters, but only if not already escaped
        // Dangerous chars outside single quotes
        $dangerousUnquoted = ['`', '$', '\\', '"', "'", ';', '&', '|', '<', '>', '(', ')', '{', '}', '[', ']', '*', '?', '~', '#', '%', '!', '=', ' '];

        // If we have more than two backslashes in a row, we remove them as this is suspicious
        // Attention! This RegEx wants to match two or more backslashes in a row
        // We have to escape it twice: once for the PHP string, once for the regex engine, that's why we have four backslashes in a row in the regex pattern
        $value = preg_replace('/\\\\{2,}/', '\\', $value);

        // Loop through the string and escape dangerous characters if not already escaped
        $result = '';
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $char = $value[$i];
            $next = $i < $len - 1 ? $value[$i + 1] : false;
            if ($char === '\\') {
                // Current character is a backslash, we have already removed all \\ above with the regex.
                // We now have to make sure, if this is just a backslash like in "AD\Login"
                // or if this backslash is escaping a dangerous character like in "escaped\$dollar" or "escaped\`backtick\`"
                if ($next !== false && in_array($next, $dangerousUnquoted, true)) {
                    // This backslash is escaping a dangerous character, so we keep it as is and skip escaping this one
                    // Nothing to do
                    continue;
                } else {
                    // End of string, or char is not dangerous, so we escape this backslash
                    $result .= '\\';
                }
            } else if (in_array($char, $dangerousUnquoted, true)) {
                // Escape dangerous character
                $result .= '\\';
            }

            $result .= $char;
        }

        return $result;
    }

}
