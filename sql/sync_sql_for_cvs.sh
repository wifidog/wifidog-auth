#!/bin/sh
echo "Note:  This script must be run as the wifidog local user"
sh dump_initial_data_postgres.sh > wifidog-postgres-initial-data.sql
chmod a+r wifidog-postgres-initial-data.sql
sh dump_schema_postgres.sh > wifidog-postgres-schema.sql
chmod a+r wifidog-postgres-schema.sql