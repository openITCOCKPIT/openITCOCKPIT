<?php declare(strict_types=1);
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

namespace itnovum\openITCOCKPIT\Perfdata;

use InvalidArgumentException;

class ScaleType {
    /**
     * 🟦
     * @var string
     */
    public const O = 'O';
    /**
     * 🟨🟩
     * @var string
     */
    public const W_O = 'W<O';
    /**
     * 🟥🟨🟩
     * @var string
     */
    public const C_W_O = 'C<W<O';
    /**
     * 🟩🟨
     * @var string
     */
    public const O_W = 'O<W';
    /**
     * 🟩🟨🟥
     * @var string
     */
    public const O_W_C = 'O<W<C';
    /**
     * 🟥🟨🟩🟨🟥
     * @var string
     */
    public const C_W_O_W_C = 'C<W<O<W<C';
    /**
     * 🟩🟨🟥🟨🟩
     * @var string
     */
    public const O_W_C_W_O = 'O<W<C<W<O';

    /**
     *
     */
    public const ALL = [
        self::O,
        self::W_O,
        self::C_W_O,
        self::O_W,
        self::O_W_C,
        self::C_W_O_W_C,
        self::O_W_C_W_O,
    ];

    /**
     * I will validate if all params are numeric values and if they are in order.
     *
     * If any of the passed arguments is not a number, FALSE will be returned.
     *
     * @return bool
     *
     * @example:
     *      validateOrder(0, 1, 2, 3,    4, 5.6, 6.7, 7.89, 9)  => true  // They are ovisously in order.
     *      validateOrder(0, 1, 2, 2,    2,   2, 6.7, 7.89, 9)  => true  // 2 is repeated, but that's fine
     *      validateOrder(0, 1, 2, 3,    4,   4, 6.7, 7.89, 1)  => false // 1 at the end is not in order.
     *      validateOrder(0, 1, 2, 3, null, 5.6, 6.7, 7.89, 9)  => false // There's a NULL value in here. FALSE
     */
    private static function validateOrder() {
        $last = null;
        $values = func_get_args();
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }

            if ($value >= $last) {
                $last = $value;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * For the given $invert flag and both given Thresholds, I will interprete the correct ScaleType to use.
     *
     * @param bool $invert
     * @param Threshold $warn
     * @param Threshold $crit
     *
     * @return string
     * @throws InvalidArgumentException in case the given parameters don't match into a valid ScaleType.
     *
     */
    public static function get(bool $invert, Threshold $warn, Threshold $crit): string {
        # echo "$crit->low < $warn->low < $warn->high < $crit->high";
        if (false) {
            // Just for legibility
        } else if ($invert && self::validateOrder($warn->low, $crit->low, $crit->high, $warn->high)) {
            return ScaleType::O_W_C_W_O;

        } else if (!$invert && self::validateOrder($crit->low, $warn->low, $warn->high, $crit->high)) {
            return ScaleType::C_W_O_W_C;

        } else if (self::validateOrder($warn->low, $crit->low)) {
            return ScaleType::O_W_C;

        } else if (self::validateOrder($crit->low, $warn->low)) {
            return ScaleType::C_W_O;

        } else if ($warn->high && $crit->high === null) {
            return ScaleType::O_W;

        } else if ($warn->low && $crit->low === null) {
            return ScaleType::W_O;

        } else if ($warn->low === null && $warn->high === null && $crit->low === null && $crit->high === null) {
            return ScaleType::O;
        }

        throw new InvalidArgumentException("This setup is unknown to me. See details.");
    }

    public static function findMin(): ?float {
        $last = null;
        $values = func_get_args();
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if ($last === null || $value < $last) {
                $last = $value;
            }
        }
        if ($last === null) {
            return null;
        }
        return (float)$last;
    }

    public static function findMax(): ?float {
        $last = null;
        $values = func_get_args();
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if ($last === null || $value > $last) {
                $last = $value;
            }
        }
        if ($last === null) {
            return null;
        }
        return (float)$last;
    }
}

// Check Plugin for testing
/*
 #!/usr/bin/php
<?php

$type = $_SERVER['argv'][1];

$min = -10;
$max = 10;
$value = rand($min, $max);
$wl = -3;
$wh = 3;
$cl = -5;
$ch = 5;


switch($type){

        case 'W<O':
                echo "Case 'W<O' | value=$value;$wl;;;\n";
                break;

        case 'C<W<O':
                // : < 10, (outside {10 .. ∞})
                echo "Case 'C<W<O' | value=$value;$wl:;$cl:;;\n";
                break;

        case 'O<W':
                //
                echo "Case 'O<W' | value=$value;$wl:$wh;;;\n";
                break;

        case 'O<W<C':
                echo "Case 'O<W<C' | value=$value;$wh;$ch;;\n";
                break;

        case 'C<W<O<W<C':
                // < 10 or > 20, (outside the range of {10 .. 20})
                echo "Case 'C<W<O<W<C' | value=$value;$wl:$wh;$cl:$ch;;\n";
                break;

        case 'O<W<C<W<O':
                // @ ≥ 10 and ≤ 20, (inside the range of {10 .. 20})
                echo "Case 'O<W<C<W<O' | value=$value;@-5:5;@-3:3;;\n";
                break;

        case 'O':
                echo "Case 'O' | value=$value;;;;\n";
                break;

        default:
                echo "Unsupported type '$type'\n";
                exit(1);

}

exit(0);
 */
