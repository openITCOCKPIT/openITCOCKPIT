<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class Colors
 *
 * Created via:
 * oitc migrations create -p GrafanaModule Colors
 *
 * Run migration:
 * oitc migrations migrate -p GrafanaModule
 *
 */
class Colors extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {

        $this->table('grafana_userdashboard_metrics')
            ->addColumn('color', 'string', [
                'default' => null,
                'limit'   => 255,
                'null'    => true,
                'after'   => 'service_id'
            ])
            ->update();

    }
}
