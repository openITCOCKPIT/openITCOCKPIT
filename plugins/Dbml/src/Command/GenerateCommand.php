<?php
/**
 * Copyright (c) it-novum GmbH (https://it-novum.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) it-novum GmbH (https://it-novum.com)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Dbml\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Association;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Dbml\Dbml\DbmlAssociation;
use Dbml\Dbml\DbmlColumn;
use Dbml\Dbml\DbmlIndex;
use Dbml\Dbml\DbmlTable;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Generate command.
 */
class GenerateCommand extends Command {

    /**
     * Plugins which tables will be ignored during DBML schema generation
     * @var string[]
     */
    protected $blockedPlugins = [
        'DebugKit',
        'Migrations',
        //'Acl'
    ];

    /**
     * Tables which will be ignored during DBML schema generation
     * @var string[]
     */
    protected $blockedTables = [];

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser->addOption('se', [
            'help'    => 'Append Statusengine 3 table associations.',
            'boolean' => true,
            'default' => false
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $appendStatusengine = $args->getOption('se') === true;

        $this->io = $io;

        $tables = $this->getTables();

        // Used to filter for duplicates (AcknowledgementHostsTable and AcknowledgementServicesTable are both using the SQL table nagios_acknowledgements for example)
        $duplicates = [];

        $DBML = $this->getProjectDescription();
        $seenAssociations = [];
        foreach ($tables as $pluginName => $pluginTables) {
            foreach ($pluginTables as $table) {
                // Remove file extension
                $tableName = pathinfo($table->getFilename(), \PATHINFO_FILENAME);

                //Remove "Table" suffix
                $tableName = substr($tableName, 0, (strlen('Table') * -1));
                if ($pluginName !== 'APP') {
                    $tableName = $pluginName . '.' . $tableName;
                }

                if (in_array($tableName, $this->blockedTables, true)) {
                    continue;
                }

                $Table = TableRegistry::getTableLocator()->get($tableName);

                $driverName = get_class($Table->getConnection()->getDriver());
                try {
                    $isAlreadyDefined = false;
                    if (isset($duplicates[$Table->getTable()])) {
                        $isAlreadyDefined = true;
                    }
                    $duplicates[$Table->getTable()] = $Table->getTable();

                    $schema = $Table->getSchema();

                    $DbmlTable = new DbmlTable(
                        $schema->name(),
                        $isAlreadyDefined
                    );
                    $DbmlTable->setComment('CakePHP table Object: ' . $tableName);

                    $this->appendSchemaToTable($DbmlTable, $schema, $driverName);

                    /**
                     * CakePHP associations:
                     * - HasOne => 1:1
                     * - HasMany => 1:n
                     * - BelongsTo => n:1
                     * - BelongsToMany => n:n
                     *
                     * DBML example:
                     * - "> many-to-one", "< one-to-many", "- one-to-one"
                     * - Ref: users.usergroup_id > usergroups.id
                     */
                    foreach ($Table->associations() as $associationName => $association) {
                        $className = $association->getClassName();
                        if ($className === 'Permissions') {
                            $className = 'Acl.Permissions';
                        }

                        $AssocTable = TableRegistry::getTableLocator()->get($className);
                        $dedupKeys = $this->getAssocDedupKeys($Table, $association, $AssocTable);
                        if (isset($seenAssociations[$dedupKeys['association']]) || isset($seenAssociations[$dedupKeys['association_reverse']])) {
                            // Association already defined.
                            continue;
                        }

                        if ($dedupKeys['association'] !== '') {
                            $seenAssociations[$dedupKeys['association']] = true;
                        }
                        if ($dedupKeys['association_reverse'] !== '') {
                            $seenAssociations[$dedupKeys['association_reverse']] = true;
                        }

                        if ($association->type() == Association::ONE_TO_ONE) {
                            // HasOne
                            // DBML: -

                            /** @var Association\HasOne $association */
                            // usergroups.id ON aros.foreign_key
                            $DbmlAssociation = new DbmlAssociation(
                                $Table->getTable(), //usergroups
                                $AssocTable->getTable(), //aros
                                $association->getPrimaryKey(), //usergroups.id
                                $association->getForeignKey(), //aros.foreign_key
                                'one-to-one',
                                $association->getDependent()
                            );

                            $DbmlTable->addAssociation($DbmlAssociation);
                        }

                        if ($association->type() == Association::ONE_TO_MANY) {
                            // HasMany
                            // DBML: <

                            if (strtolower($Table->getAlias()) === "acos") {
                                //debug($association->getAlias());
                                //    debug($className);
                            }

                            /** @var Association\HasMany $association */

                            // containers.id ON tenants.container_id
                            $DbmlAssociation = new DbmlAssociation(
                                $Table->getTable(), // containers
                                $AssocTable->getTable(), // tenants
                                $association->getPrimaryKey(), // containers.id
                                $association->getForeignKey(), // tenants.container_id
                                'one-to-many',
                                $association->getDependent()
                            );

                            $DbmlTable->addAssociation($DbmlAssociation);
                        }

                        if ($association->type() == Association::MANY_TO_ONE) {
                            // BelongsTo
                            // DBML: >

                            /** @var Association\BelongsTo $association */
                            // services.servicetemplate_id ON servicetemplates.id
                            $DbmlAssociation = new DbmlAssociation(
                                $Table->getTable(), // services
                                $AssocTable->getTable(), // servicetemplates
                                $association->getForeignKey(), //services.servicetemplate_id
                                $association->getPrimaryKey(), // servicetemplates.id

                                'many-to-one',
                                $association->getDependent()
                            );

                            $DbmlTable->addAssociation($DbmlAssociation);
                        }

                        if ($association->type() == Association::MANY_TO_MANY) {
                            // BelongsToMany
                            /** @var Table $JoinTable */
                            $JoinTable = $association->junction();

                            $isJoinTableAlreadyDefined = false;
                            if (isset($duplicates[$JoinTable->getTable()])) {
                                $isJoinTableAlreadyDefined = true;
                            }
                            $duplicates[$JoinTable->getTable()] = $JoinTable->getTable();
                            $DbmlJunctionTable = new DbmlTable($JoinTable->getTable(), $isJoinTableAlreadyDefined);

                            $schema = $JoinTable->getSchema();
                            $driverName = get_class($JoinTable->getConnection()->getDriver());
                            $this->appendSchemaToTable($DbmlJunctionTable, $schema, $driverName);


                            // For some reason we do not have to define the associations.
                            foreach ($JoinTable->associations() as $junctionAssoc) {

                                $dedupKeys = $this->getJunctionDedupKeys($JoinTable, $junctionAssoc);
                                if (isset($seenAssociations[$dedupKeys['association']]) || isset($seenAssociations[$dedupKeys['association_reverse']])) {
                                    // Association already defined.
                                    continue;
                                }

                                if ($dedupKeys['association'] !== '') {
                                    $seenAssociations[$dedupKeys['association']] = true;
                                }

                                if (get_class($junctionAssoc) !== Association\BelongsTo::class) {
                                    throw new \Exception(sprintf(
                                        'Wrong association for junction table %s. Exacted BelongsTo got %s',
                                        $JoinTable->getTable(),
                                        get_class($junctionAssoc)
                                    ));
                                }

                                /** @var Association\BelongsTo $junctionAssoc */
                                // contactgroups_to_services
                                $DbmlJunctionAssociation = new DbmlAssociation(
                                    $JoinTable->getTable(), // contactgroups_to_services
                                    $junctionAssoc->getTable(), // services
                                    $junctionAssoc->getForeignKey(), // service_id
                                    $junctionAssoc->getPrimaryKey(), // services.id

                                    'many-to-one',
                                    $junctionAssoc->getDependent()
                                );

                                $DbmlJunctionTable->addAssociation($DbmlJunctionAssociation);
                            }

                            $DbmlTable->addJunctionTable($DbmlJunctionTable);

                        }
                    }
                    $DBML .= $DbmlTable->toDbml();
                } catch (\Exception $e) {
                    $this->io->error(sprintf('Current Table: "%s" - Error: %s', $tableName, $e->getMessage()));
                    $this->io->error('We ignore this for now and try to continue');
                    //debug($e->getTraceAsString());
                }
            }
        }

        if ($appendStatusengine === true) {
            $DBML .= $this->getStatusengine3Assocs();
        }

        $path = TMP;
        $filename = 'database.dbml';
        if (!is_writable($path)) {
            throw new \Exception('Filepath "' . $path . '" is not writable!');
        }

        $fullPath = $path . $filename;

        file_put_contents($fullPath, $DBML);
        $this->io->success(sprintf('DBML file written to %s', $fullPath));
    }


    /**
     * Return a List of available Table classes.
     *
     * For plugins, it is required that they are loaded by the Bootstrap (Application.php)
     *
     * @return array
     */
    protected function getTables(): array {
        $result = [
            'APP' => $this->getTablesFromFilesystem(),
        ];


        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            if (in_array($plugin, $this->blockedPlugins, true)) {
                continue;
            }

            $pluginTables = $this->getTablesFromFilesystem($plugin);
            foreach ($pluginTables as $pluginTable) {
                if (strpos($pluginTable->getFilename(), 'AppModel') !== false) {
                    continue;
                }

                $result[$plugin][] = $pluginTable;
            }
        }

        return $result;
    }


    /**
     * Scrape through the file system and get all Table Class files
     *
     * @return SplFileInfo[]
     */
    protected function getTablesFromFilesystem(?string $plugin = null): array {
        $paths = App::classPath('Model/Table', $plugin);
        foreach ($paths as $index => $path) {
            if (!is_dir($path)) {
                unset($paths[$index]);
            }
        }

        if (empty($paths)) {
            $this->io->out(sprintf('No Tables found for %s', $plugin ? $plugin : 'APP'), 1, ConsoleIo::VERBOSE);
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($paths)->name('/^(\w+)Table\.php$/');


        $tables = [];
        foreach ($finder as $file) {
            /** @var SplFileInfo $file */
            $tables[] = $file;
        }

        return $tables;
    }

    /**
     * @param string|array $delimiter
     * @param array|string $arr
     * @return string
     */
    private function implodeEvenStrings($delimiter, $arr) {
        if (is_string($arr)) {
            return $arr;
        }
        return implode($delimiter, $arr);
    }

    /**
     * Method to get association keys in both directions to not define association twice.
     * Otherwise the Hosts table would define an association to services
     * And the Services' table would define the same association to hosts (but as reverse)
     *
     * @param Table $Table
     * @param Association $Assoc
     * @param Table $AssocTable
     * @return array
     */
    protected function getAssocDedupKeys(Table $Table, Association $Assoc, Table $AssocTable) {
        if ($Assoc->type() == Association::ONE_TO_ONE || $Assoc->type() == Association::ONE_TO_MANY) {
            $associationString = sprintf('%s.%s|%s.%s',
                $Table->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getPrimaryKey()),
                $AssocTable->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getForeignKey())
            );
            $associationStringReverse = sprintf('%s.%s|%s.%s',
                $AssocTable->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getForeignKey()),
                $Table->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getPrimaryKey())
            );

            return [
                'association'         => $associationString,
                'association_reverse' => $associationStringReverse
            ];
        }

        if ($Assoc->type() == Association::MANY_TO_ONE) {
            $associationString = sprintf('%s.%s|%s.%s',
                $Table->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getForeignKey()),
                $AssocTable->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getPrimaryKey())
            );
            $associationStringReverse = sprintf('%s.%s|%s.%s',
                $AssocTable->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getPrimaryKey()),
                $Table->getTable(),
                $this->implodeEvenStrings(',', $Assoc->getForeignKey())
            );

            return [
                'association'         => $associationString,
                'association_reverse' => $associationStringReverse
            ];
        }

        if ($Assoc->type() == Association::MANY_TO_MANY) {
            return [
                'association'         => '',
                'association_reverse' => ''
            ];
        }
    }

    /**
     * @param DbmlTable $DbmlTable
     * @param TableSchemaInterface $schema
     * @param string $driverName
     * @return void
     * @throws \Exception
     */
    protected function appendSchemaToTable(DbmlTable &$DbmlTable, TableSchemaInterface $schema, string $driverName) {
        foreach ($schema->columns() as $columnName) {
            $column = $schema->getColumn($columnName);
            $primaryKeyFields = $schema->getPrimaryKey();

            $isPrimaryKey = in_array($columnName, $primaryKeyFields, true);
            $DbmlColumn = new DbmlColumn($columnName, $column, $isPrimaryKey, $driverName);

            $DbmlTable->addColumn($DbmlColumn);
        }

        foreach ($schema->constraints() as $constraintName) {
            $index = $schema->getConstraint($constraintName);
            if ($index['type'] === 'primary') {
                continue;
            }

            $DbmlIndex = new DbmlIndex($constraintName, $index['columns'], $index['type']);
            $DbmlTable->addIndex($DbmlIndex);
        }

        foreach ($schema->indexes() as $indexName) {
            $index = $schema->getIndex($indexName);
            $DbmlIndex = new DbmlIndex($indexName, $index['columns'], $index['type']);
            $DbmlTable->addIndex($DbmlIndex);
        }
    }

    protected function getJunctionDedupKeys(Table $JoinTable, Association $junctionAssoc) {
        $associationString = sprintf('%s.%s|%s.%s',
            $JoinTable->getTable(),
            $this->implodeEvenStrings(',', $junctionAssoc->getForeignKey()),
            $junctionAssoc->getTable(),
            $this->implodeEvenStrings(',', $junctionAssoc->getPrimaryKey())
        );
        $associationStringReverse = sprintf('%s.%s|%s.%s',
            $junctionAssoc->getTable(),
            $this->implodeEvenStrings(',', $junctionAssoc->getPrimaryKey()),
            $JoinTable->getTable(),
            $this->implodeEvenStrings(',', $junctionAssoc->getForeignKey())
        );

        return [
            'association'         => $associationString,
            'association_reverse' => $associationStringReverse
        ];
    }

    /**
     * The Statusengine 3 Associating are not defined in the code. Also, the schema will not change in the future
     * @return string
     */
    protected function getStatusengine3Assocs() {
        $dbml = PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_perfdata.service_description' . PHP_EOL;
        $dbml .= 'Ref: services.uuid - statusengine_servicestatus.service_description' . PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_servicechecks.service_description' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid - statusengine_hoststatus.hostname' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid < statusengine_host_statehistory.hostname' . PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_service_scheduleddowntimes.service_description' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid < statusengine_hostchecks.hostname' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid < statusengine_host_notifications.hostname' . PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_service_notifications.service_description' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid < statusengine_host_downtimehistory.hostname' . PHP_EOL;
        $dbml .= 'Ref: statusengine_host_downtimehistory.internal_downtime_id - statusengine_host_scheduleddowntimes.internal_downtime_id' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid < statusengine_host_scheduleddowntimes.hostname' . PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_service_downtimehistory.service_description' . PHP_EOL;
        $dbml .= 'Ref: statusengine_service_downtimehistory.internal_downtime_id - statusengine_service_scheduleddowntimes.internal_downtime_id' . PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_service_statehistory.service_description' . PHP_EOL;
        $dbml .= 'Ref: services.uuid < statusengine_service_acknowledgements.service_description' . PHP_EOL;
        $dbml .= 'Ref: hosts.uuid < statusengine_host_acknowledgements.hostname' . PHP_EOL;
        return $dbml;
    }

    /**
     * @return string
     */
    protected function getProjectDescription() {
        $dbml = 'Project openITCOCKPIT {' . PHP_EOL;
        $dbml .= '  database_type: \'MySQL\'' . PHP_EOL;
        $dbml .= '  Note: \'Database documentaion of the openITCOCKPIT Project https://openitcockpit.io\'' . PHP_EOL;
        $dbml .= '}' . PHP_EOL;
        return $dbml;
    }

}
