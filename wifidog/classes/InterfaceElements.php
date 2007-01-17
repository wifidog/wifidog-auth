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
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: FormSelectGenerator.php 915 2006-01-23 05:26:20Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * @package    WiFiDogAuthServer
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
class InterfaceElements
{
    /**
     * Generates a closing HTML element
     *
     * @param string $name    Name of HTML element
     * @param string $content Content of HTML element
     * @param array  $tags    Array of HTML tags
     *
     * @return string HTML markup
     *
     * @throws Exception if $name is missing
     * @throws Exception if $tags is not an array
     *

     */
    private static function _generateClosingElement($name, $content = "", $tags = array())
    {
        // Init values
        $_retVal = "";

        if ($name) {
            $_retVal .= "<" . $name;

            // Add tags
            if (is_array($tags)) {
                foreach ($tags as $_tagName => $_tagValue) {
                    $_retVal .= ' ' . $_tagName . '="' . $_tagValue . '"';
                }
            } else {
                throw new Exception("InterfaceElements::_generateClosingElement() - \$tags is not an array.");
            }

            $_retVal .= ">$content</$name>";
        } else {
            throw new Exception("InterfaceElements::_generateClosingElement() - \$name is missing.");
        }

        return $_retVal;
    }

    /**
     * Generates a non-closing HTML element
     *
     * @param string $name  Name of HTML element
     * @param array  $tags  Array of HTML tags
     *
     * @return string HTML markup
     *
     * @throws Exception if $name or $tags is missing
     * @throws Exception if $tags is not an array
     *

     */
    private static function _generateNonClosingElement($name, $tags)
    {
        // Init values
        $_retVal = "";

        if ($name && $tags) {
            $_retVal .= "<" . $name;

            // Add tags
            if (is_array($tags)) {
                foreach ($tags as $_tagName => $_tagValue) {
                    $_retVal .= ' ' . $_tagName . '="' . $_tagValue . '"';
                }
            } else {
                throw new Exception("InterfaceElements::_generateNonClosingElement() - \$tags is not an array.");
            }

            $_retVal .= " />";
        } else {
            throw new Exception("InterfaceElements::_generateNonClosingElement() - \$name and/or \$tags is missing.");
        }

        return $_retVal;
    }

    /**
     * Generates a HTML input element
     *
     * @param string $type            Type of input element
     * @param string $name            Name of HTML input element
     * @param string $value           Value of HTML input element
     * @param string $id              ID of HTML input element
     * @param string $class           Class of HTML input element
     * @param array  $additional_tags Additional tags of HTML input element
     *
     * @return string HTML markup
     *
     * @throws Exception if $type or $name is missing
     *

     */
    private static function _generateInputTag($type, $name, $value = "", $id = "", $class = "submit", $additional_tags = array())
    {
        // Init values
        $_retVal = "";

        if ($type && $name) {
            $_tags = array(
                "type" => $type,
                "name" => $name
                );

                // Check for existing value tag
                if ($value != "") {
                    $_tags = array_merge($_tags, array("value" => $value));
                }

                // Check for existing id tag
                if ($id != "") {
                    $_tags = array_merge($_tags, array("id" => $id));
                }

                // Check for existing class tag
                if ($class != "") {
                    $_tags = array_merge($_tags, array("class" => $class));
                }

                // Check for additional tags to be used
                if (count($additional_tags) > 0) {
                    $_tags = array_merge($_tags, $additional_tags);
                }

                $_retVal = self::_generateNonClosingElement("input", $_tags);
        } else {
            throw new Exception("InterfaceElements::_generateInputTag() - \$type and/or \$name is missing.");
        }

        return $_retVal;
    }

    /**
     * DEPRECATED!  Generates a HTML div element
     *
     * @param string $content Content of HTML div element
     * @param string $class   Class of HTML div element
     * @param string $id      Id of HTML div element
     *
     * @return string HTML markup

     */
    private static function generateDiv($content = "", $class = "", $id = "")
    {
        // Init values
        $_retVal = "";
        $_tags = array();

        // Check for present class tag
        if ($class != "") {
            $_tags = array_merge($_tags, array("class" => $class));
        }

        // Check for present id tag
        if ($id != "") {
            $_tags = array_merge($_tags, array("id" => $id));
        }

        $_retVal = self::_generateClosingElement("div", $content, $_tags);

        return $_retVal;
    }

    /** DEPRECATED benoitg 2007-01-16
     * Generates a HTML input (type = button) element
     *
     * @param string $name            Name of HTML input element
     * @param string $value           Value of HTML input element
     * @param string $id              ID of HTML input element
     * @param string $class           Class of HTML input element
     * @param array  $additional_tags Additional tags of HTML input element
     *
     * @return string HTML markup
     *
     * @throws Exception if $name or $value is missing

     */
    public static function generateInputButton($name, $value, $id = "", $class = "submit", $additional_tags = array())
    {
        // Init values
        $_retVal = "";

        if ($name && $value) {
            $_retVal = self::_generateInputTag("button", $name, $value, $id, $class, $additional_tags);
        } else {
            throw new Exception("InterfaceElements::generateInputSubmit() - \$name and/or \$value is missing.");
        }

        return $_retVal;
    }

    /** DEPRECATED benoitg 2007-01-16
     * Generates a HTML input (type = checkbox) element
     *
     * @param string $name                  Name of HTML input element
     * @param string $value                 Value of HTML input element
     * @param string $description           Description of HTML input element
     * @param bool   $checked               Shall the HTML element be checked?
     * @param string $id                    ID of HTML input element
     * @param bool   $in_div                Put HTML markup in a div element?
     * @param string $class                 Class of HTML input element
     * @param array  $additional_tags       Additional tags of HTML input element
     * @param array  $additional_tags_label Additional tags of HTML input element
     *
     * @return string HTML markup
     *
     * @throws Exception if $name or $value is missing

     */
    public static function generateInputCheckbox($name, $value = "", $description = "", $checked = false, $id = "", $in_div = true, $class = "checkbox", $additional_tags = array(), $additional_tags_label = array())
    {
        // Init values
        $_retVal = "";

        if ($name) {
            // Check if HTML element shall be checked
            if ($checked === true) {
                $additional_tags = array_merge($additional_tags, array("checked" => "checked"));
            }

            $_retVal = self::_generateInputTag("checkbox", $name, $value, $id, $class, $additional_tags);

            // Generate label if $description and $id has been defined
            if ($description && $id) {
                $_retVal .= self::generateLabel($description, $id, $additional_tags_label);
            }

            // Check if HTML markup should be put into a div element
            if ($in_div === true) {
                $_retVal = self::generateDiv($_retVal);
            }
        } else {
            throw new Exception("InterfaceElements::generateInputCheckbox() - \$name is missing.");
        }

        return $_retVal;
    }

    /** DEPRECATED benoitg 2007-01-16
     * Generates a HTML input (type = hidden) element
     *
     * @param string $name            Name of HTML input element
     * @param string $value           Value of HTML input element
     * @param array  $additional_tags Additional tags of HTML input element
     *
     * @return string HTML markup
     *
     * @throws Exception if $name is missing

     */
    public static function generateInputHidden($name, $value = "", $additional_tags = array())
    {
        // Init values
        $_retVal = "";

        if ($name) {
            $_retVal = self::_generateInputTag("hidden", $name, $value, "", "", $additional_tags);
        } else {
            throw new Exception("InterfaceElements::generateInputHidden() - \$name is missing.");
        }

        return $_retVal;
    }

    /** DEPRECATED benoitg 2007-01-16
     * Generates a HTML input (type = submit) element
     *
     * @param string $name            Name of HTML input element
     * @param string $value           Value of HTML input element
     * @param string $id              ID of HTML input element
     * @param string $class           Class of HTML input element
     * @param array  $additional_tags Additional tags of HTML input element
     *
     * @return string HTML markup
     *
     * @throws Exception if $name or $value is missing

     */
    public static function generateInputSubmit($name, $value, $id = "", $class = "submit", $additional_tags = array())
    {
        // Init values
        $_retVal = "";

        if ($name && $value) {
            $_retVal = self::_generateInputTag("submit", $name, $value, $id, $class, $additional_tags);
        } else {
            throw new Exception("InterfaceElements::generateInputSubmit() - \$name and/or \$value is missing.");
        }

        return $_retVal;
    }

    /** DEPRECATED benoitg 2007-01-16
     * Generates a HTML input (type = text) element
     *
     * @param string $name            Name of HTML input element
     * @param string $value           Value of HTML input element
     * @param string $id              ID of HTML input element
     * @param string $class           Class of HTML input element
     * @param array  $additional_tags Additional tags of HTML input element
     *
     * @return string HTML markup
     *
     * @throws Exception if $name is missing

     */
    public static function generateInputText($name, $value = "", $id = "", $class = "input_text", $additional_tags = array())
    {
        // Init values
        $_retVal = "";

        if ($name) {
            $_retVal = self::_generateInputTag("text", $name, $value, $id, $class, $additional_tags);
        } else {
            throw new Exception("InterfaceElements::generateInputText() - \$name is missing.");
        }

        return $_retVal;
    }

    /**
     * Generates a HTML label element
     *
     * @param string $content         Content of HTML label element
     * @param string $for             Name of which the HTML label element
     *                                shows to
     * @param array  $additional_tags Additional tags of HTML label element
     *
     * @return string HTML markup
     *
     * @throws Exception if $for or $content is missing

     */
    private static function generateLabel($content, $for, $additional_tags = array())
    {
        // Init values
        $_retVal = "";

        if ($content && $for) {
            $_tags = array("for" => $for);

            // Check for additional tags to be used
            if (count($additional_tags) > 0) {
                $_tags = array_merge($_tags, $additional_tags);
            }

            $_retVal = self::_generateClosingElement("label", $content, $_tags);
        } else {
            throw new Exception("InterfaceElements::generateLabel() - \$for is missing.");
        }

        return $_retVal;
    }

    /**
     * Generates the divs for an HTML "admin_element_item_container" element
     *
     * @param string $title   Title of HTML element
     * @param string $data    Data  of HTML element
     * @param string $tools   Tools of HTML element
     * @param string $main_id Id of parent HTML element
     *
     * @return string HTML markup

     */
    public static function generateAdminSection($title = "", $data = "", $tools = "", $main_id = "")
    {
        // Init values
        $_retVal = "";
        $_title = "";
        $_data = "";
        $_tools = "";

        // Process title of admin section container
        if ($title != "") {
            $_title = self::generateDiv($title . ":", "admin_element_label", ($main_id ? $main_id . "_title" : ""));
        }

        // Process data of admin section container
        if ($data != "") {
            $_data = self::generateDiv($data, "admin_element_data", ($main_id ? $main_id . "_data" : ""));
        }

        // Process tools of admin section container
        if ($tools != "") {
            $_tools = self::generateDiv($tools, "admin_element_tools", ($main_id ? $main_id . "_tools" : ""));
        }

        // Generate final HTML markup
        $_retVal = $_title . $_data . $_tools;

        return $_retVal;
    }

    /**  DEPRECATED benoitg 2007-01-16
     * Generates an HTML "admin_element_item_container" element
     *
     * @param string $id       Id of HTML element
     * @param string $title    Title of HTML element
     * @param string $data     Data  of HTML element
     * @param string $tools    Tools of HTML element
     *
     * @return string HTML markup

     */
    public static function generateAdminSectionContainer($id = "", $title = "", $data = "", $tools = "")
    {

        // Process title of admin section container
        if ($title != "")
        $title = "<legend>$title</legend>";

        $_retVal = "<fieldset>$title\n";
        $_retVal .= self::generateDiv(self::generateAdminSection(null, $data, $tools, $id), "admin_element_item_container", $id);
        $_retVal .= "</fieldset>\n";
        return $_retVal;
    }

    /**
     * Generates the divs for an HTML "admin_element_item_container" element
     *
     * @param string $title   Title of HTML element
     * @param string $help    Help text for the item
     * @param string $data    Data  of HTML element
     * @param string $tools   The interactive portion of the form (buttons, etc.) if applicable
     * @param string $main_id Id of parent HTML element
     *
     * @return string HTML markup

     */
    public static function genSectionItem($data = "", $title = "", $help="", $tools = "")
    {
        // Init values
        $_retVal = "";
        $_title = "";
        $_data = "";
        $_tools = "";

        // Process title of admin section container
        if ($title != "") {
            $_title = self::generateDiv($title . ":", "admin_element_label");
        }

        // Process data of admin section container
        if ($data != "") {
            $_data = self::generateDiv($data, "admin_element_data");
        }

        // Process tools of admin section container
        if ($tools != "") {
            $_tools = self::generateDiv($tools, "admin_element_tools");
        }

        // Generate final HTML markup
        $_retVal = $_title . " " . $_data . $_tools . $help;

        return $_retVal;
    }
    
    /** Generates an HTML "admin_section" element
     *
     * @param string $$content_array  array of HTML content to bu part of the set
     * @param string $title    Title of the section
     * @param string $help Help text for the section
     * @param string $collapseable     Can the section be collapsed
     * @param string $defaultCollapsed    Is the section collapsed by default
     * @param string $additionalCSSClass  Additional CSS class for the fieldset
     * @return string HTML markup

     */
    public static function genSection($content_array, $title = "", $help="", $collapseable = false, $defaultCollapsed = false, $additionalCSSClass=null)
    {
        $retval = '';
        // Process title of admin section container
        if ($title != "")
        $title = "<legend>$title</legend>";
        $retval .= "<fieldset class='admin_element_group $additionalCSSClass'>$title";
        if($help) {
            $retval .= "<div>$help</div>\n";
        }
        if(is_array($content_array)) {
            $retval .= "<ul class='admin_element_list'>";
            foreach ($content_array as $content_item) {
                $retval .=  "<li class='admin_element_item_container'>\n".$content_item."\n</li>\n";
            }
            $retval .= "</ul>";
        } else {
            throw new Exception("InterfaceElements::generateLabel() - \$for is missing.");
        }
        $retval .= "</fieldset>\n";
        return $retval;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

