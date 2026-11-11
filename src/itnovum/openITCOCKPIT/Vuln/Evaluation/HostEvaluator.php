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

namespace itnovum\openITCOCKPIT\Vuln\Evaluation;

use App\Model\Entity\CveHostCoverage;
use App\Model\Table\CveDailyStatsTable;
use App\Model\Table\CveHostCoverageTable;
use App\Model\Table\CveHostFindingsTable;
use App\Model\Table\CveHostSummariesTable;
use App\Model\Table\CvePackageStateCvesTable;
use App\Model\Table\CvePackageStatesTable;
use App\Model\Table\CvesTable;
use App\Model\Table\MacosAppsHostsTable;
use App\Model\Table\PackagesLinuxHostsTable;
use App\Model\Table\WindowsAppsHostsTable;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxDistribution;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxFindingBuilder;
use itnovum\openITCOCKPIT\Vuln\Policy\Classification;
use itnovum\openITCOCKPIT\Vuln\Policy\CveRollup;
use itnovum\openITCOCKPIT\Vuln\Policy\Decision;
use itnovum\openITCOCKPIT\Vuln\Policy\DecisionRules;
use itnovum\openITCOCKPIT\Vuln\Policy\EvaluationStatus;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingClassifier;
use itnovum\openITCOCKPIT\Vuln\Policy\FindingFacts;
use itnovum\openITCOCKPIT\Vuln\Policy\HostRules;
use itnovum\openITCOCKPIT\Vuln\Policy\HostTotals;
use itnovum\openITCOCKPIT\Vuln\Policy\PolicyVersion;

/**
 * Evaluates hosts: turns the results of Vuls into findings, classifies them with the policy and stores
 * findings, summary, coverage and the values of the day.
 *
 * Two kinds of runs:
 * - evaluateLinuxHost(): new findings from inventory and package states. Skipped if the fingerprint is unchanged.
 * - reclassifyHost():    the stored findings get classified again, e.g. because a decision ends today.
 *                        Nothing is sent to Vuls and no finding gets added or resolved.
 */
class HostEvaluator {

    use LocatorAwareTrait;

    /** Result of evaluateLinuxHost(): the fingerprint is unchanged and the host has been classified today */
    public const RESULT_SKIPPED = 'skipped';

    /** Result of evaluateLinuxHost(): the fingerprint is unchanged, only the classification has been renewed */
    public const RESULT_RECLASSIFIED = 'reclassified';

    /** Result of evaluateLinuxHost(): new findings have been built */
    public const RESULT_EVALUATED = 'evaluated';

    private FindingClassifier $FindingClassifier;
    private DecisionRules $DecisionRules;
    private HostRules $HostRules;
    private LinuxFindingBuilder $LinuxFindingBuilder;

    public function __construct() {
        $this->FindingClassifier = new FindingClassifier();
        $this->DecisionRules = new DecisionRules();
        $this->HostRules = new HostRules();
        $this->LinuxFindingBuilder = new LinuxFindingBuilder();
    }

    /**
     * Evaluates one Linux host.
     *
     * @param array $host Row of PackagesHostDetailsTable::getLinuxHostsForCveCheck()
     * @param string $feedDigest Digest of the current Vuls database
     * @param bool $force Evaluate even if nothing has changed
     * @param DateTime $now
     * @return array{result: string, status: string|null, findings: int, new: int, reopened: int, resolved: int, open_cves: int, reasons: string[]}
     */
    public function evaluateLinuxHost(array $host, string $feedDigest, bool $force, DateTime $now): array {
        $hostId = (int)$host['host_id'];
        $osName = trim((string)$host['os_name']);
        $osVersion = trim((string)$host['os_version']);
        $kernelRelease = trim((string)($host['kernel_release'] ?? ''));

        /** @var PackagesLinuxHostsTable $PackagesLinuxHostsTable */
        $PackagesLinuxHostsTable = $this->fetchTable('PackagesLinuxHosts');
        /** @var CvePackageStatesTable $CvePackageStatesTable */
        $CvePackageStatesTable = $this->fetchTable('CvePackageStates');
        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = $this->fetchTable('CveHostSummaries');

        $installations = $PackagesLinuxHostsTable->getPackagesForCveCheck($hostId);
        $distribution = LinuxDistribution::fromInventory($osName, $osVersion);

        $statesById = [];
        $foreignApps = 0;
        if ($distribution === null) {
            $fingerprint = hash('sha256', implode("\n", [PolicyVersion::CURRENT, $feedDigest, $osName, $osVersion, count($installations)]));
        } else {
            $linkedStateIds = array_filter(array_column($installations, 'cve_package_state_id'));
            $statesById = $CvePackageStatesTable->getCheckStatusByIds(array_map('intval', $linkedStateIds));
            $foreignApps = $this->countForeignApps($hostId);
            $fingerprint = $this->LinuxFindingBuilder->getFingerprint($distribution, $kernelRelease, $feedDigest, $installations, $statesById, $foreignApps);
        }

        $lastEvaluation = $CveHostSummariesTable->getLastEvaluation($hostId);
        $isUnchanged = $lastEvaluation !== null
            && $lastEvaluation['fingerprint'] === $fingerprint
            && $lastEvaluation['status'] !== CveHostSummariesTable::STATUS_FAILED;

        if (!$force && $isUnchanged) {
            /** @var CveDailyStatsTable $CveDailyStatsTable */
            $CveDailyStatsTable = $this->fetchTable('CveDailyStats');
            // Classified today already: decisions that end today have been applied
            if ($CveDailyStatsTable->hasStatsOfDay($hostId, $this->getDay($now))) {
                return $this->result(self::RESULT_SKIPPED, $lastEvaluation['status']);
            }

            $totals = $this->reclassifyHost($hostId, $now);
            return $this->result(self::RESULT_RECLASSIFIED, $lastEvaluation['status'], ['open_cves' => $totals?->open ?? 0]);
        }

        if ($distribution === null) {
            $evaluation = HostEvaluation::notEvaluable(CoverageReason::UNSUPPORTED_DISTRIBUTION, $fingerprint, count($installations));
        } else {
            /** @var CvePackageStateCvesTable $CvePackageStateCvesTable */
            $CvePackageStateCvesTable = $this->fetchTable('CvePackageStateCves');
            $checkedStateIds = array_keys(array_filter($statesById, fn(array $state) => $state['checked'] !== null));

            $evaluation = $this->LinuxFindingBuilder->build(
                $distribution,
                $kernelRelease,
                $feedDigest,
                $installations,
                $statesById,
                $CvePackageStateCvesTable->getMatchesOfStates($checkedStateIds),
                $foreignApps
            );
        }

        return $this->saveEvaluation($hostId, 'linux', $evaluation, $feedDigest, $host['last_update'] ?? null, $now);
    }

    /**
     * Classifies the open findings of a host again and stores the new totals, e.g. because a decision ended,
     * a decision got added in the frontend or the policy changed. Findings are neither added nor resolved.
     *
     * @param int $hostId
     * @param DateTime $now
     * @return HostTotals|null null if the host has never been evaluated
     */
    public function reclassifyHost(int $hostId, DateTime $now): ?HostTotals {
        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = $this->fetchTable('CveHostSummaries');

        $lastEvaluation = $CveHostSummariesTable->getLastEvaluation($hostId);
        if ($lastEvaluation === null) {
            return null;
        }

        return $this->fetchTable('CveHostFindings')->getConnection()->transactional(function () use ($hostId, $now, $lastEvaluation, $CveHostSummariesTable) {
            $totals = $this->classifyOpenFindings($hostId, $now);
            $counters = $this->getCounters($totals);
            $color = $this->HostRules->color(EvaluationStatus::from($lastEvaluation['status']), $totals);

            $CveHostSummariesTable->saveClassificationTotals($hostId, $color->value, $counters);
            $this->saveDailyStats($hostId, $counters, 0, 0, $now);

            return $totals;
        });
    }

    /**
     * Deletes all CVE data of a host: findings, decisions, summary, coverage and the values per day.
     * Package states are shared by all hosts and get removed by CvePackageStatesTable::deleteUnusedStates().
     *
     * @param int $hostId
     * @return void
     */
    public function deleteHost(int $hostId): void {
        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = $this->fetchTable('CveHostFindings');

        $CveHostFindingsTable->getConnection()->transactional(function () use ($hostId, $CveHostFindingsTable) {
            $CveHostFindingsTable->deleteFindingsOfHost($hostId);
            $this->fetchTable('CveHostSummaries')->deleteSummaryOfHost($hostId);
            $this->fetchTable('CveHostCoverage')->deleteAll(['host_id' => $hostId]);
            $this->fetchTable('CveDailyStats')->deleteAll(['host_id' => $hostId]);
        });
    }

    /**
     * Stores the result of an evaluation in one transaction, so a failure keeps the last state of the host.
     *
     * @param int $hostId
     * @param string $platform linux, windows or macos
     * @param HostEvaluation $evaluation
     * @param string $feedDigest
     * @param DateTime|null $inventoryLastUpdate
     * @param DateTime $now
     * @return array See evaluateLinuxHost()
     */
    private function saveEvaluation(int $hostId, string $platform, HostEvaluation $evaluation, string $feedDigest, ?DateTime $inventoryLastUpdate, DateTime $now): array {
        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = $this->fetchTable('CveHostFindings');
        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = $this->fetchTable('CveHostSummaries');
        /** @var CveHostCoverageTable $CveHostCoverageTable */
        $CveHostCoverageTable = $this->fetchTable('CveHostCoverage');

        return $CveHostFindingsTable->getConnection()->transactional(function () use ($hostId, $platform, $evaluation, $feedDigest, $inventoryLastUpdate, $now, $CveHostFindingsTable, $CveHostSummariesTable, $CveHostCoverageTable) {
            $CveHostCoverageTable->replaceCoverageOfHost(
                $hostId,
                [CveHostCoverage::TYPE_REASON => $evaluation->reasons] + $evaluation->coverage
            );

            // Vuls failed for every package: keep findings and counters of the last successful evaluation
            if ($evaluation->status === EvaluationStatus::Failed) {
                $CveHostSummariesTable->saveFailure($hostId, $platform, implode(', ', $evaluation->reasons), $now);
                return $this->result(self::RESULT_EVALUATED, $evaluation->status->value, ['reasons' => $evaluation->reasons]);
            }

            // Nothing could be checked, e.g. the release is not in the Vuls database: findings stay as they are
            $saved = ['new' => 0, 'reopened' => 0, 'resolved' => 0, 'open' => 0];
            if ($evaluation->status !== EvaluationStatus::NotEvaluable) {
                $saved = $CveHostFindingsTable->saveFindingsOfHost(
                    $hostId,
                    $this->classifyNewFindings($hostId, $evaluation->findings, $now),
                    $evaluation->canResolve,
                    $now
                );
            }

            $totals = $this->classifyOpenFindings($hostId, $now);
            $counters = $this->getCounters($totals);

            $CveHostSummariesTable->saveSummary($hostId, [
                    'platform'                => $platform,
                    'status'                  => $evaluation->status->value,
                    'color'                   => $this->HostRules->color($evaluation->status, $totals)->value,
                    'fingerprint'             => $evaluation->fingerprint,
                    'feed_digest'             => $feedDigest,
                    'inventory_last_update'   => $inventoryLastUpdate,
                    'installations'           => $evaluation->installations,
                    'evaluated_installations' => $evaluation->evaluatedInstallations,
                ] + $counters, $now);

            $this->saveDailyStats($hostId, $counters, $saved['new'] + $saved['reopened'], $saved['resolved'], $now);

            return $this->result(self::RESULT_EVALUATED, $evaluation->status->value, [
                'findings'  => count($evaluation->findings),
                'new'       => $saved['new'],
                'reopened'  => $saved['reopened'],
                'resolved'  => $saved['resolved'],
                'open_cves' => $totals->open,
                'reasons'   => $evaluation->reasons,
            ]);
        });
    }

    /**
     * Adds the classification to the findings of a new evaluation. A finding that already exists keeps its decision.
     *
     * @param int $hostId
     * @param array<int, array> $findings
     * @param DateTime $now
     * @return array<int, array>
     */
    private function classifyNewFindings(int $hostId, array $findings, DateTime $now): array {
        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = $this->fetchTable('CveHostFindings');
        /** @var CvesTable $CvesTable */
        $CvesTable = $this->fetchTable('Cves');

        $signals = $CvesTable->getPolicySignalsByIds(array_column($findings, 'cve_id'));
        $decisions = $CveHostFindingsTable->getDecisionsOfHost($hostId);

        foreach ($findings as $index => $finding) {
            $hash = $CveHostFindingsTable->getFindingHash(
                $hostId,
                (int)$finding['cve_id'],
                (string)$finding['component_type'],
                (string)$finding['component_name'],
                (string)$finding['component_version']
            );

            $row = $finding + ($signals[(int)$finding['cve_id']] ?? []);
            [, $classification] = $this->classify($row, $decisions[$hash] ?? null, $now);
            $findings[$index] = $finding + $this->getClassificationFields($classification);
        }

        return $findings;
    }

    /**
     * Classifies all open findings of a host, stores the changed classifications and sums up the CVEs.
     *
     * @param int $hostId
     * @param DateTime $now
     * @return HostTotals
     */
    private function classifyOpenFindings(int $hostId, DateTime $now): HostTotals {
        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = $this->fetchTable('CveHostFindings');

        $rollups = [];
        $changed = [];
        foreach ($CveHostFindingsTable->getOpenFindingsOfHost($hostId) as $row) {
            [$facts, $classification] = $this->classify($row, $row['decision'], $now);

            $fields = $this->getClassificationFields($classification);
            if ($this->isClassificationChanged($row, $fields)) {
                $changed[(int)$row['id']] = $fields;
            }

            // A CVE in several packages counts once for the host
            $rollups[(int)$row['cve_id']] ??= new CveRollup();
            $rollups[(int)$row['cve_id']]->add($facts, $classification);
        }

        if (!empty($changed)) {
            $CveHostFindingsTable->saveClassifications($changed);
        }

        return HostTotals::fromRollups($rollups);
    }

    /**
     * Applies the policy to one finding: first the rules, then the decision of a human.
     *
     * @param array $row Finding with the signals of its CVE, see FindingFacts::fromRow()
     * @param array|null $decisionRow Row of cve_decisions, see Decision::fromRow()
     * @param DateTime $now
     * @return array{0: FindingFacts, 1: Classification}
     */
    private function classify(array $row, ?array $decisionRow, DateTime $now): array {
        $facts = FindingFacts::fromRow($row);
        $decision = $decisionRow === null ? null : Decision::fromRow($decisionRow);

        $classification = $this->DecisionRules->apply(
            $this->FindingClassifier->classify($facts),
            $facts,
            $decision,
            $now
        );

        return [$facts, $classification];
    }

    /**
     * @param Classification $classification
     * @return array{view_name: string, rule: string, reasons: int, color: string, rule_rank: int, review_required: bool, policy_version: string}
     */
    private function getClassificationFields(Classification $classification): array {
        return [
            'view_name'       => $classification->view()->value,
            'rule'            => $classification->rule->value,
            'reasons'         => $classification->reasons,
            'color'           => $classification->color()->value,
            'rule_rank'       => $classification->rank(),
            'review_required' => $classification->requiresReview(),
            'policy_version'  => PolicyVersion::CURRENT,
        ];
    }

    /**
     * @param array $row Stored finding
     * @param array $fields Result of getClassificationFields()
     * @return bool
     */
    private function isClassificationChanged(array $row, array $fields): bool {
        foreach ($fields as $field => $value) {
            if ((string)$row[$field] !== (string)$value) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param HostTotals $totals
     * @return array<string, int> Counters of cve_host_summaries, without the installations
     */
    private function getCounters(HostTotals $totals): array {
        return [
            'critical_count'     => $totals->critical,
            'high_count'         => $totals->high,
            'medium_count'       => $totals->medium,
            'low_count'          => $totals->low,
            'unknown_count'      => $totals->unknown,
            'open_count'         => $totals->open,
            'red_count'          => $totals->red,
            'action_count'       => $totals->action,
            'kev_count'          => $totals->kev,
            'exploit_count'      => $totals->exploit,
            'fixable_count'      => $totals->fixable,
            'accepted_count'     => $totals->accepted,
            'not_affected_count' => $totals->notAffected,
            'in_progress_count'  => $totals->inProgress,
        ];
    }

    /**
     * @param int $hostId
     * @param array<string, int> $counters
     * @param int $newFindings
     * @param int $resolvedFindings
     * @param DateTime $now
     * @return void
     */
    private function saveDailyStats(int $hostId, array $counters, int $newFindings, int $resolvedFindings, DateTime $now): void {
        /** @var CveDailyStatsTable $CveDailyStatsTable */
        $CveDailyStatsTable = $this->fetchTable('CveDailyStats');
        $CveDailyStatsTable->saveDailyStats($hostId, $this->getDay($now), $counters, $newFindings, $resolvedFindings);
    }

    /**
     * Day of the evaluation in the time zone of the server, as decisions end on a day
     *
     * @param DateTime $now
     * @return Date
     */
    private function getDay(DateTime $now): Date {
        return new Date($now->format('Y-m-d'));
    }

    /**
     * Windows or macOS applications in the inventory of a Linux host
     *
     * @param int $hostId
     * @return int
     */
    private function countForeignApps(int $hostId): int {
        /** @var WindowsAppsHostsTable $WindowsAppsHostsTable */
        $WindowsAppsHostsTable = $this->fetchTable('WindowsAppsHosts');
        /** @var MacosAppsHostsTable $MacosAppsHostsTable */
        $MacosAppsHostsTable = $this->fetchTable('MacosAppsHosts');

        return $WindowsAppsHostsTable->countByHostId($hostId) + $MacosAppsHostsTable->countByHostId($hostId);
    }

    /**
     * @param string $result One of the RESULT_* constants
     * @param string|null $status Status of the evaluation
     * @param array $values
     * @return array
     */
    private function result(string $result, ?string $status, array $values = []): array {
        return $values + [
                'result'    => $result,
                'status'    => $status,
                'findings'  => 0,
                'new'       => 0,
                'reopened'  => 0,
                'resolved'  => 0,
                'open_cves' => 0,
                'reasons'   => [],
            ];
    }
}
