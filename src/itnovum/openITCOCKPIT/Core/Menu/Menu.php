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

namespace itnovum\openITCOCKPIT\Core\Menu;


use App\Lib\PluginManager;

class Menu {

    const MENU_OVERVIEW = 'overview';
    const MENU_MONITORING = 'monitoring';
    const MENU_ADMINISTRATION = 'administration';
    const MENU_CONFIGURATION = 'configuration';

    /**
     * @var MenuHeadline[]
     */
    private $headlines = [];

    /**
     * @var array
     */
    private $PERMISSIONS = [];

    /**
     * Menu constructor.
     * @param array $PERMISSIONS
     */
    public function __construct($PERMISSIONS = []) {
        $this->PERMISSIONS = $PERMISSIONS;

        $Overview = new MenuHeadline(self::MENU_OVERVIEW, __('Overview'), 1);
        $Overview
            ->addLink(new MenuLink(
                __('Dashboards'),
                'DashboardsIndex',
                'dashboards',
                'index',
                '',
                ['fas', 'gauge-high'],
                ['dashboard'],
                1,
                true,
                '/dashboards/index'
            ))
            ->addLink(new MenuLink(
                __('Browser'),
                'BrowsersIndex',
                'browsers',
                'index',
                '',
                ['fas', 'list'],
                ['browser'],
                3,
                true,
                '/browsers/index'
            ))
            ->addCategory(
                (new MenuCategory(
                    'maps_category',
                    __('Maps'),
                    3,
                    ['fas', 'location-dot']
                ))
                    ->addLink(new MenuLink(
                        __('Status Map'),
                        'StatusmapsIndex',
                        'statusmaps',
                        'index',
                        '',
                        ['fas', 'globe-europe'],
                        ['statusmap', 'status', 'map'],
                        1,
                        true,
                        '/statusmaps/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Auto Map'),
                        'AutomapsIndex',
                        'automaps',
                        'index',
                        '',
                        ['fas', 'wand-magic-sparkles'],
                        ['automaps', 'auto', 'map'],
                        2,
                        true,
                        '/automaps/index'
                    ))
            )
            ->addCategory(
                (new MenuCategory(
                    'reports_category',
                    __('Reports'),
                    4,
                    ['fas', 'file-invoice']
                ))
                    ->addLink(new MenuLink(
                        __('Instant reports'),
                        'InstantreportsIndex',
                        'instantreports',
                        'index',
                        '',
                        ['fas', 'file-invoice'],
                        ['instantreports'],
                        1,
                        true,
                        '/instantreports/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Downtime reports'),
                        'DowntimereportsIndex',
                        'downtimereports',
                        'index',
                        '',
                        ['fas', 'file-invoice'],
                        [],
                        2,
                        true,
                        '/downtimereports/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Current State reports'),
                        'CurrentstatereportsIndex',
                        'currentstatereports',
                        'index',
                        '',
                        ['fas', 'file-invoice'],
                        [],
                        3,
                        true,
                        '/currentstatereports/index'
                    ))

            )
            ->addCategory(
                (new MenuCategory(
                    'status_pages',
                    __('Status Pages Management'),
                    4,
                    ['fas', 'globe']

                ))
                    ->addLink(new MenuLink(
                        __('Status pages'),
                        'StatuspagesIndex',
                        'statuspages',
                        'index',
                        '',
                        ['fas', 'info-circle'],
                        [],
                        3,
                        true,
                        '/statuspages/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Status page groups'),
                        'StatuspagegroupsIndex',
                        'statuspagegroups',
                        'index',
                        '',
                        ['fas', 'layer-group'],
                        [],
                        3,
                        true,
                        '/statuspagegroups/index'
                    )))
            ->addCategory(
                (new MenuCategory(
                    'logs_category',
                    __('Logs'),
                    5,
                    ['fas', 'file-lines']
                ))
                    ->addLink(new MenuLink(
                        __('Notifications'),
                        'NotificationsIndex',
                        'notifications',
                        'index',
                        '',
                        ['fas', 'envelope'],
                        [],
                        1,
                        true,
                        '/notifications/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Log entries'),
                        'LogentriesIndex',
                        'logentries',
                        'index',
                        '',
                        ['fas', 'file-lines'],
                        [],
                        2,
                        true,
                        '/logentries/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Change Log'),
                        'ChangelogsIndex',
                        'changelogs',
                        'index',
                        '',
                        ['fas', 'code-fork'],
                        [],
                        3,
                        true,
                        '/changelogs/index'
                    ))
                    ->addLink(new MenuLink(
                        __('Event Log'),
                        'EventlogsIndex',
                        'eventlogs',
                        'index',
                        '',
                        ['fas', 'file-text'],
                        [],
                        4,
                        true,
                        '/eventlogs/index'
                    ))
            );

        $Monitoring = new MenuHeadline(self::MENU_MONITORING, __('Monitoring'), 2);
        $Monitoring
            ->addLink(new MenuLink(
                __('Hosts'),
                'HostsIndex',
                'hosts',
                'index',
                '',
                ['fas', 'desktop'],
                ['hosts'],
                1,
                true,
                '/hosts/index'
            ))
            ->addLink(new MenuLink(
                __('Services'),
                'ServicesIndex',
                'services',
                'index',
                '',
                ['fas', 'gears'],
                ['hosts'],
                2,
                true,
                '/services/index'
            ))
            ->addLink(new MenuLink(
                __('Wizard'),
                'WizardsIndex',
                'wizards',
                'index',
                '',
                ['fas', 'wand-magic-sparkles'],
                [],
                3,
                true,
                '/wizards/index'
            ))
            ->addCategory((new MenuCategory(
                'objects_category',
                __('Objects'),
                3,
                ['fas', 'cubes']
            ))
                ->addLink(new MenuLink(
                    __('Contacts'),
                    'ContactsIndex',
                    'contacts',
                    'index',
                    '',
                    ['fas', 'user'],
                    [],
                    1,
                    true,
                    '/contacts/index'
                ))
                ->addLink(new MenuLink(
                    __('Commands'),
                    'CommandsIndex',
                    'commands',
                    'index',
                    '',
                    ['fas', 'terminal'],
                    [],
                    2,
                    true,
                    '/commands/index'
                ))
                ->addLink(new MenuLink(
                    __('User Defined Macros'),
                    'MacrosIndex',
                    'macros',
                    'index',
                    '',
                    ['fas', 'dollar-sign'],
                    [],
                    3,
                    true,
                    '/macros/index'
                ))
                ->addLink(new MenuLink(
                    __('Time Periods'),
                    'TimeperiodsIndex',
                    'timeperiods',
                    'index',
                    '',
                    ['fas', 'clock'],
                    [],
                    4,
                    true,
                    '/timeperiods/index'
                ))
                ->addLink(new MenuLink(
                    __('Calendar'),
                    'CalendarsIndex',
                    'calendars',
                    'index',
                    '',
                    ['fas', 'calendar'],
                    [],
                    5,
                    true,
                    '/calendars/index'
                ))
                ->addLink(new MenuLink(
                    __('Host Escalations'),
                    'HostescalationsIndex',
                    'hostescalations',
                    'index',
                    '',
                    ['fas', 'bomb'],
                    [],
                    6,
                    true,
                    '/hostescalations/index'
                ))
                ->addLink(new MenuLink(
                    __('Service Escalations'),
                    'ServiceescalationsIndex',
                    'serviceescalations',
                    'index',
                    '',
                    ['fas', 'bomb'],
                    [],
                    7,
                    true,
                    '/serviceescalations/index'
                ))
                ->addLink(new MenuLink(
                    __('Host Dependencies'),
                    'HostdependenciesIndex',
                    'hostdependencies',
                    'index',
                    '',
                    ['fas', 'sitemap'],
                    [],
                    8,
                    true,
                    '/hostdependencies/index'
                ))
                ->addLink(new MenuLink(
                    __('Service Dependencies'),
                    'ServicedependenciesIndex',
                    'servicedependencies',
                    'index',
                    '',
                    ['fas', 'sitemap'],
                    [],
                    9,
                    true,
                    '/servicedependencies/index'
                ))
            )
            ->addCategory((new MenuCategory(
                'groups_category',
                __('Groups'),
                4,
                ['fas', 'object-group']
            ))
                ->addLink(new MenuLink(
                    __('Host Groups'),
                    'HostgroupsIndex',
                    'hostgroups',
                    'index',
                    '',
                    ['fas', 'server'],
                    [],
                    1,
                    true,
                    '/hostgroups/index'
                ))
                ->addLink(new MenuLink(
                    __('Service Groups'),
                    'ServicegroupsIndex',
                    'servicegroups',
                    'index',
                    '',
                    ['fas', 'gears'],
                    [],
                    2,
                    true,
                    '/servicegroups/index'
                ))
                ->addLink(new MenuLink(
                    __('Contact Groups'),
                    'ContactgroupsIndex',
                    'contactgroups',
                    'index',
                    '',
                    ['fas', 'users'],
                    [],
                    3,
                    true,
                    '/contactgroups/index'
                ))
                ->addLink(new MenuLink(
                    __('Service Template Grps.'),
                    'ServicetemplategroupsIndex',
                    'servicetemplategroups',
                    'index',
                    '',
                    ['fas', 'pen-to-square'],
                    [],
                    4,
                    true,
                    '/servicetemplategroups/index'
                ))
            )
            ->addCategory((new MenuCategory(
                'downtimes_category',
                __('Downtimes'),
                5,
                ['fas', 'power-off']
            ))
                ->addLink(new MenuLink(
                    __('Host Downtimes'),
                    'DowntimesHost',
                    'downtimes',
                    'host',
                    '',
                    ['fas', 'power-off'],
                    [],
                    1,
                    true,
                    '/downtimes/host'
                ))
                ->addLink(new MenuLink(
                    __('Service Downtimes'),
                    'DowntimesService',
                    'downtimes',
                    'service',
                    '',
                    ['fas', 'power-off'],
                    [],
                    2,
                    true,
                    '/downtimes/service'
                ))
                ->addLink(new MenuLink(
                    __('Recurring Host Downtimes'),
                    'SystemdowntimesHost',
                    'systemdowntimes',
                    'host',
                    '',
                    ['fas', 'repeat'], // todo fa-flip-horizontal
                    [],
                    3,
                    true,
                    '/systemdowntimes/host'
                ))
                ->addLink(new MenuLink(
                    __('Recurring Service Downtimes'),
                    'SystemdowntimesService',
                    'systemdowntimes',
                    'service',
                    '',
                    ['fas', 'repeat'], // todo fa-flip-horizontal
                    [],
                    4,
                    true,
                    '/systemdowntimes/service'
                ))
                ->addLink(new MenuLink(
                    __('System Failures'),
                    'SystemfailuresIndex',
                    'systemfailures',
                    'index',
                    '',
                    ['fas', 'exclamation-circle'],
                    [],
                    5,
                    true,
                    '/systemfailures/index'
                ))
            )
            ->addCategory((new MenuCategory(
                'templates_category',
                __('Templates'),
                6,

                ['fas', 'pen-to-square']

            ))
                ->addLink(new MenuLink(
                    __('Host Templates'),
                    'HosttemplatesIndex',
                    'hosttemplates',
                    'index',
                    '',
                    ['fas', 'pen-to-square'],
                    [],
                    1,
                    true,
                    '/hosttemplates/index'
                ))
                ->addLink(new MenuLink(
                    __('Service Templates'),
                    'ServicetemplatesIndex',
                    'servicetemplates',
                    'index',
                    '',
                    ['fas', 'pen-to-square'],
                    [],
                    2,
                    true,
                    '/servicetemplates/index'
                ))
            );

        $Administration = new MenuHeadline(self::MENU_ADMINISTRATION, __('Administration'), 3);
        $Administration
            ->addCategory((new MenuCategory(
                'user_mgmt_category',
                __('User management'),
                1,
                ['fas', 'users']
            ))
                ->addLink(new MenuLink(
                    __('Manage Users'),
                    'UsersIndex',
                    'users',
                    'index',
                    '',
                    ['fas', 'user'],
                    [],
                    1,
                    true,
                    '/users/index'
                ))
                ->addLink(new MenuLink(
                    __('Manage User Roles'),
                    'UsergroupsIndex',
                    'usergroups',
                    'index',
                    '',
                    ['fas', 'users'],
                    [],
                    2,
                    true,
                    '/usergroups/index'
                ))
                ->addLink(new MenuLink(
                    __('User Container Roles'),
                    'UsercontainerrolesIndex',
                    'usercontainerroles',
                    'index',
                    '',
                    ['fas', 'users'],
                    [],
                    3,
                    true,
                    '/usercontainerroles/index'
                ))
                ->addLink(new MenuLink(
                    __('Message of the day'),
                    'MessagesOTDIndex',
                    'messagesOtd',
                    'index',
                    '',
                    ['fas', 'bullhorn'],
                    [__('message'), __('news'), __('information')],
                    4,
                    true,
                    '/messagesOtd/index'
                ))
                ->addLink(new MenuLink(
                    __('Dashboard Allocation'),
                    'DashboardAllocationsIndex',
                    'DashboardAllocations',
                    'index',
                    '',
                    ['fas', 'table'],
                    [],
                    5,
                    true,
                    '/DashboardAllocations/index'
                ))
            )
            ->addCategory((new MenuCategory(
                'container_mgmt_category',
                __('Container management'),
                2,
                ['fas', 'link']
            ))
                ->addLink(new MenuLink(
                    __('Tenants'),
                    'TenantsIndex',
                    'tenants',
                    'index',
                    '',
                    ['fas', 'home'],
                    [],
                    1,
                    true,
                    '/tenants/index'
                ))
                ->addLink(new MenuLink(
                    __('Containers'),
                    'ContainersIndex',
                    'containers',
                    'index',
                    '',
                    ['fas', 'link'],
                    [],
                    2,
                    true,
                    '/containers/index'
                ))
                ->addLink(new MenuLink(
                    __('Locations'),
                    'LocationsIndex',
                    'locations',
                    'index',
                    '',
                    ['fas', 'location-arrow'],
                    [],
                    3,
                    true,
                    '/locations/index'
                ))
                ->addLink(new MenuLink(
                    __('Organizational charts'),
                    'OrganizationalCharts',
                    'organizationalcharts',
                    'index',
                    '',
                    ['fas', 'sitemap'],
                    [],
                    5,
                    true,
                    '/organizationalcharts/index'
                ))
            )
            ->addCategory((new MenuCategory(
                'system_tools_category',
                __('System tools'),
                3,
                ['fas', 'link']
            ))
                ->addLink(new MenuLink(
                    __('Cron Jobs'),
                    'CronjobsIndex',
                    'cronjobs',
                    'index',
                    '',
                    ['far', 'clock'],
                    [],
                    1,
                    true,
                    '/cronjobs/index'
                ))
                ->addLink(new MenuLink(
                    __('Package Manager'),
                    'PackageManagerIndex',
                    'packetmanager',
                    'index',
                    '',
                    ['fas', 'cloud-arrow-down'],
                    [],
                    2,
                    true,
                    '/packetmanager/index'
                ))
                ->addLink(new MenuLink(
                    __('Debugging'),
                    'AdministratorsDebug',
                    'Administrators',
                    'debug',
                    '',
                    ['fas', 'bug'],
                    [],
                    3,
                    true,
                    '/administrators/debug'
                ))
                ->addLink(new MenuLink(
                    __('Performance Info'),
                    'NagiostatsIndex',
                    'nagiostats',
                    'index',
                    '',
                    ['fas', 'jet-fighter'],
                    [],
                    4,
                    true,
                    '/nagiostats/index'
                ))
                ->addLink(new MenuLink(
                    __('Backup / Restore'),
                    'BackupsIndex',
                    'backups',
                    'index',
                    '',
                    ['fas', 'database'],
                    [],
                    5,
                    true,
                    '/backups/index'
                ))
                ->addLink(new MenuLink(
                    __('Anonymous statistics'),
                    'StatisticsIndex',
                    'statistics',
                    'index',
                    '',
                    ['fas', 'chart-line'],
                    [],
                    7,
                    true,
                    '/statistics/index'
                ))
                ->addLink(new MenuLink(
                    __('System health notifications'),
                    'SystemHealthUsersIndex',
                    'systemHealthUsers',
                    'index',
                    '',
                    ['fas', 'user'],
                    [],
                    8,
                    true,
                    '/systemHealthUsers/index'
                ))
            )
            ->addCategory((new MenuCategory(
                'agent_category',
                __('openITCOCKPIT Agent'),
                4,
                ['fas', 'user-secret']
            ))
                ->addLink(new MenuLink(
                    __('Agent Wizard'),
                    'AgentconnectorsWizard',
                    'agentconnector',
                    'wizard',
                    '',
                    ['fas', 'wand-magic-sparkles'],
                    [],
                    1,
                    true,
                    '/agentconnector/wizard'
                ))
                ->addLink(new MenuLink(
                    __('Agents Overview'),
                    'AgentconnectorsPull',
                    'agentconnector',
                    'overview',
                    '',
                    ['fas', 'user-secret'],
                    [],
                    2,
                    true,
                    '/agentconnector/pull'
                ))
                ->addLink(new MenuLink(
                    __('Agent Checks'),
                    'AgentchecksIndex',
                    'agentchecks',
                    'index',
                    '',
                    ['fas', 'gears'],
                    [],
                    3,
                    true,
                    '/agentchecks/index'
                ))
            );

        $Configuration = new MenuHeadline(self::MENU_CONFIGURATION, __('System Configuration'), 4);
        $Configuration
            ->addCategory(new MenuCategory(
                'api_settings',
                __('APIs'),
                1,
                ['fas', 'code']
            ))
            ->addCategory((new MenuCategory(
                'settings_category',
                __('System'),
                3,
                ['fas', 'gears']
            ))
                ->addLink(new MenuLink(
                    __('System Settings'),
                    'SystemsettingsIndex',
                    'systemsettings',
                    'index',
                    '',
                    ['fas', 'wrench'],
                    [],
                    2,
                    true,
                    '/systemsettings/index'
                ))
                ->addLink(new MenuLink(
                    __('Config file editor'),
                    'ConfigurationFilesIndex',
                    'ConfigurationFiles',
                    'index',
                    '',
                    ['fas', 'file-waveform'],
                    [],
                    1,
                    true,
                    '/ConfigurationFiles/index'
                ))
                ->addLink(new MenuLink(
                    __('Proxy Settings'),
                    'ProxyIndex',
                    'proxy',
                    'index',
                    '',
                    ['fas', 'globe'],
                    [],
                    2,
                    true,
                    '/proxy/index'
                ))
                ->addLink(new MenuLink(
                    __('Registration'),
                    'RegistersIndex',
                    'registers',
                    'index',
                    '',
                    ['fas', 'square-check'],
                    [],
                    3,
                    true,
                    '/registers/index'
                ))
                ->addLink(new MenuLink(
                    __('Support'),
                    'SupportsIssue',
                    'supports',
                    'issue',
                    '',
                    ['fas', 'life-ring'],
                    [],
                    4,
                    true,
                    '/supports/issue'
                ))
                ->addLink(new MenuLink(
                    __('Documentation'),
                    'DocumentationsWiki',
                    'documentations',
                    'wiki',
                    '',
                    ['fas', 'book'],
                    [],
                    5,
                    true,
                    '/documentations/wiki'
                ))
                ->addLink(new MenuLink(
                    __('Wizard assignments'),
                    'WizardsAssignments',
                    'wizards',
                    'assignments',
                    '',
                    ['fas', 'wand-magic-sparkles'],
                    [],
                    6,
                    true,
                    '/wizards/assignments'
                ))
                ->addLink(new MenuLink(
                    __('Prometheus Metrics'),
                    'MetricsInfo',
                    'metrics',
                    'index',
                    '',
                    ['fas', 'heart-pulse'],
                    [],
                    7,
                    true,
                    '/metrics/info'
                ))
            );


        $this->addHeadline($Overview);
        $this->addHeadline($Monitoring);
        $this->addHeadline($Administration);
        $this->addHeadline($Configuration);
    }

    /**
     * @param MenuHeadline $MenuHeadline
     * @return $this
     */
    public function addHeadline(MenuHeadline $MenuHeadline) {
        $headlines = $this->headlines;
        $headlines[] = $MenuHeadline;

        $this->headlines = [];

        //Merge headlines with the same name together
        $names = [];
        $headlinesToRemove = [];
        foreach ($headlines as $index => $headline) {
            /** @var MenuHeadline $headline */
            if (isset($names[$headline->getName()])) {
                // Merge with existing headline
                /** @var MenuHeadline $targetHeadline */
                $targetHeadline = $names[$headline->getName()];
                $headlinesToRemove = [$index];
                foreach ($headline->getItems() as $item) {
                    if ($item instanceof MenuLink) {
                        $targetHeadline->addLink($item);
                    }
                    if ($item instanceof MenuCategory) {
                        $targetHeadline->addCategory($item);
                    }
                }
            } else {
                //Save name
                $names[$headline->getName()] = $headline;
            }
        }

        //Drop merged headlines to remove duplicates
        foreach ($headlinesToRemove as $index) {
            unset($headlines[$index]);
        }


        //Order headlines
        $indexesToOrder = [];
        foreach ($headlines as $index => $headline) {
            /** @var MenuHeadline $headline */
            $indexesToOrder[$index] = $headline->getOrder();
        }

        asort($indexesToOrder);

        foreach ($indexesToOrder as $index => $orderNumber) {
            $this->headlines[] = $headlines[$index];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getAllMenuCategories() {
        $menuCategories = [];
        foreach ($this->headlines as $headline) {
            /** @var MenuHeadline $headline */
            if (!isset($menuCategories[$headline->getName()])) {
                $menuCategories[$headline->getName()] = [];
            }

            foreach ($headline->getItems() as $item) {
                if ($item instanceof MenuCategory) {
                    $menuCategories[$headline->getName()][] = $item->getName();
                }
            }
        }

        return $menuCategories;
    }

    /**
     * @return MenuHeadline[]
     */
    public function getMenuItems() {
        $modules = PluginManager::getAvailablePlugins();
        foreach ($modules as $module) {
            $className = sprintf('\\%s\\Lib\\Menu', $module);
            if (class_exists($className)) {

                /** @var MenuInterface $PluginMenu */
                $PluginMenu = new $className();

                foreach ($PluginMenu->getHeadlines() as $headline) {
                    /** @var MenuHeadline $headline */
                    $this->addHeadline($headline);
                }
            }
        }


        $this->filterMenuByPermissions();
        $this->remoteEmptyMenuItems();

        return $this->headlines;
    }

    private function filterMenuByPermissions() {
        foreach ($this->headlines as $headline) {
            foreach ($headline->getItems() as $index => $item) {
                if ($item instanceof MenuCategory) {
                    /** @var MenuCategory $item */
                    foreach ($item->getLinks() as $subIndex => $MenuLink) {
                        if ($this->hasPermissions($MenuLink) === false) {
                            $item->removeMenuLinkByIndex($subIndex);
                        }
                    }

                } else {
                    /** @var MenuLink $item */
                    if ($this->hasPermissions($item) === false) {
                        $headline->removeMenuLinkByIndex($index);
                    }

                }
            }
        }
    }

    private function remoteEmptyMenuItems() {
        foreach ($this->headlines as $headlineIndex => $headline) {
            foreach ($headline->getItems() as $index => $item) {
                if ($item instanceof MenuCategory) {
                    /** @var MenuCategory $item */
                    if ($item->hasLinks() === false) {
                        $headline->removeMenuCategoryByIndex($index);
                    }
                }
            }

            if ($headline->hasItems() === false) {
                unset($this->headlines[$headlineIndex]);
            }
        }

    }

    /**
     * @param MenuLink $MenuLink
     * @return bool
     */
    private function hasPermissions(MenuLink $MenuLink) {
        $plugin = $MenuLink->getLowerPlugin();
        $controller = $MenuLink->getLowerController();
        $action = $MenuLink->getLowerAction();

        if ($MenuLink->isModule()) {
            return isset($this->PERMISSIONS[$plugin][$controller][$action]);
        }

        return isset($this->PERMISSIONS[$controller][$action]);
    }

}
