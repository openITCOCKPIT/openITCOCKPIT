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

/*
 *         _                    _
 *   __ _ (_) __ ___  __ __   _(_) _____      __
 *  / _` || |/ _` \ \/ / \ \ / / |/ _ \ \ /\ / /
 * | (_| || | (_| |>  <   \ V /| |  __/\ V  V /
 *  \__,_|/ |\__,_/_/\_\   \_/ |_|\___| \_/\_/
 *      |__/
*/
if (!empty($commandarguments)) {
    foreach ($commandarguments as $commandargument) {
        $input_value = '';
        if (isset($commandargument['Servicecommandargumentvalue']['value']) && $commandargument['Servicecommandargumentvalue']['value'] !== null) {
            $input_value = $commandargument['Servicecommandargumentvalue']['value'];
        }
        echo $this->Form->input('Servicecommandargumentvalue.' . $commandargument['Commandargument']['id'] . '.value', [
            'label'     => [
                'class' => 'col col-md-2 control-label text-primary',
                'text'  => $commandargument['Commandargument']['human_name'],
            ],
            'div'       => [
                'class' => 'form-group',
            ],
            'class'     => 'form-control',
            'wrapInput' => 'col col-md-8',
            'value'     => $input_value,
        ]);
        echo $this->Form->input('Servicecommandargumentvalue.' . $commandargument['Commandargument']['id'] . '.commandargument_id', [
            'type'  => 'hidden',
            'value' => $commandargument['Commandargument']['id'],
        ]);
        if (isset($commandargument['Servicecommandargumentvalue']['id']) && $commandargument['Servicecommandargumentvalue']['id'] !== null) {
            echo $this->Form->input('Servicecommandargumentvalue.' . $commandargument['Commandargument']['id'] . '.id', [
                'type'  => 'hidden',
                'value' => $commandargument['Servicecommandargumentvalue']['id'],
            ]);
        }
    }
} else {
    ?>
    <div class="form-group">
        <label class="col col-md-2 control-label hidden-mobile hidden-tablet"><!-- spacer for nice layout --></label>
        <label class="col col-md-8 col-xs-12 text-primary"><i
                    class="fa fa-info-circle"></i> <?php echo __('no parameters for this command defined'); ?></label>
    </div>
    <?php
}
