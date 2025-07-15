# openITCOCKPIT - next generation monitoring

The open source configuration interface for [Nagios](https://www.nagios.org/), [Naemon](http://www.naemon.io/)
and [Prometheus](https://prometheus.io/)

![openITCOCKPIT Logo](https://openitcockpit.io/assets/images/site/logos/logo_open_it_cockpit_community_edition_rgb.svg)

[![Discord: ](https://img.shields.io/badge/Discord-Discord.svg?label=&logo=discord&logoColor=ffffff&color=7389D8&labelColor=6A7EC2)](https://discord.gg/G8KhxKuQ9G)
[![Reddit: ](https://img.shields.io/reddit/subreddit-subscribers/openitcockpit?style=social)](https://www.reddit.com/r/openitcockpit/)
[![IRC: #openitcockpit on chat.freenode.net](https://img.shields.io/badge/%23openitcockpit-Libera.Chat-blue.svg)](https://web.libera.chat/#openitcockpit)
[![Build Status Stable](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&subject=stable)](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&subject=stable)
[![Build Status Nightly](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&subject=nightly)](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&subject=nightly)

# What is openITCOCKPIT?

openITCOCKPIT is an Open Source system monitoring tool built for different monitoring engines like Nagios, Naemon and
Prometheus.

So easy that everyone can use it: create your entire monitoring configuration with a few clicks due to our smart
interface.

This is the repository of the backend server code, that is providing the API for
the [official openITCOCKPIT frontend](https://github.com/openITCOCKPIT/openITCOCKPIT-frontend-angular).

![openITCOCKPIT](screenshots/dashboard.png?raw=true "openITCOCKPIT")

# Demo

Play around with our [Demo](https://demo.openitcockpit.io/) system. Its equipped with the majority of modules that you
will get with the community license

Credentials:

````
Username(Email): demo@openitcockpit.io
Password: demo123
````

# Build status

| Distribution | Stable                                                                                                           | Nightly                                                                                                           |
|--------------|------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| Jammy        | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&style=flat-square) | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&style=flat-square) |
| Noble        | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&style=flat-square) | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&style=flat-square) |
| Bookworm     | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&style=flat-square) | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&style=flat-square) |
| RHEL 8       | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&style=flat-square) | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&style=flat-square) |
| RHEL 9       | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fstable&style=flat-square) | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-packages%2Fnightly&style=flat-square) |
| Docker       | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-docker%2Fstable&style=flat-square)   | ![status](https://drone.openitcockpit.io/buildStatus/icon?job=openitcockpit-docker%2Fnightly&style=flat-square)   |

# System requirements

* Ubuntu LTS or Debian
* 2 CPU cores (x86-64)
* 4 GB RAM
* 60 GB space

### Production system sizing

Unfortunately there is no golden rule for the right sizing of a monitoring system. This depends on the amount of hosts
and services you like to monitor.

It's recommended to use SSD as main storage.

A rough guide:

* 32 GB RAM
* 16 CPU Cores
* 500 GB space

# Installation

openITCOCKPIT runs on Ubuntu and Debian Linux systems and is available for download/installation via an apt repository.

To install openITCOCKPIT on your system, please follow the official
documentation: https://openitcockpit.io/download_server/

## Raspberry Pi and arm64

openITCOCKPIT is 100% compatible to arm64. More information can be found on the project
website: [https://openitcockpit.io/download_server/](https://openitcockpit.io/download_server/)

## Docker

We provide pre-build Docker images of openITCOCKPIT. Please follow the instructions provided in the
documentation: [https://docs.openitcockpit.io/en/installation/docker/](https://docs.openitcockpit.io/en/installation/docker/)

# Register openitcockpit community version:

You can register your openITCOCKPIT installation to get access to free community modules.
Login to the webinterface of openITCOCKPIT and navigate to System -> Registration,
enter the community license key `e5aef99e-817b-0ff5-3f0e-140c1f342792` and click Register.
After successful registration you can install the free community modules at System tools -> Package Manager

# Main Features

* Easy to use web interface
* Template based configuration that will make your life easier
* MySQL based
* REST API
* Inbuilt package manager everyone can provide Add-ons for extending the interface
* HA cluster ready
* Two-factor authentication
* LDAP authentication
* Multitenancy
* Object permissions
* [Distributed Monitoring](https://docs.openitcockpit.io/en/configuration/distribute-module/)
* [Mod-Gearman](http://mod-gearman.org/)
* [Statusengine](http://statusengine.org/)
* And much more to discover...

# Screenshots

![openITCOCKPIT](screenshots/timeline.png?raw=true "Timeline")

![openITCOCKPIT](screenshots/mapmodule.png?raw=true "Maps")

![openITCOCKPIT](screenshots/event_correlation.png?raw=true "Event correlation")

![openITCOCKPIT](screenshots/downtime_report.png?raw=true "Downtime report")

![openITCOCKPIT](screenshots/current_state_report.png?raw=true "Current state report")

# Developers welcome

openITCOCKPIT's development is publicly available in GitHub. Everybody is welcome to join :-)

- [Creating an openITCOCKPIT development environment](https://docs.openitcockpit.io/en/development/setup-dev-env/)
- [Create your own translation](https://github.com/openITCOCKPIT/openITCOCKPIT/issues/1573)
- [Creating a new openITCOCKPIT module](https://docs.openitcockpit.io/en/development/create-new-module/introduction/)
- [Creating a new check plugin](https://docs.openitcockpit.io/en/development/new-check-plugin/)

# Need help or support?

* Official [Discord Server](https://discord.gg/G8KhxKuQ9G)
* Join [#openitcockpit](https://web.libera.chat/#openitcockpit) on Libera Chat
* [it-novum GmbH](https://it-services.it-novum.com/support-2/) provides commercial support

# Security

Please send security vulnerabilities found in openITCOCKPIT or software that is used by openITCOCKPIT
to: `security@openitcockpit.io`.

All disclosed vulnerabilities are available
here: [https://openitcockpit.io/security/](https://openitcockpit.io/security/)

# License

```
Copyright (C) 2015-2025  it-novum GmbH


openITCOCKPIT is dual licensed

1)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 of the License.


This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.


You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

2)
If you purchased an openITCOCKPIT Enterprise Edition you can use this file
under the terms of the openITCOCKPIT Enterprise Edition licence agreement.
Licence agreement and licence key will be shipped with the order
confirmation.
```
