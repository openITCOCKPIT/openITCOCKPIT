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

use itnovum\openITCOCKPIT\ConfigGenerator\NagiosCfg;


/** @var NagiosCfg $NagiosCfg */

?>


<form ng-submit="submit();" class="form-horizontal">

    <?php foreach ($NagiosCfg->getDefaults()['bool'] as $key => $defaultValue): ?>
        <div class="form-group" ng-class="{'has-error': errors.Configfile.<?php echo $key; ?>}">
            <div class="custom-control custom-checkbox  margin-bottom-10"
                 ng-class="{'has-error': errors.Configfile.<?php echo $key; ?>}">

                <input type="checkbox"
                       class="custom-control-input"
                       ng-true-value="1"
                       ng-false-value="0"
                       id="<?php echo $key; ?>"
                       ng-model="post.bool.<?php echo $key; ?>">
                <label class="custom-control-label" for="<?php echo $key; ?>">
                    <?php echo h($key); ?>
                </label>
            </div>

            <div class="col col-xs-12 col-md-offset-2 help-block">
                <?php echo h($NagiosCfg->getHelpText($key)); ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($NagiosCfg->getDefaults()['int'] as $key => $defaultValue): ?>
        <div class="form-group required" ng-class="{'has-error': errors.Configfile.<?php echo $key; ?>}">
            <label class="control-label">
                <?php echo h($key); ?>
            </label>
            <input
                class="form-control"
                type="number"
                ng-model="post.int.<?php echo $key; ?>">
            <div ng-repeat="error in errors.Configfile.<?php echo $key; ?>">
                <div class="help-block text-danger">{{ error }}</div>
            </div>
            <div class="help-block">
                <?php echo h($NagiosCfg->getHelpText($key)); ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($NagiosCfg->getDefaults()['float'] as $key => $defaultValue): ?>
        <div class="form-group required" ng-class="{'has-error': errors.Configfile.<?php echo $key; ?>}">
            <label class="control-label">
                <?php echo h($key); ?>
            </label>
            <input
                class="form-control"
                type="number"
                min="0"
                step="0.01"
                ng-model="post.float.<?php echo $key; ?>">
            <div ng-repeat="error in errors.Configfile.<?php echo $key; ?>">
                <div class="help-block text-danger">{{ error }}</div>
            </div>
            <div class="help-block">
                <?php echo h($NagiosCfg->getHelpText($key)); ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($NagiosCfg->getDefaults()['string'] as $key => $defaultValue): ?>
        <?php if ($key === 'service_check_timeout_state'): ?>
            <div class="form-group required" ng-class="{'has-error': errors.Configfile.service_check_timeout_state}">
                <label class="control-label" for="service_check_timeout_state">
                    service_check_timeout_state
                </label>
                <select
                    id="service_check_timeout_state"
                    data-placeholder="<?php echo __('Please choose'); ?>"
                    class="form-control"
                    chosen="{}"
                    ng-model="post.string.service_check_timeout_state">
                    <option value="c"><?php echo __('Critical (default)'); ?></option>
                    <option value="u"><?php echo __('Unknown'); ?></option>
                    <option value="w"><?php echo __('Warning'); ?></option>
                    <option value="o"><?php echo __('OK'); ?></option>
                </select>
                <div ng-repeat="error in errors.Configfile.service_check_timeout_state">
                    <div class="help-block text-danger">{{ error }}</div>
                </div>
                <div class="help-block">
                    <?php echo h($NagiosCfg->getHelpText($key)); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="form-group required" ng-class="{'has-error': errors.Configfile.<?php echo $key; ?>}">
                <label class="control-label">
                    <?php echo h($key); ?>
                </label>
                <input
                    class="form-control"
                    type="text"
                    ng-model="post.string.<?php echo $key; ?>">
                <div ng-repeat="error in errors.Configfile.<?php echo $key; ?>">
                    <div class="help-block text-danger">{{ error }}</div>
                </div>
                <div class="help-block">
                    <?php echo h($NagiosCfg->getHelpText($key)); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>


    <div class="card margin-top-10">
        <div class="card-body">
            <div class="float-right">
                <button class="btn btn-primary"
                        type="submit"><?php echo __('Save'); ?></button>
                <a back-button href="javascript:void(0);" fallback-state='ConfigurationFilesIndex'
                   class="btn btn-default"><?php echo __('Cancel'); ?></a>
            </div>
        </div>
    </div>
</form>
