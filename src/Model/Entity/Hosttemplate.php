<?php

namespace App\Model\Entity;

use Cake\ORM\Entity;
use SLAModule\Model\Entity\Sla;

/**
 * Hosttemplate Entity
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $description
 * @property int $hosttemplatetype_id
 * @property int $command_id
 * @property string $check_command_args
 * @property int $eventhandler_command_id
 * @property int $timeperiod_id
 * @property int $check_interval
 * @property int $retry_interval
 * @property int $max_check_attempts
 * @property float $first_notification_delay
 * @property float $notification_interval
 * @property int $notify_on_down
 * @property int $notify_on_unreachable
 * @property int $notify_on_recovery
 * @property int $notify_on_flapping
 * @property int $notify_on_downtime
 * @property int $flap_detection_enabled
 * @property int $flap_detection_on_up
 * @property int $flap_detection_on_down
 * @property int $flap_detection_on_unreachable
 * @property float $low_flap_threshold
 * @property float $high_flap_threshold
 * @property int $process_performance_data
 * @property int $freshness_checks_enabled
 * @property int|null $freshness_threshold
 * @property int $passive_checks_enabled
 * @property int $event_handler_enabled
 * @property int $active_checks_enabled
 * @property int $retain_status_information
 * @property int $retain_nonstatus_information
 * @property int $notifications_enabled
 * @property string $notes
 * @property int $priority
 * @property int $check_period_id
 * @property int $notify_period_id
 * @property string $tags
 * @property int $container_id
 * @property string|null $host_url
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Command $eventhandler_command
 * @property \App\Model\Entity\Container $container
 * @property \App\Model\Entity\Contact[] $contats
 * @property \App\Model\Entity\Contactgroup[] $contactgroups
 * @property \App\Model\Entity\Host[] $hosts
 * @property \App\Model\Entity\Hosttemplatecommandargumentvalue[] $hosttemplatecommandargumentvalues
 * @property \App\Model\Entity\Hostgroup[] $hostgroups
 * @property \App\Model\Entity\Customvariable[] $customvariables
 * @property \PrometheusModule\Model\Entity\PrometheusExporter[] $prometheus_exporters
 * @property Sla $sla
 */
class Hosttemplate extends Entity {

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
        'name'                              => true,
        'description'                       => true,
        'hosttemplatetype_id'               => true,
        'command_id'                        => true,
        'check_command_args'                => true,
        'eventhandler_command_id'           => true,
        'timeperiod_id'                     => true,
        'check_interval'                    => true,
        'retry_interval'                    => true,
        'max_check_attempts'                => true,
        'first_notification_delay'          => true,
        'notification_interval'             => true,
        'notify_on_down'                    => true,
        'notify_on_unreachable'             => true,
        'notify_on_recovery'                => true,
        'notify_on_flapping'                => true,
        'notify_on_downtime'                => true,
        'flap_detection_enabled'            => true,
        'flap_detection_on_up'              => true,
        'flap_detection_on_down'            => true,
        'flap_detection_on_unreachable'     => true,
        'low_flap_threshold'                => true,
        'high_flap_threshold'               => true,
        'process_performance_data'          => true,
        'freshness_checks_enabled'          => true,
        'freshness_threshold'               => true,
        'passive_checks_enabled'            => true,
        'event_handler_enabled'             => true,
        'active_checks_enabled'             => true,
        'retain_status_information'         => true,
        'retain_nonstatus_information'      => true,
        'notifications_enabled'             => true,
        'notes'                             => true,
        'priority'                          => true,
        'check_period_id'                   => true,
        'notify_period_id'                  => true,
        'tags'                              => true,
        'container_id'                      => true,
        'host_url'                          => true,
        'created'                           => true,
        'modified'                          => true,
        'hosttemplatetype'                  => true,
        'command'                           => true,
        'eventhandler_command'              => true,
        'timeperiod'                        => true,
        'check_period'                      => true,
        'notify_period'                     => true,
        'container'                         => true,
        'hosttemplatecommandargumentvalues' => true,
        'customvariables'                   => true,
        'contacts'                          => true,
        'contactgroups'                     => true,
        'hostgroups'                        => true,
        //'check_command'                     => true
        'prometheus_exporters'              => true,
        'sla_id'                            => true,
        'sla'                               => true
    ];

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
     * @return bool
     */
    public function hasHosttemplatecommandargumentvalues() {
        return !empty($this->hosttemplatecommandargumentvalues);
    }

    /**
     * @return string
     */
    public function getHosttemplatecommandargumentvaluesForCfg() {
        $arguments = [];
        $humanNames = [];
        foreach ($this->hosttemplatecommandargumentvalues as $hosttemplatecommandargumentvalue) {
            $nagName = $hosttemplatecommandargumentvalue->commandargument->name;
            $value = $hosttemplatecommandargumentvalue->value;
            $humanName = $hosttemplatecommandargumentvalue->commandargument->human_name;

            $arguments[$nagName] = $value;
            $humanNames[$nagName] = $humanName;

        }

        ksort($arguments, SORT_NATURAL);
        ksort($humanNames, SORT_NATURAL);

        if (empty($arguments)) {
            return '';
        }

        return sprintf(
            '%s; %s',
            implode('!', $arguments),
            implode('!', $humanNames)
        );
    }

    /**
     * @return bool
     */
    public function hasContacts() {
        return !empty($this->contacts);
    }

    /**
     * @return string
     */
    public function getContactsForCfg() {
        $contacts = [];
        foreach ($this->contacts as $contact) {
            /** @var Contact $contact */
            $contacts[] = $contact->get('uuid');
        }

        return implode(',', $contacts);
    }

    /**
     * @return bool
     */
    public function hasContactgroups() {
        return !empty($this->contactgroups);
    }

    /**
     * @return string
     */
    public function getContactgroupsForCfg() {
        $contactgroups = [];
        foreach ($this->contactgroups as $contactgroup) {
            /** @var Contactgroup $contactgroup */
            $contactgroups[] = $contactgroup->get('uuid');
        }

        return implode(',', $contactgroups);
    }

    /**
     * @return string
     */
    public function getHostNotificationOptionsForCfg() {
        $cfgValues = [];
        $fields = [
            'notify_on_recovery'    => 'r',
            'notify_on_down'        => 'd',
            'notify_on_unreachable' => 'u',
            'notify_on_flapping'    => 'f',
            'notify_on_downtime'    => 's'
        ];
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        if (empty($cfgValues)) {
            //Config error!
            $cfgValues = ['r'];
        }
        return implode(',', $cfgValues);
    }

    /**
     * @return null|string
     */
    public function getHostFlapDetectionOptionsForCfg() {
        $cfgValues = [];

        $fields = [
            'flap_detection_on_up'          => 'o',
            'flap_detection_on_down'        => 'd',
            'flap_detection_on_unreachable' => 'u',
        ];
        foreach ($fields as $field => $cfgValue) {
            if ($this->get($field) === 1) {
                $cfgValues[] = $cfgValue;
            }
        }

        if (empty($cfgValues)) {
            return null;
        }
        return implode(',', $cfgValues);
    }
}
