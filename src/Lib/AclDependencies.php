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

namespace App\Lib;

use Acl\Model\Table\AcosTable;
use Cake\Utility\Hash;

/**
 * Class AclDependencies
 * @package App
 */
class AclDependencies {

    /**
     * Hold a list of controllers and actions which
     * should always be allowed and could not be disabled by the user
     *
     * For Example:
     * PagesController::paginator()
     * PagesController::csrf()
     * Users::login()
     * Users::logout()
     *
     * @var array
     */
    private $allow = [];

    /**
     * Controller actions that depends on other controller actions.
     * This often happens when using a lot of Ajax in your frontend.
     *
     * For example:
     * Users::edit() make an Ajax call to Users::loadUsergroups() etc...
     *
     * @var array
     */
    private $dependencies = [];

    /**
     * Action names that should be ignored and not displayed by the frontend
     * @var array
     */
    private $ignore = [
        'initializePluginTables'
    ];

    /**
     * AclDependencies constructor.
     */
    public function __construct() {
        // Add actions that should always be allowed.
        $this
            ->allow('Angular', 'user_timezone')
            ->allow('Angular', 'version_check')
            ->allow('Angular', 'message_of_the_day')
            ->allow('Angular', 'menustats')
            ->allow('Angular', 'statuscount')
            ->allow('Angular', 'menu')
            ->allow('Angular', 'topSearch')
            ->allow('Angular', 'websocket_configuration')
            ->allow('Angular', 'push_configuration')
            ->allow('Angular', 'executing')
            ->allow('Angular', 'getDowntimeData')
            ->allow('Angular', 'system_health')
            ->allow('Angular', 'getPieChart')
            ->allow('Angular', 'getHalfPieChart')
            ->allow('Angular', 'getHostAndServiceStateSummaryIcon')
            ->allow('Angular', 'ldap_configuration')
            ->allow('Angular', 'queryhandler')
            ->allow('Angular', 'hostBrowserMenu')
            ->allow('Angular', 'serviceBrowserMenu')
            ->allow('Angular', 'getSatellites')
            ->allow('Angular', 'getSystemname')
            ->allow('Angular', 'getAppHeaderInfo');

        $this
            ->allow('Agentconnector', 'register_agent')
            ->allow('Agentconnector', 'submit_checkdata');

        $this
            ->allow('Automaps', 'loadContainers')
            ->allow('Automaps', 'automapWidget')
            ->allow('Automaps', 'loadAutomapsByString');

        $this
            ->allow('Calendars', 'loadCalendarsByContainerId');

        $this
            ->allow('Containers', 'byTenantForSelect')
            ->allow('Containers', 'byTenant')
            ->allow('Containers', 'loadContainersForAngular')
            ->allow('Containers', 'loadContainers')
            ->allow('Containers', 'loadContainersByContainerId')
            ->allow('Containers', 'loadSatellitesByContainerIds');

        $this
            ->allow('Downtimes', 'validateDowntimeInputFromAngular');

        $this
            ->allow('Packetmanager', 'getPackets')
            ->allow('Packetmanager', 'repositoryChecker');

        $this
            ->allow('Profile', 'edit')
            ->allow('Profile', 'changePassword')
            ->allow('Profile', 'upload_profile_icon')
            ->allow('Profile', 'deleteImage')
            ->allow('Profile', 'apikey')
            ->allow('Profile', 'edit_apikey')
            ->allow('Profile', 'delete_apikey')
            ->allow('Profile', 'create_apikey')
            ->allow('Profile', 'updateI18n');

        $this
            ->allow('Proxy', 'getSettings');

        $this
            ->allow('Services', 'byUuid')
            ->allow('Services', 'loadServicesByStringCake4')
            ->allow('Services', 'loadServicesByContainerIdCake4')
            ->allow('Services', 'loadServicesByStringForOptionGroup');

        $this
            ->allow('Graphgenerators', 'getPerfdataByUuid');

        $this
            ->allow('Dashboards', 'index')
            ->allow('Dashboards', 'getWidgetsForTab')
            ->allow('Dashboards', 'welcomeWidget')
            ->allow('Dashboards', 'saveGrid')
            ->allow('Dashboards', 'addWidgetToTab')
            ->allow('Dashboards', 'removeWidgetFromTab')
            ->allow('Dashboards', 'saveTabOrder')
            ->allow('Dashboards', 'addNewTab')
            ->allow('Dashboards', 'renameDashboardTab')
            ->allow('Dashboards', 'deleteDashboardTab')
            ->allow('Dashboards', 'startSharing')
            ->allow('Dashboards', 'stopSharing')
            ->allow('Dashboards', 'getSharedTabs')
            ->allow('Dashboards', 'createFromSharedTab')
            ->allow('Dashboards', 'checkForUpdates')
            ->allow('Dashboards', 'neverPerformUpdates')
            ->allow('Dashboards', 'updateSharedTab')
            ->allow('Dashboards', 'renameWidget')
            ->allow('Dashboards', 'lockOrUnlockTab')
            ->allow('Dashboards', 'restoreDefault')
            ->allow('Dashboards', 'hostsPiechartWidget')
            ->allow('Dashboards', 'hostsPiechart180Widget')
            ->allow('Dashboards', 'servicesPiechartWidget')
            ->allow('Dashboards', 'servicesPiechart180Widget')
            ->allow('Dashboards', 'hostsStatusListWidget')
            ->allow('Dashboards', 'hostsStatusListExtendedWidget')
            ->allow('Dashboards', 'hostsTopAlertsWidget')
            ->allow('Dashboards', 'servicesStatusListWidget')
            ->allow('Dashboards', 'servicesStatusListExtendedWidget')
            ->allow('Dashboards', 'servicesTopAlertsWidget')
            ->allow('Dashboards', 'saveTabRotateInterval')
            ->allow('Dashboards', 'parentOutagesWidget')
            ->allow('Dashboards', 'hostsDowntimeWidget')
            ->allow('Dashboards', 'servicesDowntimeWidget')
            ->allow('Dashboards', 'noticeWidget')
            ->allow('Dashboards', 'trafficLightWidget')
            ->allow('Dashboards', 'getServiceWithStateById')
            ->allow('Dashboards', 'hostStatusOverviewWidget')
            ->allow('Dashboards', 'hostStatusOverviewExtendedWidget')
            ->allow('Dashboards', 'tachoWidget')
            ->allow('Dashboards', 'serviceStatusOverviewWidget')
            ->allow('Dashboards', 'serviceStatusOverviewExtendedWidget')
            ->allow('Dashboards', 'websiteWidget')
            ->allow('Dashboards', 'todayWidget')
            ->allow('Dashboards', 'getPerformanceDataMetrics')
            ->allow('Dashboards', 'tacticalOverviewWidget')
            ->allow('Dashboards', 'tacticalOverviewHostsWidget')
            ->allow('Dashboards', 'tacticalOverviewServicesWidget')
            ->allow('Dashboards', 'calendarWidget')
            ->allow('Dashboards', 'desktopWidget');

        $this
            ->allow('FilterBookmarks', 'index')
            ->allow('FilterBookmarks', 'add')
            ->allow('FilterBookmarks', 'edit')
            ->allow('FilterBookmarks', 'delete');

        $this
            ->allow('Hosts', 'view')
            ->allow('Hosts', 'loadParentHostsByString')
            ->allow('Hosts', 'hoststatus')
            ->allow('Hosts', 'byUuid');


        $this
            ->allow('Pages', 'index');

        $this
            ->allow('Users', 'login')
            ->allow('Users', 'logout')
            ->allow('Users', 'getLocaleOptions')
            ->allow('Users', 'getUserPermissions')
            ->allow('Users', 'loadDateformats');


        $this
            ->allow('OrganizationalCharts', 'organizationalchartWidget');


        ///////////////////////////////
        //    Add dependencies       //
        //////////////////////////////
        $this
            ->dependency('Agentchecks', 'add', 'Agentchecks', 'loadServicetemplates')
            ->dependency('Agentchecks', 'edit', 'Agentchecks', 'loadServicetemplates');


        $this
            ->dependency('Agentconnector', 'wizard', 'Agentconnector', 'loadHostsByString')
            ->dependency('Agentconnector', 'wizard', 'Agentconnector', 'install')
            ->dependency('Agentconnector', 'wizard', 'Agentconnector', 'autotls')
            ->dependency('Agentconnector', 'wizard', 'Agentconnector', 'satellite_response')
            ->dependency('Agentconnector', 'wizard', 'Agentconnector', 'select_agent')
            ->dependency('Agentconnector', 'overview', 'Agentconnector', 'pull')
            ->dependency('Agentconnector', 'overview', 'Agentconnector', 'push')
            ->dependency('Agentconnector', 'overview', 'Agentconnector', 'push_satellite')
            ->dependency('Agentconnector', 'delete', 'Agentconnector', 'delete_push_agent')
            ->dependency('Agentconnector', 'delete', 'Agentconnector', 'delete_satellite_push_agent');

        $this
            ->dependency('Automaps', 'add', 'Automaps', 'getMatchingHostAndServices')
            ->dependency('Automaps', 'edit', 'Automaps', 'getMatchingHostAndServices')
            ->dependency('Automaps', 'view', 'Automaps', 'automap');

        $this
            ->dependency('Browsers', 'index', 'Browsers', 'tenantBrowser')
            ->dependency('Browsers', 'index', 'Browsers', 'locationBrowser')
            ->dependency('Browsers', 'index', 'Browsers', 'nodeBrowser');


        $this
            ->dependency('Calendars', 'add', 'Calendars', 'loadHolidays')
            ->dependency('Calendars', 'add', 'Calendars', 'loadCountryList')
            ->dependency('Calendars', 'edit', 'Calendars', 'loadHolidays')
            ->dependency('Calendars', 'edit', 'Calendars', 'loadCountryList');


        $this
            ->dependency('Commands', 'index', 'Commands', 'view');


        $this->dependency('Eventlogs', 'index', 'Eventlogs', 'listToPdf')
            ->dependency('Eventlogs', 'index', 'Eventlogs', 'listToCsv');

        $this
            ->dependency('Timeperiods', 'index', 'Timeperiods', 'view')
            ->dependency('Timeperiods', 'index', 'Timeperiods', 'loadTimeperiodsByContainerId');


        $this
            ->dependency('Contactgroups', 'index', 'Contactgroups', 'view')
            ->dependency('Contactgroups', 'add', 'Contactgroups', 'loadContacts')
            ->dependency('Contactgroups', 'add', 'Contactgroups', 'loadContainers')
            ->dependency('Contactgroups', 'edit', 'Contactgroups', 'loadContacts')
            ->dependency('Contactgroups', 'edit', 'Contactgroups', 'loadContainers');


        $this
            ->dependency('Contacts', 'index', 'Contacts', 'view')
            ->dependency('Contacts', 'add', 'Contacts', 'loadContainers')
            ->dependency('Contacts', 'add', 'Contacts', 'loadCommands')
            ->dependency('Contacts', 'add', 'Contacts', 'loadTimeperiods')
            ->dependency('Contacts', 'add', 'Contacts', 'loadLdapUserByString')
            ->dependency('Contacts', 'add', 'Contacts', 'loadUsersByContainerId')
            ->dependency('Contacts', 'edit', 'Contacts', 'loadContainers')
            ->dependency('Contacts', 'edit', 'Contacts', 'loadCommands')
            ->dependency('Contacts', 'edit', 'Contacts', 'loadTimeperiods')
            ->dependency('Contacts', 'edit', 'Contacts', 'loadLdapUserByString')
            ->dependency('Contacts', 'edit', 'Contacts', 'loadUsersByContainerId');


        $this
            ->dependency('Cronjobs', 'add', 'Cronjobs', 'getTasks')
            ->dependency('Cronjobs', 'edit', 'Cronjobs', 'getTasks');


        $this
            ->dependency('Currentstatereports', 'index', 'Currentstatereports', 'createPdfReport')
            ->dependency('Currentstatereports', 'index', 'Currentstatereports', 'createHtmlReport');

        $this
            ->dependency('DashboardAllocations', 'add', 'DashboardAllocations', 'loadElementsByContainerId')
            ->dependency('DashboardAllocations', 'edit', 'DashboardAllocations', 'loadElementsByContainerId')
            ->dependency('DashboardAllocations', 'edit', 'Users', 'loadContainersForAngular')
            ->dependency('DashboardAllocations', 'edit', 'Users', 'loadContainersForAngular');

        $this
            ->dependency('Downtimereports', 'index', 'Downtimereports', 'createPdfReport');


        $this
            ->dependency('Hostdependencies', 'index', 'Hostdependencies', 'view')
            ->dependency('Hostdependencies', 'add', 'Hostdependencies', 'loadContainers')
            ->dependency('Hostdependencies', 'add', 'Hostdependencies', 'loadElementsByContainerId')
            ->dependency('Hostdependencies', 'edit', 'Hostdependencies', 'loadContainers')
            ->dependency('Hostdependencies', 'edit', 'Hostdependencies', 'loadElementsByContainerId');


        $this
            ->dependency('Hostescalations', 'index', 'Hostescalations', 'view')
            ->dependency('Hostescalations', 'add', 'Hostescalations', 'loadContainers')
            ->dependency('Hostescalations', 'add', 'Hostescalations', 'loadElementsByContainerId')
            ->dependency('Hostescalations', 'add', 'Hostescalations', 'loadExcludedHostgroupsByContainerIdAndHostIds')
            ->dependency('Hostescalations', 'add', 'Hostescalations', 'loadExcludedHostsByContainerIdAndHostgroupIds')
            ->dependency('Hostescalations', 'edit', 'Hostescalations', 'loadContainers')
            ->dependency('Hostescalations', 'edit', 'Hostescalations', 'loadElementsByContainerId')
            ->dependency('Hostescalations', 'edit', 'Hostescalations', 'loadExcludedHostgroupsByContainerIdAndHostIds')
            ->dependency('Hostescalations', 'edit', 'Hostescalations', 'loadExcludedHostsByContainerIdAndHostgroupIds');


        $this
            ->dependency('Hostgroups', 'index', 'Hostgroups', 'listToPdf')
            ->dependency('Hostgroups', 'index', 'Hostgroups', 'listToCsv')
            ->dependency('Hostgroups', 'index', 'Hostgroups', 'view')
            ->dependency('Hostgroups', 'index', 'Hostgroups', 'loadHostgroupsByString')
            ->dependency('Hostgroups', 'index', 'Hostgroups', 'loadHostgroupsByStringAndContainers')
            ->dependency('Hostgroups', 'index', 'Hostgroups', 'loadHostgroupsByContainerId')
            ->dependency('Hostgroups', 'add', 'Hostgroups', 'loadHosts')
            ->dependency('Hostgroups', 'add', 'Hostgroups', 'loadHosttemplates')
            ->dependency('Hostgroups', 'add', 'Hostgroups', 'loadContainers')
            ->dependency('Hostgroups', 'add', 'Hostgroups', 'append')
            ->dependency('Hostgroups', 'edit', 'Hostgroups', 'loadHosts')
            ->dependency('Hostgroups', 'edit', 'Hostgroups', 'loadHosttemplates')
            ->dependency('Hostgroups', 'edit', 'Hostgroups', 'loadContainers')
            ->dependency('Hostgroups', 'edit', 'Hostgroups', 'append')
            ->dependency('Hostgroups', 'extended', 'Hostgroups', 'loadHostgroupWithHostsById')
            ->dependency('Hostgroups', 'extended', 'Hostgroups', 'listToPdf')
            ->dependency('Hostgroups', 'extended', 'Hostgroups', 'loadAdditionalInformation');


        $this
            ->dependency('Hosts', 'index', 'Hosts', 'listToPdf')
            ->dependency('Hosts', 'index', 'Hosts', 'listToCsv')
            ->dependency('Hosts', 'index', 'Hosts', 'loadHostsByContainerId')
            ->dependency('Hosts', 'index', 'Hosts', 'loadHostsByString')
            ->dependency('Hosts', 'index', 'Hosts', 'loadHostById')
            ->dependency('Hosts', 'delete', 'Hosts', 'mass_delete')
            ->dependency('Hosts', 'deactivate', 'Hosts', 'mass_deactivate')
            ->dependency('Hosts', 'browser', 'Hosts', 'getGrafanaIframeUrlForDatepicker')
            ->dependency('Hosts', 'browser', 'Hosts', 'loadAdditionalInformation')
            ->dependency('Hosts', 'browser', 'Hosts', 'loadSlaInformation')
            ->dependency('Hosts', 'browser', 'Hosts', 'loadIsarFlowInformation')
            ->dependency('Hosts', 'add', 'Hosts', 'loadContainers')
            ->dependency('Hosts', 'add', 'Hosts', 'loadCommands')
            ->dependency('Hosts', 'add', 'Hosts', 'loadElementsByContainerId')
            ->dependency('Hosts', 'add', 'Hosts', 'loadHosttemplate')
            ->dependency('Hosts', 'add', 'Hosts', 'runDnsLookup')
            ->dependency('Hosts', 'add', 'Hosts', 'loadCommandArguments')
            ->dependency('Hosts', 'add', 'Hosts', 'checkForDuplicateHostname')
            ->dependency('Hosts', 'edit', 'Hosts', 'loadContainers')
            ->dependency('Hosts', 'edit', 'Hosts', 'loadCommands')
            ->dependency('Hosts', 'edit', 'Hosts', 'loadElementsByContainerId')
            ->dependency('Hosts', 'edit', 'Hosts', 'loadHosttemplate')
            ->dependency('Hosts', 'edit', 'Hosts', 'runDnsLookup')
            ->dependency('Hosts', 'edit', 'Hosts', 'loadCommandArguments')
            ->dependency('Hosts', 'edit', 'Hosts', 'checkForDuplicateHostname')
            ->dependency('Hosts', 'copy', 'Hosts', 'checkForDuplicateHostname');


        $this
            ->dependency('Hosttemplates', 'index', 'Hosttemplates', 'view')
            ->dependency('Hosttemplates', 'index', 'Hosttemplates', 'loadHosttemplates')
            ->dependency('Hosttemplates', 'add', 'Hosttemplates', 'loadElementsByContainerId')
            ->dependency('Hosttemplates', 'add', 'Hosttemplates', 'loadContainers')
            ->dependency('Hosttemplates', 'add', 'Hosttemplates', 'loadCommands')
            ->dependency('Hosttemplates', 'add', 'Hosttemplates', 'loadCommandArguments')
            ->dependency('Hosttemplates', 'edit', 'Hosttemplates', 'loadElementsByContainerId')
            ->dependency('Hosttemplates', 'edit', 'Hosttemplates', 'loadContainers')
            ->dependency('Hosttemplates', 'edit', 'Hosttemplates', 'loadCommands')
            ->dependency('Hosttemplates', 'edit', 'Hosttemplates', 'loadCommandArguments');


        $this
            ->dependency('Instantreports', 'index', 'Instantreports', 'createPdfReport')
            ->dependency('Instantreports', 'add', 'Instantreports', 'loadContainers')
            ->dependency('Instantreports', 'generate', 'Instantreports', 'loadInstantreports');


        $this
            ->dependency('Macros', 'index', 'Macros', 'add')
            ->dependency('Macros', 'index', 'Macros', 'edit')
            ->dependency('Macros', 'index', 'Macros', 'delete')
            ->dependency('Macros', 'index', 'Macros', 'getAvailableMacroNames');


        $this
            ->dependency('Registers', 'index', 'Registers', 'checkLicense');


        $this
            ->dependency('Servicedependencies', 'index', 'Servicedependencies', 'view')
            ->dependency('Servicedependencies', 'add', 'Servicedependencies', 'loadContainers')
            ->dependency('Servicedependencies', 'add', 'Servicedependencies', 'loadElementsByContainerId')
            ->dependency('Servicedependencies', 'edit', 'Servicedependencies', 'loadContainers')
            ->dependency('Servicedependencies', 'edit', 'Servicedependencies', 'loadElementsByContainerId');


        $this
            ->dependency('Serviceescalations', 'index', 'Serviceescalations', 'view')
            ->dependency('Serviceescalations', 'add', 'Serviceescalations', 'loadContainers')
            ->dependency('Serviceescalations', 'add', 'Serviceescalations', 'loadElementsByContainerId')
            ->dependency('Serviceescalations', 'add', 'Serviceescalations', 'loadExcludedServicegroupsByContainerIdAndServiceIds')
            ->dependency('Serviceescalations', 'add', 'Serviceescalations', 'loadExcludedServicesByContainerIdAndServicegroupIds')
            ->dependency('Serviceescalations', 'add', 'Serviceescalations', 'loadExcludedServicesByContainerIdAndServicegroupIdsForOptionGroup')
            ->dependency('Serviceescalations', 'edit', 'Serviceescalations', 'loadContainers')
            ->dependency('Serviceescalations', 'edit', 'Serviceescalations', 'loadElementsByContainerId')
            ->dependency('Serviceescalations', 'edit', 'Serviceescalations', 'loadExcludedServicegroupsByContainerIdAndServiceIds')
            ->dependency('Serviceescalations', 'edit', 'Serviceescalations', 'loadExcludedServicesByContainerIdAndServicegroupIds')
            ->dependency('Serviceescalations', 'edit', 'Serviceescalations', 'loadExcludedServicesByContainerIdAndServicegroupIdsForOptionGroup');


        $this
            ->dependency('Servicegroups', 'index', 'Servicegroups', 'listToPdf')
            ->dependency('Servicegroups', 'index', 'Servicegroups', 'listToCsv')
            ->dependency('Servicegroups', 'index', 'Servicegroups', 'view')
            ->dependency('Servicegroups', 'index', 'Servicegroups', 'loadServicegroupsByContainerId')
            ->dependency('Servicegroups', 'index', 'Servicegroups', 'loadServicegroupsByString')
            ->dependency('Servicegroups', 'index', 'Servicegroups', 'loadServicegroupsByStringAndContainers')
            ->dependency('Servicegroups', 'add', 'Servicegroups', 'loadServices')
            ->dependency('Servicegroups', 'add', 'Servicegroups', 'loadServicesByStringForOptionGroup')
            ->dependency('Servicegroups', 'add', 'Servicegroups', 'loadServicetemplates')
            ->dependency('Servicegroups', 'add', 'Servicegroups', 'loadContainers')
            ->dependency('Servicegroups', 'add', 'Servicegroups', 'append')
            ->dependency('Servicegroups', 'edit', 'Servicegroups', 'loadServices')
            ->dependency('Servicegroups', 'edit', 'Servicegroups', 'loadServicesByStringForOptionGroup')
            ->dependency('Servicegroups', 'edit', 'Servicegroups', 'loadServicetemplates')
            ->dependency('Servicegroups', 'edit', 'Servicegroups', 'loadContainers')
            ->dependency('Servicegroups', 'edit', 'Servicegroups', 'append')
            ->dependency('Servicegroups', 'delete', 'Servicegroups', 'mass_delete')
            ->dependency('Servicegroups', 'extended', 'Servicegroups', 'loadServicegroupWithServicesById');


        $this
            ->dependency('Services', 'deactivate', 'Services', 'mass_deactivate')
            ->dependency('Services', 'index', 'Services', 'listToPdf')
            ->dependency('Services', 'index', 'Services', 'listToCsv')
            ->dependency('Services', 'index', 'Services', 'view')
            ->dependency('Services', 'index', 'Services', 'loadServicesByContainerId')
            ->dependency('Services', 'index', 'Services', 'loadServicesByString')
            ->dependency('Services', 'add', 'Services', 'loadElementsByHostId')
            ->dependency('Services', 'add', 'Services', 'loadServicetemplate')
            ->dependency('Services', 'add', 'Services', 'loadCommands')
            ->dependency('Services', 'add', 'Services', 'loadCommandArguments')
            ->dependency('Services', 'add', 'Services', 'loadEventhandlerCommandArguments')
            ->dependency('Services', 'edit', 'Services', 'loadElementsByHostId')
            ->dependency('Services', 'edit', 'Services', 'loadServicetemplate')
            ->dependency('Services', 'edit', 'Services', 'loadCommands')
            ->dependency('Services', 'edit', 'Services', 'loadCommandArguments')
            ->dependency('Services', 'edit', 'Services', 'loadEventhandlerCommandArguments')
            ->dependency('Services', 'serviceList', 'Services', 'deleted')
            ->dependency('Services', 'browser', 'Services', 'loadCustomalerts')
            ->dependency('Services', 'browser', 'Services', 'loadSlaInformation');


        $this
            ->dependency('Servicetemplategroups', 'index', 'Servicetemplategroups', 'loadServicetemplatesByContainerId')
            ->dependency('Servicetemplategroups', 'index', 'Servicetemplategroups', 'view')
            ->dependency('Servicetemplategroups', 'index', 'Servicetemplategroups', 'allocateToMatchingHostgroup')
            ->dependency('Servicetemplategroups', 'index', 'Servicetemplategroups', 'loadServicetemplategroupsByString')
            ->dependency('Servicetemplategroups', 'index', 'Servicetemplategroups', 'loadHostgroupsByString')
            ->dependency('Servicetemplategroups', 'add', 'Servicetemplategroups', 'loadContainers')
            ->dependency('Servicetemplategroups', 'add', 'Servicetemplategroups', 'loadServicetemplatesByContainerId')
            ->dependency('Servicetemplategroups', 'add', 'Servicetemplategroups', 'append')
            ->dependency('Servicetemplategroups', 'add', 'Servicetemplategroups', 'loadServicetemplategroupsByString')
            ->dependency('Servicetemplategroups', 'edit', 'Servicetemplategroups', 'loadContainers')
            ->dependency('Servicetemplategroups', 'edit', 'Servicetemplategroups', 'loadServicetemplatesByContainerId')
            ->dependency('Servicetemplategroups', 'edit', 'Servicetemplategroups', 'append')
            ->dependency('Servicetemplategroups', 'edit', 'Servicetemplategroups', 'loadServicetemplategroupsByString');

        $this
            ->dependency('Servicetemplates', 'index', 'Servicetemplates', 'view')
            ->dependency('Servicetemplates', 'index', 'Servicetemplates', 'loadServicetemplatesByContainerId')
            ->dependency('Servicetemplates', 'add', 'Servicetemplates', 'loadContainers')
            ->dependency('Servicetemplates', 'add', 'Servicetemplates', 'loadCommands')
            ->dependency('Servicetemplates', 'add', 'Servicetemplates', 'loadCommandArguments')
            ->dependency('Servicetemplates', 'add', 'Servicetemplates', 'loadEventhandlerCommandArguments')
            ->dependency('Servicetemplates', 'add', 'Servicetemplates', 'loadElementsByContainerId')
            ->dependency('Servicetemplates', 'edit', 'Servicetemplates', 'loadContainers')
            ->dependency('Servicetemplates', 'edit', 'Servicetemplates', 'loadCommands')
            ->dependency('Servicetemplates', 'edit', 'Servicetemplates', 'loadCommandArguments')
            ->dependency('Servicetemplates', 'edit', 'Servicetemplates', 'loadEventhandlerCommandArguments')
            ->dependency('Servicetemplates', 'edit', 'Servicetemplates', 'loadElementsByContainerId');

        $this
            ->dependency('Statuspages', 'index', 'Statuspages', 'loadContainers')
            ->dependency('Statuspages', 'view', 'Statuspages', 'loadContainers')
            ->dependency('Statuspages', 'add', 'Statuspages', 'loadContainers')
            ->dependency('Statuspages', 'edit', 'Statuspages', 'loadContainers');

        $this
            ->dependency('Users', 'index', 'Users', 'view')
            ->dependency('Users', 'index', 'Users', 'loadUsersByContainerId')
            ->dependency('Users', 'index', 'Users', 'loadUsergroups')
            ->dependency('Users', 'index', 'Users', 'listToCsv')
            ->dependency('Users', 'add', 'Users', 'loadLdapUserByString')
            ->dependency('Users', 'add', 'Users', 'loadLdapUserDetails')
            ->dependency('Users', 'add', 'Users', 'loadUsergroups')
            ->dependency('Users', 'add', 'Users', 'loadContainerRoles')
            ->dependency('Users', 'add', 'Users', 'loadContainerPermissions')
            ->dependency('Users', 'add', 'Users', 'loadContainersForAngular')
            ->dependency('Users', 'edit', 'Users', 'resetPassword')
            ->dependency('Users', 'edit', 'Users', 'loadUsergroups')
            ->dependency('Users', 'edit', 'Users', 'loadContainerRoles')
            ->dependency('Users', 'edit', 'Users', 'loadLdapUserDetails')
            ->dependency('Users', 'edit', 'Users', 'loadContainerPermissions')
            ->dependency('Users', 'edit', 'Users', 'loadContainersForAngular');


        $this
            ->dependency('Tenants', 'index', 'Tenants', 'view')
            ->dependency('Tenants', 'delete', 'Tenants', 'mass_delete');


        $this
            ->dependency('Downtimes', 'delete', 'Downtimes', 'mass_delete');


        $this
            ->dependency('Administrators', 'debug', 'Administrators', 'testMail')
            ->dependency('Administrators', 'debug', 'Administrators', 'php_info');


        $this
            ->dependency('Exports', 'index', 'Exports', 'broadcast')
            ->dependency('Exports', 'index', 'Exports', 'launchExport')
            ->dependency('Exports', 'index', 'Exports', 'verifyConfig')
            ->dependency('Exports', 'index', 'Exports', 'saveInstanceConfigSyncSelection');


        $this
            ->dependency('Containers', 'index', 'Containers', 'view')
            ->dependency('Containers', 'index', 'Containers', 'nest');


        $this
            ->dependency('Locations', 'index', 'Locations', 'view')
            ->dependency('Locations', 'add', 'Locations', 'loadContainers')
            ->dependency('Locations', 'edit', 'Locations', 'loadContainers');


        $this
            ->dependency('Usergroups', 'index', 'Usergroups', 'view')
            ->dependency('Usergroups', 'add', 'Usergroups', 'loadLdapgroupsForAngular')
            ->dependency('Usergroups', 'edit', 'Usergroups', 'loadLdapgroupsForAngular');


        $this
            ->dependency('Usercontainerroles', 'add', 'Usercontainerroles', 'loadLdapgroupsForAngular')
            ->dependency('Usercontainerroles', 'edit', 'Usercontainerroles', 'loadLdapgroupsForAngular');

        $this
            ->dependency('Backups', 'index', 'Backups', 'checkBackupFinished');


        $this
            ->dependency('Notifications', 'index', 'Notifications', 'services')
            ->dependency('Notifications', 'index', 'Notifications', 'hostTopNotifications')
            ->dependency('Notifications', 'index', 'Notifications', 'serviceTopNotifications');


        $this
            ->dependency('Statusmaps', 'index', 'Statusmaps', 'hostAndServicesSummaryStatus');


        $this
            ->dependency('Statistics', 'index', 'Statistics', 'saveStatisticDecision');


        $this
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'NagiosCfg')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'ModGearmanModule')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'AfterExport')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'NagiosModuleConfig')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'phpNSTAMaster')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'DbBackend')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'PerfdataBackend')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'GraphingDocker')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'StatusengineCfg')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'Statusengine3Cfg')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'GraphiteWeb')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'restorDefault')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'NSTAMaster')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'Gearman')
            ->dependency('ConfigurationFiles', 'edit', 'ConfigurationFiles', 'PhpFpmOitc');

        $this
            ->dependency('Services', 'add', 'Wizards', 'mysqlserver')
            ->dependency('Services', 'add', 'Wizards', 'linuxserverssh')
            ->dependency('Services', 'add', 'Wizards', 'agent')
            ->dependency('Services', 'add', 'Wizards', 'wizardHostConfiguration')
            ->dependency('Services', 'add', 'Wizards', 'validateInputFromAngular')
            ->dependency('Services', 'add', 'Wizards', 'loadElementsByContainerId')
            ->dependency('Services', 'add', 'Wizards', 'loadHostsByString')
            ->dependency('Services', 'add', 'Wizards', 'loadServicetemplatesByWizardType');


        $this
            ->dependency('MessagesOtd', 'add', 'MessagesOtd', 'notifyUsersViaMail')
            ->dependency('MessagesOtd', 'edit', 'MessagesOtd', 'notifyUsersViaMail');

        $this
            ->dependency('Metrics', 'index', 'Metrics', 'info');

        $this
            ->dependency('SystemHealthUsers', 'add', 'SystemHealthUsers', 'loadUsers');

        $this
            ->dependency('OrganizationalCharts', 'add', 'OrganizationalCharts', 'loadContainers')
            ->dependency('OrganizationalCharts', 'edit', 'OrganizationalCharts', 'loadContainers')
            ->dependency('OrganizationalCharts', 'add', 'OrganizationalChartNodes', 'loadUsers')
            ->dependency('OrganizationalCharts', 'edit', 'OrganizationalChartNodes', 'loadUsers')
            ->dependency('OrganizationalCharts', 'view', 'OrganizationalCharts', 'loadOrganizationalChartsByContainerId')
            ->dependency('OrganizationalCharts', 'view', 'OrganizationalCharts', 'loadOrganizationalChartById')
            ->dependency('OrganizationalCharts', 'view', 'OrganizationalCharts', 'loadOrganizationalChartsByString');


        //Load Plugin ALC Dependencies
        foreach (PluginManager::getAvailablePlugins() as $pluginName) {
            $className = sprintf('\%s\Lib\AclDependencies', $pluginName);
            if (class_exists($className)) {
                /** @var PluginAclDependencies $PluginAclDependencies */
                $PluginAclDependencies = new $className();

                $this->allow[$pluginName] = $PluginAclDependencies->getAllow();
                $this->dependencies[$pluginName] = $PluginAclDependencies->getDependencies();
            }
        }
    }

    /**
     * @param string $controller
     * @param string $action
     * @return $this
     */
    public function allow(string $controller, string $action): self {
        if (!isset($this->allow[$controller])) {
            $this->allow[$controller] = [];
        }

        $this->allow[$controller][$action] = $action;
        return $this;
    }

    /**
     * @param string $controller
     * @param string $action
     * @param string $dependentController
     * @param $dependentAction
     * @return $this
     */
    public function dependency(string $controller, string $action, string $dependentController, $dependentAction): self {
        if (!isset($this->dependencies[$controller][$action])) {
            $this->dependencies[$controller][$action] = [];
        }

        if (!isset($this->dependencies[$controller][$action][$dependentController])) {
            $this->dependencies[$controller][$action][$dependentController] = [];
        }

        $this->dependencies[$controller][$action][$dependentController][] = $dependentAction;


        return $this;
    }

    /**
     * @param AcosTable $AcosTable
     * @param array $selectedAcos
     * @return array
     */
    public function getDependentAcos(AcosTable $AcosTable, array $selectedAcos): array {
        $threadedAcos = $AcosTable->find('threaded')
            ->disableHydration()
            ->all();

        $acos = [];
        foreach ($threadedAcos as $threadedAco) {
            foreach ($threadedAco['children'] as $controllerAcos) {
                if (substr($controllerAcos['alias'], -6) === 'Module') {
                    $pluginAcos = $controllerAcos;
                    foreach ($pluginAcos['children'] as $pluginControllerAcos) {
                        $acos[$pluginAcos['alias']][$pluginControllerAcos['alias']] = [
                            'id'      => $pluginControllerAcos['id'],
                            'actions' => []
                        ];
                        foreach ($pluginControllerAcos['children'] as $pluginActionAcos) {
                            $acos[$pluginAcos['alias']][$pluginControllerAcos['alias']]['actions'][$pluginActionAcos['alias']] = [
                                'id' => $pluginActionAcos['id'],
                            ];
                        }
                    }
                } else {
                    //Core
                    $acos[$controllerAcos['alias']] = [
                        'id'      => $controllerAcos['id'],
                        'actions' => []
                    ];
                    foreach ($controllerAcos['children'] as $actionAco) {
                        $acos[$controllerAcos['alias']]['actions'][$actionAco['alias']] = [
                            'id' => $actionAco['id'],
                        ];
                    }
                }
            }
        }

        //Add always allowed ACL actions to $selectedAcos
        foreach ($this->allow as $controller => $actions) {
            if (substr($controller, -6) === 'Module') {
                $pluginName = $controller;
                foreach ($actions as $pluginController => $pluginActions) {
                    foreach ($pluginActions as $pluginAction) {
                        if (isset($acos[$pluginName][$pluginController]['actions'][$pluginAction]['id'])) {
                            //debug(implode('/', [$pluginName, $pluginController, $pluginAction]));
                            $acoId = $acos[$pluginName][$pluginController]['actions'][$pluginAction]['id'];

                            $selectedAcos[$acoId] = 1;
                        }
                    }
                }
            } else {
                //Core
                foreach ($actions as $action) {
                    if (isset($acos[$controller]['actions'][$action]['id'])) {
                        $acoId = $acos[$controller]['actions'][$action]['id'];

                        $selectedAcos[$acoId] = 1;
                    }
                }
            }
        }

        //Build up dependency tree (dependent ACL actions)
        $dependencyTree = [];
        foreach ($this->dependencies as $controller => $actions) { //1 core
            if (substr($controller, -6) === 'Module') {
                $pluginName = $controller;
                $pluginControllers = $actions;
                foreach ($pluginControllers as $pluginController => $pluginControllerActions) { // like 1 for core
                    foreach ($pluginControllerActions as $pluginAction => $dependentPluginControllers) { //like 2 for core
                        foreach ($dependentPluginControllers as $dependentPluginController => $dependentPluginActions) { // like 3 for core
                            foreach ($dependentPluginActions as $dependentPluginAction) { // like 4 for core
                                //debug(sprintf(
                                //    '[%s] %s/%s depends on %s/%s',
                                //    $pluginName,
                                //    $pluginController,
                                //    $pluginAction,
                                //    $dependentPluginController,
                                //    $dependentPluginAction
                                //));

                                // Action exists in plugin ?
                                if (isset($acos[$pluginName][$pluginController]['actions'][$pluginAction]['id'])) {
                                    if (isset($acos[$pluginName][$dependentPluginController]['actions'][$dependentPluginAction]['id'])) {
                                        $acoId = $acos[$pluginName][$pluginController]['actions'][$pluginAction]['id'];
                                        $dependentAcoId = $acos[$pluginName][$dependentPluginController]['actions'][$dependentPluginAction]['id'];

                                        $dependencyTree[$acoId][] = $dependentAcoId;
                                    }
                                } else {
                                    // Action exists in core ?
                                    if (isset($acos[$pluginController]['actions'][$pluginAction]['id'])) {
                                        if (isset($acos[$pluginName][$dependentPluginController]['actions'][$dependentPluginAction]['id'])) {
                                            $acoId = $acos[$pluginController]['actions'][$pluginAction]['id'];
                                            $dependentAcoId = $acos[$pluginName][$dependentPluginController]['actions'][$dependentPluginAction]['id'];
                                            $dependencyTree[$acoId][] = $dependentAcoId;

                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                //Core
                foreach ($actions as $action => $dependentControllers) { //2 core
                    foreach ($dependentControllers as $dependentController => $dependentActions) { //3 core
                        foreach ($dependentActions as $dependentAction) { //4 core
                            //debug(sprintf(
                            //    '%s/%s depends on %s/%s',
                            //    $controller,
                            //    $action,
                            //    $dependentController,
                            //    $dependentAction
                            //));

                            if (isset($acos[$controller]['actions'][$action]['id'])) {
                                if (isset($acos[$dependentController]['actions'][$dependentAction]['id'])) {
                                    $acoId = $acos[$controller]['actions'][$action]['id'];
                                    $dependentAcoId = $acos[$dependentController]['actions'][$dependentAction]['id'];

                                    $dependencyTree[$acoId][] = $dependentAcoId;
                                }
                            }
                        }
                    }
                }
            }
        }

        //Add dependent ACL actions to $selectedAcos
        $dependentAcoIdsInUse = []; // don't reset already assigned dependencies
        foreach ($selectedAcos as $acoId => $permissions) {
            if ($permissions === 1) {
                if (isset($dependencyTree[$acoId])) {
                    //Add dependencies to $selectedAcos;

                    foreach ($dependencyTree[$acoId] as $dependentAcoId) {
                        $selectedAcos[$dependentAcoId] = 1;
                        $dependentAcoIdsInUse[$dependentAcoId] = $dependentAcoId;
                    }
                }
            } else {
                if (isset($dependencyTree[$acoId])) {
                    //Remove dependencies from $selectedAcos;
                    foreach ($dependencyTree[$acoId] as $dependentAcoId) {
                        if (!in_array($dependentAcoId, $dependentAcoIdsInUse, true)) {
                            $selectedAcos[$dependentAcoId] = 0;
                        }
                    }
                }
            }
        }
        return $selectedAcos;
    }

    public function filterAcosForFrontend($acosResultThreaded) {
        $allDependenciesSimplified = [];

        foreach ($this->dependencies as $controllerName => $actions) { //1
            if (substr($controllerName, -6) === 'Module') {
                $pluginName = $controllerName;
                $pluginControllers = $actions;

                foreach ($pluginControllers as $pluginControllerName => $pluginActions) { //Like 1 for core
                    foreach ($pluginActions as $pluginActionName => $dependentPluginController) { //Like 2 for core
                        foreach ($dependentPluginController as $dependentPluginControllerName => $dependentPluginActions) { //Like 3 for core
                            foreach ($dependentPluginActions as $dependentPluginAction) { //Like 4 for core
                                if (!isset($allDependenciesSimplified[$pluginName][$dependentPluginControllerName])) {
                                    $allDependenciesSimplified[$pluginName][$dependentPluginControllerName] = [];
                                }

                                $allDependenciesSimplified[$pluginName][$dependentPluginControllerName][$dependentPluginAction] = $dependentPluginAction;
                            }
                        }
                    }
                }
            } else {
                //Core
                foreach ($actions as $actionName => $dependentController) { //2
                    foreach ($dependentController as $dependentControllerName => $dependentActions) { //3
                        foreach ($dependentActions as $dependentAction) { //4
                            if (!isset($allDependenciesSimplified[$dependentControllerName])) {
                                $allDependenciesSimplified[$dependentControllerName] = [];
                            }

                            $allDependenciesSimplified[$dependentControllerName][$dependentAction] = $dependentAction;
                        }
                    }
                }
            }
        }

        foreach ($acosResultThreaded[0]['children'] as $controllerIndex => $controller) {
            if (substr($controller['alias'], -6) === 'Module') {
                $pluginName = $controller['alias'];
                foreach ($controller['children'] as $pluginControllerIndex => $pluginController) {
                    $pluginControllerName = $pluginController['alias'];
                    foreach ($pluginController['children'] as $pluginActionIndex => $pluginAction) {
                        $pluginActionName = $pluginAction['alias'];

                        // Remove ACOs that are always allow (the user cannot untick them!)
                        if (isset($this->allow[$pluginName][$pluginControllerName][$pluginActionName])) {
                            unset($acosResultThreaded[0]['children'][$controllerIndex]['children'][$pluginControllerIndex]['children'][$pluginActionIndex]);
                        }

                        // Remove ACOs if they are dependencies of other ACOs (the user cannot untick them!)
                        if (isset($allDependenciesSimplified[$pluginName][$pluginControllerName][$pluginActionName])) {
                            unset($acosResultThreaded[0]['children'][$controllerIndex]['children'][$pluginControllerIndex]['children'][$pluginActionIndex]);
                        }

                        // Remove ACOs that shoud be ignored like public functions form AppController that exists in all controllers due to class FooController extends AppController and so on
                        if (in_array($pluginActionName, $this->ignore, true)) {
                            unset($acosResultThreaded[0]['children'][$controllerIndex]['children'][$pluginControllerIndex]['children'][$pluginActionIndex]);
                        }
                    }
                }

            } else {
                //Core
                $controllerName = $controller['alias'];
                foreach ($controller['children'] as $actionIndex => $action) {
                    $actionName = $action['alias'];

                    // Remove ACOs that are always allow (the user cannot untick them!)
                    if (isset($this->allow[$controllerName][$actionName])) {
                        unset($acosResultThreaded[0]['children'][$controllerIndex]['children'][$actionIndex]);
                    }

                    // Remove ACOs if they are dependencies of other ACOs (the user cannot untick them!)
                    if (isset($allDependenciesSimplified[$controllerName][$actionName])) {
                        unset($acosResultThreaded[0]['children'][$controllerIndex]['children'][$actionIndex]);
                    }

                    // Remove ACOs that should be ignored like public functions form AppController that exists in all controllers due to class FooController extends AppController and so on
                    if (in_array($actionName, $this->ignore, true)) {
                        unset($acosResultThreaded[0]['children'][$controllerIndex]['children'][$actionIndex]);
                    }
                }
            }

            //Make sure we have arrays [] not hashmaps {} !!!
            $acosResultThreaded[0]['children'][$controllerIndex]['children'] = array_values($acosResultThreaded[0]['children'][$controllerIndex]['children']);
        }
        foreach ($acosResultThreaded[0]['children'] as $key => $value) {
            if (Hash::dimensions($value['children']) === 2) {
                $acosResultThreaded[0]['children'][$key] = Hash::sort($acosResultThreaded[0]['children'][$key], 'alias', 'asc');
            }
        }
        return $acosResultThreaded;
    }
}
