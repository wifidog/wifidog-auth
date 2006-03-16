#!/bin/sh
#
# Creates the WiFiDog documentation
# =================================
#
# Requirements to create the documentation:
# - PEAR::PhpDocumentor version 1.3+ must be installed
# - the stable version doesn't support PHP5 code
# - install PEAR::PhpDocumentor version 1.3 this way:
#   pear install PhpDocumentor-beta
# - memory_limit flag in php.ini must be larger than 8 MB (at least 16 MB recommended)
#
# On systems running newer version of PHP (5.1.x) you'll need to use the CVS version
# of PhpDocumentor, currently!

phpdoc -dh off -pp on -j off -p off -s on -ti "WiFiDog Documentation" -dn WiFiDogAuthServer -po WiFiDogAuthServer -o  HTML:frames:default -t . -d ../wifidog,. -i *.html,*.gif,*.jpg,*.png,*.css,*.js,*.sh,*.mo,*.po,*.pl,*.txt,*.xml,*.tpl,local.config.php,*/FCKeditor/*,*/magpie/*,*/smarty/*,*/local_content/*,*/locale/*,*/tmp/*