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

namespace itnovum\openITCOCKPIT\Core\System\Health;


use App\itnovum\openITCOCKPIT\Supervisor\Binarydctl;
use Cake\Core\Configure;
use Cake\Log\Log;

class MonitoringEngine {

    private $monitoringEngine = null;

    private $MRTG = [
        'NAGIOSVERSION',
        'NAGIOSPID',
        'NUMHOSTS',
        'NUMSERVICES',
        'MINHSTPSC',
        'MAXHSTPSC',
        'AVGHSTPSC',
        'MINPSVHSTPSC',
        'MAXPSVHSTPSC',
        'AVGPSVHSTPSC',
        'MINPSVHSTLAT',
        'MAXPSVHSTLAT',
        'AVGPSVHSTLAT',
        'MINACTHSTPSC',
        'MAXACTHSTPSC',
        'AVGACTHSTPSC',
        'MINACTHSTEXT',
        'MAXACTHSTEXT',
        'AVGACTHSTEXT',
        'MINACTHSTLAT',
        'MAXACTHSTLAT',
        'AVGACTHSTLAT',
        'MINSVCPSC',
        'MAXSVCPSC',
        'AVGSVCPSC',
        'MINPSVSVCPSC',
        'MAXPSVSVCPSC',
        'AVGPSVSVCPSC',
        'MINACTSVCPSC',
        'MAXACTSVCPSC',
        'AVGACTSVCPSC',
        'MINPSVSVCLAT',
        'MAXPSVSVCLAT',
        'AVGPSVSVCLAT',
        'MINACTSVCEXT',
        'MAXACTSVCEXT',
        'AVGACTSVCEXT',
        'MINACTSVCLAT',
        'MAXACTSVCLAT',
        'AVGACTSVCLAT',
        'NUMEXTCMDS1M',
        'NUMEXTCMDS5M',
        'NUMEXTCMDS15M',
        'NUMPSVSVCCHECKS1M',
        'NUMPSVSVCCHECKS5M',
        'NUMPSVSVCCHECKS15M',
        'NUMSACTSVCCHECKS1M',
        'NUMSACTSVCCHECKS5M',
        'NUMSACTSVCCHECKS15M',
        'NUMCACHEDSVCCHECKS1M',
        'NUMCACHEDSVCCHECKS5M',
        'NUMCACHEDSVCCHECKS15M',
        'NUMOACTSVCCHECKS1M',
        'NUMOACTSVCCHECKS5M',
        'NUMOACTSVCCHECKS15M',
        'NUMACTSVCCHECKS1M',
        'NUMACTSVCCHECKS5M',
        'NUMACTSVCCHECKS15M',
        'NUMPSVHSTCHECKS1M',
        'NUMPSVHSTCHECKS5M',
        'NUMPSVHSTCHECKS15M',
        'NUMSERHSTCHECKS1M',
        'NUMSERHSTCHECKS5M',
        'NUMSERHSTCHECKS15M',
        'NUMPARHSTCHECKS1M',
        'NUMPARHSTCHECKS5M',
        'NUMPARHSTCHECKS15M',
        'NUMPARHSTCHECKS1M',
        'NUMPARHSTCHECKS5M',
        'NUMPARHSTCHECKS15M',
        'NUMSACTHSTCHECKS1M',
        'NUMSACTHSTCHECKS5M',
        'NUMSACTHSTCHECKS15M',
        'NUMCACHEDHSTCHECKS1M',
        'NUMCACHEDHSTCHECKS5M',
        'NUMCACHEDHSTCHECKS15M',
        'NUMOACTHSTCHECKS1M',
        'NUMOACTHSTCHECKS5M',
        'NUMOACTHSTCHECKS15M',
        'NUMACTHSTCHECKS1M',
        'NUMACTHSTCHECKS5M',
        'NUMACTHSTCHECKS15M',
        'NUMHSTACTCHK1M',
        'NUMHSTACTCHK5M',
        'NUMHSTACTCHK15M',
        'NUMHSTACTCHK60M',
        'NUMHSTPSVCHK1M',
        'NUMHSTPSVCHK5M',
        'NUMHSTPSVCHK15M',
        'NUMHSTPSVCHK60M',
        'NUMSVCACTCHK1M',
        'NUMSVCACTCHK5M',
        'NUMSVCACTCHK15M',
        'NUMSVCACTCHK60M',
        'NUMSVCPSVCHK1M',
        'NUMSVCPSVCHK5M',
        'NUMSVCPSVCHK15M',
        'NUMSVCPSVCHK60M',
        'NUMSVCCHECKED',
        'NUMHSTCHECKED',
        'PROGRUNTIME',
        'NUMHSTUP',
        'NUMHSTDOWN',
        'NUMHSTUNR',
        'NUMSVCOK',
        'NUMSVCWARN',
        'NUMSVCCRIT',
        'NUMSVCUNKN',
    ];

    private $delimiter = '|';

    public function __construct() {
        if (IS_CONTAINER) {
            // We always use Naemon for containers
            $this->monitoringEngine = 'Naemon Core Container';
            return;
        }

        Configure::load('nagios');
        exec(Configure::read('nagios.basepath') . Configure::read('nagios.bin') . Configure::read('nagios.nagios_bin') . ' --version | head -n 2', $output);

        // Naemon <= 1.4.2 (and Nagios) print a new line before the version string
        // Naemon >= 1.4.3 print the version string in the first line
        // https://github.com/naemon/naemon-core/commit/f12c87443cca4ce6214976e8059f256f09740187
        $this->monitoringEngine = $output[1] ?? $output[0];
    }

    /**
     * @return null
     */
    public function getMonitoringEngine() {
        return $this->monitoringEngine;
    }

    /**
     * @return bool
     */
    public function isNaemon() {
        if (IS_CONTAINER) {
            // We always use Naemon for containers
            return true;
        }
        if (empty($this->monitoringEngine)) {
            return false;
        }
        $monitoringEngine = strtolower($this->monitoringEngine);
        if (preg_match('/naemon/', $monitoringEngine)) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isNagios() {
        return !$this->isNaemon();
    }

    /**
     * @return string
     */
    public function getNagiostatsCommand() {
        return sprintf(
            '%s%s%s -c %s -D "%s" -m -d %s',
            Configure::read('nagios.basepath'),
            Configure::read('nagios.bin'),
            Configure::read('nagios.nagiostats'),
            Configure::read('nagios.nagios_cfg'),
            $this->delimiter,
            implode(',', $this->MRTG)
        );
    }

    /**
     * @return array|false
     */
    public function runNagiostats() {
        $output = [];
        if (IS_CONTAINER) {
            // Naemon is running inside a container - query remote supervisor to run "naemon -v naemon.cfg"
            try {
                $Binarydctl = new Binarydctl();
                $BinarydEndpoint = $Binarydctl->getBinarydApiEndpointByServiceName('naemon-stats');
                $result = $BinarydEndpoint->execute('naemon-stats');
                $output = [$result];
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        } else {
            // Local running Naemon
            exec($this->getNagiostatsCommand(), $output);
        }

        // Nagios and Naemon add the delimiter also to the end of the string
        // this is bad, because explode will create and empty value in the array
        // and this throw a warning in array_combine
        $result = explode($this->delimiter, $output[0]);
        $result_sizeof = sizeof($result);
        if (sizeof($this->MRTG) < $result_sizeof) {
            if (trim($result[$result_sizeof - 1]) == '') {
                unset($result[$result_sizeof - 1]);
            }
        }

        $result = array_combine($this->MRTG, $result);
        foreach ($result as $key => $value) {
            if (is_numeric($value)) {
                $result[$key] = (int)$value;
            }
        }
        return $result;
    }

}
