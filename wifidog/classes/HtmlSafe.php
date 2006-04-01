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
 * @subpackage Security
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 * @version    Subversion $Id: Cache.php 930 2006-01-29 23:15:52Z max-horvath $
 * @link       http://www.wifidog.org/
 */

/**
 * PEAR::HTML_Safe implementation
 *
 * @package    WiFiDogAuthServer
 * @subpackage Security
 * @author     Max Horvath <max.horvath@maxspot.de>
 * @copyright  2006 Max Horvath, maxspot GmbH
 */
class HtmlSafe
{

    /**
     * Defines if PEAR::HTML_Safe will be used or not
     *
     * @var bool
     * @access public
     */
    public $isHtmlSafeEnabled = false;

    /**
     * PEAR::HTML_Safe object.
     *
     * @var object
     * @access private
     */
    private $_HtmlSafe;

    /**
     * List of dangerous tags (such tags will be deleted)
     *
     * @var array
     * @access private
     */
    private $_deleteTags = array(
        'applet', 'base', 'basefont', 'bgsound', 'blink', 'body',
        'frame', 'frameset', 'head', 'html', 'ilayer', 'iframe',
        'layer', 'link', 'meta', 'style', 'title', 'script'
        );

    /**
     * List of dangerous tags (such tags will be deleted, and all content
     * inside this tags will be also removed)
     *
     * @var array
     * @access private
     */
    private $_deleteTagsContent = array('script', 'style', 'title', 'xml');

    /**
     * List of dangerous attributes
     *
     * @var array
     * @access private
     */
    private $_attributes = array('dynsrc', 'id', 'name');

    /**
     * Constructor.
     *
     * @return void
     *
     * @access public
     */
    public function __construct()
    {
        // Check if PEAR::HTML_Safe is available
        if (Dependencies::check("HtmlSafe")) {
            // Load PEAR::HTML_Safe
            require_once('HTML/Safe.php');

            // Enabled PEAR::HTML_Safe support
            $this->isHtmlSafeEnabled = true;

            // Create a PEAR::HTML_Safe object
            $this->_HtmlSafe = new HTML_Safe();

            // Define list of dangerous tags
            $this->_HtmlSafe->deleteTags = $this->getDeleteTags();

            // Define list of dangerous tags
            $this->_HtmlSafe->deleteTagsContent = $this->getDeleteTagsContent();

            // Define list of dangerous attributes
            $this->_HtmlSafe->attributes = $this->getAttributes();
        }
    }

    /**
     * Sets list of tags
     *
     * @param array $deleteTags List of tags
     * @param bool  $appendTags If set to true your list of tags will
     *                          be appended to the current list of tags
     *
     * @return bool True on a successful change of list of tags
     *
     * @access private
     */
    private function _setTags(&$tagList, $tags, $appendTags = false)
    {
        // Init values
        $_retVal = false;

        if (is_array($tags)) {
            if ($appendTags) {
                $tagList[] = $tags;
            } else {
                $tagList = $tags;
            }

            $_retVal = true;
        }

        return $_retVal;
    }

    /**
     * Returns list of dangerous tags
     *
     * @return array List of dangerous tags
     *
     * @access public
     */
    public function getDeleteTags()
    {
        return $this->_deleteTags;
    }

    /**
     * Sets list of dangerous tags
     *
     * @param array $deleteTags List of dangerous tags
     * @param bool  $appendTags If set to true your list of dangerous tags will
     *                          be appended to the current list of dangerous
     *                          tags
     *
     * @return bool True on a successful change of list of dangerous tags
     *
     * @access public
     */
    public function setDeleteTags($deleteTags, $appendTags = false)
    {
        $_retVal = $this->_setTags($this->_deleteTags, $deleteTags, $appendTags);

        if ($_retVal) {
            $this->_HtmlSafe->deleteTags = $this->getDeleteTags();
        }

        return $_retVal;
    }

    /**
     * Returns list of dangerous tags
     *
     * @return array List of dangerous tags
     *
     * @access public
     */
    public function getDeleteTagsContent()
    {
        return $this->_deleteTagsContent;
    }

    /**
     * Sets list of dangerous tags
     *
     * @param array $deleteTagsContent List of dangerous tags
     * @param bool  $appendTags        If set to true your list of dangerous
     *                                 tags will be appended to the current
     *                                 list of dangerous tags
     *
     * @return bool True on a successful change of list of dangerous tags
     *
     * @access public
     */
    public function setDeleteTagsContent($deleteTagsContent, $appendTags = false)
    {
        $_retVal = $this->_setTags($this->_deleteTagsContent, $deleteTagsContent, $appendTags);

        if ($_retVal) {
            $this->_HtmlSafe->deleteTags = $this->getDeleteTagsContent();
        }

        return $_retVal;
    }

    /**
     * Returns list of dangerous attributes
     *
     * @return array List of dangerous attributes
     *
     * @access public
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Sets list of dangerous attributes
     *
     * @param array $attributes List of dangerous attributes
     * @param bool  $appendTags If set to true your list of dangerous attributes
     *                          will be appended to the current list of
     *                          dangerous attributes
     *
     * @return bool True on a successful change of list of dangerous attributes
     *
     * @access public
     */
    public function setAttributes($attributes, $appendTags = false)
    {
        $_retVal = $this->_setTags($this->_attributes, $attributes, $appendTags);

        if ($_retVal) {
            $this->_HtmlSafe->deleteTags = $this->getAttributes();
        }

        return $_retVal;
    }

    /**
     * Parses and strips down all potentially dangerous content within HTML
     *
     * @param string $html HTML markup to be parsed
     *
     * @return string Parsed and stripped down HTML markup
     *
     * @access public
     */
    public function parseHtml($html)
    {
        return $this->_HtmlSafe->parse($html);
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

