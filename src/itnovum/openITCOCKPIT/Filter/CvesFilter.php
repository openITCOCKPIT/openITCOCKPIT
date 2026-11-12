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

namespace itnovum\openITCOCKPIT\Filter;

/**
 * Filters of the CVE pages (CvesController), one method per action.
 *
 * The frontend sends the filters as filter[<field>], e.g. filter[Hosts.name]=web or filter[CveHostFindings.color][]=red.
 */
class CvesFilter extends Filter {

    /**
     * Host list
     *
     * @return array
     */
    public function indexFilter() {
        $filters = [
            'like_or_rlike'  => [
                'Hosts.name',
            ],
            'equals'         => [
                'Hosts.id',
                'CveHostSummaries.status',
                'CveHostSummaries.color',
                'CveHostSummaries.platform',
            ],
            // e.g. filter[CveHostSummaries.kev_count]=1 for hosts with at least one CVE of the CISA KEV catalog
            'greater_equals' => [
                'CveHostSummaries.red_count',
                'CveHostSummaries.action_count',
                'CveHostSummaries.kev_count',
                'CveHostSummaries.exploit_count',
                'CveHostSummaries.open_count',
                'CveHostSummaries.in_progress_count',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Components (package and version) of one host and one view
     *
     * @return array
     */
    public function hostComponentsFilter() {
        $filters = [
            'like'   => [
                'CveHostFindings.component_name',
                'CveHostFindings.component_version',
            ],
            'equals' => [
                'CveHostFindings.component_type',
                'CveHostFindings.color',
            ],
            'bool'   => [
                'CveHostFindings.is_kernel',
                'CveHostFindings.update_available',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Findings of one host, also of one component
     *
     * @return array
     */
    public function hostFindingsFilter() {
        $filters = [
            'like'           => [
                'Cves.cve_id',
                'Cves.summary',
            ],
            'equals'         => [
                'CveHostFindings.color',
                'CveHostFindings.rule',
                'CveHostFindings.severity',
                'CveDecisions.state',
            ],
            'bool'           => [
                'CveHostFindings.update_available',
                'CveHostFindings.not_fixed_yet',
                'CveHostFindings.review_required',
                'Cves.kev_cisa',
            ],
            'greater_equals' => [
                'Cves.cvss_score',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Open CVEs across all hosts
     *
     * @return array
     */
    public function cvesFilter() {
        $filters = [
            'like'           => [
                'Cves.cve_id',
                'Cves.summary',
            ],
            'equals'         => [
                'CveHostFindings.color',
                'CveHostFindings.severity',
                'CveHostFindings.component_type',
            ],
            'bool'           => [
                'Cves.kev_cisa',
                'Cves.kev_vulncheck',
            ],
            'greater_equals' => [
                'Cves.cvss_score',
                'Cves.metasploit_count',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Hosts affected by one CVE
     *
     * @return array
     */
    public function viewFilter() {
        $filters = [
            'like_or_rlike' => [
                'Hosts.name',
            ],
            'like'          => [
                'CveHostFindings.component_name',
            ],
            'equals'        => [
                'CveHostFindings.color',
                'CveDecisions.state',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Recommendations across all hosts
     *
     * @return array
     */
    public function recommendationsFilter() {
        $filters = [
            'like'   => [
                'CveHostFindings.component_name',
            ],
            'equals' => [
                'CveHostFindings.component_type',
                'CveHostFindings.color',
            ],
            'bool'   => [
                'CveHostFindings.is_kernel',
                'CveHostFindings.update_available',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Affected software across all hosts, also the hosts of one component
     *
     * @return array
     */
    public function softwareFilter() {
        $filters = [
            'like'   => [
                'CveHostFindings.component_name',
                'CveHostFindings.component_version',
            ],
            'equals' => [
                'CveHostFindings.component_type',
            ],
            'bool'   => [
                'CveHostFindings.is_kernel',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }

    /**
     * Decisions, e.g. filter[CveDecisions.review_after]=2026-12-31 for all decisions due until the end of the year
     *
     * @return array
     */
    public function decisionsFilter() {
        $filters = [
            'like'          => [
                'CveDecisions.owner',
                'CveDecisions.reason',
                'Cves.cve_id',
                'CveHostFindings.component_name',
            ],
            'like_or_rlike' => [
                'Hosts.name',
            ],
            'equals'        => [
                'CveDecisions.state',
                'CveHostFindings.host_id',
            ],
            'lesser_equals' => [
                'CveDecisions.review_after',
            ],
        ];

        return $this->getConditionsByFilters($filters);
    }
}
