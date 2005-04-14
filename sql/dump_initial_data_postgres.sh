#!/bin/sh
echo "\connect wifidog;"
echo "BEGIN;"

pg_dump -a -D --username=wifidog -t token_status

pg_dump -a -D --username=wifidog -t venue_types
pg_dump -a -D --username=wifidog -t node_deployment_status
echo "INSERT INTO nodes (node_id, name, rss_url) VALUES ('default', 'Unknown node', NULL);"

pg_dump -a -D --username=wifidog -t schema_info
pg_dump -a -D --username=wifidog -t locales
echo "COMMIT;"