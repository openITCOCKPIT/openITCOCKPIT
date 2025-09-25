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
?>
<style>
    /* STYLE DEFINITIONS */
    * {
        margin: 0;
        padding: 0
    }

    * {
        font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif
    }

    img {
        max-width: 100%
    }

    .collapse {
        margin: 0;
        padding: 0
    }

    body {
        -webkit-font-smoothing: antialiased;
        -webkit-text-size-adjust: none;
        width: 100% !important;
        height: 100%
    }

    a {
        color: #2ba6cb
    }

    .btn {
        display: inline-block;
        padding: 6px 12px;
        margin-bottom: 0;
        font-size: 14px;
        font-weight: normal;
        line-height: 1.428571429;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        cursor: pointer;
        border: 1px solid transparent;
        border-radius: 4px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        -o-user-select: none;
        user-select: none;
        color: #333;
        background-color: white;
        border-color: #CCC;
    }

    p.callout {
        padding: 15px;
        background-color: #ecf8ff;
        margin-bottom: 15px
    }

    .callout a {
        font-weight: bold;
        color: #2ba6cb
    }

    table.social {
        background-color: #ebebeb
    }

    .social .soc-btn {
        padding: 3px 7px;
        border-radius: 2px;
        -webkit-border-radius: 2px;
        -moz-border-radius: 2px;
        font-size: 12px;
        margin-bottom: 10px;
        text-decoration: none;
        color: #FFF;
        font-weight: bold;
        display: block;
        text-align: center
    }

    a.fb {
        background-color: #3b5998 !important
    }

    a.tw {
        background-color: #1daced !important
    }

    a.gp {
        background-color: #db4a39 !important
    }

    a.ms {
        background-color: #000 !important
    }

    .sidebar .soc-btn {
        display: block;
        width: 100%
    }

    table.head-wrap {
        width: 100%;
        background-color: #213340;
        background-image: linear-gradient(to bottom, #233545, #1b2321);
        background-repeat: repeat-x;
    }

    .header.container table td.logo {
        padding: 15px
    }

    .header.container table td.label {
        padding: 15px;
        padding-left: 0
    }

    table.body-wrap {
        width: 100%
    }

    table.footer-wrap {
        width: 100%;
        clear: both !important
    }

    .footer-wrap .container td.content p {
        border-top: 1px solid #d7d7d7;
        padding-top: 15px
    }

    .footer-wrap .container td.content p {
        font-size: 10px;
        font-weight: bold
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
        line-height: 1.1;
        margin-bottom: 15px;
        color: #000
    }

    h1 small, h2 small, h3 small, h4 small, h5 small, h6 small {
        font-size: 60%;
        color: #6f6f6f;
        line-height: 0;
        text-transform: none
    }

    h1 {
        font-weight: 200;
        font-size: 44px
    }

    h2 {
        font-weight: 200;
        font-size: 37px
    }

    h3 {
        font-weight: 500;
        font-size: 27px
    }

    h4 {
        font-weight: 500;
        font-size: 23px
    }

    h5 {
        font-weight: 900;
        font-size: 17px
    }

    h6 {
        font-weight: 900;
        font-size: 14px;
        text-transform: uppercase;
        color: #b1b3b2
    }

    .collapse {
        margin: 0 !important
    }

    p, ul {
        margin-bottom: 10px;
        font-weight: normal;
        font-size: 14px;
        line-height: 1.6
    }

    p.lead {
        font-size: 17px
    }

    p.last {
        margin-bottom: 0
    }

    ul li {
        margin-left: 5px;
        list-style-position: inside
    }

    ul.sidebar {
        background: #ebebeb;
        display: block;
        list-style-type: none
    }

    ul.sidebar li {
        display: block;
        margin: 0
    }

    ul.sidebar li a {
        text-decoration: none;
        color: #666;
        padding: 10px 16px;
        margin-right: 10px;
        cursor: pointer;
        border-bottom: 1px solid #777;
        border-top: 1px solid #fff;
        display: block;
        margin: 0
    }

    ul.sidebar li a.last {
        border-bottom-width: 0
    }

    ul.sidebar li a h1, ul.sidebar li a h2, ul.sidebar li a h3, ul.sidebar li a h4, ul.sidebar li a h5, ul.sidebar li a h6, ul.sidebar li a p {
        margin-bottom: 0 !important
    }

    .container {
        display: block !important;
        max-width: 600px !important;
        margin: 0 auto !important;
        clear: both !important
    }

    .content {
        padding: 15px;
        max-width: 600px;
        margin: 0 auto;
        display: block
    }

    .content table {
        width: 100%
    }

    .column {
        width: 300px;
        float: left
    }

    .column tr td {
        padding: 15px
    }

    .column-wrap {
        padding: 0 !important;
        margin: 0 auto;
        max-width: 600px !important
    }

    .column table {
        width: 100%
    }

    .social .column {
        width: 280px;
        min-width: 279px;
        float: left
    }

    .clear {
        display: block;
        clear: both
    }

    .label {
        border-radius: 0.25em;
        color: #fff;
        display: inline;
        font-weight: 700;
        line-height: 1;
        padding: 0.1em 0.6em 0.1em;
        text-align: center;
        vertical-align: baseline;
        white-space: nowrap;
    }

    .bg-color-blue {
        background-color: #57889c !important;
    }

    .bg-color-blueLight {
        background-color: #92a2a8 !important;
    }

    .bg-color-blueDark {
        background-color: #4c4f53 !important;
    }

    .bg-color-green {
        background-color: #356e35 !important;
    }

    .bg-color-greenLight {
        background-color: #71843f !important;
    }

    .bg-color-greenDark {
        background-color: #496949 !important;
    }

    .bg-color-red {
        background-color: #a90329 !important;
    }

    .bg-color-yellow {
        background-color: #b09b5b !important;
    }

    .bg-color-orange {
        background-color: #c79121 !important;
    }

    .bg-color-orangeDark {
        background-color: #a57225 !important;
    }

    .bg-color-grayDark {
        background-color: #525252 !important;
    }

    .bg-color-magenta {
        background-color: #6e3671 !important;
    }

    .UP {
        color: #449D44 !important;
    }

    .DOWN {
        color: #C9302C !important;
    }

    .UNREACHABLE {
        color: #92A2A8 !important;
    }

    .OK {
        color: #449D44 !important;
    }

    .WARNING {
        color: #DF8F1D !important;
    }

    .CRITICAL {
        color: #C9302C !important;
    }

    .UNKNOWN {
        color: #92A2A8 !important;
    }

    .txt-color-green {
        color: #356e35 !important;
    }

    .txt-color-red {
        color: #a90329 !important;
    }

    .txt-color-orange {
        color: #b19a6b !important;
    }

    .txt-color-blueLight {
        color: #92a2a8 !important;
    }

    @media only screen and (max-width: 600px) {
        a[class="btn"] {
            display: block !important;
            margin-bottom: 10px !important;
            background-image: none !important;
            margin-right: 0 !important
        }

        div[class="column"] {
            width: auto !important;
            float: none !important
        }

        table.social div[class="column"] {
            width: auto !important
        }
    }

    .notification_type {
        color: white;
        font-size: x-large;
        line-height: 1;
        letter-spacing: 10px;
        font-weight: bold;
        text-shadow: 1px 1px 1px #A09D9D, 0 0 0 #000, 1px 1px 1px #def;
    }

    /* EVC Summary View*/
    .criticalEvcSummary {
        color: white !important;
        -webkit-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        -moz-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        padding: 2px;
        background: #d9534f; /* Outlook on Windows has no support for linear-gradient */
        background: linear-gradient(to right, #4c4f53 15px, #d9534f 2%); /* W3C */

    }

    .criticalEvcSummary:after {
        font-family: FontAwesome;
        /*content: "\f071";*/
        color: white;
    }

    .okEvcSummary {
        -webkit-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        -moz-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        padding: 5px;
        color: white;
        background: #5cb85c; /* Outlook on Windows has no support for linear-gradient */
        background: linear-gradient(to right, #4c4f53 15px, #5cb85c 2%); /* W3C */
    }

    .unknownEvcSummary {
        -webkit-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        -moz-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        padding: 2px;
        color: white;
        background: #888888; /* Outlook on Windows has no support for linear-gradient */
        background: linear-gradient(to right, #4c4f53 15px, #888888 2%); /* W3C */
    }

    .warningEvcSummary {
        -webkit-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        -moz-box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        box-shadow: 3px 3px 1px -2px rgba(0, 0, 0, 0.75);
        padding: 2px;
        color: white;
        background: #f0ad4e; /* Outlook on Windows has no support for linear-gradient */
        background: linear-gradient(to right, #4c4f53 15px, #f0ad4e 2%); /* W3C */
    }

    .disabledEvcSummary {
        background: repeating-linear-gradient(
            -45deg,
            #ffffff,
            #ffffff 10px,
            #eaf0f2 10px,
            #eaf0f2 20px
        );
    }

    .disabledEvcSummary:before {
        content: "🔌";
        position: absolute;
        right: 2px;
        top: -2px;
        font-size: 12px;
    }

    .gatter {
        display: block;
        white-space: nowrap;
        font-family: Verdana;
        -moz-transform: rotate(-90deg);
        -moz-transform-origin: center center;
        -webkit-transform: rotate(-90deg);
        -webkit-transform-origin: center center;
        -ms-transform: rotate(-90deg);
        -ms-transform-origin: center center;
        width: 15px;
        bottom: 1px;
        position: absolute;
        font-size: 14px;
        text-shadow: 1px 1px #7d7d7d;
        font-family: 'Courier New', Arial;
    }

    #evcSummary {
        border-spacing: 5px;
        border-collapse: separate;
    }

    #evcSummary td {
        position: relative;
    }

    #evcSummary td div {
        position: absolute;
        left: 0;
    }

    #evcSummary span {
        padding: 5px 10px 5px 20px;
        display: inline-block;
        font-size: 13px;
        font-family: 'Courier New', Arial;
    }

    .disabledEvcSummary span {
        padding: 15px 1px 15px 20px;
        display: inline-block;
        font-size: 13px;
        font-family: 'Courier New', Arial;
    }

    .borderOkEvcSummary {
        border-right: 5px solid #5cb85c;
    }

    .borderWarningEvcSummary {
        border-right: 5px solid #f0ad4e;
    }

    .borderCriticalEvcSummary {
        border-right: 5px solid #d9534f;
    }

    .borderUnknownEvcSummary {
        border-right: 5px solid #888888;
    }

    .borderDisabledEvcSummary {
        border-right: 5px solid #dee3e5;
    }

    .borderNotMonitoredEvcSummary {
        border-right: 5px solid #428bca;
    }

    .virtualLayerService {
        font-weight: bold;
        padding: 10px 1px 10px 15px !important;
    }

    /* Message of the day style settings for email notifcation*/

    .message_otd_alert h1 {
        color: #ffffff;
    }

    .alert-primary {
        background-color: #4285F4;
        background-image: linear-gradient(to bottom, #4285F4, #77a2ef) !important;
        background-repeat: repeat-x;
    }

    .alert-info {
        background-color: #33b5e5;
        background-image: linear-gradient(to bottom, #33b5e5, #6fc0e2) !important;
        background-repeat: repeat-x;
    }

    .alert-success {
        background-color: #00C851;
        background-image: linear-gradient(to bottom, #00C851, #45d081) !important;
        background-repeat: repeat-x;
    }

    .alert-warning {
        background-color: #ffbb33;
        background-image: linear-gradient(to bottom, #ffbb33, #fabd52) !important;
        background-repeat: repeat-x;
    }

    .alert-danger {
        background-color: #CC0000 !important;
        background-image: linear-gradient(to bottom, #CC0000, #ef2d2d) !important;
        background-repeat: repeat-x;
    }

    .title-border {
        border-bottom: 3px solid;
        border-image-slice: 0 0 1 0;
        line-height: 3;
    }

    .title-border-bottom-primary {
        color: #4285F4;
        border-image-source: linear-gradient(45deg, #4285F4, transparent);
    }

    .title-border-bottom-info {
        color: #33b5e5;
        border-image-source: linear-gradient(45deg, #33b5e5, transparent);
    }

    .title-border-bottom-success {
        color: #00C851;
        border-image-source: linear-gradient(45deg, #00C851, transparent);
    }

    .title-border-bottom-warning {
        color: #ffbb33;
        border-image-source: linear-gradient(45deg, #ffbb33, transparent);
    }

    .title-border-bottom-danger {
        color: #CC0000;
        border-image-source: linear-gradient(45deg, #CC0000, transparent);
    }

    .info-date {
        font-size: 11px;
        font-style: italic;
        color: #5e5d5d;
    }

    .resource-table {
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .resource-card {
        border: 1px solid transparent;
        border-radius: 5px;
        padding: 5px 0 5px 5px;
        margin: 5px;
        color: #ffffff;
    }

    .resource-card > h3 {
        font-size: 32px;
        color: #ffffff;
        font-weight: bold;
        line-height: 1.0;
        margin-bottom: 5px;
    }

    .resource-card > h4 {
        font-size: 18px;
        color: #ffffff;
        font-weight: bold;
        line-height: 0.8;
        margin-bottom: 2px;
    }

    .resource-status-icon {
        font-size: 10px;
    }

    .resource-list-item {
        padding: 0 5px;
        border-spacing: 0;
    }

    .text-white {
        color: white;
    }

    .not-underlined {
        text-decoration: none;
    }

</style>
