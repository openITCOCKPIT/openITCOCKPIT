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


namespace itnovum\openITCOCKPIT\Core\SNMP;


/**
 * Class SNMPScan
 * @package itnovum\openITCOCKPIT\Core\SNMP
 */
class SNMPScan {
    /**
     * @var array
     */
    private $snmpOptions;

    /**
     * @var string
     */
    protected $snmpVersion;

    /**
     * @var string
     */
    private $snmpCommunity;

    /**
     * @var int
     */
    private $securityLevel;

    /**
     * @var string
     */
    private $authProtocol;

    /**
     * @var string
     */
    private $securityName;

    /**
     * @var string
     */
    private $authPassword;

    /**
     * @var string
     */
    private $privacyProtocol;

    /**
     * @var string
     */
    private $privacyPassword;


    /**
     * SNMPAuthenticationString constructor.
     * @param $snmpOptions
     */
    public function __construct($snmpOptions) {
        $this->snmpOptions = $snmpOptions;
        if (isset($snmpOptions['snmpVersion'])) {
            $this->snmpVersion = $snmpOptions['snmpVersion'];
        }
        if (isset($snmpOptions['snmpCommunity'])) {
            $this->snmpCommunity = $snmpOptions['snmpCommunity'];
        }
        if (isset($snmpOptions['securityLevel'])) {
            $this->securityLevel = $snmpOptions['securityLevel'];
        }
        if (isset($snmpOptions['authProtocol'])) {
            $this->authProtocol = $snmpOptions['authProtocol'];
        }
        if (isset($snmpOptions['securityName'])) {
            $this->securityName = $snmpOptions['securityName'];
        }
        if (isset($snmpOptions['authPassword'])) {
            $this->authPassword = $snmpOptions['authPassword'];
        }
        if (isset($snmpOptions['privacyProtocol'])) {
            $this->privacyProtocol = $snmpOptions['privacyProtocol'];
        }
        if (isset($snmpOptions['privacyPassword'])) {
            $this->privacyPassword = $snmpOptions['privacyPassword'];
        }
    }

    /**
     * @return string
     */
    private function generateV1OrV2SnmpString() {
        return implode(' ', $this->generateV1OrV2SnmpAsArray());
    }

    /**
     * @return array
     */
    protected function generateV1OrV2SnmpAsArray() {
        /**
         *  --community SNMP community of the server (SNMP v1/2 only)
         */
        if (empty($this->snmpCommunity)) {
            throw new \InvalidArgumentException('Community name is missing');
        }
        return ['--community', $this->snmpCommunity];
    }

    /**
     * @return array
     */
    protected function generateV3SnmpAsArray() {
        /**
         *  --protocol      - The SNMP protocol to use (default: 2c, other possibilities: 1,3)
         *  --username      - The securityName for the USM security model (SNMPv3 only)
         *  --authpassword  - The authentication password for SNMPv3
         *  --authprotocol  - The authentication protocol for SNMPv3 (md5|sha)
         *  --privpassword  - The password for authPriv security level
         *  --privprotocol  - The private protocol for SNMPv3 (des|aes|aes128|3des|3desde)
         */

        $snmpV3Credentials = ['--protocol 3'];
        if (!empty($this->securityName)) {
            $snmpV3Credentials[] = '--username';
            $snmpV3Credentials[] = $this->securityName;
        }
        if (!empty($this->authPassword)) {
            $snmpV3Credentials[] = '--authpassword';
            $snmpV3Credentials[] = $this->authPassword;
        }
        if (!empty($this->authProtocol)) {
            $snmpV3Credentials[] = '--authprotocol';
            $snmpV3Credentials[] = $this->authProtocol;
        }
        if (!empty($this->privacyPassword)) {
            $snmpV3Credentials[] = '--privpassword';
            $snmpV3Credentials[] = $this->privacyPassword;
        }
        if (!empty($this->privacyProtocol)) {
            $snmpV3Credentials[] = '--privprotocol';
            $snmpV3Credentials[] = $this->privacyProtocol;
        }

        if (sizeof($snmpV3Credentials) === 1) {
            throw new \InvalidArgumentException('SNMP credentials are empty');
        }
        return $snmpV3Credentials;
    }

    /**
     * @return string
     */
    private function generateV3SnmpString() {
        return implode(' ', $this->generateV3SnmpAsArray());
    }


    public function getSnmpString() {
        if ($this->snmpVersion === '3') {
            return $this->generateV3SnmpString();
        }
        return $this->generateV1OrV2SnmpString();
    }
}
