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

use Cake\Core\Plugin;

/** Monitoring Objects */
$objectDetails = [
    'hosts'                 => [
        'label'   => __('Hosts'),
        'icon'    => 'fa fa-desktop',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'hosts',
            'plugin'     => ''
        ],
        'ui-sref' => 'HostsEdit({id:id})'
    ],
    'hosttemplates'         => [
        'label'   => __('Host templates'),
        'icon'    => 'fa-solid fa-pen-to-square',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'hosttemplates',
            'plugin'     => ''
        ],
        'ui-sref' => 'HosttemplatesEdit({id:id})'
    ],
    'hostgroups'            => [
        'label'   => __('Host groups'),
        'icon'    => 'fas fa-server',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'hostgroups',
            'plugin'     => ''
        ],
        'ui-sref' => 'HostgroupsEdit({id:id})'
    ],
    'servicetemplates'      => [
        'label'   => __('Service templates'),
        'icon'    => 'fa-solid fa-pen-to-square',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'servicetemplates',
            'plugin'     => ''
        ],
        'ui-sref' => 'ServicetemplatesEdit({id:id})'
    ],
    'servicetemplategroups' => [
        'label'   => __('Service template groups'),
        'icon'    => 'fa-solid fa-pen-to-square',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'servicetemplategroups',
            'plugin'     => ''
        ],
        'ui-sref' => 'ServicetemplategroupsEdit({id:id})'
    ],
    'servicegroups'         => [
        'label'   => __('Service groups'),
        'icon'    => 'fa fa-cogs',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'servicegroups',
            'plugin'     => ''
        ],
        'ui-sref' => 'ServicegroupsEdit({id:id})'
    ],
    'timeperiods'           => [
        'label'   => __('Time periods'),
        'icon'    => 'fa fa-clock-o',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'timeperiods',
            'plugin'     => ''
        ],
        'ui-sref' => 'TimeperiodsEdit({id:id})'
    ],
    'contacts'              => [
        'label'   => __('Contacts'),
        'icon'    => 'fa fa-user',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'contacts',
            'plugin'     => ''
        ],
        'ui-sref' => 'ContactsEdit({id:id})'
    ],
    'contactgroups'         => [
        'label'   => __('Contact groups'),
        'icon'    => 'fa fa-users',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'contactgroups',
            'plugin'     => ''
        ],
        'ui-sref' => 'ContactgroupsEdit({id:id})'
    ],
    'users'         => [
        'label'   => __('Users'),
        'icon'    => 'fa fa-user',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'users',
            'plugin'     => ''
        ],
        'ui-sref' => 'UsersEdit({id:id})'
    ],
    'usercontainerroles'         => [
        'label'   => __('User container roles'),
        'icon'    => 'fa fa-users',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'usercontainerroles',
            'plugin'     => ''
        ],
        'ui-sref' => 'UsercontainerrolesEdit({id:id})'
    ],

    'hostdependencies'      => [
        'label'   => __('Hostdependencies'),
        'icon'    => 'fa fa-sitemap',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'hostdependencies',
            'plugin'     => ''
        ],
        'ui-sref' => 'HostdependenciesEdit({id:id})'
    ],
    'hostescalations'       => [
        'label'   => __('Host escalations'),
        'icon'    => 'fa fa-bomb',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'hostescalations',
            'plugin'     => ''
        ],
        'ui-sref' => 'HostescalationsEdit({id:id})'
    ],
    'servicedependencies'   => [
        'label'   => __('Service dependencies'),
        'icon'    => 'fa fa-sitemap',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'servicedependencies',
            'plugin'     => ''
        ],
        'ui-sref' => 'ServicedependenciesEdit({id:id})'
    ],
    'serviceescalations'    => [
        'label'   => __('Service escalations'),
        'icon'    => 'fa fa-bomb',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'serviceescalations',
            'plugin'     => ''
        ],
        'ui-sref' => 'ServiceescalationsEdit({id:id})'
    ],
    /* Reports */
    'instantreports'        => [
        'label'   => __('Instant reports'),
        'icon'    => 'fa fa-file-invoice',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'instantreports',
            'plugin'     => ''
        ],
        'ui-sref' => 'InstantreportsEdit({id:id})'
    ]
];
if (Plugin::isLoaded('AutoreportModule')) {
    $objectDetails['autoreports'] = [
        'label'   => __('Auto reports'),
        'icon'    => 'fa fa-file-invoice',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'autoreports',
            'plugin'     => 'AutoreportModule'
        ],
        'ui-sref' => 'AutoreportsEditStepOne({id:id})'
    ];
}
/** Satellites Objects */
if (Plugin::isLoaded('DistributeModule')) {
    $objectDetails['satellites'] = [
        'label'   => __('Satellites'),
        'icon'    => 'fas fa-satellite',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'satellites',
            'plugin'     => 'DistributeModule'
        ],
        'ui-sref' => 'SatellitesEdit({id:id})'
    ];
}

$objectDetails['maps'] = [
    'label'   => __('Maps'),
    'icon'    => 'fa fa-map-marker',
    'rights'  => [
        'action'     => 'edit',
        'controller' => 'maps',
        'plugin'     => 'MapModule'
    ],
    'ui-sref' => 'MapsEdit({id:id})'
];

/** Grafana User Dashboards Objects */
if (Plugin::isLoaded('GrafanaModule')) {
    $objectDetails['grafana_userdashboards'] = [
        'label'   => __('User dashboards'),
        'icon'    => 'fa fa-area-chart',
        'rights'  => [
            'action'     => 'edit',
            'controller' => 'GrafanaUserdashboards',
            'plugin'     => 'GrafanaModule'
        ],
        'ui-sref' => 'GrafanaUserdashboardsEdit({id: id})'
    ];
}


?>
<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="ContainersIndex({id: post.Container.id})">
            <i class="fa fa-link"></i> <?php echo __('Containers'); ?>
        </a>
    </li>
    <?php if ($this->Acl->hasPermission('showDetails', 'containers')): ?>
        <li class="breadcrumb-item">
            <i class="fa fa-sitemap"></i> <?php echo __('Show details'); ?>
        </li>
    <?php endif; ?>
</ol>


<div class="row">
    <div class="col-lg-12 margin-bottom-10">
        <select
            id="containers"
            class="form-control"
            chosen="containers"
            ng-model="post.Container.id"
            ng-options="container.key as container.value for container in containers | filter:{value : '!'+'/root'}:true"
            ng-model-options="{debounce: 500}">
        </select>

    </div>
</div>


<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Container '); ?>
                    <span class="fw-300"><i><?php echo __('details'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <ul class="nav nav-tabs border-bottom-0 nav-tabs-clean" role="tablist">
                        <?php if ($this->Acl->hasPermission('showDetails', 'containers')): ?>
                            <li class="nav-item pointer">
                                <a class="nav-link active" data-toggle="tab" ng-click="tabName='Containers'" role="tab">
                                    <i class="fas fa-layer-group"></i>&nbsp;</i> <?php echo __('Containers'); ?>
                                </a>
                            </li>
                            <li class="nav-item pointer">
                                <a class="nav-link" data-toggle="tab" ng-click="tabName='ContainersMap'" role="tab">
                                    <i class="fas fa-sitemap"></i>&nbsp;</i> <?php echo __('Containers map'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-xs btn-success shadow-0" ng-click="toggleFullscreenMode()"
                            ng-show="tabName ==='ContainersMap'"
                            title="<?php echo __('Fullscreen mode'); ?>">
                        <i class="fa fa-expand-arrows-alt"></i>
                    </button>
                    <?php if ($this->Acl->hasPermission('index', 'tenants')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='TenantsIndex'
                           class="btn btn-default btn-xs mr-1 shadow-0 margin-left-5">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="frame-wrap">
                        <div ng-show="tabName == 'Containers'">
                            <div class="margin-top-10" ng-hide="isEmpty">
                                <table class="table m-0 table-bordered table-hover table-sm container-table">
                                    <tr ng-repeat-start="container in containersWithChilds">
                                        <th colspan="3" class="table-dark opacity-70">
                                            <h5 class="margin-0">{{container.name}}
                                                <span class="badge border border-light text-light"
                                                      ng-show="container.containertype_id == <?= CT_TENANT; ?>">
                                                    <i class="fa fa-home"></i>
                                                    <?= __('Tenant'); ?>
                                                </span>
                                                <span class="badge border border-light text-light"
                                                      ng-show="container.containertype_id == <?= CT_LOCATION; ?>">
                                                    <i class="fa fa-location-arrow"></i>
                                                    <?= __('Location'); ?>
                                                </span>
                                                <span class="badge border border-light text-light"
                                                      ng-show="container.containertype_id == <?= CT_NODE; ?>">
                                                    <i class="fa fa-link"></i>
                                                    <?= __('Node'); ?>
                                                </span>
                                            </h5>
                                        </th>
                                    </tr>
                                    <?php foreach ($objectDetails as $objectType => $object): ?>
                                        <tr ng-show="container.childsElements.<?= $objectType; ?>">
                                            <td class="width-30 text-center">
                                                <i class="<?= $object['icon']; ?>"></i>
                                            </td>
                                            <td class="col-sm-3">

                                                <?= $object['label']; ?>
                                            </td>
                                            <td>
                                                <ul class="margin-0">
                                                    <li ng-repeat="(id, name) in container.childsElements.<?= $objectType; ?>"
                                                        class="list-unstyled">
                                                        <?php if ($this->Acl->hasPermission(
                                                            $object['rights']['action'],
                                                            $object['rights']['controller'],
                                                            $object['rights']['plugin'])): ?>
                                                            <a ui-sref="<?= $object['ui-sref']; ?>">
                                                                {{name}}
                                                            </a>
                                                        <?php else: ?>
                                                            {{name}}
                                                        <?php endif; ?>
                                                    </li>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr ng-repeat-end="">
                                    </tr>
                                </table>
                            </div>
                            <div class="margin-top-10" ng-if="isEmpty">
                                <div class="text-center text-danger italic">
                                    <?php echo __('No entries match the selection'); ?>
                                </div>
                            </div>
                        </div>
                        <div ng-show="tabName == 'ContainersMap'">
                            <!-- Loader -->
                            <div class="row padding-top-80" style="display:none;" id="visProgressbarLoader">
                                <div class="col-12">
                                    <div class="visloader-progressbar-center">
                                        <div class="text-center">
                                            {{nodesCount}} <?php echo __(' nodes'); ?>
                                            <span class="statusmap-progress-dots"></span>
                                        </div>
                                        <div class="progress">
                                            <div
                                                class="progress-bar progress-bar-striped bg-secondary progress-bar-animated"
                                                role="progressbar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End Loader -->


                            <div class="frame-wrap">
                                <div class="margin-top-10" ng-if="isEmpty">
                                    <div class="text-center text-danger italic">
                                        <?php echo __('No entries match the selection'); ?>
                                    </div>
                                </div>
                                <div id="containermap" class="bg-color-white"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
