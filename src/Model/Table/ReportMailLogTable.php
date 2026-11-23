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

use App\Lib\Traits\PaginationAndScrollIndexTrait;
use App\Model\Entity\ReportMailLog;
use Cake\Mailer\Mailer;
use Cake\Mailer\Transport\MailTransport;
use Cake\Mailer\Transport\SmtpTransport;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\ReportMailLogFilter;

/**
 * ReportMailLog Model
 *
 * Central log of all report emails (Instant Reports, Autoreports, SLA Reports, ...).
 * One record per sent email.
 *
 * The column "recipients" is mapped to the JSON type, so the entity holds a plain
 * PHP array and the ORM takes care of encoding / decoding.
 *
 * There are intentionally no buildRules() (existsIn): writing a log entry must never
 * fail because a report, user or container has been deleted in the meantime.
 * report_id, report_name and container_id are snapshots at the time of sending.
 *
 * @property \App\Model\Table\ContainersTable&\Cake\ORM\Association\BelongsTo $Containers
 *
 * @method \App\Model\Entity\ReportMailLog newEmptyEntity()
 * @method \App\Model\Entity\ReportMailLog newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ReportMailLog[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ReportMailLog get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ReportMailLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ReportMailLog[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ReportMailLog|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ReportMailLog saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ReportMailLog[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 *
 * @mixin TimestampBehavior
 */
class ReportMailLogTable extends Table {

    use PaginationAndScrollIndexTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('report_mail_log');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->getSchema()->setColumnType('recipients', 'json');

        // LEFT join, as the container may have been deleted after the email was sent
        $this->belongsTo('Containers', [
            'foreignKey' => 'container_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('report_type')
            ->maxLength('report_type', 64)
            ->requirePresence('report_type', 'create')
            ->notEmptyString('report_type');

        $validator
            ->integer('report_id')
            ->requirePresence('report_id', 'create')
            ->greaterThan('report_id', 0);

        // No maxLength() for report_name, subject and message:
        // The validator runs before the entity setters, and the entity truncates
        // these values to the column length (see ReportMailLog::_setReportName() etc.).
        // A long value must not prevent the log entry from being saved.
        $validator
            ->scalar('report_name')
            ->requirePresence('report_name', 'create')
            ->notEmptyString('report_name');

        $validator
            ->integer('container_id')
            ->requirePresence('container_id', 'create')
            ->greaterThan('container_id', 0);

        // [['user_id' => int|null, 'name' => string|null, 'email' => string], ...]
        $validator
            ->array('recipients')
            ->requirePresence('recipients', 'create')
            ->notEmptyArray('recipients');

        $validator
            ->scalar('subject')
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');

        $validator
            ->integer('has_been_sent')
            ->inList('has_been_sent', [0, 1])
            ->notEmptyString('has_been_sent');

        $validator
            ->scalar('message')
            ->allowEmptyString('message');

        $validator
            ->dateTime('report_start')
            ->allowEmptyDateTime('report_start');

        $validator
            ->dateTime('report_end')
            ->allowEmptyDateTime('report_end');

        $validator
            ->dateTime('send_time')
            ->requirePresence('send_time', 'create')
            ->notEmptyDateTime('send_time');

        return $validator;
    }

    /**
     * Returns a short info text for successfully sent emails, describing what "sent" actually means.
     *
     * A successful Mailer::deliver() only means, that the email was accepted by the next mail system
     * (local sendmail / Postfix or the configured SMTP server). It is no proof of final delivery.
     * Example: With the MailTransport (PHP mail()), sendmail exiting with EX_TEMPFAIL (75) is treated as success
     * by PHP, even if Postfix is not configured at all.
     *
     * @param Mailer $Mailer
     * @return string
     */
    public function getDeliveryInfo(Mailer $Mailer): string {
        try {
            $Transport = $Mailer->getTransport();
        } catch (\Throwable $e) {
            return __('Accepted by mail transport. Final delivery is not confirmed.');
        }

        if ($Transport instanceof MailTransport) {
            return __('Accepted by the local mail system. Final delivery is not confirmed.');
        }

        if ($Transport instanceof SmtpTransport) {
            return __('Accepted by SMTP server {0}:{1}. Final delivery is not confirmed.',
                $Transport->getConfig('host'),
                $Transport->getConfig('port')
            );
        }

        return __('Accepted by mail transport {0}. Final delivery is not confirmed.',
            (new \ReflectionClass($Transport))->getShortName()
        );
    }

    /**
     * Returns the mail log of a single report (e.g. all emails of Instant Report #4).
     *
     * Permissions are checked against the container_id that is stored in the log entry
     * (snapshot at the time of sending).
     *
     * @param string $reportType ReportMailLog::REPORT_TYPE_*
     * @param int $reportId
     * @param ReportMailLogFilter $ReportMailLogFilter
     * @param PaginateOMat|null $PaginateOMat
     * @param array $MY_RIGHTS Container ids the user has access to. Empty array = no restriction (root user)
     * @return array
     */
    public function getMailLogByReport(string $reportType, int $reportId, ReportMailLogFilter $ReportMailLogFilter, ?PaginateOMat $PaginateOMat = null, array $MY_RIGHTS = []): array {
        $where = $ReportMailLogFilter->indexFilter();

        // recipients is mapped to the JSON type. Without an explicit string type, the ORM would
        // JSON encode the LIKE value ('"%foo%"'), and the search would never match.
        $recipientsLike = null;
        if (isset($where['ReportMailLog.recipients LIKE'])) {
            $recipientsLike = $where['ReportMailLog.recipients LIKE'];
            unset($where['ReportMailLog.recipients LIKE']);
        }

        $where['ReportMailLog.report_type'] = $reportType;
        $where['ReportMailLog.report_id'] = $reportId;
        $where['ReportMailLog.send_time >='] = date('Y-m-d H:i:s', $ReportMailLogFilter->getFrom());
        $where['ReportMailLog.send_time <='] = date('Y-m-d H:i:s', $ReportMailLogFilter->getTo());

        if (!empty($MY_RIGHTS)) {
            $where['ReportMailLog.container_id IN'] = $MY_RIGHTS;
        }

        $query = $this->unhydratedFind('all')
            ->select([
                'ReportMailLog.id',
                'ReportMailLog.report_type',
                'ReportMailLog.report_id',
                'ReportMailLog.report_name',
                'ReportMailLog.container_id',
                'ReportMailLog.recipients',
                'ReportMailLog.subject',
                'ReportMailLog.has_been_sent',
                'ReportMailLog.message',
                'ReportMailLog.report_start',
                'ReportMailLog.report_end',
                'ReportMailLog.send_time',
                'ReportMailLog.created',
            ])
            ->where($where);

        if ($recipientsLike !== null) {
            $query->andWhere(
                ['ReportMailLog.recipients LIKE' => $recipientsLike],
                ['ReportMailLog.recipients' => 'string']
            );
        }

        $query->orderBy(
            $ReportMailLogFilter->getOrderForPaginator('ReportMailLog.send_time', 'desc')
        );

        if ($PaginateOMat === null) {
            //Just execute query
            return $query->toArray();
        }

        if ($PaginateOMat->useScroll()) {
            return $this->scrollCake4($query, $PaginateOMat->getHandler());
        }
        return $this->paginateCake4($query, $PaginateOMat->getHandler());
    }

    /**
     * Prepares the result of getMailLogByReport() for the API:
     * - adds "user_exists" to every recipient (checked with one query), so the frontend
     *   only links users that have not been deleted in the meantime
     * - formats all dates in the time zone of the user
     * - casts has_been_sent to bool
     *
     * @param array $mailLog
     * @param UserTime $UserTime
     * @return array
     */
    public function formatMailLogForApi(array $mailLog, UserTime $UserTime): array {
        $recipientUserIds = [];
        foreach ($mailLog as $logEntry) {
            foreach ((array)$logEntry['recipients'] as $recipient) {
                if (!empty($recipient['user_id'])) {
                    $recipientUserIds[(int)$recipient['user_id']] = (int)$recipient['user_id'];
                }
            }
        }

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $existingUserIds = $UsersTable->getExistingUserIds($recipientUserIds);

        foreach ($mailLog as $index => $logEntry) {
            $recipients = [];
            foreach ((array)$logEntry['recipients'] as $recipient) {
                $recipient['user_exists'] = !empty($recipient['user_id']) && in_array((int)$recipient['user_id'], $existingUserIds, true);
                $recipients[] = $recipient;
            }
            $mailLog[$index]['recipients'] = $recipients;

            $mailLog[$index]['send_time'] = $UserTime->format($logEntry['send_time']);
            $mailLog[$index]['created'] = $UserTime->format($logEntry['created']);
            $mailLog[$index]['report_start'] = $logEntry['report_start'] !== null ? $UserTime->format($logEntry['report_start']) : null;
            $mailLog[$index]['report_end'] = $logEntry['report_end'] !== null ? $UserTime->format($logEntry['report_end']) : null;
            $mailLog[$index]['has_been_sent'] = (int)$logEntry['has_been_sent'] === ReportMailLog::SENT;
        }

        return $mailLog;
    }
}
