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

use App\Model\Entity\CveDecision;
use App\Model\Entity\CveHostFinding;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\CveDailyStatsTable;
use App\Model\Table\CveDecisionsTable;
use App\Model\Table\CveFeedsTable;
use App\Model\Table\CveHostCoverageTable;
use App\Model\Table\CveHostFindingsTable;
use App\Model\Table\CveHostSummariesTable;
use App\Model\Table\CvesTable;
use App\Model\Table\HostsTable;
use App\Model\Table\PackagesHostDetailsTable;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\CvesFilter;
use itnovum\openITCOCKPIT\Vuln\Api\CveFormatter;
use itnovum\openITCOCKPIT\Vuln\Evaluation\HostEvaluator;
use itnovum\openITCOCKPIT\Vuln\Policy\PolicyVersion;

/**
 * API of the CVE vulnerability management.
 *
 * Every query only returns hosts the user may see ($MY_RIGHTS). Decisions need write permission on the
 * containers of the host. The answers contain codes instead of texts, see CveFormatter.
 *
 * @link /opt/openitc/frontend/src/itnovum/openITCOCKPIT/Vuln/Policy/POLICY.md
 */
class CvesController extends AppController {

    /** Most findings one request may decide on, e.g. all CVEs of the kernel of one host */
    private const MAX_FINDINGS_PER_DECISION = 5000;

    /** Most hosts one request may ask the state for */
    private const MAX_HOSTS_PER_STATE_REQUEST = 1000;

    private const MAX_TREND_DAYS = 366;

    private const DEFAULT_TREND_DAYS = 60;

    /** Older Vuls databases make the summary show a warning, same limit as HostRules */
    private const FEED_MAX_AGE_DAYS = 7;

    private const VIEWS = [
        CveHostFindingsTable::VIEW_ACTION,
        CveHostFindingsTable::VIEW_UNFIXED,
        CveHostFindingsTable::VIEW_ACCEPTED,
        CveHostFindingsTable::VIEW_NOT_AFFECTED,
    ];

    /**
     * Host list, most urgent hosts first
     *
     * @return void
     */
    public function index(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $feedDate = $this->getFeedDate();
        $now = DateTime::now();

        $hosts = [];
        foreach ($CveHostSummariesTable->getCveHostsIndex($CvesFilter, $PaginateOMat, $this->getReadRights()) as $summary) {
            $hosts[] = $Formatter->host($summary, $feedDate, $now);
        }

        $this->set('hosts', $hosts);
        $this->viewBuilder()->setOption('serialize', ['hosts']);
    }

    /**
     * Tiles above the host list: hosts per state, CVEs across all hosts and the state of the data
     *
     * @return void
     */
    public function summary(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');
        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        /** @var CveFeedsTable $CveFeedsTable */
        $CveFeedsTable = TableRegistry::getTableLocator()->get('CveFeeds');

        $Formatter = new CveFormatter();
        $feedDate = $this->getFeedDate();
        $now = DateTime::now();
        $MY_RIGHTS = $this->getReadRights();

        // The state depends on the time of the request (stale data), so it gets calculated for every host
        $hosts = ['total' => 0, 'statuses' => [], 'colors' => []];
        $lastEvaluation = null;
        foreach ($CveHostSummariesTable->getCveHostsIndex(new CvesFilter($this->request), null, $MY_RIGHTS) as $summary) {
            $state = $Formatter->host($summary, $feedDate, $now)['state'];
            $hosts['total']++;
            $hosts['statuses'][$state['status']] = ($hosts['statuses'][$state['status']] ?? 0) + 1;
            $hosts['colors'][$state['color']] = ($hosts['colors'][$state['color']] ?? 0) + 1;

            $evaluated = $summary['evaluated'] ?? null;
            if ($evaluated instanceof \DateTimeInterface && ($lastEvaluation === null || $evaluated > $lastEvaluation)) {
                $lastEvaluation = $evaluated;
            }
        }

        $feed = $CveFeedsTable->getCurrentFeed();

        $summary = [
            'hosts'           => $hosts,
            'cves'            => $CveHostFindingsTable->getCveTotals($MY_RIGHTS),
            'feed'            => [
                'digest'    => $feed?->digest,
                'feed_date' => $feed?->feed_date,
                'outdated'  => $CveFeedsTable->isCurrentFeedOutdated(self::FEED_MAX_AGE_DAYS, $now),
            ],
            'last_evaluation' => $lastEvaluation,
            'policy_version'  => PolicyVersion::CURRENT,
        ];

        $this->set('summary', $summary);
        $this->viewBuilder()->setOption('serialize', ['summary']);
    }

    /**
     * Values per day of all hosts, or of one host with ?host_id=
     *
     * @return void
     */
    public function trend(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $days = (int)$this->request->getQuery('days', self::DEFAULT_TREND_DAYS);
        if ($days < 1 || $days > self::MAX_TREND_DAYS) {
            throw new BadRequestException(sprintf('days must be between 1 and %d', self::MAX_TREND_DAYS));
        }

        $hostIds = [];
        $hostId = $this->request->getQuery('host_id');
        if ($hostId !== null && $hostId !== '') {
            if (!$this->checkHostReadPermission((int)$hostId)) {
                return;
            }
            $hostIds = [(int)$hostId];
        }

        /** @var CveDailyStatsTable $CveDailyStatsTable */
        $CveDailyStatsTable = TableRegistry::getTableLocator()->get('CveDailyStats');
        $to = Date::today();
        $from = $to->subDays($days - 1);

        $this->set('trend', $CveDailyStatsTable->getTrend($from, $to, $this->getReadRights(), $hostIds));
        $this->viewBuilder()->setOption('serialize', ['trend']);
    }

    /**
     * Head of the host details: state, counters, operating system and the number of findings per tab
     *
     * @param int|null $hostId
     * @return void
     */
    public function host($hostId = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;
        if (!$this->checkHostReadPermission($hostId)) {
            return;
        }

        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');
        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        /** @var PackagesHostDetailsTable $PackagesHostDetailsTable */
        $PackagesHostDetailsTable = TableRegistry::getTableLocator()->get('PackagesHostDetails');

        $summary = $CveHostSummariesTable->getSummaryOfHost($hostId, $this->getReadRights());
        if ($summary !== null) {
            $summary = (new CveFormatter())->host($summary, $this->getFeedDate(), DateTime::now());
        }

        // The inventory as the agent reported it, without the list of packages
        $details = $PackagesHostDetailsTable->getPackagesHostDetailsByHostId($hostId);
        $inventory = null;
        if (!empty($details)) {
            $inventory = [
                'os_type'        => $details['os_type'] ?? null,
                'os_name'        => $details['os_name'] ?? null,
                'os_version'     => $details['os_version'] ?? null,
                'os_family'      => $details['os_family'] ?? null,
                'kernel_release' => $details['kernel_release'] ?? null,
                'agent_version'  => $details['agent_version'] ?? null,
                'last_update'    => $details['last_update'] ?? null,
            ];
        }

        $this->set('summary', $summary);
        $this->set('inventory', $inventory);
        $this->set('views', $CveHostFindingsTable->getCountsPerView($hostId));
        $this->set('allowEdit', $this->isHostWritable($hostId));
        $this->viewBuilder()->setOption('serialize', ['summary', 'inventory', 'views', 'allowEdit']);
    }

    /**
     * Components of one tab of the host details: ?view=action|unfixed|accepted|not_affected
     *
     * @param int|null $hostId
     * @return void
     */
    public function host_components($hostId = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;
        if (!$this->checkHostReadPermission($hostId)) {
            return;
        }
        $viewName = $this->getViewName();

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $components = [];
        foreach ($CveHostFindingsTable->getComponentsOfHost($hostId, $viewName, $CvesFilter, $PaginateOMat, $this->getReadRights()) as $component) {
            $components[] = $Formatter->hostComponent($component, $viewName);
        }

        $this->set('components', $components);
        $this->viewBuilder()->setOption('serialize', ['components']);
    }

    /**
     * Findings of one tab of the host details, with ?component_name= and ?component_version= of one component
     *
     * @param int|null $hostId
     * @return void
     */
    public function host_findings($hostId = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;
        if (!$this->checkHostReadPermission($hostId)) {
            return;
        }
        $viewName = $this->getViewName();

        $componentName = $this->request->getQuery('component_name');
        $componentVersion = $this->request->getQuery('component_version');

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $findings = [];
        $rows = $CveHostFindingsTable->getFindingsOfHost(
            $hostId,
            $viewName,
            $CvesFilter,
            $PaginateOMat,
            $this->getReadRights(),
            $componentName === null ? null : (string)$componentName,
            $componentVersion === null ? null : (string)$componentVersion
        );
        foreach ($rows as $finding) {
            $findings[] = $Formatter->finding($finding);
        }

        $this->set('findings', $findings);
        $this->viewBuilder()->setOption('serialize', ['findings']);
    }

    /**
     * Tab "coverage" of the host details: what could not be evaluated and why
     *
     * @param int|null $hostId
     * @return void
     */
    public function host_coverage($hostId = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        $hostId = (int)$hostId;
        if (!$this->checkHostReadPermission($hostId)) {
            return;
        }

        /** @var CveHostCoverageTable $CveHostCoverageTable */
        $CveHostCoverageTable = TableRegistry::getTableLocator()->get('CveHostCoverage');
        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');

        $coverage = $CveHostCoverageTable->getCoverageOfHost($hostId);
        $summary = $CveHostSummariesTable->getSummaryOfHost($hostId, $this->getReadRights());

        $result = [
            // Codes of CoverageReason
            'reasons'                 => $coverage['reason'] ?? [],
            // Lists of packages per type, e.g. missing_metadata and inactive_kernel
            'items'                   => array_diff_key($coverage, ['reason' => true]),
            'status'                  => $summary['status'] ?? null,
            'installations'           => (int)($summary['installations'] ?? 0),
            'evaluated_installations' => (int)($summary['evaluated_installations'] ?? 0),
            'error_message'           => $summary['error_message'] ?? null,
        ];

        $this->set('coverage', $result);
        $this->viewBuilder()->setOption('serialize', ['coverage']);
    }

    /**
     * One finding with CVE, decision and recommendation
     *
     * @param int|null $findingId
     * @return void
     */
    public function finding($findingId = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');

        $finding = $CveHostFindingsTable->getFindingById((int)$findingId, $this->getReadRights());
        if ($finding === null) {
            throw new NotFoundException(__('Finding not found'));
        }

        $this->set('finding', (new CveFormatter())->finding($finding));
        $this->set('allowEdit', $finding['resolved_at'] === null && $this->isHostWritable((int)$finding['host_id']));
        $this->set('decisionStates', [CveDecision::STATE_ACCEPTED, CveDecision::STATE_NOT_AFFECTED, CveDecision::STATE_IN_PROGRESS]);
        $this->viewBuilder()->setOption('serialize', ['finding', 'allowEdit', 'decisionStates']);
    }

    /**
     * Creates or replaces the decision of one or many findings. POST:
     *
     *     {"finding_ids": [1, 2], "CveDecision": {"state": "accepted", "owner": "...", "reason": "...",
     *      "review_after": "2027-01-31", "evidence_url": "https://..."}}
     *
     * All or nothing: if the decision is invalid for one finding, none gets saved. The affected hosts get classified
     * again right away, so color and counters are correct without waiting for the cronjob.
     *
     * @return void
     */
    public function decide(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $findings = $this->getFindingsOfRequest();
        if ($findings === null) {
            return;
        }

        $data = $this->request->getData('CveDecision');
        if (!is_array($data)) {
            throw new BadRequestException('CveDecision is missing');
        }

        /** @var CveDecisionsTable $CveDecisionsTable */
        $CveDecisionsTable = TableRegistry::getTableLocator()->get('CveDecisions');
        $User = new User($this->getUser());

        $result = $CveDecisionsTable->decideMany($findings, $data, $User->getId(), PolicyVersion::CURRENT);
        if (!empty($result['errors'])) {
            // The same input for all findings, the errors of the first one tell the form what is wrong
            $this->response = $this->response->withStatus(400);
            $this->set('error', reset($result['errors']));
            $this->set('findingIds', array_keys($result['errors']));
            $this->viewBuilder()->setOption('serialize', ['error', 'findingIds']);
            return;
        }

        $hostIds = $this->afterDecisionChange($findings, 'add', $User, $data);

        $this->set('success', true);
        $this->set('decisions', count($result['decisions']));
        $this->set('hostIds', $hostIds);
        $this->viewBuilder()->setOption('serialize', ['success', 'decisions', 'hostIds']);
    }

    /**
     * Ends the decision of one or many findings. POST: {"finding_ids": [1, 2]}
     *
     * @return void
     */
    public function remove_decision(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $findings = $this->getFindingsOfRequest();
        if ($findings === null) {
            return;
        }

        /** @var CveDecisionsTable $CveDecisionsTable */
        $CveDecisionsTable = TableRegistry::getTableLocator()->get('CveDecisions');
        $User = new User($this->getUser());

        $removed = 0;
        $CveDecisionsTable->getConnection()->transactional(function () use ($findings, $CveDecisionsTable, &$removed) {
            foreach ($findings as $finding) {
                if ($CveDecisionsTable->removeDecisionOfFinding((int)$finding->id)) {
                    $removed++;
                }
            }
        });

        $hostIds = $this->afterDecisionChange($findings, 'delete', $User, []);

        $this->set('success', true);
        $this->set('removed', $removed);
        $this->set('hostIds', $hostIds);
        $this->viewBuilder()->setOption('serialize', ['success', 'removed', 'hostIds']);
    }

    /**
     * Decisions of open findings, the next review date first
     *
     * @return void
     */
    public function decisions(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveDecisionsTable $CveDecisionsTable */
        $CveDecisionsTable = TableRegistry::getTableLocator()->get('CveDecisions');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $decisions = [];
        foreach ($CveDecisionsTable->getDecisionsIndex($CvesFilter, $PaginateOMat, $this->getReadRights()) as $row) {
            $row['user'] = $row['user_id'] === null ? null : ['firstname' => $row['user_firstname'], 'lastname' => $row['user_lastname'], 'id' => $row['user_id']];
            unset($row['user_firstname'], $row['user_lastname']);
            $row = $Formatter->decision($row);
            $row['host_id'] = (int)$row['host_id'];
            $row['review_required'] = (bool)$row['review_required'];
            $decisions[] = $row;
        }

        $this->set('decisions', $decisions);
        $this->viewBuilder()->setOption('serialize', ['decisions']);
    }

    /**
     * Open CVEs across all hosts
     *
     * @return void
     */
    public function cves(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $cves = [];
        foreach ($CveHostFindingsTable->getCvesIndex($CvesFilter, $PaginateOMat, $this->getReadRights()) as $row) {
            $cves[] = $Formatter->aggregate($row);
        }

        $this->set('cves', $cves);
        $this->viewBuilder()->setOption('serialize', ['cves']);
    }

    /**
     * One CVE and the hosts it affects: /cves/view/CVE-2026-1234.json
     *
     * @param string|null $cveId
     * @return void
     */
    public function view($cveId = null): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CvesTable $CvesTable */
        $CvesTable = TableRegistry::getTableLocator()->get('Cves');
        $cve = $CvesTable->getCveByCveId((string)$cveId);
        if ($cve === null) {
            throw new NotFoundException(__('CVE not found'));
        }

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $findings = [];
        foreach ($CveHostFindingsTable->getFindingsByCveId((string)$cve['cve_id'], $CvesFilter, $PaginateOMat, $this->getReadRights()) as $finding) {
            $findings[] = $Formatter->finding($finding);
        }

        $this->set('cve', $Formatter->aggregate($cve));
        $this->set('findings', $findings);
        $this->viewBuilder()->setOption('serialize', ['cve', 'findings']);
    }

    /**
     * What to do across all hosts, only findings that need action
     *
     * @return void
     */
    public function recommendations(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $Formatter = new CveFormatter();
        $recommendations = [];
        foreach ($CveHostFindingsTable->getRecommendations($CvesFilter, $PaginateOMat, $this->getReadRights()) as $row) {
            $recommendations[] = $Formatter->recommendationRow($row);
        }

        $this->set('recommendations', $recommendations);
        $this->viewBuilder()->setOption('serialize', ['recommendations']);
    }

    /**
     * Affected software across all hosts. With ?component_name= and ?component_version= the hosts of this component.
     *
     * @return void
     */
    public function software(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $CvesFilter = new CvesFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $CvesFilter->getPage());

        $componentName = $this->request->getQuery('component_name');
        $componentVersion = $this->request->getQuery('component_version');

        $Formatter = new CveFormatter();
        $rows = [];
        if ($componentName !== null && $componentVersion !== null) {
            $result = $CveHostFindingsTable->getHostsOfComponent((string)$componentName, (string)$componentVersion, $CvesFilter, $PaginateOMat, $this->getReadRights());
        } else {
            $result = $CveHostFindingsTable->getSoftwareIndex($CvesFilter, $PaginateOMat, $this->getReadRights());
        }
        foreach ($result as $row) {
            $rows[] = $Formatter->aggregate($row);
        }

        $this->set('software', $rows);
        $this->viewBuilder()->setOption('serialize', ['software']);
    }

    /**
     * State of several hosts, e.g. for an icon in the host list: ?host_ids[]=1&host_ids[]=2
     * Hosts without evaluation or without permission are missing in the answer.
     *
     * @return void
     */
    public function host_states(): void {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        $hostIds = $this->request->getQuery('host_ids', []);
        if (!is_array($hostIds)) {
            $hostIds = [$hostIds];
        }
        $hostIds = array_values(array_unique(array_map('intval', $hostIds)));
        if (count($hostIds) > self::MAX_HOSTS_PER_STATE_REQUEST) {
            throw new BadRequestException(sprintf('At most %d hosts per request', self::MAX_HOSTS_PER_STATE_REQUEST));
        }

        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');
        $Formatter = new CveFormatter();
        $feedDate = $this->getFeedDate();
        $now = DateTime::now();

        $states = [];
        foreach ($CveHostSummariesTable->getSummariesOfHosts($hostIds, $this->getReadRights()) as $hostId => $summary) {
            $states[$hostId] = $Formatter->host($summary, $feedDate, $now);
        }

        $this->set('states', $states);
        $this->viewBuilder()->setOption('serialize', ['states']);
    }

    /**
     * @return array Containers the user may read, empty for root users (no restriction)
     */
    private function getReadRights(): array {
        if ($this->hasRootPrivileges) {
            return [];
        }
        return $this->MY_RIGHTS;
    }

    /**
     * @return DateTime|null Date of the current Vuls database
     */
    private function getFeedDate(): ?DateTime {
        /** @var CveFeedsTable $CveFeedsTable */
        $CveFeedsTable = TableRegistry::getTableLocator()->get('CveFeeds');
        return $CveFeedsTable->getCurrentFeed()?->feed_date;
    }

    /**
     * @return string View of the request (?view=), the tab "action" by default
     * @throws BadRequestException
     */
    private function getViewName(): string {
        $viewName = (string)$this->request->getQuery('view', CveHostFindingsTable::VIEW_ACTION);
        if (!in_array($viewName, self::VIEWS, true)) {
            throw new BadRequestException('view must be one of ' . implode(', ', self::VIEWS));
        }
        return $viewName;
    }

    /**
     * Renders 403 if the user may not see the host.
     *
     * @param int $hostId
     * @return bool
     * @throws NotFoundException
     */
    private function checkHostReadPermission(int $hostId): bool {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        if (!$HostsTable->existsById($hostId)) {
            throw new NotFoundException(__('Host not found'));
        }

        if (!$this->allowedByContainerId($HostsTable->getHostContainerIdsByHostId($hostId), false)) {
            $this->render403();
            return false;
        }
        return true;
    }

    /**
     * @param int $hostId
     * @return bool True if the user has write permission on one of the containers of the host
     */
    private function isHostWritable(int $hostId): bool {
        if ($this->hasRootPrivileges) {
            return true;
        }

        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        $containerIds = $HostsTable->getHostContainerIdsByHostId($hostId);
        return !empty(array_intersect($containerIds, $this->getWriteContainers()));
    }

    /**
     * The open findings of finding_ids of the POST request. Renders 403 if the user may not change one of the hosts.
     *
     * @return array<int, CveHostFinding>|null null if a 403 has been rendered
     * @throws BadRequestException
     * @throws NotFoundException
     */
    private function getFindingsOfRequest(): ?array {
        $findingIds = $this->request->getData('finding_ids');
        if (!is_array($findingIds) || empty($findingIds)) {
            throw new BadRequestException('finding_ids must contain at least one finding');
        }
        $findingIds = array_values(array_unique(array_map('intval', $findingIds)));
        if (count($findingIds) > self::MAX_FINDINGS_PER_DECISION) {
            throw new BadRequestException(sprintf('At most %d findings per request', self::MAX_FINDINGS_PER_DECISION));
        }

        /** @var CveHostFindingsTable $CveHostFindingsTable */
        $CveHostFindingsTable = TableRegistry::getTableLocator()->get('CveHostFindings');
        $findings = $CveHostFindingsTable->getOpenFindingsByIds($findingIds);
        if (count($findings) !== count($findingIds)) {
            // Resolved findings can not be decided on, the decision would never show up
            throw new NotFoundException(__('Finding not found or already resolved'));
        }

        $hostIds = array_unique(array_map(fn(CveHostFinding $finding) => (int)$finding->host_id, $findings));
        foreach ($hostIds as $hostId) {
            if (!$this->isHostWritable($hostId)) {
                $this->render403();
                return null;
            }
        }

        return $findings;
    }

    /**
     * Classifies the affected hosts again and writes one changelog entry per host.
     *
     * @param array<int, CveHostFinding> $findings
     * @param string $action add or delete
     * @param User $User
     * @param array $data The decision as entered
     * @return int[] Ids of the affected hosts
     */
    private function afterDecisionChange(array $findings, string $action, User $User, array $data): array {
        /** @var HostsTable $HostsTable */
        $HostsTable = TableRegistry::getTableLocator()->get('Hosts');
        /** @var ChangelogsTable $ChangelogsTable */
        $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

        $findingsByHost = [];
        foreach ($findings as $finding) {
            $findingsByHost[(int)$finding->host_id][] = $finding;
        }

        $HostEvaluator = new HostEvaluator();
        $now = DateTime::now();
        foreach ($findingsByHost as $hostId => $findingsOfHost) {
            $HostEvaluator->reclassifyHost($hostId, $now);

            $hostName = (string)$findingsOfHost[0]->host->name;
            $changelogData = $ChangelogsTable->parseDataForChangelog(
                $action,
                'cvedecisions',
                $hostId,
                OBJECT_CVE_DECISION,
                $HostsTable->getHostContainerIdsByHostId($hostId),
                $User->getId(),
                $action === 'delete' ? $hostName . ': ' . $this->describeFindings($findingsOfHost) : $hostName,
                [
                    'CveDecision' => array_intersect_key($data, array_flip(['state', 'owner', 'reason', 'review_after', 'evidence_url'])) + [
                            'findings' => array_map(fn(CveHostFinding $finding) => [
                                'id'                => (int)$finding->id,
                                'cve_id'            => (string)$finding->cve->cve_id,
                                'component_name'    => (string)$finding->component_name,
                                'component_version' => (string)$finding->component_version,
                            ], $findingsOfHost),
                        ],
                ]
            );
            if ($changelogData) {
                $ChangelogsTable->save($ChangelogsTable->newEntity($changelogData));
            }
        }

        return array_keys($findingsByHost);
    }

    /**
     * Short text of the findings for the changelog of a removed decision, which stores no data
     *
     * @param CveHostFinding[] $findings
     * @return string e.g. "CVE-2026-1 (openssl 3.0.13), CVE-2026-2 (curl 8.5.0)"
     */
    private function describeFindings(array $findings): string {
        $maxItems = 20;
        $items = [];
        foreach (array_slice($findings, 0, $maxItems) as $finding) {
            $items[] = sprintf('%s (%s %s)', $finding->cve->cve_id, $finding->component_name, $finding->component_version);
        }
        if (count($findings) > $maxItems) {
            $items[] = sprintf('+%d', count($findings) - $maxItems);
        }
        return implode(', ', $items);
    }
}
