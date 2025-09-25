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

namespace itnovum\openITCOCKPIT\InitialDatabase;

use App\Model\Table\CommandsTable;
use App\Model\Table\ServicetemplatesTable;
use App\Model\Table\WizardAssignmentsTable;


/**
 * Class MysqlWizard
 * @package itnovum\openITCOCKPIT\InitialDatabase
 */
class MysqlWizard extends Importer {

    /**
     * @var CommandsTable
     */
    private $CommandsTable;

    /**
     * @var ServicetemplatesTable
     */
    private $ServicetemplatesTable;

    /**
     * @var WizardAssignmentsTable
     */
    private $WizardAssignmentsTable;

    /**
     * MysqlWizard constructor.
     * @param CommandsTable $CommandsTable
     * @param ServicetemplatesTable $ServicetemplatesTable
     * @param WizardAssignmentsTable $WizardAssignmentsTable
     */
    public function __construct(CommandsTable $CommandsTable, ServicetemplatesTable $ServicetemplatesTable, WizardAssignmentsTable $WizardAssignmentsTable) {
        $this->CommandsTable = $CommandsTable;
        $this->ServicetemplatesTable = $ServicetemplatesTable;
        $this->WizardAssignmentsTable = $WizardAssignmentsTable;
    }

    /**
     * @return bool
     */
    public function import() {
        $data = $this->getData();
        foreach ($data['Commands'] as $command) {
            if (isset($command['uuid']) && !$this->CommandsTable->existsByUuid($command['uuid'])) {
                $entity = $this->CommandsTable->newEntity($command);
                $this->CommandsTable->save($entity);
            }
        }

        foreach ($data['Servicetemplates'] as $servicetemplate) {
            if (isset($servicetemplate['uuid']) && !$this->ServicetemplatesTable->existsByUuid($servicetemplate['uuid'])) {
                if (isset($servicetemplate['command_id']) && $this->CommandsTable->existsByUuid($servicetemplate['command_id'])) {
                    $command = $this->CommandsTable->getCommandByUuid($servicetemplate['command_id'], true, false)[0];
                    $servicetemplate['command_id'] = $command['id'];
                    if (!empty($servicetemplate['servicetemplatecommandargumentvalues']) && !empty($command['commandarguments'])) {
                        foreach ($servicetemplate['servicetemplatecommandargumentvalues'] as $templateArgumentKey => $templateArgumentValue) {
                            $servicetemplate['servicetemplatecommandargumentvalues'][$templateArgumentKey] = [
                                'commandargument_id' => $this->getCommandArgumentIdByName($templateArgumentValue['commandargument_id'], $command['commandarguments']),
                                'value'              => $templateArgumentValue['value'],
                            ];
                        }
                    }

                    $entity = $this->ServicetemplatesTable->newEntity($servicetemplate);
                    $this->ServicetemplatesTable->save($entity);
                }
            }
        }

        if (!empty($data['Wizardassignment'])) {
            $wizardassignmentEntity['uuid'] = $data['Wizardassignment']['uuid'];
            $wizardassignmentEntity['type_id'] = $data['Wizardassignment']['type_id'];
            foreach ($data['Wizardassignment']['servicetemplates']['_ids'] as $servicetemplateUuid) {
                $servicetemplate = $this->ServicetemplatesTable->getServicetemplateByUuid($servicetemplateUuid);
                if (isset($servicetemplate['Servicetemplate']) && isset($servicetemplate['Servicetemplate']['id'])) {
                    $wizardassignmentEntity['servicetemplates']['_ids'][] = $servicetemplate['Servicetemplate']['id'];
                }
            }
            if (!$this->WizardAssignmentsTable->existsByUuidAndTypeId($wizardassignmentEntity['uuid'], $wizardassignmentEntity['type_id'])) {
                $entity = $this->WizardAssignmentsTable->newEntity($wizardassignmentEntity);
                $this->WizardAssignmentsTable->save($entity);
            } else {
                $existingAssignment = $this->WizardAssignmentsTable->getWizardByUuidForEdit($wizardassignmentEntity['uuid']);
                foreach (array_diff($wizardassignmentEntity['servicetemplates']['_ids'], $existingAssignment['servicetemplates']['_ids']) as $missingServicetemplateId) {
                    $existingAssignment['servicetemplates']['_ids'][] = $missingServicetemplateId;
                }
                $entity = $this->WizardAssignmentsTable->get($existingAssignment['id']);
                $entity = $this->WizardAssignmentsTable->patchEntity($entity, $existingAssignment);
                $this->WizardAssignmentsTable->save($entity);
            }
        }


        return true;
    }

    /**
     * @param string $name
     * @param array $commandarguments
     * @return bool|mixed
     */
    public function getCommandArgumentIdByName(string $name, array $commandarguments) {
        foreach ($commandarguments as $commandargument) {
            if (isset($commandargument['name']) && $commandargument['name'] === $name) {
                return $commandargument['id'];
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getWizardAssignmentData() {
        $data = [
            'uuid'             => '7fb02fac-1ac5-43cf-baf2-b5893f9f9aa8',
            'type_id'          => 'mysql-server',
            'servicetemplates' => [
                '_ids' => [
                    '976da093-ed3a-4fc2-978a-b8c37bb95a9d',
                    'b471e570-2921-487d-8189-c4bbfa9a09d5',
                    '7542af3e-14e7-4a39-afff-6ae316313858',
                    '41b49614-c2f1-4bdd-a03c-ef7523b810dd',
                    '9f4ef558-847e-4953-8119-fedabffc29bc',
                    '8c9a0fa8-2939-4aac-b252-f4f356313fe3',
                    '46229355-cc9d-4179-a484-b3075a7861a0',
                    '177ec9fe-6ec1-4bad-aa67-c20475bd8972',
                    'fea34274-eaca-48af-b64b-6f247b29fa07',
                    'e9bddda3-9581-4472-b774-25c641219490',
                    '3baceaba-3396-4400-a970-133c8e848eca',
                    '18bfc587-97b3-4f45-9ab4-73245453dee1',
                    '1e9eb002-aeb8-45b7-86a6-ea3f5f12d631',
                    '421af7c7-2736-4c03-a239-64dc8c2e2cf7',
                    '20a1e3b0-65b6-49af-8380-2bb55eedcc50',
                    '373e5fe2-22f9-4ebc-8055-a375ba4e951e',
                    '5bae7650-0694-4a2f-bfce-b4bb63659ff3',
                    'a0bce589-92c9-46a5-a44d-7b62efcfdd90',
                    '6163bdce-5fc1-4328-8f50-c5e21e021c39',
                    '8b0895be-bcc2-4699-baf4-9f69a40b00a2',
                    '2e64c98a-254d-4655-9ec1-7816a7c97cff'
                ]
            ]
        ];
        return $data;
    }

    public function getCommandsData() {
        $data = [
            /* connection-time in seconds*/
            [
                'name'             => 'check_mysql_health__connection-time',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode connection-time --units seconds --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'f503de41-54e2-40e9-9c26-fdfdb786a3c4',
                'description'      => 'Time to connect to the server',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in seconds'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in seconds'
                    ]
                ]
            ],
            /* uptime in seconds*/
            [
                'name'             => 'check_mysql_health__uptime',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode uptime',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'b553d65d-b422-44c2-ab1e-d8922d41e8af',
                'description'      => 'Time the server is running',
                'commandarguments' => []
            ],
            /* threads-connected */
            [
                'name'             => 'check_mysql_health__threads-connected',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode threads-connected --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '63317cd9-85d4-41d6-a8e2-a7d1602bd0f6',
                'description'      => 'Number of currently open connections',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            /* threadcache-hitrate */
            [
                'name'             => 'check_mysql_health__threadcache-hitrate',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode threadcache-hitrate --warning $ARG1$:101 --critical $ARG2$:102',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'f5db0b4e-2af7-4fb5-937f-124f6d798f2c',
                'description'      => 'Hit rate of the thread-cache',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* threads-created */
            [
                'name'             => 'check_mysql_health__threads-created',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode threads-created --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '33c569c2-8561-4d7a-83f2-ea017eb9f07c',
                'description'      => 'Number of threads created per second',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning - Number of created threads per second'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical - Number of created threads per second'
                    ]
                ]
            ],
            /* threads-running */
            [
                'name'             => 'check_mysql_health__threads-running',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode threads-running --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'c53847e7-2ca4-498e-8634-5eec26b12e01',
                'description'      => 'Number of currently running threads',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            /* threads-cached */
            [
                'name'             => 'check_mysql_health__threads-cached',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode threads-cached --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '6dd4f561-3e80-4b4a-a683-d418dacbea3b',
                'description'      => 'Number of currently cached threads',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            /* connects-aborted */
            [
                'name'             => 'check_mysql_health__connects-aborted',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode connects-aborted --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'd4b97b6f-7364-4717-b6d8-695cd2965ae8',
                'description'      => 'Number of aborted connections per second',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning - Number of aborted connects per second'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical - Number of aborted connects per second'
                    ]
                ]
            ],
            /* clients-aborted */
            [
                'name'             => 'check_mysql_health__clients-aborted',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode clients-aborted --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '117a2c52-8493-426f-bfcf-36a255f4eea4',
                'description'      => 'Number of aborted connections (because the client died) per second',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning - Number of aborted connects per second'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical - Number of aborted connects per second'
                    ]
                ]
            ],
            /* qcache-hitrate */
            [
                'name'             => 'check_mysql_health__qcache-hitrate',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode qcache-hitrate --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '45af9220-3666-422d-bf1a-9335cd958181',
                'description'      => 'Query cache hitrate',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* qcache-lowmem-prunes */
            [
                'name'             => 'check_mysql_health__qcache-lowmem-prunes',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode qcache-lowmem-prunes --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '60be76fa-1687-42b3-ae3a-679027177359',
                'description'      => 'Query cache entries pruned because of low memory',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            /* bufferpool-hitrate */
            [
                'name'             => 'check_mysql_health__bufferpool-hitrate',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode bufferpool-hitrate --warning $ARG1$:101 --critical $ARG2$:102',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '9b2d90f0-6c8c-44a6-b826-dd22a5ab02f5',
                'description'      => 'InnoDB buffer pool hitrate',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* bufferpool-wait-free */
            [
                'name'             => 'check_mysql_health__bufferpool-wait-free',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode bufferpool-wait-free --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '999ae421-6229-4197-9803-a88996905448',
                'description'      => 'InnoDB buffer pool waits for clean page available',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ],
            /* log-waits */
            [
                'name'             => 'check_mysql_health__log-waits',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode log-waits --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '016fca63-d656-457c-a556-68714bae8fa0',
                'description'      => 'InnoDB log waits because of a too small log buffer',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* tablecache-hitrate */
            [
                'name'             => 'check_mysql_health__tablecache-hitrate',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode tablecache-hitrate --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '430bd37b-1fba-4c62-aeed-cbda4275d7e0',
                'description'      => 'Table cache hitrate',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* table-lock-contention */
            [
                'name'             => 'check_mysql_health__table-lock-contention',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode table-lock-contention --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '2dc1ed45-2fcd-42c5-a6d1-91288d78f788',
                'description'      => 'Table lock contention',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* index-usage */
            [
                'name'             => 'check_mysql_health__index-usage',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode index-usage --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => 'a42e7d0d-ceba-42a3-8128-6764ce5cde8c',
                'description'      => 'Usage of indices',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* tmp-disk-tables */
            [
                'name'             => 'check_mysql_health__tmp-disk-tables',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode tmp-disk-tables --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '1e5b6d2f-503b-41dd-92ee-cfc9717d0dd3',
                'description'      => 'Percent of temp tables created on disk',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* open-files */
            [
                'name'             => 'check_mysql_health__open-files',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode open-files --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '2a08c744-6a1e-4281-bbbb-065665afb4ea',
                'description'      => 'Percent of opened files',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* slow-queries */
            [
                'name'             => 'check_mysql_health__slow-queries',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode slow-queries --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '822d6c2a-3475-45ca-9f77-d1a7be3a57be',
                'description'      => 'Slow queries',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning in %'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical in %'
                    ]
                ]
            ],
            /* long-running-procs */
            [
                'name'             => 'check_mysql_health__long-running-procs',
                'command_line'     => '$USER1$/check_mysql_health --hostname "$HOSTADDRESS$" --username "$_HOSTMYSQL_USER$" --password "$_HOSTMYSQL_PASSWORD$" --database "$_SERVICEMYSQL_DATABASE$" --mode long-running-procs --warning $ARG1$ --critical $ARG2$',
                'command_type'     => CHECK_COMMAND,
                'human_args'       => null,
                'uuid'             => '3b6d29ff-966e-49cf-9b1a-159d3139ac49',
                'description'      => 'Long running processes',
                'commandarguments' => [
                    [
                        'name'       => '$ARG1$',
                        'human_name' => 'Warning'
                    ],
                    [
                        'name'       => '$ARG2$',
                        'human_name' => 'Critical'
                    ]
                ]
            ]
        ];
        return $data;
    }

    public function getServicetemplatesData() {
        $data = [
            [
                'uuid'                                      => '976da093-ed3a-4fc2-978a-b8c37bb95a9d',
                'template_name'                             => 'MYSQL_CONNECTION_TIME',
                'name'                                      => 'MySQL Connection time',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Time to connect to the server',
                'command_id'                                => 'f503de41-54e2-40e9-9c26-fdfdb786a3c4',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '0.05',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '0.1',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => 'b471e570-2921-487d-8189-c4bbfa9a09d5',
                'template_name'                             => 'MYSQL_UPTIME',
                'name'                                      => 'MySQL Uptime',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Seconds since the server was started. We can use this to detect respawns.',
                'command_id'                                => 'b553d65d-b422-44c2-ab1e-d8922d41e8af',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '7542af3e-14e7-4a39-afff-6ae316313858',
                'template_name'                             => 'MYSQL_THREADS_CONNECTED',
                'name'                                      => 'MySQL Threads connected',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of clients currently connected. If none or too high, something is wrong.',
                'command_id'                                => '63317cd9-85d4-41d6-a8e2-a7d1602bd0f6',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '90',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '41b49614-c2f1-4bdd-a03c-ef7523b810dd',
                'template_name'                             => 'MYSQL_THREADCACHE_HITRATE',
                'name'                                      => 'MySQL Threadcache hitrate',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Hit rate of the thread-cache',
                'command_id'                                => 'f5db0b4e-2af7-4fb5-937f-124f6d798f2c',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '95',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '90',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '9f4ef558-847e-4953-8119-fedabffc29bc',
                'template_name'                             => 'MYSQL_THREADS_CREATED',
                'name'                                      => 'MySQL Threads created',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of threads created per second',
                'command_id'                                => '33c569c2-8561-4d7a-83f2-ea017eb9f07c',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '10',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '20',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '8c9a0fa8-2939-4aac-b252-f4f356313fe3',
                'template_name'                             => 'MYSQL_THREADS_RUNNING',
                'name'                                      => 'MySQL Threads running',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of currently running threads',
                'command_id'                                => 'c53847e7-2ca4-498e-8634-5eec26b12e01',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '10',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '20',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '46229355-cc9d-4179-a484-b3075a7861a0',
                'template_name'                             => 'MYSQL_THREADS_CACHED',
                'name'                                      => 'MySQL Threads cached',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of currently cached threads',
                'command_id'                                => '6dd4f561-3e80-4b4a-a683-d418dacbea3b',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '10',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '20',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '177ec9fe-6ec1-4bad-aa67-c20475bd8972',
                'template_name'                             => 'MYSQL_CONNECTS_ABORTED',
                'name'                                      => 'MySQL Connects aborted',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of aborted connections per second',
                'command_id'                                => 'd4b97b6f-7364-4717-b6d8-695cd2965ae8',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '5',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => 'fea34274-eaca-48af-b64b-6f247b29fa07',
                'template_name'                             => 'MYSQL_CLIENTS_ABORTED',
                'name'                                      => 'MySQL Clients aborted',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of failed connection attempts. When growing over a period of time either some credentials are wrong or we are being attacked.',
                'command_id'                                => '117a2c52-8493-426f-bfcf-36a255f4eea4',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '5',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => 'e9bddda3-9581-4472-b774-25c641219490',
                'template_name'                             => 'MYSQL_QCACHE_HITRATE',
                'name'                                      => 'MySQL Query cache hitrate',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '45af9220-3666-422d-bf1a-9335cd958181',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '80',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '3baceaba-3396-4400-a970-133c8e848eca',
                'template_name'                             => 'MYSQL_QCACHE_LOWMEM_PRUNES',
                'name'                                      => 'MySQL Qcache low memory prunes',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Query cache entries pruned because of low memory',
                'command_id'                                => '60be76fa-1687-42b3-ae3a-679027177359',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '10',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '18bfc587-97b3-4f45-9ab4-73245453dee1',
                'template_name'                             => 'MYSQL_BUFFERPOOL_HITRATE',
                'name'                                      => 'MySQL Bufferpool hitrate',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'InnoDB buffer pool hitrate',
                'command_id'                                => '9b2d90f0-6c8c-44a6-b826-dd22a5ab02f5',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '99',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '95',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '1e9eb002-aeb8-45b7-86a6-ea3f5f12d631',
                'template_name'                             => 'MYSQL_BUFFERPOOL_WAIT_FREE',
                'name'                                      => 'MySQL Bufferpool wait free',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'InnoDB buffer pool waits for clean page available',
                'command_id'                                => '999ae421-6229-4197-9803-a88996905448',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '10',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '421af7c7-2736-4c03-a239-64dc8c2e2cf7',
                'template_name'                             => 'MYSQL_LOG_WAITS',
                'name'                                      => 'MySQL Log waits',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'InnoDB log waits because of a too small log buffer',
                'command_id'                                => '016fca63-d656-457c-a556-68714bae8fa0',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '10',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '20a1e3b0-65b6-49af-8380-2bb55eedcc50',
                'template_name'                             => 'MYSQL_TABLECACHE_HITRATE',
                'name'                                      => 'MySQL Table cache hitrate',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '430bd37b-1fba-4c62-aeed-cbda4275d7e0',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '99',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '95',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '373e5fe2-22f9-4ebc-8055-a375ba4e951e',
                'template_name'                             => 'MYSQL_TABLE_LOCK_CONTENTION',
                'name'                                      => 'MySQL Table lock contention',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => '2dc1ed45-2fcd-42c5-a6d1-91288d78f788',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '2',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '5bae7650-0694-4a2f-bfce-b4bb63659ff3',
                'template_name'                             => 'MYSQL_INDEX_USAGE',
                'name'                                      => 'MySQL Usage of indices',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => '',
                'command_id'                                => 'a42e7d0d-ceba-42a3-8128-6764ce5cde8c',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '90',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '80',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => 'a0bce589-92c9-46a5-a44d-7b62efcfdd90',
                'template_name'                             => 'MYSQL_TMP_DISK_TABLES',
                'name'                                      => 'MySQL Temp disk tables',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of temporary tables (typically for joins) stored on slow spinning disks, instead of faster RAM.',
                'command_id'                                => '1e5b6d2f-503b-41dd-92ee-cfc9717d0dd3',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '25',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '50',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '6163bdce-5fc1-4328-8f50-c5e21e021c39',
                'template_name'                             => 'MYSQL_OPEN_FILES',
                'name'                                      => 'MySQL Open files',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Percent of opened files',
                'command_id'                                => '2a08c744-6a1e-4281-bbbb-065665afb4ea',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '80',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '95',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '8b0895be-bcc2-4699-baf4-9f69a40b00a2',
                'template_name'                             => 'MYSQL_SLOW_QUERIES',
                'name'                                      => 'MySQL Slow queries',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Number of queries that took more than long_query_time seconds to execute. Slow queries generate excessive disk reads, memory and CPU usage. Check slow_query_log to find them.',
                'command_id'                                => '822d6c2a-3475-45ca-9f77-d1a7be3a57be',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '0.1',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '1',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ],
            [
                'uuid'                                      => '2e64c98a-254d-4655-9ec1-7816a7c97cff',
                'template_name'                             => 'MYSQL_LONG_RUNNING_PROCESSES',
                'name'                                      => 'MySQL Long running processes',
                'container_id'                              => ROOT_CONTAINER,
                'servicetemplatetype_id'                    => GENERIC_SERVICE,
                'check_period_id'                           => '1',
                'notify_period_id'                          => '1',
                'description'                               => 'Excessive Number of Long Running Processes',
                'command_id'                                => '3b6d29ff-966e-49cf-9b1a-159d3139ac49',
                'check_command_args'                        => '',
                'checkcommand_info'                         => '',
                'eventhandler_command_id'                   => '0',
                'timeperiod_id'                             => '0',
                'check_interval'                            => '60',
                'retry_interval'                            => '60',
                'max_check_attempts'                        => '3',
                'first_notification_delay'                  => '0',
                'notification_interval'                     => '7200',
                'notify_on_warning'                         => '1',
                'notify_on_unknown'                         => '1',
                'notify_on_critical'                        => '1',
                'notify_on_recovery'                        => '1',
                'notify_on_flapping'                        => '0',
                'notify_on_downtime'                        => '0',
                'flap_detection_enabled'                    => '0',
                'flap_detection_on_ok'                      => '0',
                'flap_detection_on_warning'                 => '0',
                'flap_detection_on_unknown'                 => '0',
                'flap_detection_on_critical'                => '0',
                'low_flap_threshold'                        => '0',
                'high_flap_threshold'                       => '0',
                'process_performance_data'                  => '1',
                'freshness_checks_enabled'                  => '0',
                'freshness_threshold'                       => null,
                'passive_checks_enabled'                    => '1',
                'event_handler_enabled'                     => '0',
                'active_checks_enabled'                     => '1',
                'retain_status_information'                 => '0',
                'retain_nonstatus_information'              => '0',
                'notifications_enabled'                     => '1',
                'notes'                                     => '',
                'priority'                                  => '1',
                'tags'                                      => '',
                'service_url'                               => '',
                'is_volatile'                               => '0',
                'check_freshness'                           => '0',
                'servicetemplateeventcommandargumentvalues' => [],
                'servicetemplatecommandargumentvalues'      => [
                    [
                        'commandargument_id' => '$ARG1$',
                        'value'              => '10',
                    ],
                    [
                        'commandargument_id' => '$ARG2$',
                        'value'              => '20',
                    ]
                ],
                'customvariables'                           => [
                    [
                        'name'          => 'MYSQL_DATABASE',
                        'objecttype_id' => OBJECT_SERVICETEMPLATE,
                        'value'         => 'information_schema'
                    ]
                ],
                'servicegroups'                             => [],
                'contactgroups'                             => [],
                'contacts'                                  => []
            ]
        ];
        return $data;
    }

    /**
     * @return array
     */
    public function getData() {
        return [
            'Commands'         => $this->getCommandsData(),
            'Servicetemplates' => $this->getServicetemplatesData(),
            'Wizardassignment' => $this->getWizardAssignmentData()
        ];
    }
}
