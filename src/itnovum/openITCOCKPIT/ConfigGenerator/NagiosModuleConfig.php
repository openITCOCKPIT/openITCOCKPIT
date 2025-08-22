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


use Cake\Core\Configure;

/**
 * Class NagiosModuleConfig
 * @package itnovum\openITCOCKPIT\ConfigGenerator
 * @deprecated With openITCOCKPIT 4.x not in use anymore !
 */
class NagiosModuleConfig extends ConfigGenerator implements ConfigInterface {

    protected $templateDir = 'config';

    protected $template = 'nagios_module_config.php.tpl';

    protected $linkedOutfile = '/etc/openitcockpit/app/Plugin/NagiosModule/Config/config.php';

    protected $realOutfile = '/var/lib/openitcockpit/etc/generated/app/Plugin/NagiosModule/Config/config.php';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'int' => [
            'SLIDER_MIN'      => 30,
            'SLIDER_MAX'      => 14400,
            'SLIDER_STEPSIZE' => 30,
        ]
    ];

    protected $dbKey = 'NagiosModuleConfig';

    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        return true;
    }

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'nagios-module-config';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'SLIDER_MIN'      => __('Minimum value of monitoring related interval settings for sliders in seconds.'),
            'SLIDER_MAX'      => __('Maximum value of monitoring related interval settings for sliders in seconds.'),
            'SLIDER_STEPSIZE' => __('Step size of sliders in seconds'),
        ];

        if (isset($help[$key])) {
            return $help[$key];
        }

        return '';
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
        if (!file_exists($this->linkedOutfile)) {
            return false;
        }
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        Configure::load('NagiosModule.config');
        $configFromFile = Configure::read('NagiosModule');

        foreach ($config['int'] as $field => $value) {
            if (isset($configFromFile[$field])) {
                if ($config['int'][$field] != $configFromFile[$field]) {
                    $config['int'][$field] = $configFromFile[$field];
                }
            }
        }

        return $config;
    }

}
