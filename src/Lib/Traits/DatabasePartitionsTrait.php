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

namespace App\Lib\Traits;


use Cake\ORM\Table;
use Cake\Utility\Hash;

trait DatabasePartitionsTrait {

    /**
     * Returns existing MySQL Partitions as array
     * @param Table $Table
     * @return array
     */
    public function getPartitionsByTable(Table $Table): array {
        $Connection = $Table->getConnection();

        //Get existing partitions for this table out of MySQL's information_schema
        $query = $Connection->execute("
                SELECT partition_name
                FROM information_schema.partitions
                WHERE TABLE_SCHEMA = :databaseName
                AND TABLE_NAME = :tableName", [
            'databaseName' => $Connection->config()['database'],
            'tableName'    => $Table->getTable()
        ]);
        $result = $query->fetchAll('assoc');

        //MySQL 5.x
        $partitions = Hash::extract($result, '{n}.partition_name');

        //MySQL 8.x
        if (isset($result['0']['PARTITION_NAME'])) {
            //MySQL 8.x
            $partitions = Hash::extract($result, '{n}.PARTITION_NAME');
        }

        // Remove all empty values (MySQL 5.7 and MariaDB ) tend to return a null record
        return array_filter($partitions);
    }

    /**
     * This method creates the first Partition into a Table which has no partitions yet.
     * It is a more dynamic (or elegant) alternative to hardcoded table definitions like here:
     * https://github.com/openITCOCKPIT/openITCOCKPIT/blob/cdc26c67341b10389390ba4ae254e619877a980e/partitions_statusengine3.sql#L75-L77
     *
     * The idea is to use MySQL partitions and still be able to use cakephp/migrations to manage the schema
     *
     * This function can be used to create the initial partition by a column which stores a unix timestamp.
     * DO NOT USE FOR DATETIME COLUMNS.
     *
     * This function is considered as unsafe because it is not using a prepared statement.
     * NEVER PASS ANY USER INPUT TO THIS FUNCTION - IT'S A BAD IDEA ON DIFFERENT LEVELS
     *
     * From the MySQL docs:
     * > In general, parameters are legal only in Data Manipulation Language (DML) statements, and not in Data Definition Language (DDL) statements.
     * https://dev.mysql.com/doc/c-api/8.0/en/mysql-stmt-prepare.html
     *
     * @param Table $Table
     * @param string $columName
     * @return void
     */
    public function alterTableAndCreateFirstPartitionByUnixtimestampUnsafe(Table $Table, string $columName): void {
        $Connection = $Table->getConnection();

        try {
            $query = $Connection->execute(sprintf("
                ALTER TABLE %s PARTITION BY RANGE ( %s DIV 86400 ) (
                    PARTITION p_max VALUES LESS THAN ( MAXVALUE )
                )",
                $Table->getTable(),
                $columName
            ));

            $query->fetchAll('assoc');
        } catch (\Exception $e) {
            // For unknown reason this throws an
            // "SQLSTATE[HY000]: General error"
            // error on Ubuntu 20.04 BUT WORKS???!??!?!?
        }
    }

    /**
     * This method creates the first Partition into a Table which has no partitions yet.
     * It is a more dynamic (or elegant) alternative to hardcoded table definitions like here:
     * https://github.com/openITCOCKPIT/openITCOCKPIT/blob/cdc26c67341b10389390ba4ae254e619877a980e/partitions_statusengine3.sql#L75-L77
     *
     * The idea is to use MySQL partitions and still be able to use cakephp/migrations to manage the schema
     *
     * This function can be used to create the initial partition by a column which stores a MySQL DATETIME.
     * DO NOT USE FOR UNIX TIMESTAMP COLUMNS.
     *
     * This function is considered as unsafe because it is not using a prepared statement.
     * NEVER PASS ANY USER INPUT TO THIS FUNCTION - IT'S A BAD IDEA ON DIFFERENT LEVELS
     *
     * From the MySQL docs:
     * > In general, parameters are legal only in Data Manipulation Language (DML) statements, and not in Data Definition Language (DDL) statements.
     * https://dev.mysql.com/doc/c-api/8.0/en/mysql-stmt-prepare.html
     *
     * @param Table $Table
     * @param string $columName
     * @return void
     */
    public function alterTableAndCreateFirstPartitionByDatetimeUnsafe(Table $Table, string $columName): void {
        $Connection = $Table->getConnection();

        try {
            $query = $Connection->execute(sprintf("
                ALTER TABLE %s PARTITION BY RANGE ( TO_DAYS(%s) ) (
                    PARTITION p_max VALUES LESS THAN ( MAXVALUE )
                )",
                $Table->getTable(),
                $columName
            ));

            $query->fetchAll('assoc');
        } catch (\Exception $e) {
            // For unknown reason this throws an
            // "SQLSTATE[HY000]: General error"
            // error on Ubuntu 20.04 BUT WORKS???!??!?!?
        }
    }

    /**
     * Drops a partition from the given MySQL table by running an unsafe SQL statement
     *
     * This function is considered as unsafe because it is not using a prepared statement.
     * NEVER PASS ANY USER INPUT TO THIS FUNCTION - IT'S A BAD IDEA ON DIFFERENT LEVELS
     *
     * From the MySQL docs:
     * > In general, parameters are legal only in Data Manipulation Language (DML) statements, and not in Data Definition Language (DDL) statements.
     * https://dev.mysql.com/doc/c-api/8.0/en/mysql-stmt-prepare.html
     *
     * @param Table $Table
     * @param string $partitionName
     * @return \Cake\Database\StatementInterface
     */
    public function dropPartitionByNameUnsafe(Table $Table, string $partitionName) {
        $Connection = $Table->getConnection();
        return $Connection->execute("ALTER TABLE " . $Connection->config()['database'] . "." . $Table->getTable() . " DROP PARTITION " . $partitionName . ";");
    }

}
