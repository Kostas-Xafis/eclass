<?php

/* ========================================================================
 * Open eClass 3.4
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2016  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

/*
 * Password reset component
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 *
 * @abstract This component resets the user's password after verifying
 * his/hers  information through a challenge/response system.
 *
 */
use Hautelook\Phpass\PasswordHash;

include '../../include/baseTheme.php';
include 'auth.inc.php';
include 'include/sendMail.inc.php';
$pageName = $lang_remind_pass;

// javascript
load_js('pwstrength.js');
$head_content .= <<<hContent
<script type="text/javascript">
/* <![CDATA[ */

    var lang = {
hContent;
$head_content .= "pwStrengthTooShort: '" . js_escape($langPwStrengthTooShort) . "', ";
$head_content .= "pwStrengthWeak: '" . js_escape($langPwStrengthWeak) . "', ";
$head_content .= "pwStrengthGood: '" . js_escape($langPwStrengthGood) . "', ";
$head_content .= "pwStrengthStrong: '" . js_escape($langPwStrengthStrong) . "'";
$head_content .= <<<hContent
    };

    $(document).ready(function() {
        $('#password').keyup(function() {
            $('#result').html(checkStrength($('#password').val()))
        });
    });

/* ]]> */
</script>
hContent;

// Password reset link is valid for 1 hour = 3600 sec
define('TOKEN_VALID_TIME', 3600);

$emailhelpdesk = q(get_config('email_helpdesk'));
$homelink = "<br><p><a href='$urlAppend'>$langHome</a></p>\n";

function password_is_editable($password) {
    global $auth_ids;

    if (in_array($password, $auth_ids)) {
        return false; // not editable, external auth method
    } else {
        return true;  // editable
    }
}

if (isset($_REQUEST['u']) and isset($_REQUEST['h'])) {
    $change_ok = false;
    $userUID = intval($_REQUEST['u']);
    $valid = token_validate('password' . $userUID, $_REQUEST['h'], TOKEN_VALID_TIME);
    $res = Database::get()->querySingle("SELECT id FROM user WHERE id = ?d AND password NOT IN ('" . implode("', '", $auth_ids) . "')", $userUID);
    $error_messages = array();
    if ($valid and $res) {
        $tool_content .= action_bar(array(
                                array('title' => $langBack,
                                      'url' => $urlServer,
                                      'icon' => 'fa-reply',
                                      'level' => 'primary-label',
                                      'button-class' => 'btn-default')
                            ),false);

        if (isset($_POST['newpass']) and isset($_POST['newpass1']) and
                count($error_messages = acceptable_password($_POST['newpass'], $_POST['newpass1'])) == 0) {
            $hasher = new PasswordHash(8, false);
            $q1 = Database::get()->query("UPDATE user SET password = ?s
                                                      WHERE id = ?d",
                    $hasher->HashPassword($_POST['newpass']), $userUID);
            if ($q1->affectedRows > 0) {

                $tool_content = "<div class='alert alert-success'><p>$langAccountResetSuccess1</p></div>
                                                       $homelink";
                $change_ok = true;
            }
        } elseif (count($error_messages)) {
            $tool_content .= "<div class='alert alert-warning'>" .
                                implode("\n", $error_messages) .
                            "</div>";
        }
        if (!$change_ok) {
            $tool_content .= "
                <div class='form-wrapper'>
                    <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]'>
                        <input type='hidden' name='u' value='$userUID'>
                        <input type='hidden' name='h' value='" . q($_REQUEST['h']) . "'>
                        <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langNewPass1</label>
                            <div class='col-sm-10'>
                                <input type='password' size='40' name='newpass' value='' id='password' autocomplete='off'>&nbsp;<span id='result'></span>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label class='col-sm-2 control-label'>$langNewPass2</label>
                            <div class='col-sm-10'>
                                <input type='password' size='40' name='newpass1' value='' autocomplete='off'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <div class='col-sm-offset-2 col-sm-10'>
                                <input class='btn btn-primary' type='submit' name='submit' value='$langModify'>
                            </div>
                        </div>
                    </form>
                </div>";
        }
    } else {
        $tool_content .= action_bar(array(
                                array('title' => $langBack,
                                      'url' => $urlServer,
                                      'icon' => 'fa-reply',
                                      'level' => 'primary-label',
                                      'button-class' => 'btn-default')
                            ),false);
        $tool_content = "<div class='alert alert-danger'>$langAccountResetInvalidLink</div>
                                 $homelink";
    }
} elseif (isset($_POST['send_link'])) {

    $email = isset($_POST['email']) ? mb_strtolower(trim($_POST['email'])) : '';
    $userName = isset($_POST['userName']) ? canonicalize_whitespace($_POST['userName']) : '';
    /*     * *** If valid e-mail address was entered, find user and send email **** */
    $res = Database::get()->querySingle("SELECT u.id, u.surname, u.givenname, u.username, u.password, u.status FROM user u
                    LEFT JOIN admin a ON (a.user_id = u.id)
                    WHERE u.email = ?s AND
                    BINARY u.username = ?s AND
                    a.user_id IS NULL AND
                    (u.last_passreminder IS NULL OR DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 HOUR) >= u.last_passreminder)", $email, $userName); //exclude admins and currently pending requests

    $found_editable_password = false;
    if ($res) {
        if (password_is_editable($res->password)) {
            $found_editable_password = true;
            //prepare instruction for password reset
            $text = $langPassResetIntro . $emailhelpdesk;
            $text .= $langHowToResetTitle;
            $text .= $langPassResetGoHere;
            $text .= $urlServer . "modules/auth/lostpass.php?u=$res->id&h=" .
                    token_generate('password' . $res->id, true);

            $header_html_topic_notify = "<!-- Header Section -->
            <div id='mail-header'>
                <br>
                <div>
                    <div id='header-title'>$langPassResetIntro</div>
                    <div>$langPassResetIntro2 $emailhelpdesk</div>
                </div>
            </div>";

            $body_html_topic_notify = "<!-- Body Section -->
            <div id='mail-body'>
                <br>
                <div><b>$langHowToResetTitle</b></div><br>
                <div id='mail-body-inner'>
                    $langPassResetGoHere<br><br><a href='$urlServer"."modules/auth/lostpass.php?u=$res->id&h=" .
                token_generate('password' . $res->id, true)."'>$urlServer"."modules/auth/lostpass.php?u=$res->id&h=" .
                    token_generate('password' . $res->id, true)."</a>
                </div>
            </div>";

            $text = $header_html_topic_notify.$body_html_topic_notify;

            $plainText = html2text($text);
            // store the timestamp of this action (password reminding and token generation)
            Database::get()->query("UPDATE user SET last_passreminder = CURRENT_TIMESTAMP WHERE id = ?d" , $res->id);
        } else { //other type of auth...
            $auth = array_search($res->password, $auth_ids) or 1;
            $tool_content = "<div class='alert alert-danger'>
                                <p><strong>$langPassCannotChange1</strong></p>
                                <p>$langPassCannotChange2 " . get_auth_info($auth) .
                    ". $langPassCannotChange3 <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a> $langPassCannotChange4</p>
                                $homelink</div>";
        }

        /*         * *** Account details found, now send e-mail **** */
        if ($found_editable_password) {
            $emailsubject = $lang_remind_pass;
            if (!send_mail_multipart('', '', '', $email, $emailsubject, $plainText, $text)) {
                $tool_content = "<div class='alert alert-danger'>
                                <p><strong>$langAccountEmailError1</strong></p>
                                <p>$langAccountEmailError2 $email.</p>
                                <p>$langAccountEmailError3 <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a>.</p></div>
                                $homelink";
            } elseif (!isset($auth)) {
                $alert = "$lang_pass_email_ok <strong>" . q($email) . "</strong>";
                if ($_POST['send_link'] == 'toolbox') {
                    Session::Messages($alert, 'alert-success');
                    redirect_to_home_page('main/toolbox.php');
                } else {
                    $tool_content .= "<div class='alert alert-success'>$lang_pass_email_ok <strong>" .
                        q($email) . "</strong></div>$homelink";
                }
            }
        }
    } else {
        $res = Database::get()->querySingle("SELECT u.id, u.surname, u.givenname, u.username, u.password, u.status FROM user u
                    LEFT JOIN admin a ON (a.user_id = u.id)
                    WHERE u.email = ?s AND
                    BINARY u.username = ?s AND
                    a.user_id IS NULL AND
                    (u.last_passreminder IS NOT NULL OR DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 HOUR) < u.last_passreminder)", $email, $userName);
        if ($res) {
            if ($_POST['send_link'] == 'toolbox') {
                Session::Messages($langLostPassPending, 'alert-danger');
                redirect_to_home_page('main/toolbox.php');
            } else {
                $tool_content .= "<div class='alert alert-danger'>
                        <p>$langLostPassPending</p></div>
                        $homelink";
            }
        } else {
            $alert = "<p><strong>$langAccountNotFound1 (" . q("$userName / $email") . ")</strong></p>
                      <p>$langAccountNotFound2 <a href='mailto:$emailhelpdesk'>$emailhelpdesk</a>, $langAccountNotFound3</p>";
            if ($_POST['send_link'] == 'toolbox') {
                Session::Messages($alert, 'alert-danger');
                redirect_to_home_page('main/toolbox.php');
            } else {
                $tool_content .= "<div class='alert alert-danger'>$alert</div>$homelink";
            }
        }
    }
} else {
    /*     * *** Email address entry form **** */
    $tool_content .= action_bar(array(
                                array('title' => $langBack,
                                      'url' => $urlServer,
                                      'icon' => 'fa-reply',
                                      'level' => 'primary-label',
                                      'button-class' => 'btn-default')
                            ),false);
    $tool_content .= "<div class='alert alert-info'>$lang_pass_intro</div><br>";
    $tool_content .= "
<div class='form-wrapper'>
    <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]'>
            <div class='row'><div class='col-sm-8'><h4>$langUserData</h4></div></div>
            <div class='form-group'>
                <div class='col-sm-8'>
                    <input class='form-control' type='text' name='userName' id='userName' autocomplete='off' placeholder='$lang_username'>
                </div>
            </div>
            <div class='form-group'>
                <div class='col-sm-8'>
                    <input class='form-control' type='text' name='email' id='email' autocomplete='off' placeholder='$lang_email'>
                </div>
            </div>
            <div class='form-group'>
                <div class='col-sm-8'>
                    <button class='btn btn-primary' type='submit' name='send_link' value='$lang_pass_submit'>$lang_pass_submit</button>
                    <button class='btn btn-default' href='$urlServer'>$langCancel</button>
                </div>
            </div>
    </form>
</div>";
}

draw($tool_content, 0, null, $head_content);
