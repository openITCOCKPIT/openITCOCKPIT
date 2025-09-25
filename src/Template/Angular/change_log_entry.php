<?php declare(strict_types=1);
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
<time class="cbp_tmtime" datetime="{{changelogentry.time}}">
    <span>{{changelogentry.time}}</span>
    <span>{{changelogentry.timeAgoInWords}}</span>
</time>
<div class="cbp_tmicon txt-color-white {{changelogentry.color}}" title="{{changelogentry.action}}">
    <i class="{{changelogentry.icon}}"></i>
</div>
<div class="cbp_tmlabel">
    <h2 class="font-md">
        {{changelogentry.model}}:
        <strong>
            <a ui-sref="{{changelogentry.ngState}}({id: changelogentry.object_id})"
               ng-if="changelogentry.ngState && changelogentry.recordExists">
                {{changelogentry.name}}
            </a>
            <span ng-if="!changelogentry.ngState || !changelogentry.recordExists"
                  ng-class="{'changelog_delete': (changelogentry.action ==='delete' ||
                  (changelogentry.ngState && !changelogentry.recordExists))}">
                {{changelogentry.name}}
            </span>

        </strong>
        <span class="font-xs" ng-if="changelogentry.includeUser && changelogentry.user.id > 0">
            <?= __('by') ?>
            <?php if ($this->Acl->hasPermission('edit', 'users')): ?>
                <a ui-sref="UsersEdit({id: changelogentry.user.id})">
                    {{changelogentry.user.firstname}}
                    {{changelogentry.user.lastname}}
                </a>
            <?php else: ?>
                {{changelogentry.user.firstname}}
                {{changelogentry.user.lastname}}
            <?php endif; ?>
                                            </span>
        <span class="font-xs" ng-if="changelogentry.includeUser && changelogentry.user === null">
            <?= __('by Cronjob') ?>
        </span>
    </h2>


    <!-- Add and copy changes -->
    <blockquote
        ng-class="{'changelog-blockquote-success': changelogentry.action ==='add', 'changelog-blockquote-primary': changelogentry.action ==='copy'}"
        ng-if="(changelogentry.action === 'add' || changelogentry.action === 'copy') && changelogentry.showChanges"
        class="blockquote">
        <div class="margin-left-10"
             ng-repeat="(tableName, tableChanges) in changelogentry.data_unserialized">
            {{tableName}}

            <span ng-repeat="(fieldName, fieldValue) in tableChanges.data"
                  ng-if="!tableChanges.isArray && fieldName !== 'id'">
                <footer class="padding-left-10 blockquote-footer">
                    {{fieldName}}: <span class="text-primary">{{fieldValue}}</span>
                </footer>
            </span>

            <span ng-repeat="(fieldName, fieldValue) in tableChanges.data"
                  ng-if="tableChanges.isArray">
                <span ng-repeat="(subFieldName, subFieldValue) in fieldValue">
                    <footer class="padding-left-10 blockquote-footer"
                            ng-if="subFieldName !== 'id'">
                        {{subFieldName}}:
                        <span class="text-primary">{{subFieldValue}}</span>
                    </footer>
                </span>
                <div class="py-1"></div>
            </span>
        </div>
    </blockquote>

    <!-- Edit changes -->
    <blockquote class="changelog-blockquote-warning blockquote"
                ng-if="changelogentry.action === 'edit' && changelogentry.showChanges">
        <div class="margin-left-10"
             ng-repeat="(tableName, tableChanges) in changelogentry.data_unserialized">
            {{tableName}}

            <span ng-repeat="(fieldName, fieldValueChanges) in tableChanges.data"
                  ng-if="!tableChanges.isArray">
                <footer class="padding-left-10 blockquote-footer">
                    {{fieldName}}:
                    <span class="down">{{fieldValueChanges.old}}</span>
                    <i class="fa fa-caret-right"></i>
                    <span class="up">{{fieldValueChanges.new}}</span>
                </footer>
            </span>

            <span ng-repeat="(fieldIndex, fieldValueChanges) in tableChanges.data"
                  ng-if="tableChanges.isArray">
                <small
                    ng-repeat="(newFieldName, newFieldValue) in fieldValueChanges.new"
                    ng-if="fieldValueChanges.old === null">
                    <footer class="blockquote-footer">
                        {{newFieldName.replace('_id', '')}}:
                        <span class="up">{{newFieldValue}}</span>
                    </footer>

                </small>

                <small
                    ng-repeat="(oldFieldName, oldFieldValue) in fieldValueChanges.old"
                    ng-if="fieldValueChanges.new === null">
                    <footer class="blockquote-footer">
                        {{oldFieldName}}:
                        <span class="down changelog_delete">{{oldFieldValue}}</span>
                    </footer>
                </small>

                <small
                    ng-repeat="(newFieldName, newFieldValue) in fieldValueChanges.new"
                    ng-if="fieldValueChanges.old !== null && fieldValueChanges.new !== null">
                    <footer class="blockquote-footer">
                        {{newFieldName.replace('_id', '')}}:
                        <span
                            ng-class="{'text-primary': fieldValueChanges.old[newFieldName] === newFieldValue, 'down': fieldValueChanges.old[newFieldName] !== newFieldValue}">
                            {{fieldValueChanges.old[newFieldName]}}
                        </span>
                        <i class="fa fa-caret-right"></i>
                        <span
                            ng-class="{'text-primary': fieldValueChanges.old[newFieldName] === newFieldValue, 'up': fieldValueChanges.old[newFieldName] !== newFieldValue}">
                            {{newFieldValue}}
                        </span>
                    </footer>
                </small>

                <div class="py-1"></div>
            </span>
        </div>
    </blockquote>
</div>
