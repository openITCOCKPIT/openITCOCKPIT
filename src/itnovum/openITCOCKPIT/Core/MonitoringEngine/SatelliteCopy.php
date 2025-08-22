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

namespace App\itnovum\openITCOCKPIT\Core\MonitoringEngine;


use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use itnovum\openITCOCKPIT\CakePHP\Folder;
use Cake\Log\Log;

/**
 * Class SatelliteCopy
 * @package App\itnovum\openITCOCKPIT\Core\MonitoringEngine
 *
 * @deprecated
 * Configuration Sync is now done by the NSTA wirtten in Go!
 */
class SatelliteCopy {

    /**
     * @var array
     */
    private $satellite;

    /**
     * @var array
     */
    private $config;

    /**
     * @var ConsoleIo|null
     */
    private $io;

    public function __construct(array $satellite, ?ConsoleIo $io = null) {
        $this->satellite = $satellite;

        Configure::load('after_export');
        Configure::load('nagios');
        $this->config = Configure::read('after_export');
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function copy(): bool {
        try {
            if (!$this->checkPort($this->satellite['address'])) {
                throw new \Exception('Checking port failed!');
            }

            $this->out('Connect to ' . $this->satellite['name'] . ' (' . $this->satellite['address'] . ':' . $this->config['SSH']['port'] . ')', 'info');

            //Creat SFTP Ressource
            if (!is_dir(Configure::read('nagios.export.satellite_path') . $this->satellite['id'])) {
                throw new \Exception('No derectory in nagios.export.satellite_path was found!');
            }

            //Delete target on remote host
            $this->out('Delete old monitoring configuration', 'info', 0);
            $result = $this->execOverSsh('rm -rf ' . $this->config['REMOTE']['path'] . '/config');
            if ($result['returnCode'] > 0) {
                $this->out('Error: ' . $result['output'], 'error');
            } else {
                $this->out('    ok', 'success');
            }


            //Copy new files
            $this->out('Copy new configuration via rsync', 'default', 0);

            $commandArgs = [
                $this->config['SSH']['private_key'],
                Configure::read('nagios.export.satellite_path') . $this->satellite['id'],
                $this->config['SSH']['username'],
                $this->satellite['address'],
                $this->config['REMOTE']['path'],
            ];
            $commandTemplate = "rsync -e 'ssh -C -ax -i %s -o StrictHostKeyChecking=no' -avm --timeout=10 --delete %s/* %s@%s:%s";
            $command = vsprintf($commandTemplate, $commandArgs);
            exec($command, $output, $returnCode);
            if ($returnCode != 0) {
                throw new \Exception(sprintf('Failed executing "%s"', $commandTemplate));
            }
            $this->out('    ok', 'success');


            //Restart remote monitoring engine
            $this->out('Restart remote monitoring engine', 'default', 0);
            $result = $this->execOverSsh($this->config['SSH']['restart_command']);
            if ($result['returnCode'] != 0) {
                throw new \Exception(sprintf('Failed to restart monitoring engine'));
            }
            $this->out('    ok', 'success');

            //Execute remote commands - if any
            foreach ($this->config['SSH']['remote_command'] as $remoteCommand) {
                $this->out('Execute external command ' . $remoteCommand, 'default', 0);
                $result = $this->execOverSsh($remoteCommand);
                $this->out('    ok', 'success');
            }

            return true;
        } catch (\Exception $ex) {
            $this->out('Rsync failed for Satellite ' . $this->satellite['address'] . ': ' . $ex->getMessage(), 'error');
        }
        return false;
    }

    /**
     * @param $address
     * @return bool
     */
    private function checkPort(string $address): bool {
        if (!@fsockopen('tcp://' . $address, $this->config['SSH']['port'], $errorNo, $errorStr, 35)) {
            Log::error($errorStr);
            return false;
        }

        return true;
    }

    /**
     * @param $command
     * @return array
     */
    private function execOverSsh($command) {
        //Do not use PHP SSH2 anymore - it's crap...

        $output = [];
        exec(
            sprintf(
                'ssh -l %s -i %s -o StrictHostKeyChecking=no %s "%s"',
                escapeshellarg($this->config['SSH']['username']),
                escapeshellarg($this->config['SSH']['private_key']),
                escapeshellarg($this->satellite['address']),
                $command
            ),
            $output,
            $returnCode
        );

        return [
            'output'     => implode("\n", $output),
            'returnCode' => $returnCode
        ];
    }

    /**
     * @param string $msg
     * @param string $type
     * @param int $newlines
     */
    private function out(string $msg, string $type, int $newlines = 1) {
        if ($this->io !== null) {
            switch ($type) {
                case 'error':
                    $this->io->error($msg);
                    Log::error('AfterExport: ' . $msg, $newlines);
                    break;

                case 'success':
                    $this->io->success($msg, $newlines);
                    break;

                default:
                    $this->io->out($msg, $newlines);
                    break;
            }
        }
    }


}
