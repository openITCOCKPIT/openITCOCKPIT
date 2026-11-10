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

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CveDecision Entity
 *
 * Decision of a human about one finding: accepted risk, not affected or confirmed and in progress.
 * The decision remembers the state of the finding. Accepted and not affected end as soon as this state changes,
 * in progress only ends with its due date.
 *
 * @property int $id
 * @property int $cve_host_finding_id
 * @property string $state
 * @property string $owner
 * @property string $reason
 * @property \Cake\I18n\Date $review_after
 * @property string|null $evidence_url
 * @property int|null $severity
 * @property string|null $fixed_in
 * @property string|null $fix_state
 * @property bool $not_fixed_yet
 * @property string $policy_version
 * @property int|null $user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\CveHostFinding $cve_host_finding
 * @property \App\Model\Entity\User|null $user
 */
class CveDecision extends Entity {
    public const STATE_ACCEPTED = 'accepted';
    public const STATE_NOT_AFFECTED = 'not_affected';
    public const STATE_IN_PROGRESS = 'in_progress';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Only the fields a user enters. The finding, the state of the finding at the time of the decision,
     * the policy version and the user are set by CveDecisionsTable::decide(), so a request can not bind
     * a decision to another finding or fake the state it was made for.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'state'        => true,
        'owner'        => true,
        'reason'       => true,
        'review_after' => true,
        'evidence_url' => true,
    ];
}
