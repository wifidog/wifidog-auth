<?php
require_once(dirname(__FILE__) . '/../include/common.php');

require_once('classes/Authenticator.php');
require_once('classes/OpenIdServerWifidog.php');
require_once('classes/MainUI.php');
//pretty_print_r($_REQUEST);
if(!empty($_REQUEST['mode'])){
$mode=$_REQUEST['mode'];
}
else{
    $mode=null;
}
OpenIdServerWifidog::wfSpecialOpenIDServer($mode);
