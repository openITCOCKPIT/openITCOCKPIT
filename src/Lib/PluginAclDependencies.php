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

namespace App\Lib;


abstract class PluginAclDependencies {

    /**
     * Hold a list of controllers and actions which
     * should always be allowed and could not be disabled by the user
     *
     * For Example:
     * PagesController::paginator()
     * PagesController::csrf()
     * Users::login()
     * Users::logout()
     *
     * @var array
     */
    protected $allow = [];

    /**
     * Controller actions that depends on other controller actions.
     * This often happens when using a lot of Ajax in your frontend.
     *
     * For example:
     * Users::edit() make an Ajax call to Users::loadUsergroups() etc...
     *
     * @var array
     */
    protected $dependencies = [];

    public function __construct() {
        // Add actions that should always be allowed.
        //$this
        //    ->allow('Angular', 'index')
        //    ->allow('Angular', 'paginator');


        ///////////////////////////////
        //    Add dependencies       //
        //////////////////////////////
        //$this
        //    ->dependency('Agentchecks', 'add', 'Agentchecks', 'loadServicetemplates')
        //    ->dependency('Agentchecks', 'edit', 'Agentchecks', 'loadServicetemplates');
    }

    /**
     * @param string $controller
     * @param string $action
     * @return $this
     */
    protected function allow(string $controller, string $action): self {
        if (!isset($this->allow[$controller])) {
            $this->allow[$controller] = [];
        }

        $this->allow[$controller][$action] = $action;
        return $this;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $dependentController
     * @param $dependentAction
     * @return $this
     */
    protected function dependency(string $controller, string $action, string $dependentController, $dependentAction): self {
        if (!isset($this->dependencies[$controller][$action])) {
            $this->dependencies[$controller][$action] = [];
        }

        if (!isset($this->dependencies[$controller][$action][$dependentController])) {
            $this->dependencies[$controller][$action][$dependentController] = [];
        }

        $this->dependencies[$controller][$action][$dependentController][] = $dependentAction;


        return $this;
    }

    /**
     * @return array
     */
    public function getAllow() {
        return $this->allow;
    }

    /**
     * @return array
     */
    public function getDependencies() {
        return $this->dependencies;
    }

}
