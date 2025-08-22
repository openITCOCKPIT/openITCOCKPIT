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

namespace itnovum\openITCOCKPIT\Monitoring;

/**
 * Class DefaultMacros
 * @package itnovum\openITCOCKPIT\Monitoring
 */
class DefaultMacros {

    /**
     * This list is NOT copy and paste from
     * https://www.naemon.io/documentation/usersguide/macrolist.html
     * or
     * https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/3/en/macrolist.html
     *
     * Due to licensing issues:
     * https://github.com/naemon/naemon.github.io/issues/103
     *
     * we wrote all descriptions by our self.
     *
     * @return array
     */
    public static function getMacros() {
        return [
            [
                'category' => __('Host Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$HOSTNAME$',
                        'description' => __('The UUID of the host ("1284b927-2bd2-4737-a3da-c39854376ca2")')
                    ],
                    [
                        'macro'       => '$HOSTDISPLAYNAME$',
                        'description' => __('The human name of the host ("srvstorage01")')
                    ],
                    [
                        'macro'       => '$HOSTALIAS$',
                        'description' => __('The human name of the host ("srvstorage01"), same as $HOSTDISPLAYNAME$')
                    ],
                    [
                        'macro'       => '$HOSTADDRESS$',
                        'description' => __('Address or FQDN of the host. ("10.10.12.12")')
                    ],
                    [
                        'macro'       => '$HOSTSTATE$',
                        'description' => __('Current host state as string ("UP", "DOWN", or "UNREACHABLE").')
                    ],
                    [
                        'macro'       => '$HOSTSTATEID$',
                        'description' => __('Current host state as integer (0=UP, 1=DOWN, 2=UNREACHABLE).')
                    ],
                    [
                        'macro'       => '$LASTHOSTSTATE$',
                        'description' => __('Last host state as string ("UP", "DOWN", or "UNREACHABLE").')
                    ],
                    [
                        'macro'       => '$LASTHOSTSTATEID$',
                        'description' => __('Last host state as integer (0=UP, 1=DOWN, 2=UNREACHABLE).')
                    ],
                    [
                        'macro'       => '$HOSTSTATETYPE$',
                        'description' => __('Current host state type as string ("SOFT" or "HARD").')
                    ],
                    [
                        'macro'       => '$HOSTATTEMPT$',
                        'description' => __('Current check attempt as integer')
                    ],
                    [
                        'macro'       => '$MAXHOSTATTEMPTS$',
                        'description' => __('Max number of host check attempts.')
                    ],
                    [
                        'macro'       => '$HOSTEVENTID$',
                        'description' => __('Number of state change events as unique number per host.')
                    ],
                    [
                        'macro'       => '$LASTHOSTEVENTID$',
                        'description' => __('Last unique host event number.')
                    ],
                    [
                        'macro'       => '$HOSTPROBLEMID$',
                        'description' => __('Unique UUIDv4 string of the current host problem.')
                    ],
                    [
                        'macro'       => '$LASTHOSTPROBLEMID$',
                        'description' => __('Unique UUIDv4 string of the last host problem.')
                    ],
                    [
                        'macro'       => '$HOSTLATENCY$',
                        'description' => __('Delay between scheduled check time and actual check time as floating point. ("2.05")')
                    ],
                    [
                        'macro'       => '$HOSTEXECUTIONTIME$',
                        'description' => __('Plugin execution time as floating point. ("4.21").')
                    ],
                    [
                        'macro'       => '$HOSTDURATION$',
                        'description' => __('Time since last state change occured as string (HH MM SS)')
                    ],
                    [
                        'macro'       => '$HOSTDURATIONSEC$',
                        'description' => __('Time since last state change occured as integer in seconds.')
                    ],
                    [
                        'macro'       => '$HOSTDOWNTIME$',
                        'description' => __('If the host in a scheduled downtime, this value will be >= 1.')
                    ],
                    [
                        'macro'       => '$HOSTPERCENTCHANGE$',
                        'description' => __('State change percentage as floating point. (This is used by the flap detection algorithm).')
                    ],
                    [
                        'macro'       => '$HOSTGROUPNAME$',
                        'description' => __('Contain the first host group of an host as string ("12403d1a-70c0-468b-ad8d-446fb8412205")')
                    ],
                    [
                        'macro'       => '$HOSTGROUPNAMES$',
                        'description' => __('A comma separated of all the host groups the host belongs to. ("12403d1a-70c0-468b-ad8d-446fb8412205,20a1bad7-60c1-433d-8e28-716cde8b8f04")')
                    ],
                    [
                        'macro'       => '$LASTHOSTCHECK$',
                        'description' => __('Unix timestamp (integer) of the last host check.')
                    ],
                    [
                        'macro'       => '$LASTHOSTSTATECHANGE$',
                        'description' => __('Unix timestamp (integer) of last host state change.')
                    ],
                    [
                        'macro'       => '$LASTHOSTUP$',
                        'description' => __('Unix timestamp (integer) of the last host UP state.')
                    ],
                    [
                        'macro'       => '$LASTHOSTDOWN$',
                        'description' => __('Unix timestamp (integer) of the last host DOWN state.')
                    ],
                    [
                        'macro'       => '$LASTHOSTUNREACHABLE$',
                        'description' => __('Unix timestamp (integer) of the last host UNREACHABLE state.')
                    ],
                    [
                        'macro'       => '$HOSTOUTPUT$',
                        'description' => __('Output of host check plugin ("OK - 127.5.6.7: rta 0,054ms, lost 0% ").')
                    ],
                    [
                        'macro'       => '$LONGHOSTOUTPUT$',
                        'description' => __('The long plugin output of current host check.')
                    ],
                    [
                        'macro'       => '$HOSTPERFDATA$',
                        'description' => __('Performance date output of current host check. ("rta=0.054000ms;100.000000;500.000000;0.000000 pl=0%;20;60;0")')
                    ],
                    [
                        'macro'       => '$HOSTCHECKCOMMAND$',
                        'description' => __('Contains the command UUID including used command arguments ("5a538ebc-03de-4ce6-8e32-665b841abde3!3000.0,80%!4000.0,90%;")')
                    ],
                    [
                        'macro'       => '$HOSTACTIONURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$HOSTNOTESURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$HOSTNOTES$',
                        'description' => __('Uer defined notes for the host.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSERVICES$',
                        'description' => __('Total number of services associated with the host.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSERVICESOK$',
                        'description' => __('Total number of services associated with the host in state OK.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSERVICESWARNING$',
                        'description' => __('Total number of services associated with the host in state WARNING.')

                    ],
                    [
                        'macro'       => '$TOTALHOSTSERVICESCRITICAL$',
                        'description' => __('Total number of services associated with the host in state CRITICAL.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSERVICESUNKNOWN$',
                        'description' => __('Total number of services associated with the host in state UNKNOWN.')
                    ],
                    [
                        'macro'       => '$HOSTPROBLEMSTART$',
                        'description' => __('This is a timestamp in time_t format (seconds since the UNIX epoch) indicating the time at which the host state changed into a non-Up state.')
                    ],
                    [
                        'macro'       => '$HOSTPROBLEMEND$',
                        'description' => __('This is a timestamp in time_t format (seconds since the UNIX epoch) indicating the time at which the host state changed changed back into Up state.')
                    ],
                    [
                        'macro'       => '$HOSTPROBLEMDURATIONSEC$',
                        'description' => __('The duration in seconds between $HOSTPROBLEMSTART$ and $HOSTPROBLEMEND$.')
                    ],
                    [
                        'macro'       => '$HOSTPROBLEMDURATION$',
                        'description' => __('The duration between $HOSTPROBLEMSTART$ and $HOSTPROBLEMEND$ in a human readable format e.g.: 1d 10h 8m 35s')
                    ]

                ]
            ],

            [
                'category' => __('Host Group Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$HOSTGROUPALIAS$',
                        'description' => __('The UUID of the host group ("fc11e1b9-9c0f-49d7-90b5-f283fe39a21d")')
                    ],
                    [
                        'macro'       => '$HOSTGROUPMEMBERS$',
                        'description' => __('A comma separated list of the host group mebers as UUIDs ("c7176869-ffb7-4149-b960-bdbf9ae85968,f5e943f3-c46f-4f6d-8fc1-468774a72332").')
                    ],
                    [
                        'macro'       => '$HOSTGROUPNOTES$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$HOSTGROUPNOTESURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$HOSTGROUPACTIONURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ]
                ]
            ],

            [
                'category' => __('Service Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$SERVICEDESC$',
                        'description' => __('The UUID of the service ("bd19ce04-3209-4664-a146-bd1220e740bc")')
                    ],
                    [
                        'macro'       => '$SERVICEDISPLAYNAME$',
                        'description' => __('The human name of the service ("PING")')
                    ],
                    [
                        'macro'       => '$SERVICESTATE$',
                        'description' => __('Current service state as string  ("OK", "WARNING", "UNKNOWN", or "CRITICAL").')
                    ],
                    [
                        'macro'       => '$SERVICESTATEID$',
                        'description' => __('Current service state as integer (0=OK, 1=WARNING, 2=CRITICAL, 3=UNKNOWN).')
                    ],
                    [
                        'macro'       => '$LASTSERVICESTATE$',
                        'description' => __('Last service state as string ("OK", "WARNING", "UNKNOWN", or "CRITICAL").')

                    ],
                    [
                        'macro'       => '$LASTSERVICESTATEID$',
                        'description' => __('Last service state as integer (0=OK, 1=WARNING, 2=CRITICAL, 3=UNKNOWN).')
                    ],
                    [
                        'macro'       => '$SERVICESTATETYPE$',
                        'description' => __('Current service state type as string ("SOFT" or "HARD").')
                    ],
                    [
                        'macro'       => '$SERVICEATTEMPT$',
                        'description' => __('Current check attempt as integer')
                    ],
                    [
                        'macro'       => '$MAXSERVICEATTEMPTS$',
                        'description' => __('Max number of service check attempts.')
                    ],
                    [
                        'macro'       => '$SERVICEISVOLATILE$',
                        'description' => __('Is volatile as integer (0=NO, 1=YES)')
                    ],
                    [
                        'macro'       => '$SERVICEEVENTID$',
                        'description' => __('Number of state change events as unique number per service.')
                    ],
                    [
                        'macro'       => '$LASTSERVICEEVENTID$',
                        'description' => __('Last unique service event number.')
                    ],
                    [
                        'macro'       => '$SERVICEPROBLEMID$',
                        'description' => __('Unique UUIDv4 string the current service problem.')
                    ],
                    [
                        'macro'       => '$LASTSERVICEPROBLEMID$',
                        'description' => __('Unique UUIDv4 string of the last service problem.')
                    ],
                    [
                        'macro'       => '$SERVICELATENCY$',
                        'description' => __('Delay between scheduled check time and actual check time as floating point. ("2.05")')
                    ],
                    [
                        'macro'       => '$SERVICEEXECUTIONTIME$',
                        'description' => __('Plugin execution time as floating point. ("4.21").')
                    ],
                    [
                        'macro'       => '$SERVICEDURATION$',
                        'description' => __('Time since last state change occured as string (HH MM SS)')
                    ],
                    [
                        'macro'       => '$SERVICEDURATIONSEC$',
                        'description' => __('Time since last state change occured as integer in seconds.')
                    ],
                    [
                        'macro'       => '$SERVICEDOWNTIME$',
                        'description' => __('If the service in a scheduled downtime, this value will be >= 1.')
                    ],
                    [
                        'macro'       => '$SERVICEPERCENTCHANGE$',
                        'description' => __('State change percentage as floating point. (This is used by the flap detection algorithm).')
                    ],
                    [
                        'macro'       => '$SERVICEGROUPNAME$',
                        'description' => __('Contain the first service group of an service as string ("6123eb90-a3eb-4895-b348-c464d9494d14")')
                    ],
                    [
                        'macro'       => '$SERVICEGROUPNAMES$',
                        'description' => __('A comma separated of all the service groups the service belongs to. ("6123eb90-a3eb-4895-b348-c464d9494d14,666b1caf-4c0f-45e8-9380-78f9e915b82d")')
                    ],
                    [
                        'macro'       => '$LASTSERVICECHECK$',
                        'description' => __('Unix timestamp (integer) of the last service check.')
                    ],
                    [
                        'macro'       => '$LASTSERVICESTATECHANGE$',
                        'description' => __('Unix timestamp (integer) of last service state change.')
                    ],
                    [
                        'macro'       => '$LASTSERVICEOK$',
                        'description' => __('Unix timestamp (integer) of the last service OK state.')
                    ],
                    [
                        'macro'       => '$LASTSERVICEWARNING$',
                        'description' => __('Unix timestamp (integer) of the last service WARNING state.')
                    ],
                    [
                        'macro'       => '$LASTSERVICECRITICAL$',
                        'description' => __('Unix timestamp (integer) of the last service CRITICAL state.')
                    ],
                    [
                        'macro'       => '$LASTSERVICEUNKNOWN$',
                        'description' => __('Unix timestamp (integer) of the last service UNKNOWN state.')
                    ],
                    [
                        'macro'       => '$SERVICEOUTPUT$',
                        'description' => __('Output of service check plugin ("OK - 127.5.6.7: rta 0,054ms, lost 0% ").')
                    ],
                    [
                        'macro'       => '$LONGSERVICEOUTPUT$',
                        'description' => __('The long plugin output of current service check.')
                    ],
                    [
                        'macro'       => '$SERVICEPERFDATA$',
                        'description' => __('Performance date output of current service check. ("rta=0.054000ms;100.000000;500.000000;0.000000 pl=0%;20;60;0")')
                    ],
                    [
                        'macro'       => '$SERVICECHECKCOMMAND$',
                        'description' => __('Contains the command UUID including used command arguments ("5a538ebc-03de-4ce6-8e32-665b841abde3!3000.0,80%!4000.0,90%;")')
                    ],
                    [
                        'macro'       => '$SERVICEACTIONURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$SERVICENOTESURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$SERVICENOTES$',
                        'description' => __('Uer defined notes for the service.')
                    ],
                    [
                        'macro'       => '$SERVICEPROBLEMSTART$',
                        'description' => __('This is a timestamp in time_t format (seconds since the UNIX epoch) indicating the time at which the service state changed into a non-Ok state.')
                    ],
                    [
                        'macro'       => '$SERVICEPROBLEMEND$',
                        'description' => __('This is a timestamp in time_t format (seconds since the UNIX epoch) indicating the time at which the service state changed changed back into Ok state.')
                    ],
                    [
                        'macro'       => '$SERVICEPROBLEMDURATIONSEC$',
                        'description' => __('The duration in seconds between $SERVICEPROBLEMSTART$ and $SERVICEPROBLEMEND$.')
                    ],
                    [
                        'macro'       => '$SERVICEPROBLEMDURATION$',
                        'description' => __('The duration between $SERVICEPROBLEMSTART$ and $SERVICEPROBLEMEND$ in a human readable format e.g.: 1d 10h 8m 35s')
                    ]
                ]
            ],

            [
                'category' => __('Service Group Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$SERVICEGROUPALIAS$',
                        'description' => __('The UUID of the host group ("65c7692e-b322-4788-8232-97258b457f22")')
                    ],
                    [
                        'macro'       => '$SERVICEGROUPMEMBERS$',
                        'description' => __('A comma separated list of the service group mebers as UUIDs ("0a9feda1-569e-40fe-a5a3-b2f389818301,ade06bfb-f9bd-4112-9537-2b3d286a6572").')
                    ],
                    [
                        'macro'       => '$SERVICEGROUPNOTES$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$SERVICEGROUPNOTESURL$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ],
                    [
                        'macro'       => '$SERVICEGROUPNOTES$',
                        'description' => __('Not implemented by openITCOCKPIT.')
                    ]
                ]
            ],

            [
                'category' => __('Contact Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$CONTACTNAME$',
                        'description' => __('The UUID of the contact ("9595f7d6-2b79-4a7b-b7b8-414eec84c0b0")')
                    ],
                    [
                        'macro'       => '$CONTACTGROUPNAME$',
                        'description' => __('The human name of the contact ("info").')
                    ],
                    [
                        'macro'       => '$CONTACTALIAS$',
                        'description' => __('The human name of the contact ("info"), same as $CONTACTGROUPNAME$')
                    ],
                    [
                        'macro'       => '$CONTACTEMAIL$',
                        'description' => __('Email address of the contact. ("community@openitcockpit.io")')
                    ],
                    [
                        'macro'       => '$CONTACTPAGER$',
                        'description' => __('Pager number of the contact. ("004913456789")')
                    ],
                    [
                        'macro'       => '$CONTACTGROUPNAMES$',
                        'description' => __('A comma separated of all the contact groups the contact belongs to. ("06e352a8-643d-4fa8-a7bd-9ff74ad0e112,4992c7ad-1bb4-465a-8fe4-a721461b69a2")')
                    ]
                ]
            ],

            [
                'category' => __('Contact Group Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$CONTACTGROUPALIAS$',
                        'description' => __('The UUID of the contact group ("06e352a8-643d-4fa8-a7bd-9ff74ad0e112")')
                    ],
                    [
                        'macro'       => '$CONTACTGROUPMEMBERS$',
                        'description' => __('A comma separated of all the contacts belongs to the contact group. ("9595f7d6-2b79-4a7b-b7b8-414eec84c0b0,6de7d0f9-413e-41cc-b1e4-3289072b72c4")')
                    ]
                ]
            ],

            [
                'category' => __('SUMMARY Macros (Notice: CPU intensive to calculate - use with care)'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$TOTALHOSTSUP$',
                        'description' => __('Number of hosts in state UP.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSDOWN$',
                        'description' => __('Number of hosts in state DOWN.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSUNREACHABLE$',
                        'description' => __('Number of hosts in state UNREACHABLE.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSDOWNUNHANDLED$',
                        'description' => __('Number of unhandled hosts. Unhandled hosts are in a DOWN state and not acknowledged or in a scheduled downtime.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTSUNREACHABLEUNHANDLED$',
                        'description' => __('Number of unhandled hosts. Unhandled hosts are in a UNREACHABLE state and not acknowledged or in a scheduled downtime.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTPROBLEMS$',
                        'description' => __('Number of hosts in a non UP state.')
                    ],
                    [
                        'macro'       => '$TOTALHOSTPROBLEMSUNHANDLED$',
                        'description' => __('Number of unhandled hosts. Unhandled hosts are in a non UP state and not acknowledged or in a scheduled downtime.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESOK$',
                        'description' => __('Number of services in state OK.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESWARNING$',
                        'description' => __('Number of services in state WARNING.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESCRITICAL$',
                        'description' => __('Number of services in state CRITICAL.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESUNKNOWN$',
                        'description' => __('Number of services in state UNKNOWN.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESWARNINGUNHANDLED$',
                        'description' => __('Number of unhandled services. Unhandled services are in a WARNING state and not acknowledged or in a scheduled downtime.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESCRITICALUNHANDLED$',
                        'description' => __('Number of unhandled services. Unhandled services are in a CRITICAL state and not acknowledged or in a scheduled downtime.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICESUNKNOWNUNHANDLED$',
                        'description' => __('Number of unhandled services. Unhandled services are in a UNKNOWN state and not acknowledged or in a scheduled downtime.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICEPROBLEMS$',
                        'description' => __('Number of services in a non OK state.')
                    ],
                    [
                        'macro'       => '$TOTALSERVICEPROBLEMSUNHANDLED$',
                        'description' => __('Number of unhandled services. Unhandled services are in a non OK state and not acknowledged or in a scheduled downtime.')
                    ]
                ]
            ],

            [
                'category' => __('Notification Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$NOTIFICATIONTYPE$',
                        'description' => __('Notification type as string ("PROBLEM", "RECOVERY", "ACKNOWLEDGEMENT", "FLAPPINGSTART", "FLAPPINGSTOP", "FLAPPINGDISABLED", "DOWNTIMESTART", "DOWNTIMEEND", or "DOWNTIMECANCELLED").')
                    ],
                    [
                        'macro'       => '$NOTIFICATIONRECIPIENTS$',
                        'description' => __('A comma separated of all the contacts for this notification. ("12403d1a-70c0-468b-ad8d-446fb8412205,20a1bad7-60c1-433d-8e28-716cde8b8f04")')

                    ],
                    [
                        'macro'       => '$NOTIFICATIONISESCALATED$',
                        'description' => __('Is this an escalated notification (0=NO, 1=YES)')
                    ],
                    [
                        'macro'       => '$NOTIFICATIONAUTHOR$',
                        'description' => __('openITCOCKPIT user name as string.')
                    ],
                    [
                        'macro'       => '$NOTIFICATIONAUTHORNAME$',
                        'description' => __('openITCOCKPIT user name as string.')
                    ],
                    [
                        'macro'       => '$NOTIFICATIONAUTHORALIAS$',
                        'description' => __('openITCOCKPIT user name as string.')
                    ],
                    [
                        'macro'       => '$NOTIFICATIONCOMMENT$',
                        'description' => __('Comment data of the notification.')
                    ],
                    [
                        'macro'       => '$HOSTNOTIFICATIONNUMBER$',
                        'description' => __('Number of send notifications for a host. If the host state goes to RECOVERY, the counter will be reset to 0.')
                    ],
                    [
                        'macro'       => '$HOSTNOTIFICATIONID$',
                        'description' => __('Unique UUIDv4 string of the host notification.')
                    ],
                    [
                        'macro'       => '$SERVICENOTIFICATIONNUMBER$',
                        'description' => __('Number of send notifications for a service. If the service state goes to OK, the counter will be reset to 0.')
                    ],
                    [
                        'macro'       => '$SERVICENOTIFICATIONID$',
                        'description' => __('Unique UUIDv4 string of the service notification.')
                    ]
                ]
            ],

            [
                'category' => __('Date and Time Macros'),
                'class'    => 'highlight-default-macro',
                'macros'   => [
                    [
                        'macro'       => '$DATE$',
                        'description' => __('Current date as string (DD-MM-YYYY).')
                    ],
                    [
                        'macro'       => '$TIME$',
                        'description' => __('Current time as string (HH:MM:SS).')
                    ],
                    [
                        'macro'       => '$TIMET$',
                        'description' => __('Current time as UNIX timestamp.')
                    ]
                ]
            ]
        ];
    }

}
