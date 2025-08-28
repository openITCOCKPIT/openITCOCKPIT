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

namespace App\itnovum\openITCOCKPIT\Core;

class EmailCharacters {

    /**
     * Some characters like "," could break the To header of E-Mails
     * This function remove (hopefully) all dangerous characters
     * From wikipedia https://de.wikipedia.org/wiki/Header_(E-Mail):
     * > To: Der Empfänger
     * > Eine oder mehrere durch Kommata getrennte E-Mail-Adressen, an die die E-Mail primär gesendet wird. Jedem Adressaten werden auch alle anderen E-Mail-Adressen mitgeteilt.
     *
     * @param string $str
     * @return string
     */
    public static function removeDangerousCharactersForToHeader(string $str): string {
        // https://www.jochentopf.com/de/email/chars.html
        return str_replace(
            ['"', "'", '$', '%', '(', ')', '*', ',', '/', ':', ';', '<', '=', '>', '=', '@', '[', ']', '\\', '`', '{', '|', '}', '~', '?'],
            '',
            $str
        );
    }

}
