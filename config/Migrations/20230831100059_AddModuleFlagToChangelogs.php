<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
//
// This file is licensed under the terms of the openITCOCKPIT Enterprise Edition license agreement.
// The license agreement and license key were sent with the order confirmation.

declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Class AddFlagsToImportedHosts
 *
 * Created:
 * oitc migrations create AddModuleFlagToChangelogs
 *
 * Usage:
 * openitcockpit-update
 */
class AddModuleFlagToChangelogs extends \Migrations\BaseMigration {
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $this->table('changelogs')
            ->addColumn('module_flag', 'integer', [
                'after'   => 'name',
                'default' => 0,
                'limit'   => 11,
                'null'    => false,
            ])
            ->update();
    }
}
