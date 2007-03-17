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
 * @copyright  2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2007 François Proulx
 * @link       http://www.wifidog.org/
 */

/**
 * Defines any type of content
 *
 * @package    WiFiDogAuthServer
 * @author     Benoit Grégoire <bock@step.polymtl.ca>
 * @author  2007 François Proulx
 * @copyright  2005-2006 Benoit Grégoire, Technologies Coeus inc.
 * @copyright  2007 François Proulx
 */
class ContentTypeFilter implements GenericObject {
	/** Object cache for the object factory (getObject())*/
    private static $instanceArray = array();
    
    private $_id = null;
    private $_label = null;
    private $_content_type_filter_rules_array = null; 
    
    private function __construct($content_type_filter_id = null) {    	
    	if($content_type_filter_id != null) {
    		$db = AbstractDb::getObject();
			$content_type_filter_id = $db->escapeString($content_type_filter_id);
			$_content_type_filter_row = null;
			
			$sql = "SELECT * FROM content_type_filters WHERE content_type_filter_id='{$content_type_filter_id}';";
			$db->execSqlUniqueRes($sql, $_content_type_filter_row, false);
			
			if ($_content_type_filter_row != null) {
				$this->_id = $_content_type_filter_row['content_type_filter_id'];
				$this->_label = $_content_type_filter_row['content_type_filter_label'];
				// Unserialize the string to an array
				$this->_content_type_filter_rules_array = unserialize($_content_type_filter_row['content_type_filter_rules']);
			}
			else {
				throw new Exception("The ContentTypeFilter with id $content_type_filter_id could not be found in the database!");
			}
    	}
    }

    /**
     * Get the ContentTypeFilter object
     *
     * @param string $criteria_array an array of functions call on the objects.  For each one, the method must exist and return true.
     * Format is array(array(callback_funct, array(callback_funct_parameters))
     * Note that callback_funct is a method of the object to be verified, so it must not have a static classname.
     * Example:  To get Simple content types that are a subclass of file, the array would be
     *                     $criteria_array = array(array('isSimpleContent'),
                                			array('isContentType',array(array('File')))
                                			);
     * Note the second callback: isContentType, takes a SINGLE parameter, but that parameter is an array.  
     * Since the $criteria_array specifies callback parameter as a list, passing 'File' to isContentType
     *  is written as array(array('File'))
     *
     * @return object The ContentTypeFilter object, or null if there was an error
     *                (an exception is also thrown)
     */
    public static function getObject($value) {
    	// Since PHP does not support polymorphism, we need to emulate it.
    	$retval = null;
    	
    	if(is_array($value)) {
    		// Create temporary object from array definition (for backward-compatibility)
    		$object = new self();
	        $object->_content_type_filter_rules_array = $value;
	        $retval = $object;
    	}
    	else if(is_string($value) && !empty($value)) {
    		// Create object from stored DB values
    		if(!isset(self::$instanceArray[$value]))
	        {
	        	self::$instanceArray[$value] = new self($value);
	        }
	        $retval = self::$instanceArray[$value];
    	}
    	
    	return $retval;
    }
    
    /**
     * Retreives the Id of the object
     *
     * @return string The Id
     */
	public function getId()
	{
		return $this->_id;
	}
	
    public function getLabel() {
    	return $this->_label;
    }
    
    public function setLabel($value) {
    	$db = AbstractDb::getObject();

	    // Init values
		$_retVal = true;

		if ($value != $this->getLabel()) {
			$value = $db->escapeString($value);
			$_retVal = $db->execSqlUpdate("UPDATE content_type_filters SET content_type_filter_label = '{$value}' WHERE content_type_filter_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
    }
    
    public function getRules() {
    	return $this->_content_type_filter_rules_array;
    }
    
    public function setRules(array $value) {
    	$db = AbstractDb::getObject();

	    // Init values
		$_retVal = true;

		if ($value != $this->getRules()) {
			// Need to serialize for the database
			$value = $db->escapeString(serialize($value));
			
			$_retVal = $db->execSqlUpdate("UPDATE content_type_filters SET content_type_filter_rules = '{$value}' WHERE content_type_filter_id = '{$this->getId()}'", false);
			$this->refresh();
		}

		return $_retVal;
    }
    
    /* Create a new ContentTypeFilter object in the database
	 *
	 * @param string $content_type_filter_id The id of the new content type filter. If absent,
	 * will be assigned a guid.
	 *
	 * @return mixed The newly created object, or null if there was an error
	 *
	 * @see GenericObject
	 *
	 * @static
	 * @access public
	 */
	public static function createNewObject($content_type_filter_id = null, $criteria_array = null)
	{
		$db = AbstractDb::getObject();

		if (empty($content_type_filter_id)) {
			$content_type_filter_id = get_guid();
		}
		
		if($criteria_array != null && is_array($criteria_array)) {
			$content_type_filter_id = $db->escapeString($content_type_filter_id);
			// Serialize the array for storage in db.
			$criteria_array_string = $db->escapeString(serialize($criteria_array));
			
			$sql = "INSERT INTO content_type_filters (content_type_filter_id, content_type_filter_rules) VALUES ('{$content_type_filter_id}', '{$criteria_array_string}')";
	
			if (!$db->execSqlUpdate($sql, false)) {
				throw new Exception(_('Unable to insert the new ContentTypeFilter in the database!'));
			}
	
			return self::getObject($content_type_filter_id);
		}
		else
			return null;
	}
	
	/* Get an interface to create a new ContentTypeFilter
     *
     * @return string HTML markup

     */
	public static function getCreateNewObjectUI()
	{
	    // Init values
		$_html = '';

		$_name = "new_content_type_filter_rules";

		$_html .= "<b>"._("Add a new content type filter with these rules") . ": </b><br/>";
		$_html .= "<pre>array (\r\n\tarray('isContentType',\r\n\t\tarray (\r\n\t\t\tarray (\r\n\t\t\t\t'SimplePicture'\r\n\t)\r\n\t\t)\r\n\t)\r\n)</pre><br/>";
		$_html .= "<textarea cols='50' rows='10' name='{$_name}'></textarea><br/>";

		return $_html;
	}
	
	
    /**
     * Process the new object interface.
     *
     * Will return the new object if the user has the credentials and the form
     * was fully filled.
     *
     * @return string The ContentTypeFilter object or null if no new ContentTypeFilter was created

     */
	public static function processCreateNewObjectUI()
	{
		require_once('classes/User.php');
		
	    // Init values
		$_retVal = null;

		$_name = "new_content_type_filter_rules";

		if (!empty($_REQUEST[$_name])) {
			// Prepend return so that the eval call actually builds the array
			$new_content_type_filter_rules =  self::parseScalarArray($_REQUEST[$_name]);

			// Make sure the string eval'd to an array
			if (!empty($new_content_type_filter_rules) && is_array($new_content_type_filter_rules)) {
				if (!User::getCurrentUser()->isSuperAdmin()) {
					throw new Exception(_("Access denied"));
				}
				// Let the system create a GUID
				$_retVal = self::createNewObject(null, $new_content_type_filter_rules);
			}
		}

		return $_retVal;
	}
	
	/**
	 * Get an interface to pick a ContentTypeFilter
	 *
	 * If there is only one server available, no interface is actually shown
	 *
     * @param string $user_prefix         A identifier provided by the
     *                                    programmer to recognise it's generated
     *                                    html form
     * @param object $pre_content_type_filter An optional ContenTypeFilter object
     * 
     * @param string $additional_where    Additional SQL conditions for the
     *                                    servers to select
     *
     * @return string HTML markup

     */
    public static function getSelectContentTypeFilterUI($user_prefix, $pre_selected_content_type_filter = null, $additional_where = null)
    {
		$db = AbstractDb::getObject();

        // Init values
		$_html = "";
		$_content_type_filter_rows = null;
		
		if ($pre_selected_content_type_filter) {
			$_selectedId = $pre_selected_content_type_filter->getId();
		} else {
			$_selectedId = null;
		}

		$additional_where = $db->escapeString($additional_where);

		$_sql = "SELECT * FROM content_type_filters WHERE 1=1 $additional_where ORDER BY content_type_filter_label ASC";
		$db->execSql($_sql, $_content_type_filter_rows, false);

		if ($_content_type_filter_rows == null) {
			return $_html;
		}

		$_name = $user_prefix;
		$_html .= _("Content type filter").": \n";
		$_numberOfContentTypeFilters = count($_content_type_filter_rows);

		if ($_numberOfContentTypeFilters > 1) {
			$_i = 0;

			foreach ($_content_type_filter_rows as $_content_type_filter_row) {
				$_tab[$_i][0] = $_content_type_filter_row['content_type_filter_id'];
				$_tab[$_i][1] = empty($_content_type_filter_row['content_type_filter_label']) ? "["._("No label")."] - ".$_content_type_filter_row['content_type_filter_id'] : $_content_type_filter_row['content_type_filter_label'];
				$_i ++;
			}

			$_html .= FormSelectGenerator::generateFromArray($_tab, $_selectedId, $_name, null, false);
		} else {
			foreach ($_content_type_filter_rows as $_content_type_filter_row) {
				$_html .= " {$_content_type_filter_row['content_type_filter_label']} ";
				$_html .= "<input type='hidden' name='$_name' value='{$_content_type_filter_row['content_type_filter_id']}'>";
			}
		}

		return $_html;
	}
	
	public static function getAllContentTypeFilters() {

        $db = AbstractDb :: getObject();

        // Init values
        $rows = null;
        $objects = array ();

        $db->execSql("SELECT content_type_filter_id FROM content_type_filters", $rows, false);

        if ($rows) {
            foreach ($rows as $row) {
                $objects[] = self :: getObject($row['content_type_filter_id']);
            }
        }

        return $objects;
    }
	
	
    /**
     * Retreives the admin interface of this object
     *
     * @return string The HTML fragment for this interface
     */
	public function getAdminUI()
	{
	    // Init values
		$_html = '';

		$_html .= "<fieldset class='admin_container ".get_class($this)."'>\n";
		$_html .= "<legend>"._("ContentTypeFilter management")."</legend>\n";
        $_html .= "<ul class='admin_element_list'>\n";
        
		// content_type_filter_id
		$_value = htmlspecialchars($this->getId(), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("ContentTypeFilter ID") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= $_value;
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		// label
		$_name = "content_type_filter_" . $this->getId() . "_label";
		$_value = htmlspecialchars($this->getLabel(), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Label") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<input type='text' size='50' value='$_value' name='$_name'>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

		// rules
		$_name = "content_type_filter_" . $this->getId() . "_rules";
		$_value = htmlspecialchars(var_export($this->getRules(), true), ENT_QUOTES);

		$_html .= "<li class='admin_element_item_container'>\n";
		$_html .= "<div class='admin_element_label'>" . _("Rules (must be a valid array definition)") . ":</div>\n";
		$_html .= "<div class='admin_element_data'>\n";
		$_html .= "<pre>array (\r\n\tarray('isContentType',\r\n\t\tarray (\r\n\t\t\tarray (\r\n\t\t\t\t'SimplePicture'\r\n\t\t\t)\r\n\t\t)\r\n\t)\r\n)</pre>";
		$_html .= "<textarea cols='50' rows='10' name='{$_name}'>{$_value}</textarea><br/>\n";
		$_html .= "</div>\n";
		$_html .= "</li>\n";

        $_html .= "</ul>\n";
        $_html .= "</fieldset>\n";
		return $_html;
	}

    /**
     * Process admin interface of this object
     *
     * @return void
     */
	public function processAdminUI()
	{
        require_once('classes/User.php');

        try {
    		if (!User::getCurrentUser()->isSuperAdmin()) {
    			throw new Exception(_('Access denied!'));
    		}
        } catch (Exception $e) {
            $ui = MainUI::getObject();
            $ui->setToolSection('ADMIN');
            $ui->displayError($e->getMessage(), false);
            exit;
        }

		// label
		$_name = "content_type_filter_" . $this->getId() . "_label";
		$this->setLabel($_REQUEST[$_name]);

		// rules
		$_name = "content_type_filter_" . $this->getId() . "_rules";
		$new_rules_array = self::parseScalarArray($_REQUEST[$_name]);
		if(is_array($new_rules_array))
			$this->setRules($new_rules_array);
		else {
			echo _("The rules must be given as a PHP array declaration.");
		}		
	}

    /**
     * Delete this Object form the it's storage mechanism
     *
     * @param string &$errmsg Returns an explanation of the error on failure
     *
     * @return bool True on success, false on failure or access denied
     */
	public function delete(&$errmsg)
	{
	    require_once('classes/User.php');
        
		$db = AbstractDb::getObject();

	    // Init values
		$_retVal = false;

		if (!User::getCurrentUser()->isSuperAdmin()) {
			$errmsg = _('Access denied (must have super admin access)');
		} else {
			$_id = $db->escapeString($this->getId());

			if (!$db->execSqlUpdate("DELETE FROM content_type_filters WHERE content_type_filter_id='{$_id}'", false)) {
				$errmsg = _('Could not delete ContentTypeFilter!');
			} else {
				$_retVal = true;
			}
		}

		return $_retVal;
	}
	
	
    /**
     * Reloads the object from the database
     *
     * Should normally be called after a set operation
     *
     * @return void     */
	protected function refresh()
	{
		$this->__construct($this->_id);
	}
	
    /** Is this class name an acceptable content type?  Will call all functions in the criteria array, but ADDING THE CANDIDATE CLASSNAME as the LAST parameter.
     * @param string $classname The classname to check
     * @return true or false.  Will also silently return false if the class does not exist */
    public function isAcceptableContentClass($classname) {
        $retval = true;
                //pretty_print_r($this->getRules());
                //$reflector = new ReflectionClass($classname);
                //$methods = $reflector->getMethods();
                //pretty_print_r($methods);
        if(is_array($this->getRules()))
        {
            foreach ($this->getRules() as $criteria) {
                //echo "call_user_func_array called on $classname with: ";
                //pretty_print_r($criteria);

                if(is_callable(array($classname,$criteria[0])) === false)
                {
                    throw new exception (sprintf("Class %s does not implement method %s", $classname, $criteria[0]));
                }

                $criteria[1][]=$classname;
                if(!call_user_func_array(array($classname,$criteria[0]), $criteria[1])) {
                    //The content type does not meet the criteria
                    $retval=false;
                    break;
                }
                //$retval ? $result ="TRUE" : $result="FALSE";
                //echo "call_user_func_array result: $result<br>";
            }
        }
        return $retval;
    }
    
    /** 
     * Parses a string containing an complex array in PHP array() syntax 
     * All leaves must be strings Indexes aren't supported
     * @param $string The string to be parsed
     * @return array
     */ 
	public static function parseScalarArray($string, $debug = false){
	    if($debug)
	        echo "Original string: <br/>$string";
	
	    $chunks = preg_split ( "/(array|,|\(|\))+?/",$string, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY );
	    foreach ($chunks as $index=>$chunk) {
	        $chunks[$index] = trim($chunk);
	        if(empty($chunks[$index])) {
	            unset($chunks[$index]);
	        }
	    }
	    
	    if($debug)
	        pretty_print_r($chunks);
	        
	    $retval = null;
	    $current=null;
	    $numParen=0;
	    foreach ($chunks as $index=>$chunk) { 
	        //Strip the bloody array indexes!
	        preg_match ( "/(?:\s*\d*\s=>\s*)?(.*)/", $chunk , $matches );
	        //pretty_print_r($matches);
	        $chunk = $matches[1];
	        
	        switch ($chunk) {
	            case 'array': ;
	            	break;
	            case ',': ;
	            	break;
	            case '(': ;
		            $numParen++;
		            $parent=$current;
		            $current['array']=array();
		            $current['parent']=$parent;
		            break;
	            case ')':
	                $numParen--;
	                if($current['parent']==null)
	                break;
	                $current['parent']['array'][] = $current['array'];
	                //pretty_print_r($current);
	                $current = $current['parent'];
	                break;
	            default:
	                $chunk = trim($chunk,"\"'");
	                if(!empty($chunk)) {
	                $current['array'][] = trim($chunk,"\"'");
	                }
	        }
	        
	        if($debug && $chunk!='array' && $chunk!=','){
	            echo "Chunk: $chunk  numParen: $numParen, current:";
	            pretty_print_r($current);
	            echo "<hr/>";
	        }
	    }
	    
	    if ($numParen != 0)
	        throw new exception ("Unparseable string");
	        
	    $retval = $current['array'];
	    return $retval;
	}	
    
} //end class
/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */