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

use App\Model\Table\RegistersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use itnovum\openITCOCKPIT\Core\System\Gearman;
use itnovum\openITCOCKPIT\Core\System\Health\LsbRelease;


class RegistersController extends AppController {
    use LocatorAwareTrait;

    public function index() {
        if (!$this->isApiRequest()) {
            throw new \Cake\Http\Exception\MethodNotAllowedException();
        }

        $TableLocator = $this->getTableLocator();

        /** @var RegistersTable $RegistersTable */
        $RegistersTable = $TableLocator->get('Registers');

        if ($this->request->is('get')) {
            $license = $RegistersTable->getLicense();
            $hasLicense = false;
            if ($license !== null) {
                $hasLicense = true;
            }

            $licenseResponse = null;
            if ($hasLicense) {
                $licenseResponse = $RegistersTable->checkLicenseKey($license['license']);
            }


            $isCommunityLicense = false;
            if ($hasLicense && isset($licenseResponse['license']->license)) {
                if ($licenseResponse['license']->license == $RegistersTable->getCommunityLicenseKey()) {
                    $isCommunityLicense = true;
                }
            }

            $this->set('hasLicense', $hasLicense);
            $this->set('license', $license);
            $this->set('licenseResponse', $licenseResponse);
            $this->set('isCommunityLicense', $isCommunityLicense);
            $this->viewBuilder()->setOption('serialize', ['hasLicense', 'license', 'licenseResponse', 'isCommunityLicense']);
            return;
        }

        if ($this->request->is('post')) {
            $license = $this->request->getData('Registers.license');


            $licenseResponse = $RegistersTable->checkLicenseKey($license);
            if ($licenseResponse['success'] === true) {
                if (is_object($licenseResponse['license']) && property_exists($licenseResponse['license'], 'license')) {
                    //license is valid

                    $licenseEntity = $RegistersTable->getLicenseEntity();
                    if (is_null($licenseEntity)) {
                        //no license yet
                        $licenseEntity = $RegistersTable->newEmptyEntity();
                    }

                    $licenseEntity = $RegistersTable->patchEntity($licenseEntity, ['license' => $license]);

                    $GearmanClient = new Gearman();
                    $LsbRelease = new LsbRelease();
                    if ($LsbRelease->isDebianBased()) {
                        $GearmanClient->sendBackground('create_apt_config', ['key' => $license]);
                    }

                    if ($LsbRelease->isRhelBased()) {
                        $GearmanClient->sendBackground('create_dnf_config', ['key' => $license]);
                    }

                    $licenseEntity->set('apt', 1);
                    $licenseEntity->set('accepted', 1);
                    $RegistersTable->save($licenseEntity);

                    if ($licenseEntity->hasErrors()) {
                        $this->response = $this->response->withStatus(400);
                        $this->set('error', $licenseEntity->getErrors());
                        $this->viewBuilder()->setOption('serialize', ['error']);
                        return;
                    }

                    $isCommunityLicense = false;
                    if ($licenseResponse['license']->license == $RegistersTable->getCommunityLicenseKey()) {
                        $isCommunityLicense = true;
                    }


                    $this->set('licenseResponse', $licenseResponse);
                    $this->set('isCommunityLicense', $isCommunityLicense);
                    $this->viewBuilder()->setOption('serialize', ['licenseResponse', 'isCommunityLicense']);
                    return;
                }
            }

            $this->set('licenseResponse', $licenseResponse);
            $this->viewBuilder()->setOption('serialize', ['licenseResponse']);
        }
    }

}
