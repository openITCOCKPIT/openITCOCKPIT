<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Container Entity
 *
 * @property int $id
 * @property int $containertype_id
 * @property string $name
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rght
 *
 * @property \App\Model\Entity\Containertype $containertype
 * @property \App\Model\Entity\ParentContainer $parent_container
 * @property \App\Model\Entity\Automap[] $automaps
 * @property \App\Model\Entity\Autoreport[] $autoreports
 * @property \App\Model\Entity\Calendar[] $calendars
 * @property \App\Model\Entity\ChangelogsToContainer[] $changelogs_to_containers
 * @property \App\Model\Entity\Contactgroup[] $contactgroups
 * @property \App\Model\Entity\ContactsToContainer[] $contacts_to_containers
 * @property \App\Model\Entity\ChildContainer[] $child_containers
 * @property \App\Model\Entity\GrafanaUserdashboard[] $grafana_userdashboards
 * @property \App\Model\Entity\Hostdependency[] $hostdependencies
 * @property \App\Model\Entity\Hostescalation[] $hostescalations
 * @property \App\Model\Entity\Hostgroup[] $hostgroups
 * @property \App\Model\Entity\Host[] $hosts
 * @property \App\Model\Entity\HostsToContainer[] $hosts_to_containers
 * @property \App\Model\Entity\Hosttemplate[] $hosttemplates
 * @property \App\Model\Entity\IdoitObject[] $idoit_objects
 * @property \App\Model\Entity\IdoitObjecttype[] $idoit_objecttypes
 * @property \App\Model\Entity\Instantreport[] $instantreports
 * @property \App\Model\Entity\Location[] $locations
 * @property \App\Model\Entity\MapUpload[] $map_uploads
 * @property \App\Model\Entity\MapsToContainer[] $maps_to_containers
 * @property \App\Model\Entity\Mkagent[] $mkagents
 * @property \App\Model\Entity\NmapConfiguration[] $nmap_configurations
 * @property \App\Model\Entity\RotationsToContainer[] $rotations_to_containers
 * @property \App\Model\Entity\Satellite[] $satellites
 * @property \App\Model\Entity\Servicedependency[] $servicedependencies
 * @property \App\Model\Entity\Serviceescalation[] $serviceescalations
 * @property \App\Model\Entity\Servicegroup[] $servicegroups
 * @property \App\Model\Entity\Servicetemplategroup[] $servicetemplategroups
 * @property \App\Model\Entity\Servicetemplate[] $servicetemplates
 * @property \App\Model\Entity\Tenant[] $tenants
 * @property \App\Model\Entity\Timeperiod[] $timeperiods
 * @property \App\Model\Entity\UsersToContainer[] $users_to_containers
 */
class Container extends Entity
{

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
        'containertype_id' => true,
        'name' => true,
        'parent_id' => true,
        'lft' => true,
        'rght' => true,
        'containertype' => true,
        'parent_container' => true,
        'automaps' => true,
        'autoreports' => true,
        'calendars' => true,
        'changelogs_to_containers' => true,
        'contactgroups' => true,
        'contacts_to_containers' => true,
        'child_containers' => true,
        'grafana_userdashboards' => true,
        'hostdependencies' => true,
        'hostescalations' => true,
        'hostgroups' => true,
        'hosts' => true,
        'hosts_to_containers' => true,
        'hosttemplates' => true,
        'idoit_objects' => true,
        'idoit_objecttypes' => true,
        'instantreports' => true,
        'locations' => true,
        'map_uploads' => true,
        'maps_to_containers' => true,
        'mkagents' => true,
        'nmap_configurations' => true,
        'rotations_to_containers' => true,
        'satellites' => true,
        'servicedependencies' => true,
        'serviceescalations' => true,
        'servicegroups' => true,
        'servicetemplategroups' => true,
        'servicetemplates' => true,
        'tenants' => true,
        'timeperiods' => true,
        'users_to_containers' => true
    ];
}
