<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class UpdateNotificationsEnabled extends \Migrations\BaseMigration {
    /**
     * Change Method.
     * Class UpdateNotificationsEnabled
     *
     * Created:
     * oitc migrations create UpdateNotificationsEnabled
     *
     * Usage:
     *openitcockpit-update
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void {
        $this->execute('UPDATE hosttemplates SET hosttemplates.notifications_enabled = 1');
        $this->execute('UPDATE servicetemplates SET servicetemplates.notifications_enabled = 1');
    }
}
