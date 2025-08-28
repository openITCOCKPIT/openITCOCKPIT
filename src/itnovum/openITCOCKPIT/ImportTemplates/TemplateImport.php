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

namespace App\itnovum\openITCOCKPIT\ImportTemplates;

use App\Model\Table\CommandsTable;
use App\Model\Table\HosttemplatesTable;
use App\Model\Table\MacrosTable;
use App\Model\Table\ServicetemplategroupsTable;
use App\Model\Table\ServicetemplatesTable;

/**
 * Class TemplateImport
 * @package App\itnovum\openITCOCKPIT\ImportTemplates
 * @TODO backward compatibility - rename old templates with "_LEGACY" suffix -> only commands affected
 */
class TemplateImport {

    /**
     * @var array
     */
    private $mapping = [];

    /**
     * @var CommandsTable
     */
    private $CommandsTable;

    /**
     * @var MacrosTable
     */
    private $MacrosTable;

    /**
     * @var ServicetemplatesTable
     */
    private $ServicetemplatesTable;

    /**
     * @var HosttemplatesTable
     */
    private $HosttemplatesTable;

    /**
     * @var ServicetemplategroupsTable
     */
    private $ServicetemplategroupsTable;

    /**
     * TemplateImport constructor.
     * @param CommandsTable $CommandsTable
     * @param MacrosTable $MacrosTable
     * @param ServicetemplatesTable $ServicetemplatesTable
     * @param HosttemplatesTable $HosttemplatesTable
     * @param ServicetemplategroupsTable $ServicetemplategroupsTable
     */
    public function __construct(CommandsTable $CommandsTable, MacrosTable $MacrosTable, ServicetemplatesTable $ServicetemplatesTable, HosttemplatesTable $HosttemplatesTable, ServicetemplategroupsTable $ServicetemplategroupsTable) {
        $this->CommandsTable = $CommandsTable;
        $this->MacrosTable = $MacrosTable;
        $this->ServicetemplatesTable = $ServicetemplatesTable;
        $this->HosttemplatesTable = $HosttemplatesTable;
        $this->ServicetemplategroupsTable = $ServicetemplategroupsTable;
    }

    /**
     * @param $command_line
     * @return string
     */
    private function replaceMacroNames($command_line): string {
        $commandLineParts = explode(' ', $command_line);
        $newCommandLineParts = [];
        foreach ($commandLineParts as $commandLinePart) {
            if (strstr($commandLinePart, '$USER')) {
                foreach ($this->mapping['macros'] as $oldName => $newName) {
                    if (strstr($commandLinePart, $oldName)) {
                        $newCommandLineParts[] = str_replace($oldName, $newName, $commandLinePart);
                        continue 2;
                    }
                }
            }
            $newCommandLineParts[] = $commandLinePart;
        }
        return implode(' ', $newCommandLineParts);
    }

    /**
     * @param string $name
     * @param array $commandarguments
     * @return false|mixed
     */
    private function getCommandArgumentIdByName(string $name, array $commandarguments) {
        foreach ($commandarguments as $commandargument) {
            if (isset($commandargument['name']) && $commandargument['name'] === $name) {
                return $commandargument['id'];
            }
        }
        return false;
    }

    /**
     * BACKWARD COMPATIBILITY METHOD
     * command names must be unique so we need to check if the name is already in use
     * uuid is not in the database - this has been checked by the importCommands() method
     * in case of positive match (name already in use) rename it with "_legacy" suffix
     * @param array $command
     */
    private function checkCommandNames($command): void {
        $commandName = $command['name'];

        // Check if an command with the same name already exists
        $existingCommand = $this->CommandsTable->getCommandByName($commandName);
        if (empty($existingCommand)) {
            // No command with the same name - nothing todo
            return;
        }

        // Command with the same name exists
        $commandEntityToRename = $this->CommandsTable->get($existingCommand['Command'][0]['id']);
        $retry = 1;
        while (!empty($existingCommand)) {
            if ($retry === 1) {
                $newCommandName = sprintf('%s (before %s)', $commandName, OPENITCOCKPIT_VERSION);
            } else {
                $newCommandName = sprintf('%s (%s)', $commandName, $retry);
            }

            $existingCommand = $this->CommandsTable->getCommandByName($newCommandName);
            $retry++;
        }

        // New name found
        $commandEntityToRename->name = $newCommandName;
        $this->CommandsTable->save($commandEntityToRename);
    }

    /**
     * @param $data
     */
    public function import($data): void {

        $this->mapping['macros'] = [];
        if (!empty($data['Macros'])) {
            $this->importMacros($data['Macros']);
        }

        if (!empty($data['Commands'])) {
            $this->importCommands($data['Commands']);
        }

        if (!empty($data['Servicetemplates'])) {
            $this->importServicetemplates($data['Servicetemplates']);
        }

        if (!empty($data['Servicetemplategroups'])) {
            $this->importServicetemplategroups($data['Servicetemplategroups']);
        }

        if (!empty($data['Hosttemplates'])) {
            $this->importHosttemplates($data['Hosttemplates']);
        }
    }

    /**
     * @param $macros
     */
    private function importMacros($macros): void {
        foreach ($macros as $macro) {
            $existingMacro = $this->MacrosTable->find()
                ->select(['id'])
                ->where([
                    'value'       => $macro['value'],
                    'description' => $macro['description'],
                ])
                ->first();

            if (!empty($existingMacro)) {
                $this->mapping['macros'][$macro['name']] = $existingMacro->get('name');
                continue;
            }
            $availableMacroNames = $this->MacrosTable->getAvailableMacroNames();
            if (!empty($availableMacroNames)) {
                $newMacroName = array_key_first($availableMacroNames);
                $this->mapping['macros'][$macro['name']] = $newMacroName;
                $macro['name'] = $newMacroName;
                $entity = $this->MacrosTable->newEntity($macro);
                $this->MacrosTable->save($entity);
            }
            //no macro names available anymore
        }
    }

    /**
     * @param $commands
     */
    private function importCommands($commands): void {
        foreach ($commands as $command) {
            if ($this->CommandsTable->existsByUuid($command['uuid'])) {
                //skip command if its already exists
                continue;
            }

            //backward compatibilty method
            $this->checkCommandNames($command);

            $command['command_line'] = $this->replaceMacroNames($command['command_line']);
            $entity = $this->CommandsTable->newEntity($command);
            $this->CommandsTable->save($entity);

        }
    }

    /**
     * @param $servicetemplates
     */
    private function importServicetemplates($servicetemplates): void {
        foreach ($servicetemplates as $servicetemplate) {

            if ($this->ServicetemplatesTable->existsByUuid($servicetemplate['uuid'])) {
                //skip current template if it already exists
                continue;
            }

            if (!$this->CommandsTable->existsByUuid($servicetemplate['command_id'])) {
                //skip template if command does not exist
                continue;
            }

            $command = $this->CommandsTable->getCommandByUuid($servicetemplate['command_id'], true, false)[0];
            $servicetemplate['command_id'] = $command['id'];

            if (!empty($servicetemplate['servicetemplatecommandargumentvalues']) && !empty($command['commandarguments'])) {
                foreach ($servicetemplate['servicetemplatecommandargumentvalues'] as $templateArgumentKey => $templateArgumentValue) {
                    $servicetemplate['servicetemplatecommandargumentvalues'][$templateArgumentKey] = [
                        'commandargument_id' => $this->getCommandArgumentIdByName($templateArgumentValue['commandargument_id'], $command['commandarguments']),
                        'value'              => $templateArgumentValue['value'],
                    ];
                }
            }
            $entity = $this->ServicetemplatesTable->newEntity($servicetemplate);
            $this->ServicetemplatesTable->save($entity);
        }
    }

    /**
     * @param $servicetemplategroups
     */
    private function importServicetemplategroups($servicetemplategroups): void {
        foreach ($servicetemplategroups as $servicetemplategroup) {
            if ($this->ServicetemplategroupsTable->existsByUuid($servicetemplategroup['uuid'])) {
                //skip current template if its already exists
                continue;
            }

            $servicetemplates = [];
            foreach ($servicetemplategroup['servicetemplates'] as $key => $servicetemplate) {
                if (!$this->ServicetemplatesTable->existsByUuid($servicetemplate['id'])) {
                    //delete missing servictemplate from group
                    unset($servicetemplategroup['servicetemplates'][$key]);
                    continue;
                }
                //replace servicetemplate id
                $servicetemplate = $this->ServicetemplatesTable->getServicetemplateByUuid($servicetemplate['id']);
                $servicetemplategroup['servicetemplates'][$key]['id'] = $servicetemplate['Servicetemplate']['id'];
                $servicetemplates[] = $servicetemplate['Servicetemplate']['id'];
            }
            $servicetemplategroup['servicetemplates']['_ids'] = $servicetemplates;
            $entity = $this->ServicetemplategroupsTable->newEntity($servicetemplategroup);
            $this->ServicetemplategroupsTable->save($entity);
        }
    }


    /**
     * @param $hosttemplates
     */
    private function importHosttemplates($hosttemplates): void {
        foreach ($hosttemplates as $hosttemplate) {

            if ($this->HosttemplatesTable->existsByUuid($hosttemplate['uuid'])) {
                //skip current template if it already exists
                continue;
            }

            if (!$this->CommandsTable->existsByUuid($hosttemplate['command_id'])) {
                //skip template if command does not exist
                continue;
            }

            $command = $this->CommandsTable->getCommandByUuid($hosttemplate['command_id'], true, false)[0];
            $hosttemplate['command_id'] = $command['id'];

            if (!empty($hosttemplate['hosttemplatecommandargumentvalues']) && !empty($command['commandarguments'])) {
                foreach ($hosttemplate['hosttemplatecommandargumentvalues'] as $templateArgumentKey => $templateArgumentValue) {
                    $hosttemplate['hosttemplatecommandargumentvalues'][$templateArgumentKey] = [
                        'commandargument_id' => $this->getCommandArgumentIdByName($templateArgumentValue['commandargument_id'], $command['commandarguments']),
                        'value'              => $templateArgumentValue['value'],
                    ];
                }
            }
            $entity = $this->HosttemplatesTable->newEntity($hosttemplate);
            $this->HosttemplatesTable->save($entity);
        }
    }
}
