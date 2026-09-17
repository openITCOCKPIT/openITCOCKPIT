<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class PushNotificationsRelay extends BaseMigration
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
        if (!$this->hasTable('push_notifications_relay')) {
            $this->table('push_notifications_relay')
                ->addColumn('address', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('port', 'integer', [
                    'default' => null,
                    'limit'   => 5,
                    'null'    => false,
                ])
                ->addColumn('auth_key', 'string', [
                    'default' => null,
                    'limit'   => 255,
                    'null'    => false,
                ])
                ->addColumn('enabled', 'boolean', [
                    'default' => true,
                    'limit'   => null,
                    'null'    => false,
                ])
                ->create();
        }
    }
}
