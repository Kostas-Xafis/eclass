<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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


$mail_ver_excluded = true;
require_once '../include/baseTheme.php';
$toolName = $contactpoint;

$postaddress = nl2br(q(get_config('postaddress')));
$Institution = q(get_config('institution'));
$phone = q(get_config('phone'));
$fax = q(get_config('fax'));
$phonemessage = empty($phone) ? "<label>$langPhone:</label> <span class='not_visible'> - $langProfileNotAvailable - </span><br>" : "<label>$langPhone:&nbsp;</label>$phone<br>";
$faxmessage = empty($fax) ? "<label>$langFax</label> <span class='not_visible'> - $langProfileNotAvailable - </span><br>" : "<label>$langFax&nbsp;</label>$fax<br>";
$emailhelpdesk = get_config('email_helpdesk');
$emailhelpdesk = empty($emailhelpdesk) ? "<label>$langEmail:</label> <span class='not_visible'> - $langProfileNotAvailable - </span><br>" : "<label>$langEmail: </label>&nbsp;<a href='mailto:$emailhelpdesk'>".$emailhelpdesk."</a>";       

$tool_content .= action_bar(array(
                                array('title' => $langBack,
                                      'url' => $urlServer,
                                      'icon' => 'fa-reply',
                                      'level' => 'primary-label',
                                      'button-class' => 'btn-default')
                            ),false);
$tool_content .= "<div class='row'>
                    <div class='col-xs-12'>
                        <div class='panel'>
                            <div class='panel-body' style='display: flex; flex-direction: column;'>
                                <div>
                                    <label>$langPostMail&nbsp;</label>$Institution<br> $postaddress<br> $phonemessage $faxmessage $emailhelpdesk                                
                                </div>
                                <iframe 
                                    id='gmap'
                                    src='https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d953.7471360260289!2d23.708101534334496!3d37.96100861727749!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14a1bcf93dea0b05%3A0x2f890695cd7e38e1!2sHarokopio%20University!5e0!3m2!1sen!2sgr!4v1737727618309!5m2!1sen!2sgr'
                                    width='600'
                                    height='450'
                                    allowfullscreen=''
                                    loading='lazy'
                                    referrerpolicy='no-referrer-when-downgrade'>
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
                ";

if (isset($uid) and $uid) {
    draw($tool_content, 1);
} else {
    draw($tool_content, 0);
}
