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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH, Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: change_password.php 914 2006-01-23 05:25:43Z max-horvath $
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

		<link rel="stylesheet" type="text/css" href="{$stylesheetURL}">

        <style type="text/css">
            {include file="$stylesheetParsedFile"}
        </style>
    </head>

    <body id='page' class='{$page_name}'>
    	{if !empty($contentArray.page_header) || $debugRequested}
        	<div class='page_header'>
            {if $debugRequested}
            	<pre>{$debugOutput}</pre>
        	{/if}
        	</div>
    	{/if} 


        <div id="page_body">
        {if !empty($contentArray.left_area_top) ||  !empty($contentArray.left_area_middle) ||  !empty($contentArray.left_area_middle)}
                <div id="left_area">
	                {if !empty($contentArray.left_area_top)}
	                <div id="left_area_top">
	                    {$contentArray.left_area_top}
	                </div>
	                {/if}
	                {if !empty($contentArray.left_area_middle)}
	                <div id="left_area_middle">
	                    {$contentArray.left_area_middle}
	                </div>
	                {/if}
	                {if !empty($contentArray.left_area_bottom)}
	                <div id="left_area_bottom">
	                    {$contentArray.left_area_bottom}
	                </div>
	                {/if}   
                </div>
        {/if}
        
        {if !empty($contentArray.main_area_top) ||  !empty($contentArray.main_area_middle) ||  !empty($contentArray.main_area_middle)}
                <div id="main_area">
	                {if !empty($contentArray.main_area_top)}
	                <div id="main_area_top">
	                    {$contentArray.main_area_top}
	                </div>
	                {/if}
	                {if !empty($contentArray.main_area_middle)}
	                <div id="main_area_middle">
	                    {$contentArray.main_area_middle}
	                </div>
	                {/if}
	                {if !empty($contentArray.main_area_bottom)}
	                <div id="main_area_bottom">
	                    {$contentArray.main_area_bottom}
	                </div>
	                {/if}   
                </div>
        {/if}        

          {if !empty($contentArray.right_area_top) ||  !empty($contentArray.right_area_middle) ||  !empty($contentArray.right_area_bottom)}
                <div id="right_area">
	                {if !empty($contentArray.right_area_top)}
	                <div id="right_area_top">
	                    {$contentArray.right_area_top}
	                </div>
	                {/if}
	                {if !empty($contentArray.right_area_middle)}
	                <div id="right_area_middle">
	                    {$contentArray.right_area_middle}
	                </div>
	                {/if}
	                {if !empty($contentArray.right_area_bottom)}
	                <div id="right_area_bottom">
	                    {$contentArray.right_area_bottom}
	                </div>
	                {/if}   
                </div>
        {/if}   
        </div>
    {if !empty($contentArray.page_footer)}
        <div class='page_footer'>
			{$contentArray.page_footer}
        </div>
    {/if} 

        {foreach from=$footerScripts item=currScript}
          {$currScript}
        {/foreach}
    </body>
</html>