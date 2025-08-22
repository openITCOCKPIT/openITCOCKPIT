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

namespace itnovum\openITCOCKPIT\ApiShell;


class ApiExtensionLoader {

    /**
     * @var array
     */
    private $availableModels = [
        'Systemsettings',
        'Containers',
        'Cronjob',
        'Commands',
    ];

    /**
     * @var mixed
     */
    private $Cake;

    /**
     * @var string
     */
    private $modelName;

    /**
     * @var string
     */
    private $pluginName;

    public function __construct($cake, OptionParser $optionParser) {
        $this->modelName = $optionParser->getModel();
        $this->pluginName = $optionParser->getPlugin();
        $this->Cake = $cake;
    }

    /**
     * @param string $pluginName
     * @param string $modelName
     *
     * @return bool
     */
    public function isAvailable() {
        if (strlen($this->pluginName) > 0) {
            return in_array($this->pluginName . '.' . $this->modelName, $this->availableModels);
        }

        return in_array($this->modelName, $this->availableModels);
    }

    public function getApi() {
        $namespace = sprintf('itnovum\openITCOCKPIT\ApiShell\%s\Api', $this->modelName);
        $this->Cake->loadModel($this->modelName);

        return new $namespace($this->Cake, $this->modelName);
    }

}
