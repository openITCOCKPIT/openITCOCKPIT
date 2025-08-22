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

namespace itnovum\openITCOCKPIT\Crate;


class CrateHosttemplateForHost {

    /**
     * @var int
     */
    private $hosttemplate_id;

    /**
     * @var int
     */
    private $active_checks_enabled;

    /**
     * @var string
     */
    private $tags;

    /**
     * CrateHosttemplateForHost constructor.
     * @param int $id
     */
    public function __construct($id) {
        $this->hosttemplate_id = (int)$id;
    }

    /**
     * @param int $active_checks_enabled
     */
    public function setActiveChecksEnabled($active_checks_enabled) {
        $this->active_checks_enabled = (int)$active_checks_enabled;
    }

    /**
     * @param string $tags
     */
    public function setTags($tags) {
        $this->tags = $tags;
    }


    /**
     * @param array $hosttemplate
     */
    public function setDataFromFindResult($hosttemplate) {
        $this->setActiveChecksEnabled($hosttemplate['Hosttemplate']['active_checks_enabled']);
        $this->setTags($hosttemplate['Hosttemplate']['tags']);
    }

    /**
     * @return array
     */
    public function getFindQuery() {
        return [
            'recursive'  => -1,
            'fields'     => [
                'Hosttemplate.id',
                'Hosttemplate.active_checks_enabled',
                'Hosttemplate.tags',
            ],
            'conditions' => [
                'Hosttemplate.id' => $this->hosttemplate_id
            ]
        ];
    }

    public function getDataToUpdateActiveChecksEnabled() {
        return [
            'active_checks_enabled' => $this->active_checks_enabled
        ];
    }

    public function getConditionToUpdateActiveChecksEnabled() {
        return [
            'active_checks_enabled_from_template' => true,
            'hosttemplate_id'                     => $this->hosttemplate_id
        ];
    }

    public function getDataToUpdateTags() {
        return [
            'tags' => $this->tags
        ];
    }

    public function getConditionToUpdateTags() {
        return [
            'tags_from_template' => true,
            'hosttemplate_id'    => $this->hosttemplate_id
        ];
    }


}
