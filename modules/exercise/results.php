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


require_once 'exercise.class.php';

$require_current_course = true;

require_once '../../include/baseTheme.php';
require_once 'include/lib/textLib.inc.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
ModalBoxHelper::loadModalBox();

$toolName = $langResults;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langExercices);

if (isset($_GET['exerciseId'])) {
    $exerciseId = getDirectReference($_GET['exerciseId']);
    $exerciseIdIndirect = $_GET['exerciseId'];
}

// if the object is not in the session
if (!isset($_SESSION['objExercise'][$exerciseId])) {
    // construction of Exercise
    $objExercise = new Exercise();
    // if the specified exercise doesn't exist or is disabled
    if (!$objExercise->read($exerciseId) && (!$is_editor)) {
        $tool_content .= "<p>$langExerciseNotFound</p>";
        draw($tool_content, 2);
        exit();
    }
    if(!$objExercise->selectScore() && !$is_editor) {
        redirect_to_home_page("modules/exercise/index.php?course=$course_code");
    }
}

if (isset($_SESSION['objExercise'][$exerciseId])) {
    $objExercise = $_SESSION['objExercise'][$exerciseId];
}

if ($is_editor && isset($_GET['purgeAttempID'])) {
    $eurid = $_GET['purgeAttempID'];
    $objExercise->purgeAttempt($exerciseIdIndirect, $eurid);
    Session::Messages($langPurgeExerciseResultsSuccess);
    redirect_to_home_page("modules/exercise/results.php?course=$course_code&exerciseId=" . getIndirectReference($_GET['exerciseId']));
}

$exerciseTitle = $objExercise->selectTitle();
$exerciseDescription = $objExercise->selectDescription();
$exerciseDescription_temp = nl2br(make_clickable($exerciseDescription));
$exerciseTimeConstraint = $objExercise->selectTimeConstraint();
$displayScore = $objExercise->selectScore();
$exerciseAttemptsAllowed = $objExercise->selectAttemptsAllowed();
$userAttempts = Database::get()->querySingle("SELECT COUNT(*) AS count FROM exercise_user_record WHERE eid = ?d AND uid= ?d", $exerciseId, $uid)->count;
$cur_date = new DateTime("now");
$end_date = new DateTime($objExercise->selectEndDate());
$showScore = $displayScore == 1
            || $is_editor
            || $displayScore == 3 && $exerciseAttemptsAllowed == $userAttempts
            || $displayScore == 4 && $end_date < $cur_date;


if ($is_editor) {
    $tool_content .= action_bar([
        [ 'title' => $langCheckGrades,
          'icon' => 'fa-bar-chart',
          'class' => 'check-grades',
          'level' => 'primary-label'
        ],
        [ 'title' => $langExport,
          'url' => "csv.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect",
          'icon' => 'fa-area-chart',
          'button-class' => 'btn-success',
          'level' => 'primary-label'
        ],
        [ 'title' => "$langExport $langExportWithAnswers",
          'url' => "csv.php?course=$course_code&amp;full=true&amp;exerciseId=$exerciseIdIndirect",
          'icon' => 'fa-area-chart',
          'button-class' => 'btn-success',
          'level' => 'primary-label'
        ]
    ]);
}

$tool_content .= "<div class='panel panel-primary'>
    <div class='panel panel-heading'>" . q_math($exerciseTitle) . "</div>";
if ($exerciseDescription_temp) {
    $tool_content .= "<div class='panel panel-body'>" . standard_text_escape($exerciseDescription_temp) . "</div>";
}
$tool_content .= "</div>";


$status = (isset($_GET['status'])) ? intval($_GET['status']) : '';
$tool_content .= "<select class='form-control' style='margin:0 0 12px 0;' id='status_filtering'>
        <option value='results.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect'>--- $langCurrentStatus ---</option>
        <option value='results.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect&amp;status=".ATTEMPT_ACTIVE."' ".(($status === 0)? 'selected' : '').">$langAttemptActive</option>
        <option value='results.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect&amp;status=".ATTEMPT_COMPLETED."' ".(($status === 1)? 'selected' : '').">$langAttemptCompleted</option>
        <option value='results.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect&amp;status=".ATTEMPT_PENDING."' ".(($status === 2)? 'selected' : '').">$langAttemptPending</option>
        <option value='results.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect&amp;status=".ATTEMPT_PAUSED."' ".(($status === 3)? 'selected' : '').">$langAttemptPaused</option>
        <option value='results.php?course=$course_code&amp;exerciseId=$exerciseIdIndirect&amp;status=".ATTEMPT_CANCELED."' ".(($status === 4)? 'selected' : '').">$langAttemptCanceled</option>
        </select>";
//This part of the code could be improved
if ($is_editor) {
    $result = Database::get()->queryArray("SELECT DISTINCT uid FROM `exercise_user_record` WHERE eid in (SELECT id FROM exercise WHERE course_id = ?d)", $course_id);
} else {
    $result[] = (object) array('uid' => $uid);
}
$extra_sql = ($status !== '' ) ? ' AND attempt_status = '.$status : '';

foreach ($result as $row) {
    $sid = $row->uid;
    // check if there is exercise assigned to teacher
    $testTheStudent = Database::get()->querySingle("SELECT assigned_to FROM `exercise_user_record` 
                                     WHERE eid = ?d 
                                     AND uid = ?d 
                                     AND attempt_status = " . ATTEMPT_PENDING . "", $exerciseId, $sid);
    if ($testTheStudent) {
        if ($testTheStudent->assigned_to != $_SESSION['uid'] && isset($testTheStudent->assigned_to)) {
            continue;
        }
    }    
    $theStudent = Database::get()->querySingle("SELECT surname, givenname, am FROM user WHERE id = ?d", $sid);

    $result2 = Database::get()->queryArray("SELECT
                    DATE_FORMAT(a.record_start_date, '%Y-%m-%d / %H:%i') AS record_start_date,
                    a.record_end_date,
                    IF (attempt_status = 1 OR b.time_constraint = 0,
                        TIME_TO_SEC(TIMEDIFF(a.record_end_date, a.record_start_date)),
                        b.time_constraint*60-a.secs_remaining) AS time_duration,
                    a.total_score, a.total_weighting, a.eurid, a.attempt_status, a.assigned_to 
                FROM `exercise_user_record` a, exercise b
                WHERE a.uid = ?d AND a.eid = ?d AND a.eid = b.id$extra_sql
                ORDER BY a.record_start_date DESC", $sid, $exerciseId);
    if (count($result2) > 0) { // if users found
        $tool_content .= "<div class='table-responsive'><table class='table-default'>";
        $tool_content .= "<tr><td colspan='".($is_editor ? 5 : 4)."'>";
        if (!$sid) {
            $tool_content .= "$langNoGroupStudents";
        } else {
            if ($theStudent->am == '') {
                $studentam = '-';
            } else {
                $studentam = $theStudent->am;
            }
            $tool_content .= "<strong>$langUser:</strong> " . q($theStudent->surname) . " " . q($theStudent->givenname) . "  <div class='smaller'>($langAm: " . q($studentam) . ")</div>";
        }
        $tool_content .= "</td>
                </tr>
                <tr>
                  <th class='text-center'>" . $langStart . "</th>
                  <th class='text-center'>" . $langExerciseDuration . "</th>
                  <th class='text-center'>" . $langTotalScore . "</th>
                  <th class='text-center'>" . $langCurrentStatus. "</th>
                  ". ($is_editor ? "<th class='text-center'>" . icon('fa-gears'). "</th>" : "") ."
                </tr>";

        $k = 0;
        foreach ($result2 as $row2) {
            // check if there is exercise assigned to teacher
            if ($row2->assigned_to != $_SESSION['uid'] && isset($row2->assigned_to)) {
                continue;
            }

            $row_class = "";
            if ($row2->attempt_status == ATTEMPT_COMPLETED) { // IF ATTEMPT COMPLETED
                $status = $langAttemptCompleted;
                if ($showScore) {
                    $answersCount = Database::get()->querySingle("SELECT count(*) AS answers_cnt FROM `exercise_answer_record` WHERE `eurid` = ?d", $row2->eurid)->answers_cnt;
                    if ($answersCount) {
                        $results_link = "<a href='exercise_result.php?course=$course_code&amp;eurId=$row2->eurid'>" . q($row2->total_score) . "/" . q($row2->total_weighting) . "</a>";
                    } else {
                        $results_link = q($row2->total_score) . "/" . q($row2->total_weighting);
                    }
                } else {
                    switch ($displayScore) {
                        case 2:
                            $results_link = $langScoreNotDisp;
                            break;
                        case 3:
                            $results_link = $langScoreDispLastAttempt;
                            break;
                        case 4:
                            $results_link = $langScoreDispEndDate;
                            break;
                    }
                }
            } else if ($row2->attempt_status == ATTEMPT_PAUSED) {
                $results_link = "-/-";
                $status = $langAttemptPaused;
            } else if ($row2->attempt_status == ATTEMPT_ACTIVE) {
                $results_link = "-/-";
                $status = $langAttemptActive;
                $now = new DateTime('NOW');
                $estimatedEndTime = DateTime::createFromFormat('Y-m-d / H:i', $row2->record_start_date);
                // in an active exercise if a time constaint passes the exercise can safely be deleted
                // if not it can be deleted after a day
                if ($exerciseTimeConstraint) {
                    $estimatedEndTime->add(new DateInterval('PT' . $exerciseTimeConstraint . 'M'));
                } else {
                    $estimatedEndTime->add(new DateInterval('P1D'));
                }
                if ($now > $estimatedEndTime) {
                    $row_class = " class='warning' data-toggle='tooltip' title='$langAttemptActiveButDeadMsg'";
                } else {
                    $row_class = " class='success' data-toggle='tooltip' title='$langAttemptActiveMsg'";
                }
                // IF ATTEMPT PENDING OR CANCELED
            } else if ($row2->attempt_status == ATTEMPT_PENDING) {
                $results_link = q($row2->total_score) . "/" . q($row2->total_weighting);
                $status = "<a href='exercise_result.php?course=$course_code&amp;eurId=$row2->eurid'>" . $langAttemptPending . "</a>";
            } else if ($row2->attempt_status == ATTEMPT_CANCELED) {
                $results_link = "-/-";
                $status = $langAttemptCanceled;
                $row_class = " class='danger' data-toggle='tooltip' title='$langAttemptCanceled''";
            }

            $tool_content .= "<tr$row_class><td class='text-center'>" . q($row2->record_start_date) . "</td>";
            if ($row2->time_duration == '00:00:00' || empty($row2->time_duration) || $row2->attempt_status == ATTEMPT_ACTIVE) { // for compatibility
                $tool_content .= "<td class='text-center'>$langNotRecorded</td>";
            } else {
                $tool_content .= "<td class='text-center'>" . format_time_duration($row2->time_duration) . "</td>";
            }
            $tool_content .= "<td class='text-center'>$results_link</td>
                              <td class='text-center'>$status</td>";
            if ($is_editor) {
                $tool_content .= "
                <td class='option-btn-cell'>" . action_button(array(
                        array(
                            'title' => $langDelete,
                            'url' => "results.php?course=$course_code&exerciseId=$exerciseId&purgeAttempID=$row2->eurid",
                            'icon' => "fa-times",
                            'confirm' => $langConfirmPurgeExercises,
                            'class' => 'delete'
                        )
                    )) . "</td>";
            }
            $tool_content .= "</tr>";
            $k++;
        }
        $tool_content .= "</table></div><br>";
    }
}

$tool_content .= "
    <script type='text/javascript'>
        $(function () {
          // bind change event to select
          $('#status_filtering').bind('change', function () {
              var url = $(this).val(); // get selected value
              if (url) { // require a URL
                  window.location = url; // redirect
              }
              return false;
          });
        });
    </script>";

if ($is_editor) {
    $tool_content .= "
    <script type='text/javascript'>
        $(function () {
          $('.check-grades').click(function (e) {
            var links = $('td a[href*=exercise_result]'),
                count = links.length,
                i = 0,
                itemsToRegrade = [];
            e.preventDefault();
            var dialog = bootbox.dialog({
              title: '" . js_escape($langCheckGradesConsistent) . "',
              message: '<div class=\"progress\">' +
                  '<div class=\"progress-bar progress-bar-striped active\" ' +
                  'role=\"progressbar\" style=\"min-width: 4em; width: 0%;\">' +
                  '0 / ' + count + '</div>'
              });
            var urls = $('td a[href*=exercise_result]').map(function (i, el) {
                return el.href + '&check=true';
            }).get();
            var regradeDialog;
            var regradeCallback = function (item) {
                if (item) {
                    $.post(item.url, { regrade: true },
                        function (data) {
                            i++;
                            regradeDialog.find('.progress-bar')
                                .css({width: (i / count * 100) + '%'})
                                .text(i + ' / ' + count);
                            regradeCallback(itemsToRegrade.shift());
                        });
                    } else {
                        window.location.replace('" . str_replace("'", "\\'", $_SERVER['REQUEST_URI']) . "');
                    }
            };
            var gradeCallback = function (url) {
                if (url) {
                    $.get(url, function (data) {
                        if (data['result'] != 'ok') {
                            itemsToRegrade.push(data);
                        }
                        i++;
                        dialog.find('.progress-bar')
                            .css({width: (i / count * 100) + '%'})
                            .text(i + ' / ' + count);
                        gradeCallback(urls.shift());
                    });
                } else {
                    dialog.modal('hide');
                    if (itemsToRegrade.length === 0) {
                        bootbox.alert({
                            title: '" . js_escape($langCheckGradesConsistent) . "',
                            message: '<p>" . js_escape($langCheckFinished . ' ' . $langRegradeNotNeeded) . "</p>',
                            backdrop: true
                        });
                    } else {
                        bootbox.confirm({
                            title: '" . js_escape($langCheckGradesConsistent) . "',
                            message: '<p>" . js_escape($langCheckFinished . ' ' . $langRegradeAttemptsList) . "</p><ul>' +
                                itemsToRegrade.map(function (item) {
                                    return '<li><a href=\"' + item.url + '\" target=\"_blank\">' + item.title + '</a></li>';
                                }).join('') + '</ul>',
                            buttons: {
                                cancel: {
                                    label: '" . js_escape($langCancel) . "'
                                },
                                confirm: {
                                    label: '" . js_escape($langRegradeAll) . "'
                                }
                            },
                            callback: function (result) {
                                if (result) {
                                    i = 0;
                                    count = itemsToRegrade.length;
                                    dialog.modal('hide');
                                    regradeDialog = bootbox.dialog({
                                      title: '" . js_escape($langRegradeAll) . "',
                                      message: '<div class=\"progress\">' +
                                          '<div class=\"progress-bar progress-bar-striped active\" ' +
                                          'role=\"progressbar\" style=\"min-width: 4em; width: 0%;\">' +
                                          '0 / ' + count + '</div>'
                                      });
                                    regradeCallback(itemsToRegrade.shift());
                                }
                            }
                        });
                    }
                }
            };
            gradeCallback(urls.shift());
          });
        });
    </script>";
}

draw($tool_content, 2, null, $head_content);
