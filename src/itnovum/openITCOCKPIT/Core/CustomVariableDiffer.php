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

namespace itnovum\openITCOCKPIT\Core;


use itnovum\openITCOCKPIT\Core\ValueObjects\CustomVariable;

class CustomVariableDiffer extends CustomVariableHelper {

    /**
     * @var CustomVariablesRepository
     */
    private $hostCustomVariablesRepository;

    /**
     * @var CustomVariablesRepository
     */
    private $hosttemplateCustomVariablesRepository;

    /**
     * CustomVariableDiffer constructor.
     *
     * @param array $hostCustomVariables from CakePHP's find
     * @param array $hosttemplateCustomVariables from CakePHP's find
     */
    public function __construct($hostCustomVariables, $hosttemplateCustomVariables) {
        $this->hostCustomVariablesRepository = $this->convertCustomVariablesToRepository($hostCustomVariables);
        $this->hosttemplateCustomVariablesRepository = $this->convertCustomVariablesToRepository($hosttemplateCustomVariables);
    }

    /**
     * @return CustomVariablesRepository
     */
    public function getCustomVariablesToSaveAsRepository() {
        $variablesToSaveAsRepository = new CustomVariablesRepository();

        foreach ($this->hostCustomVariablesRepository->getAllCustomVariables() as $hostCustomVariable) {
            /** @var CustomVariable $hostCustomVariable */

            //Check if a custom variable with this name exists in host template
            /** @var CustomVariable $hostTemplateCustomVariable */
            $hostTemplateCustomVariable = $this->hosttemplateCustomVariablesRepository->getByVariableName($hostCustomVariable->getName());
            if ($hostTemplateCustomVariable !== false) {
                //We found a custom variable in the host template with the same name

                //Check if the values are the same or not
                if ($hostCustomVariable->getValue() != $hostTemplateCustomVariable->getValue()) {
                    $hostCustomVariable->resetId();

                    //Overwrite objecttype_id from hosttemplate to host
                    if($hostCustomVariable->getObjecttypeId() == OBJECT_HOSTTEMPLATE){
                        $hostCustomVariable->setObjecttypeId(OBJECT_HOST);
                    }

                    if($hostCustomVariable->getObjecttypeId() == OBJECT_SERVICETEMPLATE){
                        $hostCustomVariable->setObjecttypeId(OBJECT_SERVICE);
                    }

                    $variablesToSaveAsRepository->addCustomVariable($hostCustomVariable);
                }
            }

            if ($hostTemplateCustomVariable === false) {
                //This custom variable does not exists on the hosttemplate
                $variablesToSaveAsRepository->addCustomVariable($hostCustomVariable);
            }
        }

        return $variablesToSaveAsRepository;
    }
}
