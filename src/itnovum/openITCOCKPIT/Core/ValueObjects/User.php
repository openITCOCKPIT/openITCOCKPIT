<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
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

namespace itnovum\openITCOCKPIT\Core\ValueObjects;

use Authentication\IdentityInterface;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use LasseRafn\InitialAvatarGenerator\InitialAvatar;

class User {
    /**
     * Do not make this public or build a getter method for it.
     * The CronjobUser class (extends this one) has no Identity
     * @var IdentityInterface
     */
    private $Identity;

    /**
     * @var bool
     */
    protected $recursiveBrowser;

    /**
     * @var mixed|null
     */
    protected $fullName;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $dateformat;

    /**
     * @var int
     */
    protected $usergroupId;


    /**
     * User constructor.
     * @param IdentityInterface $Identity
     */
    public function __construct(IdentityInterface $Identity) {
        $this->Identity = $Identity;

        $this->recursiveBrowser = (bool)$Identity->get('recursive_browser');
        $this->fullName = sprintf('%s %s', $Identity->get('firstname'), $Identity->get('lastname'));
        $this->id = (int)$Identity->get('id');
        $this->timezone = $Identity->get('timezone');
        $this->dateformat = $Identity->get('dateformat');
        $this->usergroupId = $Identity->get('usergroup_id');
    }

    /**getUserTime
     * @return boolean
     */
    public function isRecursiveBrowserEnabled() {
        return $this->recursiveBrowser;
    }

    /**
     * @return mixed|null
     */
    public function getFullName() {
        return $this->fullName;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * @return string
     */
    public function getDateformat() {
        return $this->dateformat;
    }

    /**
     * @return UserTime
     */
    public function getUserTime() {
        return new UserTime($this->timezone, $this->dateformat);
    }

    /**
     * PITFALL! ITC-2693
     * The usergroup_id from the Database is maybe just the fallback usergroup of the user.
     * The usergroup of the current Identity can be overwritten by the LdapUsergroupIdMiddleware depending on the
     * LDAP Group a user is member of.
     *
     * So instand of using the usergroup_id from the database, use THIS getUsergroupId method because it uses the
     * usergroupId from the current Identity
     *
     * @return int
     */
    public function getUsergroupId(): int {
        return $this->usergroupId;
    }

    /**
     * @return string|null
     * @deprecated Moved into Angular Frontend
     */
    public function getUserAvatar() {

        $diskPath = WWW_ROOT . 'img' . DS . 'userimages' . DS . 'initial_avatar_' . md5($this->fullName) . '.png';

        $Avatar = new InitialAvatar();
        $image = $Avatar->name($this->fullName)
            ->background('#46c3db')
            ->color('#c5e3f6')
            ->size(200)->generate()
            ->save($diskPath, 100, 'png');
        $userImage = null;

        if (file_exists($diskPath)) {
            $userImage = '/img/userimages' . DS . 'initial_avatar_' . md5($this->fullName) . '.png';
        }

        return $userImage;
    }

}
