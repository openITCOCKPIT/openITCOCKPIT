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

namespace itnovum\openITCOCKPIT\Vuln\Api;

use DateTimeInterface;
use itnovum\openITCOCKPIT\Vuln\Policy\ActionAdvisor;
use itnovum\openITCOCKPIT\Vuln\Policy\Classification;
use itnovum\openITCOCKPIT\Vuln\Policy\Color;
use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingFacts;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingView;
use itnovum\openITCOCKPIT\Vuln\Policy\HostRules;
use itnovum\openITCOCKPIT\Vuln\Policy\Reason;
use itnovum\openITCOCKPIT\Vuln\Policy\Recommendation;
use itnovum\openITCOCKPIT\Vuln\Policy\Rule;

/**
 * Turns the rows of the CVE tables into the answers of the API (CvesController).
 *
 * The API returns codes, never texts: the frontend translates them. Numbers come as int, flags as bool, so the
 * frontend does not need to care that MySQL returns aggregates as strings. Everything that depends on the time
 * of the request (a stale host) or on the policy (the recommendation) is calculated here, not stored.
 */
class CveFormatter {

    private const SUMMARY_COUNTERS = [
        'critical_count',
        'high_count',
        'medium_count',
        'low_count',
        'unknown_count',
        'open_count',
        'red_count',
        'action_count',
        'kev_count',
        'exploit_count',
        'fixable_count',
        'accepted_count',
        'not_affected_count',
        'in_progress_count',
        'installations',
        'evaluated_installations',
    ];

    /** Fields of the grouping queries in CveHostFindingsTable */
    private const AGGREGATE_INTS = [
        'id',
        'host_id',
        'cve_count',
        'red_count',
        'kev_count',
        'exploit_count',
        'in_progress_count',
        'action_count',
        'host_count',
        'action_host_count',
        'red_host_count',
        'fixable_host_count',
        'host_update_count',
        'finding_count',
        'version_count',
        'lowest_rule_rank',
        'metasploit_count',
        'verified_exploit_count',
        'poc_exploit_count',
    ];

    private const AGGREGATE_BOOLS = [
        'is_kernel',
        'update_available',
        'usable_update',
        'kev_cisa',
        'kev_vulncheck',
    ];

    private HostRules $HostRules;

    private ActionAdvisor $ActionAdvisor;

    public function __construct(?HostRules $HostRules = null, ?ActionAdvisor $ActionAdvisor = null) {
        $this->HostRules = $HostRules ?? new HostRules();
        $this->ActionAdvisor = $ActionAdvisor ?? new ActionAdvisor();
    }

    /**
     * A row of cve_host_summaries with its host, plus the state at the time of the request:
     * stale data turns the host gray, see HostRules::state().
     *
     * @param array $summary Row of CveHostSummariesTable
     * @param DateTimeInterface|null $feedDate Date of the current Vuls database, null if unknown
     * @param DateTimeInterface $now
     * @return array
     */
    public function host(array $summary, ?DateTimeInterface $feedDate, DateTimeInterface $now): array {
        // Internal, only used to skip unchanged hosts
        unset($summary['fingerprint']);

        foreach (self::SUMMARY_COUNTERS as $field) {
            if (array_key_exists($field, $summary)) {
                $summary[$field] = (int)$summary[$field];
            }
        }
        foreach (['id', 'host_id'] as $field) {
            if (array_key_exists($field, $summary)) {
                $summary[$field] = (int)$summary[$field];
            }
        }

        $inventoryLastUpdate = $summary['inventory_last_update'] ?? null;
        $state = $this->HostRules->state(
            EvaluationStatus::tryFrom((string)$summary['status']) ?? EvaluationStatus::Failed,
            Color::tryFrom((string)$summary['color']) ?? Color::Gray,
            $inventoryLastUpdate instanceof DateTimeInterface ? $inventoryLastUpdate : null,
            $feedDate,
            $now
        );
        $summary['state'] = [
            'status'             => $state->status->value,
            'color'              => $state->color->value,
            'sort_order'         => $state->sortOrder,
            'inventory_age_days' => $state->inventoryAgeDays,
        ];

        return $summary;
    }

    /**
     * A finding with its CVE, host and decision, plus the reasons as codes and the recommendation.
     *
     * @param array $finding Row of CveHostFindingsTable::getFindingsOfHost() and the like
     * @return array
     */
    public function finding(array $finding): array {
        foreach (['id', 'host_id', 'cve_id', 'reasons', 'rule_rank', 'reopen_count'] as $field) {
            if (array_key_exists($field, $finding)) {
                $finding[$field] = (int)$finding[$field];
            }
        }
        foreach (['is_kernel', 'not_fixed_yet', 'update_available', 'review_required'] as $field) {
            if (array_key_exists($field, $finding)) {
                $finding[$field] = (bool)$finding[$field];
            }
        }
        $finding['is_running'] = $finding['is_running'] === null ? null : (bool)$finding['is_running'];
        $finding['severity'] = $finding['severity'] === null ? null : (int)$finding['severity'];

        $cve = $finding['cve'] ?? [];
        foreach (['id', 'metasploit_count', 'verified_exploit_count', 'poc_exploit_count'] as $field) {
            if (array_key_exists($field, $cve)) {
                $cve[$field] = (int)$cve[$field];
            }
        }
        foreach (['kev_cisa', 'kev_vulncheck'] as $field) {
            if (array_key_exists($field, $cve)) {
                $cve[$field] = (bool)$cve[$field];
            }
        }
        if (isset($cve['cvss_score'])) {
            $cve['cvss_score'] = (float)$cve['cvss_score'];
        }
        $finding['cve'] = $cve;

        $finding['reason_codes'] = self::reasonCodes((int)$finding['reasons']);
        $finding['recommendation'] = $this->findingRecommendation($finding, $cve);

        // The hasOne association is called cve_decision, the API calls it decision
        $finding['decision'] = $this->decision($finding['cve_decision'] ?? null);
        unset($finding['cve_decision']);

        return $finding;
    }

    /**
     * A component of one host (CveHostFindingsTable::getComponentsOfHost()), plus the recommendation.
     *
     * @param array $component
     * @param string $viewName The view the component is shown in
     * @return array
     */
    public function hostComponent(array $component, string $viewName): array {
        $component = $this->aggregate($component);
        $component['is_running'] = $component['is_running'] === null ? null : (bool)$component['is_running'];

        $recommendation = $this->ActionAdvisor->forHostComponent(
            FindingView::tryFrom($viewName) ?? FindingView::Action,
            (string)$component['component_type'],
            $component['is_kernel'],
            $component['is_running'],
            $component['usable_update'],
            (string)($component['update_version'] ?? '')
        );
        $component['recommendation'] = self::recommendation($recommendation);

        return $component;
    }

    /**
     * A recommendation across hosts (CveHostFindingsTable::getRecommendations()).
     *
     * @param array $row
     * @return array
     */
    public function recommendationRow(array $row): array {
        $row = $this->aggregate($row);

        $recommendation = $this->ActionAdvisor->forComponent((string)$row['component_type'], $row['is_kernel'], $row['usable_update']);
        $result = self::recommendation($recommendation);
        // The target version only makes sense if the update fixes something
        $result['version'] = $row['usable_update'] && !empty($row['update_version']) ? (string)$row['update_version'] : null;
        $row['recommendation'] = $result;

        return $row;
    }

    /**
     * Any row of the grouping queries: CVEs, software, hosts of a component.
     *
     * @param array $row
     * @return array
     */
    public function aggregate(array $row): array {
        foreach (self::AGGREGATE_INTS as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = (int)$row[$field];
            }
        }
        foreach (self::AGGREGATE_BOOLS as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = (bool)$row[$field];
            }
        }
        if (array_key_exists('highest_severity', $row)) {
            $row['highest_severity'] = $row['highest_severity'] === null ? null : (int)$row['highest_severity'];
        }
        if (array_key_exists('is_running', $row) && $row['is_running'] !== null) {
            $row['is_running'] = (bool)$row['is_running'];
        }
        if (isset($row['cvss_score'])) {
            $row['cvss_score'] = (float)$row['cvss_score'];
        }

        return $row;
    }

    /**
     * A decision as the API returns it, with the name of the user who made it.
     *
     * @param array|null $decision Contained CveDecisions row
     * @return array|null
     */
    public function decision(?array $decision): ?array {
        if (empty($decision)) {
            return null;
        }

        foreach (['id', 'cve_host_finding_id', 'user_id'] as $field) {
            if (array_key_exists($field, $decision) && $decision[$field] !== null) {
                $decision[$field] = (int)$decision[$field];
            }
        }
        if (array_key_exists('severity', $decision)) {
            $decision['severity'] = $decision['severity'] === null ? null : (int)$decision['severity'];
        }
        if (array_key_exists('not_fixed_yet', $decision)) {
            $decision['not_fixed_yet'] = (bool)$decision['not_fixed_yet'];
        }

        $user = $decision['user'] ?? null;
        $decision['user'] = empty($user) ? null : [
            'id'   => (int)$user['id'],
            'name' => trim($user['firstname'] . ' ' . $user['lastname']),
        ];

        return $decision;
    }

    /**
     * @param int $reasons Reason::* bits
     * @return string[] e.g. ['high_priority', 'fix_known']
     */
    public static function reasonCodes(int $reasons): array {
        return array_map('strtolower', Reason::toNames($reasons));
    }

    /**
     * @param Recommendation $recommendation
     * @return array{action: string, version: string|null}
     */
    public static function recommendation(Recommendation $recommendation): array {
        return [
            'action'  => $recommendation->action->value,
            'version' => $recommendation->version,
        ];
    }

    /**
     * @param array $finding
     * @param array $cve
     * @return array{action: string, version: string|null}|null null if the rule is unknown, e.g. of an old policy
     */
    private function findingRecommendation(array $finding, array $cve): ?array {
        $rule = Rule::tryFrom((string)($finding['rule'] ?? ''));
        if ($rule === null) {
            return null;
        }

        // FindingFacts expects the signals of the CVE next to the fields of the finding
        $facts = FindingFacts::fromRow($finding + [
                'kev_cisa'               => $cve['kev_cisa'] ?? false,
                'kev_vulncheck'          => $cve['kev_vulncheck'] ?? false,
                'metasploit_count'       => $cve['metasploit_count'] ?? 0,
                'verified_exploit_count' => $cve['verified_exploit_count'] ?? 0,
                'poc_exploit_count'      => $cve['poc_exploit_count'] ?? 0,
            ]);

        return self::recommendation($this->ActionAdvisor->forFinding($facts, new Classification($rule, (int)$finding['reasons'])));
    }
}
