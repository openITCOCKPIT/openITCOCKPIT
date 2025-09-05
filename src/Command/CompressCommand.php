<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

declare(strict_types=1);

namespace App\Command;

use App\Lib\PluginManager;
use Cake\Console\Arguments;
use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use itnovum\openITCOCKPIT\Core\AngularJS\AngularAssets;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Compress command.
 */
class CompressCommand extends Command {
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
        $io->out('Copy node_modules...    ', 0);
        $this->copyNodeModules();
        $io->success('done');

        //css files
        $io->out('Compress openITCOCKPIT CSS files...    ', 0);
        $appCssFiles = $this->fetchAllCssFiles();
        $this->compressFiles($appCssFiles, 'compressed_app.css');
        $this->minifyCssFile('compressed_app.css');
        $io->success('done');
        unset($appCssFiles);

        //angular controllers
        $io->out('Compress Angular controllers...    ', 0);
        $angularControllers = $this->fetchAllAngularControllers();
        $this->compressFiles($angularControllers, 'compressed_angular_controllers.js');
        $this->minifyJsFile('compressed_angular_controllers.js');
        $io->success('done');
        unset($angularControllers);

        //angular directives
        $io->out('Compress Angular directives...    ', 0);
        $angularDirectives = $this->fetchAllAngularDirectives();
        $this->compressFiles($angularDirectives, 'compressed_angular_directives.js');
        $this->minifyJsFile('compressed_angular_directives.js');
        $io->success(' done');
        unset($angularDirectives);

        //angular services
        $io->out('Compress Angular services...    ', 0);
        $angularServices = $this->fetchAllAngularServices();
        $this->compressFiles($angularServices, 'compressed_angular_services.js');
        $this->minifyJsFile('compressed_angular_services.js');
        $io->success('done');
        unset($angularServices);

        //angular statess
        $io->out('Compress Angular states...    ', 0);
        $angularStates = $this->fetchAllAngularStates();
        $this->compressFiles($angularStates, 'compressed_angular_states.js');
        $this->minifyJsFile('compressed_angular_states.js');
        $io->success('done');
        unset($angularStates);
    }

    public function fetchAllAngularControllers() {
        return $this->fetchAllAngularFiles('controllers');
    }

    public function fetchAllAngularDirectives() {
        return $this->fetchAllAngularFiles('directives');
    }

    public function fetchAllAngularServices() {
        return $this->fetchAllAngularFiles('services');
    }

    public function fetchAllAngularStates() {
        $AngularAssets = new AngularAssets();
        return $AngularAssets->getPluginNgStateJsFiles();
    }

    private function fetchAllAngularFiles(string $subDir) {
        $Finder = new Finder();
        $Finder
            ->name('*.js')
            ->ignoreDotFiles(true)
            ->files();

        $allFiles = [];

        foreach ($Finder->in(WWW_ROOT . 'js' . DS . 'scripts' . DS . $subDir) as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getPath();
            $filename = $file->getBasename();
            $allFiles[] = $path . DS . $filename;
        }

        foreach (PluginManager::getAvailablePlugins() as $pluginName) {
            $dir = PLUGIN . $pluginName . DS . 'webroot' . DS . 'js' . DS . 'scripts' . DS . $subDir;
            if (!is_dir($dir)) {
                continue;
            }

            $Finder = new Finder();
            $Finder
                ->name('*.js')
                ->ignoreDotFiles(true)
                ->files();

            foreach ($Finder->in($dir) as $file) {
                /** @var SplFileInfo $file */
                $path = $file->getPath();
                $filename = $file->getBasename();
                $allFiles[] = $path . DS . $filename;
            }

        }

        return $allFiles;
    }

    /**
     * @return array
     */
    private function fetchAllCssFiles() {
        $AngularAssets = new AngularAssets();
        return $AngularAssets->getCssFilesOnDisk();
    }

    public function compressFiles($files, $outFileName) {
        $outFile = fopen(WWW_ROOT . 'dist' . DS . $outFileName, 'w+');

        foreach ($files as $file) {
            if (file_exists($file)) {
                //Remove strict because of js issue:
                //Uncaught SyntaxError: Octal literals are not allowed in strict mode.
                //Not all JS files are strict compatible
                $content = str_replace(["'use strict';", '"use strict";'], '', file_get_contents($file));
                fwrite($outFile, $content);
                fwrite($outFile, PHP_EOL);
            }
        }

        fclose($outFile);
    }

    public function concatFiles($files, $outFileName) {
        $outFile = fopen(WWW_ROOT . 'dist' . DS . $outFileName, 'w+');

        foreach ($files as $file) {
            if (file_exists($file)) {
                fwrite($outFile, file_get_contents($file));
                fwrite($outFile, PHP_EOL);
            }
        }

        fclose($outFile);
    }

    public function minifyJsFile($fileName) {
        $minifier = new JS(WWW_ROOT . 'dist' . DS . $fileName);

        $file = fopen(WWW_ROOT . 'dist' . DS . $fileName, 'w+');
        fwrite($file, $minifier->minify());
        fclose($file);
    }

    public function minifyCssFile($fileName) {
        $minifier = new CSS(WWW_ROOT . 'dist' . DS . $fileName);

        $file = fopen(WWW_ROOT . 'dist' . DS . $fileName, 'w+');
        fwrite($file, $minifier->minify());
        fclose($file);
    }

    public function copyNodeModules() {
        $outputDir = WWW_ROOT . 'node_modules';
        if (!is_dir($outputDir)) {
            mkdir($outputDir);
        }

        $Filesystem = new Filesystem();

        //Delete all old files
        $Filesystem->remove($outputDir);

        $AngularAssets = new AngularAssets();
        foreach ($AngularAssets->getNodeJsFiles() as $nodeModuleJsFile) {
            $sourcePath = dirname($nodeModuleJsFile);
            $sourceDirName = basename($sourcePath);

            //Create directory //Example: WWW_ROOT . node_modules/popper.js/dist/umd
            $Filesystem->mkdir(WWW_ROOT . $sourcePath);

            //Copy files
            $Filesystem->mirror(ROOT . DS . $sourcePath, WWW_ROOT . $sourcePath);
        }

        foreach ($AngularAssets->getNodeCssFiles() as $nodeModuleCssFile) {
            $sourcePath = dirname($nodeModuleCssFile);
            $sourceDirName = basename($sourcePath);

            //Create directory //Example: WWW_ROOT . node_modules/popper.js/dist/umd
            $Filesystem->mkdir(WWW_ROOT . $sourcePath);

            //Copy files
            $Filesystem->mirror(ROOT . DS . $sourcePath, WWW_ROOT . $sourcePath);
        }

        //Copy FontAwesome 5.x
        $Filesystem->mirror(ROOT . DS . 'node_modules/@fortawesome/fontawesome-free', WWW_ROOT . 'node_modules/@fortawesome/fontawesome-free');

        //Copy FontAwesome 4.7.x
        $Filesystem->mirror(ROOT . DS . 'node_modules/font-awesome', WWW_ROOT . 'node_modules/font-awesome');

        //Copy Bootstrap fr Login Screen
        $Filesystem->mirror(ROOT . DS . 'node_modules/bootstrap/dist/css', WWW_ROOT . 'node_modules/bootstrap/dist/css');

        //Copy Flag svgs
        $Filesystem->mirror(ROOT . DS . 'node_modules/flag-icon-css/flags', WWW_ROOT . 'node_modules/flag-icon-css/flags');

        //Remove al .html files
        $Finder = new Finder();
        $Finder
            ->files()
            ->name('*.html')
            ->ignoreDotFiles(true);

        foreach ($Finder->in($outputDir) as $file) {
            /** @var SplFileInfo $file */
            $Filesystem->remove($file->getRealPath());
        }
    }

}
