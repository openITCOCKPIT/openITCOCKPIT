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

namespace itnovum\openITCOCKPIT\Agent;

use App\Model\Table\SystemsettingsTable;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\System\Health\SystemId;

/**
 * needs: mkdir -p /opt/openitc/agent && chown www-data:www-data -R /opt/openitc/agent
 *
 * Class AgentCertificateData
 */
class AgentCertificateData {

    /**
     * Use Elliptic Curve Cryptography
     * @var bool
     */
    private $ECC_CA = false;

    /**
     * How long till expiry of the server CA
     * 100 years
     * @var int
     */
    private $days_CA = 36500;

    /**
     * @var string
     */
    private $caCertFile = '/opt/openitc/agent/server_ca.pem';

    /**
     * @var string
     */
    private $caKeyFile = '/opt/openitc/agent/server_ca.key';

    public function __construct() {

    }

    public function isEccCa(): bool {
        return $this->ECC_CA;
    }

    public function getCaDayLifetime(): int {
        return $this->days_CA;
    }

    public function getCaCertFile(): string {
        return $this->caCertFile;
    }

    public function getCaCert(): string {
        return file_get_contents($this->getCaCertFile());
    }

    public function getCaKeyFile(): string {
        return $this->caKeyFile;
    }

    /**
     * @param string $csr
     * @return string agent certificate
     */
    public function signAgentCsr($csr): string {
        // Generate signed cert from csr
        $x509 = openssl_csr_sign(
            $csr,
            file_get_contents($this->getCaCertFile()),
            file_get_contents($this->getCaKeyFile()),
            3650,
            [
                'digest_alg'      => 'sha512',
                'x509_extensions' => 'v3_req'
            ],
            time()
        );

        openssl_x509_export($x509, $signedAgentCert);
        return $signedAgentCert;
    }

    public function generateServerCA() {
        // Generate initial agent server ca certificate
        $SystemId = new SystemId();

        $subject = [
            "commonName" => $SystemId->getSystemId() . '.agentserver.oitc',
        ];

        $folderpath = dirname($this->getCaCertFile());
        if (!file_exists($folderpath) || !is_dir($folderpath)) {
            mkdir($folderpath, 0777, true);
        }

        // Generate a new private key
        $digest_alg = 'sha512';
        $private_key = openssl_pkey_new([
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            "digest_alg"       => $digest_alg,
            "private_key_bits" => 4096,
        ]);
        if ($this->isEccCa()) {
            $digest_alg = 'sha384';
            $private_key = openssl_pkey_new([
                "private_key_type" => OPENSSL_KEYTYPE_EC,
                "curve_name"       => 'prime256v1',
            ]);
        }

        $csr = openssl_csr_new($subject, $private_key, ['digest_alg' => $digest_alg]);

        $days = $this->getCaDayLifetime();
        $x509 = openssl_csr_sign($csr, null, $private_key, $days, ['digest_alg' => $digest_alg], time());
        openssl_x509_export_to_file($x509, $this->getCaCertFile());
        openssl_pkey_export_to_file($private_key, $this->getCaKeyFile());
        sleep(1); // ?

        /** @var SystemsettingsTable $SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $systemsettings = $SystemsettingsTable->findAsArray();
        $user = $systemsettings['WEBSERVER']['WEBSERVER.USER'];
        $group = $systemsettings['MONITORING']['MONITORING.GROUP'];

        chown($this->getCaCertFile(), $user);
        chgrp($this->getCaCertFile(), $group);
        chmod($this->getCaCertFile(), 0640);

        chown($this->getCaKeyFile(), $user);
        chgrp($this->getCaKeyFile(), $group);
        chmod($this->getCaKeyFile(), 0640);
    }
}
