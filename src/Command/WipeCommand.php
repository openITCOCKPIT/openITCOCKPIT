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

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Wipe command.
 */
class WipeCommand extends Command {

    private $verbose = false;

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

        $parser->addOption('recursive', ['short' => 'r', 'help' => 'Searching for files recursive', 'boolean' => true]);
        $parser->addOption('file', ['short' => 'f', 'help' => 'The file that should be wiped']);
        $parser->addOption('dir', ['short' => 'd', 'help' => 'The directory that should be wiped']);

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
        $io->out('<info>openITCOCKPIT file wiping shell</info>');

        $this->verbose = $args->getOption('verbose');

        if ($args->getOption('file')) {
            $this->wipeFile($io, $args->getOption('file'));
        }

        if ($args->getOption('dir')) {
            $this->wipeDir($io, $args->getOption('dir'), $args->getOption('recursive'));
        }
    }

    /**
     * @param ConsoleIo $io
     * @param string $file
     * @return bool
     */
    private function wipeFile(ConsoleIo $io, string $file) {
        if (!file_exists($file)) {
            $io->out('<error>File ' . $file . ' does not exists</error>');

            return false;
        }

        if ($this->verbose) {
            $io->out('<comment>Wiping file: ' . $file . '</comment>');
        }
        return fclose(fopen($file, 'w+'));
    }

    /**
     * @param ConsoleIo $io
     * @param string $dir
     * @param bool $recursive
     */
    private function wipeDir(ConsoleIo $io, string $dir, bool $recursive = true) {
        if (!is_dir($dir)) {
            $io->out('<error>' . $dir . ' is not a directory.</error>');
            return;
        }

        $Finder = new Finder();
        if (!$recursive) {
            $Finder->depth('== 0');
        }
        $Finder->in($dir);
        foreach ($Finder->files() as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isFile()) {
                $this->wipeFile($io, $fileInfo->getRealPath());
            }
        }

        return;
    }
}
