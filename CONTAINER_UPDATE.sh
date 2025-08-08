#!/bin/bash
#
# Copyright (C) <2015-present>  <it-novum GmbH>
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

if [[ $1 == "--help" ]]; then
  echo "Supported parameters:"
  echo "--rights             Reset file permissions"
  echo "--cc                 Clear model cache"

  exit 0
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

if [[ ! -f "$BASHCONF" ]]; then
  MYSQL_USER=openitcockpit
  MYSQL_DATABASE=openitcockpit
  MYSQL_PASSWORD=
  MYSQL_HOST=localhost
  MYSQL_PORT=3306
  eval $(php -r "require '$APPDIR/src/itnovum/openITCOCKPIT/Database/MysqlConfigFileParserForCli.php'; \$mcp = new MysqlConfigFileParserForCli(); \$r = \$mcp->parse_mysql_cnf('/opt/openitc/etc/mysql/mysql.cnf'); echo \$r['shell'];")

  echo "dbc_dbuser='${MYSQL_USER}'" >$BASHCONF
  echo "dbc_dbpass='${MYSQL_PASSWORD}'" >>$BASHCONF
  echo "dbc_dbserver='${MYSQL_HOST}'" >>$BASHCONF
  echo "dbc_dbport='${MYSQL_PORT}'" >>$BASHCONF
  echo "dbc_dbname='${MYSQL_DATABASE}'" >>$BASHCONF
fi

. /opt/openitc/etc/mysql/bash.conf

echo "Copy required system files"
rsync -K -a ${APPDIR}/system/etc/. /etc/ # we use rsync because the destination can be a symlink on RHEL
chown root:root /etc
cp -r ${APPDIR}/system/usr/. /usr/
cp ${APPDIR}/system/nginx/ssl_options_$OSVERSION /etc/nginx/openitc/ssl_options.conf
cp ${APPDIR}/system/nginx/ssl_cert.conf /etc/nginx/openitc/ssl_cert.conf
chmod +x /usr/bin/oitc

echo "Create mysqldump of your current database"
BACKUP_TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
BACKUP_DIR='/opt/openitc/nagios/backup'
mkdir -p $BACKUP_DIR
#If you have mysql binlog enabled uses this command:
#mysqldump --defaults-extra-file=${DUMPINIFILE} --databases $dbc_dbname --flush-privileges --single-transaction --master-data=1 --flush-logs --triggers --routines --events --hex-blob \

# ITC-2921
# MySQL Bug: https://bugs.mysql.com/bug.php?id=109685
# As with mysqldump 8.0.32 --single-transaction requires RELOAD or FLUSH_TABLES privilege(s) which the openitcockpit user does not have
# So for now the workaround is to remove "--single-transaction"
mysqldump --defaults-extra-file=${DUMPINIFILE} --databases $dbc_dbname --flush-privileges --triggers --routines --no-tablespaces --events --hex-blob \
  --ignore-table=$dbc_dbname.nagios_acknowledgements \
  --ignore-table=$dbc_dbname.nagios_commands \
  --ignore-table=$dbc_dbname.nagios_commenthistory \
  --ignore-table=$dbc_dbname.nagios_comments \
  --ignore-table=$dbc_dbname.nagios_configfiles \
  --ignore-table=$dbc_dbname.nagios_configfilevariables \
  --ignore-table=$dbc_dbname.nagios_conninfo \
  --ignore-table=$dbc_dbname.nagios_contact_addresses \
  --ignore-table=$dbc_dbname.nagios_contact_notificationcommands \
  --ignore-table=$dbc_dbname.nagios_contactgroup_members \
  --ignore-table=$dbc_dbname.nagios_contactgroups \
  --ignore-table=$dbc_dbname.nagios_contactnotificationmethods \
  --ignore-table=$dbc_dbname.nagios_contactnotifications \
  --ignore-table=$dbc_dbname.nagios_contacts \
  --ignore-table=$dbc_dbname.nagios_contactstatus \
  --ignore-table=$dbc_dbname.nagios_customvariables \
  --ignore-table=$dbc_dbname.nagios_customvariablestatus \
  --ignore-table=$dbc_dbname.nagios_dbversion \
  --ignore-table=$dbc_dbname.nagios_downtimehistory \
  --ignore-table=$dbc_dbname.nagios_eventhandlers \
  --ignore-table=$dbc_dbname.nagios_externalcommands \
  --ignore-table=$dbc_dbname.nagios_flappinghistory \
  --ignore-table=$dbc_dbname.nagios_host_contactgroups \
  --ignore-table=$dbc_dbname.nagios_host_contacts \
  --ignore-table=$dbc_dbname.nagios_host_parenthosts \
  --ignore-table=$dbc_dbname.nagios_hostchecks \
  --ignore-table=$dbc_dbname.nagios_hostdependencies \
  --ignore-table=$dbc_dbname.nagios_hostescalation_contactgroups \
  --ignore-table=$dbc_dbname.nagios_hostescalation_contacts \
  --ignore-table=$dbc_dbname.nagios_hostescalations \
  --ignore-table=$dbc_dbname.nagios_hostgroup_members \
  --ignore-table=$dbc_dbname.nagios_hostgroups \
  --ignore-table=$dbc_dbname.nagios_hosts \
  --ignore-table=$dbc_dbname.nagios_hoststatus \
  --ignore-table=$dbc_dbname.nagios_instances \
  --ignore-table=$dbc_dbname.nagios_logentries \
  --ignore-table=$dbc_dbname.nagios_notifications \
  --ignore-table=$dbc_dbname.nagios_processevents \
  --ignore-table=$dbc_dbname.nagios_programstatus \
  --ignore-table=$dbc_dbname.nagios_runtimevariables \
  --ignore-table=$dbc_dbname.nagios_scheduleddowntime \
  --ignore-table=$dbc_dbname.nagios_service_contactgroups \
  --ignore-table=$dbc_dbname.nagios_service_contacts \
  --ignore-table=$dbc_dbname.nagios_service_parentservices \
  --ignore-table=$dbc_dbname.nagios_servicechecks \
  --ignore-table=$dbc_dbname.nagios_servicedependencies \
  --ignore-table=$dbc_dbname.nagios_serviceescalation_contactgroups \
  --ignore-table=$dbc_dbname.nagios_serviceescalation_contacts \
  --ignore-table=$dbc_dbname.nagios_serviceescalations \
  --ignore-table=$dbc_dbname.nagios_servicegroup_members \
  --ignore-table=$dbc_dbname.nagios_servicegroups \
  --ignore-table=$dbc_dbname.nagios_services \
  --ignore-table=$dbc_dbname.nagios_servicestatus \
  --ignore-table=$dbc_dbname.nagios_statehistory \
  --ignore-table=$dbc_dbname.nagios_systemcommands \
  --ignore-table=$dbc_dbname.nagios_timedeventqueue \
  --ignore-table=$dbc_dbname.nagios_timedevents \
  --ignore-table=$dbc_dbname.nagios_timeperiod_timeranges \
  --ignore-table=$dbc_dbname.nagios_timeperiods \
  --ignore-table=$dbc_dbname.statusengine_dbversion \
  --ignore-table=$dbc_dbname.statusengine_host_acknowledgements \
  --ignore-table=$dbc_dbname.statusengine_host_downtimehistory \
  --ignore-table=$dbc_dbname.statusengine_host_notifications \
  --ignore-table=$dbc_dbname.statusengine_host_scheduleddowntimes \
  --ignore-table=$dbc_dbname.statusengine_host_statehistory \
  --ignore-table=$dbc_dbname.statusengine_hostchecks \
  --ignore-table=$dbc_dbname.statusengine_hoststatus \
  --ignore-table=$dbc_dbname.statusengine_logentries \
  --ignore-table=$dbc_dbname.statusengine_nodes \
  --ignore-table=$dbc_dbname.statusengine_perfdata \
  --ignore-table=$dbc_dbname.statusengine_service_acknowledgements \
  --ignore-table=$dbc_dbname.statusengine_service_downtimehistory \
  --ignore-table=$dbc_dbname.statusengine_service_notifications \
  --ignore-table=$dbc_dbname.statusengine_service_scheduleddowntimes \
  --ignore-table=$dbc_dbname.statusengine_service_statehistory \
  --ignore-table=$dbc_dbname.statusengine_servicechecks \
  --ignore-table=$dbc_dbname.statusengine_servicestatus \
  --ignore-table=$dbc_dbname.statusengine_tasks \
  --ignore-table=$dbc_dbname.statusengine_users \
  --ignore-table=$dbc_dbname.customalerts \
  --ignore-table=$dbc_dbname.customalert_statehistory \
  --ignore-table=$dbc_dbname.sla_availability_status_hosts_log \
  --ignore-table=$dbc_dbname.sla_availability_status_services_log \
  --ignore-table=$dbc_dbname.sla_host_outages \
  --ignore-table=$dbc_dbname.sla_service_outages \
  >$BACKUP_DIR/openitcockpit_dump_$BACKUP_TIMESTAMP.sql

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

echo "Running openITCOCKPIT Core database migration"
oitc migrations migrate

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
echo "Import extended openitcockpit-update module scripts ..."

MODULE_SCRIPS_LOCK_DIR="/opt/openitc/var/updatesh_module_locks/"
MODULES_DIR="${APPDIR}/plugins/"
DEDICATED_MODULE_SCRIPTS_DIR_NAME="updateScripts"
LOADED_MODULE_SCRIPTS=()

# include module scripts class header
echo "Import ${APPDIR}/bin/updatesh_module_scripts/system.h.sh"
. ${APPDIR}/bin/updatesh_module_scripts/system.h.sh ${APPDIR}/bin/updatesh_module_scripts/
moduleList=($(ls -A1 $MODULES_DIR*/$DEDICATED_MODULE_SCRIPTS_DIR_NAME/*))
for entry in "${moduleList[@]}"
do
    if [[ $entry =~ \.h.sh$ ]]; then
        echo "Import $entry"
        shortName=`basename $entry`
        realName=${shortName/".h.sh"/""}
        path=${entry/$shortName/""}
        LOADED_MODULE_SCRIPTS+=($realName)
        . $entry $path
    fi
done

if type "system.property" &> /dev/null; then
    system.property lockDir = $MODULE_SCRIPS_LOCK_DIR
    system.property modulesDir = $MODULES_DIR
    system.initialize
fi

for Module in "${LOADED_MODULE_SCRIPTS[@]}"; do
    if [ "$Module" != "system" ]; then
        if type "${Module}" &> /dev/null; then
            # create class object
            ${Module} module

            # set required variables
            module.property name = "${Module}"
            module.property dbIniFile = "${Module}"

            module.initialize
        fi
    fi
done

echo "---------------------------------------------------------------"
echo "Convert MySQL Tables from utf8_general_ci to utf8mb4_general_ci..."

# Disabled - this takes ages!
#mysql --defaults-extra-file=${INIFILE} -e "ALTER DATABASE ${dbc_dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

mysql --defaults-extra-file=${INIFILE} --batch --skip-column-names -e "SELECT TABLE_NAME FROM \`information_schema\`.\`TABLES\` WHERE \`TABLE_SCHEMA\`='${dbc_dbname}' AND \`TABLE_NAME\` NOT LIKE 'nagios_%' AND \`TABLE_COLLATION\`='utf8_general_ci'" | while read TABLE_NAME; do
    echo "ALTER TABLE \`${TABLE_NAME}\` CONVERT TO CHARACTER SET utf8mb4; ✔"
    mysql --defaults-extra-file=${INIFILE} -e "ALTER TABLE \`${TABLE_NAME}\` CONVERT TO CHARACTER SET utf8mb4;"
done

#Compress and minify javascript files
oitc compress

#Acc ALC dependencies config for itc core
echo "---------------------------------------------------------------"
echo "Scan for new user permissions. This will take a while..."
oitc acl.aco_sync

#Set default permissions, check for always allowed permissions and dependencies
oitc roles --enable-defaults --admin

echo "Checking that a server certificate for the openITCOCKPIT Monitoring Agent exists"
oitc agent --generate-server-ca

# ITC-1911
echo "Cleanup for invalid parent hosts on satellite instance"
oitc ParentHostsVisibilityCleaning

for i in "$@"; do
  case $i in
  --cc)
    echo "Clear out Model Cache /opt/openitc/frontend/tmp/cache/models/"
    rm -rf /opt/openitc/frontend/tmp/cache/models/*
    echo "Clear out CLI Model Cache /opt/openitc/frontend/tmp/cli/cache/cli/models/"
    rm -rf /opt/openitc/frontend/tmp/cli/cache/cli/models/*
    ;;

  --rights)
    oitc rights
    ;;

  *)
    #No default at the moment
    ;;
  esac
done

echo "Flush redis cache"
redis-cli -h $OITC_REDIS_HOST -p $OITC_REDIS_PORT FLUSHALL
echo ""

echo "Copy required system files"
rsync -K -a ${APPDIR}/system/etc/. /etc/
chown root:root /etc
cp -r ${APPDIR}/system/usr/. /usr/
cp ${APPDIR}/system/nginx/ssl_options_$OSVERSION /etc/nginx/openitc/ssl_options.conf
# only ensure that the files exist
touch /etc/nginx/openitc/ssl_cert.conf
touch /etc/nginx/openitc/custom.conf


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

if getent group ssl-cert &>/dev/null; then
    if [ -z "$(groups nagios | grep ssl-cert)" ]; then
        usermod -aG ssl-cert nagios
    fi
fi

oitc config_generator_shell --generate-container
oitc nagios_export --resource

if [[ -d "/opt/openitc/nagios/rollout" ]]; then
    if [[ ! -f "/opt/openitc/nagios/rollout/resource.cfg" ]]; then
        ln -s /opt/openitc/nagios/etc/resource.cfg /opt/openitc/nagios/rollout/resource.cfg
    fi
fi

echo $OITC_GRAFANA_ADMIN_PASSWORD > /opt/openitc/etc/grafana/admin_password

if [[ -d /opt/openitc/frontend/plugins/GrafanaModule ]]; then
    oitc GrafanaModule.service_account --grafana-hostname "$OITC_GRAFANA_HOSTNAME" --grafana-url "$OITC_GRAFANA_URL" --graphite-web-host "$OITC_GRAPHITE_WEB_ADDRESS" --graphite-web-port "$OITC_GRAPHITE_WEB_PORT" --victoria-metrics-host "$VICTORIA_METRICS_HOST" --victoria-metrics-port "$VICTORIA_METRICS_PORT"
fi

echo "Restart monitoring engine"
oitc supervisor restart naemon

echo "Enabling webserver configuration"
if [[ ! -f "/etc/nginx/sites-enabled/openitc" ]]; then
    ln -s /etc/nginx/sites-available/openitc /etc/nginx/sites-enabled/openitc
fi
rm -f /etc/nginx/sites-enabled/default

set +e

# todo fix this
#for Module in "${LOADED_MODULE_SCRIPTS[@]}"; do
#    if [ "$Module" != "system" ]; then
#        if type "${Module}" &> /dev/null; then
#            # create class object
#            ${Module} module
#
#            # set required variables
#            module.property name = "${Module}"
#            module.property dbIniFile = "${Module}"
#
#            module.finish
#        fi
#    fi
#done

# Set filesystem permissions after all is done - again
#todo fix if realy required
#chown www-data:www-data /opt/openitc/logs/frontend
#oitc rights
#chown nagios:nagios /opt/openitc/logs/frontend/nagios
#chown www-data:www-data /opt/openitc/frontend/
#chmod 775 /opt/openitc/logs/frontend
#chmod 775 /opt/openitc/logs/frontend/nagios
#chown www-data:www-data /opt/openitc/frontend/tmp
