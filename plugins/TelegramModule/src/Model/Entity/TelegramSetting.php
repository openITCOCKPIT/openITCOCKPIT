<?php

// SPDX-FileCopyrightText: 2021-2026 Timo Triebensky <contact@binsky.org>
//
// SPDX-License-Identifier: MIT

declare(strict_types=1);

namespace TelegramModule\Model\Entity;

use Cake\ORM\Entity;

/**
 * TelegramModule Entity
 *
 * @property int $id
 * @property string $token
 * @property int $last_update_id
 * @property bool $two_way
 * @property string $external_webhook_domain
 * @property string $webhook_api_key
 * @property bool $use_proxy
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 */
class TelegramSetting extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'token'                   => true,
        'last_update_id'          => true,
        'two_way'                 => true,
        'external_webhook_domain' => true,
        'webhook_api_key'         => true,
        'use_proxy'               => true,
        'created'                 => true,
        'modified'                => true,
    ];
}
