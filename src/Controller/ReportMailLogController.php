<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\ReportMailLog;
use App\Model\Table\ReportMailLogTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ReportMailLogFilter;

/**
 * Global log of all report emails (Instant Reports, Autoreports, SLA Reports, ...)
 * Menu: Logs -> Report Mail Log
 *
 * Class ReportMailLogController
 * @package App\Controller
 */
class ReportMailLogController extends AppController {

    public function index() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var ReportMailLogTable $ReportMailLogTable */
        $ReportMailLogTable = TableRegistry::getTableLocator()->get('ReportMailLog');

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        $ReportMailLogFilter = new ReportMailLogFilter($this->request, $UserTime);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $ReportMailLogFilter->getPage());

        $MY_RIGHTS = [];
        if ($this->hasRootPrivileges === false) {
            $MY_RIGHTS = $this->MY_RIGHTS;
        }

        $mailLog = $ReportMailLogTable->getMailLogIndex($ReportMailLogFilter, $PaginateOMat, $MY_RIGHTS);
        $mailLog = $ReportMailLogTable->prepareRecipientsForApi($mailLog, $MY_RIGHTS);

        // Check with one query per report type, which reports still exist.
        // Only existing reports in a writable container get linked in the frontend.
        $reportIdsByType = [];
        foreach ($mailLog as $logEntry) {
            $reportIdsByType[$logEntry['report_type']][] = (int)$logEntry['report_id'];
        }
        $existingReportIdsByType = $ReportMailLogTable->getExistingReportIdsByType($reportIdsByType);

        $reportTypes = $ReportMailLogTable->getReportTypes();

        foreach ($mailLog as $index => $logEntry) {
            $reportType = $logEntry['report_type'];
            $reportExists = in_array((int)$logEntry['report_id'], $existingReportIdsByType[$reportType] ?? [], true);

            $mailLog[$index]['report_type_name'] = $reportTypes[$reportType]['name'] ?? $reportType;
            $mailLog[$index]['report_exists'] = $reportExists;
            $mailLog[$index]['allowEdit'] = $reportExists && $this->isWritableContainer((int)$logEntry['container_id']);
            $mailLog[$index]['send_time'] = $UserTime->format($logEntry['send_time']);
            $mailLog[$index]['has_been_sent'] = (int)$logEntry['has_been_sent'] === ReportMailLog::SENT;
        }

        // Report types for the filter (only installed modules)
        $reportTypesForFilter = [];
        foreach ($reportTypes as $reportType => $config) {
            $reportTypesForFilter[] = [
                'key'  => $reportType,
                'name' => $config['name']
            ];
        }

        $this->set('mailLog', $mailLog);
        $this->set('reportTypes', $reportTypesForFilter);

        $toJson = ['mailLog', 'reportTypes', 'paging'];
        if ($this->isScrollRequest()) {
            $toJson = ['mailLog', 'reportTypes', 'scroll'];
        }
        $this->viewBuilder()->setOption('serialize', $toJson);
    }
}
