<?php
/**
 * Statusengine Worker
 * Copyright (C) 2016-2017  Daniel Ziegler
 *
 * This file is dual licensed
 *
 * 1)
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * 2)
 * MIT License
 * Copyright (C) 2016-2017  Daniel Ziegler
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Statusengine;


class PerfdataParser {

    /**
     * @var string
     */
    private $perfdataString;

    /**
     * PerfdataParser constructor.
     * @param string $perfdataString string with monitoring performance data
     */
    public function __construct($perfdataString) {
        $this->perfdataString = $perfdataString??'';
    }

    /**
     * Parse perfdata of the naemon plugin output to an array
     *
     * @return array An array with the gauges and perfdata values
     */
    function parse() {
        $return = [];
        $gauges = $this->splitGauges($this->perfdataString);
        foreach ($gauges as $gauge) {
            $result = $this->parseGauge($gauge);
            $return = array_merge($return, $result);
        }
        return $return;
    }

    /**
     * Split the given perfdata string into gauges
     * @param string $perfdataString => rta=0.069000ms;100.000000;500.000000;0.000000 pl=0%;20;60;0
     * @return array An array with the found gauges
     */
    public function splitGauges() {
        $perfdataString = trim($this->perfdataString);
        $len = strlen($perfdataString);
        $pointer = 0;
        $gauges = [];
        $gauge = '';
        $state = 'SEARCH_SPACE';
        while ($len > $pointer) {
            $char = $perfdataString[$pointer];
            if (($char == "'" || $char == '"') && $state == 'SEARCH_SPACE') {
                //we found a starting quote
                $state = 'SEARCH_QUOTE';
                if ($char == '"') {
                    $char = "'";
                }
                $gauge .= $char;
                $pointer++;
                continue;
            }
            switch ($state) {
                case 'SEARCH_QUOTE':
                    if (($char == "'" || $char == '"') && $state == 'SEARCH_QUOTE') {
                        //we found the ending quote
                        if ($char == '"') {
                            $char = "'";
                        }
                        $gauge .= $char;
                        $state = 'SEARCH_SPACE';
                        $pointer++;
                        continue 2;
                    }
                    break;
                case 'SEARCH_SPACE':
                    if ($char == ' ') {
                        $gauges[] = $gauge;
                        $gauge = '';
                        $pointer++;
                        continue 2;
                    }
                    break;
            }
            $gauge .= $char;
            $pointer++;
            //reach end of strig?
            if ($pointer == $len) {
                $gauges[] = $gauge;
            }
        }
        return $gauges;
    }

    /**
     * Split the given gauge into the perfdata values
     * like current. unit, warning, critical, min and max
     * @param string $perfdataString => rta=0.069000ms;100.000000;500.000000;0.000000
     * @return array An array with the gauge
     */
    public function parseGauge($gauge) {
        $len = strlen($gauge);
        $pointer = 0;
        $gaugeName = '';
        $result = [
            'current'  => null,
            'unit'     => null,
            'warning'  => null,
            'critical' => null,
            'min'      => null,
            'max'      => null
        ];
        $state = 'SEARCH_GAUGE_NAME';
        while ($len > $pointer) {
            $char = $gauge[$pointer];
            switch ($state) {
                case 'SEARCH_GAUGE_NAME':
                    if ($char == '=') {
                        $state = 'SEARCH_VALUE';
                        $pointer++;
                        continue 2;
                    }
                    $gaugeName .= $char;
                    break;
                case 'SEARCH_VALUE':
                    if ($char == '.' || $char == ',' || $char == '-' || ($char >= '0' && $char <= '9')) {
                        $char = $this->makeUsDecimal($char);
                        $result['current'] .= $char;
                    } else {
                        //not numeric, i guess we found the unit
                        $state = 'SEARCH_UNIT';
                        continue 2;
                    }
                    break;
                case 'SEARCH_UNIT':
                    if ($char == ';') {
                        $state = 'SEARCH_WARNING';
                        $pointer++;
                        continue 2;
                    }
                    $result['unit'] .= $char;
                    break;
                case 'SEARCH_WARNING':
                    if ($char == ';') {
                        $state = 'SEARCH_CRITICAL';
                        $pointer++;
                        continue 2;
                    }
                    $char = $this->makeUsDecimal($char);
                    $result['warning'] .= $char;
                    break;
                case 'SEARCH_CRITICAL':
                    if ($char == ';') {
                        $state = 'SEARCH_MINIMUM';
                        $pointer++;
                        continue 2;
                    }
                    $char = $this->makeUsDecimal($char);
                    $result['critical'] .= $char;
                    break;
                case 'SEARCH_MINIMUM':
                    if ($char == ';') {
                        $state = 'SEARCH_MAXIMUM';
                        $pointer++;
                        continue 2;
                    }
                    $char = $this->makeUsDecimal($char);
                    $result['min'] .= $char;
                    break;
                case 'SEARCH_MAXIMUM':
                    if ($char == ';') {
                        $pointer++;
                        $state = 'DONE';
                        continue 2;
                    }
                    $char = $this->makeUsDecimal($char);
                    $result['max'] .= $char;
                    break;
            }
            $pointer++;
        }
        //Fix %% unit issue
        if ($result['unit'] == '%%') {
            $result['unit'] = '%';
        }

        // ITC-2824 make classical metrics look the same
        $result['metric'] = $gaugeName;

        $return = [
            $gaugeName => $result
        ];
        return $return;
    }

    /**
     * Form a number in US decimal
     * @param int a EU or US decimal number
     * @return int a number in US decimal format
     */
    public function makeUsDecimal($number) {
        return str_replace(',', '.', $number);
    }

}
