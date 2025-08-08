<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class GolangAgentPush
 *
 * Created:
 * oitc migrations create GolangPushAgentIndex
 *
 * Usage:
 * openitcockpit-update
 */
class GolangPushAgentIndex extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $this->table('push_agents')
            ->addIndex(
                [
                    'agentconfig_id',
                    'uuid'
                ]
            )
            ->update();
    }
}
