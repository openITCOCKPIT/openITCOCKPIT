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

use App\Lib\QrCodeGenerator;
use App\Model\Table\ApikeysTable;
use App\Model\Table\EventlogsTable;
use App\Model\Table\UsersTable;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use itnovum\openITCOCKPIT\Core\Locales;
use itnovum\openITCOCKPIT\Core\System\FileUploadSize;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\Apikey;

/**
 * Class ProfileController
 * @package App\Controller
 */
class ProfileController extends AppController {

    public function edit() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');

        if (!$UsersTable->existsById($User->getId())) {
            throw new NotFoundException(__('User not found'));
        }

        $user = $UsersTable->getUserForEdit($User->getId());
        // i need the password field to prevent change detection
        $userForChangelog = [
            'User' => $UsersTable->getUserById($User->getId())
        ];
        $isLdapUser = !empty($user['User']['samaccountname']);

        unset($user['User']['usercontainerroles']);
        unset($user['User']['containers']);
        unset($user['User']['usercontainerroles_containerids']);
        unset($user['User']['ContainersUsersMemberships']);
        unset($user['User']['apikeys']);

        $FileUploadSize = new FileUploadSize();

        if ($this->request->is('get') && $this->isAngularJsRequest()) {
            //Return profile information
            $this->set('user', $user['User']);
            $this->set('isLdapUser', $isLdapUser);
            $this->set('maxUploadLimit', $FileUploadSize->toArray());
            $this->set('newDesktopApi', true);
            $this->set('oitcVersion', OPENITCOCKPIT_VERSION);
            $this->set('hasRootPrivileges', $this->hasRootPrivileges);
            $this->viewBuilder()->setOption('serialize', ['user', 'isLdapUser', 'maxUploadLimit', 'newDesktopApi', 'oitcVersion', 'hasRootPrivileges']);
            return;
        }

        if ($this->request->is('post') || $this->request->is('put')) {
            $data = $this->request->getData('User', []);

            $user = $UsersTable->get($User->getId());
            $user->setAccess('id', false);

            if ($isLdapUser) {
                $data['is_ldap'] = true;
                $user->setAccess('email', false);
                $user->setAccess('firstname', false);
                $user->setAccess('lastname', false);
                $user->setAccess('password', false);
                $user->setAccess('samaccountname', false);
                $user->setAccess('ldap_dn', false);
            }

            //prevent multiple hash of password
            if ($data['password'] === '' && $data['confirm_password'] === '') {
                unset($data['password']);
                unset($data['confirm_password']);
            }

            $user = $UsersTable->patchEntity($user, $data);

            $data = [
                'User' => $data
            ];

            $user = $UsersTable->updateUser(
                $user,
                $data,
                $userForChangelog,
                $User->getId()
            );
            if ($user->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $user->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }

            //Update user information in $_SESSION
            Cache::clearAll();
            $session = $this->request->getSession();
            $session->write('Auth', $UsersTable->get($User->getId()));

            $this->set('user', $user);
            $this->viewBuilder()->setOption('serialize', ['user']);
        }
    }

    public function changePassword() {
        $User = new User($this->getUser());

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $Hasher = $UsersTable->getDefaultPasswordHasher();

        $user = $UsersTable->get($User->getId());
        $userForLog = [
            'User' => $UsersTable->getUserById($User->getId())
        ];

        $data = $this->request->getData('Password');

        if (!$Hasher->check($data['current_password'], $user->get('password'))) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', [
                'current_password' => [
                    __('Current password is incorrect')
                ]
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $user = $UsersTable->patchEntity($user, $data);

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

        $session = $this->request->getSession();
        $session->write('Auth', $UsersTable->get($User->getId()));

        $this->set('message', __('Password changed successfully.'));
        $this->viewBuilder()->setOption('serialize', ['message']);
    }

    public function upload_profile_icon() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $User = new User($this->getUser());

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->get($User->getId(), contain: [
            'Containers'
        ]);

        if ($user === null) {
            throw new NotFoundException();
        }

        /***** Change users profile image *****/
        if (!is_dir(WWW_ROOT . 'img' . DS . 'userimages')) {
            mkdir(WWW_ROOT . 'img' . DS . 'userimages');
        }

        if (isset($_FILES['Picture']['tmp_name']) && isset($_FILES['Picture']['name'])) {
            $filename = $UsersTable->uploadProfilePicture();
            if ($filename) {
                //Delete old image

                $oldImage = $user->get('image');
                if ($oldImage !== '' && $oldImage !== null) {
                    $oldImageFull = WWW_ROOT . 'img' . DS . 'userimages' . DS . $oldImage;
                    if (file_exists($oldImageFull) && !is_dir($oldImageFull)) {
                        unlink($oldImageFull);
                    }
                }

                $user->set('image', $filename);
                //prevent multiple hash of password
                unset($user->password);
                $UsersTable->save($user);
                if ($user->hasErrors()) {
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $user->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                }

                //Update cached data in user identity / current session
                $session = $this->request->getSession();
                $UserEntity = $session->read('Auth');
                if ($UserEntity instanceof \App\Model\Entity\User) {
                    $UserEntity->set('image', $filename);
                }

                $this->response = $this->response->withStatus(200);
                $this->set('success', true);
                $this->set('message', __('File Upload success!'));
                $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                return;
            }

            $this->response = $this->response->withStatus(400);
            $this->set('error', __('Could not save image data, may be wrong data type. Allowed types are .png, .jpg and .gif'));
            $this->set('message', __('Could not save image data, may be wrong data type. Allowed types are .png, .jpg and .gif'));
            $this->viewBuilder()->setOption('serialize', ['error', 'message']);
            return;
        }
        $this->set('user', []);
        $this->viewBuilder()->setOption('serialize', ['user']);

    }

    public function apikey() {
        if (!$this->isApiRequest()) {
            throw new MethodNotAllowedException('Only API requests.');
        }

        $User = new User($this->getUser());
        $UserTime = $User->getUserTime();

        /** @var $ApikeysTable ApikeysTable */
        $ApikeysTable = TableRegistry::getTableLocator()->get('Apikeys');

        if ($this->request->is('get')) {
            $id = $this->request->getQuery('id');
            if (is_numeric($id)) {
                //Get an api key by id and user_id
                if (!$ApikeysTable->existsById($id)) {
                    throw new NotFoundException(__('Invalid API key'));
                }
                $apikey = $ApikeysTable->getApikeyByIdAndUserId(
                    $id,
                    $User->getId()
                );
                $Apikey = new Apikey($apikey);
                if (!empty($Apikey->getLastUse())) {
                    $Apikey->setLastUse($UserTime->format($Apikey->getLastUse()->getTimestamp()));
                }
                $Apikey = $Apikey->toArray();

                $qr = new QrCodeGenerator($Apikey['apikey']);
                $qrcode = $qr->getQrCodeAsBase64();

                $this->set('apikey', $Apikey);
                $this->set('qrcode', $qrcode);
                $this->viewBuilder()->setOption('serialize', ['apikey', 'qrcode']);
                return;
            }

            //Return all api keys of the user
            $apikeysResult = $ApikeysTable->getAllapiKeysByUserId($User->getId());

            $apikeys = [];
            foreach ($apikeysResult as $apikey) {
                $Apikey = new Apikey($apikey);
                if (!empty($Apikey->getLastUse())) {
                    $Apikey->setLastUse($UserTime->format($Apikey->getLastUse()->getTimestamp()));
                }
                $apikeys[] = $Apikey->toArray();
            }

            $this->set('apikeys', $apikeys);
            $this->viewBuilder()->setOption('serialize', ['apikeys']);
            return;
        }

        if ($this->request->is('post')) {
            //Update an api key by id
            $data = $this->request->getData('Apikey', []);
            if (isset($data['id'])) {
                $id = $data['id'];

                if (!$ApikeysTable->existsById($id)) {
                    throw new NotFoundException(__('Invalid API key'));
                }
                $apikey = $ApikeysTable->get($id);
                $apikey = $ApikeysTable->patchEntity($apikey, $data);

                $ApikeysTable->save($apikey);
                if ($apikey->hasErrors()) {
                    $this->response = $this->response->withStatus(400);
                    $this->set('error', $apikey->getErrors());
                    $this->viewBuilder()->setOption('serialize', ['error']);
                    return;
                } else {
                    $this->set('message', __('API key updated successfully'));
                    $this->viewBuilder()->setOption('serialize', ['message']);
                    return;
                }
            }

        }
    }

    public function create_apikey() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var $ApikeysTable ApikeysTable */
        $ApikeysTable = TableRegistry::getTableLocator()->get('Apikeys');

        if ($this->request->is('get')) {
            //Generate new API key
            $apikey = $ApikeysTable->generateApiKey();

            $newApiKey = [
                'key'  => $apikey,
                'time' => time()
            ];

            $session = $this->request->getSession();
            $session->write('latest_api_key', $newApiKey);

            $qr = new QrCodeGenerator($apikey);
            $qrcode = $qr->getQrCodeAsBase64();

            $this->set('apikey', $apikey);
            $this->set('qrcode', $qrcode);
            $this->viewBuilder()->setOption('serialize', ['apikey', 'qrcode']);
            return;
        }

        if ($this->request->is('post')) {
            $User = new User($this->getUser());
            //Save new API key
            //Resolve ITC-2170
            $session = $this->request->getSession();

            $newApiKey = $session->read('latest_api_key');

            if (!isset($newApiKey['key']) || !$newApiKey['time']) {
                throw new BadRequestException();
            }
            //Is API-Key older than 5 minutes?
            if ($newApiKey['time'] < (time() - 5 * 60)) {
                throw new BadRequestException('API key expired');
            }

            $apikey = [
                'apikey'      => $newApiKey['key'],
                'description' => $this->request->getData('Apikey.description', ''),
                'user_id'     => $User->getId()
            ];

            $apikeyEntity = $ApikeysTable->newEmptyEntity();
            $apikeyEntity = $ApikeysTable->patchEntity($apikeyEntity, $apikey);

            $ApikeysTable->save($apikeyEntity);
            if ($apikeyEntity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $apikeyEntity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
            } else {
                $this->set('message', __('API key created successfully'));
                $this->viewBuilder()->setOption('serialize', ['message']);
                $session->delete('latest_api_key');
            }
        }
    }

    public function delete_apikey($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }


        $User = new User($this->getUser());

        /** @var $ApikeysTable ApikeysTable */
        $ApikeysTable = TableRegistry::getTableLocator()->get('Apikeys');

        $apikey = $ApikeysTable->getApikeyByIdAndUserId($id, $User->getId());

        if (empty($apikey)) {
            throw new NotFoundException('Invalide API key');
        }

        if ($ApikeysTable->delete($apikey)) {
            $this->set('message', __('Api key deleted successfully'));
            $this->viewBuilder()->setOption('serialize', ['message']);
            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('message', __('Could not delete api key'));
        $this->viewBuilder()->setOption('serialize', ['message']);

    }

    public function deleteImage() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $User = new User($this->getUser());

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->get($User->getId(), contain: [
            'Containers'
        ]);

        //prevent multiple hash of password
        unset($user->password);

        if ($user->image != null && $user->image != '') {
            if (file_exists(WWW_ROOT . 'img' . DS . 'userimages' . DS . $user->image)) {
                unlink(WWW_ROOT . 'img' . DS . 'userimages' . DS . $user->image);
            }
        }

        $user->image = null;

        $UsersTable->save($user);
        if ($user->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $user->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        //Update cached data in user identity / current session
        $session = $this->request->getSession();
        $UserEntity = $session->read('Auth');
        if ($UserEntity instanceof \App\Model\Entity\User) {
            $UserEntity->set('image', null);
        }

        $this->response = $this->response->withStatus(200);
        $this->set('success', true);
        $this->set('message', __('File deleted sucessfully'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
        return;
    }

    public function edit_apikey() {
        //Only ship HTML template
        return;
    }

    public function updateI18n() {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $User = new User($this->getUser());

        /** @var $UsersTable UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');


        $localesPath = Configure::read('App.paths.locales')[0];
        $definedLocalCodes = Locales::getLocalCodesFromDefinedLanguages();
        $locales = [];
        $localeDirs = array_filter(glob($localesPath . '*'), 'is_dir');
        array_walk($localeDirs, function ($value, $key) use (&$locales, $localesPath, $definedLocalCodes) {
            $i18n = substr($value, strlen($localesPath));
            if (in_array($i18n, $definedLocalCodes, true)) {
                $locales[] = $i18n;
            }
        });

        $i18n = $this->request->getData('i18n', 'en_US');
        if (in_array($i18n, $locales, true)) {
            $user = $UsersTable->get($User->getId());
            $user->set('i18n', $i18n);
            $UsersTable->save($user);

            //Update user information in $_SESSION
            Cache::clearAll();
            $session = $this->request->getSession();
            $session->write('Auth', $UsersTable->get($User->getId()));
        }

    }
}
