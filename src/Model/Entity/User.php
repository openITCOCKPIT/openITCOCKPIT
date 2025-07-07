<?php
// Copyright (C) <2019>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property int $usergroup_id
 * @property int $status
 * @property string $email
 * @property string $password
 * @property string $firstname
 * @property string $lastname
 * @property string|null $position
 * @property string|null $company
 * @property string|null $phone
 * @property string|null $timezone
 * @property string|null $i18n
 * @property string|null $dateformat
 * @property string|null $image
 * @property string|null $onetimetoken
 * @property string|null $samaccountname
 * @property string|null $ldap_dn
 * @property bool $showstatsinmenu
 * @property int $dashboard_tab_rotation
 * @property int $paginatorlength
 * @property int $recursive_browser
 * @property bool $is_oauth
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property \Cake\I18n\FrozenTime|null last_login
 *
 * @property \App\Model\Entity\Usergroup $usergroup
 * @property \App\Model\Entity\Apikey[] $apikeys
 * @property \App\Model\Entity\Changelog[] $changelogs
 * @property \App\Model\Entity\Contact[] $contacts
 * @property \App\Model\Entity\DashboardTab[] $dashboard_tabs
 * @property \App\Model\Entity\InstantreportsToUser[] $instantreports_to_users
 * @property \App\Model\Entity\MapUpload[] $map_uploads
 * @property \App\Model\Entity\Systemfailure[] $systemfailures
 * @property \App\Model\Entity\UsersToAutoreport[] $users_to_autoreports
 * @property \App\Model\Entity\UsersToContainer[] $users_to_containers
 */
class User extends Entity {

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'usergroup_id'            => true,
        'email'                   => true,
        'password'                => true,
        'firstname'               => true,
        'lastname'                => true,
        'position'                => true,
        'company'                 => true,
        'department'              => true,
        'phone'                   => true,
        'timezone'                => true,
        'i18n'                    => true,
        'dateformat'              => true,
        'image'                   => true,
        'onetimetoken'            => true,
        'samaccountname'          => true,
        'ldap_dn'                 => true,
        'showstatsinmenu'         => true,
        'dashboard_tab_rotation'  => true,
        'paginatorlength'         => true,
        'is_active'               => true,
        'recursive_browser'       => true,
        'created'                 => true,
        'modified'                => true,
        'usergroup'               => true,
        'apikeys'                 => true,
        'changelogs'              => true,
        'contacts'                => true,
        'dashboard_tabs'          => true,
        'instantreports_to_users' => true,
        'map_uploads'             => true,
        'systemfailures'          => true,
        'users_to_autoreports'    => true,
        'users_to_containers'     => true,
        'containers'              => true,
        'usercontainerroles'      => true,
        'is_oauth'                => true,
        'last_login'              => true
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'password'
    ];

}
