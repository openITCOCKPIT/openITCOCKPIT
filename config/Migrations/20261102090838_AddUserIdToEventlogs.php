<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserIdToEventlogs extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     *
     */
    public function change(): void
    {
        if ($this->hasTable('eventlogs')) {

            $table = $this->table('eventlogs');

            if (!$table->hasColumn('user_id')) {

                $table->addColumn('user_id', 'integer', [
                    'default' => null,
                    'limit' => 11,
                    'null' => true,
                    'after' => 'object_id',
                ]);
                $table->addIndex(['user_id'],
                    [
                        'name' => 'BY_USER_ID',
                        'unique' => false,
                    ]);
                $table->update();
            }
        }
    }
}
