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

declare(strict_types=1);

namespace App\Controller;

use itnovum\openITCOCKPIT\ApiShell\Exceptions\MissingParameterExceptions;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class BackupsController extends AppController {

    public function index() {
        $backup_files = $this->getBackupFiles();

        $this->set(compact('backup_files'));
        $this->viewBuilder()->setOption('serialize', ['backup_files']);
    }

    public function backup() {
        if (!$this->isJsonRequest()) {
            return;
        }
        if (empty($this->request->getData('filename'))) {
            $error = [
                'filename' => [
                    '_empty' => 'This field cannot be left empty'
                ]
            ];
            $this->set('error', $error);
            $this->viewBuilder()->setOption('serialize', ['error']);
            $this->response = $this->response->withStatus(400);
            return;
        }

        $filenameForBackup = $this->request->getData('filename') . "_" . date("Y-m-d_His") . ".sql";
        if (preg_match('/^[a-zA-Z0-9_\-]*$/', $this->request->getData('filename'))) {
            $error = false;
            $backupRunning = true;
        } else {
            $error = true;
            $backupRunning = false;
        }

        if (!$error) {
            $GearmanClient = new Gearman();
            $GearmanClient->sendBackground("make_sql_backup", ['filename' => $filenameForBackup]);
        }

        $backup = [
            'backupRunning' => $backupRunning,
            'error'         => $error,
        ];

        $this->set('backup', $backup);
        $this->viewBuilder()->setOption('serialize', ['backup']);
    }

    public function restore() {
        if (!$this->isJsonRequest()) {
            return;
        }

        if (empty($this->request->getData('backupfile'))) {
            $error = [
                'backupfile' => [
                    '_empty' => 'This field cannot be left empty'
                ]
            ];
            $this->set('error', $error);
            $this->viewBuilder()->setOption('serialize', ['error']);
            $this->response = $this->response->withStatus(400);
            return;
        }

        $pathForRestore = $this->request->getData('backupfile');
        $GearmanClient = new Gearman();
        $GearmanClient->sendBackground("restore_sql_backup", ['path' => $pathForRestore]);
        $backup = [
            'backupRunning' => true,
        ];
        $this->set('backup', $backup);
        $this->viewBuilder()->setOption('serialize', ['backup']);
    }

    public function checkBackupFinished() {
        if (!$this->isJsonRequest()) {
            return;
        }

        $backup_files = $this->getBackupFiles();
        $GearmanClient = new Gearman();
        $backupFinished = [];
        $finished = false;
        $error = false;
        $fileBackup = "/opt/openitc/nagios/backup/finishBackup.txt.sql";
        $fileRestore = "/opt/openitc/nagios/backup/finishRestore.txt.sql";
        if (file_exists($fileBackup)) {
            $finished = true;
            $error = false;
            $GearmanClient->send("delete_sql_backup", ['path' => $fileBackup]);
        } else if (file_exists($fileRestore)) {
            $finished = true;
            $error = false;
            $GearmanClient->send("delete_sql_backup", ['path' => $fileRestore]);
        } else {
            $finished = false;
            $error = false;
        }

        $backupFinished = [
            'finished'     => $finished,
            'error'        => $error,
            'backup_files' => $backup_files,
        ];

        $this->set('backupFinished', $backupFinished);
        $this->viewBuilder()->setOption('serialize', ['backupFinished']);
    }

    public function deleteBackupFile() {
        if (!$this->isJsonRequest()) {
            return;
        }
        if (empty($this->request->getData('filename'))) {
            throw new MissingParameterExceptions('filename is missing');
        }

        $fileToDelete = $this->request->getData('filename');

        $GearmanClient = new Gearman();
        $result = $GearmanClient->send("delete_sql_backup", ['path' => $fileToDelete]);

        $backup_files = $this->getBackupFiles();

        $deleteFinished = [
            'result'       => $result,
            'backup_files' => $backup_files,
        ];

        $this->set('deleteFinished', $deleteFinished);
        $this->viewBuilder()->setOption('serialize', ['deleteFinished']);
    }

    public function downloadBackupFile() {
        if (empty($this->request->getQuery('filename'))) {
            throw new MissingParameterExceptions('filename is missing');
        }
        $this->autoRender = false;

        $backup_files = $this->getBackupFiles();
        $dl_file = null;

        foreach ($backup_files as $key => $backup_file) {
            if ($backup_file === $this->request->getQuery('filename')) {
                $dl_file = $key;
            }
        }
        if ($dl_file === null) {
            throw new FileNotFoundException();
        }

        $this->response->setTypeMap('sql', 'application/octet-stream');
        $this->response->withType('sql');
        $response = $this->response->withFile($dl_file, ['download' => true, 'name' => $this->request->getQuery('filename')]);
        return $response;
    }

    private function getBackupFiles() {
        $backup_files = [];
        $files = scandir("/opt/openitc/nagios/backup/");
        foreach ($files as $file) {
            if (strstr($file, ".sql") && !strstr($file, ".txt.sql")) {
                $backup_files["/opt/openitc/nagios/backup/" . $file] = $file;
            }
        }
        return $backup_files;
    }
}
