<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class UpdateAgentshostscache
 *
 * Created:
 * oitc migrations create UpdateAgentshostscache
 *
 * Usage:
 * openitcockpit-update
 */
class UpdateAgentshostscache extends \Migrations\BaseMigration {

    public function up(): void {
        $this->table('agenthostscache')
            ->changeColumn('checkdata', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_MEDIUM])
            ->update();
        $this->table('agentconfigs')
            ->addColumn('push_noticed', 'boolean', [
                'default' => false,
                'null'    => false,
                'after'   => 'password'
            ])
            ->update();
    }

    public function down(): void {
        $this->table('agenthostscache')
            ->changeColumn('checkdata', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_REGULAR])
            ->update();
        $this->table('agentconfigs')
            ->removeColumn('push_noticed')
            ->update();
    }
}
