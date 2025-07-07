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
  echo "--no-restart         Do not restart services at the end of the update"
  echo "--no-system-files    Do not copy files from system folder like nginx or php-fpm configs"

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
  --ignore-table=$dbc_dbname.statusengine_host_notifications_log \
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
  --ignore-table=$dbc_dbname.statusengine_service_notifications_log \
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

echo "Update Containertype from Devicegroup to Node"
mysql "--defaults-extra-file=$INIFILE" -e "UPDATE containers SET containertype_id=5 WHERE containertype_id=4"

# Update column length of logentry_data ITC-2551 and Statusengine 3.7.3
echo "Checking column length of statusengine_logentries table"
CURRENT_LOGENTRY_COLUMN_TYPE=$(mysql "--defaults-extra-file=$INIFILE" -e "SELECT COLUMN_TYPE FROM \`information_schema\`.\`COLUMNS\` WHERE \`TABLE_SCHEMA\`='${dbc_dbname}' AND \`TABLE_NAME\`='statusengine_logentries' AND \`COLUMN_NAME\`='logentry_data'" -B -s 2>/dev/null)
if [ "$CURRENT_LOGENTRY_COLUMN_TYPE" = "varchar(255)" ]; then
    echo "Increasing column length of statusengine_logentries table. This will take a while..."

    # Update statusengine_logentries table to use varchar(2048)
    mysql --defaults-extra-file=${INIFILE} -e "DROP INDEX logentries ON statusengine_logentries"
    mysql --defaults-extra-file=${INIFILE} -e "DROP INDEX logentry_data_time ON statusengine_logentries"
    mysql --defaults-extra-file=${INIFILE} -e "ALTER TABLE statusengine_logentries CHANGE logentry_data logentry_data VARCHAR(2048) DEFAULT NULL"
    mysql --defaults-extra-file=${INIFILE} -e "CREATE INDEX logentries_se ON statusengine_logentries (entry_time, node_name)"
fi

echo "Checking column length of statusengine_hoststatus table"
CURRENT_LOGENTRY_COLUMN_TYPE=$(mysql "--defaults-extra-file=$INIFILE" -e "SELECT COLUMN_TYPE FROM \`information_schema\`.\`COLUMNS\` WHERE \`TABLE_SCHEMA\`='${dbc_dbname}' AND \`TABLE_NAME\`='statusengine_hoststatus' AND \`COLUMN_NAME\`='long_output'" -B -s 2>/dev/null)
if [ "$CURRENT_LOGENTRY_COLUMN_TYPE" = "varchar(1024)" ]; then
    echo "Increasing column length of statusengine_hoststatus table. This will take a while..."

    # Perfdata is 2048 because of bionic
    # Row size too large. The maximum row size for the used table type, not counting BLOBs, is 65535. This includes storage overhead, check the manual. You have to change some columns to TEXT or BLOBs
    mysql --defaults-extra-file=${INIFILE} -e "ALTER TABLE statusengine_hoststatus CHANGE long_output long_output VARCHAR(8192) DEFAULT NULL, CHANGE perfdata perfdata VARCHAR(2048) DEFAULT NULL"
fi

echo "Checking column length of statusengine_servicestatus table"
CURRENT_LOGENTRY_COLUMN_TYPE=$(mysql "--defaults-extra-file=$INIFILE" -e "SELECT COLUMN_TYPE FROM \`information_schema\`.\`COLUMNS\` WHERE \`TABLE_SCHEMA\`='${dbc_dbname}' AND \`TABLE_NAME\`='statusengine_servicestatus' AND \`COLUMN_NAME\`='long_output'" -B -s 2>/dev/null)
if [ "$CURRENT_LOGENTRY_COLUMN_TYPE" = "varchar(1024)" ]; then
    echo "Increasing column length of statusengine_servicestatus table. This will take a while..."

    # Perfdata is 2048 because of bionic
    # Row size too large. The maximum row size for the used table type, not counting BLOBs, is 65535. This includes storage overhead, check the manual. You have to change some columns to TEXT or BLOBs
    mysql --defaults-extra-file=${INIFILE} -e "ALTER TABLE statusengine_servicestatus CHANGE long_output long_output VARCHAR(8192) DEFAULT NULL, CHANGE perfdata perfdata VARCHAR(2084) DEFAULT NULL"
fi

# Upgrade to Checkmk 2 in Docker Container
mysql --defaults-extra-file=${INIFILE} -e "UPDATE commands SET command_line = '\$USER1\$/checkmk_http_client -H \$HOSTNAME\$' WHERE name = 'check_mk_active' AND command_line LIKE 'PYTHONPATH=/opt/openitc/check_mk/lib/python OMD_ROOT=/opt/openitc/check_mk%';"


#Check and create missing cronjobs
#oitc api --model Cronjob --action create_missing_cronjobs --data ""

#Compress and minify javascript files
oitc compress

#Acc ALC dependencies config for itc core
echo "---------------------------------------------------------------"
echo "Scan for new user permissions. This will take a while..."
oitc Acl.acl_extras aco_sync

#Set default permissions, check for always allowed permissions and dependencies
oitc roles --enable-defaults --admin

#Check for browser push notification commands
echo "Check for browser push notification commands"
#oitc api --model Commands --action addByUuid --ignore-errors 1 --data 'host-notify-by-browser-notification' '/opt/openitc/frontend/bin/cake send_push_notification --type Host --notificationtype $NOTIFICATIONTYPE$ --hostuuid "$HOSTNAME$" --state "$HOSTSTATEID$" --output "$HOSTOUTPUT$"  --ackauthor "$NOTIFICATIONAUTHOR$" --ackcomment "$NOTIFICATIONCOMMENT$" --user-id $_CONTACTOITCUSERID$' '3' 'cd13d22e-acd4-4a67-997b-6e120e0d3153' 'Send a host notification to the browser window'
#oitc api --model Commands --action addByUuid --ignore-errors 1 --data 'service-notify-by-browser-notification' '/opt/openitc/frontend/bin/cake send_push_notification --type Service --notificationtype $NOTIFICATIONTYPE$ --hostuuid "$HOSTNAME$" --serviceuuid "$SERVICEDESC$" --state "$SERVICESTATEID$" --output "$SERVICEOUTPUT$" --ackauthor "$NOTIFICATIONAUTHOR$" --ackcomment "$NOTIFICATIONCOMMENT$" --user-id $_CONTACTOITCUSERID$' '3' 'c23255b7-5b1a-40b4-b614-17837dc376af ' 'Send a service notification to the browser window'

#Generate documentation
#oitc docu_generator
#oitc systemsettings_import

#Migrate openITCOCKPIT Monitoring Agent 1.x database records for 3.x
echo "Migrate openITCOCKPIT Monitoring Agent configuration for Agent version 3.x. This will take a while..."
oitc agent --migrate

echo "Checking that a server certificate for the openITCOCKPIT Monitoring Agent exists"
oitc agent --generate-server-ca

# ITC-2800 ITC-2819
#echo "Apply strict checking of host group assignments by container permissions"
#oitc HostgroupContainerPermissions

# ITC-1911
echo "Cleanup for invalid parent hosts on satellite instance"
oitc ParentHostsVisibilityCleaning

NORESTART=false
NOSYSTEMFILES=false
for i in "$@"; do
  case $i in
  --cc)
    echo "Clear out Model Cache /opt/openitc/frontend/tmp/cache/models/"
    rm -rf /opt/openitc/frontend/tmp/cache/models/*
    echo "Clear out CLI Model Cache /opt/openitc/frontend/tmp/cli/cache/cli/models/"
    rm -rf /opt/openitc/frontend/tmp/cli/cache/cli/models/*
    echo "Clear out Nagios Model Cache /opt/openitc/frontend/tmp/nagios/cache/nagios/models/"
    rm -rf /opt/openitc/frontend/tmp/nagios/cache/nagios/models/*
    ;;

  --rights)
    oitc rights
    ;;

  --no-restart)
    NORESTART=true
    ;;

    --no-system-files)
    NOSYSTEMFILES=true
    ;;

  *)
    #No default at the moment
    ;;
  esac
done

echo "Flush redis cache"
redis-cli FLUSHALL
echo ""

if [[ "$NORESTART" == "true" ]]; then
  echo "#########################################"
  echo "# RESTART OF SERVICES MANUALLY DISABLED #"
  echo "#########################################"
  echo ""
  echo "Update successfully finished"
  exit 0
fi

OSVERSION=$(grep VERSION_CODENAME /etc/os-release | cut -d= -f2)
OS_BASE="debian"

if [[ -f "/etc/redhat-release" ]]; then
    echo "Detected RedHat based operating system."
    OS_BASE="RHEL"
    OSVERSION=$(source /etc/os-release && echo $VERSION_ID | cut -d. -f1) # e.g. 8
fi

PHPVersion=$(php -r "echo substr(PHP_VERSION, 0, 3);")

if [[ "$NOSYSTEMFILES" == "false" ]]; then
  echo "Copy required system files"
  rsync -K -a ${APPDIR}/system/etc/. /etc/
  chown root:root /etc
  cp -r ${APPDIR}/system/lib/. /lib/
  cp -r ${APPDIR}/system/usr/. /usr/
  cp ${APPDIR}/system/nginx/ssl_options_$OSVERSION /etc/nginx/openitc/ssl_options.conf
  # only ensure that the files exist
  touch /etc/nginx/openitc/ssl_cert.conf
  touch /etc/nginx/openitc/custom.conf
fi

chmod +x /usr/bin/oitc

echo "Create required system folders"
mkdir -p /opt/openitc/etc/{mysql,grafana,carbon,frontend,nagios,nsta,statusengine} /opt/openitc/etc/statusengine/Config
mkdir -p /opt/openitc/etc/mod_gearman
mkdir -p /opt/openitc/logs/mod_gearman

mkdir -p /opt/openitc/logs/frontend/nagios
chown www-data:www-data /opt/openitc/logs/frontend
chown nagios:nagios /opt/openitc/logs/frontend/nagios
chmod 775 /opt/openitc/logs/frontend
chmod 775 /opt/openitc/logs/frontend/nagios
chown nagios:nagios /opt/openitc/logs/mod_gearman

mkdir -p /opt/openitc/logs/nagios/archives
chown nagios:www-data /opt/openitc/logs/nagios /opt/openitc/logs/nagios/archives
chmod 775 /opt/openitc/logs/nagios /opt/openitc/logs/nagios/archives

mkdir -p /opt/openitc/frontend/tmp/nagios
chown www-data:www-data /opt/openitc/frontend/tmp
chown nagios:nagios -R /opt/openitc/frontend/tmp/nagios

chmod u+s /opt/openitc/nagios/libexec/check_icmp
chmod u+s /opt/openitc/nagios/libexec/check_dhcp

mkdir -p /opt/openitc/etc/nagios/nagios.cfg.d

mkdir -p /opt/openitc/frontend/webroot/img/charts
chown www-data:www-data /opt/openitc/frontend/webroot/img/charts

mkdir -p /opt/openitc/var/prometheus
chown nagios:nagios /opt/openitc/var/prometheus
mkdir -p /opt/openitc/var/prometheus/victoria-metrics

# ITC-3125 Fix file permissions after an (possible) update of Nginx
if [[ "$OS_BASE" == "RHEL" ]]; then
    chown www-data:root /var/lib/nginx -R
    chown www-data:root /var/lib/nginx/tmp -R
fi

if [[ -d /opt/openitc/frontend/plugins/MapModule/webroot/img/ ]]; then
    chown -R www-data:www-data /opt/openitc/frontend/plugins/MapModule/webroot/img/
fi

if getent group ssl-cert &>/dev/null; then
    if [ -z "$(groups nagios | grep ssl-cert)" ]; then
        usermod -aG ssl-cert nagios
    fi
fi

oitc config_generator_shell --generate
oitc nagios_export --resource

if [[ -d "/opt/openitc/nagios/rollout" ]]; then
    if [[ ! -f "/opt/openitc/nagios/rollout/resource.cfg" ]]; then
        ln -s /opt/openitc/nagios/etc/resource.cfg /opt/openitc/nagios/rollout/resource.cfg
    fi
fi

# ITC-3479
#if [[ -d /opt/openitc/frontend/plugins/GrafanaModule ]]; then
#    set +e
#    oitc GrafanaModule.service_account
#    set -e
#fi

if [ ! -f /opt/openitc/etc/mod_gearman/secret.file ]; then
    echo "Generate new shared secret for Mod-Gearman"
    MG_KEY=$(php -r "echo bin2hex(openssl_random_pseudo_bytes(16, \$cstrong));")
    echo $MG_KEY > /opt/openitc/etc/mod_gearman/secret.file
fi
chown nagios:nagios /opt/openitc/etc/mod_gearman/secret.file
chmod 400 /opt/openitc/etc/mod_gearman/secret.file

if [ ! -f /opt/openitc/nagios/libexec/check_gearman ]; then
    if [ -f /opt/openitc/mod_gearman/bin/check_gearman ]; then
        echo "Copy check_gearman plugin"
        cp /opt/openitc/mod_gearman/bin/check_gearman /opt/openitc/nagios/libexec/check_gearman
        chmod +x /opt/openitc/nagios/libexec/check_gearman
    fi
fi

echo "Enable new systemd services"
systemctl daemon-reload
systemctl enable sudo_server.service\
 oitc_cmd.service\
 gearman_worker.service\
 push_notification.service\
 openitcockpit-node.service\
 openitcockpit-graphing.service\
 oitc_cronjobs.timer\
 statusengine.service

systemctl restart\
 sudo_server.service\
 oitc_cmd.service\
 gearman_worker.service\
 push_notification.service\
 openitcockpit-node.service\
 oitc_cronjobs.timer\
 statusengine.service

echo "Detected PHP Version: ${PHPVersion} try to restart php-fpm"

echo "Restart monitoring engine"
systemctl restart nagios.service

# Restart services if they are running
for srv in openitcockpit-graphing.service nginx.service nsta.service event-collectd.service mod-gearman-worker.service; do
  if systemctl is-active --quiet $srv; then
    echo "Restart service: $srv"
    systemctl restart $srv
  fi
done

set +e
systemctl is-enabled --quiet php${PHPVersion}-fpm.service &>/dev/null
RC=$?
if [ $RC -eq 0 ]; then
  #Is it php7.3-fpm-service ?
  systemctl restart php${PHPVersion}-fpm.service
else
  # Is it just php-fpm.service?
  systemctl is-enabled --quiet php-fpm.service
  RC=$?
  if [ $RC -eq 0 ]; then
    systemctl restart php-fpm.service
  else
    echo "ERROR: could not detect php-fpm systemd service file. You need to restart php-fpm manualy"
  fi
fi
set -e

if [[ -d /opt/openitc/frontend/plugins/GrafanaModule ]]; then
    # ITC-3479 In case we migrate from SQLite to MySQL
    set +e

    echo "Waiting 3 seconds for Grafana..."
    sleep 3

    oitc GrafanaModule.service_account
    set -e
fi

echo "Cleanup old Docker images"
if systemctl is-active --quiet docker.service; then
    docker image prune --force
    echo "Docker cleanup complete"
else
    echo "Docker is NOT Running";
    echo "Please start Docker and run the following command manually"
    echo "docker image prune -a --force"
fi

for Module in "${LOADED_MODULE_SCRIPTS[@]}"; do
    if [ "$Module" != "system" ]; then
        if type "${Module}" &> /dev/null; then
            # create class object
            ${Module} module

            # set required variables
            module.property name = "${Module}"
            module.property dbIniFile = "${Module}"

            module.finish
        fi
    fi
done

# Set filesystem permissions after all is done - again
chown www-data:www-data /opt/openitc/logs/frontend
oitc rights
chown nagios:nagios /opt/openitc/logs/frontend/nagios
chown www-data:www-data /opt/openitc/frontend/
chmod 775 /opt/openitc/logs/frontend
chmod 775 /opt/openitc/logs/frontend/nagios
chown www-data:www-data /opt/openitc/frontend/tmp
chown nagios:nagios -R /opt/openitc/frontend/tmp/nagios
chmod u+s /opt/openitc/nagios/libexec/check_icmp
chmod u+s /opt/openitc/nagios/libexec/check_dhcp
