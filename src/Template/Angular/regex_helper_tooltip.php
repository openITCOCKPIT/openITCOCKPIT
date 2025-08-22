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

<i class="far fa-lightbulb light-on regexHelper" data-toggle="popover" data-html="true" title="<?= __('RegEx Examples'); ?>"
   data-content='<div class="container">
        <div class="row bordered font-xs border-bottom padding-bottom-5">
           <div class="col col-lg-1 bg-primary bold text-white rounded text-nowrap">.*</div>
           <div class="col col-lg-3 text-nowrap"><span class="bold text-primary">localhost</span></div>
           <div class="col-md-auto"><?= __('Match anything regex'); ?></div>
        </div>
        <div class="row bordered font-xs border-bottom padding-bottom-5">
           <div class="col col-lg-1 bg-primary bold text-white rounded">.</div>
           <div class="col col-lg-3 text-nowrap">local<span class="bold text-primary">.</span>ost</div>
           <div class="col-md-auto"><?= __('Match any character'); ?></div>
        </div>
        <div class="row bordered font-xs border-bottom padding-bottom-5">
           <div class="col col-lg-1 bg-primary bold text-white rounded">^</div>
           <div class="col col-lg-3 text-nowrap"><span class="bold text-primary">^</span>ocalhost</div>
           <div class="col-md-auto"><?= __('Match the beginning of a string'); ?></div>
        </div>
        <div class="row bordered font-xs border-bottom padding-bottom-5">
           <div class="col col-lg-1 bg-primary bold text-white rounded">$</div>
           <div class="col col-lg-3 text-nowrap">localhos<span class="bold text-primary">$</span></div>
           <div class="col-md-auto"><?= __('Match the end of a string'); ?></div>
        </div>
        <div class="row bordered font-xs padding-bottom-5">
           <div class="col col-lg-1 bg-primary bold text-white rounded">|</div>
           <div class="col col-lg-3 text-nowrap">wan<span class="bold text-primary">|</span>lan</div>
           <div class="col-md-auto"><?= __('Match either of the sequences'); ?></div>
        </div>
</div>'></i>
