<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Agentconfig Entity
 *
 * @property int $id
 * @property int|null $host_id
 * @property bool $use_https
 * @property bool $insecure
 * @property bool $use_autossl
 * @property bool $autossl_successful
 * @property bool $use_push_mode
 * @property bool $basic_auth
 * @property string $password
 * @property bool $proxy
 * @property bool $push_noticed
 * @property string config
 * @property PushAgent $push_agent
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Host $host
 */
class Agentconfig extends Entity {
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
        'port'               => true,
        'host_id'            => true,
        'use_https'          => true,
        'insecure'           => true,
        'use_autossl'        => true,
        'autossl_successful' => true,
        'use_push_mode'      => true,
        'basic_auth'         => true,
        'username'           => true,
        'password'           => true,
        'proxy'              => true,
        'push_noticed'       => true,
        'config'             => true,
        'push_agent'         => true,
        'created'            => true,
        'modified'           => true,
        'host'               => true
    ];

}
