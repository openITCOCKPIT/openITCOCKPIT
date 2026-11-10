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

namespace App\Model\Table;

use App\Model\Entity\CveDecision;
use App\Model\Entity\CveHostFinding;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CveDecisions Model
 *
 * Decisions of humans about one finding (host, CVE, component and version):
 * - accepted:     The risk is accepted. The finding stays open and gray
 * - not_affected: The host is not affected, e.g. the vulnerable function is not used. Requires a link to the analysis
 * - in_progress:  The finding is confirmed and somebody works on it. The finding keeps its color, the decision only marks it
 *
 * Every decision needs a responsible owner, a reason and a review date in the future (the due date for in_progress).
 * The decision remembers the state of the finding. How a decision affects the classification is part of the policy:
 * accepted and not_affected end on the review date or as soon as severity, fix or vendor state change,
 * in_progress ends on its due date.
 *
 * @property \App\Model\Table\CveHostFindingsTable&\Cake\ORM\Association\BelongsTo $CveHostFindings
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\CveDecision newEmptyEntity()
 * @method \App\Model\Entity\CveDecision newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CveDecision get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\CveDecision patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveDecision|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveDecisionsTable extends Table {

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_decisions');
        $this->setDisplayField('state');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('CveHostFindings', [
            'foreignKey' => 'cve_host_finding_id',
            'joinType'   => 'INNER',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType'   => 'LEFT',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('cve_host_finding_id')
            ->requirePresence('cve_host_finding_id', 'create')
            ->notEmptyString('cve_host_finding_id');

        $validator
            ->inList('state', [CveDecision::STATE_ACCEPTED, CveDecision::STATE_NOT_AFFECTED, CveDecision::STATE_IN_PROGRESS], __('Please select a valid state.'))
            ->requirePresence('state', 'create')
            ->notEmptyString('state', __('Please select a valid state.'));

        $validator
            ->scalar('owner')
            ->maxLength('owner', 255)
            ->requirePresence('owner', 'create')
            ->notEmptyString('owner', __('Please enter the responsible person or team.'));

        $validator
            ->scalar('reason')
            ->requirePresence('reason', 'create')
            ->notEmptyString('reason', __('Please enter a reason.'));

        $validator
            ->date('review_after', ['ymd'], __('Please enter a valid date.'))
            ->requirePresence('review_after', 'create')
            ->notEmptyDate('review_after', __('Please enter a review date.'));

        $validator
            ->scalar('evidence_url')
            ->maxLength('evidence_url', 512)
            ->url('evidence_url', __('Please enter a valid URL.'))
            ->add('evidence_url', 'webLink', [
                'rule'    => fn($value) => in_array(parse_url((string)$value, PHP_URL_SCHEME), ['http', 'https'], true),
                'message' => __('Only http and https links are allowed.'),
            ])
            ->allowEmptyString('evidence_url');

        $validator
            ->integer('severity')
            ->range('severity', [0, 4])
            ->allowEmptyString('severity');

        $validator
            ->scalar('fixed_in')
            ->maxLength('fixed_in', 255)
            ->allowEmptyString('fixed_in');

        $validator
            ->scalar('fix_state')
            ->maxLength('fix_state', 255)
            ->allowEmptyString('fix_state');

        $validator
            ->boolean('not_fixed_yet')
            ->notEmptyString('not_fixed_yet');

        $validator
            ->scalar('policy_version')
            ->maxLength('policy_version', 32)
            ->requirePresence('policy_version', 'create')
            ->notEmptyString('policy_version');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        return $validator;
    }

    /**
     * Rules that need more than one field or the database.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker {
        $rules->add($rules->existsIn(['cve_host_finding_id'], 'CveHostFindings'), [
            'errorField' => 'cve_host_finding_id',
        ]);

        $rules->add($rules->isUnique(['cve_host_finding_id']), [
            'errorField' => 'cve_host_finding_id',
            'message'    => __('This finding already has a decision.'),
        ]);

        // A review date in the past would end the decision right away. Unchanged dates of existing decisions are fine.
        $rules->add(function (CveDecision $decision) {
            if (!$decision->isNew() && !$decision->isDirty('review_after')) {
                return true;
            }
            return $decision->review_after instanceof Date && $decision->review_after->greaterThan(Date::today());
        }, 'reviewAfterInFuture', [
            'errorField' => 'review_after',
            'message'    => __('The review date has to be in the future.'),
        ]);

        $rules->add(function (CveDecision $decision) {
            return $decision->state !== CveDecision::STATE_NOT_AFFECTED || trim((string)$decision->evidence_url) !== '';
        }, 'evidenceForNotAffected', [
            'errorField' => 'evidence_url',
            'message'    => __('"Not affected" requires a link to the analysis.'),
        ]);

        return $rules;
    }

    /**
     * Creates or replaces the decision of a finding. The state of the finding gets stored with the decision.
     *
     * @param CveHostFinding $finding
     * @param array $data User input: state, owner, reason, review_after (Y-m-d), evidence_url
     * @param int|null $userId User who made the decision
     * @param string $policyVersion Current version of the policy
     * @return CveDecision Check hasErrors() to find out if the decision was saved
     */
    public function decide(CveHostFinding $finding, array $data, ?int $userId, string $policyVersion): CveDecision {
        $decision = $this->find()
            ->where(['CveDecisions.cve_host_finding_id' => $finding->id])
            ->first();

        // These fields are not mass assignable (see CveDecision::$_accessible). They overwrite whatever the
        // request contains and get released only for this call.
        $serverFields = [
            'cve_host_finding_id' => $finding->id,
            'severity'            => $finding->severity,
            'fixed_in'            => $finding->fixed_in,
            'fix_state'           => $finding->fix_state,
            'not_fixed_yet'       => $finding->not_fixed_yet,
            'policy_version'      => $policyVersion,
            'user_id'             => $userId,
        ];
        $options = ['accessibleFields' => array_fill_keys(array_keys($serverFields), true)];
        $data = array_merge($data, $serverFields);

        $decision = $decision === null ? $this->newEntity($data, $options) : $this->patchEntity($decision, $data, $options);

        if (!$decision->hasErrors()) {
            $this->save($decision);
        }

        return $decision;
    }

    /**
     * Removes the decision of a finding. The finding gets its normal classification with the next evaluation.
     *
     * @param int $findingId
     * @return bool True if a decision was removed
     */
    public function removeDecisionOfFinding(int $findingId): bool {
        return $this->deleteAll(['cve_host_finding_id' => $findingId]) > 0;
    }

    /**
     * Returns the decisions of open findings that end until the given date, e.g. for a reminder.
     *
     * @param Date $until
     * @param array $MY_RIGHTS
     * @return array
     */
    public function getDecisionsDueUntil(Date $until, array $MY_RIGHTS = []): array {
        $query = $this->unhydratedFind()
            ->contain([
                'CveHostFindings' => function (SelectQuery $query) {
                    return $query
                        ->select([
                            'CveHostFindings.id',
                            'CveHostFindings.host_id',
                            'CveHostFindings.component_name',
                            'CveHostFindings.component_version',
                        ])
                        ->contain([
                            'Cves'  => fn(SelectQuery $query) => $query->select(['Cves.id', 'Cves.cve_id']),
                            'Hosts' => fn(SelectQuery $query) => $query->select(['Hosts.id', 'Hosts.name', 'Hosts.uuid']),
                        ]);
                },
            ])
            ->where([
                'CveDecisions.review_after <='   => $until,
                'CveHostFindings.resolved_at IS' => null,
            ])
            ->orderBy(['CveDecisions.review_after' => 'asc']);

        if (!empty($MY_RIGHTS)) {
            $query->innerJoin(['HostsToContainersSharing' => 'hosts_to_containers'], [
                'HostsToContainersSharing.host_id = CveHostFindings.host_id'
            ]);
            $query->where([
                'HostsToContainersSharing.container_id IN' => $MY_RIGHTS
            ]);
            $query->groupBy(['CveDecisions.id']);
        }

        return $query->toArray();
    }
}
