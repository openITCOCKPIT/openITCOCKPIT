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


use Yasumi\Holiday;
use Yasumi\Yasumi;

class Holidays {

    /**
     * @param string $countryCode
     * @return array|Holiday[]
     * @throws \ReflectionException
     */
    public function getHolidays($countryCode = 'de') {
        $countryCode = strtoupper($countryCode);
        $localCode = $this->getLocalCodeByCountryCode($countryCode);

        $providers = Yasumi::getProviders();
        if (!isset($providers[$countryCode])) {
            throw new \RuntimeException(sprintf('No provider for country code "%s"', $countryCode));
        }

        $provider = Yasumi::create($providers[$countryCode], date('Y'));
        $holidays = $provider->getHolidays();

        $filteredHolidays = [];
        foreach ($holidays as $holiday) {
            /** @var $holiday Holiday */
            $holidayName = $holiday->getName(); //en_US
            if (isset($holiday->translations[$localCode])) { //exists de_DE ?
                if ($holiday->translations[$localCode] !== $holidayName) { //No not translate english to english
                    $holidayName = $holiday->translations[$localCode] . ' / ' . $holidayName; //Local translation + english
                }
            } else {
                $shortLocalCode = explode('_', $localCode, 2);  //exists 'de' ? (remove _DE from de_DE) ?
                if (isset($holiday->translations[strtolower($shortLocalCode[0])])) {
                    if ($holiday->translations[strtolower($shortLocalCode[0])] !== $holidayName) { //No not translate english to english
                        $holidayName = $holiday->translations[strtolower($shortLocalCode[0])] . ' / ' . $holidayName; //Local translation + english
                    }
                }
            }


            $filteredHolidays[date('Y-m-d', $holiday->getTimestamp())] = [
                'start'           => $holiday->format('Y-m-d'),
                'title'           => $holidayName,
                'default_holiday' => true,
                'className'       => 'bg-color-magenta'
            ];
        }

        ksort($filteredHolidays);
        return array_values($filteredHolidays);
    }


    /**
     * @return array countries as CountryCode => CountryName Array
     */
    public function getCountries() {
        return Locales::getCountries();
    }

    /**
     * @param string $countryCode
     * @return string
     */
    private function getLocalCodeByCountryCode($countryCode) {
        return Locales::getLocalCodeByCountryCode($countryCode);
    }
}
