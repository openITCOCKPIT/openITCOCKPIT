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

namespace App\Middleware;

use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsergroupsTable;
use Cake\Cache\Cache;
use Cake\Core\InstanceConfigTrait;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Ldap\LdapClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LdapUsergroupIdMiddleware implements MiddlewareInterface {

    use InstanceConfigTrait;

    protected $_defaultConfig = [
        'identityAttribute' => 'identity'
    ];

    public function __construct(array $config = []) {
        $this->setConfig($config);
    }

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        // ITC-2693
        $identity = $request->getAttribute($this->getConfig('identityAttribute'));
        if ($identity !== null) {
            //User is logged in
            $samaccountname = $identity->get('samaccountname');
            if (!empty($samaccountname)) {

                if (!empty($identity->get('samaccountname'))) {
                    $cacheKey = 'ltc_ldap_usergroup_id_for_' . $identity->get('id');
                }

                if (Cache::read($cacheKey, 'long_time_cache') === null) {
                    // Query LDAP Server to get current LDAP Groups

                    /** @var SystemsettingsTable $SystemsettingsTable */
                    $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
                    /** @var UsergroupsTable $UsergroupsTable */
                    $UsergroupsTable = TableRegistry::getTableLocator()->get('Usergroups');

                    $Ldap = LdapClient::fromSystemsettings($SystemsettingsTable->findAsArraySection('FRONTEND'));

                    $ldapUser = $Ldap->getUser($samaccountname, true);
                    if ($ldapUser) {
                        $usergroupLdap = $UsergroupsTable->getUsergroupByLdapUserMemberOf($ldapUser['memberof']);
                        if (isset($usergroupLdap['id'])) {
                            // Use the usergroup_id (user role) via LDAP group matching
                            // Use the LDAP based usergroup_id
                            Cache::write($cacheKey, $usergroupLdap['id'], 'long_time_cache');
                        } else {
                            // No LDAP usergroup matching found - cache the "fallback" usergroup_id from the users table
                            // to not stress the LDAP server on every request
                            // Most customers do not have any LDAP group assignments
                            Cache::write($cacheKey, $identity->get('usergroup_id'), 'long_time_cache');
                        }
                    }
                }

                $currentUsergroupId = (int)$identity->get('usergroup_id');
                $cachedUsergroupId = (int)Cache::read($cacheKey, 'long_time_cache');
                if ($currentUsergroupId !== $cachedUsergroupId) {
                    // Overwrite the fallback usergroup_id from the users table, with the usergroup_id via the LDAP groups mapping
                    $identity->overwriteUsergroupId($cachedUsergroupId);
                }
            }
        }

        // Calling $handler->handle() delegates control to the *next* middleware
        // In your application's queue.
        $response = $handler->handle($request);
        return $response;
    }
}
