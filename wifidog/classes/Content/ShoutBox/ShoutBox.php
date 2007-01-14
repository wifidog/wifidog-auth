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
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2007 Benoit Grégoire, Technologies Coeus inc.
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Represents a list of banner ads
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 */
class ShoutBox extends Content {
    /**
     * Constructor
     *
     * @param string $content_id Content id
     *
     * @return void     */
    protected function __construct($content_id) {
    	parent :: __construct($content_id);
    }

    /**
     * Gets the content that is to be added as onclick value in the form
     *
     * @return Content or null
    
     */
    private function getOnClickContent() {
    	$content_id = $this->getKVP('ShoutBox_onclick_content_id');
    	if($content_id)
    	{
    		return Content::getObject($content_id);
    	}
    	else
    	{
    		return null;
    	}
    }

    /**
     * Set the content that is to be added as onclick value in the form
     *
     * @param Content object or null
     *
     * @return true
     */
    private function setOnClickContent($content) {
    	if($content)
    	{
    		$this->setKVP('ShoutBox_onclick_content_id', $content->getId());
    	}
    	else
    	{
    		$this->setKVP('ShoutBox_onclick_content_id', null);
    	}
    	return true;
    }

    public function getAdminUI($subclass_admin_interface = null, $title = null) {
    	$html = null;
    	/* OnclickContent */
    	$criteria_array = array (
    	array ('isTextualContent'),
    	array ('isSimpleContent')
    	);

    	$onclick_allowed_content_types = ContentTypeFilter :: getObject($criteria_array);


    	$content = $this->getOnClickContent();
    	$html .= "<li class='admin_element_item_container admin_section_edit_description'>\n";
    	$html .= "<div class='admin_element_data'>\n";
    	$onclick_title = _("Shout button 'onclick=' value (optionnal):");
    	if (!$content) {
    		$name = "shoutbox_" . $this->id . "_onclick_content_new";
    		$html .= self :: getNewContentUI($name, $onclick_allowed_content_types, $onclick_title);
    		$html .= $hint;
    	} else {
    		$html .= $content->getAdminUI(null, $onclick_title);
    		$html .= "<div class='admin_section_hint'>" . sprintf(_("Note that the onclick parameter will appear inside double quotes in html.  They must be properly encoded fot that context.  You can access the shout text in Javascript with: %s"), "document.getElementById('shout_text').value") . "</div>\n";
    		$html .= "</div>\n";
    		$html .= "<div class='admin_element_tools'>\n";
    		$name = "shoutbox_" . $this->id . "_onclick_content_erase";
    		$html .= "<input type='submit' class='submit' name='$name' value='" . sprintf(_("Delete %s (%s)"), _("onclick parameter"), get_class($content)) . "'>";

    	}    	    		$html .= "</div>\n";
    	$html .= "</li>\n";
    	return parent :: getAdminUI($html, $title);
    }

    /**
    * Processes the input of the administration interface for Picture
    *
    * @return void
    */
    public function processAdminUI() {
    	$db=AbstractDb::getObject();
    	if ($this->isOwner(User :: getCurrentUser()) || User :: getCurrentUser()->isSuperAdmin()) {
    		parent :: processAdminUI();
    		/* OnclickContent */
    		$content = $this->getOnClickContent();
    		if (!$content) {
    			$name = "shoutbox_" . $this->id . "_onclick_content_new";
    			$content = self :: processNewContentUI($name);
    			$this->setOnClickContent($content);
    		} else {
    			$name = "shoutbox_" . $this->id . "_onclick_content_erase";
    
    			if (!empty ($_REQUEST[$name]) && $_REQUEST[$name] == true) {
    				$this->setOnClickContent(null);
    				$content->delete($errmsg);
    			} else {
    				$content->processAdminUI();
    			}
    		}
    	}
    }

       /** Retreives the user interface of this object.  Anything that overrides this method should call the parent method with it's output at the END of processing.
      * @return The HTML fragment for this interface */
       public function getUserUI() {
        $html = '';
        $html_main = '';

        $db = AbstractDb::getObject();
        $node = Node::getCurrentNode();
        if($node) {
        	$node_id = $db->escapeString($node->getId());
        	$sql = "SELECT *, EXTRACT(EPOCH FROM creation_date) as creation_date_php FROM content_shoutbox_messages WHERE origin_node_id='$node_id' ORDER BY creation_date DESC LIMIT 5\n";
        	$db->execSql($sql, $rows, false);
        	if($rows) {
        		$html_main .= "<em>"._("Last five messages:")."</em>";
        		$html_main .= "<ul>";
        		foreach ($rows as $row) {
        			$user = User::getObject($row['author_user_id']);
        			$content = Content::getObject($row['message_content_id']);
        			$html_main .= "<li>";
        			$html_main .= $user->getListUI()."\n";
        			$html_main .= "<span class='date'>".strftime('%x', $row['creation_date_php'])."</span>\n";
        			$html_main .= "<div class='message'>".$content->getListUI()."</div>\n";
        			$html_main .= "</li>";
        		}
        		$html_main .= "</ul>";
        	}
        }
        else
        {
        	$html_main .= "<p>"._("Sorry, I am unable to determine your current node")."</p>\n";
        }

        $real_node = Node::getCurrentRealNode();
        //$real_node = Node::getCurrentNode();//For testing
        if($real_node) {
        	$html .= "<form action='".BASE_URL_PATH."content/ShoutBox/add_message.php'>\n";
        	$html .= "<input type='hidden' name='shoutbox_id' value='".$this->getId()."'/>\n";
        	//$html .= "destination_url: ";pretty_print_r($_SERVER);
        	$html .= "<input type='hidden' name='node_id' size='40' value='".$node->getId()."'/>\n";
        	$html .= "<input type='text' name='shout_text' id='shout_text' size='40' value=''/>\n";
        	$onclick_content = $this->getOnClickContent();
        	if($onclick_content){
        		$onclick="onclick=\"".$onclick_content->getString()."\"";
        	} else
        	{
        		$onclick = null;
        	}
        	$html .= "<input type='submit' name='shout_submit' $onclick value='"._("Shout!")."'>\n";
        	$html .= "</form>\n";
        }
        else
        {
        	$html .= "<p>"._("Sorry, you must be at a hotspot to use the shoutbox")."</p>\n";
        }

        $this->setUserUIMainDisplayContent($html_main);
        $this->setUserUIMainInteractionArea($html);
        return Content :: getUserUI();
       }

    /**
     * Reloads the object from the database.
     *
     * Should normally be called after a set operation.
     *
     * This function is private because calling it from a subclass will call the
     * constructor from the wrong scope
     *
     * @return void
    
     */
    private function refresh() {
    	$this->__construct($this->id);
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */