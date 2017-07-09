#!/bin/bash
DIR=$(pwd)
BIN="phpcs"
STANDARD=""
FILES=""
CMD_NAME=$0

HELP="Usage: $CMD_NAME [--standard=<standard>] [--fix] [FILEs]...
Lint FILEs (check and fix coding style).

Mandatory arguments to long options are mandatory for short options too.
  -a, --all                  all files
  -f, --fix                  fix sniff violations automatically.
  -s, --standard=WORD        The name or path of the coding standard to use.
                             if you don't specify, it will try to use ./phpcs.xml,
                             if file not exists, it will use PSR2 by default.
  -h, --help                 display this help and exit
  [FILEs]                     One or more files and/or directories to check
                             (the current directory by default)."

TEMP=$(getopt -o afhs: --long all,fix,help,standard: -n "$CMD_NAME" -- "$@")

if [ $? != 0 ]; then
    echo "Terminating..." >&2;
    exit 1;
fi

eval set -- "$TEMP"

while true; do
        case "$1" in
                -a|--all)
                    PHP_FILES=$(ls *.php)
                    DIRS=$(ls -d */)
                    FILES="$PHP_FILES"$'\n'"$DIRS"
                    #FILES=$(pwd) # phpcs is too slow when check big project, just as phpunit
                    shift;;
                -f|--fix)
                    BIN="phpcbf"
                    shift;;
                -h|--help)
                    echo "$HELP"
                    exit 0;;
                -s|--standard)
                    STANDARD=$2
                    shift 2;;
                --) shift; break;;
                *) echo "Internal error!"; exit 1;;
        esac
done

BIN_PATH=""
if [ -f ./vendor/bin/$BIN ]; then
    BIN_PATH=./vendor/bin/$BIN
else
    BIN_PATH=$(which $BIN)
    if [ $? -ne 0 ]; then
        echo "ERROR: can not find "$BIN""
        echo "----------------------------------------------------------------------"
        echo "     TRY:     composer require --dev squizlabs/php_codesniffer"
        echo "----------------------------------------------------------------------"
        exit 1
    fi
fi

if [ -z $STANDARD ]; then
    if [ -f "$DIR/phpcs.xml" ]; then
        STANDARD="$DIR/phpcs.xml"
    else
        STANDARD="PSR2"
    fi
fi

if [ -z "$FILES" ]; then
    FILES="$*"
fi

BRANCH=$(git rev-parse --abbrev-ref HEAD)

if [ -z "$FILES" ]; then
    FILES=$(git diff --diff-filter=ACM --name-only HEAD | grep .php)
    if [ "$BRANCH" != "master" ]; then
        # compare this branch with master
        BRANCH_FILES=$(git diff --diff-filter=ACM --name-only master...$BRANCH | grep .php)
        FILES="$FILES"$'\n'"$BRANCH_FILES"
    elif [ -z "$FILES" ]; then
        # if run lint on master, if there is no modified FILES, means have commited, so check last commit.
        FILES=$(git diff --diff-filter=ACM --name-only HEAD^ HEAD | grep .php)
    fi
    if [ -z "$FILES" ]; then
        exit 0
    fi
fi

ERRORS=0
for FILENAME in $FILES
do
    OUTPUT=$($BIN_PATH --standard=$STANDARD $FILENAME)
    if [ $? -ne 0 ]; then
        ERRORS=$(($ERRORS+1))
        echo "$OUTPUT"
    fi
done

echo '----------------------------------------------------------------------'
if [ $BIN == 'phpcs' ]; then
    if [ $ERRORS -eq 0 ]; then
        echo ':) :) :) NICE code!'
    else
        echo ':( :( :( BAD code! run with --fix to FIX SOME VIOLATIONS AUTOMATICALLY'
    fi
else
    # TODO phpcbf return 0 when nothing changed, return 1/2/3 when fix something, should we follow it?
    if [ $ERRORS -eq 0 ]; then
        echo ':( :( :( can not auto fix! You need edit them manually.'
    else
        echo ':) :) :) auto fixed some files! you need retry:  git add'
    fi
fi
echo '----------------------------------------------------------------------'
exit $ERRORS
