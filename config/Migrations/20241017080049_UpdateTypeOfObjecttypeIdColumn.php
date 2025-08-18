<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class UpdateTypeOfObjecttypeIdColumn
 *
 * Created:
 * oitc migrations create UpdateTypeOfObjecttypeIdColumn
 *
 * Usage:
 * openitcockpit-update
 */
class UpdateTypeOfObjecttypeIdColumn extends \Migrations\BaseMigration {
    public function up(): void {
        $this->table('changelogs')
            ->changeColumn('objecttype_id', 'biginteger', [
                'limit'  => 20,
                'signed' => false
            ])
            ->update();

        $this->table('customvariables')
            ->changeColumn('objecttype_id', 'biginteger', [
                'limit'  => 20,
                'signed' => false
            ])
            ->update();

        $this->table('systemdowntimes')
            ->changeColumn('objecttype_id', 'biginteger', [
                'limit'  => 20,
                'signed' => false
            ])
            ->update();
    }

    public function down(): void {
        $this->table('changelogs')
            ->changeColumn('objecttype_id', 'integer', [
                'limit'  => 11,
                'signed' => false
            ])
            ->update();

        $this->table('customvariables')
            ->changeColumn('objecttype_id', 'integer', [
                'limit'  => 11,
                'signed' => false
            ])
            ->update();

        $this->table('systemdowntimes')
            ->changeColumn('objecttype_id', 'integer', [
                'limit'  => 11,
                'signed' => false
            ])
            ->update();
    }
}
