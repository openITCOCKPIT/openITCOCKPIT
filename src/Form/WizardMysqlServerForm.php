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


// in src/Form/WizardMysqlServerForm.php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Class WizardMysqlServerForm
 * @package App\Form
 */
class WizardMysqlServerForm extends Form {
    protected function _buildSchema(Schema $schema): Schema {
        return $schema
            ->addField('username', ['type' => 'string'])
            ->addField('password', ['type' => 'string'])
            ->addField('database', ['type' => 'string'])
            ->addField('services', ['type' => 'array']);
    }

    public function validationDefault(Validator $validator): Validator {
        $validator
            ->requirePresence('username')
            ->allowEmptyString('username', null, false);
        $validator
            ->requirePresence('password')
            ->allowEmptyString('password', null, false);
        $validator
            ->requirePresence('database')
            ->allowEmptyString('database', null, false);
        $validator
            ->requirePresence('services')
            ->notEmptyArray('services', __('You have to select at least one service'), false);

        return $validator;
    }
}
