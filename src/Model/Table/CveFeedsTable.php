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

use App\Model\Entity\CveFeed;
use Cake\I18n\DateTime;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CveFeeds Model
 *
 * Vuls databases that have been used. Exactly one of them is the current one. If the current database is too old,
 * all hosts turn gray: green would only mean that an outdated database knows no CVEs.
 *
 * @method \App\Model\Entity\CveFeed newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CveFeed patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CveFeed saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CveFeedsTable extends Table {

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('cve_feeds');
        $this->setDisplayField('digest');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->scalar('digest')
            ->maxLength('digest', 255)
            ->requirePresence('digest', 'create')
            ->notEmptyString('digest');

        $validator
            ->dateTime('feed_date')
            ->allowEmptyDateTime('feed_date');

        $validator
            ->boolean('is_current')
            ->notEmptyString('is_current');

        return $validator;
    }

    /**
     * Marks the given Vuls database as the current one.
     *
     * @param string $digest
     * @param DateTime|null $feedDate When the database was built, null keeps a known date
     * @return CveFeed
     */
    public function setCurrentFeed(string $digest, ?DateTime $feedDate = null): CveFeed {
        return $this->getConnection()->transactional(function () use ($digest, $feedDate) {
            $this->updateAll(['is_current' => false], ['is_current' => true, 'digest !=' => $digest]);

            $feed = $this->find()->where(['CveFeeds.digest' => $digest])->first();

            $data = ['digest' => $digest, 'is_current' => true];
            if ($feedDate !== null) {
                $data['feed_date'] = $feedDate;
            }

            $feed = $feed === null ? $this->newEntity($data) : $this->patchEntity($feed, $data);

            return $this->saveOrFail($feed);
        });
    }

    /**
     * @return CveFeed|null
     */
    public function getCurrentFeed(): ?CveFeed {
        return $this->find()
            ->where(['CveFeeds.is_current' => true])
            ->first();
    }

    /**
     * Returns true if there is no current Vuls database, its date is unknown or it is older than the given days.
     *
     * @param int $maxAgeDays
     * @param DateTime|null $now
     * @return bool
     */
    public function isCurrentFeedOutdated(int $maxAgeDays, ?DateTime $now = null): bool {
        $feed = $this->getCurrentFeed();
        if ($feed === null || $feed->feed_date === null) {
            return true;
        }

        return $feed->feed_date->lessThan(($now ?? DateTime::now())->subDays($maxAgeDays));
    }
}
