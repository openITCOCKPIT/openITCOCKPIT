<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class Agentconnector
 *
 * Created:
 * oitc migrations create Agentconnector
 *
 * Usage:
 * openitcockpit-update
 */
class Agentconnector extends \Migrations\BaseMigration {

    public function up(): void {
        if (!$this->hasTable('agentconnector')) {
            $this->table('agentconnector')
                ->addColumn('hostuuid', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('checksum', 'blob', [
                    'default' => null,
                    'null'    => true,
                ])
                ->addColumn('ca_checksum', 'blob', [
                    'default' => null,
                    'null'    => true,
                ])
                ->addColumn('generation_date', 'datetime', [
                    'default' => null,
                    'null'    => true,
                ])
                ->addColumn('remote_addr', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('http_x_forwarded_for', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => true,
                ])
                ->addColumn('trusted', 'boolean', [
                    'default' => false,
                    'null'    => false,
                ])
                ->create();
        }

        if (!$this->hasTable('agenthostscache')) {
            $this->table('agenthostscache')
                ->addColumn('hostuuid', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('checkdata', 'text', [
                    'default' => null,
                    'null'    => true,
                ])
                ->addColumn('modified', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->addColumn('created', 'datetime', [
                    'default' => null,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }
    }

    public function down(): void {
        if ($this->hasTable('agentconnector')) {
            $this->table('agentconnector')->drop()->save();
        }
        if ($this->hasTable('agenthostscache')) {
            $this->table('agenthostscache')->drop()->save();
        }
    }
}
