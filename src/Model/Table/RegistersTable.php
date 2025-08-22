<?php

namespace App\Model\Table;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use itnovum\openITCOCKPIT\Core\FileDebugger;
use itnovum\openITCOCKPIT\Core\Http;
use itnovum\openITCOCKPIT\Core\PackagemanagerRequestBuilder;

/**
 * Registers Model
 *
 * @method \App\Model\Entity\Register get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Register newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Register[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Register|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Register|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Register patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Register[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Register findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RegistersTable extends Table {

    use LocatorAwareTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('registers');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('license')
            ->maxLength('license', 37)
            ->requirePresence('license', 'create')
            ->notEmptyString('license');

        return $validator;
    }

    /**
     * @return mixed License Array or null
     */
    public function getLicense() {
        $query = $this->getLicenseEntity();
        if (!empty($query)) {
            return $query->toArray();
        }
        return $query;
    }

    /**
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getLicenseEntity() {
        return $this->find()->first();
    }

    /**
     * @param string $license
     * @return array|bool|null
     */
    public function checkLicenseKey($license) {
        if (empty($license)) {
            return [
                'success' => false,
                'error'   => __('Please enter a license key'),
                'license' => null
            ];
        }

        $TableLocator = $this->getTableLocator();
        /** @var ProxiesTable $Proxies */
        $Proxies = $TableLocator->get('Proxies');

        $packagemanagerRequestBuilder = new PackagemanagerRequestBuilder(ENVIRONMENT, $license);
        $http = new Http(
            $packagemanagerRequestBuilder->getUrlForLicenseCheck(),
            $packagemanagerRequestBuilder->getOptions(),
            $Proxies->getSettings()
        );
        $http->sendRequest();
        $error = $http->getLastError();
        $response = json_decode($http->data);

        if (is_object($response)) {
            //wrong spelled "license" comes from license server
            if (property_exists($response, 'license')) {
                if (!empty($response->license) && property_exists($response->license, 'License')) {
                    if (!empty($response->license->License) && strtotime($response->license->License->expire) > time()) {
                        return [
                            'success' => true,
                            'error'   => null,
                            'license' => $response->license->License
                        ];
                    }
                }
            }
        }

        if ($error === false) {
            return [
                'success' => false,
                'error'   => __('Invalid license key'),
                'license' => null
            ];
        }

        return [
            'success' => false,
            'error'   => $error,
            'license' => null
        ];
    }

    public function getCommunityLicenseKey() {
        return 'e5aef99e-817b-0ff5-3f0e-140c1f342792';
    }
}
