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

namespace App\Lib;

use App\Model\Table\HosttemplatesTable;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use itnovum\openITCOCKPIT\CakePHP\Folder;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\UUID;

/**
 * Class DebugConfigNagiosTask
 * @package App\Lib
 */
class DebugConfigNagiosTask {

    private $conf = [];
    private $monitoringLog = '';
    private $Constants = [];
    private $uuidCache = [];

    /**
     * @var ConsoleIo
     */
    private $io;

    private $tablesToSeach = [
        'Hosts'               => [
            'fields' => ['id', 'uuid', 'name'],
        ],
        'Hosttemplates'       => [
            'fields' => ['id', 'uuid', 'name'],
        ],
        'Timeperiods'         => [
            'fields' => ['id', 'uuid', 'name'],
        ],
        'Commands'            => [
            'fields' => ['id', 'uuid', 'name'],
        ],
        'Contacts'            => [
            'fields' => ['id', 'uuid', 'name'],
        ],
        'Contactgroups'       => [
            'fields'           => ['id', 'uuid', 'container_id'],
            'containertype_id' => CT_CONTACTGROUP,
            'contain'          => [
                'Containers' => [
                    'fields' => [
                        'name'
                    ]
                ]
            ]
        ],
        'Hostgroups'          => [
            'fields'           => ['id', 'uuid', 'container_id'],
            'containertype_id' => CT_HOSTGROUP,
            'contain'          => [
                'Containers' => [
                    'fields' => [
                        'name'
                    ]
                ]
            ]
        ],
        'Servicegroups'       => [
            'fields'           => ['id', 'uuid', 'container_id'],
            'containertype_id' => CT_SERVICEGROUP,
            'contain'          => [
                'Containers' => [
                    'fields' => [
                        'name'
                    ]
                ]
            ]
        ],
        'Services'            => [
            'fields'  => ['id', 'uuid', 'name'],
            'contain' => [
                'Servicetemplates' => [
                    'fields' => [
                        'name'
                    ]
                ]
            ]
        ],
        'Servicetemplates'    => [
            'fields' => ['id', 'uuid', 'name'],
        ],
        'Hostescalations'     => [
            'fields'  => ['id', 'uuid', 'container_id'],
            'contain' => [
                'Hostgroups' => [
                    'Containers' => [
                        'fields' => [
                            'name'
                        ]
                    ]
                ]
            ]
        ],
        'Serviceescalations'  => [
            'fields'  => ['id', 'uuid', 'container_id'],
            'contain' => [
                'Servicegroups' => [
                    'Containers' => [
                        'fields' => [
                            'name'
                        ]
                    ]
                ]
            ]
        ],
        'Hostdependencies'    => [
            'fields' => ['id', 'uuid', 'container_id'],
        ],
        'Servicedependencies' => [
            'fields' => ['id', 'uuid', 'container_id']
        ]
    ];

    /**
     * DebugConfigNagiosTask constructor.
     * @param ConsoleIo $io
     */
    public function __construct(ConsoleIo $io) {
        $this->io = $io;
    }

    public function execute() {
        /** @var HosttemplatesTable $HosttemplatesTable */
        $HosttemplatesTable = TableRegistry::getTableLocator()->get('Hosttemplates');
        debug($HosttemplatesTable->find()->all());
    }

    public function setup($conf = []) {
        $this->conf = $conf;
        $this->monitoringLog = Configure::read('nagios.logfilepath') . Configure::read('nagios.logfilename');
        $this->Constants = new Constants();
        $this->_buildUuidCache();
        $this->searchUuids();
    }

    private function _buildUuidCache() {
        $this->uuidCache = [];
        foreach ($this->tablesToSeach as $TableName => $searchDefinition) {
            /** @var Table $Table */
            $Table = TableRegistry::getTableLocator()->get($TableName);

            $query = $Table->find()
                ->select($this->tablesToSeach[$TableName]['fields']);

            if (isset($this->tablesToSeach[$TableName]['contain']) && !empty($this->tablesToSeach[$TableName]['contain'])) {
                $containForQuery = [];
                foreach (array_keys($this->tablesToSeach[$TableName]['contain']) as $tableName) {
                    $containForQuery[] = $tableName;
                    if (isset($this->tablesToSeach[$TableName]['contain'][$tableName]['fields']) && !empty($this->tablesToSeach[$TableName]['contain'][$tableName]['fields'])) {
                        $fields = $this->tablesToSeach[$TableName]['contain'][$tableName]['fields'];
                        $containForQuery[$tableName] = function (Query $query) use ($fields) {
                            return $query->select($fields);
                        };
                    }
                }
                $query->contain($containForQuery);
            }

            $result = $query->disableHydration()->all();

            if (!empty($result)) {
                $result = $result->toArray();
            }

            foreach ($result as $entry) {
                if ($TableName == 'Services') {
                    if ($entry['name'] == null || $entry['name'] == '') {
                        if (isset($entry['servicetemplate']) && isset($entry['servicetemplate']['name'])) {
                            $entry['name'] = $entry['servicetemplate']['name'];
                        }
                    }
                }
                if (isset($entry['container']['name'])) {
                    $entry['container_name'] = $entry['container']['name'];
                }

                $entry['TableName'] = $TableName;

                if (!isset($entry['uuid'])) {
                    debug($entry);
                }

                $this->uuidCache[$entry['uuid']] = $entry;
            }
        }
    }

    /**
     * @param null|string $TableName
     * @param $confName
     */
    public function debug(?string $TableName, string $confName) {
        if ($TableName !== null && is_array($this->tablesToSeach) && isset($this->tablesToSeach[$TableName])) {

            /** @var Table $Table */
            $Table = TableRegistry::getTableLocator()->get($TableName);
            $ModelSchema = $Table->getSchema()->columns();

            if (in_array('name', array_keys($ModelSchema), true)) {
                $input = $this->io->ask(__d('oitc_console', 'Please enter the name of the ' . $TableName . '! This is a wildcard search, for example type "default host" or just "def". Hit return to see all ' . $TableName));
                $result = $Table->find()->where([
                    'name LIKE' => '%' . $input . '%',
                ])->all();
            } else if (in_array('container_id', array_keys($ModelSchema), true) && isset($this->tablesToSeach[$TableName]['containertype_id'])) {
                $input = $this->io->ask(__d('oitc_console', 'Please enter the name of the ' . $TableName . '! This is a wildcard search, for example type "default host" or just "def". Hit return to see all ' . $TableName));
                $result = $Table->find()->contain(['Containers'])->where([
                    'Container.name LIKE'        => '%' . $input . '%',
                    'Container.containertype_id' => $this->tablesToSeach[$TableName]['containertype_id'],
                ])->all();
            } else {
                $this->io->out('<error>' . __d('oitc_console', 'No name field for ' . $TableName . ' found in database!') . '</error>');
                $result = $Table->find()->all();
            }

            if (!empty($result)) {
                $result = $result->toArray();
            }

            if (sizeof($result) > 1) {
                if (isset($input)) {
                    $this->io->out(__d('oitc_console', 'I found ' . sizeof($result) . ' results matching to "' . $input . '". Please select one ' . $TableName . ' by typing the number in square brackets'));
                } else {
                    $this->io->out(__d('oitc_console', 'Please select one ' . $TableName . ' by typing the number in square brackets'));
                }
                foreach ($result as $r) {
                    if (isset($r['name'])) {
                        $this->io->out('[' . $r['id'] . '] ' . $r['name']);
                    } else if (isset($r['Container']['name'])) {
                        $this->io->out('[' . $r['id'] . '] ' . $r['Container']['name']);
                    } else {
                        $this->io->out('[' . $r['id'] . '] ' . $r['uuid']);
                    }
                }
                $input = $this->io->ask(__d('oitc_console', 'Your choice please'));
                if ($Table->exists($input)) {
                    $result = [];
                    $result[0] = $Table->find()->where([
                        'id' => $input
                    ])->firstOrFail();
                    $this->_outFile($result[0]['uuid'], $TableName, $confName);
                } else {
                    $this->io->out('<error>' . $TableName . __d('oitc_console', ' not found') . '</error>');
                }
            } else {
                if (sizeof($result) == 1) {
                    $this->_outFile($result[0]['uuid'], $TableName, $confName);
                } else {
                    $this->io->out('<error>No object matching given conditions</error>');
                }
            }

        } else {
            echo "Unknown Model !";
            exit();
        }
    }

    public function debugByUuid($uuid = null) {
        if ($uuid === null) {
            $uuid = $this->io->ask(__d('oitc_console', 'Please enter your UUID'));
        }

        $result = $this->searchByUuid($uuid);
        if (!empty($result)) {
            $this->_outFile($uuid, $result['TableName'], strtolower($result['TableName']));
        } else {
            $this->io->out('<error>' . __d('oitc_console', 'No result for given UUID') . '</error>');
        }
    }

    public function parseMonitoringLogfile() {
        foreach ($this->tail() as $line) {
            $timestamp = null;
            preg_match('#\d{10,11}#', $line, $timestamp);
            if (isset($timestamp[0]) && is_numeric($timestamp[0])) {
                $this->io->out(preg_replace('#\d{10,11}#', '<comment>' . date('d.m.Y - H:i:s', $timestamp[0]) . '</comment>', $line));
            } else {
                $this->io->out($line);
            }
        }
    }

    private function tail($lines = 100) {
        $return = [];
        $file = fopen($this->monitoringLog, "r");
        $flen = filesize($this->monitoringLog);
        if ($flen % 4096 != 0) {
            $flen = ((int)($flen / 4096) + 1) * 4096;
        }

        $newlines = 0;
        $startpos = -1;

        for ($goto = 0; $goto <= $flen - 4096; $goto += 4096) {
            fseek($file, $flen - $goto - 4096);
            $data = fread($file, 4096);
            if ($data === false)
                continue;
            for ($i = strlen($data) - 1; $i >= 0; $i--) {
                if ($data[$i] == "\n") {
                    $newlines++;
                    $startpos = $flen - $goto - 4096 + $i + 1;
                    if ($newlines > $lines)
                        break 2;
                }
            }
        }

        if ($startpos != -1) {
            fseek($file, $startpos);
            $fileContent = fread($file, $flen - $startpos);
            $return[] = $this->searchUuids($fileContent);
        }
        $ret = [];
        foreach ($return as $r) {
            $ret = explode("\n", $r);
        }

        return $ret;
    }

    public function tailf() {
        $callback = function ($timestamp) {
            return '<comment>' . date('d.m.Y - H:i:s', $timestamp[0]) . '</comment>';
        };

        $options = [
            'cwd' => Configure::read('nagios.logfilepath'),
            'env' => [
                'LANG'     => 'C',
                'LANGUAGE' => 'en_US.UTF-8',
                'LC_ALL'   => 'C',
                'PATH'     => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
            ],
        ];

        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "r"],
        ];

        $process = proc_open('/usr/bin/tail -f -n 100 ' . Configure::read('nagios.logfilename'), $descriptorspec, $pipes, $options['cwd'], $options['env']);
        while (true) {
            $status = proc_get_status($process);
            if ($status['running'] != 1) {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                break;
            }

            $string = fgets($pipes[1], 1024);
            $string = preg_replace_callback('#\d{10,11}#', $callback, $string);
            $string = $this->searchUuids($string);
            $this->io->out($string, 0);
        }
    }

    private function _outFile($uuid, $TableName, $confName) {
        if (!$this->conf['minified'] || in_array($TableName, ['Hostdependencies', 'Hostescalations', 'Servicedependencies', 'Serviceescalations'])) {
            // Model that are not saved as minified config files or minified configs asre tournd off
            $file = new \SplFileInfo($this->conf['path'] . $this->conf[$confName] . $uuid . $this->conf['suffix']);
            if ($file->isFile() && $file->isReadable()) {
                $this->io->hr();
                $this->io->out('<warning>File: ' . $this->conf['path'] . $this->conf[$confName] . $uuid . $this->conf['suffix'] . '</warning>');
                $this->io->out('<info>' . __d('oitc_console', 'Notice: This is not the real nagios configuration file. This is a human readable version of the config.') . '</info>');
                $this->io->hr();
                $this->io->out($this->io->nl());
                $this->io->out($this->searchUuids($file->openFile()->fread($file->getSize())));
            } else {
                $this->io->out(__d('oitc_console', '<error>File not found! (' . $this->conf['path'] . $this->conf[$confName] . $uuid . $this->conf['suffix'] . ')</error>'));
            }
        } else {
            $configFolder = new Folder($this->conf['path'] . $this->conf[$confName]);
            $configFiles = $configFolder->find();

            // User want the file of an object, that is inside of an minified file, so we need to parse the minified config
            if (!empty($configFiles)) {
                foreach ($configFiles as $configFile) {
                    $fileAsArray = file($configFolder->pwd() . $configFile);

                    $tableToNagios = [
                        'Commands'         => 'command_name',
                        'Contactgroups'    => 'contactgroup_name',
                        'Contacts'         => 'contact_name',
                        'Hosts'            => 'host_name',
                        'Hostgroups'       => 'hostgroup_name',
                        'Hosttemplates'    => 'host_name',
                        'Services'         => 'service_description',
                        'Servicegroups'    => 'servicegroup_name',
                        'Servicetemplates' => 'service_description',
                        'Timeperiods'      => 'timeperiod_name'
                    ];

                    $needel = $tableToNagios[$TableName];

                    $configContent = [];
                    $breakForeach = false;
                    $state = 'SEARCH_FOR_DEFINITION';
                    foreach ($fileAsArray as $line) {
                        $configContent[] = $line;
                        switch ($state) {
                            case 'SEARCH_FOR_DEFINITION':
                                if (preg_match('/^define .*\{[\s\t]*$/', $line)) {
                                    $state = 'SEARCH_FOR_OBJECT_NAME';
                                }
                                break;

                            case 'SEARCH_FOR_OBJECT_NAME': //host_name, command_name, contacnt_name, etc
                                if (preg_match('/' . $needel . '/', $line)) {
                                    $check = explode($needel, $line);
                                    if (sizeof($check) == 2) {
                                        if (trim($check[1]) == $uuid) {
                                            //this is the object we search for!
                                            $state = 'SEARCH_FOR_END_OF_DEFENITION';
                                            break;
                                        }
                                    }
                                    //This is not the object we are searching for!
                                    $state = 'SEARCH_FOR_END_OF_DEFENITION_AND_CONTINUE';
                                }
                                break;

                            case 'SEARCH_FOR_END_OF_DEFENITION':
                                if (trim($line) == '}') {
                                    // We have the complet object now, so we can break out of switch and foreach
                                    $breakForeach = true;
                                    break;
                                }
                                break;

                            case 'SEARCH_FOR_END_OF_DEFENITION_AND_CONTINUE':
                                // this was the wrong object, so throw everyting away and continue with next definition
                                if (trim($line) == '}') {
                                    $configContent = [];
                                    $state = 'SEARCH_FOR_DEFINITION';
                                }
                                break;
                        }

                        if ($breakForeach === true) {
                            break;
                        }
                    }

                    if (!empty($configContent)) {
                        $this->io->hr();
                        $this->io->out(__('<warning>File: {0}</warning>', $configFolder->pwd() . $configFile));
                        $this->io->out('<info>' . __d('oitc_console', 'Notice: This is not the real nagios configuration file. This is a human readable version of the config.') . '</info>');
                        $this->io->hr();
                        foreach ($configContent as $line) {
                            $this->io->out($this->searchUuids($line), false);
                        }
                        //break config files foreach.
                        break;
                    }

                }
            } else {
                $this->io->out(__d('oitc_console', '<error>Folder %s is empty!</error>', $configFolder->pwd()));
            }

        }
    }

    public function searchUuids($string = '') {
        if (!isset($this->uuidCache) || empty($this->uuidCache) || !is_array($this->uuidCache)) {
            $this->_buildUuidCache();
        }

        $string = preg_replace_callback(UUID::regex(), [$this, '_replaceUuid'], $string);

        return $string;
    }

    public function searchByUuid($uuid = null) {
        if (isset($this->uuidCache[$uuid])) {
            return $this->uuidCache[$uuid];
        }

        return [];
    }

    private function _replaceUuid($matches) {
        foreach ($matches as $match) {
            if (isset($this->uuidCache[$match])) {
                //Checking if name exists or if we need to use the container:
                if (isset($this->uuidCache[$match]['name'])) {
                    return '<red_bold>' . $this->uuidCache[$match]['name'] . "</red_bold><comment>[" . $match . "]</comment>";
                } else if (isset($this->uuidCache[$match]['container_name'])) {
                    return '<red_bold>' . $this->uuidCache[$match]['container_name'] . "</red_bold><comment>[" . $match . "]</comment>";
                } else {
                    return "<error>name not found in DB</error><comment>[" . $match . "]</comment>";
                }

            }

            return "<error>object not found in UUID cache</error><comment>[" . $match . "]</comment>";
        }
    }

    /**
     * @param bool $replaceTimestamps
     * @return void
     */
    public function translateStdin($replaceTimestamps = false) {
        $ConsoleInput = new ConsoleInput();
        while ($ConsoleInput->dataAvailable()) {
            $result = $ConsoleInput->read();

            if ($result === null) {
                // End-of-file
                break;
            }

            if ($result !== false) {
                $line = $this->searchUuids($result);

                if ($replaceTimestamps === true) {
                    $timestamp = null;
                    preg_match('#\d{10,11}#', $line, $timestamp);
                    if (isset($timestamp[0]) && is_numeric($timestamp[0])) {
                        $line = preg_replace('#\d{10,11}#', '<comment>' . date('d.m.Y - H:i:s', $timestamp[0]) . '</comment>', $line);
                    }
                }

                $this->io->out($line);
            }
        }
    }

}
