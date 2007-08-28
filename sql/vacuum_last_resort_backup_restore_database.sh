#!/bin/bash
DATABASE_NAME="wifidog";
USERNAME="wifidog";
SUPERUSER="postgres";
FILENAME=$1;

if [ -z $FILENAME ] ; then
echo "You must specify a filename as the first argument"
exit 1
fi

cmd="pg_dump --blobs --file=$FILENAME --format=c -i -v --compress=3 -U $SUPERUSER $DATABASE_NAME"
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to dump the database, I can't proceed without a valid backup"
    exit 1
fi

cmd="dropdb -U $SUPERUSER $DATABASE_NAME" 
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to delete the database"
    exit 1
fi

echo "Creating database"

cmd="createdb -U $SUPERUSER $DATABASE_NAME --encoding=UTF-8 --owner=$USERNAME"
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to create the database, you probably need to delete the existing database"
    exit 1
fi
echo "Restoring database"
cmd="pg_restore -U $SUPERUSER -d $DATABASE_NAME -v $FILENAME"
echo $cmd
$cmd
retval=$?
#if [[ $retval -ne 0 ]] ; then
#    echo "Unable to restore the database completely"
#    exit 1
#fi

echo "Vacuuming database"
sql="VACUUM ANALYZE;"
cmd="psql -U $SUPERUSER -d $DATABASE_NAME --command \"$sql\""
echo $cmd
eval $cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to restore the database completely"
    exit 1
fi
exit $?