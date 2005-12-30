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
 * This a wrapper class conforming RFC822 capable of sending valid UTF-8 MIME
 * headers
 *
 * @package    WiFiDogAuthServer
 * @author     Francois Proulx <francois.proulx@gmail.com>
 * @copyright  2005 Francois Proulx <francois.proulx@gmail.com> - Technologies
 * Coeus inc.
 * @version    CVS: $Id$
 * @link       http://sourceforge.net/projects/wifidog/
 */

class Mail
{
	// List of fake e-mails hosts
	private static $_hosts_black_list = array ("simplicato.net", "mytrashmail.com", "spamhole.com", "mailexpire.com", "sneakemail.com", "spamex.com", "emailias.com", "mymailoasis.com", "spamcon.org", "spamgourmet.com", "spammotel.com", "dodgeit.com");

	// Business domain attributes
	private $from_name;
	private $from_email;
	private $to_name;
	private $to_email;
	private $subject;
	private $body;

	public function __construct()
	{
	}

	private function encodeMimeHeader($header)
	{
		// BASE 64 according to the RFC
		// Taken from : www.php.net mb_send_mail comments
		$header = preg_replace('/([^a-z ])/ie', 'sprintf("=%02x",ord(StripSlashes("\\1")))', $header);
		$header = str_replace(' ', '_', $header);
		return "=?utf-8?Q?$header?=";
	}

	public function getMessageBody()
	{
		return $this->body;
	}

	public function getMessageSubject()
	{
		return $this->subject;
	}

	public function getRecipientName()
	{
		return $this->to_name;
	}

	public function getRecipientEmail()
	{
		return $this->to_email;
	}

	public function getSenderName()
	{
		return $this->from_name;
	}

	public function getSenderEmail()
	{
		return $this->from_email;
	}

	// Packs e-mail and send it according to RFC822
	public function send()
	{
		$headers = "From: \"".$this->getSenderName()."\" <".$this->getSenderEmail().">\r\n";
		$headers .= "Reply-To: ".$this->getSenderEmail()."\r\n";
		$headers .= "Content-Type: text/plain; charset=utf-8";
		$args = "-f".$this->getSenderEmail();
		return mail($this->getRecipientEmail(), $this->getMessageSubject(), $this->getMessageBody(), $headers, $args);
	}

	public function setMessageBody($body)
	{
		$this->body = $body;
	}

	public function setMessageSubject($subject)
	{
		$this->subject = $this->encodeMimeHeader($subject);
	}

	public function setRecipientEmail($mail)
	{
		$this->to_email = $mail;
	}

	public function setSenderName($name)
	{
		// Encode header
		$this->from_name = $this->encodeMimeHeader($name);
	}

	public function setSenderEmail($mail)
	{
		$this->from_email = $mail;
	}

	/**
	 * Validates an e-mail address 
	 *
	 * This function will make sure an e-mail is RFC822 compliant
	 * and is not black listed.
	 * 
	 * Here's an example of how to use the function:
	 * <code>
	 * Mail::validateEmailAddress($mail);
	 * </code>
	 *
	 * @param string $mail The e-mail address to validate
	 *
	 * @return boolean Returns whether the e-mail is valid or not
	 *
	 * @access public
	 * @static
	 */
	public static function validateEmailAddress($email)
	{
		$matches = null;
		// Test if the email matches the regexp
		$regex = "/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i";
		if (preg_match_all($regex, $email, $matches))
		{
			// If the hostname is black listed, reject the e-mail.
			$full_hostname = $matches[2][0].".".$matches[3][0];
			if(in_array($full_hostname, self::$_hosts_black_list))
				return false;
			else
				return true;
		}
		else
			return false;
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