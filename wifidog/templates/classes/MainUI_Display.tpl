{*

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
 * Definition of main HTML page
 *
 * @package    WiFiDogAuthServer
 * @subpackage Templates
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting, Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

*}
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="-1">
        {$htmlHeaders}
        <title>{$title}</title>
        {foreach from=$stylesheetUrlArray item=stylesheetUrl}
          	<link rel="stylesheet" type="text/css" {if !empty($stylesheetUrl.media)}media="{$stylesheetUrl.media}"{/if}	href="{$stylesheetUrl.href}">
        {/foreach}
		<script src="{$base_url_path}js/formutils.js" type="text/javascript"></script>
		<script src="{$base_url_path}js/addEvent.js"  type="text/javascript"></script>
		<script src="{$base_url_path}js/menu.js"  type="text/javascript"></script>
    </head>
    <body id='page' class='{$page_name}'>
    <div id="wrap">
    	{if !empty($contentDisplayArray.page_header) || $debugRequested || $alwaysShowHeader}
        	<div id='page_header'>
            {if $debugRequested}
            	<pre>{$debugOutput}</pre>
        	{/if}
			{if !empty($contentDisplayArray.page_header)}
				{$contentDisplayArray.page_header}
        	{/if}
            </div>
    	{/if}
        <div id="page_body">
	          {if !empty($siteMenu) && $siteMenuPlaceAll}
	                <div id="site_menu">
	                    {$siteMenu}
	                </div>
	          {/if}
        <div id="content-wrap">
        {if !empty($contentDisplayArray.left_area_top) ||  !empty($contentDisplayArray.left_area_middle) ||  !empty($contentDisplayArray.left_area_middle)}
                <div id="left_area">
	                {if !empty($contentDisplayArray.left_area_top)  || (!empty($siteMenu) && $siteMenuPlaceLeft)}
	                <div id="left_area_top">
                    {if !empty($siteMenu) && $siteMenuPlaceLeft}    	                
                    <div id="site_menu">
	                    {$siteMenu}
	                </div>	 
                    {/if}
                        {$contentDisplayArray.left_area_top}
	                </div>
	                {/if}
	                {if !empty($contentDisplayArray.left_area_middle)}
	                <div id="left_area_middle">
	                    {$contentDisplayArray.left_area_middle}
	                </div>
	                {/if}
	                {if !empty($contentDisplayArray.left_area_bottom)}
	                <div id="left_area_bottom">
	                    {$contentDisplayArray.left_area_bottom}
	                </div>
	                {/if}
                </div>
        {/if}
        {if !empty($contentDisplayArray.main_area_top) ||  !empty($contentDisplayArray.main_area_middle) ||  !empty($contentDisplayArray.main_area_middle)}
                <div id="main_area">
	                {if !empty($contentDisplayArray.main_area_top) || (!empty($siteMenu) && $siteMenuPlaceMain)}
	                <div id="main_area_top">
                    {if !empty($siteMenu) && $siteMenuPlaceMain}    	                
                    <div id="site_menu">
	                    {$siteMenu}
	                </div>
	                {/if}
                        {$contentDisplayArray.main_area_top}
	                </div>
	                {/if}
	                {if !empty($contentDisplayArray.main_area_middle)}
	                <div id="main_area_middle">
	                    {$contentDisplayArray.main_area_middle}
	                </div>
	                {/if}
	                {if !empty($contentDisplayArray.main_area_bottom)}
	                <div id="main_area_bottom">
	                    {$contentDisplayArray.main_area_bottom}
	                </div>
	                {/if}
                </div>
        {/if}
          {if !empty($contentDisplayArray.right_area_top) ||  !empty($contentDisplayArray.right_area_middle) ||  !empty($contentDisplayArray.right_area_bottom)}
                <div id="right_area">
	                {if !empty($contentDisplayArray.right_area_top)}
	                <div id="right_area_top">
	                    {$contentDisplayArray.right_area_top}
	                </div>
	                {/if}
	                {if !empty($contentDisplayArray.right_area_middle)}
	                <div id="right_area_middle">
	                    {$contentDisplayArray.right_area_middle}
	                </div>
	                {/if}
	                {if !empty($contentDisplayArray.right_area_bottom)}
	                <div id="right_area_bottom">
	                    {$contentDisplayArray.right_area_bottom}
	                </div>
	                {/if}
                </div>
        {/if}
        </div>
    </div>
    {if !empty($contentDisplayArray.page_footer) || $alwaysShowFooter}
        <div id='page_footer'>
			{$contentDisplayArray.page_footer}
        </div>
    {/if}

        {foreach from=$footerScripts item=currScript}
          {$currScript}
        {/foreach}
    </div>
    </body>
</html>
