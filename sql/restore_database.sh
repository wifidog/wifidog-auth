#!/bin/bash
DATABASE_NAME="wifidog";
USERNAME="wifidog";
SUPERUSER="postgres";
FILENAME=$1;

if [ -z $FILENAME ] ; then
echo "You must specify a filename as the first argument"
exit 1
fi

echo "Do I need to delete the current $DATABASE_NAME database before restoring from $FILENAME? (y/n)"
read delete_confirm
if [ $delete_confirm = "y" -o $delete_confirm = "Y" ] ; then
cmd="dropdb -U $SUPERUSER $DATABASE_NAME" 
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to delete the database"
    exit 1
fi
else
echo "Not trying to delete database $DATABASE_NAME"
fi

echo "Do I need to create the user $USERNAME before restoring from $FILENAME? (y/n)"
read create_user_confirm
if [ $create_user_confirm = "y" -o $create_user_confirm = "Y" ] ; then
cmd="createuser -U $SUPERUSER --no-superuser --no-createdb --no-createrole --pwprompt $USERNAME" 
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to create user $USERNAME "
    exit 1
fi
else
echo "Not trying to create user $USERNAME"
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


echo "Do I need to Adding the plpgsql language to database (as the admin user)? (y/n)"
read add_plpgsql_confirm
if [ $add_plpgsql_confirm = "y" -o $add_plpgsql_confirm = "Y" ] ; then
cmd="createlang -U $SUPERUSER plpgsql $DATABASE_NAME"
echo $cmd
$cmd
retval=$?
if [[ $retval -ne 0 ]] ; then
    echo "Unable to create the plpgsql language, you may need to install the module"
    exit 1
fi
else
echo "Not trying to add plpgsql"
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
