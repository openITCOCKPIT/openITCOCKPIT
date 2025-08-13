<?php
// Copyright (C) <2015-present>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.


declare(strict_types=1);

namespace App\Command;

use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Rights command.
 */
class RightsCommand extends Command {

    /**
     * @var bool
     */
    private $modulesOnly = false;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);
        $parser->addOption('modules', [
            'help'    => 'Only set permissions to the modules folder',
            'default' => false,
            'boolean' => true
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        $this->modulesOnly = $args->getOption('modules');

        $directories = [
            '/opt/openitc/frontend/',
            '/opt/openitc/frontend-angular/',
            '/opt/openitc/frontend-modules/'
        ];
        $this->setRights($io, $directories);
    }

    /**
     * @param ConsoleIo $io
     * @param array $dirs
     */
    private function setRights(ConsoleIo $io, $dirs = []) {
        try {
            /** @var SystemsettingsTable $SystemsettingsTable */
            $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
            $systemsettings = $SystemsettingsTable->findAsArray();
            $user = $systemsettings['WEBSERVER']['WEBSERVER.USER'];
            $group = $systemsettings['WEBSERVER']['WEBSERVER.GROUP'];

            $fs = new Filesystem();
            foreach ($dirs as $dir) {
                if ($this->modulesOnly) {
                    if (!preg_match('/modules/', $dir)) {
                        $io->info('Skipping ' . $dir);
                        continue;
                    }
                }

                if ($fs->exists($dir)) {
                    $io->out(sprintf(
                        '<info>Set filesystem permissions for user %s to %s...    </info>',
                        $user,
                        $dir
                    ), 0);
                    $fs->chown($dir, $user, true);
                    $fs->chgrp($dir, $group, true);
                    $io->out('<success>done!</success>');
                }

            }
        } catch (IOExceptionInterface $e) {
            $io->out('<error>An error occurred at ' . $e->getPath() . ' </error>');
        }
    }
}
