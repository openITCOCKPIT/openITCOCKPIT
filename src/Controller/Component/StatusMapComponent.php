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

namespace App\Controller\Component;

use Cake\Controller\Component;

class StatusMapComponent extends Component {
    /**
     * @param $isDisable
     * @param \itnovum\openITCOCKPIT\Core\Hoststatus $Hoststatus
     * @return string $nodeGroup
     */
    public function getNodeGroupName($isDisable, \itnovum\openITCOCKPIT\Core\Hoststatus $Hoststatus) {
        $HumanReadableHostStates = ['Up', 'Down', 'Unreachable'];
        if ($isDisable) {
            return 'disabled';
        }
        if ($Hoststatus->currentState() === null) {
            return 'notMonitored';
        }
        if ($Hoststatus->isInDowntime() && $Hoststatus->isAcknowledged()) {
            $nodeGroupName = 'isAcknowledgedAndIsInDowntime';
        } else if ($Hoststatus->isInDowntime()) {
            $nodeGroupName = 'isInDowntime';
        } else if ($Hoststatus->isAcknowledged()) {
            $nodeGroupName = 'isAcknowledged';
        } else {
            $nodeGroupName = 'host'; // --> simple state group
        }
        //HostUp, or isAcknowledgedDown, ...
        $nodeGroupName = sprintf(
            '%s%s',
            $nodeGroupName,
            $HumanReadableHostStates[$Hoststatus->currentState()]
        );
        return $nodeGroupName;
    }
}
