<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
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

declare(strict_types=1);

namespace GrafanaModule\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Behavior\TimestampBehavior;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use GrafanaModule\Model\Entity\GrafanaConfiguration;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use itnovum\openITCOCKPIT\Grafana\GrafanaApiConfiguration;

/**
 * GrafanaConfigurations Model
 *
 * @method GrafanaConfiguration get($primaryKey, $options = [])
 * @method GrafanaConfiguration newEntity($data = null, array $options = [])
 * @method GrafanaConfiguration[] newEntities(array $data, array $options = [])
 * @method GrafanaConfiguration|false save(EntityInterface $entity, $options = [])
 * @method GrafanaConfiguration saveOrFail(EntityInterface $entity, $options = [])
 * @method GrafanaConfiguration patchEntity(EntityInterface $entity, array $data, array $options = [])
 * @method GrafanaConfiguration[] patchEntities($entities, array $data, array $options = [])
 * @method GrafanaConfiguration findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin TimestampBehavior
 */
class GrafanaConfigurationsTable extends Table {
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('grafana_configurations');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('GrafanaConfigurationHostgroupMembership', [
            'foreignKey'   => 'configuration_id',
            'joinType'     => 'INNER',
            'className'    => 'GrafanaModule.GrafanaConfigurationHostgroupMembership',
            'saveStrategy' => 'replace'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param Validator $validator Validator instance.
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('api_url')
            ->maxLength('api_url', 200)
            ->requirePresence('api_url', 'create')
            ->notEmptyString('api_url');

        $validator
            ->scalar('api_key')
            ->maxLength('api_key', 200)
            ->requirePresence('api_key', 'create')
            ->notEmptyString('api_key');

        $validator
            ->scalar('graphite_prefix')
            ->maxLength('graphite_prefix', 200)
            ->requirePresence('graphite_prefix', 'create')
            ->notEmptyString('graphite_prefix');

        $validator
            ->boolean('use_https')
            ->requirePresence('use_https', 'create')
            ->notEmptyString('use_https');

        $validator
            ->boolean('use_proxy')
            ->requirePresence('use_proxy', 'create')
            ->notEmptyString('use_proxy');

        $validator
            ->boolean('ignore_ssl_certificate')
            ->requirePresence('ignore_ssl_certificate', 'create')
            ->notEmptyString('ignore_ssl_certificate');

        $validator
            ->scalar('dashboard_style')
            ->maxLength('dashboard_style', 200)
            ->requirePresence('dashboard_style', 'create')
            ->notEmptyString('dashboard_style');

        return $validator;
    }

    /**
     * @return int 1
     */
    public function getConfigurationId() {
        // 1 is the default id of the grafana configuration because at the moment
        // openITCOCKPIT supports only one Grafana configuration.
        return 1;
    }

    /**
     * @return array
     */
    public function getGrafanaConfigurationForEdit() {
        try {
            $result = $this->find()
                ->contain(['GrafanaConfigurationHostgroupMembership'])
                ->disableHydration()
                ->firstOrFail();

            foreach ($result['grafana_configuration_hostgroup_membership'] as $hostgroup) {
                if ($hostgroup['excluded'] === 0) {
                    $result['Hostgroup'][] = $hostgroup['hostgroup_id'];
                } else {
                    $result['Hostgroup_excluded'][] = $hostgroup['hostgroup_id'];
                }
            }

            unset($result['grafana_configuration_hostgroup_membership']);
            return $result;
        } catch (RecordNotFoundException $e) {
            return [
                'id'                     => $this->getConfigurationId(), //its 1 every time
                'api_url'                => '',
                'api_key'                => '',
                'graphite_prefix'        => '',
                'use_https'              => 1,
                'use_proxy'              => 0,
                'ignore_ssl_certificate' => 0,
                'dashboard_style'        => 'light',
                'Hostgroup'              => [],
                'Hostgroup_excluded'     => []
            ];
        }
    }

    /**
     * @return array
     */
    public function getGrafanaConfiguration() {
        return $this->getGrafanaConfigurationForEdit();
    }

    /**
     * @return array|EntityInterface
     */
    public function getGrafanaConfigurationEntity() {
        try {
            return $this->find()->firstOrFail();
        } catch (RecordNotFoundException $e) {
            return $this->newEmptyEntity();
        }
    }


    /**
     * @param GrafanaApiConfiguration $GrafanaApiConfiguration
     * @param array $proxySettings
     * @return Client|string
     */
    public function testConnection(GrafanaApiConfiguration $GrafanaApiConfiguration, array $proxySettings) {
        $options = [
            'headers' => [
                'authorization' => 'Bearer ' . $GrafanaApiConfiguration->getApiKey()
            ],
            'verify'  => $GrafanaApiConfiguration->isIgnoreSslCertificate()
        ];
        if ($GrafanaApiConfiguration->isUseProxy() && !(empty($proxySettings['ipaddress']) & empty($proxySettings['port']))) {
            $options['proxy'] = [
                'http'  => sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port']),
                'https' => sprintf('%s:%s', $proxySettings['ipaddress'], $proxySettings['port'])
            ];
        } else {
            $options['proxy'] = [
                'http'  => '',
                'https' => ''
            ];
        }
        $client = new Client($options);
        $request = new Request('GET', $GrafanaApiConfiguration->getApiUrl() . '/org');
        try {
            $response = $client->send($request);
        } catch (\Exception $e) {
            if ($e instanceof ClientException) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                return $responseBody;
            }
            return $e->getMessage();
        }
        if ($response->getStatusCode() == 200) {
            return $client;
        }
    }

    /**
     * @param GrafanaApiConfiguration $GrafanaApiConfiguration
     * @param $proxySettings
     * @param $uid
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function existsUserDashboard(GrafanaApiConfiguration $GrafanaApiConfiguration, $proxySettings, $uid) {
        $client = $this->testConnection($GrafanaApiConfiguration, $proxySettings);
        $request = new \GuzzleHttp\Psr7\Request(
            'GET',
            sprintf('%s/dashboards/uid/%s', $GrafanaApiConfiguration->getApiUrl(), $uid),
            ['content-type' => 'application/json']
        );
        try {
            $response = $client->send($request);
        } catch (\Exception $e) {
            //debug($e->getMessage());
            return false;
        }
        if ($response->getStatusCode() == 200) {
            return true;
        }
        return false;
    }

    /**
     * Called by UPDATE.sh and SETUP.sh of openITCOCKPIT
     *
     * @param string $apiUrl
     * @param string $apiToken
     * @return array|true
     */
    public function saveGrafanaConfigurationForSetupAndUpdate(string $apiUrl, string $apiToken) {
        $enity = $this->getGrafanaConfigurationEntity();
        $enity->set('id', $this->getConfigurationId());
        $enity->set('api_url', $apiUrl);
        $enity->set('api_key', $apiToken);
        $enity->set('graphite_prefix', 'openitcockpit');
        $enity->set('use_https', 1);
        $enity->set('use_proxy', 0);
        $enity->set('ignore_ssl_certificate', 1);
        $enity->set('dashboard_style', 'light');

        $this->save($enity);
        if ($enity->hasErrors()) {
            return $enity->getErrors();
        }

        return true;
    }
}
