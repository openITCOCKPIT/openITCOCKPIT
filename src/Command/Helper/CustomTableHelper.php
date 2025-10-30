<?php

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

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
