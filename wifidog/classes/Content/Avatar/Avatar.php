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
 * Load required classes
 */
require_once('classes/Content/SimplePicture/SimplePicture.php');
/**
 * Represents an avatar
 *
 * @package    WiFiDogAuthServer
 * @subpackage ContentClasses
 * @author François Proulx <francois.proulx@gmail.com>
 * @copyright Copyright (c) 2007 François Proulx
 */
class Avatar extends SimplePicture
{
    /**
     * Constructor
     *
     * @param string $content_id Content id     */
    protected function __construct($content_id) {
        parent :: __construct($content_id);
        $this -> configEnableEditWidthHeight(false);
        $this -> configEnableHyperlink(false);

		// Max 40x40 pixels
        $this->setMaxDisplayWidth(40);
        $this->setMaxDisplayHeight(40);
    }

    public static function getDefaultUserUI() {
        $html = "<img src='" . COMMON_IMAGES_URL . "default_avatar.png' class='Avatar' />";
        return $html;
    }

    /**
	 * Validates the uploaded file and return a boolean to tell if valid
	 * This method should be overridden when you need to write special validation scripts
	 *
	 * @param string path to input file
	 * @param string path to output file (by ref.), making sure you create a struct that matches the $_FILES[][] format
	 *
	 * @return boolean
	 */
	protected function validateUploadedFile($input, &$output) {
		$errmsg = null;
		// Only if GD is available, resize to max size
		if (Dependency::check("gd", $errmsg)) {
			// Extract image metadata
			list($width_orig, $height_orig, $type, $attr) = getimagesize($input['tmp_name']);
			// Check if it busts the max size
			if($width_orig > $this->getMaxDisplayWidth() || $height_orig > $this->getMaxDisplayHeight()) {
				// Init with max values
				$width = $this->getMaxDisplayWidth();
				$height = $this->getMaxDisplayHeight();

				// Compute ratios
				$ratio_orig = $width_orig / $height_orig;
				if ($this->getMaxDisplayWidth() / $this->getMaxDisplayHeight() > $ratio_orig) {
				   $width =  $height * $ratio_orig;
				} else {
				   $height = $width / $ratio_orig;
				}

				// Resample
				$image_p = imagecreatetruecolor($width, $height);
				$image = imagecreatefromjpeg($input['tmp_name']);
				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

				// Build output metadata struct
				$output = array();
				$output['tmp_name'] = tempnam("/tmp", session_id());
				$output['type'] = "image/png";
				$output['name'] = $input['name'];

				// Output PNG at full compression (no artefact)
				imagepng($image_p, $output['tmp_name'], 9);

				// Write new file size
				$output['size'] = filesize($output['tmp_name']);
			}
		}
		return true;
	}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */