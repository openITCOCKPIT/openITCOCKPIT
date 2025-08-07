<?php

namespace App\Model\Entity;

use App\Lib\Constants;
use Cake\ORM\Entity;
use Cake\Utility\Hash;

/**
 * Service Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $servicetemplate_id
 * @property int $host_id
 * @property string|null $name
 * @property string|null $description
 * @property int|null $command_id
 * @property string $check_command_args
 * @property int|null $eventhandler_command_id
 * @property int|null $notify_period_id
 * @property int|null $check_period_id
 * @property float|null $check_interval
 * @property float|null $retry_interval
 * @property int|null $max_check_attempts
 * @property float|null $first_notification_delay
 * @property float|null $notification_interval
 * @property int|null $notify_on_warning
 * @property int|null $notify_on_unknown
 * @property int|null $notify_on_critical
 * @property int|null $notify_on_recovery
 * @property int|null $notify_on_flapping
 * @property int|null $notify_on_downtime
 * @property int|null $is_volatile
 * @property int|null $flap_detection_enabled
 * @property int|null $flap_detection_on_ok
 * @property int|null $flap_detection_on_warning
 * @property int|null $flap_detection_on_unknown
 * @property int|null $flap_detection_on_critical
 * @property float|null $low_flap_threshold
 * @property float|null $high_flap_threshold
 * @property int|null $process_performance_data
 * @property int|null $freshness_checks_enabled
 * @property int|null $freshness_threshold
 * @property int|null $passive_checks_enabled
 * @property int|null $event_handler_enabled
 * @property int|null $active_checks_enabled
 * @property int|null $notifications_enabled
 * @property string|null $notes
 * @property int|null $priority
 * @property string|null $tags
 * @property int|null $own_contacts
 * @property int|null $own_contactgroups
 * @property int|null $own_customvariables
 * @property string|null $service_url
 * @property int|null $sla_relevant
 * @property int $service_type
 * @property int|null $disabled
 * @property int $usage_flag
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Host $host
 * @property \App\Model\Entity\Servicetemplate $servicetemplate
 * @property \CheckmkModule\Model\Entity\Mkservicedata $mkservicedata
 * @property \NewModule\Model\Entity\Servicecommandargumentvalue[] $servicecommandargumentvalues
 * @property \App\Model\Entity\Servicegroup[] $servicegroups
 * @property \PrometheusModule\Model\Entity\PrometheusAlertRule prometheus_alert_rule
 */
class Service extends Entity {
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected array $_accessible = [
        'uuid'                              => true,
        'servicetemplate_id'                => true,
        'host_id'                           => true,
        'name'                              => true,
        'description'                       => true,
        'command_id'                        => true,
        'check_command_args'                => true,
        'eventhandler_command_id'           => true,
        'notify_period_id'                  => true,
        'check_period_id'                   => true,
        'check_interval'                    => true,
        'retry_interval'                    => true,
        'max_check_attempts'                => true,
        'first_notification_delay'          => true,
        'notification_interval'             => true,
        'notify_on_warning'                 => true,
        'notify_on_unknown'                 => true,
        'notify_on_critical'                => true,
        'notify_on_recovery'                => true,
        'notify_on_flapping'                => true,
        'notify_on_downtime'                => true,
        'is_volatile'                       => true,
        'flap_detection_enabled'            => true,
        'flap_detection_on_ok'              => true,
        'flap_detection_on_warning'         => true,
        'flap_detection_on_unknown'         => true,
        'flap_detection_on_critical'        => true,
        'low_flap_threshold'                => true,
        'high_flap_threshold'               => true,
        'process_performance_data'          => true,
        'freshness_checks_enabled'          => true,
        'freshness_threshold'               => true,
        'passive_checks_enabled'            => true,
        'event_handler_enabled'             => true,
        'active_checks_enabled'             => true,
        'notifications_enabled'             => true,
        'notes'                             => true,
        'priority'                          => true,
        'tags'                              => true,
        'own_contacts'                      => true,
        'own_contactgroups'                 => true,
        'own_customvariables'               => true,
        'service_url'                       => true,
        'sla_relevant'                      => true,
        'service_type'                      => true,
        'disabled'                          => true,
        'usage_flag'                        => true,
        'created'                           => true,
        'modified'                          => true,
        'host'                              => true,
        'servicetemplate'                   => true,
        'mkservicedata'                     => true,
        'servicecommandargumentvalues'      => true,
        'serviceeventcommandargumentvalues' => true,
        'customvariables'                   => true,
        'contactgroups'                     => true,
        'contacts'                          => true,
        'servicegroups'                     => true,
        'prometheus_alert_rule'             => true
    ];

    /**
     * @param Servicetemplate $servicetemplate
     * @return array
     */
    public function getCommandargumentValuesForCfg(Servicetemplate $servicetemplate) {
        $servicecommandargumentvaluesForCfg = [];
        $servicecommandargumentvalues = $this->get('servicecommandargumentvalues');
        if (!empty($servicecommandargumentvalues)) {
            $service = $this->toArray();
            $servicetemplate = $servicetemplate->toArray();
            if ($service['command_id'] === null || $service['command_id'] == $servicetemplate['command_id']) {
                $servicecommandargumentvalues = $service['servicecommandargumentvalues'];

                $commandArgumentValuesDiff = array_diff(
                    Hash::extract($servicetemplate['servicetemplatecommandargumentvalues'], '{n}.commandargument_id'),
                    Hash::extract($servicecommandargumentvalues, '{n}.commandargument_id')
                );
                if (!empty($commandArgumentValuesDiff)) {
                    //add missing (new) command argument values from service template to service command argument values
                    foreach ($commandArgumentValuesDiff as $commandArgumentValueId) {
                        $key = array_search(
                            $commandArgumentValueId,
                            array_column($servicetemplate['servicetemplatecommandargumentvalues'], 'commandargument_id'), true
                        );
                        $servicecommandargumentvalues[] = $servicetemplate['servicetemplatecommandargumentvalues'][$key];
                    }
                }
            }

            foreach ($servicecommandargumentvalues as $servicecommandargumentvalue) {
                $servicecommandargumentvaluesForCfg[] = [
                    'name'       => $servicecommandargumentvalue['commandargument']['name'],
                    'human_name' => $servicecommandargumentvalue['commandargument']['human_name'],
                    'value'      => $servicecommandargumentvalue['value']
                ];
            }
        }
        return Hash::sort($servicecommandargumentvaluesForCfg, '{n}.name', 'asc', 'natural');
    }

    /**
     * @return array
     */
    public function getEventhandlerCommandargumentValuesForCfg() {
        $serviceeventcommandargumentvaluesForCfg = [];
        $serviceeventcommandargumentvalues = $this->get('serviceeventcommandargumentvalues');

        foreach ($serviceeventcommandargumentvalues as $serviceeventcommandargumentvalue) {
            /** @var $serviceeventcommandargumentvalue Serviceeventcommandargumentvalue */
            $serviceeventcommandargumentvaluesForCfg[] = [
                'name'       => $serviceeventcommandargumentvalue->get('commandargument')->get('name'),
                'human_name' => $serviceeventcommandargumentvalue->get('commandargument')->get('human_name'),
                'value'      => $serviceeventcommandargumentvalue->get('value')
            ];
        }

        return Hash::sort($serviceeventcommandargumentvaluesForCfg, '{n}.name', 'asc', 'natural');
    }

    public function hasEventhandler() {
        return $this->eventhandler_command_id > 0;
    }

    /**
     * @return bool
     */
    public function hasCustomvariables() {
        return !empty($this->customvariables);
    }

    /**
     * @return array
     */
    public function getCustomvariablesForCfg() {
        $cfgValues = [];
        foreach ($this->customvariables as $Customvariable) {
            /** @var Customvariable $Customvariable */
            $key = sprintf('_%s', $Customvariable->get('name'));
            $cfgValues[$key] = $Customvariable->get('value');
        }
        return $cfgValues;
    }

    /**
     * @return string
     */
    public function getServicegroupsForCfg() {
        $servicegroups = [];
        foreach ($this->servicegroups as $servicegroup) {
            /** @var Servicegroup $servicegroup */
            $servicegroups[] = $servicegroup->get('uuid');
        }
        return implode(',', $servicegroups);
    }

    /**
     * @return string
     */
    public function getContactsforCfg() {
        $contacts = [];
        foreach ($this->get('contacts') as $contact) {
            /** @var $contact Contact */
            $contacts[] = $contact->get('uuid');
        }
        return implode(',', $contacts);
    }

    /**
     * @return string
     */
    public function getContactgroupsforCfg() {
        $contactgroups = [];
        foreach ($this->get('contactgroups') as $contactgroup) {
            /** @var $contactgroup Contactgroup */
            $contactgroups[] = $contactgroup->get('uuid');
        }
        return implode(',', $contactgroups);
    }

    /**
     * @param Servicetemplate $servicetemplate
     * @return string
     */
    public function getNotificationOptionsForCfg(Servicetemplate $servicetemplate) {
        $cfgValues = [];
        $fields = [
            'notify_on_recovery' => 'r',
            'notify_on_warning'  => 'w',
            'notify_on_critical' => 'c',
            'notify_on_unknown'  => 'u',
            'notify_on_flapping' => 'f',
            'notify_on_downtime' => 's'
        ];


        //Does the service have own notification options?
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === null) {
                //Use the value of the service template
                if ($servicetemplate->get($field) === 1) {
                    $cfgValues[] = $cfgValue;
                }
            }

            if ($this->get($field) === 1) {
                //Service has defined its own value
                $cfgValues[] = $cfgValue;
            }
        }

        $notificationOptions = implode(',', $cfgValues);

        if ($notificationOptions === $servicetemplate->getServiceNotificationOptionsForCfg()) {
            //Service has the same notification options like the service template - go for inheritance
            return '';
        }

        return $notificationOptions;
    }

    /**
     * @param Servicetemplate $servicetemplate
     * @return string
     */
    public function getFlapdetectionOptionsForCfg(Servicetemplate $servicetemplate) {
        $cfgValues = [];
        $fields = [
            'flap_detection_on_ok'       => 'o',
            'flap_detection_on_warning'  => 'w',
            'flap_detection_on_critical' => 'c',
            'flap_detection_on_unknown'  => 'u'
        ];


        //Does the service have own flap detection options?
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === null) {
                //Use the value of the service template
                if ($servicetemplate->get($field) === 1) {
                    $cfgValues[] = $cfgValue;
                }
            }

            if ($this->get($field) === 1) {
                //Service has defined its own value
                $cfgValues[] = $cfgValue;
            }
        }

        $flapdetectionOptions = implode(',', $cfgValues);

        if ($flapdetectionOptions === $servicetemplate->getServiceFlapDetectionOptionsForCfg()) {
            //Service has the same flap detection options like the service template - go for inheritance
            return '';
        }

        return $flapdetectionOptions;
    }

    /**
     * @return array
     */
    public function getContainerIds() {
        $containerIds = [
            $this->host->container_id
        ];

        foreach ($this->host->hosts_to_containers_sharing as $container) {
            /** @var Container $container */
            $containerIds[] = $container->get('id');
        }

        return array_unique($containerIds);
    }

    /**
     * @return array
     */
    public function isUsedByModules() {
        $Constants = new Constants();
        $moduleConstants = $Constants->getModuleConstants();

        $usedBy = [];
        foreach ($moduleConstants as $moduleName => $moduleId) {
            if ($this->usage_flag & $moduleId) {
                switch ($moduleId) {
                    case AUTOREPORT_MODULE:
                        $usedBy[$moduleName] = [
                            'baseUrl' => '#',
                            'state'   => 'AutoreportsServiceUsedBy',
                            'message' => __('Used by Autoreport module'),
                            'module'  => 'AutoreportModule',
                            'id'      => $this->id
                        ];
                        break;
                    case EVENTCORRELATION_MODULE:
                        $usedBy[$moduleName] = [
                            'baseUrl' => '#',
                            'state'   => 'EventcorrelationsServiceUsedBy',
                            'message' => __('Used by Eventcorrelation module'),
                            'module'  => 'EventcorrelationModule',
                            'id'      => $this->id
                        ];
                        break;
                    default:
                        $usedBy[$moduleName] = $moduleId;
                        break;
                }
            }
        }
        return $usedBy;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function getValueOf(string $field) {
        if ($this->{$field} !== null && $this->{$field} !== '') {
            // Use the value of the Service
            return $this->{$field};
        }

        // Use the value of the servicetemplate
        return $this->servicetemplate->{$field};
    }

}
