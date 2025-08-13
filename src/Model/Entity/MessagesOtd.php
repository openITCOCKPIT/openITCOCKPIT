<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MessagesOtd Entity
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string|null $content
 * @property string $style
 * @property int $user_id
 * @property \Cake\I18n\Date $date
 * @property int $expiration_duration
 * @property int $notify_users
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Usergroup $usergroup
 */
class MessagesOtd extends Entity {
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
        'title'               => true,
        'description'         => true,
        'content'             => true,
        'style'               => true,
        'user_id'             => true,
        'date'                => true,
        'expiration_duration' => true,
        'notify_users'        => true,
        'created'             => true,
        'modified'            => true,
        'user'                => true,
        'usergroups'          => true
    ];
}
