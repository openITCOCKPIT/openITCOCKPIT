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


namespace itnovum\openITCOCKPIT\Grafana;


use App\itnovum\openITCOCKPIT\Grafana\GrafanaTooltip;

class GrafanaApiConfiguration {
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $graphitePrefix;

    /**
     * @var bool
     */
    private $useHttps;

    /**
     * @var bool
     */
    private $useProxy;

    /**
     * @var bool
     */
    private $ignoreSslCertificate;

    /**
     * @var array
     */
    private $includedHostgroups;

    /**
     * @var array
     */
    private $excludedHostgroups;

    /**
     * @var string
     */
    private $dashboardStyle = 'light';

    /**
     * @var null|string
     */
    private $hostUuid = null;

    /**
     * @var null|string
     */
    private $grafana_uid = null;
    private $graphTooltip;
    private $range;
    private $refresh;

    /**
     * GrafanaConfiguration constructor.
     * @param $apiUrl
     * @param $apiKey
     * @param $graphitePrefix
     * @param $useHttps
     * @param $ignorSslCertificate
     * @param array $includedHostgroups
     * @param array $excludedHostgroups
     */
    public function __construct(
        $apiUrl,
        $apiKey,
        $graphitePrefix,
        $useHttps,
        $useProxy,
        $ignorSslCertificate,
        $includedHostgroups = [],
        $excludedHostgroups = [],
        $dashboardStyle = 'light'
    ) {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->graphitePrefix = $graphitePrefix;
        $this->useHttps = (bool)$useHttps;
        $this->useProxy = (bool)$useProxy;
        $this->ignoreSslCertificate = (bool)$ignorSslCertificate;
        $this->includedHostgroups = $includedHostgroups;
        $this->excludedHostgroups = $excludedHostgroups;
        $this->dashboardStyle = $dashboardStyle;
    }

    /**
     * @param array $configuration
     * @return GrafanaApiConfiguration
     */
    public static function fromArray($configuration) {
        $apiUrl = null;
        $apiKey = null;
        $graphitePrefix = null;
        $useHttps = true;
        $useProxy = true;
        $ignoreSslCertificate = false;
        $dashboardStyle = 'light';
        $includedHostgroups = [];
        $excludedHostgroups = [];

        if (!empty($configuration['api_url'])) {
            $apiUrl = $configuration['api_url'];
        }
        if (!empty($configuration['api_key'])) {
            $apiKey = $configuration['api_key'];
        }
        if (!empty($configuration['graphite_prefix'])) {
            $graphitePrefix = $configuration['graphite_prefix'];
        }
        if (isset($configuration['use_https'])) {
            $useHttps = $configuration['use_https'];
        }
        if (isset($configuration['use_proxy'])) {
            $useProxy = $configuration['use_proxy'];
        }
        if (isset($configuration['ignore_ssl_certificate'])) {
            $ignoreSslCertificate = $configuration['ignore_ssl_certificate'];
        }
        if (!empty($configuration['dashboard_style'])) {
            $dashboardStyle = $configuration['dashboard_style'];
        }

        if (isset($configuration['Hostgroup']) && is_array($configuration['Hostgroup'])) {
            //Test grafana connection request
            foreach ($configuration['Hostgroup'] as $hostgroupId) {
                $includedHostgroups[$hostgroupId] = $hostgroupId;
            }
        }

        if (isset($configuration['Hostgroup_excluded']) && is_array($configuration['Hostgroup_excluded'])) {
            //Test grafana connection request
            foreach ($configuration['Hostgroup_excluded'] as $excludedHostgroupId) {
                $excludedHostgroups[$excludedHostgroupId] = $excludedHostgroupId;
            }
        }

        //Cake2 legacy
        //if (!empty($configuration['GrafanaConfigurationHostgroupMembership'])) {
        //    $includedHostgroups = Hash::combine(
        //        $configuration['GrafanaConfigurationHostgroupMembership'],
        //        '{n}[excluded=0].hostgroup_id',
        //        '{n}[excluded=0].hostgroup_id'
        //    );
        //    $excludedHostgroups = Hash::combine(
        //        $configuration['GrafanaConfigurationHostgroupMembership'],
        //        '{n}[excluded=1].hostgroup_id',
        //        '{n}[excluded=1].hostgroup_id'
        //    );
        //}

        return new self($apiUrl, $apiKey, $graphitePrefix, $useHttps, $useProxy, $ignoreSslCertificate, $includedHostgroups, $excludedHostgroups, $dashboardStyle);
    }

    /**
     * @return string
     */
    public function getApiKey() {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getGraphitePrefix() {
        return $this->graphitePrefix;
    }

    /**
     * @return bool
     */
    public function isUseHttps() {
        return $this->useHttps;
    }

    /**
     * @return bool
     */
    public function isUseProxy() {
        return $this->useProxy;
    }

    /**
     * @return bool
     */
    public function isIgnoreSslCertificate() {
        return !$this->ignoreSslCertificate;
    }

    /**
     * @return array
     */
    public function getIncludedHostgroups() {
        return $this->includedHostgroups;
    }

    /**
     * @return array
     */
    public function getExcludedHostgroups() {
        return $this->excludedHostgroups;
    }

    /**
     * @return string
     */
    public function getApiUrl() {
        if ($this->isDockerGrafana()) {
            //The Docker Grafana of openITCOCKPIT can be accessed for API requests via
            // http://127.0.0.1:8085/grafana
            // Via this URL an login through the API Keys of Grafana is possible.

            // The external URL https://xxx.xxx.xxx.xxx/grafana requires a valid openITCOCKPIT Auth Cookie - not good for API requests.
            return sprintf(
                'http://127.0.0.1:8085%s/api',
                $this->getDockerUrl()
            );
        }

        return sprintf(
            '%s%s/api',
            $this->isUseHttps() ? 'https://' : 'http://',
            $this->apiUrl
        );
    }

    private function getUiUrl() {
        return sprintf(
            '%s%s',
            $this->isUseHttps() ? 'https://' : 'http://',
            $this->apiUrl
        );
    }

    /**
     * @param $uuid
     */
    public function setHostUuid($uuid) {
        $this->hostUuid = $uuid;
    }

    final public function setRefresh(string $refresh): void {
        $this->refresh = $refresh;
    }

    final public function setRange(string $range): void {
        $this->range = $range;
    }

    /**
     * @param int $graphTooltip
     * @return void
     */
    final public function setGraphTooltip(int $graphTooltip): void {
        if (!in_array($graphTooltip, GrafanaTooltip::ALL, true)) {
            return;
        }
        $this->graphTooltip = $graphTooltip;
    }

    /**
     * @param string
     */
    public function setGrafanaUid($uid) {
        if ($uid === '') {
            $uid = null;
        }
        $this->grafana_uid = $uid;
    }

    /**
     * @return string
     */
    public function getIframeUrl() {
        $uiUrl = $this->getUiUrl();
        if ($this->isDockerGrafana()) {
            $uiUrl = $this->getDockerUrl();
        }

        //New Grafana URL >= ? 8.x
        if ($this->grafana_uid !== null) {
            return sprintf(
                '%s/d/%s/%s?theme=%s&kiosk',
                $uiUrl,
                $this->grafana_uid,
                $this->hostUuid,
                $this->dashboardStyle
            );
        }

        //Old grafana 7.x
        return sprintf(
            '%s/dashboard/db/%s?theme=%s&kiosk',
            $uiUrl,
            $this->hostUuid,
            $this->dashboardStyle
        );
    }

    public function getIframeUrlForDatepicker($timerange = 'now-3h', $autorefresh = '0') {
        //&kiosk=tv require Grafana 5.3+ to work
        //https://github.com/grafana/grafana/issues/13493
        //Since Grafana 5.3, users can escape the &kiosk mode by pressing esc key.
        //Also &kiosk=tv is not very helpful. So we implemented an datepicker for now.

        $autoRefreshUrlStr = '';
        if ($autorefresh !== 0 && $autorefresh !== '0') {
            $autoRefreshUrlStr = sprintf('&refresh=%s', $autorefresh);
        }

        $uiUrl = $this->getUiUrl();
        if ($this->isDockerGrafana()) {
            $uiUrl = $this->getDockerUrl();
        }

        if ($this->grafana_uid !== null) {
            //Old Grfana URL <? 5.4
            return sprintf(
                '%s/d/%s/%s?theme=%s%s&from=%s&to=now&kiosk',
                $uiUrl,
                $this->grafana_uid,
                $this->hostUuid,
                $this->dashboardStyle,
                $autoRefreshUrlStr,
                $timerange
            );
        } else {
            //Old Grfana URL < 5.4
            return sprintf(
                '%s/dashboard/db/%s?theme=%s%s&from=%s&to=now&kiosk',
                $uiUrl,
                $this->hostUuid,
                $this->dashboardStyle,
                $autoRefreshUrlStr,
                $timerange
            );
        }
    }

    public function getIframeUrlForUserDashboard($url, $timerange = null, $autorefresh = null) {
        //&kiosk=tv require Grafana 5.3+ to work
        //https://github.com/grafana/grafana/issues/13493
        //Since Grafana 5.3, users can escape the &kiosk mode by pressing esc key.
        //Also &kiosk=tv is not very helpful. So we implemented an datepicker for now.

        $timerange = $timerange ?? $this->range ?? 'now-3h';
        $autorefresh = $autorefresh ?? $this->refresh ?? '0';


        $autoRefreshUrlStr = '';
        if ($autorefresh !== 0 && $autorefresh !== '0') {
            $autoRefreshUrlStr = sprintf('&refresh=%s', $autorefresh);
        }

        $uiUrl = $this->getUiUrl();
        if ($this->isDockerGrafana()) {
            $uiUrl = $this->getDockerUrl();
        }

        $url = sprintf(
            '%s%s?theme=%s%s&from=%s&to=now&kiosk',
            $uiUrl,
            $url,
            $this->dashboardStyle,
            $autoRefreshUrlStr,
            $timerange
        );

        if ($this->isDockerGrafana()) {
            //Remove /grafana prefix to avoid /grafana/grafana
            return substr($url, strlen($this->getDockerUrl()));
        }

        return $url;
    }

    public function isDockerGrafana() {
        return $this->apiUrl === 'grafana.docker';
    }

    public function getDockerUrl() {
        return '/grafana';
    }
}
