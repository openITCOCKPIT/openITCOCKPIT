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

namespace App\Command;

use App\Model\Table\CveFeedsTable;
use App\Model\Table\CveHostSummariesTable;
use App\Model\Table\CvePackageStatesTable;
use App\Model\Table\PackagesHostDetailsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;
use itnovum\openITCOCKPIT\Vuln\Evaluation\CoverageReason;
use itnovum\openITCOCKPIT\Vuln\Evaluation\HostEvaluator;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxDistribution;
use itnovum\openITCOCKPIT\Vuln\Linux\LinuxRequestBuilder;
use itnovum\openITCOCKPIT\Vuln\Vuls\ReleaseNotInFeedException;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsClient;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsException;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsFeed;
use itnovum\openITCOCKPIT\Vuln\Vuls\VulsReportReader;
use Throwable;

/**
 * Checks the package states of all Linux hosts with Vuls and evaluates every host.
 *
 * 1. Determine the current Vuls database (identifier and date)
 * 2. Create the package states of the inventory and link the installations to them
 * 3. Send every package state without a result for the current database to Vuls, one group after the other,
 *    and store the CVEs per package state
 * 4. Evaluate every host: findings, classification by the policy, summary, coverage and the values of the day
 * 5. Remove the CVE data of deleted hosts and package states nobody has installed anymore
 *
 * Package states that were checked with the current database are skipped, so a second run without a new
 * database or new packages sends nothing to Vuls. Hosts with an unchanged fingerprint are skipped as well,
 * they only get classified again once per day, so decisions end on their day.
 *
 * If Vuls is not reachable, the hosts are still evaluated with the last results: a decision that ends today
 * must not wait for Vuls. The command ends with an error in this case.
 *
 * The Vuls server has to run already, openITCOCKPIT never starts it (systemd or docker compose do).
 * A release the Vuls database does not know yet (e.g. a brand new release) is no error: the hosts are not
 * evaluable until a newer database knows the release.
 *
 * Usage:
 * oitc cve_check
 * oitc cve_check --force --host-id 42
 */
class CveCheckCommand extends Command implements CronjobInterface {

    /** Result of checkGroup(): all package states of the group got a result */
    private const GROUP_CHECKED = 'checked';

    /** Result of checkGroup(): the Vuls database does not know the release, the group is not evaluable */
    private const GROUP_NOT_IN_FEED = 'not_in_feed';

    /** Result of checkGroup(): at least one package state could not be checked */
    private const GROUP_FAILED = 'failed';

    /**
     * Package states nobody had installed for this many days get removed
     */
    private const UNUSED_STATE_DAYS = 7;

    /**
     * @param ConsoleOptionParser $parser
     * @return ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription('Checks the installed Linux packages of all hosts for CVEs with Vuls and evaluates every host.');
        $parser->addOption('force', [
            'help'    => 'Evaluate every host, also if nothing has changed',
            'boolean' => true,
        ]);
        $parser->addOption('host-id', [
            'help' => 'Only sync and evaluate this host',
        ]);

        return $parser;
    }

    /**
     * @param Arguments $args
     * @param ConsoleIo $io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int {
        $hostIds = [];
        if ($args->getOption('host-id') !== null) {
            $hostIds = [(int)$args->getOption('host-id')];
        }

        /** @var CveFeedsTable $CveFeedsTable */
        $CveFeedsTable = TableRegistry::getTableLocator()->get('CveFeeds');
        /** @var CvePackageStatesTable $CvePackageStatesTable */
        $CvePackageStatesTable = TableRegistry::getTableLocator()->get('CvePackageStates');

        $VulsClient = new VulsClient();
        $isVulsReachable = $VulsClient->isHealthy();
        if (!$isVulsReachable) {
            $io->error(sprintf('Vuls is not reachable at %s, the hosts get evaluated with the last results', $VulsClient->getAddress()));
        }

        // 1. Vuls database
        $io->out('Determine the Vuls database... ', 0);
        $feedDigest = null;
        try {
            $VulsFeed = new VulsFeed();
            $feedDigest = $VulsFeed->getDigest();
            $CveFeedsTable->setCurrentFeed($feedDigest, $VulsFeed->getFeedDate());
            $io->out(sprintf('%s from %s', $feedDigest, $VulsFeed->getFeedDate()->format('Y-m-d H:i')));
        } catch (VulsException $e) {
            $io->error($e->getMessage());
            $isVulsReachable = false;
            $feedDigest = $CveFeedsTable->getCurrentFeed()?->digest;
        }

        if ($feedDigest === null) {
            $io->error('No Vuls database has been used so far, nothing to evaluate.');
            return static::CODE_ERROR;
        }

        // 2. Package states of the inventory
        $io->out('Sync package states with the inventory... ', 0);
        $sync = $CvePackageStatesTable->syncFromInventory($hostIds);
        $io->out(sprintf(
            '%d hosts, %d package states (%d new), %d installations can not be checked',
            $sync['hosts'],
            $sync['states'],
            $sync['new_states'],
            $sync['skipped_installations']
        ));

        // 3. Check the pending package states group by group
        $isCheckComplete = $isVulsReachable && $this->checkPendingStates($feedDigest, $VulsClient, $CvePackageStatesTable, $io);

        // 4. Evaluate the hosts
        $isEvaluationComplete = $this->evaluateHosts($hostIds, $feedDigest, (bool)$args->getOption('force'), $io);

        // 5. Cleanup, only if all hosts have been synced
        if (empty($hostIds)) {
            $this->cleanup($CvePackageStatesTable, $io);
        }

        if (!$isCheckComplete || !$isEvaluationComplete) {
            return static::CODE_ERROR;
        }

        $io->success('CVE check finished.');
        return static::CODE_SUCCESS;
    }

    /**
     * Sends all package states without a result for the current Vuls database to Vuls.
     *
     * @param string $feedDigest
     * @param VulsClient $VulsClient
     * @param CvePackageStatesTable $CvePackageStatesTable
     * @param ConsoleIo $io
     * @return bool False if at least one group could not be checked. Releases unknown to Vuls are no error.
     */
    private function checkPendingStates(string $feedDigest, VulsClient $VulsClient, CvePackageStatesTable $CvePackageStatesTable, ConsoleIo $io): bool {
        $groups = $CvePackageStatesTable->getPendingGroups($feedDigest);
        if (empty($groups)) {
            $io->out('All package states are checked with the current Vuls database.');
            return true;
        }

        $results = [self::GROUP_CHECKED => 0, self::GROUP_NOT_IN_FEED => 0, self::GROUP_FAILED => 0];
        foreach ($groups as $group) {
            $results[$this->checkGroup($group, $feedDigest, $VulsClient, $CvePackageStatesTable, $io)]++;
        }

        if ($results[self::GROUP_NOT_IN_FEED] > 0) {
            $io->out(sprintf('<warning>%d groups are not evaluable: the Vuls database does not know their release yet.</warning>', $results[self::GROUP_NOT_IN_FEED]));
        }

        if ($results[self::GROUP_FAILED] > 0) {
            $io->error(sprintf('%d of %d groups could not be checked completely.', $results[self::GROUP_FAILED], count($groups)));
            return false;
        }

        $io->out(sprintf('%d groups checked.', $results[self::GROUP_CHECKED]));
        return true;
    }

    /**
     * Evaluates every Linux host. An error of one host does not stop the others.
     *
     * @param int[] $hostIds All hosts if empty
     * @param string $feedDigest
     * @param bool $force
     * @param ConsoleIo $io
     * @return bool False if at least one host could not be evaluated
     */
    private function evaluateHosts(array $hostIds, string $feedDigest, bool $force, ConsoleIo $io): bool {
        /** @var PackagesHostDetailsTable $PackagesHostDetailsTable */
        $PackagesHostDetailsTable = TableRegistry::getTableLocator()->get('PackagesHostDetails');
        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');

        $HostEvaluator = new HostEvaluator();
        $now = DateTime::now();
        $results = [HostEvaluator::RESULT_EVALUATED => 0, HostEvaluator::RESULT_RECLASSIFIED => 0, HostEvaluator::RESULT_SKIPPED => 0];
        $failed = 0;

        $io->out('Evaluate hosts...');
        foreach ($PackagesHostDetailsTable->getLinuxHostsForCveCheck($hostIds) as $host) {
            $label = sprintf('  %s (%d)', $host['host_name'], $host['host_id']);
            $start = microtime(true);

            try {
                $result = $HostEvaluator->evaluateLinuxHost($host, $feedDigest, $force, $now);
            } catch (Throwable $e) {
                $failed++;
                $io->err(sprintf('%s: %s', $label, $e->getMessage()));
                $CveHostSummariesTable->saveFailure((int)$host['host_id'], 'linux', mb_substr($e->getMessage(), 0, 1000), $now);
                continue;
            }

            $results[$result['result']]++;
            if ($result['result'] === HostEvaluator::RESULT_SKIPPED) {
                $io->verbose(sprintf('%s: unchanged', $label));
                continue;
            }

            if ($result['result'] === HostEvaluator::RESULT_RECLASSIFIED) {
                $io->out(sprintf('%s: classified again, %d open CVEs', $label, $result['open_cves']));
                continue;
            }

            $io->out(sprintf(
                '%s: %s, %d findings (%d new, %d reopened, %d resolved), %d open CVEs%s, %.1f s',
                $label,
                $result['status'],
                $result['findings'],
                $result['new'],
                $result['reopened'],
                $result['resolved'],
                $result['open_cves'],
                empty($result['reasons']) ? '' : ' [' . implode(', ', $result['reasons']) . ']',
                microtime(true) - $start
            ));
        }

        $io->out(sprintf(
            '%d hosts evaluated, %d classified again, %d unchanged%s',
            $results[HostEvaluator::RESULT_EVALUATED],
            $results[HostEvaluator::RESULT_RECLASSIFIED],
            $results[HostEvaluator::RESULT_SKIPPED],
            $failed > 0 ? sprintf(', <error>%d failed</error>', $failed) : ''
        ));

        return $failed === 0;
    }

    /**
     * Removes the CVE data of deleted hosts and package states that no host has installed anymore.
     *
     * @param CvePackageStatesTable $CvePackageStatesTable
     * @param ConsoleIo $io
     * @return void
     */
    private function cleanup(CvePackageStatesTable $CvePackageStatesTable, ConsoleIo $io): void {
        /** @var CveHostSummariesTable $CveHostSummariesTable */
        $CveHostSummariesTable = TableRegistry::getTableLocator()->get('CveHostSummaries');

        $HostEvaluator = new HostEvaluator();
        $deletedHostIds = $CveHostSummariesTable->getIdsOfDeletedHosts();
        foreach ($deletedHostIds as $hostId) {
            $HostEvaluator->deleteHost((int)$hostId);
        }

        $deletedStates = $CvePackageStatesTable->deleteUnusedStates(DateTime::now()->subDays(self::UNUSED_STATE_DAYS));

        if (!empty($deletedHostIds) || $deletedStates > 0) {
            $io->out(sprintf('Removed the CVE data of %d deleted hosts and %d unused package states.', count($deletedHostIds), $deletedStates));
        }
    }

    /**
     * Sends the pending package states of one group to Vuls and stores the result.
     *
     * @param array $group Row of CvePackageStatesTable::getPendingGroups()
     * @param string $feedDigest
     * @param VulsClient $VulsClient
     * @param CvePackageStatesTable $CvePackageStatesTable
     * @param ConsoleIo $io
     * @return string One of the GROUP_* constants
     */
    private function checkGroup(array $group, string $feedDigest, VulsClient $VulsClient, CvePackageStatesTable $CvePackageStatesTable, ConsoleIo $io): string {
        $label = sprintf('%s %s', $group['os_name'], $group['os_version']);
        if ($group['is_kernel']) {
            $label .= ' kernel ' . ($group['kernel_release'] ?? 'unknown');
        }
        $io->out(sprintf('  %s: %d package states... ', $label, $group['pending']), 0);
        $start = microtime(true);

        $states = $CvePackageStatesTable->getStatesOfGroup(
            $group['os_name'],
            $group['os_version'],
            $group['is_kernel'],
            $group['kernel_release'],
            $feedDigest
        );
        $stateIds = array_map(fn($state) => $state->id, $states);

        $distribution = LinuxDistribution::fromInventory($group['os_name'], $group['os_version']);
        if ($distribution === null) {
            $CvePackageStatesTable->saveCheckError($stateIds, CoverageReason::UNSUPPORTED_DISTRIBUTION);
            $io->out('<warning>distribution not supported by Vuls</warning>');
            return self::GROUP_FAILED;
        }

        $releasePackages = [];
        if ($distribution->getReleasePackageName() !== null) {
            $releasePackages = $CvePackageStatesTable->getStatesByNames($group['os_name'], $group['os_version'], [$distribution->getReleasePackageName()]);
        }

        $plan = (new LinuxRequestBuilder())->build($distribution, $group['is_kernel'] ? $group['kernel_release'] : null, $states, $releasePackages);

        $isComplete = true;
        foreach ($plan->getSkipped() as $stateId => $reason) {
            $CvePackageStatesTable->saveCheckError([$stateId], $reason);
            $isComplete = false;
        }

        $Reader = new VulsReportReader();
        $cveCount = 0;
        $unassigned = 0;
        foreach ($plan->getRequests() as $request) {
            try {
                $report = $VulsClient->scan($request->toArray());
            } catch (ReleaseNotInFeedException $e) {
                // No request of this group can succeed. The code tells the evaluation why the hosts are not evaluable.
                $CvePackageStatesTable->saveCheckError($stateIds, CoverageReason::RELEASE_NOT_IN_FEED);
                $io->out(sprintf('<warning>%s</warning>', $e->getMessage()));
                return self::GROUP_NOT_IN_FEED;
            } catch (VulsException $e) {
                $CvePackageStatesTable->saveCheckError($request->getStateIds(), mb_substr($e->getMessage(), 0, 1000));
                $io->err(sprintf('    %s: %s', $request->serverName, $e->getMessage()));
                $isComplete = false;
                continue;
            }

            $result = $Reader->read($report, $request);
            $CvePackageStatesTable->saveCheckResults($feedDigest, $result['cves'], $result['matches']);
            $cveCount += count($result['cves']);
            $unassigned += count($result['unassigned']);
        }

        $io->out(sprintf(
            '%d requests, %d CVEs%s, %.1f s',
            count($plan->getRequests()),
            $cveCount,
            $unassigned > 0 ? sprintf(' (%d without matching package)', $unassigned) : '',
            microtime(true) - $start
        ));

        if (!empty($plan->getSkipped())) {
            $io->out(sprintf('    <warning>%d package states skipped: %s</warning>', count($plan->getSkipped()), implode(', ', array_unique($plan->getSkipped()))));
        }

        return $isComplete ? self::GROUP_CHECKED : self::GROUP_FAILED;
    }
}
