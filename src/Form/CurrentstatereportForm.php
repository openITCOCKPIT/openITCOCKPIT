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


// in src/Form/CurrentstatereportForm.php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class CurrentstatereportForm extends Form {

    protected function _buildSchema(Schema $schema): Schema {
        return $schema
            ->addField('services', ['type' => 'array'])
            ->addField('current_state', ['type' => 'array']);
    }

    public function validationDefault(Validator $validator): Validator {
        $validator
            ->requirePresence('services', true, __('You must specify at least one service.'))
            ->allowEmptyArray('services', __('You must specify at least one service.'), false);

        $validator
            ->requirePresence('current_state')
            ->add('current_state', 'custom', [
                'rule'    => [$this, 'atLeastOneServicestatus'], //\App\Lib\Traits\CustomValidationTrait
                'message' => __('You must specify at least one service status.')
            ]);

        return $validator;
    }

    /**
     * @param mixed $value
     * @param array $context
     * @return bool
     *
     * Custom validation rule for service status
     */
    public function atLeastOneServicestatus($value, $context) {
        $fields = ['ok', 'warning', 'critical', 'unknown'];
        foreach ($fields as $field) {
            $value = $context['data']['current_state'][$field];

            if (isset($context['data']['current_state'][$field])) {
                $val = $context['data']['current_state'][$field];
            }
            if ($val === true || $val === 'true') {
                // (bool)true on POST
                // (string)'true' in GET

                return true;
            }
        }
        return false;
    }
}
