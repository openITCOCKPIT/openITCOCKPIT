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

namespace itnovum\openITCOCKPIT\ConfigGenerator;


use App\itnovum\openITCOCKPIT\ConfigGenerator\ContainerConfigInterface;
use Cake\Core\Configure;

class PerfdataBackend extends ConfigGenerator implements ConfigInterface,ContainerConfigInterface {

    protected $templateDir = 'config';

    protected $template = 'perfdatabackend.php.tpl';


    /**
     * @var string
     */
    protected $linkedOutfile = CONFIG . 'perfdatabackend.php';

    /**
     * @var string
     */
    protected $realOutfile = CONFIG . 'perfdatabackend.php';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'string' => [
            'perfdatabackend' => 'Whisper'
        ]
    ];

    protected $dbKey = 'PerfdataBackend';


    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        $error = [];
        $fakeModelName = 'Configfile';
        if (isset($data['string']) && is_array($data['string'])) {
            foreach ($data['string'] as $field => $value) {
                if ($field === 'perfdatabackend') {
                    if (!in_array($value, ['Whisper', 'Crate', 'Rrdtool'], true)) {
                        $error[$fakeModelName][$field][] = __('Value out of range (Whisper, Crate, Rrdtool)');
                    }
                }
            }
        }

        if (!empty($error)) {
            return $error;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'perfdata-backend-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'dbbackend' => __('Time-series backend used by openITCOCKPIT to store performance data. Be careful with this option!'),
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
    }

    public function getValuesFromEnvironment() {
        return [
            [
                'key'   => 'perfdatabackend',
                'value' => env('OITC_PERFDATA_BACKEND', 'Whisper'),
            ]
        ];
    }

    /**
     * Save the configuration as text file on disk
     *
     * @param array $dbRecords
     * @return bool|int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function writeToFile($dbRecords) {
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);
        $configToExport = [];
        foreach ($config as $type => $fields) {
            foreach ($fields as $key => $value) {
                $configToExport[$key] = $value;
            }
        }

        return $this->saveConfigFile($configToExport);
    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        return $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        /*
        //No migration for PerfdataBackend
        if (!file_exists($this->linkedOutfile)) {
            return false;
        }
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        Configure::load('perfdatabackend');
        $configFromFile = Configure::read('perfdatabackend');

        if ($config['string']['perfdatabackend'] != $configFromFile) {
            $config['string']['perfdatabackend'] = $configFromFile;
        }

        return $config;
        */
    }

}
