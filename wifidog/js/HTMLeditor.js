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
 * @subpackage HTMLeditor
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2005 Max Horvath <max.horvath@maxspot.de> - maxspot GmbH
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

// WiFiDOG skin
FCKConfig.SkinPath = FCKConfig.BasePath + '../../../include/HTMLeditor/';

// Path to our CSS defenitions
FCKConfig.EditorAreaCSS = FCKConfig.BasePath + '../../../local_content/default/stylesheet.css';

// Don't show script sources in source mode
FCKConfig.ProtectedSource.Add(/<script[\s\S]*?\/script>/gi);	// <SCRIPT> tags.
FCKConfig.ProtectedSource.Add(/<\?[\s\S]*?\?>/g); 				// PHP style server side code <?...?>

FCKConfig.ToolbarSets["WiFiDOG"] = [
    ['Templates'],
    ['Style', 'FontFormat'],
    ['Cut', 'Copy', 'Paste', 'PasteWord'],
    ['Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
    ['Preview'],
    '/',
    ['Bold', 'Italic', 'Underline', 'StrikeThrough', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull'],
    ['OrderedList', 'UnorderedList', '-', 'Outdent', 'Indent'],
    ['Link', 'Unlink', 'Anchor'],
    ['Image', 'Flash', 'Table', 'Rule', 'Smiley', 'SpecialChar']
] ;

FCKConfig.ContextMenu = ['Generic', 'Link', 'Anchor', 'Image', 'Flash', 'NumberedList', 'BulletedList', 'Table', 'TableCell'] ;

FCKConfig.FontFormats		= 'p;div;pre;address;h1;h2;h3;h4;h5;h6' ;

FCKConfig.LinkUpload = false;
FCKConfig.LinkBrowser = false;

FCKConfig.ImageUpload = false;
FCKConfig.ImageBrowser = false;

FCKConfig.FlashUpload = false;
FCKConfig.FlashBrowser = false;