# Example configuration file for the Statusengine Go worker (cmd/app).
#
# @formatter:off
#
# Usage: go run ./cmd/app -config /path/to/config.yaml
#        (or STATUSENGINE_CONFIG=/path/to/config.yaml go run ./cmd/app)
#
# Every key below is optional - omit anything you don't want to override.
# Precedence, for every setting, is:
#
#   explicit CLI flag  >  environment variable  >  this file  >  built-in default
#
# So this file is the place for your "boring", rarely-changing settings,
# while flags/environment variables (handy in Docker/CI) can still
# override anything here for a one-off run without editing the file.
#
# All values below are shown with their built-in default.

# Queue backend to consume from: "gearman" or "rabbitmq".
consumer: gearman

# Gearman job server address (host:port). Only used when consumer: gearman.
gearman_addr: 127.0.0.1:4730

# Maximum number of Gearman job handlers running at once, PER QUEUE. This
# is not a throughput knob: the library dispatches every assigned job on
# its own goroutine and grabs the next without waiting, and a handler
# blocks on MySQL once a bulk-insert buffer is full. Without a cap, a
# backlog accumulates inside the worker (one goroutine plus a decoded
# payload per job) instead of staying queued at the job server.
#
# Per queue, because the worker opens one Gearman connection per queue and
# each has a Work loop of its own. A shared budget is a budget that one
# backlogged queue takes all of - and since that loop stops reading its
# socket once the budget is gone, every other queue stops being served
# entirely, not merely slowly. The process-wide worst case is therefore
# this value times the number of queues (12), so 8 here is 96 overall. An
# idle queue costs nothing; a cap only binds when there is traffic.
#
# Must be >= 1. NOTE: this replaces the old `gearman_max_concurrent_jobs`,
# which was a single budget shared by all queues. The worker refuses to
# start if that key is still present rather than reinterpreting its value,
# because carrying a 64 over would mean 768 concurrent handlers here.
gearman_max_concurrent_jobs_per_queue: {{gearman_max_concurrent_jobs_per_queue}}

# RabbitMQ broker URL (amqp://user:pass@host:port/vhost). Only used when
# consumer: rabbitmq.
rabbitmq_url: amqp://statusengine:statusengine@127.0.0.1:5672/

# Maximum unacknowledged deliveries the broker may push, per queue. The
# RabbitMQ counterpart of gearman_max_concurrent_jobs_per_queue: without it AMQP
# delivers as fast as it can and the whole backlog ends up buffered in
# this process. Applies per queue, so the worst case in memory is this
# value times the number of queues (currently 12). Must be >= 1.
rabbitmq_prefetch: 100

# MySQL data source name (go-sql-driver/mysql format).
mysql_dsn: {{mysql_user}}:{{mysql_password}}@tcp({{mysql_host}}:3306)/{{mysql_database}}?parseTime=true

# Maximum number of open MySQL connections, also used as the idle-connection
# limit so the pool doesn't tear down and redial connections between the
# bulk-inserters' 250ms flush ticks. Keep this below the server's
# max_connections, and remember it applies per worker process.
mysql_max_open_conns: {{mysql_max_open_conns}}

# Rows buffered per table before a bulk INSERT is flushed ahead of the
# 250ms ticker. The default of 100 is conservative; 700 is the documented
# production value and also the maximum.
#
# The ceiling is not a matter of taste. Every flush is sent as a server-side
# prepared statement, and those are limited to 65535 placeholders. What has
# to fit is not this number of rows but roughly twice it: on a shutdown or
# core-restart flush the buffer is topped up with whatever is still in
# flight, and all of it becomes one statement. At 700 the widest table
# (statusengine_servicestatus, 43 columns) needs 1399 x 43 = 60157 of the
# 65535 - with room for three more columns. At 1000 it would need 85957 and
# fail with "Error 1390: Prepared statement contains too many placeholders",
# which is deterministic, therefore never retried, and would drop every
# batch: the two status tables would silently stop being written.
#
# Raising this only matters under sustained load. With a 250ms ticker a
# batch size of N only binds above roughly 4*N events per second on that one
# table - 100 caps at ~400 rows/s per table, 700 at ~2800. Note also that a
# failed flush drops its batch, so this is equally the number of events one
# bad row can take down with it.
mysql_batch_size: {{number_of_bulk_records}}

# Metrics buffered before a Graphite Carbon write is flushed ahead of the
# 250ms ticker. Default 100, maximum 1000, which is also the documented
# production value.
#
# Higher than the MySQL ceiling because nothing in the protocol limits it: a
# batch is one Write of newline-delimited plaintext lines. The cap is purely
# about loss - a failed dial or write drops the entire buffered batch and
# leaves re-dialling to the next flush, and Graphite metrics have no
# redelivery behind them.
graphite_batch_size: 100

# Address the WebSocket HTTP server (/ws) listens on. Loopback-only by
# default: the /ws stream carries every monitoring event the worker sees,
# so exposing it on the network is an explicit decision, not something
# that happens by leaving a setting alone. Set ":8080" to listen on all
# interfaces - and set api_keys below to a real secret if you do.
listen_addr: {{listen_addr}}

# Address the Prometheus /metrics HTTP server listens on.
metrics_listen_addr: "{{metrics_listen_addr}}"

# Graphite Carbon plaintext receiver address (host:port). Only used when
# perfdata_route is "graphite" or "both".
graphite_addr: {{graphite_address}}:{{graphite_port}}

# Prefix prepended to every Graphite metric path:
# <graphite_prefix>.<hostname>.<service_description>.<metric_label>
graphite_prefix: {{graphite_prefix}}

# Where statusngin_service_perfdata metrics are written: "mysql",
# "graphite" or "both".
perfdata_route: graphite

# node_name value written into statusengine_hoststatus/statusengine_servicestatus rows.
nodename: openITCOCKPIT

# API keys accepted by the /ws endpoint. Real clients should send one of
# these via the "Authorization: Bearer <key>" or "X-Api-Key" header; the
# browser-based demo client (web/ws-test-client.html) uses the ?api_key=
# query parameter instead, since browser JavaScript can't set custom
# headers on a WebSocket handshake.
#
# Leaving this empty does NOT disable authentication: the worker then
# generates a random key at startup and logs it as a warning. That is a
# safety net so an unconfigured worker is never an open event stream - it
# changes on every restart, so configure a real key here for anything
# that has to reconnect on its own.
{% if api_keys|length > 0  %}
api_keys: ["{{ api_keys|join('"," ') }}"]
{% else %}
api_keys: []
{% endif %}
# api_keys:
#   - change-me-to-a-real-secret
#   - a-second-key-for-another-client

# API keys accepted by the /commands endpoint, which submits Naemon
# external commands. Deliberately a separate list from api_keys above,
# with no overlap in either direction: an api_keys entry grants *reading*
# the event stream, an entry here grants *controlling the monitoring
# core* - passive check results, forced checks, downtimes, acknowledgements
# and, via "raw", most of Naemon's external command set.
#
# Unlike api_keys, leaving this empty really does disable the endpoint.
# There is no generated fallback key: a random write-access key nobody
# asked for is worse than no endpoint, so with nothing configured here the
# worker does not serve /commands at all and says so at startup.
{% if command_api_keys|length > 0  %}
command_api_keys: ["{{ command_api_keys|join('"," ') }}"]
{% else %}
command_api_keys: []
{% endif %}
# command_api_keys:
#   - a-secret-only-you-knows

# Address the external-command HTTP server (/commands) listens on. Its own
# port rather than sharing listen_addr, so the read stream and the write
# endpoint can be exposed - or not - independently. Loopback-only by
# default.
command_listen_addr: "{{command_listen_addr}}"

# On a core restart (Naemon/Nagios/Icinga reload), clear out stale
# hoststatus/servicestatus rows how?
#   false (default): TRUNCATE both tables outright.
#   true: delete only rows for objects openITCOCKPIT's own hosts/services
#         tables no longer know about, so status for objects that still
#         exist survives the restart instead of needing to be rebuilt.
enable_openitcockpit_tweaks: true

# Discard statusngin_hoststatus and statusngin_servicestatus events older
# than this, instead of writing them to MySQL and broadcasting them.
#
# These two queues carry a full snapshot of an object's current state, sent
# again on every check and overwriting its predecessor in a single-row-per-
# object table - so a snapshot from ten minutes ago has no reader left.
# While the worker is down they pile up one job per check interval per
# object, and draining that backlog costs the same MySQL time live traffic
# needs, which the live traffic then waits for. Skipping it lets the worker
# catch up in seconds instead of minutes.
#
# Applies to those two queues ONLY. Every other queue carries history -
# check results, state changes, notifications - where each event is the
# single record of something that happened and age is no reason to drop it.
#
# Any Go duration ("5m", "90s", "1h"). "0" processes every event regardless
# of age. Note the comparison is between the monitoring core's clock and
# this worker's: if the two hosts disagree by more than this, both queues
# stop being written entirely. statusengine_queue_events_discarded_stale_total
# is the metric that shows it.
status_max_age: 5m

# Minimum log level: "debug", "info", "warn" or "error".
#
# At "info" the worker logs lifecycle events and one summary per 30 seconds
# per active table ("db: write stats", "graphite: write stats") - nothing per
# job, per batch or per row. Leave it there for a service: "debug" adds one
# line per bulk-insert flush, which was measured at ~145 MB of journal per
# hour under sustained load. Use "debug" to explain a specific flush, not to
# watch a healthy worker. Errors are logged at every occurrence regardless of
# this setting.
log_level: info

# Structured log output format: "text" or "json".
log_format: text


###############################################################################
# DATA RETENTION (cmd/db_cleanup only)
#
# Everything below is read by the cleanup tool, not by the worker - the
# worker ignores these keys, just as the cleanup tool ignores everything
# above except mysql_dsn, log_level and log_format. Both binaries share
# this one file:
#
#   go run ./cmd/db_cleanup -config /path/to/config.yaml
#
# Run it from cron or a systemd timer, once a day is plenty. In a cluster,
# run it on exactly one node - or on several at clearly different times.
#
# Every value is a number of DAYS of history to keep, configured
# separately for hosts and services. The key names are the ones the legacy
# PHP worker uses, so an existing config.yml can be carried over value for
# value - including its convention:
#
#   0 disables cleanup of that table entirely.
#
# A missing key falls back to the built-in default shown here; only an
# explicit 0 switches a table off.
###############################################################################

# Every executed check. By far the largest tables - these two are the
# reason the cleanup exists.
#   statusengine_hostchecks.start_time / statusengine_servicechecks.start_time
age_hostchecks: 0
age_servicechecks: 0

# Acknowledgements.
#   statusengine_host_acknowledgements.entry_time / statusengine_service_acknowledgements.entry_time
age_host_acknowledgements: 0
age_service_acknowledgements: 0

# Notifications, one row per notified contact.
#   statusengine_host_notifications.start_time / statusengine_service_notifications.start_time
age_host_notifications: 0
age_service_notifications: 0

# Notification log, one row per notification event.
#   statusengine_host_notifications_log.start_time / statusengine_service_notifications_log.start_time
age_host_notifications_log: 0
age_service_notifications_log: 0

# State changes. Kept far longer than checks because availability reports
# are computed from these.
#   statusengine_host_statehistory.state_time / statusengine_service_statehistory.state_time
age_host_statehistory: 0
age_service_statehistory: 0

# Past downtimes. Despite the legacy key name, this cleans the *history*
# tables - currently scheduled downtimes are never touched.
#   statusengine_host_downtimehistory.entry_time / statusengine_service_downtimehistory.entry_time
age_host_downtimes: 0
age_service_downtimes: 0

# Naemon/Nagios log entries.
#   statusengine_logentries.entry_time
age_logentries: 0

# Performance data. Only relevant when perfdata_route above writes to
# MySQL; with Graphite, its own retention applies instead.
#   statusengine_perfdata.timestamp_unix
age_perfdata: 0

# Rows deleted per DELETE statement. Each batch is its own transaction, so
# a smaller value holds locks for shorter, keeps the undo log small and
# produces binlog events replicas can digest - at the cost of more
# round-trips. Must be >= 1.
cleanup_batch_size: 5000

# Pause between two batches of the same table, as a duration string
# ("50ms", "1s"). The default of no pause deletes as fast as the database
# allows, which is usually right for a nightly run on an idle system; set
# a pause if the cleanup competes with live check results.
cleanup_batch_pause: 0s

