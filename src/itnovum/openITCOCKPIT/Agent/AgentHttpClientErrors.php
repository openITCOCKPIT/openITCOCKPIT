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

namespace itnovum\openITCOCKPIT\Agent;


class AgentHttpClientErrors {

    /**
     * No errors
     */
    const ERRNO_OK = 0;

    /**
     * Agent should only response to HTTPS requests because of sucessfull AutoTLS in the past
     * but the agent response to HTTP requests. So the cert file got delete on the Agent.
     */
    const ERRNO_AGENT_RESPONSES_TO_HTTP = 1 << 0;

    /**
     * The Agent is configured to only use HTTP
     */
    const ERRNO_AGENT_USES_ONLY_HTTP = 1 << 1;

    /**
     * AutoTLS certificates got exchanged successfully in the past but now the Cert from the oITC Server
     * mismatches the cert from the Agent. System mybe compromised
     */
    const ERRNO_HTTPS_COMPROMISED = 1 << 2;

    /**
     * Error while sending agent certificate to the Agent
     */
    const ERRNO_EXCHANGE_HTTPS_CERTIFICATE = 1 << 3;

    /**
     * No json response from agent or missing fields
     */
    const ERRNO_BAD_AGENT_RESPONSE = 1 << 4;

    /**
     * Unknown error
     */
    const ERRNO_UNKNOWN = 1 << 5;

    /**
     * Error while creating HTTPS connection
     */
    const ERRNO_HTTPS_ERROR = 1 << 6;

    /**
     * Error while creating insecure HTTP connection
     */
    const ERRNO_HTTP_ERROR = 1 << 7;
}
