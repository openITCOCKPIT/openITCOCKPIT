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
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.


use itnovum\openITCOCKPIT\Monitoring\DefaultMacros;

?>

<ol class="breadcrumb page-breadcrumb">
    <li class="breadcrumb-item">
        <a ui-sref="DashboardsIndex">
            <i class="fa fa-home"></i> <?php echo __('Home'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <a ui-sref="CommandsIndex">
            <i class="fa fa-terminal"></i> <?php echo __('Commands'); ?>
        </a>
    </li>
    <li class="breadcrumb-item">
        <i class="fas fa-edit"></i> <?php echo __('Edit'); ?>
    </li>
</ol>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Edit command:'); ?>
                    <span class="fw-300"><i>{{post.Command.name}}</i></span>
                </h2>
                <div class="panel-toolbar">
                    <div class="text-muted cursor-default d-none d-sm-none d-md-none d-lg-block margin-right-10">
                        UUID: {{post.Command.uuid}}
                    </div>

                    <button ng-click="showDefaultMacros()"
                            class="btn btn-xs btn-primary mr-1 shadow-0">
                        <i class="fa fa-usd"></i>
                        <?php echo __('Default macros overview'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('index', 'macros')): ?>
                        <button ng-click="showMacros()"
                                class="btn btn-xs btn-primary mr-1 shadow-0"><?php echo __('$USERn$ overview'); ?></button>
                    <?php endif; ?>

                    <?php if ($this->Acl->hasPermission('index', 'commands')): ?>
                        <a back-button href="javascript:void(0);" fallback-state='CommandsIndex'
                           class="btn btn-xs btn-default shadow-0">
                            <i class="fas fa-long-arrow-alt-left"></i> <?php echo __('Back'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <form ng-submit="submit();" ng-init="successMessage=
                        {objectName : '<?php echo __('Command'); ?>' , message: '<?php echo __('updated successfully'); ?>'}">

                        <div class="row">
                            <div class="col-xs-12 col-md-offset-2 col-md-12 col-lg-12 padding-left-0 padding-right-0">
                                <div class="alert alert-block alert-warning">
                                    <a class="close" data-dismiss="alert" href="javascript:void(0);">×</a>
                                    <h4 class="alert-heading">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <?php echo __('Security notice'); ?>
                                    </h4>
                                    <?php echo __('User defined macros inside of command_line could lead to unwanted code execution.'); ?>
                                    <br/>
                                    <?php echo __('It is recommended to only provide access for a certain group of users to edit commands and user defined macros.'); ?>
                                </div>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="control-label">
                                <?php echo __('Command type'); ?>
                            </label>
                            <select
                                class="form-control"
                                chosen="commandtypes"
                                ng-init="post.Command.command_type='1'"
                                ng-model="post.Command.command_type">
                                <?php
                                $command_types = [
                                    CHECK_COMMAND        => __('Service check command'),
                                    HOSTCHECK_COMMAND    => __('Host check command'),
                                    NOTIFICATION_COMMAND => __('Notification command'),
                                    EVENTHANDLER_COMMAND => __('Eventhandler command'),
                                ];
                                foreach ($command_types as $key => $value) :
                                    printf('<option value="%s">%s</option>', $key, $value);
                                endforeach;
                                ?>
                            </select>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.name}">
                            <label class="control-label">
                                <?php echo __('Name'); ?>
                            </label>
                            <input
                                class="form-control"
                                type="text"
                                ng-model="post.Command.name">
                            <div ng-repeat="error in errors.name">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="form-group required" ng-class="{'has-error': errors.command_line}">
                            <label class="control-label" for="commandLineTextArea">
                                <?php echo __('Command line'); ?>
                            </label>
                            <textarea class="form-control code-font" type="text" ng-model="post.Command.command_line"
                                      cols="30" rows="6" id="commandLineTextArea">
                                </textarea>
                            <div ng-repeat="error in errors.command_line">
                                <div class="help-block text-danger">{{ error }}</div>
                            </div>
                        </div>

                        <div class="col col-md-10 col-xs-12 text-info padding-bottom-10">
                            <i class="fa fa-info-circle"></i>

                            <?php
                            $link = __('user defined macro');
                            if ($this->Acl->hasPermission('index', 'macros')):
                                $link = sprintf('<a ui-sref="MacrosIndex">%s</a>', $link);
                            endif;
                            ?>

                            <?php echo __('A $-sign needs to be escaped manually (\$). Semicolons (;) needs to be defined as {0}.', $link); ?>
                            <br/>
                            <?php echo __('Nagios supports up to 32 $ARGx$ macros ($ARG1$ through $ARG32$)'); ?>
                        </div>


                        <div class="form-group">
                            <label class="form-label" for="descriptionTextArea">
                                <?php echo __('Description'); ?>
                            </label>
                            <textarea class="form-control" type="text" ng-model="post.Command.description"
                                      cols="30" rows="6" id="descriptionTextArea">
                                </textarea>
                        </div>


                        <!-- Arguments -->
                        <legend class="font-sm">
                            <h5><?php echo __('Arguments'); ?>:</h5>
                        </legend>
                        <div id="command_args">
                            <!-- empty because we create a new command! -->
                        </div>

                        <div ng-repeat="arg in args" class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-1">
                                    <div style="margin-top: 24px;" class="text-purple">
                                        {{arg.name}}
                                    </div>
                                </div>
                                <div class="col-lg-10 form-group">
                                    <label class="control-label" for="newArg">
                                        <?php echo __('Name'); ?>
                                    </label>
                                    <input class="form-control input-sm" type="text"
                                           placeholder="<?php echo __('Please enter a name'); ?>"
                                           name="data[Commandargument][{{arg.id}}][human_name]"
                                           ng-model="arg.human_name"
                                           id="newArg"
                                           style="width:100%;">
                                </div>
                                <div class="col-md-1 col-lg-1">
                                    <a class="btn btn-danger btn-sm waves-effect waves-themed deleteCommandArg margin-top-25"
                                       href="javascript:void(0);"
                                       ng-click="removeArg(arg.count)">
                                        <i class="fa fa-trash fa-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row ml-1">
                            <div ng-show="args.length > 0">
                                <span class="col col-lg-3 col-xs-12 text-danger">
                                    <i class="fa fa-exclamation-circle"></i>
                                    <?php echo __('empty arguments will be removed automatically'); ?>
                                </span>
                            </div>
                            <div class="ml-auto mr-3">
                                <a class="btn btn-success btn-xs " id="add_new_arg" href="javascript:void(0);"
                                   ng-click="addArg()">
                                    <i class="fa fa-plus"></i>
                                    <?php echo __('Add argument'); ?>
                                </a>
                            </div>
                        </div>

                        <div class="card margin-top-10">
                            <div class="card-body">
                                <div class="float-right">
                                    <button class="btn btn-primary"
                                            type="button"
                                            ng-click="checkForMisingArguments()"><?php echo __('Update command'); ?></button>
                                    <a back-button href="javascript:void(0);" fallback-state='CommandsIndex'
                                       class="btn btn-default"><?php echo __('Cancel'); ?></a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if ($this->Acl->hasPermission('index', 'macros')): ?>
    <div class="modal" tabindex="-1" role="dialog" id="MacrosOverview">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('User defined macros'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="MacroContent">
                                <table id="macrosTable"
                                       class="table table-striped m-0 table-bordered table-hover table-sm">
                                    <thead>
                                    <tr>
                                        <th class="no-sort">
                                            <?php echo __('Name'); ?>
                                        </th>
                                        <th class="no-sort">
                                            <?php echo __('Value'); ?>
                                        </th>
                                        <th class="no-sort">
                                            <?php echo __('Description'); ?>
                                        </th>
                                        <th>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr ng-repeat="macro in macros">
                                        <td class="text-primary bold">
                                            {{macro.name}}
                                        </td>
                                        <td>
                                            <code ng-class="{'macroPassword': macro.password}">
                                                {{macro.value}}
                                            </code>
                                        </td>
                                        <td>
                                            {{macro.description}}
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-default txt-color-red btn-xs"
                                                    title="<?php echo __('Hide value'); ?>"
                                                    ng-click="macro.password = 1"
                                                    ng-hide="macro.password">
                                                <i class="fa fa-eye-slash"></i>
                                            </button>

                                            <button class="btn btn-default txt-color-blue btn-xs"
                                                    title="<?php echo __('Show value'); ?>"
                                                    ng-click="macro.password = 0"
                                                    ng-show="macro.password">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                            data-dismiss="modal"><?php echo __('Close'); ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>


<div class="modal" tabindex="-1" role="dialog" id="argumentMisMatchModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><?php echo __('Mismatch in number of defined arguments detected'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">
                        <?php echo __('Different amount of used {0} variables compared to defined arguments!', '<code>$ARGn$</code>'); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-12">
                        <?php echo __('Number of used {0} variables:', '<code>$ARGn$</code>'); ?> <strong>{{usedCommandLineArgs}}</strong>&nbsp;
                    </div>
                    <div class="col-xs-12">
                        <?php echo __('Number of defined arguments:'); ?> <strong>{{definedCommandArguments}}</strong>
                    </div>
                </div>

                <div class="row padding-top-10" ng-show="usedCommandLineToDefinedCommandArguments.length > 0">
                    <div class="col-xs-12">
                        <?= __('Used in command line, but missing in arguments definition'); ?>
                        <code>{{usedCommandLineToDefinedCommandArguments.join(', ')}}</code>

                    </div>
                </div>
                <div class="row padding-top-10">
                    <div class="col-xs-12" ng-show="definedCommandArgumentsToUsedCommandLine.length > 0">
                        <?= __('Defined in arguments definition, but not used in command line'); ?>
                        <code>{{definedCommandArgumentsToUsedCommandLine.join(', ')}}</code>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" ng-click="submit()">
                    <?php echo __('Save anyway'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Cancel'); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal" tabindex="-1" role="dialog" id="defaultMacrosOverview">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('List of all available default macros'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-xs-12">

                        <?php foreach (DefaultMacros::getMacros() as $macroCategory): ?>
                            <h4>
                                <?php echo h($macroCategory['category']); ?>
                            </h4>
                            <table id="macrosTable"
                                   class="table table-striped m-0 table-bordered table-hover padding-bottom-20">
                                <thead>
                                <tr>
                                    <th class="no-sort">
                                        <?php echo __('Macro'); ?>
                                    </th>
                                    <th class="no-sort">
                                        <?php echo __('Description'); ?>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($macroCategory['macros'] as $macro): ?>
                                    <tr>
                                        <td class="text-primary bold">
                                            <?php echo h($macro['macro']); ?>
                                        </td>
                                        <td>
                                            <?php echo h($macro['description']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <hr>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __('Close'); ?></button>
            </div>
        </div>
    </div>
</div>
