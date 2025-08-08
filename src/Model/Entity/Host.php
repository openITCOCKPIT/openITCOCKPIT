<?php

namespace App\Model\Entity;

use App\Lib\Constants;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use SLAModule\Model\Entity\Sla;

/**
 * Host Entity
 *
 * @property int $id
 * @property string $uuid
 * @property int $container_id
 * @property string $name
 * @property string|null $description
 * @property int $hosttemplate_id
 * @property string $address
 * @property int|null $command_id
 * @property int|null $eventhandler_command_id
 * @property int|null $timeperiod_id
 * @property int|null $check_interval
 * @property int|null $retry_interval
 * @property int|null $max_check_attempts
 * @property float|null $first_notification_delay
 * @property float|null $notification_interval
 * @property int|null $notify_on_down
 * @property int|null $notify_on_unreachable
 * @property int|null $notify_on_recovery
 * @property int|null $notify_on_flapping
 * @property int|null $notify_on_downtime
 * @property int|null $flap_detection_enabled
 * @property int|null $flap_detection_on_up
 * @property int|null $flap_detection_on_down
 * @property int|null $flap_detection_on_unreachable
 * @property float|null $low_flap_threshold
 * @property float|null $high_flap_threshold
 * @property int|null $process_performance_data
 * @property int|null $freshness_checks_enabled
 * @property int|null $freshness_threshold
 * @property int|null $passive_checks_enabled
 * @property int|null $event_handler_enabled
 * @property int|null $active_checks_enabled
 * @property int|null $retain_status_information
 * @property int|null $retain_nonstatus_information
 * @property int|null $notifications_enabled
 * @property string|null $notes
 * @property int|null $priority
 * @property int|null $check_period_id
 * @property int|null $notify_period_id
 * @property string|null $tags
 * @property int $own_contacts
 * @property int $own_contactgroups
 * @property int $own_customvariables
 * @property string|null $host_url
 * @property int|null $satellite_id
 * @property int $host_type
 * @property int|null $disabled
 * @property int $usage_flag
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\Hosttemplate $hosttemplate
 * @property \App\Model\Entity\Command $command
 * @property \App\Model\Entity\EventhandlerCommand $eventhandler_command
 * @property \App\Model\Entity\Timeperiod $timeperiod
 * @property \App\Model\Entity\Timeperiod $check_period
 * @property \App\Model\Entity\Timeperiod $notify_period
 * @property \App\Model\Entity\Satellite $satellite
 * @property \App\Model\Entity\Contactgroup[] $contactgroups
 * @property \App\Model\Entity\Contact[] $contacts
 * @property \App\Model\Entity\DeletedHost[] $deleted_hosts
 * @property \App\Model\Entity\DeletedService[] $deleted_services
 * @property \App\Model\Entity\Eventcorrelation[] $eventcorrelations
 * @property \App\Model\Entity\Hostcommandargumentvalue[] $hostcommandargumentvalues
 * @property \App\Model\Entity\Container[] $hosts_to_containers_sharing
 * @property \App\Model\Entity\HostsToHostdependency[] $hosts_to_hostdependencies
 * @property \App\Model\Entity\HostsToHostescalation[] $hosts_to_hostescalations
 * @property \App\Model\Entity\Hostgroup[] $hostgroups
 * @property \App\Model\Entity\Host[] $parenthosts
 * @property \App\Model\Entity\Service[] $services
 * @property \App\Model\Entity\Customvariable[] $customvariables
 * @property \PrometheusModule\Model\Entity\PrometheusExporter[] $prometheus_exporters
 * @property \PrometheusModule\Model\Entity\PrometheusAlertRule prometheus_alert_rule
 * @property Sla $sla
 */
class Host extends Entity {

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
        'uuid'                          => true,
        'container_id'                  => true,
        'name'                          => true,
        'description'                   => true,
        'hosttemplate_id'               => true,
        'address'                       => true,
        'command_id'                    => true,
        'eventhandler_command_id'       => true,
        'timeperiod_id'                 => true,
        'check_interval'                => true,
        'retry_interval'                => true,
        'max_check_attempts'            => true,
        'first_notification_delay'      => true,
        'notification_interval'         => true,
        'notify_on_down'                => true,
        'notify_on_unreachable'         => true,
        'notify_on_recovery'            => true,
        'notify_on_flapping'            => true,
        'notify_on_downtime'            => true,
        'flap_detection_enabled'        => true,
        'flap_detection_on_up'          => true,
        'flap_detection_on_down'        => true,
        'flap_detection_on_unreachable' => true,
        'low_flap_threshold'            => true,
        'high_flap_threshold'           => true,
        'process_performance_data'      => true,
        'freshness_checks_enabled'      => true,
        'freshness_threshold'           => true,
        'passive_checks_enabled'        => true,
        'event_handler_enabled'         => true,
        'active_checks_enabled'         => true,
        'retain_status_information'     => true,
        'retain_nonstatus_information'  => true,
        'notifications_enabled'         => true,
        'notes'                         => true,
        'priority'                      => true,
        'check_period_id'               => true,
        'notify_period_id'              => true,
        'tags'                          => true,
        'own_contacts'                  => true,
        'own_contactgroups'             => true,
        'own_customvariables'           => true,
        'host_url'                      => true,
        'satellite_id'                  => true,
        'host_type'                     => true,
        'disabled'                      => true,
        'usage_flag'                    => true,
        'created'                       => true,
        'modified'                      => true,
        'container'                     => true,
        'hosttemplate'                  => true,
        'command'                       => true,
        'eventhandler_command'          => true,
        'timeperiod'                    => true,
        'check_period'                  => true,
        'notify_period'                 => true,
        'satellite'                     => true,
        'contactgroups'                 => true,
        'contacts'                      => true,
        'eventcorrelations'             => true,
        'hostcommandargumentvalues'     => true,
        'hosts_to_containers_sharing'   => true,
        'hosts_to_hostdependencies'     => true,
        'hosts_to_hostescalations'      => true,
        'hostgroups'                    => true,
        'parenthosts'                   => true,
        'services'                      => true,
        'customvariables'               => true,
        'prometheus_exporters'          => true,
        'prometheus_alert_rule'         => true,
        'sla_id'                        => true,
        'sla'                           => true
    ];

    /**
     * @return array
     */
    public function getContainerIds() {
        $containerIds = [
            $this->container_id
        ];

        foreach ($this->hosts_to_containers_sharing as $container) {
            /** @var Container $container */
            $containerIds[] = $container->get('id');
        }

        return array_unique($containerIds);
    }

    /**
     * @return bool
     */
    public function hasParentHostsForExport() {
        return !empty($this->getParentHostsForCfgAsArray());
    }

    /**
     * @return string
     */
    public function getParentHostsForCfg() {
        return implode(',', $this->getParentHostsForCfgAsArray());
    }

    /**
     * @return array
     */
    public function getParentHostsForCfgAsArray() {
        $parenthosts = [];
        foreach ($this->parenthosts as $parenthost) {
            if ($parenthost->get('disabled') === 0) {
                $parenthosts[] = $parenthost->get('uuid');
            }
        }

        return $parenthosts;
    }

    /**
     * @return array
     */
    public function getParentHostsForSatCfgAsArray() {
        $parenthosts = [];
        foreach ($this->parenthosts as $parenthost) {
            /** @var $parenthost Host */
            if ($parenthost->get('disabled') === 0 && $parenthost->get('satellite_id') === $this->get('satellite_id')) {
                $parenthosts[] = $parenthost->get('uuid');
            }
        }

        return $parenthosts;
    }

    /**
     * @param Hosttemplate $hosttemplate
     * @return array
     */
    public function getCommandargumentValuesForCfg(Hosttemplate $hosttemplate) {
        $hostcommandargumentvaluesForCfg = [];
        $hostcommandargumentvalues = $this->get('hostcommandargumentvalues');
        if (!empty($hostcommandargumentvalues)) {
            $host = $this->toArray();
            $hosttemplate = $hosttemplate->toArray();
            if ($host['command_id'] === null || $host['command_id'] == $hosttemplate['command_id']) {
                $hostcommandargumentvalues = $host['hostcommandargumentvalues'];

                $commandArgumentValuesDiff = array_diff(
                    Hash::extract($hosttemplate['hosttemplatecommandargumentvalues'], '{n}.commandargument_id'),
                    Hash::extract($hostcommandargumentvalues, '{n}.commandargument_id')
                );
                if (!empty($commandArgumentValuesDiff)) {
                    //add missing (new) command argument values from host template to host command argument values
                    foreach ($commandArgumentValuesDiff as $commandArgumentValueId) {
                        $key = array_search(
                            $commandArgumentValueId,
                            array_column($hosttemplate['hosttemplatecommandargumentvalues'], 'commandargument_id'), true
                        );
                        $hostcommandargumentvalues[] = $hosttemplate['hosttemplatecommandargumentvalues'][$key];
                    }
                }
            }


            foreach ($hostcommandargumentvalues as $hostcommandargumentvalue) {
                $hostcommandargumentvaluesForCfg[] = [
                    'name'       => $hostcommandargumentvalue['commandargument']['name'],
                    'human_name' => $hostcommandargumentvalue['commandargument']['human_name'],
                    'value'      => $hostcommandargumentvalue['value']
                ];
            }
        }

        return Hash::sort($hostcommandargumentvaluesForCfg, '{n}.name', 'asc', 'natural');
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
     * @param Hosttemplate $hosttemplate
     * @return string
     */
    public function getNotificationOptionsForCfg(Hosttemplate $hosttemplate) {
        $cfgValues = [];
        $fields = [
            'notify_on_recovery'    => 'r',
            'notify_on_down'        => 'd',
            'notify_on_unreachable' => 'u',
            'notify_on_flapping'    => 'f',
            'notify_on_downtime'    => 's'
        ];


        //Does the host have own notification options?
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === null) {
                //Use the value of the host template
                if ($hosttemplate->get($field) === 1) {
                    $cfgValues[] = $cfgValue;
                }
            }

            if ($this->get($field) === 1) {
                //Host has defined its own value
                $cfgValues[] = $cfgValue;
            }
        }

        $notificationOptions = implode(',', $cfgValues);

        if ($notificationOptions === $hosttemplate->getHostNotificationOptionsForCfg()) {
            //Host has the same notification options like the host template - go for inheritance
            return '';
        }

        return $notificationOptions;
    }

    /**
     * @param Hosttemplate $hosttemplate
     * @return string
     */
    public function getFlapdetectionOptionsForCfg(Hosttemplate $hosttemplate) {
        $cfgValues = [];
        $fields = [
            'flap_detection_on_up'          => 'o',
            'flap_detection_on_down'        => 'd',
            'flap_detection_on_unreachable' => 'u'
        ];


        //Does the host have own flap detection options?
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === null) {
                //Use the value of the host template
                if ($hosttemplate->get($field) === 1) {
                    $cfgValues[] = $cfgValue;
                }
            }

            if ($this->get($field) === 1) {
                //Host has defined its own value
                $cfgValues[] = $cfgValue;
            }
        }

        $flapdetectionOptions = implode(',', $cfgValues);

        if ($flapdetectionOptions === $hosttemplate->getHostFlapDetectionOptionsForCfg()) {
            //Host has the same flap detection options like the host template - go for inheritance
            return '';
        }

        return $flapdetectionOptions;
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
    public function getHostgroupsForCfg() {
        $hostgroups = [];
        foreach ($this->hostgroups as $hostgroup) {
            /** @var Hostgroup $hostgroup */
            $hostgroups[] = $hostgroup->get('uuid');
        }
        return implode(',', $hostgroups);
    }

    /**
     * @return bool
     */
    public function isSatelliteHost() {
        return $this->get('satellite_id') > 0;
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
                            'state'   => 'AutoreportsHostUsedBy',
                            'message' => __('Used by Autoreport module'),
                            'module'  => 'AutoreportModule',
                            'id'      => $this->id
                        ];
                        break;
                    case EVENTCORRELATION_MODULE:
                        $usedBy[$moduleName] = [
                            'baseUrl' => '#',
                            'state'   => 'EventcorrelationsHostUsedBy',
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

        // Use the value of the hosttemplate
        return $this->hosttemplate->{$field};
    }
}
