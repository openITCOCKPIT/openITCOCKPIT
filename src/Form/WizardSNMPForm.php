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


namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Class WizardSNMPForm
 * @package App\Form
 */
class WizardSNMPForm extends Form {
    protected function _buildSchema(Schema $schema): Schema {
        return $schema
            ->addField('snmpCommunity', ['type' => 'string'])
            ->addField('securityName', ['type' => 'string'])
            ->addField('authPassword', ['type' => 'string'])
            ->addField('privacyProtocol', ['type' => 'string'])
            ->addField('authProtocol', ['type' => 'string'])
            ->addField('privacyPassword', ['type' => 'string'])
            ->addField('services', ['type' => 'array']);
    }

    public function validationDefault(Validator $validator): Validator {
        $validator
            ->requirePresence('snmpCommunity')
            ->allowEmptyString('snmpCommunity', null, function ($context) {
                return isset($context['data']['snmpVersion']) && $context['data']['snmpVersion'] === '3';
            });
        $validator
            ->requirePresence('securityName')
            ->allowEmptyString('securityName', null, function ($context) {
                if (isset($context['data']['snmpVersion']) &&
                    in_array($context['data']['snmpVersion'], ['1', '2'], true)) {
                    return true;
                }
                return !(isset($context['data']['snmpVersion']) && $context['data']['snmpVersion'] === '3' &&
                    $context['data']['securityLevel'] !== '3');
            });
        $validator
            ->requirePresence('authPassword')
            ->allowEmptyString('authPassword', null, function ($context) {
                return !(isset($context['data']['snmpVersion']) && $context['data']['snmpVersion'] === '3' &&
                    $context['data']['securityLevel'] !== '3');
            });
        $validator
            ->requirePresence('privacyProtocol')
            ->allowEmptyString('privacyProtocol', null, function ($context) {
                return !(isset($context['data']['snmpVersion']) && $context['data']['snmpVersion'] === '3' &&
                    $context['data']['securityLevel'] !== '3');
            });
        $validator
            ->requirePresence('authProtocol')
            ->allowEmptyString('authProtocol', null, function ($context) {
                return !(isset($context['data']['snmpVersion']) && $context['data']['snmpVersion'] === '3' &&
                    $context['data']['securityLevel'] !== '3');
            });
        $validator
            ->requirePresence('privacyPassword')
            ->allowEmptyString('privacyPassword', null, function ($context) {
                return !(isset($context['data']['snmpVersion']) && $context['data']['snmpVersion'] === '3' &&
                    $context['data']['securityLevel'] !== '3');
            });

        $validator
            ->allowEmptyArray('services', __('Please select at least one service or interface'), function ($context) {
                if (sizeof($context['data']['interfaces']) > 0) {
                    return true;
                }
                return false;
            });
        return $validator;
    }
}
