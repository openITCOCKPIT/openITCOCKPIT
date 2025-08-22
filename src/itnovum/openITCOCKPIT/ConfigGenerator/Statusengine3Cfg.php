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


use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Statusengine3Cfg extends ConfigGenerator implements ConfigInterface {

    protected $templateDir = 'statusengine3';

    protected $template = 'config.yml.tpl';

    protected $realOutfile = '/opt/openitc/statusengine3/worker/etc/config.yml';

    protected $linkedOutfile = '/opt/openitc/etc/statusengine/config.yml';

    /**
     * @var string
     */
    protected $commentChar = '//';

    protected $defaults = [
        'int' => [
            'number_servicestatus_worker' => 1,
            'number_hoststatus_worker'    => 1,
            'number_hostcheck_worker'     => 1,
            'number_servicecheck_worker'  => 1,
            'number_perfdata_worker'      => 1,

            'number_of_bulk_records' => 500,
            'max_bulk_delay'         => 1,

            'graphite_port' => 2003
        ],

        'string' => [
            'graphite_address' => '127.0.0.1',
            'graphite_prefix'  => 'openitcockpit'
        ]
    ];

    protected $dbKey = 'Statusengine3Cfg';

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
        return 'statusengine3-cfg';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getHelpText($key) {
        $help = [
            'number_servicestatus_worker' => 'Number of Statusengine Worker processes which consume statusngin_servicestatus queue',
            'number_hoststatus_worker'    => 'Number of Statusengine Worker processes which consume statusngin_hoststatus queue',
            'number_hostcheck_worker'     => 'Number of Statusengine Worker processes which consume statusngin_hostchecks queue',
            'number_servicecheck_worker'  => 'Number of Statusengine Worker processes which consume statusngin_servicechecks queue',
            'number_perfdata_worker'      => 'Number of Statusengine Worker processes which consume statusngin_servicechecks queue (Statusengine 3)',
            'number_of_bulk_records'      => 'Number of SQL operations per bunch',
            'max_bulk_delay'              => 'Time in seconds Statusengine will wait to reach number_of_bulk_records',
            'graphite_port'               => 'Carbon cache port number',
            'graphite_address'            => 'Carbon cache server address',
            'graphite_prefix'             => 'Prefix added to every metric stored in carbon (Required for Grafana)'
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

        $success = true;

        $FileHeader = new FileHeader();
        $configToExport['STATIC_FILE_HEADER'] = $FileHeader->getHeader($this->commentChar);

        $mcp = new \App\itnovum\openITCOCKPIT\Database\MysqlConfigFileParser();
        $ini_file = $mcp->parse_mysql_cnf('/opt/openitc/etc/mysql/mysql.cnf');

        $configToExport['mysql_host'] = $ini_file['host'];
        $configToExport['mysql_user'] = $ini_file['user'];
        $configToExport['mysql_password'] = $ini_file['password'];
        $configToExport['mysql_database'] = $ini_file['database'];

        /*
         * Write:
         * - config.yml
         */
        $loader = new FilesystemLoader([
            $this->getTemplatePath()
        ]);
        $twig = new Environment($loader, ['debug' => true]);

        // /opt/openitc/statusengine3/worker/etc/config.yml
        $ConfigSymlink = new ConfigSymlink($this->realOutfile, $this->linkedOutfile);
        if (!file_put_contents($this->realOutfile, $twig->render($this->getTemplateName(), $configToExport))) {
            $success = false;
        }
        $ConfigSymlink->link();


        return $success;

    }

    /**
     * @param array $dbRecords
     * @return bool|array
     */
    public function migrate($dbRecords) {
        return $this->mergeDbResultWithDefaultConfiguration($dbRecords);
    }

}
