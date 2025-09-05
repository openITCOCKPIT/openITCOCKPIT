#!/bin/bash
#
# Copyright (C) 2015-2025  it-novum GmbH
# Copyright (C) 2025-today Allgeier IT Services GmbH
#
# This file is dual licensed
#
# 1.
#     This program is free software: you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation, version 3 of the License.
#
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
# 2.
#     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
#     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
#     License agreement and license key will be shipped with the order
#     confirmation.
#

# This SETUP Script should ONLY BE USED WHEN running inside of an container like for example Docker

if ! [ $(id -u) = 0 ]; then
    echo "You need to run this script as root user or via sudo!"
    exit 1
fi

# Enable debug mode so that CakePHP will create missing folders
# https://github.com/openITCOCKPIT/openITCOCKPIT/issues/1446
# https://github.com/cakephp/migrations/issues/565
export OITC_DEBUG=1

APPDIR="/opt/openitc/frontend"

INIFILE=/opt/openitc/etc/mysql/mysql.cnf
DUMPINIFILE=/opt/openitc/etc/mysql/dump.cnf
BASHCONF=/opt/openitc/etc/mysql/bash.conf

OSVERSION=$(grep VERSION_CODENAME /etc/os-release | cut -d= -f2)
OS_BASE="debian"

echo "Copy required system files"
rsync -K -a ${APPDIR}/system/etc/. /etc/ # we use rsync because the destination can be a symlink on RHEL
chown root:root /etc
cp -r ${APPDIR}/system/usr/. /usr/
cp ${APPDIR}/system/nginx/ssl_options_$OSVERSION /etc/nginx/openitc/ssl_options.conf
cp ${APPDIR}/system/nginx/ssl_cert.conf /etc/nginx/openitc/ssl_cert.conf
echo "# This file will NOT be overwritten during an update" >> /etc/nginx/openitc/custom.conf
chmod +x /usr/bin/oitc

echo "Create required system folders"
mkdir -p /opt/openitc/etc/{mysql,grafana,carbon,frontend,nagios,nsta}
mkdir -p /opt/openitc/nagios/etc/config
mkdir -p /opt/openitc/etc/nagios/nagios.cfg.d

chown www-data:www-data /opt/openitc/logs/frontend
chown nagios:www-data /opt/openitc/nagios/etc/config
chown nagios:www-data /opt/openitc/etc/nagios/nagios.cfg.d
chmod 775 /opt/openitc/logs/frontend

chown www-data:www-data /opt/openitc/frontend/tmp

mkdir -p /opt/openitc/frontend/webroot/img/charts
chown www-data:www-data /opt/openitc/frontend/webroot/img/charts

if [[ -d /opt/openitc/frontend/plugins/MapModule/webroot/img/ ]]; then
    chown -R www-data:www-data /opt/openitc/frontend/plugins/MapModule/webroot/img/
fi

# It is important that the statusengine-worker container is already up and running and has created all
# the Statusengine related MySQL tables, because the setup will add partitions to the tables
echo "Checking if Statusengine Tables where already created..."
COUNTER=0
MYSQL_ONLINE=0

set +e
while [ "$COUNTER" -lt 30 ]; do
    #Is Grafana Server Online?
    OUTPUT=$(mysql "--defaults-extra-file=$INIFILE" -e "SELECT 1 FROM statusengine_nodes;" -B -s 2>/dev/null)

    if [ "$OUTPUT" ]; then
        echo "Tables are present."
        MYSQL_ONLINE=1
        break
    fi
    echo "Waiting for Statusengine to create tables..."
    COUNTER=$((COUNTER + 1))
    sleep 1
done

if [[ "$MYSQL_ONLINE" == 0 ]]; then
    echo "ERROR!"
    echo "Statusengine tables are missing! ABORT!"
    exit 1
fi
set -e

#mkdir -p /opt/openitc/var/prometheus
#chown nagios:nagios /opt/openitc/var/prometheus
#mkdir -p /opt/openitc/var/prometheus/victoria-metrics

echo "---------------------------------------------------------------"
echo "Import openITCOCKPIT Core database schema"
oitc migrations migrate


echo "---------------------------------------------------------------"
echo "Load default database"
oitc migrations seed

echo "Running openITCOCKPIT Module database migration/s"
for PLUGIN in $(ls -1 "${APPDIR}/plugins"); do
    if [[ "$PLUGIN" == *Module ]]; then
        if [[ -d "${APPDIR}/plugins/${PLUGIN}/config/Migrations" ]]; then
            echo "Running openITCOCKPIT ${PLUGIN} database migration"
            oitc migrations migrate -p "${PLUGIN}"
        fi

        if [[ -d "${APPDIR}/plugins/${PLUGIN}/config/Seeds" ]]; then
            num_files=$(find "${APPDIR}/plugins/${PLUGIN}/config/Seeds" -mindepth 1 -iname "*.php" -type f | wc -l)
            if [[ "$num_files" -gt 0 ]]; then
                echo "Importing default records for ${PLUGIN} into database"
                oitc migrations seed -p "${PLUGIN}"
            fi
        fi

    fi
done

echo "---------------------------------------------------------------"
# Is mysql or mariadb ?
MYSQL_SERVER_SOFTWARE=$(mysql --defaults-extra-file=${INIFILE} --batch --skip-column-names -e "SELECT IF(VERSION() LIKE '%mariadb%', 'mariadb', 'mysql');")

# Unicode version 4 (very old, but works everywhere)
MYSQL_COLLATIONS="utf8mb4_general_ci"

if [[ "$MYSQL_SERVER_SOFTWARE" == "mariadb" ]]; then
  echo "Detected MariaDB"
  echo "Convert MariaDB Tables from to utf8mb4_uca1400_ai_ci..."
  echo "This will take a while - Please be patient"
  # Unicode version 14 (For some reason, MariaDB supports Unicode 14 but not 9)
  # See ITC-3408
  MYSQL_COLLATIONS="utf8mb4_uca1400_ai_ci"
else
  echo "Detected MySQL"
  echo "Convert MySQL Tables from to utf8mb4_0900_ai_ci..."
  echo "This will take a while - Please be patient"
  # Unicode version 9 - (MariaDB does support this since version 11.4.5 - Released 4. Feb. 2025)
  # So we can only use this for MySQL at the moment since nobody has MariaDB 11.4.5 at the moment (31.03.2025)
  MYSQL_COLLATIONS="utf8mb4_0900_ai_ci"
fi

# Disabled - this takes ages!
#mysql --defaults-extra-file=${INIFILE} -e "ALTER DATABASE ${dbc_dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

mysql --defaults-extra-file=${INIFILE} --batch --skip-column-names -e "SELECT TABLE_NAME FROM \`information_schema\`.\`TABLES\` WHERE \`TABLE_SCHEMA\`='${dbc_dbname}' AND \`TABLE_NAME\` NOT LIKE 'nagios_%' AND \`TABLE_COLLATION\` NOT LIKE '${MYSQL_COLLATIONS}'" | while read TABLE_NAME; do
    echo "ALTER TABLE \`${TABLE_NAME}\` CONVERT TO CHARACTER SET utf8mb4 COLLATE  ${MYSQL_COLLATIONS}; ✔"
    mysql --defaults-extra-file=${INIFILE} -e "ALTER TABLE \`${TABLE_NAME}\` CONVERT TO CHARACTER SET utf8mb4 COLLATE  ${MYSQL_COLLATIONS};"
done

mysql --defaults-extra-file=${INIFILE} -e "ALTER DATABASE ${dbc_dbname} CHARACTER SET utf8mb4 COLLATE ${MYSQL_COLLATIONS};"



if [ -d "${APPDIR}/plugins/CheckmkModule/src" ]; then
    oitc checkmkNagiosExport --init
fi

echo "---------------------------------------------------------------"
echo "Create new WebSocket Key"
WEBSOCKET_KEY=$(php -r "echo bin2hex(openssl_random_pseudo_bytes(80, \$cstrong));")
mysql "--defaults-extra-file=${INIFILE}" -e "UPDATE systemsettings SET \`systemsettings\`.\`value\`='${WEBSOCKET_KEY}' WHERE \`key\`='SUDO_SERVER.API_KEY';"

oitc config_generator_shell --generate-container

echo "---------------------------------------------------------------"
echo "Configure Grafana"

echo $OITC_GRAFANA_ADMIN_PASSWORD > /opt/openitc/etc/grafana/admin_password

if [[ -d /opt/openitc/frontend/plugins/GrafanaModule ]]; then
    oitc GrafanaModule.service_account --grafana-hostname "$OITC_GRAFANA_HOSTNAME" --grafana-url "$OITC_GRAFANA_URL" --graphite-web-host "$OITC_GRAPHITE_WEB_ADDRESS" --graphite-web-port "$OITC_GRAPHITE_WEB_PORT" --victoria-metrics-host "$VICTORIA_METRICS_HOST" --victoria-metrics-port "$VICTORIA_METRICS_PORT"
fi

echo "---------------------------------------------------------------"
echo "Scan and import ACL objects. This will take a while..."
oitc acl.aco_sync

oitc setup --fast

oitc nagios_export

if [[ -d "/opt/openitc/nagios/rollout" ]]; then
    if [[ ! -f "/opt/openitc/nagios/rollout/resource.cfg" ]]; then
        ln -s /opt/openitc/nagios/etc/resource.cfg /opt/openitc/nagios/rollout/resource.cfg
    fi
fi

echo "Enabling webserver configuration"
if [[ ! -f "/etc/nginx/sites-enabled/openitc" ]]; then
    ln -s /etc/nginx/sites-available/openitc /etc/nginx/sites-enabled/openitc
fi
rm -f /etc/nginx/sites-enabled/default

#Set default permissions, check for always allowed permissions and dependencies
oitc roles --enable-defaults --admin

date > /opt/openitc/var/.installation_done
