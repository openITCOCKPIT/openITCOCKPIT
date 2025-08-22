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

namespace itnovum\openITCOCKPIT\Core;


use DateTimeZone;

class Timezone {

    /**
     * Get list of timezone identifiers
     *
     * @param int|string $filter A regex to filter identifier
     *    Or one of DateTimeZone class constants (PHP 5.3 and above)
     * @param string $country A two-letter ISO 3166-1 compatible country code.
     *    This option is only used when $filter is set to DateTimeZone::PER_COUNTRY (available only in PHP 5.3 and above)
     * @param bool|array $options If true (default value) groups the identifiers list by primary region.
     *    Otherwise, an array containing `group`, `abbr`, `before`, and `after` keys.
     *    Setting `group` and `abbr` to true will group results and append timezone
     *    abbreviation in the display value. Set `before` and `after` to customize
     *    the abbreviation wrapper.
     * @return array List of timezone identifiers
     * @since 2.2
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::listTimezones
     * @license MIT
     */
    public static function listTimezones($filter = null, $country = null, $options = []) {
        if (is_bool($options)) {
            $options = [
                'group' => $options,
            ];
        }
        $defaults = [
            'group'  => true,
            'abbr'   => false,
            'before' => ' - ',
            'after'  => null,
        ];
        $options += $defaults;
        $group = $options['group'];

        $regex = null;
        if (is_string($filter)) {
            $regex = $filter;
            $filter = null;
        }
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            if ($regex === null) {
                $regex = '#^((Africa|America|Antartica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)/|UTC)#';
            }
            $identifiers = DateTimeZone::listIdentifiers();
        } else {
            if ($filter === null) {
                $filter = DateTimeZone::ALL;
            }
            $identifiers = DateTimeZone::listIdentifiers($filter, $country);
        }

        if ($regex) {
            foreach ($identifiers as $key => $tz) {
                if (!preg_match($regex, $tz)) {
                    unset($identifiers[$key]);
                }
            }
        }

        if ($group) {
            $return = [];
            $now = time();
            $before = $options['before'];
            $after = $options['after'];
            foreach ($identifiers as $key => $tz) {
                $abbr = null;
                if ($options['abbr']) {
                    $dateTimeZone = new DateTimeZone($tz);
                    $trans = $dateTimeZone->getTransitions($now, $now);
                    $abbr = isset($trans[0]['abbr']) ?
                        $before . $trans[0]['abbr'] . $after :
                        null;
                }
                $item = explode('/', $tz, 2);
                if (isset($item[1])) {
                    $return[$item[0]][$tz] = $item[1] . $abbr;
                } else {
                    $return[$item[0]] = [$tz => $item[0] . $abbr];
                }
            }
            return $return;
        }
        return array_combine($identifiers, $identifiers);
    }

}
