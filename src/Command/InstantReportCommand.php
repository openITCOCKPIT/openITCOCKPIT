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

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.

declare(strict_types=1);

namespace App\Command;

use App\itnovum\openITCOCKPIT\Core\Permissions\MyRightsFactory;
use App\itnovum\openITCOCKPIT\Core\Reports\InstantreportCreator;
use App\Model\Entity\Instantreport;
use App\Model\Entity\User;
use App\Model\Table\InstantreportsTable;
use App\Model\Table\SystemsettingsTable;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\Interfaces\CronjobInterface;
use itnovum\openITCOCKPIT\Core\KeyValueStore;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Core\Views\UserTime;
use PuppeteerPdf\Pdf\PdfRenderer;

/**
 * Class InstantReportCommand
 * @package App\Command
 */
class InstantReportCommand extends Command implements CronjobInterface {

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
        $io->setStyle('green', ['text' => 'green', 'blink' => false]);
        $io->setStyle('red', ['text' => 'red', 'blink' => false]);

        $io->out('Sending Instant Reports...');

        $UserPermissionsCache = new KeyValueStore();

        /** @var InstantreportsTable $InstantreportsTable */
        $InstantreportsTable = TableRegistry::getTableLocator()->get('Instantreports');
        $instanreports = $InstantreportsTable->find()
            ->contain('Users')
            ->where([
                'Instantreports.send_email' => 1
            ]);
        /** @var  $SystemsettingsTable SystemsettingsTable */
        $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
        $_systemsettings = $SystemsettingsTable->findAsArray();

        $Logo = new Logo();


        $toSend = false;
        $sendIntervals = [
            __('Never'),
            __('Daily'),
            __('Weekly'),
            __('Monthly'),
            __('Yearly')
        ];
        /** @var  $instantreport Instantreport */
        foreach ($instanreports as $instantreport) {
            try {
                $hasToBeSend = $instantreport->hasToBeSend();
                if ($hasToBeSend === false) {
                    continue;
                }
                $emailsToSend = [];
                foreach ($instantreport->get('users') as $user) {
                    $emailsToSend[] = $user->get('email');
                }
                if (empty($emailsToSend)) {
                    continue;
                }
                $instantreportId = $instantreport->get('id');
                $fromDate = $instantreport->reportStartTime();
                $toDate = $instantreport->reportEndTime();
                $instantreportName = $instantreport->get('name');
                $UserTime = new UserTime(date_default_timezone_get(), 'd.m.Y H:i:s');
                $instantReportCronPdfName = preg_replace('[^0-9a-zA-Z_\s]', '_', $instantreportName) . '.pdf';
                $subject = $sendIntervals[$instantreport->get('send_interval')] . ' Instant Report ' . $instantreportName;

                foreach ($instantreport->get('users') as $user) {
                    /** @var User $user */
                    if (!$UserPermissionsCache->has($user->get('id'))) {
                        $UserPermissionsCache->set(
                            $user->get('id'),
                            MyRightsFactory::getUserPermissions($user->get('id'), $user->get('usergroup_id'))
                        );
                    }

                    $userPermissions = $UserPermissionsCache->get($user->get('id'));

                    $InstantreportCreator = new InstantreportCreator(
                        $UserTime,
                        $userPermissions['MY_RIGHTS'],
                        $userPermissions['hasRootPrivileges']
                    );
                    $instantReport = $InstantreportCreator->createReport(
                        $instantreportId,
                        $fromDate,
                        $toDate
                    );

                    $PdfRenderer = new PdfRenderer();
                    $PdfRenderer->templatePath('Instantreports/pdf');
                    $PdfRenderer->template('create_pdf_report', 'default');
                    $PdfRenderer->viewVars([
                        'instantReport' => $instantReport,
                        'fromDate'      => $UserTime->format($fromDate),
                        'toDate'        => $UserTime->format($toDate),
                        'UserTime'      => $UserTime
                    ]);
                    // Get the PDF string returned
                    $pdf = $PdfRenderer->output();

                    //$file = fopen('/tmp/test.pdf', 'w+');
                    //fwrite($file, $pdf);
                    //fclose($file);

                    $Mailer = new Mailer();
                    $Mailer->setSubject($subject);
                    $Mailer->setFrom($_systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'], $_systemsettings['MONITORING']['MONITORING.FROM_NAME']);
                    $Mailer->setEmailFormat('both');
                    $Mailer->setTo($user->get('email'));
                    $Mailer->setAttachments([
                        'logo.png'                => [
                            'file'      => $Logo->getSmallLogoPdfPath(),
                            'mimetype'  => 'image/png',
                            'contentId' => '100'
                        ],
                        $instantReportCronPdfName => [
                            'data'     => $pdf,
                            'mimetype' => 'application/pdf'
                        ]
                    ]);
                    $Mailer->viewBuilder()
                        ->setTemplate('instantreport_mail')
                        ->setVar('instantReportName', $instantreportName);

                    $Mailer->deliver();
                }

                $instantreport->set('last_send_date', date('Y-m-d H:i:s'));
                $InstantreportsTable->save($instantreport);

                $io->success('Report "' . $instantreport->get('id') . '" sent to mail address "' . implode(', ', $emailsToSend) . '"');

            } catch (\Exception $ex) {
                $io->out('<red> ' . __('An error occured while sending instant report mail: ') . $ex->getMessage() . '</red>');
            }
        }
        if (!$toSend) {
            $io->success('No emails to send');
        }
        $this->cleanUp();
        $io->hr();
    }


    /**
     * @param $str
     * @return bool
     */
    private function cleanUp() {
        $savePath = APP . '/webroot/img/charts/';
        if (!is_dir($savePath)) {
            return false;
        }

        $files = [];
        foreach (new \DirectoryIterator($savePath) as $fileInfo) {
            if (!$fileInfo->isDot()) {
                $files[] = $fileInfo->getRealPath();
            }
        }
        if (empty($files)) {
            return true;
        }

        $Filesystem = new \Symfony\Component\Filesystem\Filesystem();
        $Filesystem->remove($files);
    }
}
