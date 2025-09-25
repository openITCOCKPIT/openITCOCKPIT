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

namespace itnovum\openITCOCKPIT\Core\AngularJS\Request;

use App\itnovum\openITCOCKPIT\Database\SanitizeOrder;
use itnovum\openITCOCKPIT\Core\ValueObjects\HostStates;
use itnovum\openITCOCKPIT\Core\ValueObjects\ServiceStates;
use itnovum\openITCOCKPIT\Core\ValueObjects\StateTypes;

class AngularRequest {

    /**
     * @var \Cake\Http\ServerRequest
     */
    private $Request;


    /**
     * @var array
     */
    protected $filters = [
        'action' => [
            'like' => [
                'Model.field'
            ]
        ]
    ];

    /**
     * @var string
     */
    protected $HostStateField = 'Hoststatus.current_state';

    /**
     * @var string
     */
    protected $HostStateTypeField = 'Hoststatus.state_type';

    /**
     * @var string
     */
    protected $ServiceStateField = 'Servicestatus.current_state';

    /**
     * @var string
     */
    protected $ServiceStateTypeField = 'Servicestatus.state_type';

    /**
     * AngularRequest constructor.
     * @param \CakeRequest || \Cake\Http\ServerRequest $Request
     */
    public function __construct(\Cake\Http\ServerRequest $Request) {
        $this->Request = $Request;
    }

    /**
     * @return ServiceStates
     */
    public function getServiceStates() {
        $field = $this->ServiceStateField;
        $ServiceStates = new ServiceStates();

        if ($this->queryHasField($field)) {
            $requestValues = $this->getQueryFieldValue($field);
            foreach ($ServiceStates->getAvailableStateIds() as $stateName => $stateId) {
                if (in_array($stateName, $requestValues, true)) {
                    $ServiceStates->setState($stateId, true);
                }
            }
        }

        return $ServiceStates;
    }

    /**
     * @return StateTypes
     */
    public function getServiceStateTypes() {
        $field = $this->ServiceStateTypeField;
        $ServiceStateTypes = new StateTypes();

        if ($this->queryHasField($field)) {
            $requestValues = $this->getQueryFieldValue($field);
            if ($requestValues !== '') {
                $ServiceStateTypes->setStateType((int)$requestValues, true);
            }
        }

        return $ServiceStateTypes;
    }

    /**
     * @return HostStates
     */
    public function getHostStates() {
        $field = $this->HostStateField;
        $HostStates = new HostStates();

        if ($this->queryHasField($field)) {
            $requestValues = $this->getQueryFieldValue($field);
            foreach ($HostStates->getAvailableStateIds() as $stateName => $stateId) {
                if (in_array($stateName, $requestValues, true)) {
                    $HostStates->setState($stateId, true);
                }
            }
        }

        return $HostStates;
    }

    /**
     * @return StateTypes
     */
    public function getHostStateTypes() {
        $field = $this->HostStateTypeField;
        $HostStateTypes = new StateTypes();

        if ($this->queryHasField($field)) {
            $requestValues = $this->getQueryFieldValue($field);
            if ($requestValues !== '') {
                $HostStateTypes->setStateType((int)$requestValues, true);
            }
        }

        return $HostStateTypes;
    }

    /**
     * @return false|float|int
     */
    public function getFrom() {
        if ($this->queryHasField('from')) {
            $value = strtotime($this->getQueryFieldValue('from'));
            if ($value) {
                return $value;
            }
        }
        return time() - (3600 * 24 * 30);
    }

    /**
     * @return false|float|int
     */
    public function getTo() {
        if ($this->queryHasField('to')) {
            $value = strtotime($this->getQueryFieldValue('to'));
            if ($value) {
                return $value;
            }
        }
        return time() + (3600 * 24 * 30 * 2);
    }


    /**
     * @return \Cake\Http\ServerRequest
     */
    public function getRequest() {
        return $this->Request;
    }


    /**
     * @param $field
     * @return bool
     */
    public function queryHasField($field) {
        $query = $this->Request->getQuery();
        return isset($query['filter'][$field]);
    }

    /**
     * @param $field
     * @return null|mixed
     */
    public function getQueryFieldValue($field, $strict = false) {
        if ($this->queryHasField($field)) {
            $query = $this->Request->getQuery();

            if ($strict === false) {
                return $query['filter'][$field];
            }

            if ($strict === true) {
                $value = $query['filter'][$field];
                if (is_array($value)) {
                    $value = array_filter($value, function ($val) {
                        if ($val === null || $val === '') {
                            return false;
                        }
                        return true;
                    });
                }
                if (!empty($value)) {
                    return $value;
                }
            }

        }
        return null;
    }


    /**
     * WARNING: Order fields/directions are not sanitized by the CakePHP query builder.
     * You should use an allowed list of fields/directions when passing in user-supplied data to order().
     *
     * @param string $default
     * @return string
     */
    protected function getSort($default = '') {
        $query = $this->Request->getQuery();

        $sort = $default;
        if (isset($query['sort'])) {
            $sort = $query['sort'];
        }
        return SanitizeOrder::filterOrderColumn($sort);
    }

    /**
     * @param string $default
     * @return string
     */
    protected function getDirection($default = '') {
        $query = $this->Request->getQuery();

        if (isset($query['direction'])) {
            return ($query['direction'] === 'asc') ? 'asc' : 'desc';
        }

        return ($default === 'asc') ? 'asc' : 'desc';
    }

    /**
     * @param string $sort
     * @param string $direction
     * @return array
     */
    public function getOrderForPaginator($sort = '', $direction = '') {
        return [
            $this->getSort($sort) => $this->getDirection($direction)
        ];
    }

    /**
     * @param int $default
     * @return int
     */
    public function getPage($default = 1) {
        $query = $this->Request->getQuery();

        if (isset($query['page'])) {
            return (int)$query['page'];
        }
        return (int)$default;
    }

}

