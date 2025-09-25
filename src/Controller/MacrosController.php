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

use App\Model\Table\MacrosTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;

class MacrosController extends AppController {

    use LocatorAwareTrait;

    public function index() {
        $TableLocator = $this->getTableLocator();
        /** @var $Macros MacrosTable */
        $Macros = $TableLocator->get('Macros');

        if ($this->isJsonRequest() && !$this->isAngularJsRequest()) {
            //Legacy API
            $this->set('all_macros', $Macros->getAllMacros());
            $this->viewBuilder()->setOption('serialize', ['all_macros']);
            return;
        }

        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        $all_macros = $Macros->find('all')->disableHydration()->toArray();
        $all_macros = Hash::sort($all_macros, '{n}.name', 'asc', 'natural');
        $this->set('all_macros', $all_macros);
        $this->viewBuilder()->setOption('serialize', ['all_macros']);
    }

    public function add() {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $TableLocator = $this->getTableLocator();
        /** @var $Macros MacrosTable */
        $Macros = $TableLocator->get('Macros');

        $macro = $Macros->newEmptyEntity();
        $macro = $Macros->patchEntity($macro, $this->request->getData('Macro'));
        $Macros->save($macro);

        if ($macro->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $macro->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $this->set('macro', $macro);
        $this->viewBuilder()->setOption('serialize', ['macro']);
    }

    /**
     * @param null $id
     */
    public function edit($id = null) {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $TableLocator = $this->getTableLocator();
        /** @var $Macros MacrosTable */
        $Macros = $TableLocator->get('Macros');

        if (!$Macros->exists($id)) {
            throw new NotFoundException('Macro not found');
        }

        $macro = $Macros->get($id);
        $macro = $Macros->patchEntity($macro, $this->request->getData('Macro'));

        $Macros->save($macro);

        if ($macro->hasErrors()) {
            $this->response = $this->response->withStatus(400);
            $this->set('error', $macro->getErrors());
            $this->viewBuilder()->setOption('serialize', ['error']);
            return;
        }

        $this->set('macro', $macro);
        $this->viewBuilder()->setOption('serialize', ['macro']);
    }

    /**
     * @param null $id
     */
    public function delete($id = null) {
        if (!$this->isAngularJsRequest() || !$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        $TableLocator = $this->getTableLocator();
        /** @var $Macros MacrosTable */
        $Macros = $TableLocator->get('Macros');

        if (!$Macros->existsById($id)) {
            throw new NotFoundException('Macro not found');
        }

        $macro = $Macros->get($id);
        if ($Macros->delete($macro)) {
            $this->set('success', true);
            $this->viewBuilder()->setOption('serialize', ['success']);
            return;
        }

        $this->response = $this->response->withStatus(500);
        $this->set('success', false);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    public function getAvailableMacroNames() {
        if (!$this->isAngularJsRequest()) {
            throw new MethodNotAllowedException();
        }

        $include = $this->request->getQuery('include');

        $TableLocator = $this->getTableLocator();
        /** @var $Macros MacrosTable */
        $Macros = $TableLocator->get('Macros');

        $availableMacroNames = array_values($Macros->getAvailableMacroNames());
        if ($include !== '') {
            $availableMacroNames[] = $include;
        }

        $this->set('availableMacroNames', $availableMacroNames);
        $this->viewBuilder()->setOption('serialize', ['availableMacroNames']);
    }
}
