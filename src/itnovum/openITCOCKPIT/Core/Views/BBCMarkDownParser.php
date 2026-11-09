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

namespace itnovum\openITCOCKPIT\Core\Views;


use ParsedownExtra;

/**
 * Renders text as BBCode when a recognized tag is present, otherwise as Markdown.
 */
class BBCMarkDownParser {

    /**
     * @param string|null $text
     * @param bool $escapedNewlines Decode escaped line breaks before Markdown parsing.
     * @return string
     */
    public function render(?string $text, bool $escapedNewlines = false): string {
        $text = $text ?? '';
        if ($this->isBbCode($text)) {
            $BBCodeParser = new BBCodeParser();
            return $BBCodeParser->nagiosNl2br($BBCodeParser->asHtml($text, true));
        }

        if ($escapedNewlines) {
            $text = str_replace(['\r\n', '\n', '\r'], "\n", $text);
        }

        $ParseDown = new ParsedownExtra();
        $ParseDown->setSafeMode(true);
        $ParseDown->setBreaksEnabled(true);
        return $ParseDown->text($text);
    }

    /**
     * Detect editor tags and the additional legacy tags registered by BBCodeParser.
     * A matching tag selects BBCode for the entire text, including code examples.
     *
     * @param string $text
     * @return bool
     */
    private function isBbCode(string $text): bool {
        return preg_match('/\[(b|i|u|color|text|left|center|right|justify|url|img|code|xx-small|x-small|small|large|x-large|xx-large)(?:\s*=[^\]]*)?\]/i', $text) === 1;
    }

}
