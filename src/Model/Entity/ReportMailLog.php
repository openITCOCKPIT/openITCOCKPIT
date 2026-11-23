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
 * ReportMailLog Entity
 *
 * @property int $id
 * @property string $report_type
 * @property int $report_id
 * @property string $report_name
 * @property int $container_id
 * @property array $recipients [['user_id' => int|null, 'name' => string|null, 'email' => string], ...]
 * @property string $subject
 * @property int $has_been_sent
 * @property string|null $message Error message if not sent, null if sent
 * @property \Cake\I18n\DateTime|null $report_start
 * @property \Cake\I18n\DateTime|null $report_end
 * @property \Cake\I18n\DateTime $send_time
 * @property \Cake\I18n\DateTime|null $created
 *
 * @property \App\Model\Entity\Container $container
 */
class ReportMailLog extends Entity {

    public const REPORT_TYPE_INSTANTREPORT = 'instantreport';
    public const REPORT_TYPE_AUTOREPORT = 'autoreport';
    public const REPORT_TYPE_SLA = 'sla';

    public const NOT_SENT = 0;
    public const SENT = 1;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'report_type'   => true,
        'report_id'     => true,
        'report_name'   => true,
        'container_id'  => true,
        'recipients'    => true,
        'subject'       => true,
        'has_been_sent' => true,
        'message'       => true,
        'report_start'  => true,
        'report_end'    => true,
        'send_time'     => true,
        'created'       => true,
        'container'     => true,
    ];

    /**
     * Truncate values to the column length, so that a too long report name, subject
     * or exception message never prevents the log entry from being saved.
     */

    protected function _setReportName(?string $value): ?string {
        return $this->truncate($value, 255);
    }

    protected function _setSubject(?string $value): ?string {
        return $this->truncate($value, 255);
    }

    protected function _setMessage(?string $value): ?string {
        return $this->truncate($value, 1000);
    }

    /**
     * @return bool
     */
    public function hasBeenSent(): bool {
        return (int)$this->get('has_been_sent') === self::SENT;
    }

    /**
     * Returns all recipient email addresses of this email.
     *
     * @return string[]
     */
    public function getRecipientEmails(): array {
        $emails = [];
        foreach ((array)$this->get('recipients') as $recipient) {
            if (!empty($recipient['email'])) {
                $emails[] = $recipient['email'];
            }
        }
        return $emails;
    }

    /**
     * @param string|null $value
     * @param int $length
     * @return string|null
     */
    private function truncate(?string $value, int $length): ?string {
        if ($value === null) {
            return null;
        }
        return mb_substr($value, 0, $length);
    }
}
