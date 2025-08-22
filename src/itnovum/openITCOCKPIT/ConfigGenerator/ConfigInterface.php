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

namespace itnovum\openITCOCKPIT\ConfigGenerator;


interface ConfigInterface {

    /**
     * @return string
     */
    public function getTemplatePath();

    /**
     * @return string
     */
    public function getTemplateName();

    /**
     * @return string
     */
    public function getTemplateNameWithPath();

    /**
     * @param $data
     * @return true|array
     */
    public function customValidationRules($data);

    /**
     * @return string
     * @deprecated Not used by Angular frontend anymore (AngularJS legacy)
     */
    public function getAngularDirective();

    /**
     * Save the configuration as text file on disk
     *
     * @param array $dbRecords from CakePHP find
     */
    public function writeToFile($dbRecords);

    /**
     * @return string
     */
    public function getDbKey();

    /**
     * Migrate current existing config file to database
     * @param array $dbRecords from CakePHP find
     * @return bool
     */
    public function migrate($dbRecords);

    /**
     * Return the help text for the given key
     * If no help text is available, return an empty string
     *
     * @param string $key
     * @return string
     */
    public function getHelpText(string $key);
}
