[nsta]
; All config options can be found: https://github.com/openITCOCKPIT/nsta/blob/development/cli/cli.go
;
; address for local gearman server
gearman = "127.0.0.1:4730"

; http listener for incoming requests
; Bind to local addres: 127.0.0.1:7473
; Bind to all interfaces :7473
; Bind to IPv6 interface [::]:7473
; Bind to local IPv6 interface [::1]:7473
listen-http = "{{listen_http}}"
listen-https = "{{listen_https}}"

; tls-key is used by the internal webserver
tls-key = "{{tls_key}}"

; tls-cert is used by the internal webserver
tls-cert = "{{tls_cert}}"

; mode is always server on master
mode = "server"

; mycnf points to the database connection credentials
mycnf = "/opt/openitc/etc/mysql/mysql.cnf"

; nagios-path is used to fetch satellite configuration files
nagios-path = "/opt/openitc/nagios"
