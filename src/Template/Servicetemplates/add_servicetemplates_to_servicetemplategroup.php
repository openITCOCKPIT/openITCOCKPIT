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
?>

<div id="angularAddServicetemplatesToServicetemplategroups" class="modal" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary txt-color-white">
                <h5 class="modal-title">
                    <i class="fas fa-link"></i>
                    <?php echo __('Append service template/s to service template group'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <?php echo __('Selected service templates'); ?>
                    </div>

                    <div class="col-lg-12 margin-top-10">
                        <ul>
                            <li ng-repeat="(id, templateName) in objects">
                                {{ templateName }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" ui-sref="ServicetemplategroupsAdd({ids:servicetemplateIds})"
                        data-dismiss="modal">
                    <?php echo __('Create new service template group'); ?>
                </button>

                <button type="button" class="btn btn-primary" ui-sref="ServicetemplategroupsAppend({ids:servicetemplateIds})"
                        data-dismiss="modal">
                    <?php echo __('Append existing service template group'); ?>
                </button>

                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo __('Close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
