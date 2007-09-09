<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +-------------------------------------------------------------------+
// | WiFiDog Authentication Server                                     |
// | =============================                                     |
// |                                                                   |
// | The WiFiDog Authentication Server is part of the WiFiDog captive  |
// | portal suite.                                                     |
// +-------------------------------------------------------------------+
// | PHP version 5 required.                                           |
// +-------------------------------------------------------------------+
// | Homepage:     http://www.wifidog.org/                             |
// | Source Forge: http://sourceforge.net/projects/wifidog/            |
// +-------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or     |
// | modify it under the terms of the GNU General Public License as    |
// | published by the Free Software Foundation; either version 2 of    |
// | the License, or (at your option) any later version.               |
// |                                                                   |
// | This program is distributed in the hope that it will be useful,   |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
// | GNU General Public License for more details.                      |
// |                                                                   |
// | You should have received a copy of the GNU General Public License |
// | along with this program; if not, contact:                         |
// |                                                                   |
// | Free Software Foundation           Voice:  +1-617-542-5942        |
// | 59 Temple Place - Suite 330        Fax:    +1-617-542-2652        |
// | Boston, MA  02111-1307,  USA       gnu@gnu.org                    |
// |                                                                   |
// +-------------------------------------------------------------------+

/**
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  Copyright 2006,2007 Internet Brands (http://www.internetbrands.com/)
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */


 $path_extra = OPENID_PATH;
 $path = ini_get('include_path');
 $path = $path_extra . PATH_SEPARATOR . $path;
 ini_set('include_path', $path);
 require_once("Auth/OpenID/Server.php");
 require_once("Auth/OpenID/Consumer.php");
 # These are trust roots that we don't bother asking users
 # whether the trust root is allowed to trust; typically
 # for closely-linked partner sites.

 $wgOpenIDServerForceAllowTrust = array();

 # Where to store transitory data. Can be 'memc' for the $wgMemc
 # global caching object, or 'file' if caching is turned off
 # completely and you need a fallback.

 $wgOpenIDServerStoreType = 'file';

 # If the store type is set to 'file', this is is the name of a
 # directory to store the data in.
 define('CACHE_NONE', 'CACHE_NONE');
 $wgMainCacheType = CACHE_NONE;
 $wgOpenIDServerStorePath = ($wgMainCacheType == CACHE_NONE) ? WIFIDOG_ABS_FILE_PATH."tmp/openidserver/" : NULL;

 # Outputs a Yadis (http://yadis.org/) XRDS file, saying that this server
 # supports OpenID and lots of other jazz.

 class OpenIdServerWifidog {
     function wfSpecialOpenIDXRDS($par) {
         global $wgOut;

         // XRDS preamble XML.
         $xml_template = array('<?xml version="1.0" encoding="UTF-8"?>',
         '<xrds:XRDS',
         '  xmlns:xrds="xri://\$xrds"',
         '  xmlns:openid="http://openid.net/xmlns/1.0"',
         '  xmlns="xri://$xrd*($v*2.0)">',
         '<XRD>');

         // Generate the user page URL.
         $user_title = Title::makeTitle(NS_USER, $par);
         $user_url = $user_title->getFullURL();

         // Generate the OpenID server endpoint URL.
         $server_title = Title::makeTitle(NS_SPECIAL, 'OpenIDServer');
         $server_url = $server_title->getFullURL();

         // Define array of Yadis services to be included in
         // the XRDS output.
         $services = array(
         array('uri' => $server_url,
         'priority' => '0',
         'types' => array('http://openid.net/signon/1.0',
         'http://openid.net/sreg/1.0'),
         'delegate' => $user_url)
         );

         // Generate <Service> elements into $service_text.
         $service_text = "\n";
         foreach ($services as $service) {
             $types = array();
             foreach ($service['types'] as $type_uri) {
                 $types[] = '    <Type>'.$type_uri.'</Type>';
             }
             $service_text .= implode("\n",
             array('  <Service priority="'.$service['priority'].'">',
             '    <URI>'.$server_url.'</URI>',
             implode("\n", $types),
             '  </Service>'));
         }

         $wgOut->disable();

         // Print content-type and XRDS XML.
         header("Content-Type", "application/xrds+xml");
         print implode("\n", $xml_template);
         print $service_text;
         print implode("\n", array("</XRD>", "</xrds:XRDS>"));
     }

     # Special page for the server side of OpenID
     # It has three major flavors:
     # * no parameter is for external requests to validate a user.
     # * 'Login' is we got a validation request but the
     #   user wasn't logged in. We show them a form (see self::serverLoginForm)
     #   and they post the results, which go to self::serverLogin
     # * 'Trust' is when the user has logged in, but they haven't
     #   specified whether it's OK to let the requesting site trust them.
     #   If they haven't, we show them a form (see OpenIDServerTrustForm)
     #   and let them post results which go to OpenIDServerTrust.
     #
     # OpenID has its own modes; we only handle two of them ('check_setup' and
     # 'check_immediate') and let the OpenID libraries handle the rest.
     #
     # Output may be just a redirect, or a form if we need info.

     function wfSpecialOpenIDServer($par) {

         global $wgOut;

         $server =& self::getOpenIDServer();
         //pretty_print_r($_SERVER);
         switch ($par) {
             case 'Login':
                 if(empty($_REQUEST['user_id'])){
                     throw new Exception('user_id must be present');
                 }
                 $user = User::getObject($_REQUEST['user_id']);
                 list($request, $sreg) = self::serverSessionFetchValues();
                 $result = self::serverLogin($request);
                 if ($result) {
                     if (is_string($result)) {
                         self::serverLoginForm($request, $result, $user);
                         return;
                     } else {
                         self::outputServerResponse($server, $result);
                         return;
                     }
                 }
                 break;
             case 'Trust':
                 list($request, $sreg) = self::serverSessionFetchValues();
                 $result = OpenIDServerTrust($request, $sreg);
                 if ($result) {
                     if (is_string($result)) {
                         OpenIDServerTrustForm($request, $sreg, $result);
                         return;
                     } else {
                         self::outputServerResponse($server, $result);
                         return;
                     }
                 }
                 break;
             default:
                 $method = $_SERVER['REQUEST_METHOD'];
                 $query = null;
                 if ($method == 'GET') {
                     $query = $_GET;
                 } else {
                     $query = $_POST;
                 }
                 $request = $server->decodeRequest();
                 $sreg = self::getServerSregFromQuery($query);
                 //pretty_print_r($sreg);
                 $response = NULL;
                 break;
         }
         //pretty_print_r($request);
         if (!isset($request)) {
             throw new exception("OpenID request parameters missing");
             return;
         }
         else if(get_class($request)=='Auth_OpenID_ServerError') {
             //throw new exception("OpenID error: ".$request->text);
         }
          
          

         switch ($request->mode) {
             case "checkid_setup":
                 $response = self::ServerCheck($server, $request, $sreg, false);
                 break;
             case "checkid_immediate":
                 $response = self::ServerCheck($server, $request, $sreg, true);
                 break;
             default:
                 # For all the other parts, just let the libs do it
                 $response =& $server->handleRequest($request);
         }

         # self::ServerCheck returns NULL if some output (like a form)
         # has been done

         if (isset($response)) {
             # We're done; clear values
             self::serverSessionClearValues();
             self::outputServerResponse($server, $response);
         }
     }

     # Returns the full URL of the special page; we need to pass it around
     # for some requests

     public static function getOpenIdServerUrl() {
         return BASE_URL_PATH."openid/";
     }

     static function getOpenIDStore($storeType, $prefix, $options) {
         global $wgOut;

         switch ($storeType) {
             case 'memcached':
             case 'memc':
                 return new OpenID_MemcStore($prefix);

             case 'file':
                 # Auto-create path if it doesn't exist
                 if (!is_dir($options['path'])) {
                     if (!mkdir($options['path'], 0770, true)) {
                         throw new exception("Path for the OpenID FileStore is inaccessible");
                         return NULL;
                     }
                 }
                 require_once("Auth/OpenID/FileStore.php");
                 return new Auth_OpenID_FileStore($options['path']);

             default:
                 throw new exception("Unknown OpenID Store type");
         }
     }

     # Returns an Auth_OpenID_Server from the libraries. Utility.
     private static function getOpenIDServer() {
         global $wgOpenIDServerStorePath,
         $wgOpenIDServerStoreType;

         $store = self::getOpenIDStore($wgOpenIDServerStoreType,
         'server',
         array('path' => $wgOpenIDServerStorePath));

         return new Auth_OpenID_Server($store);
     }

     # Checks a validation request. $imm means don't run any UI.
     # Fairly meticulous and step-by step, and uses assertions
     # to point out assumptions at each step.
     #
     # XXX: this should probably be broken up into multiple functions for
     # clarity.

     static function ServerCheck($server, $request, $sreg, $imm = true) {

         assert(isset($server));
         assert(isset($request));
         assert(isset($sreg));
         assert(isset($imm) && is_bool($imm));

         # Is the passed identity URL a user page?

         $url = $request->identity;

         assert(isset($url) && strlen($url) > 0);

         $user = self::DEPRECATEDgetUsernameFromOpenIdUrl($url);

         if (!$user) {
             throw new Exception("OpenID: '$url' not a user page.\n");
             return $request->answer(false, self::getOpenIdServerUrl());
         }

         assert($user);

         # Is there a logged in user?

         if ($user != User::getCurrentUser()) {
             //throw new Exception ("OpenID: User not logged in.\n");
             if ($imm) {
                 return $request->answer(false, self::getOpenIdServerUrl());
             } else {
                 # Bank these for later
                 self::serverSessionSaveValues($request, $sreg);
                 self::serverLoginForm($request, null, $user);
                 return NULL;
             }
         }

         assert($user);

         assert(is_array($sreg));

         # Does the request require sreg fields that the user has not specified?

         if (array_key_exists('required', $sreg)) {
             $notFound = false;
             foreach ($sreg['required'] as $reqfield) {
                 if (is_null(self::getUserField($user, $reqfield))) {
                     $notFound = true;
                     break;
                 }
             }
             if ($notFound) {
                 //("OpenID: Consumer demands info we don't have.\n");
                 return $request->answer(false, self::getOpenIdServerUrl());
             }
         }

         # Trust check

         $trust_root = $request->trust_root;

         assert(isset($trust_root) && is_string($trust_root) && strlen($trust_root) > 0);

         $trust = self::GetUserTrust($user, $trust_root);

         # Is there a trust record?

         if (is_null($trust)) {
             wfDebug("OpenID: No trust record.\n");
             if ($imm) {
                 return $request->answer(false, self::getOpenIdServerUrl());
             } else {
                 # Bank these for later
                 self::serverSessionSaveValues($request, $sreg);
                 OpenIDServerTrustForm($request, $sreg);
                 return NULL;
             }
         }

         assert(!is_null($trust));

         # Is the trust record _not_ to allow trust?
         # NB: exactly equal

         if ($trust === false) {
             wfDebug("OpenID: User specified not to allow trust.\n");
             return $request->answer(false, self::getOpenIdServerUrl());
         }

         assert(isset($trust) && is_array($trust));

         # Does the request require sreg fields that the user has
         # not allowed us to pass, or has not specified?

         if (array_key_exists('required', $sreg)) {
             $notFound = false;
             foreach ($sreg['required'] as $reqfield) {
                 if (!in_array($reqfield, $trust) ||
                 is_null(self::getUserField($user, $reqfield))) {
                     $notFound = true;
                     break;
                 }
             }
             if ($notFound) {
                 wfDebug("OpenID: Consumer demands info user doesn't want shared.\n");
                 return $request->answer(false, self::getOpenIdServerUrl());
             }
         }

         # assert(all required sreg fields are in $trust)

         # XXX: run a hook here to check

         # SUCCESS

         $response_fields = array_intersect(array_unique(array_merge($sreg['required'], $sreg['optional'])),
         $trust);

         $response = $request->answer(true);

         assert(isset($response));

         foreach ($response_fields as $field) {
             $value = self::getUserField($user, $field);
             if (!is_null($value)) {
                 $response->addField('sreg', $field, $value);
             }
         }

         return $response;
     }

     # Get the user's configured trust value for a particular trust root.
     # Returns one of three values:
     # * NULL -> no stored trust preferences
     # * false -> stored trust preference is not to trust
     # * array -> possibly empty array of allowed profile fields; trust is OK

     static function GetUserTrust($user, $trust_root) {
         //TODO:  ACTUALLY IMPLEMENT ME
         return(array());
         
         static $allFields = array('nickname', 'fullname', 'email', 'language');
         global $wgOpenIDServerForceAllowTrust;

         foreach ($wgOpenIDServerForceAllowTrust as $force) {
             if (preg_match($force, $trust_root)) {
                 return $allFields;
             }
         }

         $trust_array = self::GetUserTrustArray($user);

         if (array_key_exists($trust_root, $trust_array)) {
             return $trust_array[$trust_root];
         } else {
             return null; # Unspecified trust
         }
     }

     static function setUserTrust(&$user, $trust_root, $value) {

         $trust_array = self::GetUserTrustArray($user);

         if (is_null($value)) {
             if (array_key_exists($trust_root, $trust_array)) {
                 unset($trust_array[$trust_root]);
             }
         } else {
             $trust_array[$trust_root] = $value;
         }

         self::setUserTrustArray($user, $trust_array);
     }

     static function GetUserTrustArray($user) {
         $trust_array = array();
         $trust_str = $user->getOption('openid_trust');
         if (strlen($trust_str) > 0) {
             $trust_records = explode("\x1E", $trust_str);
             foreach ($trust_records as $record) {
                 $fields = explode("\x1F", $record);
                 $trust_root = array_shift($fields);
                 if (count($fields) == 1 && strcmp($fields[0], 'no') == 0) {
                     $trust_array[$trust_root] = false;
                 } else {
                     $fields = array_map('trim', $fields);
                     $fields = array_filter($fields, array('OpenIdServerWifidog','isValidField'));
                     $trust_array[$trust_root] = $fields;
                 }
             }
         }
         return $trust_array;
     }

     static function setUserTrustArray(&$user, $arr) {
         $trust_records = array();
         foreach ($arr as $root => $value) {
             if ($value === false) {
                 $record = implode("\x1F", array($root, 'no'));
             } else if (is_array($value)) {
                 if (count($value) == 0) {
                     $record = $root;
                 } else {
                     $value = array_map('trim', $value);
                     $value = array_filter($value, array('OpenIdServerWifidog','isValidField'));
                     $record = implode("\x1F", array_merge(array($root), $value));
                 }
             } else {
                 continue;
             }
             $trust_records[] = $record;
         }
         $trust_str = implode("\x1E", $trust_records);
         $user->setOption('openid_trust', $trust_str);
     }

     static public function isValidField($name) {
         # XXX: eventually add timezone
         static $fields = array('nickname', 'email', 'fullname', 'language');
         return in_array($name, $fields);
     }

     static private function getServerSregFromQuery($query) {
         $sreg = array('required' => array(), 'optional' => array(),
         'policy_url' => NULL);
         if (array_key_exists('openid.sreg.required', $query)) {
             $sreg['required'] = explode(',', $query['openid.sreg.required']);
         }
         if (array_key_exists('openid.sreg.optional', $query)) {
             $sreg['optional'] = explode(',', $query['openid.sreg.optional']);
         }
         if (array_key_exists('openid.sreg.policy_url', $query)) {
             $sreg['policy_url'] = $query['openid.sreg.policy_url'];
         }
         return $sreg;
     }

     static function getUserField($user, $field) {
         switch ($field) {
             case 'nickname':
                 return $user->getName();
                 break;
             case 'fullname':
                 return $user->getRealName();
                 break;
             case 'email':
                 return $user->getEmail();
                 break;
             case 'language':
                 return $user->getOption('language');
                 break;
             default:
                 return NULL;
         }
     }

     static private function outputServerResponse($server, $response) {
         $wr =& $server->encodeResponse($response);
         header("Status: " . $wr->code);
         foreach ($wr->headers as $k => $v) {
             header("$k: $v");
         }
         print $wr->body;
         return;
     }

     static private function serverLoginForm($request, $msg = null, User $user) {

         $url = $request->identity;
         $name = self::DEPRECATEDgetUsernameFromOpenIdUrl($url);
         $trust_root = $request->trust_root;

         $ui = MainUI::getObject();
         $html = null;
         $action =  BASE_URL_PATH."openid/?mode=Login";
         $html .= "<form method='post' action='$action'>\n";
         $userData['preSelectedUser']=$user;
         $html .= Authenticator::getLoginUI($userData);
         $html .= "</form>\n";
         $ui->addContent('main_area_top', $html);
         $ui->display();
     }

     static private function serverSessionSaveValues($request, $sreg) {
         $_SESSION['openid_server_request'] = $request;
         $_SESSION['openid_server_sreg'] = $sreg;

         return true;
     }

     static private function serverSessionFetchValues() {
         return array($_SESSION['openid_server_request'], $_SESSION['openid_server_sreg']);
     }

     static private function serverSessionClearValues() {
         unset($_SESSION['openid_server_request']);
         unset($_SESSION['openid_server_sreg']);
         return true;
     }
     /** @return null if successfull, or message if not, or XML answer if cancelled */
     private static function serverLogin($request) {
         //if ($wgRequest->getCheck('wpCancel')) {
         //    return $request->answer(false);
         //}

         $retval = null;
         Authenticator::processLoginUI();
         $user = User::getCurrentUser();
         if(!$user) {
             return "Login failed";
         }
         return $retval;
     }

     function OpenIDServerTrustForm($request, $sreg, $msg = NULL) {

         global $wgOut, $wgUser;

         $url = $request->identity;
         $name = self::DEPRECATEDgetUsernameFromOpenIdUrl($url);
         $trust_root = $request->trust_root;

         $instructions = wfMsg('openidtrustinstructions', $trust_root);
         $allow = wfMsg('openidallowtrust', $trust_root);

         if (is_null($sreg['policy_url'])) {
             $policy = wfMsg('openidnopolicy');
         } else {
             $policy = wfMsg('openidpolicy', $sreg['policy_url']);
         }

         if (isset($msg)) {
             $wgOut->addHTML("<p class='error'>{$msg}</p>");
         }

         $ok = wfMsg('ok');
         $cancel = wfMsg('cancel');

         $sk = $wgUser->getSkin();

         $wgOut->addHTML("<p>{$instructions}</p>" .
         '<form action="' . $sk->makeSpecialUrl('OpenIDServer/Trust') . '" method="POST">' .
         '<input name="wpAllowTrust" type="checkbox" value="on" checked="checked" id="wpAllowTrust">' .
         '<label for="wpAllowTrust">' . $allow . '</label><br />');

         $fields = array_filter(array_unique(array_merge($sreg['optional'], $sreg['required'])),
         array('OpenIdServerWifidog','isValidField'));

         if (count($fields) > 0) {
             $wgOut->addHTML('<table>');
             foreach ($fields as $field) {
                 $wgOut->addHTML("<tr>");
                 $wgOut->addHTML("<th><label for='wpAllow{$field}'>");
                 $wgOut->addHTML(wfMsg("openid$field"));
                 $wgOut->addHTML("</label></th>");
                 $value = self::getUserField($wgUser, $field);
                 $wgOut->addHTML("</td>");
                 $wgOut->addHTML("<td> " . ((is_null($value)) ? '' : $value) . "</td>");
                 $wgOut->addHTML("<td>" . ((in_array($field, $sreg['required'])) ? wfMsg('openidrequired') : wfMsg('openidoptional')) . "</td>");
                 $wgOut->addHTML("<td><input name='wpAllow{$field}' id='wpAllow{$field}' type='checkbox'");
                 if (!is_null($value)) {
                     $wgOut->addHTML(" value='on' checked='checked' />");
                 } else {
                     $wgOut->addHTML(" disabled='disabled' />");
                 }
                 $wgOut->addHTML("</tr>");
             }
             $wgOut->addHTML('</table>');
         }
         $wgOut->addHTML("<input type='submit' name='wpOK' value='{$ok}' /> <input type='submit' name='wpCancel' value='{$cancel}' />");
         return NULL;
     }

     function OpenIDServerTrust($request, $sreg) {
         global $wgRequest, $wgUser;

         assert(isset($request));
         assert(isset($sreg));
         assert(isset($wgRequest));

         if ($wgRequest->getCheck('wpCancel')) {
             return $request->answer(false);
         }

         $trust_root = $request->trust_root;

         assert(isset($trust_root) && strlen($trust_root) > 0);

         # If they don't want us to allow trust, save that.

         if (!$wgRequest->getCheck('wpAllowTrust')) {

             self::setUserTrust($wgUser, $trust_root, false);
             # Set'em and sav'em
             $wgUser->saveSettings();
         } else {

             $fields = array_filter(array_unique(array_merge($sreg['optional'], $sreg['required'])),
             array('OpenIdServerWifidog','isValidField'));

             $allow = array();

             foreach ($fields as $field) {
                 if ($wgRequest->getCheck('wpAllow' . $field)) {
                     $allow[] = $field;
                 }
             }

             self::setUserTrust($wgUser, $trust_root, $allow);
             # Set'em and sav'em
             $wgUser->saveSettings();
         }

     }

     # Converts an URL to a user name, if possible

     private static function DEPRECATEDgetUsernameFromOpenIdUrl($url) {
         # URL must be a string
         if (!isset($url) || !is_string($url) || strlen($url) == 0) {
             return null;
         }

         # it must start with our server, case doesn't matter
         /* (strpos(strtolower($url), strtolower($wgServer)) !== 0) {
             return null;
         }*/

         $parts = parse_url($url);
//pretty_print_r($parts);
         $relative = $parts['path'];
         if (!is_null($parts['query']) && strlen($parts['query']) > 0) {
             preg_match("/user_id=(.*?)(?:&|$)/", $parts['query'], $matches);
             //pretty_print_r($matches);
             $userId=$matches[1];
             $user = User::getObject($userId);
         }

         $retval = null;
         if($user) {
             $retval = $user;
         }
         return $retval;
     }
 }
