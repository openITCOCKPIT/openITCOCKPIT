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

namespace App\Command\Helper;

use Cake\Command\Helper\TableHelper;
use UnexpectedValueException;

class CustomTableHelper extends TableHelper {
    
    /**
     * Get the width of a cell exclusive of style tags.
     *
     * @param string $text The text to calculate a width for.
     * @return int The width of the textual content in visible characters.
     */
    protected function _cellWidth(string $text): int {
        if ($text === '') {
            return 0;
        }

        if (!str_contains($text, '<') && !str_contains($text, '>')) {
            return mb_strwidth($text);
        }

        $styles = $this->_io->styles();
        $tags = implode('¦', array_keys($styles));
        $text = (string)preg_replace('#</?(?:' . $tags . ')>#', '', $text);

        return mb_strwidth($text);
    }

    /**
     * Output a row.
     *
     * @param array $row The row to output.
     * @param array<int> $widths The widths of each column to output.
     * @param array<string, mixed> $options Options to be passed.
     * @return void
     */
    protected function _render(array $row, array $widths, array $options = []): void {
        if ($row === []) {
            return;
        }

        $out = '';
        foreach (array_values($row) as $i => $column) {
            $column = (string)$column;
            $pad = $widths[$i] - $this->_cellWidth($column);
            if (!empty($options['style'])) {
                $column = $this->_addStyle($column, $options['style']);
            }
            if ($column !== '' && preg_match('#(.*)<text-right>.+</text-right>(.*)#', $column, $matches)) {
                if ($matches[1] !== '' || $matches[2] !== '') {
                    throw new UnexpectedValueException('You cannot include text before or after the text-right tag.');
                }
                $column = str_replace(['<text-right>', '</text-right>'], '', $column);
                $out .= '¦ ' . str_repeat(' ', $pad) . $column . ' ';
            } else {
                $out .= '¦ ' . $column . str_repeat(' ', $pad) . ' ';
            }
        }
        $out .= '¦';
        $this->_io->out($out);
    }
}
