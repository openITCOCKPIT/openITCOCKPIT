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

namespace itnovum\openITCOCKPIT\Core\System\Health;


use Cake\ORM\Table;

class MySQLHealth {

    /**
     * @var Table
     */
    private $Table;

    public function __construct(Table $Table) {
        $this->Table = $Table;
    }

    /**
     * @return array
     */
    public function selectInnoDbMetrics() {
        $connection = $this->getDbConnection();
        $result = $connection->execute('SHOW GLOBAL STATUS LIKE "innodb%"');
        $result = $result->fetchAll('assoc');
        return $this->parseGlobalStatusResult($result);
    }

    /**
     * @return array
     */
    public function selectTmpMetrics() {
        $connection = $this->getDbConnection();
        $result = $connection->execute('SHOW GLOBAL STATUS LIKE "Created_tmp_%"');
        $result = $result->fetchAll('assoc');
        return $this->parseGlobalStatusResult($result);
    }

    /**
     * @return array
     */
    public function selectQueryCacheMetrics() {
        $connection = $this->getDbConnection();
        $result = $connection->execute('SHOW GLOBAL STATUS LIKE "Qcache%"');
        $result = $result->fetchAll('assoc');
        return $this->parseGlobalStatusResult($result);
    }

    public function selectComMetrics() {
        $connection = $this->getDbConnection();
        $fields = [
            'Com_alter_table',
            'Com_commit',
            'Com_create_table',
            'Com_delete',
            'Com_insert',
            'Com_select',
            'Com_set_option',
            'Com_show_create_table',
            'Com_truncate',
            'Com_update'
        ];

        $result = $connection->execute(sprintf(
            "SHOW GLOBAL STATUS WHERE Variable_name IN ('%s')",
            implode("', '", $fields)
        ));
        $result = $result->fetchAll('assoc');
        return $this->parseGlobalStatusResult($result);
    }

    /**
     * @return array
     */
    public function getAllMetrics() {
        $metrics = [];
        foreach ($this->selectInnoDbMetrics() as $key => $value) {
            $metrics[$key] = $value;
        }

        foreach ($this->selectComMetrics() as $key => $value) {
            $metrics[$key] = $value;
        }

        foreach ($this->selectQueryCacheMetrics() as $key => $value) {
            $metrics[$key] = $value;
        }

        foreach ($this->selectTmpMetrics() as $key => $value) {
            $metrics[$key] = $value;
        }
        return $metrics;
    }

    /**
     * @param $rawResult
     * @return array
     */
    private function parseGlobalStatusResult($rawResult) {
        $result = [];

        foreach ($rawResult as $record) {
            $result[$record['Variable_name']] = $record['Value'];
        }
        return $result;
    }

    /**
     * @return \Cake\Database\Connection
     */
    private function getDbConnection() {
        return $this->Table->getConnection();
    }

}
