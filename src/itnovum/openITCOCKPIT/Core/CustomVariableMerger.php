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


class CustomVariableMerger extends CustomVariableHelper {

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
    public function getCustomVariablesMergedAsRepository() {
        $hostCustomVariablesRepository = $this->hostCustomVariablesRepository;
        foreach ($hostCustomVariablesRepository->getAllCustomVariables() as $hostCustomVariable) {
            //Delete variables with the same name of the host template
            $this->hosttemplateCustomVariablesRepository->deleteByVariableName($hostCustomVariable->getName());
        }

        foreach ($this->hosttemplateCustomVariablesRepository->getAllCustomVariables() as $hosttemplateCustomVariable) {
            $hostCustomVariablesRepository->addCustomVariable($hosttemplateCustomVariable);
        }

        return $hostCustomVariablesRepository;
    }

}
