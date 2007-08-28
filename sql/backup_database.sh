#!/bin/bash
DATABASE_NAME="wifidog";
USERNAME="wifidog";
FILENAME=$1;

if [ -z $FILENAME ] ; then
echo "You must specify a filename as the first argument"
exit 1
fi

$cmd="pg_dump --blobs --file=$FILENAME --format=c -i -O -o -v --compress=3 -U $USERNAME $DATABASE_NAME"
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to dump the database"
    exit 1
fi