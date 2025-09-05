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


use Cake\Utility\Hash;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class GraphingDocker extends ConfigGenerator implements ConfigInterface {

    protected $templateDir = 'graphing';

    protected $template = 'docker-compose.yml';

    protected $linkedOutfile = '/opt/openitc/docker/container/graphing/docker-compose.yml';

    protected $realOutfile = '/opt/openitc/docker/container/graphing/docker-compose.yml';

    /**
     * @var string
     */
    protected $commentChar = '#';

    protected $defaults = [
        'string' => [
            'carbon_path'                   => '/var/lib/graphite/whisper',
            'carbon_storage_schema'         => '60s:365d',
            'timezone'                      => 'Europe/Berlin',
            'USE_AUTO_NETWORKING'           => '1', //String for AngularJs - sorry,
            //'bip'                   => '',
            //'fixed_cidr'            => '',
            'docker_compose_subnet'         => '',
            'victoria_metrics_storage_path' => '/opt/openitc/var/prometheus/victoria-metrics'
        ],
        'int'    => [
            'number_of_carbon_cache_instances'  => 2,
            'number_of_carbon_c_relay_workers'  => 4,
            'local_graphite_http_port'          => 8888,
            'local_graphite_plaintext_port'     => 2003,
            'victoria_metrics_retention_period' => 1,
            'local_victoria_metrics_http_port'  => 8428
        ],
        'float'  => [
            'default_average_x_files_factor' => 0.5,
        ],
        'bool'   => [
            'WHISPER_FALLOCATE_CREATE'     => 1,
            'enable_docker_userland_proxy' => 0
        ],
    ];

    protected $dbKey = 'GraphingDocker';

    /**
     * @param $data
     * @return array|bool
     */
    public function validate($data) {
        $error = [];
        $fakeModelName = 'Configfile';

        $Validator = new ConfigValidator();

        $fieldsThatCanBeEmpty = [
            //'bip',
            //'fixed_cidr',
            'docker_compose_subnet'
        ];

        foreach ($this->defaults as $type => $fields) {
            foreach ($fields as $field => $defaultValue) {
                if (!isset($data[$type][$field])) {
                    if ($data['string']['USE_AUTO_NETWORKING'] == '1') {
                        if (in_array($field, $fieldsThatCanBeEmpty, true)) {
                            continue 2;
                        }
                    }


                    $error[$fakeModelName][$field][] = __('This field cannot be left blank.');
                }

                $value = $data[$type][$field];

                switch ($type) {
                    case 'float':
                        if (!$Validator->assertFloat($value)) {
                            $error[$fakeModelName][$field][] = __('This field needs to be a float.');
                        }
                        break;

                    case 'int':
                        if ($data['string']['USE_AUTO_NETWORKING'] == '1') {
                            if (in_array($field, $fieldsThatCanBeEmpty, true)) {
                                continue 2;
                            }
                        }

                        if (!$Validator->assertInt($value)) {
                            $error[$fakeModelName][$field][] = __('This field needs to be an integer.');
                        }
                        break;

                    case 'bool':
                        if (!$Validator->assertBool($value)) {
                            $error[$fakeModelName][$field][] = __('This field needs to be a boolean.');
                        }
                        break;

                    case 'string':
                        if ($data['string']['USE_AUTO_NETWORKING'] == '1') {
                            if (in_array($field, $fieldsThatCanBeEmpty, true)) {
                                continue 2;
                            }
                        }

                        if (!$Validator->assertStringNotEmpty($value)) {
                            $error[$fakeModelName][$field][] = __('This field can not left be blank.');
                        }
                        break;

                    default:
                        break;
                }
            }
        }

        $customResult = $this->customValidationRules($data);
        if (is_array($customResult) && !empty($customResult)) {
            $error = Hash::merge($error, $customResult);
        }

        if (empty($error)) {
            return true;
        }
        $this->validationErrors = $error;
        return false;
    }

    /**
     * @param array $data
     * @return array|bool|true
     */
    public function customValidationRules($data) {
        $error = [];

        if ($data['string']['USE_AUTO_NETWORKING'] == '0') {

            //Remove bip and fixed_cidr because restart issues of docker daemon
            //foreach (['bip', 'fixed_cidr', 'docker_compose_subnet'] as $field) {
            foreach (['docker_compose_subnet'] as $field) {
                if (isset($data['string'][$field])) {
                    //Parse 192.168.1.1/24
                    $bip = explode('/', $data['string'][$field]);
                    if (count($bip) !== 2) {
                        $error['Configfile'][$field][] = __('Value is not in CIDR notation');
                    } else {
                        $ip = $bip[0];
                        $subnet = $bip[1];

                        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                            $error['Configfile'][$field][] = __('Value is not a valid IPv4 address in CIDR notation');
                        }

                        if (!is_numeric($subnet)) {
                            $error['Configfile'][$field][] = __('Subnet needs to be numeric.');
                        }

                        if ($subnet < 0 || $subnet > 32) {
                            $error['Configfile'][$field][] = __('Value is not a valid IPv4 address in CIDR notation. Subnet is out of range.');
                        }
                    }
                }
            }


            if (isset($data['string']['fixed_cidr']) && isset($data['string']['docker_compose_subnet'])) {
                if ($data['string']['fixed_cidr'] === $data['string']['docker_compose_subnet']) {
                    $error['Configfile']['docker_compose_subnet'][] = __('Can not be the same subnet as used for Fixed CIDR');
                }
            }

            return $error;
        }

        if (isset($data['float']['default_average_x_files_factor'])) {
            if ($data['float']['default_average_x_files_factor'] < 0 || $data['float']['default_average_x_files_factor'] > 1) {
                $error['Configfile']['default_average_x_files_factor'][] = __('Value needs to be between 0 and 1, e.g. 0.5');
                return $error;
            }
        }

        return true;
    }

    /**
     * @param $requestData
     * @return array
     */
    public function convertRequestForSaveAll($requestData) {
        $records = [];

        $fieldsToRemove = [
            //'bip',
            //'fixed_cidr',
            'docker_compose_subnet'
        ];

        foreach ($requestData as $type => $fields) {
            foreach ($fields as $key => $value) {

                if ($requestData['string']['USE_AUTO_NETWORKING'] == '1') {
                    if (in_array($key, $fieldsToRemove, true)) {
                        continue;
                    }
                }

                $records[] = [
                    'ConfigurationFile' => [
                        'config_file' => $this->getDbKey(),
                        'key'         => $key,
                        'value'       => $value
                    ]
                ];
            }
        }

        return $records;
    }

    /**
     * @return string
     */
    public function getAngularDirective() {
        return 'graphing-docker-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'carbon_path'                       => __('Path where Carbon will store whisper files.'),
            'carbon_storage_schema'             => __('Carbon storage schema'),
            'number_of_carbon_cache_instances'  => __('Number of carbon cache instances for multi core CPU scaling'),
            'number_of_carbon_c_relay_workers'  => __('Number of Carbon-C-Relay worker threads. (Carbon-Cache load balancer)'),
            'local_graphite_http_port'          => __('Local HTTP port used by Graphite-Web'),
            'local_graphite_plaintext_port'     => __('Local plaintext port to send metrics to Carbon-C-Relay.'),
            'WHISPER_FALLOCATE_CREATE'          => __(' Only beneficial on linux filesystems that support the fallocate system call. It maintains the benefits of contiguous reads/writes, but with a potentially much faster creation speed, by allowing the kernel to handle the block allocation and zero-ing. Enabling this option may allow a large increase of MAX_CREATES_PER_MINUTE. If enabled on an OS or filesystem that is unsupported this option will gracefully fallback to standard POSIX file access methods.'),
            'timezone'                          => __('Set your local timezone for Graphite-Web. (Django\'s default is America/Chicago) If your graphs appear to be offset by a couple hours then this probably needs to be explicitly set to your local timezone. Set this value to the same timezone, as your servers timezone is!'),
            'USE_AUTO_NETWORKING'               => __('Determine if docker daemon will automatically configure network interface docker0'),
            //'bip'                              => __('Supply a specific IP address and netmask for the docker0 bridge, using standard CIDR notation. For example: 10.253.253.1/24'),
            //'fixed_cidr'                       => __('Restrict the IP range from the docker0 subnet, using standard CIDR notation. For example: 10.253.253.0/24. Needs to be a subnet, where BIP is part of.'),
            'docker_compose_subnet'             => __('Subnet used by Docker Compose in CIDR notation. For Example  192.168.1.0/24. Needs to be a different subnet than used for Fixed CIDR!'),
            'victoria_metrics_storage_path'     => __('Path used by VictoriaMetrics to store data.'),
            'victoria_metrics_retention_period' => __('Period in month how long VictoriaMetrics will keep stored metrics.'),
            'local_victoria_metrics_http_port'  => __('Local HTTP port used by VictoriaMetrics.'),
            'enable_docker_userland_proxy'      => __('Enable the Docker userland proxy. Warning: This could lead to very high CPU usage.'),
            'default_average_x_files_factor'    => __('xFilesFactor should be a floating point number between 0 and 1, and specifies what fraction of the previous retention level\'s slots must have non-null values in order to aggregate to a non-null value. The default is 0.5.')
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
        //This method needs to write multiple configuration files!
        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);
        $configToExport = [];
        foreach ($config as $type => $fields) {
            foreach ($fields as $key => $value) {
                $configToExport[$key] = $value;
            }
        }

        if (!is_dir('/opt/openitc/etc/carbon') || !is_dir('/opt/openitc/etc/grafana')) {
            return false;
        }

        $configDir = dirname($this->linkedOutfile);
        if (!is_dir($configDir)) {
            return false;
        }

        $FileHeader = new FileHeader();
        $configToExport['STATIC_FILE_HEADER'] = $FileHeader->getHeader($this->commentChar);
        $success = true;

        /*
         * Write:
         * - carbon-c-relay.conf
         * - carbon.conf
         * - storage-schemas.conf
         * - storage-aggregation.conf
         * - local_settings.py
         * - wsgi.py
         * - grafana.ini
         * - docker-compose.yml
         */
        $loader = new FilesystemLoader([
            $this->getTemplatePath(),
            $this->getTemplatePath() . DS . 'carbon-c-relay',
            $this->getTemplatePath() . DS . 'carbon-cache',
            $this->getTemplatePath() . DS . 'grafana',
            $this->getTemplatePath() . DS . 'graphite-web'
        ]);
        $twig = new Environment($loader, ['debug' => true]);
        $configToExport['CarbonCaches'] = [];
        for ($i = 1; $i <= $configToExport['number_of_carbon_cache_instances']; $i++) {
            $instanceName = $i;
            if ($i < 10) {
                $instanceName = sprintf('0%s', $i);
            }
            $hostname = sprintf('carbon-cache%s', $instanceName);

            $configToExport['CarbonCaches'][] = [
                'instanceName' => $instanceName,
                'hostname'     => $hostname,
                'port'         => 2003
            ];
        }

        $configToExport['WHISPER_FALLOCATE_CREATE'] = $this->asUcfirstBoolString($config['bool']['WHISPER_FALLOCATE_CREATE']);

        //docker-compose.yml
        if (!file_put_contents($this->linkedOutfile, $twig->render($this->getTemplateName(), $configToExport))) {
            $success = false;
        }

        //carbon-c-relay.conf
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/carbon/carbon-c-relay.conf', '/opt/openitc/etc/carbon/carbon-c-relay.conf');
        if (!file_put_contents('/opt/openitc/etc/carbon/carbon-c-relay.conf', $twig->render('carbon-c-relay.conf', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        //carbon.conf
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/carbon/carbon.conf', '/opt/openitc/etc/carbon/carbon.conf');
        if (!file_put_contents('/opt/openitc/etc/carbon/carbon.conf', $twig->render('carbon.conf', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        //storage-schemas.conf
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/carbon/storage-schemas.conf', '/opt/openitc/etc/carbon/storage-schemas.conf');
        if (!file_put_contents('/opt/openitc/etc/carbon/storage-schemas.conf', $twig->render('storage-schemas.conf', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        //storage-aggregation.conf
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/carbon/storage-aggregation.conf', '/opt/openitc/etc/carbon/storage-aggregation.conf');
        if (!file_put_contents('/opt/openitc/etc/carbon/storage-aggregation.conf', $twig->render('storage-aggregation.conf', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        //local_settings.py (graphite web)
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/carbon/local_settings.py', '/opt/openitc/etc/carbon/local_settings.py');
        if (!file_put_contents('/opt/openitc/etc/carbon/local_settings.py', $twig->render('local_settings.py', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        //wsgi.py (graphite web)
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/carbon/wsgi.py', '/opt/openitc/etc/carbon/wsgi.py');
        if (!file_put_contents('/opt/openitc/etc/carbon/wsgi.py', $twig->render('wsgi.py', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        //grafana.ini
        $ConfigSymlink = new ConfigSymlink('/opt/openitc/etc/grafana/grafana.ini', '/opt/openitc/etc/grafana/grafana.ini');
        if (!file_put_contents('/opt/openitc/etc/grafana/grafana.ini', $twig->render('grafana.ini', $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();

        ///etc/docker/daemon.json

        $json = [];

        //Load contents of /etc/docker/daemon.json if file exists
        if (file_exists('/etc/docker/daemon.json') && is_file('/etc/docker/daemon.json')) {
            $json = json_decode(file_get_contents('/etc/docker/daemon.json'), true);
        }

        $json['userland-proxy'] = (int)$config['bool']['enable_docker_userland_proxy'] === 1;

        $file = fopen('/etc/docker/daemon.json', 'w+');
        if (is_resource($file)) {
            fwrite($file, json_encode($json, JSON_PRETTY_PRINT));
            fclose($file);
        }

        /*
        if ($config['string']['USE_AUTO_NETWORKING'] === '0') {
            $json = [];

            //Load contants of /etc/docker/daemon.json if file exists
            if (file_exists('/etc/docker/daemon.json') && is_file('/etc/docker/daemon.json')) {
                $json = json_decode(file_get_contents('/etc/docker/daemon.json'), true);
            }

            $json['bip'] = $config['string']['bip'];
            $json['fixed-cidr'] = $config['string']['fixed_cidr'];

            $file = fopen('/etc/docker/daemon.json', 'w+');
            if (is_resource($file)) {
                fwrite($file, json_encode($json, JSON_PRETTY_PRINT));
                fclose($file);
            }
        }

        if ($config['string']['USE_AUTO_NETWORKING'] === '1') {
            $json = [];

            //Load contants of /etc/docker/daemon.json if file exists
            if (file_exists('/etc/docker/daemon.json') && is_file('/etc/docker/daemon.json')) {
                $json = json_decode(file_get_contents('/etc/docker/daemon.json'), true);
            }

            //Remove IP Address settings to restore docker default
            if(isset($json['bip'])){
                unset($json['bip']);
            }

            if(isset($json['fixed-cidr'])){
                unset($json['fixed-cidr']);
            }

            if(empty($json)){
                //Config is empty now... Remove it
                unlink('/etc/docker/daemon.json');
            }else{
                //Save rest of docker.json
                $file = fopen('/etc/docker/daemon.json', 'w+');
                if (is_resource($file)) {
                    fwrite($file, json_encode($json, JSON_PRETTY_PRINT));
                    fclose($file);
                }
            }
        }
        */

        return $success;
    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        //No migration for this configs

        $config = $this->mergeDbResultWithDefaultConfiguration($dbRecords);

        ///etc/docker/daemon.json

        if (file_exists('/etc/docker/daemon.json') && is_file('/etc/docker/daemon.json')) {
            $json = json_decode(file_get_contents('/etc/docker/daemon.json'), true);

            if (array_key_exists('userland-proxy', $json)) {
                $config['bool']['enable_docker_userland_proxy'] = $json['userland-proxy'] === true;
            }

        }

        /*
        if (file_exists('/etc/docker/daemon.json') && is_file('/etc/docker/daemon.json')) {
            $json = json_decode(file_get_contents('/etc/docker/daemon.json'), true);

            if (isset($json['bip']) && isset($json['fixed-cidr'])) {
                $config['string']['USE_AUTO_NETWORKING'] = 0;
                $config['string']['bip'] = $json['bip'];
                $config['string']['fixed_cidr'] = $json['fixed-cidr'];
            }

        }
        */

        if (file_exists($this->linkedOutfile)) {
            try {
                $yamlConfig = Yaml::parseFile($this->linkedOutfile);
                if (isset($yamlConfig['networks']['default']['ipam']['config'][0]['subnet'])) {
                    $config['string']['USE_AUTO_NETWORKING'] = 0;
                    $config['string']['docker_compose_subnet'] = $yamlConfig['networks']['default']['ipam']['config'][0]['subnet'];
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
                echo PHP_EOL;
            }
        }

        return $config;

    }

}
