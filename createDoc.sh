#!/bin/sh
#
# Creates the WiFiDog documentation
#
# Make sure you've installed PEAR::PhpDocumentor version 1.3+ on your computer!

phpdoc -t  doc -d  wifidog/ -i  wifidog/admin/templates/,wifidog/classes/AbstractDbMySql.php,wifidog/images/,wifidog/includes/HTMLeditor/,wifidog/js/,wifidog/lib/FCKeditor/,wifidog/lib/magpie/,wifidog/lib/Phlickr/,wifidog/lib/smarty/,wifidog/local_content/,wifidog/locale/,wifidog/templates/,wifidog/tmp/,local.config.php -pp on -s on -ti "WiFiDog Documentation" -o  HTML:frames:default
