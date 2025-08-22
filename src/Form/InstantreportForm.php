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


// in src/Form/InstantreportForm.php
namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Class InstantreportForm
 * @package App\Form
 */
class InstantreportForm extends Form {
    protected function _buildSchema(Schema $schema): Schema {
        return $schema
            ->addField('id', ['type' => 'string'])
            ->addField('from_date', ['type' => 'string'])
            ->addField('to_date', ['type' => 'string']);
    }

    public function validationDefault(Validator $validator): Validator {
        $validator
            ->requirePresence('instantreport_id')
            ->allowEmptyString('instantreport_id', null, false);

        $validator
            ->date('from_date', ['dmy'])
            ->requirePresence('from_date')
            ->allowEmptyDateTime('from_date', null, false);

        $validator
            ->date('to_date', ['dmy'])
            ->requirePresence('to_date')
            ->allowEmptyDateTime('to_date', null, false)
            ->add('to_date', 'custom', [
                'rule'    => function ($value, $context) {
                    $fromDate = strtotime($context['data']['from_date'] . ' 00:00:00');
                    $toDate = strtotime($value . ' 23:59:59');
                    return !($fromDate > $toDate);
                },
                'message' => '"From" date should be greater then "to" date.'
            ]);
        return $validator;
    }
}
