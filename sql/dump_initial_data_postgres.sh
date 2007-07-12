#!/bin/sh
echo "\connect wifidog;"
echo "BEGIN;"

pg_dump -a -D --username=wifidog -t token_status
pg_dump -a -D --username=wifidog -t venue_types
pg_dump -a -D --username=wifidog -t node_deployment_status
pg_dump -a -D --username=wifidog -t content_available_display_areas
pg_dump -a -D --username=wifidog -t content_available_display_pages
pg_dump -a -D --username=wifidog -t stakeholder_types

echo "INSERT INTO networks (network_id, network_authenticator_class, network_authenticator_params) VALUES ('default-network', 'AuthenticatorLocalUser', '\'default-network\'');";
echo "INSERT INTO nodes (network_id, node_id, gw_id, name) VALUES ('default-network', 'default', 'default', 'My first node');"
echo "INSERT INTO virtual_hosts (virtual_host_id, hostname, default_network) VALUES ('DEFAULT_VHOST', 'localhost', 'default-network');";
echo "INSERT INTO server (server_id, default_virtual_host) VALUES ('SERVER_ID', 'DEFAULT_VHOST');";
echo "INSERT into roles (role_id, stakeholder_type_id) VALUES ('SERVER_SYSADMIN', 'Server');";
echo "INSERT into roles (role_id, stakeholder_type_id) VALUES ('NETWORK_SYSADMIN', 'Network');";

pg_dump -a -D --username=wifidog -t schema_info
pg_dump -a -D --username=wifidog -t locales

echo "COMMIT;"