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

class PackagemanagerRequestBuilder {

    /**
     * @var string
     */
    private $internalAddress = 'http://packagemanager.openitcockpit.io';

    /**
     * @var array
     */
    private $internalOptions = [
        'CURLOPT_SSL_VERIFYPEER' => false,
        'CURLOPT_SSL_VERIFYHOST' => false,
    ];

    /**
     * @var string
     */
    private $externalAddress = 'https://packagemanager.openitcockpit.io';

    /**
     * @var array
     */
    private $externalOptions = [];

    /**
     * @var string
     */
    private $baseUrl = '%s/modules/fetch/%s/4.json';

    /**
     * @var string
     */
    private $checkLicenseUrl = '%s/licenses/check/%s.json';

    /**
     * @var string
     */
    private $ENVIRONMENT;

    /**
     * @var string
     */
    private $license = '0';

    /**
     * PackagemanagerRequestBuilder constructor.
     *
     * @param string $ENVIRONMENT
     * @param string $license
     */
    public function __construct($ENVIRONMENT, $license = '') {
        $this->ENVIRONMENT = $ENVIRONMENT;
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getUrl() {
// ITC-1350
//        if ($this->ENVIRONMENT === \Environments::DEVELOPMENT) {
//            return sprintf($this->baseUrl, $this->internalAddress, $this->license);
//        }
        if ($this->license === '') {
            $this->license = 0;
        }

        return sprintf($this->baseUrl, $this->externalAddress, $this->license);
    }

    /**
     * @return string
     */
    public function getUrlForLicenseCheck() {
// ITC-1350
//        if ($this->ENVIRONMENT === \Environments::DEVELOPMENT) {
//            return sprintf($this->checkLicenseUrl, $this->internalAddress, $this->license);
//        }

        return sprintf($this->checkLicenseUrl, $this->externalAddress, $this->license);
    }

    /**
     * @return array
     */
    public function getOptions() {
// ITC-1350
//        if ($this->ENVIRONMENT === \Environments::DEVELOPMENT) {
//            return $this->internalOptions;
//        }

        return $this->externalOptions;
    }

}
