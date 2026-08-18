<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddGroupTagsToStatuspagesTables extends BaseMigration
{
    private array $tableNames = [
        'statuspages_to_hosts',
        'statuspages_to_hostgroups',
        'statuspages_to_services',
        'statuspages_to_servicegroups',
    ];

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
        foreach ($this->tableNames as $tableName) {
            if (!$this->hasTable($tableName)) {
                continue;
            }

            $table = $this->table($tableName);

            if (!$table->hasColumn('group_tags')) {
                $table->addColumn('group_tags', 'string', [
                    'limit' => 255,
                    'null' => true,
                    'default' => null,
                ]);
                $table->update();
            }
        }
    }
}
