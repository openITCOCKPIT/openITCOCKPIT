<?php

use Doctrine\DBAL\Schema\Schema;


require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap.php';
$schema = new Schema();

/****************************************
 * Define: statusengine_dbversion
 ***************************************/
$table = $schema->createTable("statusengine_dbversion");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("id", "integer", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("dbversion", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '3.0.0',
    'length' => 255,
));
$table->setPrimaryKey([
    "id"
]);



/****************************************
 * Define: statusengine_host_acknowledgements
 ***************************************/
$table = $schema->createTable("statusengine_host_acknowledgements");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("entry_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("author_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("comment_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("acknowledgement_type", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_sticky", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("persistent_comment", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("notify_contacts", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->setPrimaryKey([
    "hostname",
    "entry_time",
    "entry_time_usec"
]);
$table->addIndex([
    "hostname"
], "hostname");
$table->addIndex([
    "entry_time"
], "entry_time");



/****************************************
 * Define: statusengine_host_downtimehistory
 ***************************************/
$table = $schema->createTable("statusengine_host_downtimehistory");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("internal_downtime_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("scheduled_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("entry_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("author_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("comment_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("triggered_by_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("is_fixed", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("duration", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("scheduled_end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("was_started", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("actual_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("actual_end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("was_cancelled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->setPrimaryKey([
    "hostname",
    "node_name",
    "scheduled_start_time",
    "internal_downtime_id"
]);
$table->addIndex([
    "hostname",
    "entry_time",
    "entry_time_usec",
    "scheduled_start_time",
    "scheduled_end_time",
    "was_cancelled"
], "reports");
$table->addIndex([
    "hostname",
    "scheduled_start_time",
    "scheduled_end_time",
    "was_cancelled"
], "list");



/****************************************
 * Define: statusengine_host_notifications
 ***************************************/
/*
$table = $schema->createTable("statusengine_host_notifications");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("start_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("contact_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("command_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("command_args", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("reason_type", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("ack_author", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("ack_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->setPrimaryKey([
    "hostname",
    "start_time",
    "start_time_usec"
]);
$table->addIndex([
    "hostname"
], "hostname");
$table->addIndex([
    "start_time"
], "start_time");
*/


/****************************************
 * Define: statusengine_host_scheduleddowntimes
 ***************************************/
$table = $schema->createTable("statusengine_host_scheduleddowntimes");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("internal_downtime_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("scheduled_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("author_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("comment_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("triggered_by_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("is_fixed", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("duration", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("scheduled_end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("was_started", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("actual_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->setPrimaryKey([
    "hostname",
    "node_name",
    "scheduled_start_time",
    "internal_downtime_id"
]);



/****************************************
 * Define: statusengine_host_statehistory
 ***************************************/
/*
$table = $schema->createTable("statusengine_host_statehistory");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("state_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("state_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("state_change", "boolean", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_hardstate", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("current_check_attempt", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("max_check_attempts", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_hard_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("long_output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->setPrimaryKey([
    "hostname",
    "state_time",
    "state_time_usec"
]);
$table->addIndex([
    "hostname",
    "state_time"
], "hostname_time");
*/


/****************************************
 * Define: statusengine_hostchecks
 ***************************************/
/*
$table = $schema->createTable("statusengine_hostchecks");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("start_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_hardstate", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("timeout", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("early_timeout", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("latency", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("execution_time", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("perfdata", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->addColumn("command", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("current_check_attempt", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("max_check_attempts", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("long_output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->setPrimaryKey([
    "hostname",
    "start_time",
    "start_time_usec"
]);
$table->addIndex([
    "start_time",
    "end_time"
], "times");
$table->addIndex([
    "hostname",
    "start_time"
], "hostname");
*/


/****************************************
 * Define: statusengine_hoststatus
 ***************************************/
$table = $schema->createTable("statusengine_hoststatus");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("status_update_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("long_output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->addColumn("perfdata", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 2048,
));
$table->addColumn("current_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("current_check_attempt", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("max_check_attempts", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_check", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("next_check", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("is_passive_check", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_state_change", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_hard_state_change", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_hard_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_hardstate", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_notification", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("next_notification", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("notifications_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("problem_has_been_acknowledged", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("acknowledgement_type", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("passive_checks_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("active_checks_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("event_handler_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("flap_detection_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_flapping", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("latency", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("execution_time", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("scheduled_downtime_depth", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("process_performance_data", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("obsess_over_host", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("normal_check_interval", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("retry_check_interval", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("check_timeperiod", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("last_time_up", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_time_down", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_time_unreachable", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("current_notification_number", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("percent_state_change", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("event_handler", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("check_command", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->setPrimaryKey([
    "hostname"
]);
$table->addIndex([
    "current_state",
    "node_name"
], "current_state_node");
$table->addIndex([
    "problem_has_been_acknowledged",
    "scheduled_downtime_depth",
    "current_state"
], "issues");


/****************************************
 * Define: statusengine_logentries
 ***************************************/
/*
$table = $schema->createTable("statusengine_logentries");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("id", "bigint", array (
    'unsigned' => true,
    'autoincrement' => true,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("logentry_type", "integer", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("logentry_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 2048,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->setPrimaryKey([
    "id"
]);
$table->addIndex([
    "entry_time",
    "node_name"
], "logentries_se");
*/


/****************************************
 * Define: statusengine_nodes
 ***************************************/
$table = $schema->createTable("statusengine_nodes");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("node_version", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("node_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->setPrimaryKey([
    "node_name"
]);



/****************************************
 * Define: statusengine_perfdata
 ***************************************/
$table = $schema->createTable("statusengine_perfdata");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("label", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("timestamp", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("timestamp_unix", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("value", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("unit", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 10,
));
$table->addIndex([
    "hostname",
    "service_description",
    "label",
    "timestamp_unix"
], "metric");
$table->addIndex([
    "timestamp_unix"
], "timestamp_unix");



/****************************************
 * Define: statusengine_service_acknowledgements
 ***************************************/
$table = $schema->createTable("statusengine_service_acknowledgements");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("entry_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("author_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("comment_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("acknowledgement_type", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_sticky", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("persistent_comment", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("notify_contacts", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->setPrimaryKey([
    "service_description",
    "entry_time",
    "entry_time_usec"
]);
$table->addIndex([
    "hostname",
    "service_description"
], "servicename");
$table->addIndex([
    "entry_time"
], "entry_time");
$table->addIndex([
    "service_description",
    "entry_time"
], "servicedesc_time");



/****************************************
 * Define: statusengine_service_downtimehistory
 ***************************************/
$table = $schema->createTable("statusengine_service_downtimehistory");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("internal_downtime_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("scheduled_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("entry_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("author_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("comment_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("triggered_by_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("is_fixed", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("duration", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("scheduled_end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("was_started", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("actual_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("actual_end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("was_cancelled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->setPrimaryKey([
    "hostname",
    "service_description",
    "node_name",
    "scheduled_start_time",
    "internal_downtime_id"
]);
$table->addIndex([
    "service_description",
    "entry_time",
    "entry_time_usec",
    "scheduled_start_time",
    "scheduled_end_time",
    "was_cancelled"
], "reports");
$table->addIndex([
    "service_description",
    "scheduled_start_time",
    "scheduled_end_time",
    "was_cancelled"
], "report");



/****************************************
 * Define: statusengine_service_notifications
 ***************************************/
/*
$table = $schema->createTable("statusengine_service_notifications");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("start_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("contact_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("command_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("command_args", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("reason_type", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("ack_author", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("ack_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->setPrimaryKey([
    "service_description",
    "start_time",
    "start_time_usec"
]);
$table->addIndex([
    "hostname",
    "service_description"
], "servicename");
$table->addIndex([
    "start_time"
], "start_time");
*/


/****************************************
 * Define: statusengine_service_scheduleddowntimes
 ***************************************/
$table = $schema->createTable("statusengine_service_scheduleddowntimes");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("internal_downtime_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("scheduled_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("author_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("comment_data", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("triggered_by_id", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("is_fixed", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("duration", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
));
$table->addColumn("scheduled_end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("was_started", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("actual_start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->setPrimaryKey([
    "hostname",
    "service_description",
    "node_name",
    "scheduled_start_time",
    "internal_downtime_id"
]);



/****************************************
 * Define: statusengine_service_statehistory
 ***************************************/
/*
$table = $schema->createTable("statusengine_service_statehistory");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("state_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("state_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("state_change", "boolean", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_hardstate", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("current_check_attempt", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("max_check_attempts", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_hard_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("long_output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->setPrimaryKey([
    "service_description",
    "state_time",
    "state_time_usec"
]);
$table->addIndex([
    "hostname",
    "service_description",
    "state_time"
], "host_servicename_time");
$table->addIndex([
    "service_description",
    "state_time"
], "servicename_time");
*/


/****************************************
 * Define: statusengine_servicechecks
 ***************************************/
/*
$table = $schema->createTable("statusengine_servicechecks");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("start_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("start_time_usec", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => true,
    'default' => '0',
));
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_hardstate", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("end_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("timeout", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("early_timeout", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("latency", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("execution_time", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("perfdata", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->addColumn("command", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("current_check_attempt", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("max_check_attempts", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("long_output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->setPrimaryKey([
    "service_description",
    "start_time",
    "start_time_usec"
]);
$table->addIndex([
    "hostname",
    "service_description",
    "start_time"
], "servicename");
*/


/****************************************
 * Define: statusengine_servicestatus
 ***************************************/
$table = $schema->createTable("statusengine_servicestatus");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("hostname", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("service_description", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("status_update_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 1024,
));
$table->addColumn("long_output", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->addColumn("perfdata", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 2048,
));
$table->addColumn("current_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("current_check_attempt", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("max_check_attempts", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_check", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("next_check", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("is_passive_check", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_state_change", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_hard_state_change", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_hard_state", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_hardstate", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("last_notification", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("next_notification", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("notifications_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("problem_has_been_acknowledged", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("acknowledgement_type", "smallint", [
    'unsigned'      => true,
    'autoincrement' => false,
    'notnull'       => false,
    'default'       => '0',
]);
$table->addColumn("passive_checks_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("active_checks_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("event_handler_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("flap_detection_enabled", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("is_flapping", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("latency", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("execution_time", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("scheduled_downtime_depth", "smallint", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("process_performance_data", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("obsess_over_service", "boolean", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("normal_check_interval", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("retry_check_interval", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("check_timeperiod", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("last_time_ok", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_time_warning", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_time_critical", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("last_time_unknown", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("current_notification_number", "integer", array (
    'unsigned' => true,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("percent_state_change", "float", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => '0',
));
$table->addColumn("event_handler", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("check_command", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->setPrimaryKey([
    "hostname",
    "service_description"
]);
$table->addIndex([
    "service_description"
], "service_description");
$table->addIndex([
    "current_state",
    "node_name"
], "current_state_node");
$table->addIndex([
    "problem_has_been_acknowledged",
    "scheduled_downtime_depth",
    "current_state"
], "issues");



/****************************************
 * Define: statusengine_tasks
 ***************************************/
$table = $schema->createTable("statusengine_tasks");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("uuid", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("node_name", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("entry_time", "bigint", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => true,
    'default' => NULL,
));
$table->addColumn("type", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("payload", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 8192,
));
$table->addIndex([
    "uuid"
], "uuid");
$table->addIndex([
    "node_name"
], "node_name");



/****************************************
 * Define: statusengine_users
 ***************************************/
$table = $schema->createTable("statusengine_users");
$table->addOption("engine" , "InnoDB");
$table->addOption("collate" , "utf8mb4_general_ci");
$table->addOption("charset" , "utf8mb4");
$table->addOption("comment" , "");
$table->addColumn("username", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addColumn("password", "string", array (
    'unsigned' => false,
    'autoincrement' => false,
    'notnull' => false,
    'default' => NULL,
    'length' => 255,
));
$table->addIndex([
    "username",
    "password"
], "username");



return $schema;
