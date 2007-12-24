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
 * @subpackage NodeLists
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 * @version    Subversion $Id: $
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once('classes/Dependency.php');
require_once('classes/NodeList.php');
require_once('classes/MainUI.php');
require_once('classes/Network.php');
require_once('classes/Node.php');
require_once('classes/User.php');

// Check for FPDF library
if (Dependency::check("FPDF")) {
    // Start server load check
    $_serverBusy = false;

    if (PHP_OS != "Windows" && PHP_OS != "Darwin" && @file_exists('/proc/loadavg') && $_loadavgFile = @file_get_contents('/proc/loadavg')) {
        $_loadavg = explode(' ', $_loadavgFile);

        if (trim($_loadavg[0]) > 5) {
            $_serverBusy = true;
        }
    }

    if (!$_serverBusy) {
        // Load FPDF library
        require_once("lib/fpdf153/fpdf.php");

        /**
         * PDF class of WiFiDog, extends FPDF library
         *
         * Extended private functions don't use the "private" construct used to
         * declare the function as the parent class is written in PHP4-style. Every
         * function in a PHP4-style-class is public. A child class cannot change the
         * visibility of a parents function.
         *
         * @package    WiFiDogAuthServer
         * @author     Max Horváth <max.horvath@freenet.de>
         * @copyright  2006 Max Horváth, Horvath Web Consulting
         */
        class PdfWiFiDog extends FPDF
        {
            /**
             * Is document is protected?
             *
             * @var bool

             */
            private $_encrypted;

            /**
             * U entry in pdf document
             *
             * @var string

             */
            private $_uValue;

            /**
             * O entry in pdf document
             *
             * @var string

             */
            private $_oValue;

            /**
             * P entry in pdf document
             *
             * @var string

             */
            private $_pValue;

            /**
             * Encryption object id
             *
             * @var string

             */
            private $_encObjectId;

            /**
             * Last encrypted RC4 key (cached for optimization)
             *
             * @var string

             */
            private $_lastRC4Key;

            /**
             * Last computed RC4 key
             *
             * @var string

             */
            private $_lastRC4KeyC;

            /**
             * Array of column widths
             *
             * @var array

             */
            private $_widths;

            /**
             * Array of column alignments
             *
             * @var array

             */
            private $_aligns;

            /**
             * Constructor
             *
             * @param string $orientation Oriantation of generated page
             *                              - P for portrait
             *                              - L for landscape
             * @param string $unit        In which unit shall this class compute?
             *                              - pt
             *                              - mm
             *                              - cm
             *                              - in
             * @param string $format      Format of generated page
             *                              - letter
             *                              - legal
             *                              - a3
             *                              - a4
             *                              - a5
             *
             * @return void
             */
            public function __construct($orientation = 'L', $unit = 'mm', $format = 'letter')
            {
                // Call parent constructor
                parent::FPDF($orientation, $unit, $format);

                $this->_encrypted = false;
                $this->_lastRC4Key = '';
                $this->padding = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08" .
                "\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
            }

            /**
             * Function to set permissions as well as user and owner passwords
             *
             * @param array  $permissions Array with values taken from the
             *                            following list:
             *                              - copy
             *                              - print
             *                              - modify
             *                              - annot-forms
             *                            If a value is present it means that the
             *                            permission is granted.
             * @param string $userPass    If an user password is set, the user will
             *                            be prompted for it before the document
             *                            will be opened
             * @param string $ownerPass   If an owner password is set, the document
             *                            can be opened in privilege mode with no
             *                            restrictions if that password is entered
             *
             * @return void
             */
            public function SetProtection($permissions = array(), $userPass = '', $ownerPass = null)
            {
                // Init values
                $_options = array("print" => 4, "modify" => 8, "copy" => 16, "annot-forms" => 32);
                $_protection = 192;

                // Set permissions
                foreach ($permissions as $_permission) {
                    if (!isset($_options[$_permission])) {
                        $this->Error("Incorrect permission: " . $_permission);
                    }

                    // Update protection of document
                    $_protection += $_options[$_permission];
                }

                // If no owner password has been defined generate a random one
                if ($ownerPass === null) {
                    $ownerPass = uniqid(rand());
                }

                $this->_encrypted = true;

                $this->_generateencryptionkey($userPass, $ownerPass, $_protection);
            }

            /**
             * Set the array of column widths
             *
             * @param array $w Array of column widths
             *
             * @return void
             */
            public function SetWidths($w)
            {
                $this->_widths = $w;
            }

            /**
             * Set the array of column alignments
             *
             * @param array $a Array of column alignments
             *
             * @return void
             */
            function SetAligns($a)
            {
                $this->_aligns = $a;
            }

            /**
             * Generates a table with containing the nodes data
             *
             * @param array $header Header of table
             * @param array $data   Data of table
             *
             * @return void
             */
            public function nodeList($header, $data)
            {
                // Calculate the height of the row
                $_nb = 0;

                for ($_i = 0; $_i < count($header); $_i++) {
                    $_nb = max($_nb, $this->_nbLines($this->_widths[$_i], $header[$_i]));
                }

                $_h = 5 * $_nb;

                // Issue a page break first if needed
                $this->_checkPageBreak($_h);

                // Draw the Header
                for ($_i = 0; $_i < count($header); $_i++) {
                    $_w = $this->_widths[$_i];
                    $_a = $this->_aligns[$_i];

                    // Save the current position
                    $_x = $this->GetX();
                    $_y = $this->GetY();

                    // Draw the border
                    $this->Rect($_x, $_y, $_w, $_h);

                    // Enable bold text
                    $this->SetFont("", "B");

                    // Print the text
                    $this->MultiCell($_w, 5, utf8_decode($header[$_i]), 0, "C");

                    // Disable bold text
                    $this->SetFont("");

                    // Put the position to the right of the cell
                    $this->SetXY($_x + $_w, $_y);
                }

                // Go to the next line
                $this->Ln($_h);

                // Draw the Data
                foreach ($data as $_row) {
                    // Calculate the height of the row
                    $_nb = 0;

                    for ($_i = 0; $_i < 8; $_i++) {
                        // Define which array data to use
                        switch ($_i) {
                            case 0:
                                $_dataPart = $_row['name'];
                                break;

                            case 1:
                                $_dataPart = $_row['civic_number'] . " " . $_row['street_name'];
                                break;
                            case 2:
                                $_dataPart = $_row['postal_code'];
                                break;

                            case 3:
                                $_dataPart = $_row['city'];
                                break;

                            case 4:
                                $_dataPart = $_row['province'];
                                break;

                            case 5:
                                $_dataPart = $_row['public_phone_number'];
                                break;

                            case 6:
                                $_dataPart = $_row['public_email'];
                                break;

                            case 7:
                                $_dataPart = str_replace(array("http://", "https://"), "", $_row['home_page_url']);
                                break;

                            default:
                                throw new Exception("Error: PdfWifidog::nodeList(): Fatal error while defining which array data to use.");
                                break;
                        }

                        $_nb = max($_nb, $this->_nbLines($this->_widths[$_i], $_dataPart));
                    }

                    $_h = 5 * $_nb;

                    // Issue a page break first if needed
                    $this->_checkPageBreak($_h);

                    // Draw the data
                    for ($_i = 0; $_i < 8; $_i++) {
                        // Define which array data to use
                        switch ($_i) {
                            case 0:
                                $_dataPart = $_row['name'];
                                break;

                            case 1:
                                if (defined("ORDER_CIVIC_NUMBER") && ORDER_CIVIC_NUMBER == "street_name_first") {
                                    $_dataPart = $_row['street_name'] . " " . $_row['civic_number'];
                                } else {
                                    $_dataPart = $_row['civic_number'] . " " . $_row['street_name'];
                                }
                                break;
                            case 2:
                                $_dataPart = $_row['postal_code'];
                                break;

                            case 3:
                                $_dataPart = $_row['city'];
                                break;

                            case 4:
                                $_dataPart = $_row['province'];
                                break;

                            case 5:
                                $_dataPart = $_row['public_phone_number'];
                                break;

                            case 6:
                                $_dataPart = $_row['public_email'];
                                break;

                            case 7:
                                $_dataPart = str_replace(array("http://", "https://"), "", $_row['home_page_url']);
                                break;

                            default:
                                throw new Exception("Error: PdfWifidog::nodeList(): Fatal error while defining which array data to use.");
                                break;
                        }

                        // Get styles
                        $_w = $this->_widths[$_i];
                        $_a = $this->_aligns[$_i];

                        // Save the current position
                        $_x = $this->GetX();
                        $_y = $this->GetY();

                        // Draw the border
                        $this->Rect($_x, $_y, $_w, $_h);

                        // Print the text
                        $this->MultiCell($_w, 5, utf8_decode($_dataPart), 0, $_a);

                        // Put the position to the right of the cell
                        $this->SetXY($_x + $_w, $_y);
                    }

                    // Go to the next line
                    $this->Ln($_h);
                }
            }

            /**
             * Defines basic values of the PDF file
             *
             * @return void

             */
            function _putinfo()
            {
                if (defined("WIFIDOG_NAME")) {
                    $this->_out('/Producer ' . $this->_textstring(WIFIDOG_NAME));
                } else {
                    $this->_out('/Producer ' . $this->_textstring('WiFiDog Authentication Server'));
                }

                if (!empty($this->title)) {
                    $this->_out('/Title ' . $this->_textstring($this->title));
                }

                if (!empty($this->subject)) {
                    $this->_out('/Subject ' . $this->_textstring($this->subject));
                }

                if (!empty($this->author)) {
                    $this->_out('/Author ' . $this->_textstring($this->author));
                }

                if (!empty($this->keywords)) {
                    $this->_out('/Keywords ' . $this->_textstring($this->keywords));
                }

                if (!empty($this->creator)) {
                    $this->_out('/Creator ' . $this->_textstring($this->creator));
                }

                $this->_out('/CreationDate ' . $this->_textstring('D:' . date('YmdHis')));
            }

            /**
             * Extends parent _putstream function with encryption
             *
             * @param string $s String to be processed
             *
             * @return void

             */
            function _putstream($s)
            {
                if ($this->_encrypted) {
                    $s = $this->_RC4($this->_objectkey($this->n), $s);
                }

                parent::_putstream($s);
            }

            /**
             * Extends parent _textstream function with encryption
             *
             * @param string $s String to be processed
             *
             * @return string Processed string

             */
            function _textstring($s)
            {
                if ($this->_encrypted) {
                    $s = $this->_RC4($this->_objectkey($this->n), $s);
                }

                return parent::_textstring($s);
            }

            /**
             * Compute key depending on object number where the encrypted data is
             * stored
             *
             * @param int $n Number of object
             *
             * @return Computed key

             */
            private function _objectkey($n)
            {
                return substr($this->_md5_16($this->encryption_key.pack('VXxx', $n)), 0, 10);
            }

            /**
             * Escape special characters (extends parent function)
             *
             * @param string $s String to be processed
             *
             * @return Processed string

             */
            function _escape($s)
            {
                $s = str_replace('\\', '\\\\', $s);
                $s = str_replace(')', '\\)', $s);
                $s = str_replace('(', '\\(', $s);
                $s = str_replace("\r", '\\r', $s);

                return $s;
            }

            /**
             * Helper function for parent _put-functions to be extended with
             * encryption
             *
             * @return void

             */
            private function _putencryption()
            {
                $this->_out('/Filter /Standard');
                $this->_out('/V 1');
                $this->_out('/R 2');
                $this->_out('/O (' . $this->_escape($this->_oValue) . ')');
                $this->_out('/U (' . $this->_escape($this->_uValue) . ')');
                $this->_out('/P ' . $this->_pValue);
            }

            /**
             * Extends parent _putresources function with encryption
             *
             * @return void

             */
            function _putresources()
            {
                parent::_putresources();

                if ($this->_encrypted) {
                    $this->_newobj();
                    $this->_encObjectId = $this->n;
                    $this->_out('<<');
                    $this->_putencryption();
                    $this->_out('>>');
                    $this->_out('endobj');
                }
            }

            /**
             * Extends parent _puttrailer function with encryption
             *
             * @return void

             */
            function _puttrailer()
            {
                parent::_puttrailer();

                if ($this->_encrypted) {
                    $this->_out('/Encrypt ' . $this->_encObjectId . ' 0 R');
                    $this->_out('/ID [()()]');
                }
            }

            /**
             * RC4 encryption algorithm to be used in PDF format
             *
             * @param string $key  Key to be used for encryption
             * @param string $text String to be encrypted
             *
             * @return Encrypted string

             */
            private function _RC4($key, $text)
            {
                if ($this->_lastRC4Key != $key) {
                    $k = str_repeat($key, 256/strlen($key) + 1);
                    $rc4 = range(0, 255);
                    $j = 0;

                    for ($i = 0; $i < 256; $i++) {
                        $t = $rc4[$i];
                        $j = ($j + $t + ord($k{$i})) % 256;
                        $rc4[$i] = $rc4[$j];
                        $rc4[$j] = $t;
                    }

                    $this->_lastRC4Key = $key;
                    $this->_lastRC4KeyC = $rc4;
                } else {
                    $rc4 = $this->_lastRC4KeyC;
                }

                $len = strlen($text);
                $a = 0;
                $b = 0;
                $out = '';

                for ($i = 0; $i < $len; $i++) {
                    $a = ($a + 1) % 256;
                    $t= $rc4[$a];
                    $b = ($b + $t) % 256;
                    $rc4[$a] = $rc4[$b];
                    $rc4[$b] = $t;
                    $k = $rc4[($rc4[$a] + $rc4[$b]) % 256];
                    $out .= chr(ord($text{$i}) ^ $k);
                }

                return $out;
            }

            /**
             * Get MD5 as binary string
             *
             * @param string $string String to be converted
             *
             * @return Converted string

             */
            private function _md5_16($string)
            {
                return pack('H*', md5($string));
            }

            /**
             * Compute O value
             *
             * @param string $userPass  User password to be used
             * @param string $ownerPass Owner password to be used
             *
             * @return Computed O value

             */
            private function _GenOvalue($userPass, $ownerPass)
            {
                $tmp = $this->_md5_16($ownerPass);
                $owner_RC4_key = substr($tmp, 0, 5);

                return $this->_RC4($owner_RC4_key, $userPass);
            }

            /**
             * Compute U value
             *
             * @return Computed U value

             */
            private function _GenUvalue()
            {
                return $this->_RC4($this->encryption_key, $this->padding);
            }

            /**
             * Compute encryption key
             *
             * @param string $userPass   User password to be used
             * @param string $ownerPass  Owner password to be used
             * @param int    $protection Protection to be used
             *
             * @return Generated encryption key

             */
            private function _generateencryptionkey($userPass, $ownerPass, $protection)
            {
                // Pad passwords
                $userPass = substr($userPass . $this->padding, 0, 32);
                $ownerPass = substr($ownerPass . $this->padding, 0, 32);

                // Compute O value
                $this->_oValue = $this->_GenOvalue($userPass, $ownerPass);

                // Compute encyption key
                $tmp = $this->_md5_16($userPass . $this->_oValue . chr($protection) . "\xFF\xFF\xFF");
                $this->encryption_key = substr($tmp, 0, 5);

                // Compute U value
                $this->_uValue = $this->_GenUvalue();

                // Compute P value
                $this->_pValue = -(($protection ^ 255) + 1);
            }

            /**
             * Check if we need to add a page break
             *
             * @param int $h Height of page
             *
             * @return void

             */
            private function _checkPageBreak($h)
            {
                // If the height h would cause an overflow, add a new page immediately
                if ($this->GetY() + $h > $this->PageBreakTrigger) {
                    $this->AddPage($this->CurOrientation);
                }
            }

            /**
             * Computes the number of lines a MultiCell of width w will take
             *
             * @param int    $w   Width of cell
             * @param string $txt Text to be calculated
             *
             * @return Number of lines

             */
            private function _nbLines($w, $txt)
            {
                $cw = &$this->CurrentFont['cw'];

                if ($w == 0) {
                    $w = $this->w - $this->rMargin - $this->x;
                }

                $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                $s = str_replace("\r", '', $txt);
                $nb = strlen($s);

                if ($nb > 0 && $s[$nb - 1] == "\n") {
                    $nb--;
                }

                $sep = -1;
                $i = 0;
                $j = 0;
                $l = 0;
                $nl = 1;

                while ($i < $nb) {
                    $c = $s[$i];

                    if ($c == "\n") {
                        $i++;
                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;

                        continue;
                    }

                    if ($c == ' ') {
                        $sep = $i;
                    }

                    $l += $cw[$c];

                    if ($l > $wmax) {
                        if ($sep == -1) {
                            if ($i == $j) {
                                $i++;
                            }
                        } else {
                            $i = $sep + 1;
                        }

                        $sep = -1;
                        $j = $i;
                        $l = 0;
                        $nl++;
                    } else {
                        $i++;
                    }
                }

                return $nl;
            }
        }
    } else {
        $ui = MainUI::getObject();

        $errmsg = _("To protect the server the PDF file has not been created, because the server is too busy right now!");
        $ui->displayError($errmsg);

        exit();
    }
}

/**
 * Defines the HTML type of node list
 *
 * @package    WiFiDogAuthServer
 * @subpackage NodeLists
 * @author     Max Horváth <max.horvath@freenet.de>
 * @copyright  2006 Max Horváth, Horvath Web Consulting
 */
class NodeListPDF extends NodeList
{
    /**
     * Is FPDF available?
     *
     * @var bool

     */
    private $_pdfAvailable = false;

    /**
     * PdfWifidog object
     *
     * @var object

     */
    private $_pdf;

    /**
     * Format of page
     *
     * @var string

     */
    private $_pdfFormat = "letter";

    /**
     * Sort list by?
     *
     * @var string

     */
    private $_pdfSort = "name";

    /**
     * Format of date
     *
     * @var string

     */
    private $_pdfDate = "m/d/Y";

    /**
     * Path of logo
     *
     * @var string

     */
    private $_pdfImage = "media/base_theme/images/wifidog_logo.jpg";

    /**
     * Network to generate the list from
     *
     * @var object

     */
    private $_network;

    /**
     * Nodes to generate the list from
     *
     * @var array

     */
    private $_nodes;

    /**
     * Object of current user
     *
     * @var object

     */
    private $_currentUser;

    /**
     * Does the node list have all required dependencies, etc.?  In not redefined by the child class,
     * returns true
     *
     * @return true or false
     */
    static public function isAvailable()
    {
        return Dependency::check("FPDF");
    }

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(&$network)
    {

        $db = AbstractDb::getObject();

        // Init network
        $this->_network = $network;

        // Init PdfWifidog and check if FDPF is available
        if (Dependency::check("FPDF")) {
            // Set PDF availability switch
            $this->_pdfAvailable = true;

            // Try to load customization file
            if (file_exists(WIFIDOG_ABS_FILE_PATH . "templates/NodeLists/NodeListPDF.php")) {
                require_once("templates/NodeLists/NodeListPDF.php");
            }

            /*
             * Process customizations
             */

            // Page format
            if (defined("PDF_FORMAT") && PDF_FORMAT == "a4") {
                $this->_pdfFormat = PDF_FORMAT;
            }

            // Sort by?
            if (defined("PDF_SORT")) {
                switch (PDF_SORT) {
                    case "street_name":
                    case "postal_code":
                    case "city":
                        $this->_pdfSort = PDF_SORT;
                        break;

                    default:
                        $this->_pdfSort = "name";
                        break;

                }
            }

            // Format of date
            if (defined("PDF_DATE")) {
                $this->_pdfDate = PDF_DATE;
            }

            // Path of logo
            if (defined("PDF_IMAGE")) {
                $this->_pdfImage = PDF_IMAGE;
            }

            /*
             * Customizations processed
             */

            // Init PDF class
            $this->_pdf = new PdfWiFiDog("L", "mm", $this->_pdfFormat);

            // Define document values
            $this->_pdf->SetTitle($this->_network->getName() . " " . _("Hotspots"));
            $this->_pdf->SetSubject($this->_network->getName() . " " . _("Hotspots"));
            $this->_pdf->SetAuthor($this->_network->getName());
            $this->_pdf->SetKeywords($this->_network->getName() . ", " . _("Hotspots"));

            if (defined("WIFIDOG_NAME")) {
                $this->_pdf->SetCreator(WIFIDOG_NAME);
            }

            // Define styles
            $this->_pdf->SetAligns(array("L", "L", "C", "L", "L", "L", "L", "L"));

            // Set width according to page format
            if ($this->_pdfFormat == "letter") {
                $this->_pdf->SetWidths(array(35, 35, 15, 35, 35, 35, 35, 35));
            } else {
                $this->_pdf->SetWidths(array(36, 36, 15, 36, 36, 36, 36, 45));
            }

            // Apply protection
            $this->_pdf->SetProtection(array('print'));
        }

        // Init user
        $this->_currentUser = User::getCurrentUser();

        // Query the database, sorting by node name
        $db->execSql("SELECT *, (CURRENT_TIMESTAMP-last_heartbeat_timestamp) AS since_last_heartbeat, EXTRACT(epoch FROM creation_date) as creation_date_epoch, CASE WHEN ((CURRENT_TIMESTAMP-last_heartbeat_timestamp) < interval '5 minutes') THEN true ELSE false END AS is_up FROM nodes WHERE network_id = '" . $db->escapeString($this->_network->getId()) . "' AND (node_deployment_status = 'DEPLOYED' OR node_deployment_status = 'NON_WIFIDOG_NODE') ORDER BY lower(" . $this->_pdfSort . ")", $this->_nodes, false);
    }

    /**
     * Sets header of output
     *
     * @return void
     */
    public function setHeader()
    {
        if (Dependency::check("FPDF")) {
            header("Cache-control: private, no-cache, must-revalidate, post-check=0, pre-check=0");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); # Past date
            header("Pragma: no-cache");
            header("Content-Transfer-Encoding: binary");
            header("Content-Type: application/pdf");
            header("Content-Disposition: inline; filename=" . $this->_network->getId() . "_hotspot_status.pdf");
        }
    }

    /**
     * Displays the output of this object.
     *
     * @return string The PDF file
     */
    public function getOutput()
    {
        // Init values
        $_nodeDetails = array();

        // Check for FPDF support
        if ($this->_pdfAvailable) {
            // Init PDF
            $this->_pdf->AddPage();

            // Analyze logo
            $_imageSize = GetImageSize(WIFIDOG_ABS_FILE_PATH . $this->_pdfImage);

            // Calculate position of logo
            if ($this->_pdfFormat == "letter") {
                $_imageLeft = 270;
            } else {
                $_imageLeft = 286;
            }

            $_imageWidth = 27 * ($_imageSize[0] / (72 / 25.4)) / ($_imageSize[1] / (72 / 25.4));
            $_imageLeft = $_imageLeft - $_imageWidth;

            // Place logo
            $this->_pdf->Image(WIFIDOG_ABS_FILE_PATH . $this->_pdfImage, $_imageLeft, 12, 0, 27);

            // Define font size for Header
            $this->_pdf->SetFont('Arial', '', 36);

            $this->_pdf->Write(15, utf8_decode($this->_network->getName() . " " . _("Hotspots")));
            $this->_pdf->Ln();

            // Define font size for the description and the node list
            $this->_pdf->SetFont('Arial', '', 8);

            // Check sorting
            switch ($this->_pdfSort) {
                case "street_name":
                    $_sortBy = _("street name");
                    break;

                case "postal_code":
                    $_sortBy = _("postal code");
                    break;

                case "city":
                    $_sortBy = _("city");
                    break;

                default:
                    $_sortBy = _("name");
                    break;

            }

            $this->_pdf->Write(10, utf8_decode(sprintf(_("This list contains all Hotspots of %s sorted by %s."), $this->_network->getName(), $_sortBy)));
            $this->_pdf->Ln(4);

            $this->_pdf->Write(10, utf8_decode(sprintf(_("Number of Hotspots: %d"), count($this->_nodes))));
            $this->_pdf->Ln(4);

            $this->_pdf->Write(10, utf8_decode(sprintf(_("Last updated on: %s"), date($this->_pdfDate))));
            $this->_pdf->Ln(12);

            // Node details
            if ($this->_nodes) {
                foreach ($this->_nodes as $_nodeData) {
                    $_node = Node::getObject($_nodeData['node_id']);
                    $_nodeData['num_online_users'] = $_node->getNumOnlineUsers();
                    $_nodeDetails[] = $_nodeData;
                }
            }

            $_header = array(_("Hotspot"), _("Address"), _("Postal code"), _("City"), _("Province / State"), _("Telephone"), _("Email"), _("Homepage URL"));

            $this->_pdf->nodeList($_header, $_nodeDetails);

            // Compile PDF file
            $this->_pdf->Output();
        }
        else {
            $ui = MainUI::getObject();

            $errmsg = _("PDF file cannot be created because the FPDF library is not installed!");
            $ui->displayError($errmsg);

            exit();
        }
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
