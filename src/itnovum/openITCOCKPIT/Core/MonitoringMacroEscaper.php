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

namespace App\itnovum\openITCOCKPIT\Core;

/**
 * Class MonitoringMacroEscaper
 * ITC-3685
 * Will use php's escapeshellarg() to escape macros used in the monitoring configuration.
 * This can prevent command injection vulnerabilities if macros are used in check command definitions.
 *
 * Escaping can also break existing check command definitions there are multiple switches to control the escaping behavior:
 * - MONITORING.ESCAPE_HOSTADDRESS_IN_COMMANDLINE
 * - MONITORING.ESCAPE_NAMES_IN_COMMANDLINE
 * - MONITORING.ESCAPE_ARGUMENTS_IN_COMMANDLINE
 * - MONITORING.ESCAPE_MACROS_IN_COMMANDLINE
 *
 */
class MonitoringMacroEscaper {

    /**
     * Will escape $HOSTADDRESS$ macro if enabled
     * @var bool
     */
    private bool $escapeHostAddress = true;

    /**
     * Escape $HOSTDISPLAYNAME$, $HOSTALIAS$, $HOSTNOTES$, $SERVICEDISPLAYNAME$, $SERVICENOTES$ and $...ALIAS$
     * @var bool
     */
    private bool $escapeNames = true;

    /**
     * Escape $ARGn$
     * @var bool
     */
    private bool $escapeArguments = false;

    /**
     * Escape custom variables such as $_HOST...$, $_SERVICE...$ or $_CONTACT...$
     * @var bool
     */
    private bool $escapeMacros = false;

    public static function fromSystemsettings(array $systemsettings): self {
        return new self(
            $systemsettings['MONITORING.ESCAPE_HOSTADDRESS_IN_COMMANDLINE'] ?? true,
            $systemsettings['MONITORING.ESCAPE_NAMES_IN_COMMANDLINE'] ?? true,
            $systemsettings['MONITORING.ESCAPE_ARGUMENTS_IN_COMMANDLINE'] ?? false,
            $systemsettings['MONITORING.ESCAPE_MACROS_IN_COMMANDLINE'] ?? false,
        );
    }

    public function __construct(bool $escapeHostAddress = true, bool $escapeNames = true, bool $escapeArguments = false, bool $escapeMacros = false) {
        $this->escapeHostAddress = $escapeHostAddress;
        $this->escapeNames = $escapeNames;
        $this->escapeArguments = $escapeArguments;
        $this->escapeMacros = $escapeMacros;
    }

    public function escapeHostAddress(): bool {
        return $this->escapeHostAddress;
    }

    public function escapeNames(): bool {
        return $this->escapeNames;
    }

    public function escapeArguments(): bool {
        return $this->escapeArguments;
    }

    public function escapeMacros(): bool {
        return $this->escapeMacros;
    }

    /**
     * Depending on the settings, this method will escape the given value for the given macro name using php's escapeshellarg() function.
     *
     * @param $macroName
     * @param $value
     * @return string
     */
    public function escape($macroName, $value): string {
        if ($this->escapeHostAddress && $macroName === '$HOSTADDRESS$') {
            return escapeshellarg($value);
        }

        $nameMacros = [
            '$HOSTDISPLAYNAME$',
            '$SERVICEDISPLAYNAME$',
            '$HOSTNOTES$',
            '$SERVICENOTES$',
            '$HOSTGROUPALIAS$',
            '$SERVICEGROUPALIAS$',
            '$CONTACTALIAS$',
            '$CONTACTGROUPALIAS$'
        ];
        if ($this->escapeNames && in_array($macroName, $nameMacros, true)) {
            // Escape name related macros
            return escapeshellarg($value);
        }

        if ($this->escapeArguments && str_starts_with($macroName, '$ARG') && str_ends_with($macroName, '$')) {
            // Escape $ARGn$ macros
            return $this->escapeShell($value);
        }

        if ($this->escapeMacros && str_starts_with($macroName, '$_') && str_ends_with($macroName, '$')) {
            // Escape custom variables such as $_HOST...$, $_SERVICE...$ or $_CONTACT...$
            return escapeshellarg($value);
        }

        // Do not escape if not cached above
        return $value;
    }

    /**
     * Escapes dangerous shell characters in a string for bash/sh using a state machine.
     * Only escapes characters outside quotes or inside double quotes.
     * Characters inside single quotes are not escaped.
     *
     * @param string $value
     * @return string
     */
    public function escapeShell(string $value): string {

    }

}
