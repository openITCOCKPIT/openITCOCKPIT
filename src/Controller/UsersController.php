<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\Lib\PluginManager;
use App\Model\Entity\Changelog;
use App\Model\Entity\User;
use App\Model\Table\ChangelogsTable;
use App\Model\Table\ContainersTable;
use App\Model\Table\EventlogsTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsercontainerrolesTable;
use App\Model\Table\UsergroupsTable;
use App\Model\Table\UsersTable;
use Authentication\Authenticator\ResultInterface;
use Authentication\Controller\Component\AuthenticationComponent;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\AngularJS\Api;
use itnovum\openITCOCKPIT\Core\Locales;
use itnovum\openITCOCKPIT\Core\LoginBackgrounds;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;
use itnovum\openITCOCKPIT\Filter\UsersFilter;
use itnovum\openITCOCKPIT\Ldap\LdapClient;
use itnovum\openITCOCKPIT\oAuth\oAuthClient;


/**
 * Class UsersController
 * @package App\Controller
 * @property AuthenticationComponent $Authentication
 */
class UsersController extends AppController {

    public function beforeFilter(EventInterface $event) {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login']);
    }

    public function login() {
        $this->viewBuilder()->setLayout('login');
        $LoginBackgrounds = new LoginBackgrounds();
        $images = $LoginBackgrounds->getImages();

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $isSsoEnabled = $SystemsettingsTable->isOAuth2();
        $redirectToSsoLoginPage = $this->request->getQuery('redirect_sso', null) === 'true';

        $forceRedirectSsousersToLoginScreen = false;
        if ($isSsoEnabled === true) {
            $forceRedirectSsousersToLoginScreen = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.SSO.FORCE_USER_TO_LOGINPAGE')->get('value') === '1';
        }

        // touch /opt/openitc/frontend/src/../DEMO_MODE to enable demo mode
        // In demoe mode, the frontend will autofill the credentials
        $demoFile = APP . '..' . DS . 'DEMO_MODE';
        $demoMode = file_exists($demoFile);

        if ($redirectToSsoLoginPage === true) {
            $oAuthClient = new oAuthClient();

            $authorizationUrl = $oAuthClient->getAuthorizationUrl();
            $session = $this->request->getSession();

            //Save current state to $_SESSION to mitigate CSRF attack
            $session->write('oauth2state', $oAuthClient->getState());

            $this->redirect($authorizationUrl);
            return;
        }

        $disableAnimation = $SystemsettingsTable->isLoginAnimationDisabled();
        if ($this->getRequest()->getQuery('remote')) {
            $disableAnimation = true;
        }

        if ($disableAnimation) {
            $images['particles'] = 'none';
        }

        $disableSocialButtons = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.DISABLE_LOGIN_SOCIAL_BUTTONS')->get('value') === '1';
        $enableColumnLayout = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.ENABLE_COLUMN_LAYOUT_FOR_THE_LOGIN_PAGE')->get('value') === '1';

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        /** @var EventlogsTable $EventlogsTable */
        $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

        $hasValidSslCertificate = false;
        if (isset($_SERVER['SSL_VERIFIED']) && $_SERVER['SSL_VERIFIED'] === 'SUCCESS' && isset($_SERVER['SSL_CERT'])) {
            $hasValidSslCertificate = $this->getUser() !== null;
        }

        $isLoggedIn = $this->getUser() !== null;
        $errorMessages = [];
        $successMessages = [];

        if ($this->request->is('GET') && $this->request->getQuery('code', null) !== null && $isSsoEnabled) {
            // The browser fires two requests.
            // One is a GET request, when the user gets redirected from the oAuth Login Page back to openITCOCKPIT.
            // This is the request where the oAuth Login happens.
            //
            // The second request is also a GET Request done by Angular to query status information.
            // So we need to store any errors to the session that we can return errors for angularjs
            $Session = $this->request->getSession();
            //FileDebugger::dump($this->request->getQuery());
            // The user came back from the oAuth login page.
            // Check if we have any errors during the oAuth login
            $result = $this->Authentication->getResult();
            if ($result->getStatus() !== ResultInterface::SUCCESS) {
                if ($result->getStatus() === 'FAILURE_IDENTITY_NOT_FOUND') {

                    try {
                        $oauth_error = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.SSO.NO_EMAIL_MESSAGE')->get('value');
                    } catch (RecordNotFoundException $e) {
                        // Only a fallback if the key is missing in the database
                        $oauth_error = __('E-Mail address not found in openITCOCKPIT Database.');
                    }
                    $Session->write('oauth_user_not_found', $oauth_error);
                }
            } else {
                $loginData = $result->getData();
                $UsersTable->saveLastLoginDate($loginData['email']);
                $userFromDb = $UsersTable->getUserByEmailForLoginLog($loginData['email']);
                if (!empty($userFromDb)) {

                    $containerIds = Hash::extract($userFromDb, 'containers.{n}.id');

                    $containerRoleContainerIds = $UsersTable->getContainerIdsOfUserContainerRoles(['User' => $userFromDb]);
                    $containerIds = array_merge($containerIds, $containerRoleContainerIds);

                    $loginData = $EventlogsTable->createDataJsonForUser($userFromDb->get('email'));
                    $fullName = $userFromDb->get('firstname') . ' ' . $userFromDb->get('lastname');
                    $EventlogsTable->saveNewEntity('login', 'User', $userFromDb->id, $fullName, $loginData, $containerIds);
                }
                $Session->write('oauth_login_successful', 'Login via Single sign-on successful');
                $this->redirect('/a/users/login', 302);
            }
        }

        $this->set('_csrfToken', $this->request->getParam('_csrfToken'));
        $this->set('images', $images);
        $this->set('disableAnimation', $disableAnimation);
        $this->set('disableSocialButtons', $disableSocialButtons);
        $this->set('enableColumnLayout', $enableColumnLayout);
        $this->set('hasValidSslCertificate', $hasValidSslCertificate);
        $this->set('isSsoEnabled', $isSsoEnabled);
        $this->set('forceRedirectSsousersToLoginScreen', $forceRedirectSsousersToLoginScreen);
        $this->set('isLoggedIn', $isLoggedIn);
        $this->set('demoMode', $demoMode);

        if ($this->request->is('get')) {
            if ($this->isJsonRequest()) {
                $Session = $this->request->getSession();
                if ($Session->read('oauth_user_not_found', null) !== null) {
                    $errorMessages[] = $Session->read('oauth_user_not_found');
                    $Session->delete('oauth_user_not_found');
                }
                if ($Session->read('oauth_login_successful', null) !== null) {
                    $successMessages[] = $Session->read('oauth_login_successful');
                    $Session->delete('oauth_login_successful');
                }
            }

            // Add URL for (custom) logo
            $Logo = new Logo();
            $this->set('logoUrl', $Logo->getLoginLogoHtml());
            $this->set('isCustomLoginBackground', $Logo->isCustomLoginBackground());
            $this->set('customLoginBackgroundHtml', $Logo->getCustomLoginBackgroundHtml());

            $this->set('errorMessages', $errorMessages);
            $this->set('successMessages', $successMessages);

            $this->viewBuilder()->setOption('serialize', ['_csrfToken', 'logoUrl', 'images', 'hasValidSslCertificate', 'isLoggedIn', 'isSsoEnabled', 'forceRedirectSsousersToLoginScreen', 'errorMessages', 'successMessages', 'isCustomLoginBackground', 'customLoginBackgroundHtml', 'disableAnimation', 'disableSocialButtons', 'enableColumnLayout', 'demoMode']);
            return;
        }

        if ($this->request->is('post')) {
            // Docs: https://book.cakephp.org/authentication/1/en/index.html

            $result = $this->Authentication->getResult();
            if ($result->getStatus() === ResultInterface::SUCCESS) {
                $this->set('success', true);
                $loginData = $this->request->getData();
                $UsersTable->saveLastLoginDate($loginData['email']);
                $userFromDb = $UsersTable->getUserByEmailForLoginLog($loginData['email']);
                if (!empty($userFromDb)) {

                    $containerIds = Hash::extract($userFromDb, 'containers.{n}.id');

                    $containerRoleContainerIds = $UsersTable->getContainerIdsOfUserContainerRoles(['User' => $userFromDb]);
                    $containerIds = array_merge($containerIds, $containerRoleContainerIds);

                    $loginData = $EventlogsTable->createDataJsonForUser($userFromDb->get('email'));
                    $fullName = $userFromDb->get('firstname') . ' ' . $userFromDb->get('lastname');
                    $EventlogsTable->saveNewEntity('login', 'User', $userFromDb->id, $fullName, $loginData, $containerIds);
                }
                $this->viewBuilder()->setOption('serialize', ['success']);
                return;
            }

            $this->RequestHandler->renderAs($this, 'json');
            $this->response = $this->response->withStatus(400);
            $errors = $result->getErrors();
            $errors['Password'][] = __('Invalid username or password');

            $this->set('success', false);
            $this->set('errors', $errors);
            $this->set('_csrfToken', $this->request->getParam('_csrfToken'));
            $this->viewBuilder()->setOption('serialize', ['success', 'errors', '_csrfToken']);
        }
    }

    public function logout() {
        $Session = $this->request->getSession();
        $identity = $this->getUser();
        $userId = $identity->get('id');
        $isOAuthLogin = $Session->read('is_oauth_login') === true;
        $Session->delete('is_oauth_login');
        $Session->delete('MessageOtd.showMessage');
        Cache::delete('ltc_ldap_usergroup_id_for_' . $userId, 'long_time_cache');
        Cache::delete('userPermissions_' . $userId, 'permissions');

        $this->Authentication->logout();

        if ($isOAuthLogin === true) {
            $oAuthClient = new oAuthClient();
            if ($oAuthClient->hasLogoutUrl()) {
                $this->redirect($oAuthClient->getLogoutUrl());
            }
            return;
        }


        $this->redirect('/a/users/login');
    }

    public function index() {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            $this->set('isLdapAuth', $SystemsettingsTable->isLdapAuth());
            return;
        }

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $UsersFilter = new UsersFilter($this->request);
        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $UsersFilter->getPage());

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            // root users can see all users
            $MY_RIGHTS = [];
        }
        $all_tmp_users = $UsersTable->getUsersIndex($UsersFilter, $PaginateOMat, $MY_RIGHTS);
        $all_users = [];

        $types = $UsersTable->getUserTypesWithStyles();
        $UserTime = $User->getUserTime();
        foreach ($all_tmp_users as $_user) {
            /** @var User $_user */
            $user = $_user->toArray();
            if (!empty($user['last_login'])) {
                $user['last_login'] = $UserTime->format($user['last_login']->getTimestamp());
            }
            $user['allow_edit'] = $this->hasRootPrivileges;
            if (!empty($user['samaccountname'])) {
                $user['UserTypes'] = [$types['LDAP_USER']];
                if ($user['is_oauth'] === true) {
                    $user['UserTypes'][] = $types['OAUTH_USER'];
                }
            } else if ($user['is_oauth'] === true) {
                $user['UserTypes'] = [$types['OAUTH_USER']];
            } else {
                $user['UserTypes'] = [$types['LOCAL_USER']];
            }
            if ($this->hasRootPrivileges === false) {
                //Check permissions for non ROOT Users
                $containerWithWritePermissionByUserContainerRoles = Hash::combine(
                    $user['usercontainerroles'],
                    '{n}.containers.{n}._joinData.container_id',
                    '{n}.containers.{n}._joinData.permission_level'
                );

                $notPermittedContainer = array_filter($containerWithWritePermissionByUserContainerRoles, function ($v, $k) {
                    return (!isset($this->MY_RIGHTS_LEVEL[$k]) || (isset($this->MY_RIGHTS_LEVEL[$k]) && $this->MY_RIGHTS_LEVEL[$k] < $v));

                }, ARRAY_FILTER_USE_BOTH);
                if (!empty($notPermittedContainer)) {
                    $user['allow_edit'] = false;
                } else {
                    $containerWithWritePermissionByUserContainerRoles = array_keys($containerWithWritePermissionByUserContainerRoles);

                    $container = Hash::extract(
                        $user['containers'],
                        '{n}.id'
                    );

                    $container = array_unique(array_merge($container, $containerWithWritePermissionByUserContainerRoles));
                    foreach ($container as $containerId) {
                        if ($this->isWritableContainer($containerId)) {
                            $user['allow_edit'] = true;
                            break;
                        }
                    }
                }
            }
            $all_users[] = $user;
        }

        $this->set('isLdapAuth', $SystemsettingsTable->isLdapAuth());
        $this->set('all_users', $all_users);
        $this->set('myUserId', $User->getId());
        $this->viewBuilder()->setOption('serialize', ['all_users', 'myUserId', 'isLdapAuth']);
    }

    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular

            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $this->set('isOAuth2', $SystemsettingsTable->isOAuth2());
            return;
        }

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if ($this->request->is('post') || $this->request->is('put')) {

            $data = $this->request->getData('User', []);
            if (!isset($data['ContainersUsersMemberships'])) {
                $data['ContainersUsersMemberships'] = [];
            }
            $data['containers'] = $UsersTable->containerPermissionsForSave(
                $data['ContainersUsersMemberships'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );

            $user = $UsersTable->newEmptyEntity();
            $user = $UsersTable->patchEntity($user, $data);

            if ($user->is_oauth === true) {
                //remove password validation when user is an oAuth2 user.
                $user->password = '';
                $user->confirm_password = '';
                $UsersTable->getValidator()->remove('password');
                $UsersTable->getValidator()->remove('confirm_password');
            }

            $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

            $data = [
                'User' => $data
            ];

            $user = $UsersTable->createUser($user, $data, $User->getId());
            if ($user->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $user->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            Cache::clear('permissions');
            $this->set('user', $user);
            $this->viewBuilder()->setOption('serialize', ['user']);
        }
    }

    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        if (!$UsersTable->existsById($id)) {
            throw new NotFoundException(__('User not found'));
        }

        $user = $UsersTable->getUserForEdit($id);
        $userForChangelog = $user;
        $containersToCheck = array_unique(
            array_merge(
                $user['User']['usercontainerroles_containerids']['_ids'], //Container Ids through Container Roles
                $user['User']['containers']['_ids'] //Containers defined by the user itself
            )
        );

        $notPermittedContainerIds = [];
        foreach ($user['User']['ContainersUsersMemberships'] as $containerId => $rightLevel) {
            if (!isset($this->MY_RIGHTS_LEVEL[$containerId]) || (isset($this->MY_RIGHTS_LEVEL[$containerId]) && $this->MY_RIGHTS_LEVEL[$containerId] < $rightLevel)) {
                $notPermittedContainerIds[] = $containerId;
            }
        }

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        $userContainerRolesReadonly = !$this->hasRootPrivileges;
        if (!$this->hasRootPrivileges) {
            $containersWithWriteRights = $this->getWriteContainers();
            $allowedUserContainerRoles = $UsercontainerrolesTable->getUsercontainerrolesForPermissionCheck($containersWithWriteRights);
            $allowedUserContainerRolesIds = Hash::extract(
                $allowedUserContainerRoles,
                '{n}.id'
            );

            $userContainerRolesReadonly = !empty(
            array_diff(
                array_merge($user['User']['usercontainerroles']['_ids'], $user['User']['usercontainerroles_ldap']['_ids']),
                $allowedUserContainerRolesIds
            ));
        }

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());
        $UserTime = $User->getUserTime();
        foreach ($user['User']['apikeys'] as $i => $apikey) {
            if (isset($apikey['last_use']) && $apikey['last_use'] instanceof FrozenTime) {
                $user['User']['apikeys'][$i]['last_use'] = $UserTime->format($apikey['last_use']->getTimestamp());
            }
        }

        $isLdapUser = !empty($user['User']['samaccountname']);

        $types = $UsersTable->getUserTypesWithStyles();
        if ($isLdapUser) {
            $UserTypes = [$types['LDAP_USER']];
            if ($user['User']['is_oauth'] === true) {
                $UserTypes[] = $types['OAUTH_USER'];
            }
        } else if ($user['User']['is_oauth'] === true) {
            $UserTypes = [$types['OAUTH_USER']];
        } else {
            $UserTypes = [$types['LOCAL_USER']];
        }
        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return user information
            $this->set('user', $user['User']);
            $this->set('isLdapUser', $isLdapUser);
            $this->set('UserTypes', $UserTypes);
            $this->set('userContainerRolesReadonly', $userContainerRolesReadonly);
            $this->set('notPermittedContainerIds', array_map('intval', $notPermittedContainerIds)); // Make sure its a int array for Angular
            $this->viewBuilder()->setOption('serialize', ['user', 'isLdapUser', 'UserTypes', 'notPermittedContainerIds', 'userContainerRolesReadonly']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('User', []);
            if (!isset($data['ContainersUsersMemberships'])) {
                $data['ContainersUsersMemberships'] = [];
            }

            if (!$this->hasRootPrivileges) {
                $containerIdsWithWritePermissions = array_filter($this->MY_RIGHTS_LEVEL, function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);
                $userToEditContainerIdsWithWritePermissions = array_filter($data['ContainersUsersMemberships'], function ($v) {
                    return $v == WRITE_RIGHT;
                }, ARRAY_FILTER_USE_BOTH);

                $notPermittedContainerIds = array_keys(
                    array_diff_key($userToEditContainerIdsWithWritePermissions, $containerIdsWithWritePermissions)
                );

                foreach ($data['ContainersUsersMemberships'] as $key => $value) {
                    // do not overwrite container settings if the user does not have sufficient rights
                    if (in_array($key, $notPermittedContainerIds, true)) {
                        continue;
                    }
                    // reverting write permission to read permission due to insufficient user permission rights
                    if ($key !== ROOT_CONTAINER && !array_key_exists($key, $containerIdsWithWritePermissions) && $value > 1) {
                        $data['ContainersUsersMemberships'][$key] = READ_RIGHT;
                    }
                }
            }
            $data['containers'] = $UsersTable->containerPermissionsForSave(
                $data['ContainersUsersMemberships'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );
            $user = $UsersTable->get($id);
            $user->setAccess('id', false);

            if ($userContainerRolesReadonly) {
                $user->setAccess('usercontainerroles', false);
            }

            if ($isLdapUser) {
                $data['is_ldap'] = true;
                $user->setAccess('email', false);
                $user->setAccess('firstname', false);
                $user->setAccess('lastname', false);
                $user->setAccess('company', false);
                $user->setAccess('department', false);
                $user->setAccess('password', false);
                $user->setAccess('samaccountname', false);
                $user->setAccess('ldap_dn', false);

                // Get User Container Roles from LDAP groups
                /** @var SystemsettingsTable $SystemsettingsTable */
                $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
                $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));
                $ldapUser = $Ldap->getUser($data['samaccountname'], true);

                /** @var UsercontainerrolesTable $UsercontainerrolesTable */
                $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

                $userContainerRoleContainerPermissionsLdap = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                    $ldapUser['memberof']
                );

                // Convert old belongsToMany request into through join Membership data. (Only required for LDAP users to set through_ldap field in users_to_usercontainerroles join talbe)
                $usercontainerroles = $data['usercontainerroles']['_ids'] ?? [];
                $data['usercontainerroles'] = [];

                // Add user container roles that are assigned via LDAP
                foreach ($userContainerRoleContainerPermissionsLdap as $usercontainerrole) {
                    $usercontainerroleId = $usercontainerrole['id'];
                    $data['usercontainerroles'][$usercontainerroleId] = [
                        'id'        => $usercontainerroleId,
                        '_joinData' => [
                            'through_ldap' => true // This got assigned automatically via LDAP
                        ]
                    ];
                    $userForChangelog['User']['usercontainerroles'][$usercontainerroleId] = [
                        'id'        => $usercontainerroleId,
                        '_joinData' => [
                            'through_ldap' => true // This got assigned automatically via LDAP
                        ]
                    ];
                }

                foreach ($usercontainerroles as $usercontainerroleId) {
                    // Use the ID to be able to overwrite automatically assignments done by LDAP
                    $data['usercontainerroles'][$usercontainerroleId] = [
                        'id'        => $usercontainerroleId,
                        '_joinData' => [
                            'through_ldap' => false // This user container role got selected by the user
                        ]
                    ];
                }
            }

            if ($user->is_oauth === true) {
                $user->setAccess('is_oauth', false); //do not allow to change is_oauth
                //oAuth users has no password
                $data['password'] = '';
                $data['confirm_password'] = '';
            }
            //prevent multiple hash of password
            if ($data['password'] === '' && $data['confirm_password'] === '') {
                unset($data['password']);
                unset($data['confirm_password']);
            }

            $Hasher = $UsersTable->getDefaultPasswordHasher();
            $passwordHasChanged = false;
            if (array_key_exists('password', $data) && !empty($data['password'])) {
                $passwordHasChanged = $Hasher->check($data['password'], $user->get('password')) !== true;
            }

            $user = $UsersTable->patchEntity($user, $data);

            $data = [
                'User' => $data
            ];

            $user = $UsersTable->updateUser(
                $user,
                $data,
                $userForChangelog,
                $User->getId(),
                $passwordHasChanged
            );
            if ($user->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $user->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            if ($passwordHasChanged) {
                /** @var EventlogsTable $EventlogsTable */
                $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

                $containerIds = Hash::extract($data, 'User.containers.{n}.id');

                $containerRoleContainerIds = $UsersTable->getContainerIdsOfUserContainerRoles($data);
                $containerIds = array_merge($containerIds, $containerRoleContainerIds);

                $eventlogData = $EventlogsTable->createDataJsonForUser($user->get('email'));
                $fullName = $user->get('firstname') . ' ' . $user->get('lastname');
                $EventlogsTable->saveNewEntity('user_password_change', 'User', $user->id, $fullName, $eventlogData, $containerIds);
            }

            Cache::clear('permissions');
            $this->set('user', $user);
            $this->viewBuilder()->setOption('serialize', ['user']);
        }
    }


    /**
     * @param int|null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        if ($id == $User->getId()) {
            throw new \RuntimeException('You cannot delete yourself!');
        }

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if (!$UsersTable->existsById($id)) {
            throw new NotFoundException(__('User not found'));
        }

        $user = $UsersTable->getUserForPermissionCheck($id);
        $userForLog = $UsersTable->getUserById($id);
        $containersToCheck = array_unique(array_merge(
            $user['usercontainerroles_containerids']['_ids'], //Container Ids through Container Roles
            $user['containers']['_ids']) //Containers defined by the user itself
        );

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        /** @var EventlogsTable $EventlogsTable */
        $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

        $user = $UsersTable->get($id);

        if ($UsersTable->delete($user)) {

            $containerIds = Hash::extract($userForLog, 'containers.{n}.id');

            $containerRoleContainerIds = $UsersTable->getContainerIdsOfUserContainerRoles(['User' => $userForLog]);
            $containerIds = array_merge($containerIds, $containerRoleContainerIds);

            $eventlogData = $EventlogsTable->createDataJsonForUser($userForLog['email']);
            $fullName = $userForLog['firstname'] . ' ' . $userForLog['lastname'];
            $EventlogsTable->saveNewEntity('user_delete', 'User', $userForLog['id'], $fullName, $eventlogData, $containerIds);

            /** @var  ChangelogsTable $ChangelogsTable */
            $ChangelogsTable = TableRegistry::getTableLocator()->get('Changelogs');

            $changelog_data = $ChangelogsTable->parseDataForChangelog(
                'delete',
                'users',
                $id,
                OBJECT_USER,
                $containerIds,
                $User->getId(),
                $fullName,
                $userForLog
            );
            if ($changelog_data) {
                /** @var Changelog $changelogEntry */
                $changelogEntry = $ChangelogsTable->newEntity($changelog_data);
                $ChangelogsTable->save($changelogEntry);

            }

            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);

            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
        return;
    }

    public function addFromLdap() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular

            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $this->set('isOAuth2', $SystemsettingsTable->isOAuth2());
            return;
        }

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if ($this->request->is('post') || $this->request->is('put')) {

            $data = $this->request->getData('User', []);

            if (empty($data['samaccountname'])) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', [
                    'samaccountname' => [
                        '_empty' => __('This field cannot be left empty')
                    ]
                ]);
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            $data['is_ldap'] = true;
            if (!isset($data['ContainersUsersMemberships'])) {
                $data['ContainersUsersMemberships'] = [];
            }
            $data['containers'] = $UsersTable->containerPermissionsForSave(
                $data['ContainersUsersMemberships'],
                $this->hasRootPrivileges,
                $this->MY_RIGHTS_LEVEL
            );

            // Get User Container Roles from LDAP groups

            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));
            $ldapUser = $Ldap->getUser($data['samaccountname'], true);

            /** @var UsercontainerrolesTable $UsercontainerrolesTable */
            $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

            $userContainerRoleContainerPermissionsLdap = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                $ldapUser['memberof']
            );


            // Convert old belongsToMany request into through join Membership data.
            $usercontainerroles = $data['usercontainerroles']['_ids'] ?? [];
            $data['usercontainerroles'] = [];

            // Add user container roles that are assigned via LDAP
            foreach ($userContainerRoleContainerPermissionsLdap as $usercontainerrole) {
                $usercontainerroleId = $usercontainerrole['id'];
                $data['usercontainerroles'][$usercontainerroleId] = [
                    'id'        => $usercontainerroleId,
                    '_joinData' => [
                        'through_ldap' => true // This got assigned automatically via LDAP
                    ]
                ];
            }

            foreach ($usercontainerroles as $usercontainerroleId) {
                // Use the ID to be able to overwrite automatically assignments done by LDAP
                $data['usercontainerroles'][$usercontainerroleId] = [
                    'id'        => $usercontainerroleId,
                    '_joinData' => [
                        'through_ldap' => false // This user container role got selected by the user
                    ]
                ];
            }

            //remove password validation when user is imported from ldap
            $UsersTable->getValidator()->remove('password');
            $UsersTable->getValidator()->remove('confirm_password');

            $user = $UsersTable->newEmptyEntity();
            $user = $UsersTable->patchEntity($user, $data);
            $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

            $data = [
                'User' => $data
            ];

            $user = $UsersTable->createUser($user, $data, $User->getId());
            if ($user->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $user->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }


            Cache::clear('permissions');
            $this->set('user', $user);
            $this->viewBuilder()->setOption('serialize', ['user']);
        }
    }

    /****************************
     *       AJAX METHODS       *
     ****************************/

    /**
     * @param int|null $id
     */
    public function resetPassword($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if (!$UsersTable->existsById($id)) {
            throw new NotFoundException(__('User not found'));
        }

        $user = $UsersTable->getUserForPermissionCheck($id);
        $containersToCheck = array_unique(array_merge(
            $user['usercontainerroles_containerids']['_ids'], //Container Ids through Container Roles
            $user['containers']['_ids']) //Containers defined by the user itself
        );

        if (!$this->allowedByContainerId($containersToCheck)) {
            $this->render403();
            return;
        }

        $user = $UsersTable->get($id);
        $userForLog = [
            'User' => $UsersTable->getUserById($id)
        ];
        $newPassword = $UsersTable->generatePassword();

        $user->set('password', $newPassword);

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();

        $Logo = new Logo();

        $Mailer = new Mailer();
        $Mailer->setFrom($systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'], $systemsettings['MONITORING']['MONITORING.FROM_NAME']);
        $Mailer->addTo($user->get('email'));
        $Mailer->setSubject(__('Your ') . $systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'] . __(' got reset!'));
        $Mailer->setEmailFormat('text');
        $Mailer->setAttachments([
            'logo.png' => [
                'file'      => $Logo->getSmallLogoPdfPath(),
                'mimetype'  => 'image/png',
                'contentId' => '100'
            ]
        ]);
        $Mailer->viewBuilder()
            ->setTemplate('reset_password')
            ->setVar('systemname', $systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'])
            ->setVar('newPassword', $newPassword);

        $user->set('password', $newPassword);

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        $data = [
            'User' => $user->toArray()
        ];

        $user = $UsersTable->updateUser(
            $user,
            $data,
            $userForLog,
            $User->getId(),
            true
        );
        if ($user->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $user->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        /** @var EventlogsTable $EventlogsTable */
        $EventlogsTable = TableRegistry::getTableLocator()->get('Eventlogs');

        $containerIds = Hash::extract($userForLog, 'User.containers.{n}.id');

        $containerRoleContainerIds = $UsersTable->getContainerIdsOfUserContainerRoles($userForLog);
        $containerIds = array_merge($containerIds, $containerRoleContainerIds);

        $eventlogData = $EventlogsTable->createDataJsonForUser($user->get('email'));
        $fullName = $user->get('firstname') . ' ' . $user->get('lastname');
        $EventlogsTable->saveNewEntity('user_password_change', 'User', $user->id, $fullName, $eventlogData, $containerIds);

        $Mailer->deliver();
        $this->set('message', __('Password reset successfully. The new password was send to {0}', $user->email));
        $this->viewBuilder()->setOption('serialize', ['message']);

    }


    public function listToCsv() {
        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $UsersFilter = new UsersFilter($this->request);

        $MY_RIGHTS = $this->MY_RIGHTS;
        if ($this->hasRootPrivileges) {
            // root users can see all users
            $MY_RIGHTS = [];
        }
        $all_tmp_users = $UsersTable->getUsersIndex($UsersFilter, null, $MY_RIGHTS);
        $all_users = [];

        $UserTime = $User->getUserTime();
        foreach ($all_tmp_users as $_user) {
            /** @var User $_user */
            $user = $_user->toArray();
            if (!empty($user['last_login'])) {
                $user['last_login'] = $UserTime->format($user['last_login']->getTimestamp());
            }
            $user['isLdapUser'] = __('No');
            $user['isOAuthUser'] = __('No');
            if (!empty($user['samaccountname'])) {
                $user['isLdapUser'] = __('Yes');
                if ($user['is_oauth'] === true) {
                    $user['isOAuthUser'] = __('Yes');
                }
            } else if ($user['is_oauth'] === true) {
                $user['isOAuthUser'] = __('Yes');
            }

            $all_users[] = [
                $user['full_name'],
                $user['id'],
                $user['email'],
                $user['phone'],
                $user['company'],
                $user['is_active'],
                $user['isLdapUser'],
                $user['isOAuthUser'],
                $user['usergroup']['name'],
                $user['last_login']
            ];
        }


        $header = [
            'full_name',
            'user_id',
            'email',
            'phone',
            'company',
            'is_active',
            'is_ldap_user',
            'is_oauth_user',
            'user_role',
            'last_login'
        ];


        $this->set('data', $all_users);

        $filename = __('Users_') . date('dmY_his') . '.csv';
        $this->setResponse($this->getResponse()->withDownload($filename));
        $this->viewBuilder()
            ->setClassName('CsvView.Csv')
            ->setOptions([
                'delimiter' => ';', // Excel prefers ; over ,
                'bom'       => true, // Fix UTF-8 umlauts in Excel
                'serialize' => 'data',
                'header'    => $header,
            ]);
    }


    /**
     * @throws \FreeDSx\Ldap\Exception\BindException
     */
    public function loadLdapUserByString() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));

        $samaccountname = (string)$this->request->getQuery('samaccountname', '');
        $ldapUsers = $Ldap->getUsers($samaccountname);
        $this->set('ldapUsers', $ldapUsers);
        $this->viewBuilder()->setOption('serialize', ['ldapUsers']);
    }

    public function loadUsersByContainerId() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        /** @var ContainersTable $ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $containerId = (int)$this->request->getQuery('containerId', 0);
        // Due to the only filter condition is the container_id (no LIMIT or LIKE in SQL )
        // I think the selected parameter is not required
        //$selected = $this->request->getQuery('selected', []);

        if ($containerId === 0) {
            //Missing parameter in URL
            $containerId = ROOT_CONTAINER;
        }
        $containerIds = $ContainersTable->resolveChildrenOfContainerIds($containerId);
        $users = Api::makeItJavaScriptAble(
            $UsersTable->getUsersByContainerIds($containerIds, 'list')
        );

        $this->set('users', $users);
        $this->viewBuilder()->setOption('serialize', ['users']);
    }

    public function loadUsergroups() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UsergroupsTable $UsergroupsTable */
        $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

        $_usergroups = $UsergroupsTable->find('list')->toArray();

        //Rewrite hashmap to array because javascript will break the "orderd hashmap" because hashmaps have no order
        $usergroups = [];
        foreach ($_usergroups as $id => $name) {
            $usergroups[] = [
                'key'   => $id,
                'value' => $name
            ];
        }

        $this->set('usergroups', $usergroups);
        $this->viewBuilder()->setOption('serialize', ['usergroups']);
    }

    public function loadDateformats() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }
        /** @var UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $dateformats = $UsersTable->getDateformats();
        $options = [];

        $User = new \itnovum\openITCOCKPIT\Core\ValueObjects\User($this->getUser());

        foreach ($dateformats as $dateformat) {
            $UserTime = new UserTime($User->getTimezone(), $dateformat);
            $options[$dateformat] = $UserTime->format(time());
        }
        $dateformats = Api::makeItJavaScriptAble($options);
        $defaultDateFormat = 'H:i:s - d.m.Y'; // key 10

        $timezones = \itnovum\openITCOCKPIT\Core\Timezone::listTimezones();

        $this->set('dateformats', $dateformats);
        $this->set('defaultDateFormat', $defaultDateFormat);
        $this->set('timezones', $timezones);
        $this->set('serverTimeZone', date_default_timezone_get());
        $this->set('serverTime', date('d.m.Y H:i:s'));
        $this->viewBuilder()->setOption('serialize', ['dateformats', 'defaultDateFormat', 'timezones', 'serverTime', 'serverTimeZone']);
    }

    public function loadContainerRoles() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'Usercontainerroles.name'
            ]
        ]);
        $selected = $this->request->getQuery('selected', []);
        $userContainerIds = $this->MY_RIGHTS;
        if (!$this->hasRootPrivileges) {
            $userContainerIds = array_filter($this->MY_RIGHTS_LEVEL, function ($v) {
                return $v == WRITE_RIGHT;
            }, ARRAY_FILTER_USE_BOTH);
            $userContainerIds = array_keys($userContainerIds);
        }
        $ucr = Api::makeItJavaScriptAble(
            $UsercontainerrolesTable->getUsercontainerrolesAsList(
                $GenericFilter,
                $selected,
                $userContainerIds
            )
        );
        $filteredUserContainerRoles = [];

        if (!$this->hasRootPrivileges && !empty($ucr)) {
            $usercontainerRoleIdsToCheck = Hash::extract($ucr, '{n}.key');
            $userContainerRolesWithContainerIDs = $UsercontainerrolesTable->getUsercontanerRoleWithAllContainerIdsByIds(
                $usercontainerRoleIdsToCheck
            );
            // clean up user container roles
            foreach ($ucr as $userContainerRole) {
                if (isset($userContainerRolesWithContainerIDs[$userContainerRole['key']])) {
                    //Check if user has rights to see all containers in container user role
                    if (empty(array_diff($userContainerRolesWithContainerIDs[$userContainerRole['key']], $userContainerIds))) {
                        $filteredUserContainerRoles[] = $userContainerRole;
                    }
                }
            }
            $ucr = $filteredUserContainerRoles;
        }

        $this->set('usercontainerroles', $ucr);
        $this->viewBuilder()->setOption('serialize', ['usercontainerroles']);
    }

    public function loadContainerPermissions() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $usercontainerRoleIds = $this->request->getQuery('usercontainerRoleIds', null);
        if ($usercontainerRoleIds === null) {
            $usercontainerRoleIds = [];
        }
        if (!is_array($usercontainerRoleIds)) {
            $usercontainerRoleIds = [$usercontainerRoleIds];
        }

        /** @var UsercontainerrolesTable $UsercontainerrolesTable */
        $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');
        $containerPermissions = $UsercontainerrolesTable->getContainerPermissionsByUserContainerRoleIds($usercontainerRoleIds);

        //Merge the permissions of all user container roles together.
        //WRITE_RIGHT will overwrite a READ_RIGHT

        $permissions = [];
        foreach ($containerPermissions as $userContainerRole) {
            foreach ($userContainerRole['containers'] as $container) {
                if (isset($permissions[$container['id']])) {
                    //Container permission is already set.
                    //Only overwrite it, if it is a WRITE_RIGHT
                    if ($container['_joinData']['permission_level'] === WRITE_RIGHT) {
                        $permissions[$container['id']] = $container;
                    }
                } else {
                    //Container is not yet in permissions - add it
                    $permissions[$container['id']] = $container;
                }
                $permissions[$container['id']]['user_roles'][$userContainerRole['id']] = [
                    'id'   => $userContainerRole['id'],
                    'name' => $userContainerRole['name']
                ];
            }
        }

        $this->set('userContainerRoleContainerPermissions', $permissions);
        $this->viewBuilder()->setOption('serialize', ['userContainerRoleContainerPermissions']);
    }

    public function getLocaleOptions() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $localesPath = Configure::read('App.paths.locales')[0];
        $definedLocalCodes = Locales::getLocalCodesFromDefinedLanguages();
        $localeOptions = [];
        $localeDirs = array_filter(glob($localesPath . '*'), 'is_dir');
        array_walk($localeDirs, function ($value, $key) use (&$localeOptions, $localesPath, $definedLocalCodes) {
            $i18n = substr($value, strlen($localesPath));
            if (in_array($i18n, $definedLocalCodes, true)) {
                $language = Locales::getLanguageByLocalCode($i18n);
                $localeOptions[] = [
                    'i18n' => $i18n,
                    'name' => $language['label']
                ];
            }
        });
        $this->set('localeOptions', $localeOptions);
        $this->viewBuilder()->setOption('serialize', ['localeOptions']);
    }

    public function loadLdapUserDetails() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));

        $samaccountname = (string)$this->request->getQuery('samaccountname', '');
        $ldapUser = null;
        if (!empty($samaccountname)) {
            $ldapUser = $Ldap->getUser($samaccountname, true);
            if ($ldapUser) {
                /** @var UsercontainerrolesTable $UsercontainerrolesTable */
                $UsercontainerrolesTable = TableRegistry::getTableLocator()->get('Usercontainerroles');

                $ldapUser['userContainerRoleContainerPermissionsLdap'] = $UsercontainerrolesTable->getContainerPermissionsByLdapUserMemberOf(
                    $ldapUser['memberof']
                );

                $permissions = [];
                foreach ($ldapUser['userContainerRoleContainerPermissionsLdap'] as $userContainerRole) {
                    foreach ($userContainerRole['containers'] as $container) {
                        if (isset($permissions[$container['id']])) {
                            //Container permission is already set.
                            //Only overwrite it, if it is a WRITE_RIGHT
                            if ($container['_joinData']['permission_level'] === WRITE_RIGHT) {
                                $permissions[$container['id']] = $container;
                            }
                        } else {
                            //Container is not yet in permissions - add it
                            $permissions[$container['id']] = $container;
                        }
                        $permissions[$container['id']]['user_roles'][$userContainerRole['id']] = [
                            'id'   => $userContainerRole['id'],
                            'name' => $userContainerRole['name']
                        ];
                    }
                }
                $ldapUser['userContainerRoleContainerPermissionsLdap'] = $permissions;

                // Load matching user role (Adminisgtrator, Viewer, etc...)
                /** @var UsergroupsTable $UsergroupsTable */
                $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');
                $ldapUser['usergroupLdap'] = $UsergroupsTable->getUsergroupByLdapUserMemberOf($ldapUser['memberof']);
            }
        }
        $this->set('ldapUser', $ldapUser);
        $this->viewBuilder()->setOption('serialize', ['ldapUser']);
    }

    public function loadContainersForAngular() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        /** @var $ContainersTable ContainersTable */
        $ContainersTable = TableRegistry::getTableLocator()->get('Containers');

        $containers = $ContainersTable->easyPath(
            $this->MY_RIGHTS,
            OBJECT_HOST, [],
            $this->hasRootPrivileges,
            [CT_HOSTGROUP]
        );
        $containerIdsWithWritePermissions = array_filter($this->MY_RIGHTS_LEVEL, function ($v, $k) use ($containers) {
            return $v == WRITE_RIGHT && array_key_exists($k, $containers);
        }, ARRAY_FILTER_USE_BOTH);

        $containerIdsWithWritePermissions = array_keys($containerIdsWithWritePermissions);

        $containers = Api::makeItJavaScriptAble($containers);
        $this->set('containers', $containers);
        $this->set('containerIdsWithWritePermissions', $containerIdsWithWritePermissions);
        $this->viewBuilder()->setOption('serialize', ['containers', 'containerIdsWithWritePermissions']);
    }

    public function getUserPermissions() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template
            return;
        }

        $modules = PluginManager::getAvailablePlugins();


        $this->set('permissions', $this->PERMISSIONS);
        $this->set('modules', $modules);
        $this->viewBuilder()->setOption('serialize', ['permissions', 'modules']);
    }
}
