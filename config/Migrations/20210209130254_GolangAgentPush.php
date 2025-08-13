<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class GolangAgentPush
 *
 * Created:
 * oitc migrations create GolangAgentPush
 *
 * Usage:
 * openitcockpit-update
 */
class GolangAgentPush extends \Migrations\BaseMigration {

    /**
     * Whether the tables created in this migration
     * should auto-create an `id` field or not
     *
     * This option is global for all tables created in the migration file.
     * If you set it to false, you have to manually add the primary keys for your
     * tables using the Migrations\Table::addPrimaryKey() method
     *
     * @var bool
     */
    public bool $autoId = false;

    public function up(): void {
        if ($this->hasTable('agentconnector')) {
            $this->table('agentconnector')->drop()->save();
        }
        if ($this->hasTable('agenthostscache')) {
            $this->table('agenthostscache')->drop()->save();
        }

        if (!$this->hasTable('push_agents')) {
            $this->table('push_agents')
                ->addColumn('id', 'integer', [
                    'autoIncrement' => true,
                    'default'       => null,
                    'limit'         => 11,
                    'null'          => false,
                ])
                ->addPrimaryKey(['id'])
                ->addColumn('uuid', 'string', [
                    'default' => null,
                    'limit'   => 37,
                    'null'    => false,
                ])
                ->addColumn('agentconfig_id', 'string', [
                    'default' => null,
                    'null'    => true,
                ])
                ->addColumn('password', 'string', [
                    'limit' => 255,
                    'null'  => false,
                ])
                ->addColumn('hostname', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('ipaddress', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('remote_address', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('http_x_forwarded_for', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('checkresults', 'text', [
                    'default' => null,
                    'null'    => true,
                    'limit'   => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM
                ])
                ->addColumn('last_update', 'datetime', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addIndex(
                    [
                        'uuid',
                    ],
                    ['unique' => true]
                )
                ->addIndex(
                    [
                        'uuid',
                        'agentconfig_id'
                    ]
                )
                ->create();
        }

    }

    public function down(): void {
        if ($this->hasTable('push_agents')) {
            $this->table('push_agents')->drop()->save();
        }
    }

}
