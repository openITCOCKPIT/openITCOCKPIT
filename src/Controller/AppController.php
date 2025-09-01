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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Controller;

use App\itnovum\openITCOCKPIT\Core\Permissions\MyRightsFactory;
use App\Model\Table\SystemsettingsTable;
use Authentication\Controller\Component\AuthenticationComponent;
use Authentication\IdentityInterface;
use Authorization\Identity;
use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\View\JsonView;
use Exception;
use itnovum\openITCOCKPIT\Core\DbBackend;
use itnovum\openITCOCKPIT\Core\PerfdataBackend;
use PuppeteerPdf\View\PdfView;

/**
 * Class AppController
 * @package App\Controller
 * @property AuthenticationComponent $Authentication
 */
class AppController extends Controller {

    /**
     * @var bool
     */
    protected $hasRootPrivileges = false;

    /**
     * @var array
     */
    public $MY_RIGHTS = [];

    /**
     * @var array
     */
    public $MY_RIGHTS_LEVEL = [];

    /**
     * @var array
     */
    protected $PERMISSIONS = [];

    /**
     * @var DbBackend
     */
    protected $DbBackend;

    /**
     * @var PerfdataBackend
     */
    protected $PerfdataBackend;

    /**
     * @var string|null
     */
    private $SYSTEMNAME = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     * @throws Exception
     */
    public function initialize(): void {
        parent::initialize();

        $this->loadComponent('Flash');

        // Docs: https://book.cakephp.org/authentication/1/en/index.html
        $this->loadComponent('Authentication.Authentication', [
            'logoutRedirect' => '/a/users/login'  // Default is false
        ]);

        if (isset($this->getUser()->i18n) && strlen($this->getUser()->i18n) >= 3) {
            I18n::setLocale($this->getUser()->i18n);
        }

        /*
         * Enable the following component for recommended CakePHP security settings.
         * see https://book.cakephp.org/4/en/controllers/components/security.html
         */
        //$this->loadComponent('Security');
    }

    /**
     * @param array|int $containerIds
     * @param bool $useLevel
     * @return bool
     */
    protected function allowedByContainerId($containerIds = [], $useLevel = true) {
        if ($this->hasRootPrivileges === true) {
            return true;
        }

        if ($useLevel === true) {
            $MY_WRITE_RIGHTS = array_filter($this->MY_RIGHTS_LEVEL, function ($value) {
                if ((int)$value === WRITE_RIGHT) {
                    return true;
                }

                return false;
            });
            $MY_WRITE_RIGHTS = array_keys($MY_WRITE_RIGHTS);
            if (!is_array($containerIds)) {
                $containerIds = [$containerIds];
            }
            $result = array_intersect($containerIds, $MY_WRITE_RIGHTS);
            if (!empty($result)) {
                return true;
            }

            if ($this->isApiRequest() && !$this->isAngularJsRequest()) {
                throw new ForbiddenException('403 Forbidden');
            }

            return false;
        }


        $rights = $this->MY_RIGHTS;

        if (is_array($containerIds)) {
            $result = array_intersect($containerIds, $rights);
            if (!empty($result)) {
                return true;
            }
        } else {
            if (in_array($containerIds, $rights)) {
                return true;
            }
        }

        if ($this->isApiRequest() && !$this->isAngularJsRequest()) {
            throw new ForbiddenException('403 Forbidden');
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getWriteContainers() {
        $MY_WRITE_RIGHTS = array_filter($this->MY_RIGHTS_LEVEL, function ($value) {
            if ((int)$value === WRITE_RIGHT) {
                return true;
            }

            return false;
        });
        $MY_WRITE_RIGHTS = array_keys($MY_WRITE_RIGHTS);

        return $MY_WRITE_RIGHTS;
    }

    /**
     * @param string|null $action
     * @param string|null $controller
     * @param string|null $plugin
     * @return bool
     */
    protected function hasPermission($action = null, $controller = null, $plugin = null) {
        $controller = strtolower($controller);
        $action = strtolower($action);
        if ($plugin !== null) {
            $plugin = strtolower($plugin);
        }

        //return false;
        if ($plugin === null) {
            $plugin = Inflector::classify($this->getRequest()->getParam('plugin', ''));
        }

        if ($plugin === null || $plugin === '') {
            return isset($this->PERMISSIONS[$controller][$action]);
        }

        return isset($this->PERMISSIONS[$plugin][$controller][$action]);
    }

    /**
     * @param int $containerId
     * @return bool
     */
    protected function isWritableContainer($containerId) {
        if ($this->hasRootPrivileges === true) {
            return true;
        }
        if (isset($this->MY_RIGHTS_LEVEL[$containerId])) {
            return (int)$this->MY_RIGHTS_LEVEL[$containerId] === WRITE_RIGHT;
        }
        return false;
    }


    /**
     * @return bool
     */
    protected function isHtmlRequest(): bool {
        return $this->request->getParam('_ext') === 'html';
    }

    /**
     * @return bool
     */
    protected function isJsonRequest(): bool {
        return $this->request->getParam('_ext') === 'json';
    }

    /**
     * @return bool
     */
    protected function isXmlRequest() {
        return $this->request->getParam('_ext') === 'xml';
    }

    /**
     * @return bool
     */
    protected function isPdfRequest(): bool {
        return $this->request->getParam('_ext') === 'pdf';
    }

    /**
     * @return bool
     */
    protected function isZipRequest(): bool {
        return $this->request->getParam('_ext') === 'zip';
    }

    protected function isApiRequest() {
        if ($this->isJsonRequest() || $this->isXmlRequest()) {
            return true;
        }

        return false;
    }

    protected function isAngularJsRequest() {
        if ($this->isApiRequest()) {
            return $this->request->getQuery('angular') !== null;
        }
        return false;
    }

    protected function isScrollRequest() {
        if ($this->isApiRequest()) {
            if ($this->request->getQuery('scroll', 'false') !== 'false') {
                return true;
            }
        }

        return false;
    }

    /**
     * This method tries to determine if the request tries to load a legacy AngularJS template
     * Most of the time this is the case if the URL ends with .html
     *
     * However, by default CakePHP will always try to render the template, also if the URL
     * does not contain .html.
     *
     * In this case we check the headers
     *
     * @return bool
     */
    protected function isLegacyHtmlTemplateRequest(): bool {
        // Some actions actually need to render HTML (phpinfo for example).
        // In this case, we create a whitelist for now
        $controller = strtolower($this->request->getParam('controller'));
        $action = strtolower($this->request->getParam('action'));

        $whitelist = [
            'administrators.php_info' => 'administrators.php_info',
            'eventlogs.listtocsv'     => 'eventlogs.listtocsv',
            'hosts.listtocsv'         => 'hosts.listtocsv',
            'services.listtocsv'      => 'services.listtocsv',
            'hostgroups.listtocsv'    => 'hostgroups.listtocsv',
            'servicegroups.listtocsv' => 'servicegroups.listtocsv',
            'users.listtocsv'         => 'users.listtocsv',
            'users.logout'            => 'users.logout',
            'statuspages.publicview'  => 'statuspages.publicview',
        ];

        $key = $controller . '.' . $action;
        if (isset($whitelist[$key])) {
            // We hit a whitelist controller.action
            return false;
        }

        if ($this->request->getParam('_ext') !== null) {
            // We have an extension, make sure it is .html
            if ($this->request->getParam('_ext') === 'html') {
                return true;
            } else {
                // We have an extension like .json, .zip, .pdf etc...
                return false;
            }
        }

        // No extension, check headers
        $hasHtmlHeader = false;
        foreach ($this->request->getHeader('Accept') as $accept) {
            if (strpos($accept, 'text/html') !== false) {
                $hasHtmlHeader = true;
                break;
            }
        }

        return $hasHtmlHeader;
    }

    public function beforeFilter(EventInterface $event) {
        parent::beforeFilter($event);

        // Disable old AngularJS Frontend
        if ($this->isLegacyHtmlTemplateRequest()) {

            $path = $this->request->getPath();
            if (empty($path) || $path === '/') {
                $isLoggedIn = $this->getUser() !== null;
                if ($isLoggedIn) {
                    // The user is logged in and probably wants to access the openITCOCKPIT Angular Frontend
                    // Most likely the user as accessed the system via an IP-Address or a saved bookmark
                    // Users that are not logged in should be caught by the AppAuthenticationMiddleware

                    // Instead of rendering the default error message, we redirect the user to the Angular Frontend
                    $this->redirect('/a/');
                    $event->setResult(false);
                    return;
                }
            }

            $event->setResult($this->render('/Error/errorBackend', 'backend'));
            return;
        }

        $this->DbBackend = new DbBackend();
        $this->PerfdataBackend = new PerfdataBackend();
        $this->set('DbBackend', $this->DbBackend);
        $this->set('PerfdataBackend', $this->PerfdataBackend);

        $user = $this->Authentication->getIdentity();

        if ($user !== null) {
            $userId = $user->get('id');

            //User is logged in
            $cacheKey = 'userPermissions_' . $userId;

            if (Cache::read($cacheKey, 'permissions') === null) {
                $permissions = $this->getUserPermissions($user);
                Cache::write($cacheKey, $permissions, 'permissions');
            }

            $permissions = Cache::read($cacheKey, 'permissions');

            $this->MY_RIGHTS = $permissions['MY_RIGHTS'];
            $this->MY_RIGHTS_LEVEL = $permissions['MY_RIGHTS_LEVEL'];
            $this->PERMISSIONS = $permissions['PERMISSIONS'];
            $this->hasRootPrivileges = $permissions['hasRootPrivileges'];
        }
    }

    /**
     * @return IdentityInterface|null
     */
    protected function getUser() {
        return $this->Authentication->getIdentity();
    }

    protected function render403($options = []) {
        $_options = [
            'headline' => __('Permission denied'),
            'error'    => __('You are not permitted to access this object'),
            'icon'     => 'fa-exclamation-triangle',
            'referer'  => ['action' => 'index'],
        ];

        $options = Hash::merge($_options, $options);

        $this->set('options', $options);

        if ($this->isApiRequest()) {
            //Angular wants json response
            $this->set('status', 403);
            $this->set('statusText', 'Forbidden');
            $this->viewBuilder()->setOption('serialize', ['status', 'statusText']);
        }

        $this->response = $this->response->withStatus(403);
        $this->render('/Errors/error403');
        return;
    }

    /**
     * @param EntityInterface $entity
     */
    protected function serializeCake4ErrorMessage(EntityInterface $entity) {
        $this->set('error', $entity->getErrors());
        $this->viewBuilder()->setOption('serialize', ['error']);
        if ($this->isAngularJsRequest()) {
            $this->response = $this->response->withStatus(400);
            return;
        }
    }

    /**
     * Add CSRF token to all .json requests
     */
    public function beforeRender(EventInterface $event) {
        if (!$this->isApiRequest()) {
            //Set Permissions for ACL Helper
            $this->set('ACLPERMISSIONS', $this->PERMISSIONS);
            $this->set('hasRootPrivileges', $this->hasRootPrivileges);
            $this->set('MY_RIGHTS_LEVEL', $this->MY_RIGHTS_LEVEL);
        }

        if ($this->isJsonRequest()) {
            $this->set('_csrfToken', $this->request->getAttribute('csrfToken'));
            $serialize = $this->viewBuilder()->getOption('serialize');
            if ($serialize === null) {
                $serialize = [];
            }
            $serialize[] = '_csrfToken';
            //Add Paginator info to json response
            $paging = $this->viewBuilder()->getVar('paging');
            if ($paging !== null) {
                $serialize[] = 'paging';
            }

            //Add Scroll Paginator info to json response
            $scroll = $this->viewBuilder()->getVar('scroll');
            if ($scroll !== null) {
                $serialize[] = 'scroll';
            }
            $this->viewBuilder()->setOption('serialize', $serialize);
        }
    }

    /**
     * @param Identity $identity
     * @return array
     */
    private function getUserPermissions(Identity $identity) {
        $userId = $identity->get('id');
        $usergroupId = $identity->get('usergroup_id');

        //FileDebugger::dump(sprintf('user_id %s uses usergroup_id: %s', $userId, $usergroupId));
        $userPermissions = MyRightsFactory::getUserPermissions($userId, $usergroupId);
        $this->hasRootPrivileges = $userPermissions['hasRootPrivileges'];
        return $userPermissions;
    }

    /**
     * @return string|null
     */
    protected function getSystemname() {
        if ($this->SYSTEMNAME === null) {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $entity = $SystemsettingsTable->getSystemsettingByKey('FRONTEND.SYSTEMNAME');
            $this->SYSTEMNAME = $entity->get('value');
        }

        return $this->SYSTEMNAME;
    }

    /**
     * @param EntityInterface $entity
     */
    protected function serializeCake4Id(EntityInterface $entity) {

        if (!$this->isJsonRequest()) {
            return;
        }
        $this->set('id', $entity->id);
        $this->viewBuilder()->setOption('serialize', ['id']);
    }

    public function viewClasses(): array {
        if ($this->request->getParam('_ext') === 'html') {
            // CakePHP 5 workaround so that /foo/bar.html renders the same as /foo/bar
            return [];
        }

        return [JsonView::class, PdfView::class];
    }
}
