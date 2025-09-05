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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


namespace itnovum\openITCOCKPIT\Grafana;

/**
 * This class is a PHP representation of the default Grafana Units.
 * It is used to create a Select box of all Grafana Units for the Grafana User Dashboard Editor.
 */
class GrafanaTargetUnits {

    /**
     * @return array
     */
    public function getUnitsForJavaScript() {
        $jsCategoriesAndUnits = [];
        foreach ($this->getUnits() as $category => $units) {
            $jsUnits = [];
            foreach ($units as $key => $unit) {
                $jsUnits[] = [
                    'key'  => $key,
                    'unit' => $unit
                ];
            }

            $jsCategoriesAndUnits[] = [
                'key'    => $category,
                'values' => $jsUnits
            ];
        }
        return $jsCategoriesAndUnits;
    }

    /**
     * @return array
     */
    public function getUnits() {
        //Units from https://github.com/grafana/grafana/blob/master/public/app/core/utils/kbn.ts
        return [
            //'grafana_name' => 'human name',

            //none
            __('None')            => [
                'none'        => __('None'),
                'short'       => __('Short'),
                'percent'     => __('Percent (0-100)'),
                'percentunit' => __('Percent (0.0-1.0)'),
                'humidity'    => __('Humidity (%H)'),
                'dB'          => __('Decibel (dB)'),
                'hex0x'       => __('Hexadecimal (0x)'),
                'hex'         => __('Hexadecimal'),
                'sci'         => __('Scientific notation'),
                'locale'      => __('Locale format'),
            ],

            //time
            __('Time')            => [
                'hertz' => __('Herz'),
                'ns'    => __('Nanoseconds (ns)'),
                'µs'    => __('Microseconds (µs)'),
                'ms'    => __('Milliseconds (ms)'),
                's'     => __('Seconds (s)'),
                'm'     => __('Minutes'),
                'h'     => __('Hours'),
                'd'     => __('Days'),
            ],

            //data (IEC)
            __('Data (IEC)')      => [
                'bits'   => __('Bits'),
                'bytes'  => __('Bytes'),
                'kbytes' => __('Kibibytes (KiB)'),
                'mbytes' => __('Mebibytes (MiB)'),
                'gbytes' => __('Gibibytes (GiB)'),
            ],

            //data (Metric)
            __('Data (Metric)')   => [
                'decbits'   => __('Bits'),
                'decbytes'  => __('Bytes'),
                'deckbytes' => __('Kilobytes (KB)'),
                'decmbytes' => __('Megabytes (MB)'),
                'decgbytes' => __('Gigabytes (GB)'),
            ],

            //data rate
            __('Data rates')      => [
                'pps'   => __('Packets/sec'),
                'bps'   => __('Bits/sec'),
                'Bps'   => __('Bytes/sec'),
                'Kbits' => __('Kilobits/sec (kbit/s)'),
                'KBs'   => __('Kilobytes/ses (KB/s)'),
                'Mbits' => __('Megabits/sec (mbit/s)'),
                'MBs'   => __('Megabytes/sec (MB/s)'),
                'Gbits' => __('Gigabits/sec (gbit/s)'),
                'GBs'   => __('Gigabytes/sec (GB/s)'),
            ],

            //data throughput
            __('Data throughput') => [
                'ops'   => __('ops/sec (ops)'),
                'reqps' => __('requests/sec (rps)'),
                'rps'   => __('reads/sec (rps)'),
                'wps'   => __('writes/sec (wps)'),
                'iops'  => __('I/O ops/sec (iops)'),
                'opm'   => __('ops/min (opm)'),
                'rpm'   => __('reads/min (rpm)'),
                'wpm'   => __('writes/min (wpm)'),
            ],

            //temperature
            __('Temperature')     => [
                'celsius'   => __('Celsius (°C)'),
                'farenheit' => __('Farenheit (°F)'),
                'kelvin'    => __('Kelvin (K)'),
            ],

            //currency
            __('Currencies')      => [
                'currencyUSD' => __('Dollars ($)'),
                'currencyEUR' => __('Euro (€)'),
                'currencyGBP' => __('Pounds (£)'),
                'currencyJPY' => __('Yen (¥)'),
                'currencyRUB' => __('Rubles (₽)'),
                'currencyUAH' => __('Hryvnias (₴)'),
                'currencyBRL' => __('Real (R$)'),
                'currencyDKK' => __('Danish Krone (kr)'),
                'currencyISK' => __('Icelandic Króna (kr)'),
                'currencyNOK' => __('Norwegian Krone (kr)'),
                'currencySEK' => __('Swedish Krona (kr)'),
                'currencyCZK' => __('Czech koruna (czk)'),
                'currencyCHF' => __('Swiss franc (CHF)'),
                'currencyPLN' => __('Polish Złoty (PLN)'),
                'currencyBTC' => __('Bitcoin (฿)'),
            ],

            //length
            __('Length')          => [
                'lengthmm' => __('Millimetre (mm)'),
                'lengthm'  => __('Meter (m)'),
                'lengthkm' => __('Kilometer (km)'),
                'lengthft' => __('Feet (ft)'),
                'lengthmi' => __('Mile (mi)'),
            ],


            //area
            __('Area')            => [
                'areaM2'  => __('Square Meters (m²)'),
                'areaF2'  => __('Square Feet (ft²)'),
                'areaMI2' => __('Square Miles (mi²)'),
            ],

            //mass
            __('Mass')            => [
                'massmg' => __('Milligram (mg)'),
                'massg'  => __('Gram (g)'),
                'masskg' => __('Kilogram (kg)'),
                'masst'  => __('Ton (t)'),
            ],

            //velocity
            __('Velocity')        => [
                'velocityms'   => __('Metres/second (m/s)'),
                'velocitykmh'  => __('Kilometers/hour (km/h)'),
                'velocitymph'  => __('Miles/hour (mph)'),
                'velocityknot' => __('Knot (kn)'),
            ],


            //volume
            __('Volume')          => [
                'mlitre'  => __('Millilitre (ml)'),
                'litre'   => __('Litre (l)'),
                'm3'      => __('Cubic metre (m3)'),
                'dm3'     => __('Cubic decimetre (dm3)'),
                'gallons' => __('Gallons'),
            ],

            //energy
            __('Energy')          => [
                'watt'          => __('Watt (W)'),
                'kwatt'         => __('Kilowatt (kW)'),
                'mwatt'         => __('Milliwatt (mW)'),
                'Wm2'           => __('Watt per square metre (W/m²)'),
                'voltamp'       => __('Volt-ampere (VA)'),
                'kvoltamp'      => __('Kilovolt-ampere (kVA)'),
                'voltampreact'  => __('Volt-ampere reactive (var)'),
                'kvoltampreact' => __('Kilovolt-ampere reactive (kvar)'),
                'watth'         => __('Watt-hour (Wh)'),
                'kwatth'        => __('Kilowatt-hour (kWh)'),
                'kwattm'        => __('Kilowatt-min (kWm)'),
                'joule'         => __('Joule (J)'),
                'ev'            => __('Electron volt (eV)'),
                'amp'           => __('Ampere (A)'),
                'kamp'          => __('Kiloampere (kA)'),
                'mamp'          => __('Milliampere (mA)'),
                'volt'          => __('Volt (V)'),
                'kvolt'         => __('Kilovolt (kV)'),
                'mvolt'         => __('Millivolt (mV)'),
                'dBm'           => __('Decibel-milliwatt (dBm)'),
                'ohm'           => __('Ohm (Ω)'),
                'lumens'        => __('Lumens (Lm)'),
            ],

            //pressure
            __('Pressure')        => [
                'pressurembar' => __('Millibars'),
                'pressurebar'  => __('Bars'),
                'pressurekbar' => __('Kilobars'),
                'pressurehpa'  => __('Hectopascals'),
                'pressurekpa'  => __('Kilopascals'),
                'pressurehg'   => __('Inches of mercury'),
                'pressurepsi'  => __('PSI'),
            ],

            //force
            __('Force')           => [
                'forceNm'  => __('Newton-meters (Nm)'),
                'forcekNm' => __('Kilonewton-meters (kNm)'),
                'forceN'   => __('Newtons (N)'),
                'forcekN'  => __('Kilonewtons (kN)'),
            ],

            //flow
            __('Flow')            => [
                'litreh'   => __('Litre/hour (l/h)'),
                'flowlpm'  => __('Litre/min (l/min)'),
                'flowmlpm' => __('Millilitre/min (ml/min)'),
                'flowcms'  => __('Cubic meters/sec (cms)'),
                'flowgpm'  => __('Gallons/min (gpm)'),
                'flowcfs'  => __('Cubic feet/sec (cfs)'),
                'flowcfm'  => __('Cubic feet/min (cfm)'),
            ],

            //angle
            __('Angle')           => [
                'degree' => __('Degrees (°)'),
                'radian' => __('Radians'),
                'grad'   => __('Gradian'),
            ],

            //date & time
            __('Date and time')   => [
                'dateTimeAsIso'   => __('YYYY-MM-DD HH:mm:ss'),
                'dateTimeAsUS'    => __('DD/MM/YYYY h:mm:ss a'),
                'dateTimeFromNow' => __('From Now'),
            ],

            //hash rate
            __('Hash rates')      => [
                'Hs'  => __('Hashes/sec'),
                'KHs' => __('Kilohashes/sec'),
                'MHs' => __('Megahashes/sec'),
                'GHs' => __('Gigahashes/sec'),
                'THs' => __('Terahashes/sec'),
                'PHs' => __('Petahashes/sec'),
                'EHs' => __('Exahashes/sec'),
            ],

            //acceleration
            __('Acceleration')    => [
                'accMS2' => __('Meters/sec²'),
                'accFS2' => __('Feet/sec²'),
                'accG'   => __('G unit'),
            ],

            //radiation
            __('Radiation')       => [
                'radbq'     => __('Becquerel (Bq)'),
                'radci'     => __('curie (Ci)'),
                'radgy'     => __('Gray (Gy)'),
                'radrad'    => __('rad'),
                'radsv'     => __('Sievert (Sv)'),
                'radrem'    => __('rem'),
                'radexpckg' => __('Exposure (C/kg)'),
                'radr'      => __('roentgen (R)'),
                'radsvh'    => __('Sievert/hour (Sv/h)'),
            ],

            //concentration
            __('Concentration')   => [
                'ppm'      => __('parts-per-million (ppm)'),
                'conppb'   => __('parts-per-billion (ppb)'),
                'conngm3'  => __('nanogram per cubic metre (ng/m³)'),
                'conngNm3' => __('nanogram per normal cubic metre (ng/Nm³)'),
                'conμgm3'  => __('microgram per cubic metre (μg/m³)'),
                'conμgNm3' => __('microgram per normal cubic metre (μg/Nm³)'),
                'conmgm3'  => __('milligram per cubic metre (mg/m³)'),
                'conmgNm3' => __('milligram per normal cubic metre (mg/Nm³)'),
                'congm3'   => __('gram per cubic metre (g/m³)'),
                'congNm3'  => __('gram per normal cubic metre (g/Nm³)'),
            ],
        ];
    }

    /**
     * @param $unit
     * @return bool
     */
    public function exists($unit) {
        foreach ($this->getUnits() as $category => $units) {
            if (isset($units[$unit])) {
                return true;
            }
        }
        return false;
    }

}

