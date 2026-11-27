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

namespace itnovum\openITCOCKPIT\Filter;

/**
 * Filter for the central report mail log (report_mail_log).
 * Used by the mail log endpoints of Instant Reports, Autoreports, SLA Reports, ...
 */
class ReportMailLogFilter extends Filter {

    /**
     * @return array
     */
    public function indexFilter() {
        $filters = [
            'like' => [
                'ReportMailLog.recipients',
                'ReportMailLog.subject'
            ],
            'bool' => [
                'ReportMailLog.has_been_sent'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Filter for the global report mail log (Logs -> Report Mail Log) over all report types
     *
     * @return array
     */
    public function globalIndexFilter() {
        $filters = [
            'like'   => [
                'ReportMailLog.report_name'
            ],
            'equals' => [
                'ReportMailLog.report_type'
            ]
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Start of the time range (server time). Default: 30 days ago
     *
     * @return int
     */
    public function getFrom() {
        if ($this->queryHasField('from')) {
            $value = strtotime($this->getQueryFieldValue('from'));
            if ($value) {
                return $this->toServerTime($value);
            }
        }
        return time() - (3600 * 24 * 30);
    }

    /**
     * End of the time range (server time). Default: now
     *
     * @return int
     */
    public function getTo() {
        if ($this->queryHasField('to')) {
            $value = strtotime($this->getQueryFieldValue('to'));
            if ($value) {
                return $this->toServerTime($value);
            }
        }
        return time();
    }
}
