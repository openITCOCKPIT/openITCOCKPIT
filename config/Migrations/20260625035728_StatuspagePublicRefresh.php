<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class StatuspagePublicRefresh extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        if ($this->hasTable('statuspages')) {
            $this->table('statuspages')
                ->addColumn('public_refresh', 'integer', [
                    'after'   => 'public_identifier',
                    'default' => 60,
                    'limit'   => 11,
                    'null'    => false,
                ])
                ->update();
        }
    }
}
