<?php

function randompass()
{
   $rand_pass = ''; // makes sure the $pass var is empty.
   for( $j = 0; $j < 3; $j++ ) {
       $startnend = array(
           'b','c','d','f','g','h','j','k','l','m','n',
           'p','q','r','s','t','v','w','x','y','z',
       );
       $mid = array(
           'a','e','i','o','u','y',
       );
       $count1 = count( $startnend ) - 1;
       $count2 = count( $mid ) - 1;

       for( $i = 0; $i < 3; $i++) {
           if( $i != 1 ) {
               $rand_pass .= $startnend[rand( 0, $count1 )];
           } else {
               $rand_pass .= $mid[rand( 0, $count2 )];
           }
       }
   }
   return $rand_pass;
}

function send_validation_email($email) {
    global $db;
    global $smarty;

    $user_info = null;
    $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
    if ($user_info == null) {
        $smarty->assign("error", _("Unable to locate ") . $_REQUEST["email"] . _(" in the database."));
    } else {
        if ($user_info['account_status'] != ACCOUNT_STATUS_VALIDATION) {
            /* Note:  Do not display the username here, for privacy reasons */
            $smarty->assign("error", _("The user is not in validation period."));
	    } else {
            if (empty($user_info['validation_token'])) {
                $smarty->assign("error", _("The validation token is empty."));
            } else {
                $subject = VALIDATION_EMAIL_SUBJECT;
                $url = "http://" . $_SERVER["HTTP_HOST"] . "/validate.php?username=" . $_REQUEST["username"] . "&token=" . $user_info["validation_token"];
                $body = "Hello!

Please follow the link below to validate your account.

$url

Thank you,

The Team";
                $from = "From: " . VALIDATION_EMAIL_FROM_ADDRESS;

                mail($email, $subject, $body, $from);
                $smarty->append("message", _("An email with confirmation instructions was sent to your email address.  Your account has been granted 15 minutes of access to retrieve your email and validate your account.  You may now open a browser window and go to any remote Internet address to obtain the login page."));
                $smarty->display("templates/validate.html");
                exit;
            }
        }
    }
}

function send_lost_username_email($email) {
    global $db;
    global $smarty;

    $db->ExecSqlUniqueRes("SELECT user_id FROM users WHERE email='$email'", $user_info, false);
    if($user_info == null) {
        $smarty->assign("error", _("Unable to locate ") . $email . _(" in the database."));
    } else {
        $subject = LOST_USERNAME_EMAIL_SUBJECT;
        $body = "Hello,

You have requested that the authentication server send you your username:

Username: $user_info[user_id]

Have a nice day,

The Team";
        $from = "From: ".VALIDATION_EMAIL_FROM_ADDRESS;

        mail($email, $subject, $body, $from);
        $smarty->append("message", _("Your username has been emailed to you."));
        $smarty->display("templates/validate.html");
        exit;
    }
}

function send_lost_password_email($email) {
    global $db;
    global $smarty;

    $new_password = randompass();
    $password_hash = get_password_hash($new_password);
    $update_successful = $db->ExecSqlUpdate("UPDATE users SET pass='$password_hash' WHERE email='{$email}'");
    if ($update_successful) {

        $db->ExecSqlUniqueRes("SELECT * FROM users WHERE email='$email'", $user_info, false);
        if ($user_info == null) {
            $smarty->assign("error", _("Unable to locate ") . $email . _("in the database."));
        } else {
            $subject = LOST_PASSWORD_EMAIL_SUBJECT;
            $body = "Hello,

You have requested that the authentication server send you a new password:

Username: $user_info[user_id]
Password: $new_password

Have a nice day,

The Team";
            $from = "From: ".VALIDATION_EMAIL_FROM_ADDRESS;

            mail($email, $subject, $body, $from);
            $smarty->append("message", _("A new password has been emailed to you."));
            $smarty->display("templates/validate.html");
            exit;
        }
    } else {
        $smarty->assign("error", _("Internal error."));
    }
}
?>
