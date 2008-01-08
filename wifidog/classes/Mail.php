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
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 * @version    Subversion $Id$
 * @link       http://www.wifidog.org/
 */

/**
 * Load required classes
 */
require_once ('lib/PHPMailer/class.phpmailer.php');
require_once ('lib/PHPMailer/class.smtp.php');
require_once ('lib/PHPMailer/phpmailer.lang-en.php');

/**
 * This a wrapper class conforming RFC822 capable of sending valid UTF-8 MIME
 * headers
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005-2006 Francois Proulx, Technologies Coeus inc.
 */
class Mail {
    /**
     * List of fake e-mails hosts.  IMPORTANT NOTE:  This is not meant to block
     * hotmail, gmail, etc.  On the contrary, we encourage users to use
     * annonymous email.  We just want them to stay reachable in case of problem
     * or abuse.
     * 
     * All domains must be lowercase
     * @note We may have to do something a little smarter to deal with the likes
     *  of 2prong.com and blah+blah@gmail.com
     */
    private static $_hosts_black_list = array (
        "10minutemail.com",
        "afrobacon.com",
        "antispam24.de",
        "bloglines.com",
        "discardmail.com",
        "disposeamail.com",
        "dodgeit.com",
        "dontreg.com",
        "dumpmail.de",
        "e4ward.com",
        "emailias.com",
        "guerrillamail.info",
        "gishpuppy.com",
        "golfilla.info",
        "greensloth.com",
        "hatespam.org",
        "haltospam.com",
        "h8s.org",
        "ipoo.org",
        "jetable.org",
        "kasmail.com",
        "mail-filter.com",
        "maileater.com",
        "mailexpire.com",
        "mailinator.com",
        "mailnull.com",
        "mailshell.com",
        "mymailoasis.com",
        "mytrashmail.com",
        "mytrashmail.net",
        "nervmich.net",
        "oopi.org",
        "poofy.org",
        "pookmail.com",
        "put2.net",
        "senseless-entertainment.com",
        "shortmail.net",
        "simplicato.net",
        "slaskpost.se",
        "sneakemail.com",
        "sofort-mail.de",
        "spam.la",
        "spamcon.org",
        "spamday.com",
        "spamex.com",
        "spamgourmet.com",
        "spamhole.com",
        "spammotel.com",
        "tempemail.net",
        "tempinbox.com",
        "temporaryinbox.com",
        "throwaway.de",
        "trash-mail.de",
        "woodyland.org",
        "wuzup.net"
    );
    /**
     * Name email will been sent from
     *
     * @var string
     *
     * @access private
     */
    private $_fromName;

    /**
     * Address email will be sent from
     *
     * @var string
     *
     * @access private
     */
    private $_fromEmail;

    /**
     * Name email will be sent to
     *
     * @var string
     *
     * @access private
     */
    private $_toName;

    /**
     * Address email will be sent to
     *
     * @var string
     *
     * @access private
     */
    private $_toEmail;

    /**
     * Subject of email
     *
     * @var string
     *
     * @access private
     */
    private $_subject;

    /**
     * Content of email
     *
     * @var string
     *
     * @access private
     */
    private $_body;

    /**
     * Priority of email
     *
     * @var boolean
     *
     * @access private
     */
    private $_highPriority;

    /**
     * Encodes the MIME header
     *
     * @param string $header Header of email
     *
     * @return string Encoded MIME header
    
     *
     * @see http://www.php.net/manual/en/function.mb-send-mail.php
     */
    private function _encodeMimeHeader($header) {
        // BASE 64 according to the RFC
        $header = preg_replace('/([^a-z ])/ie', 'sprintf("=%02x",ord(StripSlashes("\\1")))', $header);
        $header = str_replace(' ', '_', $header);
        return "=?utf-8?Q?$header?=";
    }

    /**
     * Returns name of sender of email
     *
     * @return string Name of sender of email
     *
     * @access public
     */
    public function getSenderName() {
        return $this->_fromName;
    }

    /**
     * Sets name of sender of email
     *
     * @param string $name Name of sender of email
     *
     * @return void
     *
     * @access public
     */
    public function setSenderName($name) {
        // Encode name
        $this->_fromName = $this->_encodeMimeHeader($name);
    }

    /**
     * Returns address of sender of email
     *
     * @return string Address of sender of email
     *
     * @access public
     */
    public function getSenderEmail() {
        return $this->_fromEmail;
    }

    /**
     * Sets address of sender of email
     *
     * @param string $mail Address of sender of email
     *
     * @return void
     *
     * @access public
     */
    public function setSenderEmail($mail) {
        $this->_fromEmail = $mail;
    }

    /**
     * Returns name of recipient of email
     *
     * @return string Name of recipient of email
     *
     * @access public
     */
    public function getRecipientName() {
        return $this->_toName;
    }

    /**
     * Sets name of recipient of email
     *
     * @param string $name Name of recipient of email
     *
     * @return void
     *
     * @access public
     */
    public function setRecipientName($name) {
        // Encode name
        $this->_toName = $this->_encodeMimeHeader($name);
    }

    /**
     * Returns address of recipient of email
     *
     * @return string Address of recipient of email
     *
     * @access public
     */
    public function getRecipientEmail() {
        return $this->_toEmail;
    }

    /**
     * Sets address of recipient of email
     *
     * @param string $mail Address of recipient of email
     *
     * @return void
     *
     * @access public
     */
    public function setRecipientEmail($mail) {
        $this->_toEmail = $mail;
    }

    /**
     * Returns subject of email
     *
     * @return string Subject of email
     *
     * @access public
     */
    public function getMessageSubject() {
        return $this->_subject;
    }

    /**
     * Sets subject of email
     *
     * @param string $subject Subject of email
     *
     * @return void
     *
     * @access public
     */
    public function setMessageSubject($subject) {
        $this->_subject = $this->_encodeMimeHeader($subject);
    }

    /**
     * Returns message body of email
     *
     * @return string Message body of email
     *
     * @access public
     */
    public function getMessageBody() {
        return $this->_body;
    }

    /**
     * Sets message body of email
     *
     * @param string $body Message body of email
     *
     * @return void
     *
     * @access public
     */
    public function setMessageBody($body) {
        $this->_body = $body;
    }

    /**
     * Returns the priority of the email
     *
     * @return boolean Whether high priority or not
     *
     * @access public
     */
    public function getHighPriority() {
        return $this->_highPriority;
    }

    /**
     * Sets the priority of the email
     *
     * @param boolean $boolean Whether high priority or not
     *
     * @return void
     *
     * @access public
     */
    public function setHighPriority($boolean) {
        $this->_highPriority = $boolean ? true : false;
    }

    /**
     * Packs email and sends it according to RFC822
     *
     * @return bool True if email could be sent
     *
     * @access public
     */
    public function send() {
        $mail = new PHPMailer();
        $mail->CharSet = "utf-8";
        # $mail->SMTPDebug=TRUE;

        $mail->Mailer = EMAIL_MAILER;
        if (EMAIL_MAILER == 'smtp') {
            $mail->IsSMTP();
            $mail->Host = EMAIL_HOST;
            $mail->SMTPAuth = EMAIL_AUTH;

            if (EMAIL_AUTH) {
                $mail->Username = EMAIL_USERNAME;
                $mail->Password = EMAIL_PASSWORD;
            }
        }

        $mail->AddAddress($this->getRecipientEmail(), $this->getRecipientName());
        $mail->From = $this->getSenderEmail();
        $mail->FromName = $this->getSenderName();
        $mail->Sender = $this->getSenderEmail(); // add Sender Name
        if ($this->getHighPriority()) {
            $mail->Priority = 1;
        }
        $mail->Subject = $this->getMessageSubject();
        $mail->Body = $this->getMessageBody();

        $result = $mail->Send();
        if (!$result) {
            print $mail->ErrorInfo;
        }
        return $result;
    }

    /**
     * Validates an email address
     *
     * This function will make sure an e-mail is RFC822 compliant
     * and is not black listed.
     *
     * @param string $mail The email address to validate
     *
     * @return bool Returns whether the email address is valid or not
     *
     * @static
     * @access public
     */
    public static function validateEmailAddress($email) {
        // Init values
        $_matches = null;
        $_retVal = false;

        // Test if the email address is valid
        $regex = "/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i";

        if (preg_match_all($regex, $email, $_matches)) {
            // If the hostname is black listed, reject the email address
            $full_hostname = $_matches[2][0] . "." . $_matches[3][0];

            if (!in_array(strtolower($full_hostname), self :: $_hosts_black_list)) {
                $_retVal = true;
            }
        }

        return $_retVal;
    }

}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */