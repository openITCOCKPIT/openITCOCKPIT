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


class GrafanaTargetUnit {


    /**
     * @var string
     */
    private $unit;

    /**
     * @var bool
     */
    private $isStatic;

    /**
     * GrafanaTargetUnit constructor.
     * @param $unit
     */
    public function __construct($unit, $isStatic = false) {
        $this->unit = $unit;
        $this->isStatic = $isStatic;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->getUnit();
    }

    /**
     * This method converts common Naemon Metric Units into Grafana Units
     * E.g.: "B" will return "bytes" as Grafana expect it.
     *
     * It has more or less nothing in common with \itnovum\openITCOCKPIT\Grafana\GrafanaTargetUnits as this calss
     * is used to create a Selectbox of avaialbe Grafana Units while this function converts Naemon/Nagios units into Grafana units
     *
     * @return string
     */
    public function getUnit() {

        //For units that are not suported by this class.
        //This class was build to convert Nagios/rrdtool units to Grafana...
        if ($this->isStatic) {
            return $this->unit;
        }

        switch ($this->unit) {
            // time
            case 'hz':
                return 'hertz';
                break;
            case 'ns':
                return 'ns';
                break;
            case 'us':
            case 'µs':
                return 'µs';
                break;
            case 'ms':
                return 'ms';
                break;
            case 's':
                return 's';
                break;
            case 'm':
                return 'm';
                break;
            case 'h':
                return 'h';
                break;
            case 'd':
                return 'd';
                break;

            //data (IEC)
            case 'B':
                return 'bytes';
                break;
            case 'KB':
                return 'kbytes';
                break;
            case 'MB':
                return 'mbytes';
                break;
            case 'GB':
                return 'gbytes';
                break;

            //data rate
            case 'B/s':
                return 'Bps';
                break;
            case 'KB/s':
                return 'KBs';
                break;
            case 'MB/s':
                return 'MBs';
                break;
            case 'GB/s':
                return 'GBs';
                break;
            case 'kbits':
                return 'Kbits';
                break;
            case 'mbits':
                return 'Mbits';
                break;
            case 'gbits':
                return 'Gbits';
                break;

            // data throughput
            case 'ops':
                return 'ops';
                break;
            case 'iops':
                return 'iops';
                break;
            case 'rps':
                return 'rps';
                break;
            case 'wps':
                return 'wps';
                break;
            case 'rpm':
                return 'rpm';
                break;
            case 'wpm':
                return 'wpm';
                break;


            //energy
            case 'w':
            case 'watt':
            case 'watts':
                return 'watt';
                break;
            case 'kWh':
                return 'kwatth';
                break;
            case 'Wh':
                return 'watth';
                break;
            case 'J':
                return 'joule';
                break;
            case 'V':
                return 'volt';
                break;
            case 'kV':
                return 'kvolt';
                break;
            case 'A':
                return 'amp';
                break;
            case 'kA':
                return 'kamp';
                break;

            //temperature
            case 'C':
            case 'celsius':
            case '°C':
                return 'celsius';
                break;
            case 'F':
            case 'farenheit':
            case '°F':
                return 'farenheit';
                break;
            case 'K':
            case 'kelvin':
            case '°K':
                return 'kelvin';
                break;

            //pressure
            case 'mbar':
                return 'pressurembar';
                break;
            case 'bar':
                return 'pressurebar';
                break;
            case 'kbar':
                return 'pressurekbar';
                break;
            case 'hPa':
                return 'pressurehpa';
                break;
            case 'psi':
                return 'pressurepsi';
                break;


            //none
            case 'decibel':
            case 'dB':
                return 'dB';
                break;
            case '%':
            case '%%':
                return 'percent';
                break;

            default:
                return 'none';
                break;

        }
    }

}

