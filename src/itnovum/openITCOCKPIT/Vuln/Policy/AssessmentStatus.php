<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today AVENDIS GmbH
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

namespace itnovum\openITCOCKPIT\Vuln\Policy;

/**
 * Status of the CVE assessment of a host as shown in the host list (not the monitoring state of the host).
 * Together with the color the frontend derives the text,
 * e.g. Evaluated and red is "urgent", Evaluated and green is "no priority" (never "secure").
 */
enum AssessmentStatus: string {

    /** Evaluated with current data */
    case Evaluated = 'evaluated';

    /** Evaluated with current data, but not everything could be checked */
    case Partial = 'partial';

    /** The inventory or the Vuls database is too old. The findings stay visible, the color turns gray */
    case Stale = 'stale';

    /** The last evaluation failed, the last successful result stays visible */
    case Failed = 'failed';

    /** Nothing could be evaluated */
    case NotEvaluated = 'not_evaluated';
}
