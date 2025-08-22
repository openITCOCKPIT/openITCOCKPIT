<?php

namespace App\Model\Table;

use App\Lib\Traits\Cake2ResultTableTrait;
use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Model;

/**
 * Systemsettings Model
 *
 * @method \App\Model\Entity\Systemsetting get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Systemsetting newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Systemsetting[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Systemsetting|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Systemsetting|bool saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Systemsetting patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Systemsetting[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Systemsetting findOrCreate($search, ?callable $callback = null, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class SystemsettingsTable extends Table {
    use LocatorAwareTrait;
    use Cake2ResultTableTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void {
        parent::initialize($config);

        $this->setTable('systemsettings');
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

        return $validator;
    }


    /**
     * @param bool $asEntity
     * @return array|Query
     */
    public function getSystemsettings($asEntity = false) {
        $query = $this->find('all');
        if ($asEntity) {
            return $query->all()->toArray();
        }
        return $query->disableHydration()->toArray();
    }

    /**
     * @return array
     */
    public function getSettings() {
        $systemsettings = $this->getSystemsettings();
        $all_systemsettings = [];
        foreach ($systemsettings as $systemsetting) {
            $all_systemsettings[$systemsetting['section']][] = $systemsetting;
        }
        // sort the list like it is in openITCOCKPIT\InitialDatabase\Systemsettings
        // it is just sorting, no deletions, no additions
        //require_once OLD_APP . 'src' . DS . 'itnovum' . DS . 'openITCOCKPIT' . DS . 'InitialDatabase' . DS . 'Systemsetting.php';
        //$mySytemsettings = new \itnovum\openITCOCKPIT\InitialDatabase\Systemsetting(new Model());
        //$myData = $mySytemsettings->getData();
        $sortedSystemSettingsSchema = $sortedSystemSettings = [];

        //foreach ($myData as $singleSetting) {
        //    $sortedSystemSettingsSchema[$singleSetting['Systemsetting']['section']][] = $singleSetting['Systemsetting']['key'];
        //}

        foreach ($sortedSystemSettingsSchema as $sSectionName => $sSection) {
            foreach ($sSection as $sSettingOptionKey) {
                // looping through our Settings
                foreach ($all_systemsettings as $nsSectionName => $nsSection) {
                    if ($sSectionName === $nsSectionName) {
                        foreach ($nsSection as $nsSectionK => $nsSettingOption) {
                            if ($sSettingOptionKey === $nsSettingOption['key']) {
                                $sortedSystemSettings[$sSectionName][] = $nsSettingOption;
                                unset($all_systemsettings[$nsSectionName][$nsSectionK]);
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // if in DB there are some options, but not in Schema, we place them at the end
        foreach ($all_systemsettings as $nsSectionName => $nsSection) {
            foreach ($nsSection as $nsSettingOption) {
                $sortedSystemSettings[$nsSectionName][] = $nsSettingOption;
            }
        }
        return $sortedSystemSettings;
    }

    /**
     * @return array
     */
    public function findAsArray() {
        $systemsettings = $this->getSettings();
        $return = [];
        if (!is_null($systemsettings)) {
            foreach ($systemsettings as $key => $value) {
                $return[$key] = [];
                foreach ($value as $systemsetting) {
                    $return[$key][$systemsetting['key']] = $systemsetting['value'];
                }
            }
        }
        return $return;
    }

    /**
     * @param string $section
     * @return array
     */
    public function findAsArraySection($section = '') {
        $query = $this->find()->where([
            'section' => $section
        ]);
        $systemsettings = $query->disableHydration()->toArray();

        $return = [];
        if (!is_null($systemsettings)) {
            foreach ($systemsettings as $values) {
                $return[$section][$values['key']] = $values['value'];
            }
        }
        return $return;
    }

    /**
     * @return mixed
     */
    public function getMasterInstanceName() {
        if (!Cache::read('systemsettings_master_instance', 'permissions')) {
            $name = $this->findAsArraySection('FRONTEND')['FRONTEND']['FRONTEND.MASTER_INSTANCE'];
            Cache::write('systemsettings_master_instance', $name, 'permissions');
        }
        return Cache::read('systemsettings_master_instance', 'permissions');
    }

    /**
     * @return mixed
     */
    public function getQueryHandlerPath() {
        if (!Cache::read('systemsettings_qh_path', 'permissions')) {
            $path = $this->findAsArraySection('MONITORING')['MONITORING']['MONITORING.QUERY_HANDLER'];
            Cache::write('systemsettings_qh_path', $path, 'permissions');
        }
        return Cache::read('systemsettings_qh_path', 'permissions');
    }

    public function isLdapAuth() {
        if (!Cache::read('systemsettings_is_ldap_auth', 'permissions')) {
            $settings = $this->findAsArraySection('FRONTEND');
            $value = false;
            if ($settings['FRONTEND']['FRONTEND.AUTH_METHOD'] === 'ldap' || $settings['FRONTEND']['FRONTEND.AUTH_METHOD'] === 'sso_ldap') {
                $value = true;
            }

            Cache::write('systemsettings_is_ldap_auth', $value, 'permissions');
        }
        return Cache::read('systemsettings_is_ldap_auth', 'permissions');
    }

    public function isOAuth2() {
        if (!Cache::read('systemsettings_is_o_auth_2', 'permissions')) {
            $settings = $this->findAsArraySection('FRONTEND');
            $value = false;
            if ($settings['FRONTEND']['FRONTEND.AUTH_METHOD'] === 'sso' || $settings['FRONTEND']['FRONTEND.AUTH_METHOD'] === 'sso_ldap') {
                $value = true;
            }
            Cache::write('systemsettings_is_o_auth_2', $value, 'permissions');
        }
        return Cache::read('systemsettings_is_o_auth_2', 'permissions');
    }

    /**
     * @return bool
     */
    public function blurCheckCommand() {
        if (!Cache::read('systemsettings_blur_command_line_in_browser', 'permissions')) {
            $settings = $this->findAsArraySection('FRONTEND');
            $value = $settings['FRONTEND']['FRONTEND.BLUR_COMMAND_LINE_IN_BROWSER'] === '1';
            Cache::write('systemsettings_blur_command_line_in_browser', $value, 'permissions');
        }
        return Cache::read('systemsettings_blur_command_line_in_browser', 'permissions');
    }

    /**
     * @param string $key
     * @return array
     */
    public function getSystemsettingByKeyAsCake2($key) {
        $query = $this->find()
            ->where([
                'Systemsettings.key' => $key
            ])
            ->first();
        return $this->formatFirstResultAsCake2($query->toArray());
    }

    /**
     * @param string $key
     * @return \Cake\Datasource\EntityInterface
     */
    public function getSystemsettingByKey($key) {
        return $this->find()
            ->where([
                'Systemsettings.key' => $key
            ])
            ->firstOrFail();
    }

    /**
     * @return bool
     */
    public function isLoginAnimationDisabled() {
        try {
            $result = $this->getSystemsettingByKey('FRONTEND.DISABLE_LOGIN_ANIMATION');

            $value = (int)$result->get('value');
            return $value === 1;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * @return bool
     */
    public function isOpenLdapServer() {
        try {
            $result = $this->getSystemsettingByKey('FRONTEND.LDAP.TYPE');

            return $result->get('value') === 'openldap';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getOAuthConfig() {
        $query = $this->find()
            ->where([
                'Systemsettings.key IN' => [
                    'FRONTEND.SSO.CLIENT_ID',
                    'FRONTEND.SSO.CLIENT_SECRET',
                    'FRONTEND.SSO.AUTH_ENDPOINT',
                    'FRONTEND.SSO.TOKEN_ENDPOINT',
                    'FRONTEND.SSO.USER_ENDPOINT',
                    'FRONTEND.SSO.NO_EMAIL_MESSAGE',
                    'FRONTEND.SSO.LOG_OFF_LINK',
                    'FRONTEND.SSO.AUTH_PROVIDER'
                ]
            ])
            ->disableHydration()
            ->all();

        $result = $query->toArray();
        return Hash::combine($result, '{n}.key', '{n}.value');
    }

    /**
     * @return bool
     */
    public function isWebsiteWidgetEnabled() {
        try {
            if (!Cache::read('FRONTEND.ENABLE_IFRAME_IN_DASHBOARDS', 'permissions')) {
                $result = $this->getSystemsettingByKey('FRONTEND.ENABLE_IFRAME_IN_DASHBOARDS');
                $value = (int)$result->get('value');
                $value = $value === 1;

                Cache::write('FRONTEND.ENABLE_IFRAME_IN_DASHBOARDS', $value, 'permissions');
            }

            return Cache::read('FRONTEND.ENABLE_IFRAME_IN_DASHBOARDS', 'permissions');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function existsByKey($key) {
        return $this->exists(['key' => $key]);

    }
}
