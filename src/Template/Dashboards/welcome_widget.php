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

<div class="row">
    <div class="col-12">
        <div class="pull-left">
            <img src="<?= h($userImage) ?>" id="userImage"
                 style="border-left: 3px solid #40AC2B; height: auto; width: 120px">
        </div>
        <div class="pull-left col-md-7">
            <strong>{{hostCount}}</strong ng-if="hostCount"> <?php echo __('hosts are monitored'); ?>

            <br/>
            <strong>{{serviceCount}}</strong> <?php echo __('services are monitored'); ?>
            <br/>
            <br/>
            <?php echo __('Your selected Timezone is '); ?>
            <strong><?= h($userTimezone); ?></strong>
            <?php if ($userTimezone !== date_default_timezone_get()): ?>
                <br/>
                <?php echo __('Server timezone is:'); ?>
                <strong><?= date_default_timezone_get(); ?></strong>
            <?php endif; ?>
            <br/>
            <?= __('{0} version is {1},', $systemname, OPENITCOCKPIT_VERSION); ?>
            <?php
            if ($hasSubscription === false):
                echo __('No active subscription');
                echo ' 🥺';
            elseif ($hasSubscription === true && $isCommunityEdition === true):
                echo '<span class="text-community">' . __('Community Edition') . '</span>';
            else:
                echo '<span class="text-enterprise">' . __('Enterprise Edition') . '</span>';
            endif;
            ?>
        </div>
    </div>
</div>

