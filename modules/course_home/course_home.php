<?php

/* ========================================================================
 * Open eClass 3.5
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

/**
 * @file: course_home.php
 * @brief: course home page
 */
$require_current_course = true;
$guest_allowed = true;

define('HIDE_TOOL_TITLE', 1);
define('STATIC_MODULE', 1);
require_once '../../include/baseTheme.php';
require_once 'include/lib/textLib.inc.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'include/lib/hierarchy.class.php';
require_once 'include/lib/course.class.php';
require_once 'include/action.php';
require_once 'include/course_settings.php';
require_once 'include/log.class.php';
require_once 'modules/sharing/sharing.php';
require_once 'modules/rating/class.rating.php';
require_once 'modules/comments/class.comment.php';
require_once 'modules/comments/class.commenting.php';
require_once 'include/lib/fileDisplayLib.inc.php';
require_once 'modules/document/doc_init.php';
require_once 'main/personal_calendar/calendar_events.class.php';
require_once 'modules/course_metadata/CourseXML.php';
require_once 'modules/progress/process_functions.php';

doc_init();
$tree = new Hierarchy();
$course = new Course();
$pageName = ''; // delete $pageName set in doc_init.php

$main_content = $cunits_content = $course_info_extra = "";

add_units_navigation(TRUE);

load_js('tools.js');

$course_info = Database::get()->querySingle("SELECT keywords, visible, prof_names, public_code, course_license, finish_date,
                                               view_type, start_date, finish_date, description, home_layout, course_image, password
                                          FROM course WHERE id = ?d", $course_id);

// Handle unit reordering
if ($is_editor and isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (isset($_POST['toReorder'])) {
        reorder_table('course_units', 'course_id', $course_id, $_POST['toReorder'],
            isset($_POST['prevReorder'])? $_POST['prevReorder']: null);
        exit;
    }
}

load_js('bootstrap-calendar');
load_js('bootstrap-calendar-master/components/underscore/underscore-min.js');
load_js('sortable/Sortable.min.js');

ModalBoxHelper::loadModalBox();
$head_content .= "<style>
    #collapseDescription {
        background-color: #f5f5f5;
    }
    #collapseDescription > div {
        padding: 20px;
    }
</style>";


/*
 *
 *
 */

$head_content .= "
<script type='text/javascript'>
    $(document).ready(function() {
        $('#btn-syllabus').click(function () {
            $(this).find('.fa-chevron-right').toggleClass('fa-rotate-90');
        });";

// Units sorting
if ($is_editor and $course_info->view_type == 'units') {
    $head_content .= '
        Sortable.create(boxlistSort, {
            animation: 350,
            handle: \'.fa-arrows\',
            animation: 150,
            onUpdate: function (evt) {
                var itemEl = $(evt.item);
                var idReorder = itemEl.attr(\'data-id\');
                var prevIdReorder = itemEl.prev().attr(\'data-id\');

                $.ajax({
                  type: \'post\',
                  dataType: \'text\',
                  data: {
                      toReorder: idReorder,
                      prevReorder: prevIdReorder,
                  }
                });
            }
        });';
}

// Calendar stuff
$head_content .= 'var calendar = $("#bootstrapcalendar").calendar({
                    tmpl_path: "'.$urlAppend.'js/bootstrap-calendar-master/tmpls/",
                    events_source: "'.$urlAppend.'main/calendar_data.php?course='.$course_code.'",
                    language: "'.$langLanguageCode.'",
                    views: {year:{enable: 0}, week:{enable: 0}, day:{enable: 0}},
                    onAfterViewLoad: function(view) {
                                $("#current-month").text(this.getTitle());
                                $(".btn-group button").removeClass("active");
                                $("button[data-calendar-view=\'" + view + "\']").addClass("active");
                                }
        });

        $(".btn-group button[data-calendar-nav]").each(function() {
            var $this = $(this);
            $this.click(function() {
                calendar.navigate($this.data("calendar-nav"));
            });
        });

        $(".btn-group button[data-calendar-view]").each(function() {
            var $this = $(this);
            $this.click(function() {
                calendar.view($this.data("calendar-view"));
            });
        });'

    ."})
    </script>";
$registerUrl = js_escape($urlAppend . 'modules/course_home/register.php?course=' . $course_code);
if ($course_info->password !== '') {
    $head_content .= "
        <script type='text/javascript'>
            $(function() {
                $('#passwordModal').on('click', function(e){
                    e.preventDefault();
                    bootbox.dialog({
                        title: '$langLessonCode',
                        message: '<form class=\"form-horizontal\" action=\"$registerUrl\" method=\"POST\" id=\"password_form\">'+
                                    '<div class=\"form-group\">'+
                                        '<div class=\"col-sm-12\">'+
                                            '<input type=\"password\" class=\"form-control\" id=\"password\" name=\"pass\">'+
                                            '<input type=\"hidden\" class=\"form-control\" name=\"register\" value=\"from-home\">'+
                                            \"" . generate_csrf_token_form_field() . "\" +
                                        '</div>'+
                                    '</div>'+
                                  '</form>',
                        buttons: {
                            cancel: {
                                label: '$langCancel',
                                className: 'btn-default'
                            },
                            success: {
                                label: '$langSubmit',
                                className: 'btn-success',
                                callback: function (d) {
                                    var password = $('#password').val();
                                    if (password != '') {
                                        $('#password_form').submit();
                                    } else {
                                        $('#password').closest('.form-group').addClass('has-error');
                                        $('#password').after('<span class=\"help-block\">$langTheFieldIsRequired</span>');
                                        return false;
                                    }
                                }
                            }
                        }
                    });
                })
            });
        </script>";
} else {
    $head_content .= "
        <script type='text/javascript'>
            $(function() {
              $('#passwordModal').on('click', function(e){
                e.preventDefault();
                $('<form method=\"POST\" action=\"$registerUrl\">' +
                  '<input type=\"hidden\" name=\"register\" value=\"true\">' +
                  \"" . generate_csrf_token_form_field() . "\" +
                  '</form>').appendTo('body').submit();
              });
            });
        </script>";
}

// For statistics: record login
Database::get()->query("INSERT INTO logins
    SET user_id = ?d, course_id = ?d, ip = ?s, date_time = " . DBHelper::timeAfter(),
    $uid, $course_id, Log::get_client_ip());

// opencourses hits summation
$visitsopencourses = 0;
$hitsopencourses = 0;
if (get_config('opencourses_enable')) {
    $cxml = CourseXMLElement::initFromFile($course_code);
    $reslastupdate = Database::get()->querySingle("select datestamp from oai_record where course_id = ?d and deleted = ?d", $course_id, 0);
    $lastupdate = null;
    if ($reslastupdate) {
        $lastupdate = strtotime($reslastupdate->datestamp);
    }
    if ($cxml && $lastupdate && (time() - $lastupdate > 24 * 60 * 60)) {
        // need to refresh hits when no update occurred during the last 24 hours
        CourseXMLElement::refreshCourse($course_id, $course_code);
        $cxml = CourseXMLElement::initFromFile($course_code);
    }
    $visitsopencourses = ($cxml && $cxml->visits) ? intval((string) $cxml->visits) : 0;
    $hitsopencourses = ($cxml && $cxml->hits) ? intval((string) $cxml->hits) : 0;
}

$action = new action();
$action->record(MODULE_ID_UNITS);

if (isset($_GET['from_search'])) { // if we come from home page search
    header("Location: {$urlServer}modules/search/search_incourse.php?all=true&search_terms=$_GET[from_search]");
}

$keywords = q(trim($course_info->keywords));
$visible = $course_info->visible;
$professor = $course_info->prof_names;
$public_code = $course_info->public_code;
$course_license = $course_info->course_license;

$res = Database::get()->queryArray("SELECT cd.id, cd.title, cd.comments, cd.type, cdt.icon FROM course_description cd
                                    LEFT JOIN course_description_type cdt ON (cd.type = cdt.id)
                                    WHERE cd.course_id = ?d AND cd.visible = 1 ORDER BY cd.order", $course_id);

$head_content .= "
        <script>
            $(function() {
                $('body').keydown(function(e) {
                    if(e.keyCode == 37 || e.keyCode == 39) {
                        if ($('.modal.in').length) {
                            var visible_modal_id = $('.modal.in').attr('id').match(/\d+/);
                            if (e.keyCode == 37) {
                                var new_modal_id = parseInt(visible_modal_id) - 1;
                            } else {
                                var new_modal_id = parseInt(visible_modal_id) + 1;
                            }
                            var new_modal = $('#hidden_'+new_modal_id);
                            if (new_modal.length) {
                                hideVisibleModal();
                                new_modal.modal('show');
                            }
                        }
                    }
                });
            });
            function hideVisibleModal(){
                var visible_modal = $('.modal.in');
                if (visible_modal) { // modal is active
                    visible_modal.modal('hide'); // close modal
                }
            };
        </script>";

if (count($res) > 0) {
    foreach ($res as $key => $row) {
        $desctype = intval($row->type) - 1;
        $hidden_id = "hidden_" . $key;
        $next_id = '';
        $previous_id = '';
        if ($key + 1 < count($res)) $next_id = "hidden_" . ($key + 1);
        if ($key > 0) $previous_id = "hidden_" . ($key - 1);

        $tool_content .= "<div class='modal fade' id='$hidden_id' tabindex='-1' role='dialog' aria-labelledby='myModalLabel_$key' aria-hidden='true'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                        <div class='modal-header'>
                                        <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                        <div class='modal-title h4' id='myModalLabel_$key'>" . q($row->title) . "</div>
                                    </div>
                                    <div class='modal-body' style='max-height: calc(100vh - 210px); overflow-y: auto;'>".
                                      standard_text_escape($row->comments)
                                    ."</div>
                                    <div class='modal-footer'>";
                                        if ($previous_id) {
                                            $tool_content .= "<a class='btn btn-default' data-dismiss='modal' data-toggle='modal' href='#$previous_id'><span class='fa fa-arrow-left'></span></a>";
                                        }
                                        if ($next_id) {
                                            $tool_content .= "<a class='btn btn-default' data-dismiss='modal' data-toggle='modal' href='#$next_id'><span class='fa fa-arrow-right'></span></a>";
                                        }
        $tool_content .= "
                                    </div>
                                  </div>
                                </div>
                              </div>";
        $course_info_extra .= "<a class='list-group-item' data-modal='syllabus-prof' data-toggle='modal' data-target='#$hidden_id' href='javascript:void(0);'>".q($row->title) ."</a>";
    }
} else {
    $course_info_extra = "<div class='text-muted'>$langNoInfoAvailable</div>";
}
$main_content .= "<div class='course_info'>";
if ($course_info->description) {
    $description = standard_text_escape($course_info->description);

    // Text button for read more & read less
    $postfix_truncate_more = "<a href='#' class='more_less_btn'>$langReadMore &nbsp;<span class='fa fa-arrow-down'></span></a>";
    $postfix_truncate_less = "<a href='#' class='more_less_btn'>$langReadLess &nbsp;<span class='fa fa-arrow-up'></span></a>";

    // Create full description text & truncated text
    $full_description = $description.$postfix_truncate_less;
    $truncated_text = ellipsize_html($description, 1000, $postfix_truncate_more);

    // Hidden html text to store the full description text & the truncated desctiption text so as to be accessed by javascript
    $main_content .= "<div id='not_truncated' class='hidden'>$full_description</div>";
    $main_content .= "<div id='truncated' class='hidden'>$truncated_text</div>";

    // Show the description text
    $main_content .= "<div id='descr_content' class='is_less'>$truncated_text</div>";

} else {
    $main_content .= "<p class='not_visible'> - $langThisCourseDescriptionIsEmpty - </p>";
}
/* Disable keywords
if (!empty($keywords)) {
    $main_content .= "<p id='keywords'><strong>$langCourseKeywords</strong> $keywords</p>";
}
*/
$main_content .= "</div>";

if (!empty($addon)) {
    $main_content .= "<div class='course_info'><div class='h1'>$langCourseAddon</div><p>$addon</p></div>";
}
if (setting_get(SETTING_COURSE_COMMENT_ENABLE, $course_id) == 1) {
    commenting_add_js();
    $comm = new Commenting('course', $course_id);
    $comment_content = $comm->put($course_code, $is_editor, $uid);
}
if (setting_get(SETTING_COURSE_RATING_ENABLE, $course_id) == 1) {
    $rating = new Rating('fivestar', 'course', $course_id);
    $rating_content = $rating->put($is_editor, $uid, $course_id);
}
if (is_sharing_allowed($course_id)) {
    if (setting_get(SETTING_COURSE_SHARING_ENABLE, $course_id) == 1) {
        $social_content = print_sharing_links($urlServer."courses/$course_code", $currentCourseName);
    }
}
$panel_footer = "";
if(isset($rating_content) || isset($social_content) || isset($comment_content)) {
    $panel_footer .= "
                <div class='panel-footer'>
                    <div class='row'>";
    if(isset($rating_content)){
     $panel_footer .=
            "<div class='col-sm-6'>
                $rating_content
            </div>";
    }
    if(isset($social_content) || isset($comment_content)){

        $subcontent = "";

        if(isset($comment_content)){
            $subcontent .= $comment_content;
        }
        if(isset($social_content) && isset($comment_content)){
            $subcontent .= "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp; ";
        }
        if(isset($social_content)){
            $subcontent .= $social_content;
        }
        $panel_footer .=
            "<div class='text-right ".(isset($rating_content) ? "col-xs-6" : "col-xs-12")."'>
                $subcontent
            </div>";
    }

    $panel_footer .= "
                </div>
            </div>";
}

// other actions in course unit
if ($is_editor) {
    // update index and refresh course metadata
    require_once 'modules/search/indexer.class.php';

    if (isset($_REQUEST['del'])) { // delete course unit
        $id = intval(getDirectReference($_REQUEST['del']));
        if ($course_info->view_type == 'units') {
            Database::get()->query("UPDATE `course_units` SET `order`=`order` - 1 WHERE `order`>?d", $_REQUEST['order']);
            Database::get()->query('DELETE FROM course_units WHERE id = ?d', $id);
            Database::get()->query('DELETE FROM unit_resources WHERE unit_id = ?d', $id);
            Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_UNIT, $id);
            Indexer::queueAsync(Indexer::REQUEST_REMOVEBYUNIT, Indexer::RESOURCE_UNITRESOURCE, $id);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_COURSE, $course_id);
            CourseXMLElement::refreshCourse($course_id, $course_code);
            Session::Messages($langCourseUnitDeleted, 'alert-success');
            redirect_to_home_page("courses/$course_code/");
        }
    } elseif (isset($_REQUEST['vis'])) { // modify visibility
        $id = intval(getDirectReference($_REQUEST['vis']));
        $vis = Database::get()->querySingle("SELECT `visible` FROM course_units WHERE id = ?d", $id)->visible;
        $newvis = ($vis == 1) ? 0 : 1;
        Database::get()->query("UPDATE course_units SET visible = ?d WHERE id = ?d AND course_id = ?d", $newvis, $id, $course_id);
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_UNIT, $id);
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_COURSE, $course_id);
        CourseXMLElement::refreshCourse($course_id, $course_code);
        redirect_to_home_page("courses/$course_code/#unit_$_REQUEST[vis]");
    } elseif (isset($_REQUEST['access'])) {
        $id = intval(getDirectReference($_REQUEST['access']));
        $access = Database::get()->querySingle("SELECT `public` FROM course_units WHERE id = ?d", $id);
        $newaccess = ($access->public == '1') ? '0' : '1';
        Database::get()->query("UPDATE course_units SET public = ?d WHERE id = ?d AND course_id = ?d", $newaccess, $id, $course_id);
        redirect_to_home_page("courses/$course_code/#unit_$_REQUEST[access]");
    } elseif (isset($_REQUEST['down'])) {
        $id = intval(getDirectReference($_REQUEST['down'])); // change order down
        if ($course_info->view_type == 'units' or $course_info->view_type == 'simple') {
            move_order('course_units', 'id', $id, 'order', 'down', "course_id=$course_id");
        }
    } elseif (isset($_REQUEST['up'])) { // change order up
        $id = intval(getDirectReference($_REQUEST['up']));
        if ($course_info->view_type == 'units' or $course_info->view_type == 'simple') {
            move_order('course_units', 'id', $id, 'order', 'up', "course_id=$course_id");
        }
    }

}

$numUsers = Database::get()->querySingle("SELECT COUNT(user_id) AS numUsers
                FROM course_user
                WHERE course_id = ?d", $course_id)->numUsers;
$studentUsers = Database::get()->querySingle("SELECT COUNT(*) AS studentUsers FROM course_user
                                        WHERE status = " .USER_STUDENT . "
                                            AND editor = 0
                                            AND course_id = ?d", $course_id)->studentUsers;

if ($uid) {
    $course_completion_id = is_course_completion_active(); // is course completion active?
    if ($course_completion_id) {
        if ($is_editor) {
            $certified_users = Database::get()->querySingle("SELECT COUNT(*) AS t FROM user_badge
                                                              JOIN course_user ON user_badge.user=course_user.user_id
                                                                    AND status = " .USER_STUDENT . "
                                                                    AND editor = 0
                                                                    AND course_id = ?d
                                                                    AND completed = 1
                                                                    AND badge = ?d", $course_id, $course_completion_id)->t;
        } else {
            $course_completion_status = has_certificate_completed($uid, 'badge', $course_completion_id);
            $percentage = get_cert_percentage_completion('badge', $course_completion_id) . "%";
        }
    }
}

// display course citation
$citation_text = q($professor) . ". <em>" . q($currentCourseName) .
    ".</em> $langAccessed" . claro_format_locale_date($dateFormatLong, strtotime('now')) .
    " $langFrom2 {$urlServer}courses/$course_code/";

$tool_content .= "<div class='modal fade' id='citation' tabindex='-1' role='dialog' aria-labelledby='citationModalLabel' aria-hidden='true'>
                    <div class='modal-dialog'>
                        <div class='modal-content'>
                            <div class='modal-header'>
                                <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                                <div class='modal-title h4' id='citationModalLabel'>$langCitation</div>
                            </div>
                            <div class='modal-body'>".
                              standard_text_escape($citation_text)
                            ."</div>
                        </div>
                    </div>
                </div>";

// display opencourses level in bar
$level = ($levres = Database::get()->querySingle("SELECT level FROM course_review WHERE course_id =  ?d", $course_id)) ? CourseXMLElement::getLevel($levres->level) : false;
if (isset($level) && !empty($level)) {
    $metadataUrl = $urlServer . 'modules/course_metadata/info.php?course=' . $course_code;
    $head_content .= "
<link rel='stylesheet' type='text/css' href='{$urlAppend}modules/course_metadata/course_metadata.css'>
<style type='text/css'></style>
<script type='text/javascript'>
/* <![CDATA[ */

    var dialog;

    var showMetadata = function(course) {
        $('.modal-body', dialog).load('../../modules/course_metadata/anoninfo.php', {course: course}, function(response, status, xhr) {
            if (status === 'error') {
                $('.modal-body', dialog).html('Sorry but there was an error, please try again');
            }
        });
        dialog.modal('show');
    };

    $(document).ready(function() {

        dialog = $(\"<div class='modal fade' tabindex='-1' role='dialog' aria-labelledby='modal-label' aria-hidden='true'><div class='modal-dialog modal-lg'><div class='modal-content'><div class='modal-header'><button type='button' class='close' data-dismiss='modal'><span aria-hidden='true'>&times;</span><span class='sr-only'>{$langCancel}</span></button><div class='modal-title h4' id='modal-label'>{$langCourseMetadata}</div></div><div class='modal-body'>body</div></div></div></div>\");
    });

/* ]]> */
</script>";
    $opencourses_level = "
    <div class='row'>
        <div class='col-xs-4'>
            <img class='img-responsive center-block' src='$themeimg/open_courses_logo_small.png' title='" . $langOpenCourses . "' alt='" . $langOpenCourses . "' />
        </div>
        <div class='col-xs-8'>
            <div style='border-bottom:1px solid #ccc; margin-bottom: 5px;'>${langOpenCoursesLevel}: $level</div>
            <p class='not_visible'>
            <small>$langVisitsShort : &nbsp;$visitsopencourses</small>
            <br />
            <small>$langHitsShort : &nbsp;$hitsopencourses</small>
            </p>
        </div>
    </div>
";
    $opencourses_level_footer = "<div class='row'>
        <div class='col-xs-12 text-right'>
            <small><a href='javascript:showMetadata(\"$course_code\");'>$langCourseMetadata</a>".icon('fa-tags', $langCourseMetadata, "javascript:showMetadata(\"$course_code\");")."</small>
        </div>
    </div>";
}

if ($course_info->home_layout == 3) {
    $left_column = '';
    $main_content_cols = '';
} else {
    $course_image_url = isset($course_info->course_image) ? "{$urlAppend}courses/$course_code/image/" . rawurlencode($course_info->course_image) : "$themeimg/ph1.jpg";
    $left_column = "
        <div class='banner-image-wrapper col-md-5 col-sm-5 col-xs-12'>
            <div>
                <img class='banner-image img-responsive' src='$course_image_url' alt='Course Banner'/>
            </div>
        </div>";
   $main_content_cols = 'col-sm-7';
}
$edit_link = $action_bar = '';
if ($is_editor) {
    warnCourseInvalidDepartment(true);
    $edit_link = "
        <div class='access access-edit pull-left'><a href='{$urlAppend}modules/course_home/editdesc.php?course=$course_code'><span class='fa fa-pencil' style='line-height: 30px;' data-toggle='tooltip' data-placement='top' title='Επεξεργασία πληροφοριών'></span><span class='hidden'>.</span></a></div>";
} elseif ($uid) {
    $myCourses = [];
    Database::get()->queryFunc("SELECT course.code course_code, course.public_code public_code,
                                   course.id course_id, status
                              FROM course_user, course
                             WHERE course_user.course_id = course.id
                               AND user_id = ?d", function ($course) use (&$myCourses) {
        $myCourses[$course->course_id] = $course;
    }, $uid);
    if (!in_array($course_id, array_keys($myCourses))) {
        $action_bar = action_bar(array(
            array('title' => $langRegister,
                  'url' => $urlAppend . "modules/course_home/register.php?course=$course_code",
                  'icon' => 'fa-check',
                  'link-attrs' => "id='passwordModal'",
                  'level' => 'primary-label',
                  'button-class' => 'btn-success')));
    }

    $edit_link = " ";
}

$courseDescriptionVisible = count($res);

$course_info_popover = "<div class='list-group'>$course_info_extra</div>";

$categoryItems = array();
Database::get()->queryFunc('SELECT category_id, category.name AS category_name,
        category_value_id AS value_id, category_value.name AS value_name
    FROM course_category
        INNER JOIN category_value ON course_category.category_value_id = category_value.id
        INNER JOIN category ON category_value.category_id = category.id
    WHERE course_id = ?d
    ORDER BY category.ordering, category_value.ordering',
    function ($item) use (&$categoryItems) {
        $cid = $item->category_id;
        if (!isset($categoryItems[$cid])) {
            $categoryItems[$cid] = "
                <div class='col-xs-12 col-sm-6 col-md-3'>
                    <ul class='list-unstyled'>
                        <li><strong>" . q(getSerializedMessage($item->category_name)) . "</strong></li>";
        }
        $categoryItems[$cid] .= "<li><span class='fa fa-plus-circle fa-fw'></span>" .
            q(getSerializedMessage($item->value_name)) . "</li>";
    }, $course_id);

if (count($categoryItems)) {
    foreach ($categoryItems as &$item) {
        $item .= '</ul></div>';
    }
    $categoryDisplay = "
        <div class='col-xs-12'>
            <div class='row margin-top-thin'>" . implode('', $categoryItems) . "</div>
        </div>";
} else {
    $categoryDisplay = '';
}

// offline course setting
$offline_course = get_config('offline_course') && (setting_get(SETTING_OFFLINE_COURSE, $course_id));

// content
$tool_content .= "
$action_bar
<div class='row margin-top-thin margin-bottom-fat'>
    <div class='col-md-12'>
        <div class='panel panel-default'>

            <div class='panel-body'>
                <div id='course-title-wrapper' class='course-info-title clearfix'>
                    <div class='pull-left h4'>$langDescription</div> $edit_link
                    <ul class='course-title-actions clearfix pull-right list-inline'>" .

                        ($is_course_admin? "<li class='access pull-right'><a href='{$urlAppend}modules/course_info/?course=$course_code' style='color: #23527C;'><span class='fa fa-wrench fa-fw' data-toggle='tooltip' data-placement='top' title='$langCourseInfo'></span></a></li>": '') . "
                        <li class='access pull-right'><a href='javascript:void(0);'>". course_access_icon($visible) . "</a></li>
                        <li class='access pull-right'><a data-modal='citation' data-toggle='modal' data-target='#citation' href='javascript:void(0);'><span class='fa fa-paperclip fa-fw' data-toggle='tooltip' data-placement='top' title='$langCitation'></span><span class='hidden'>.</span></a></li>";
                        if ($uid) {
                            $tool_content .= "<li class='access pull-right'>";
                            if ($is_course_admin) {
                                $tool_content .= "<a href='{$urlAppend}modules/user/index.php?course=$course_code'>
                                        <span class='fa fa-users fa-fw' data-toggle='tooltip' data-placement='top' title='$numUsers $langRegistered'></span>
                                        <span class='hidden'>.</span>
                                    </a>";
                            } else {
                                if ($visible == COURSE_CLOSED) {
                                    $tool_content .= "<a href = '{$urlAppend}modules/user/userslist.php?course=$course_code'>
                                            <span class='fa fa-users fa-fw' data-toggle='tooltip' data-placement='top' title='$numUsers $langRegistered'></span>
                                            <span class='hidden'>.</span>
                                        </a>";
                                }
                            }
                            $tool_content .= "</li>";
                        }
                        if ($offline_course) {
                            $tool_content .= "<li class='access pull-right'>
                                    <a href='{$urlAppend}modules/offline/index.php?course={$course_code}'>
                                        <span class='fa fa-download fa-fw' data-toggle='tooltip' data-placement='top' title='$langDownloadCourse'></span>
                                    </a>
                                </li>";
                        }
                        $tool_content .= "
                    </ul>
                </div>
                $left_column
                <div class='col-xs-12 $main_content_cols'>
                    $main_content
                </div>
                $categoryDisplay";

                $edit_course_desc_link = '';
                if ($is_editor) {
                    if ($courseDescriptionVisible > 0) {
                        $edit_course_desc_link = "&nbsp;&nbsp;" . icon('fa-pencil', $langCourseDescription,$urlAppend . "modules/course_description/index.php?course=" . $course_code);
                    } else {
                        $edit_course_desc_link = "&nbsp;&nbsp;" . icon('fa-plus', $langAdd,$urlAppend . "modules/course_description/index.php?course=" . $course_code);
                    }
                }
                // course description
                if ((!$is_editor) and (!$courseDescriptionVisible)) { // if there is no course info don't display link to users;
                    $tool_content .= "";
                    if ($course_license) {
                        $tool_content .= "<span class='pull-right' style='margin-top: 15px;'>" . copyright_info($course_id) . "</span>";
                    }
                } else {
                    $tool_content .= "
                        <div class='col-xs-12 course-below-wrapper' style='margin-top: 25px;'>
                            <div class='course-info-title clearfix'>
                                    <h5>
                                  <a role='button' id='btn-syllabus' data-toggle='collapse' href='#collapseDescription' aria-expanded='false' aria-controls='collapseDescription'>
                                            <span class='fa fa-chevron-right fa-fw'></span><span style='padding-left: 5px;'>$langCourseDescription</span></a>
                                            $edit_course_desc_link";
                                        // course license
                                    if ($course_license) {
                                        $tool_content .= "<span class='pull-right'>" . copyright_info($course_id) . "</span>";
                                    }
                                  $tool_content .= "</h4>";
                                    // course description
                                  $tool_content .= "<div class='collapse' id='collapseDescription' style='margin-top: 20px;'>";
                                  foreach ($res as $row) {
                                      $tool_content .= "<div style='margin-top: 1px;'><strong>" . q($row->title) . "</strong></div>
                                                        <div style='margin-left: 10px; margin-right: 10px;'>" . standard_text_escape($row->comments) . "</div>";
                                  }
                    $tool_content .= "</div></div></div>";
                }
                $tool_content .= "
            </div>
        $panel_footer
    </div>
    </div>
</div>
";


if ($is_editor) {
    $last_id = Database::get()->querySingle("SELECT id FROM course_units
                                                   WHERE course_id = ?d AND `order` >= 0
                                                   ORDER BY `order` DESC LIMIT 1", $course_id);
    if ($last_id) {
        $last_id = $last_id->id;
    }
    $query = "SELECT id, title, start_week, finish_week, comments, visible, public, `order` FROM course_units WHERE course_id = ?d AND `order` >= 0 ORDER BY `order`";
} else {
    $query = "SELECT id, title, start_week, finish_week, comments, visible, public, `order` FROM course_units WHERE course_id = ?d AND visible = 1 AND `order` >= 0 ORDER BY `order`";
}

$sql = Database::get()->queryArray($query, $course_id);
$total_cunits = count($sql);
if ($total_cunits > 0) {
    $cunits_content .= "";
    $count_index = 0;
    foreach ($sql as $cu) {
        $not_shown = false;
        // check if course unit has started
        if (!(is_null($cu->start_week)) and (date('Y-m-d') < $cu->start_week)) {
            $not_shown = true;
        }
        // check visibility
        if ($cu->visible == 1) {
            $count_index++;
        }
        // access status
        $access = $cu->public;
        // Visibility icon and class
        $vis = $cu->visible;
        $class_vis = ($vis == 0 or $not_shown) ? 'not_visible' : '';
        $cu_indirect = getIndirectReference($cu->id);
        if (!$is_editor and $not_shown) {
            continue;
        } else {
            $cunits_content .= "<div id='unit_$cu_indirect' class='col-xs-12' data-id='$cu->id'><div class='panel clearfix'><div class='col-xs-12'>
            <div class='item-content'>
              <div class='item-header clearfix'>
                <div class='item-title h4'>
                    <a class='$class_vis' href='${urlServer}modules/units/?course=$course_code&amp;id=$cu->id'>" . q($cu->title) . "</a>";
                    $cunits_content .= "<small><span class='help-block'>";
                    if (!(is_null($cu->start_week))) {
                        $cunits_content .= "$langFrom2 " . nice_format($cu->start_week);
                    }
                    if (!(is_null($cu->finish_week))) {
                        $cunits_content .= " $langTill " . nice_format($cu->finish_week);
                    }
                    $cunits_content .= "</span></small>";
                $cunits_content .= "</div>";
        }


        if ($is_editor) {
            $cunits_content .= "<div class='item-side'>
                                  <div class='reorder-btn'>
                                    <span class='fa fa-arrows' data-toggle='tooltip' data-placement='top' title='$langReorder'></span>
                                      </div>" .
                action_button(array(
                    array('title' => $langEditChange,
                          'url' => $urlAppend . "modules/units/info.php?course=$course_code&amp;edit=$cu->id",
                          'icon' => 'fa-edit'),
                    array('title' => $vis == 1? $langViewHide : $langViewShow,
                          'url' => "$_SERVER[REQUEST_URI]?vis=$cu_indirect",
                          'icon' => $vis == 1? 'fa-eye-slash' : 'fa-eye'),
                    array('title' => $access == 1? $langResourceAccessLock : $langResourceAccessUnlock,
                          'url' => "$_SERVER[REQUEST_URI]?access=$cu_indirect",
                          'icon' => $access == 1? 'fa-lock' : 'fa-unlock',
                          'show' => $visible == COURSE_OPEN),
                    array('title' => $langDelete,
                          'url' => "$_SERVER[REQUEST_URI]?del=$cu_indirect&order=".$cu->order,
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langCourseUnitDeleteConfirm))) .
                '</div>';
        }
        $cunits_content .= "</div>
            <div class='item-body'>";
        $cunits_content .= ($cu->comments == ' ')? '': standard_text_escape($cu->comments);
        $cunits_content .= "</div></div>";
        $cunits_content .= "</div></div></div>";
    }
} else {
    $cunits_content .= "<div class='col-sm-12'><div class='panel'><div class='panel-body not_visible'> - $langNoUnits - </div></div></div>";
}

// Content Box: Course Units
// Content Box: Calendar
// Content Box: Announcements
$displayCalendar = Database::get()->querySingle('SELECT MAX(visible) AS vis FROM course_module
                            WHERE course_id = ?d AND module_id IN (?d, ?d, ?d, ?d)',
                            $course_id, MODULE_ID_AGENDA, MODULE_ID_EXERCISE,
                            MODULE_ID_TC, MODULE_ID_ASSIGN)->vis;
$displayAnnouncements = Database::get()->querySingle('SELECT visible FROM course_module
                            WHERE course_id = ?d AND module_id = ' . MODULE_ID_ANNOUNCE,
                            $course_id)->visible;

if (($total_cunits > 0 or $is_editor) and $course_info->view_type == 'units') {
    $alter_layout = FALSE;
    $cunits_sidebar_columns = 4;
    $cunits_sidebar_subcolumns = 12;
} else {
    $alter_layout = TRUE;
    $cunits_sidebar_columns = 12;
    $cunits_sidebar_subcolumns = 6;
}
$tool_content .= "<div class='row'>";
if ($course_info->view_type == 'activity') {
    if ($is_editor) {
        $tool_content .= "
            <div class='col-xs-12 margin-bottom-thin'>
                <a href='{$urlAppend}modules/course_info/activity_edit.php?course=$course_code' class='btn btn-default'>
                    <span class='fa fa-edit fa-fw'></span>" . q($langActivityEdit) . "</a>
            </div>";
    }
    $tool_content .= "<div class='col-md-12'>";
    $qVisible = ($is_editor? '': 'AND visible = 1');
    $items = Database::get()->queryArray("SELECT activity_content.id, heading, content
        FROM activity_heading
            LEFT JOIN activity_content
                ON activity_heading.id = activity_content.heading_id AND
                   course_id = ?d
        ORDER BY `order`", $course_id);
    foreach ($items as $item) {
        if (trim($item->content)) {
            $tool_content .= "
                    <div class='panel panel-default clearfix'>
                        <div class='panel-heading'><h4>" . q(getSerializedMessage($item->heading)) ."</h4></div>
                        <div class='panel-body'>" . standard_text_escape($item->content) . "</div>";
            $resources = Database::get()->queryArray("SELECT * FROM unit_resources
                WHERE unit_id = ?d AND `order` >= 0 $qVisible ORDER BY `order`", $item->id);
            if (count($resources)) {
                $tool_content .= "
                        <div class='table-responsive'>
                            <table class='table table-striped table-hover'>
                                <tbody>";
                foreach ($resources as $info) {
                    $info->comments = standard_text_escape($info->comments);
                    show_resourceWeek($info);
                }
                $tool_content .= "
                                </tbody>
                            </table>
                        </div>";
            }
            $tool_content .= "</div>";
        }
    }
    $tool_content .= "</div>";

} elseif (!$alter_layout) {
    $unititle = $langCourseUnits;
    $tool_content .= "
    <div class='col-md-8 course-units'>
        <div class='row'>
            <div class='col-md-12'>
                <h3 class='content-title pull-left'>$unititle</h3>";

    //help icon
    $head_content .= "
        <script>
        $(function() {
            $('#help-btn').click(function(e) {
                e.preventDefault();
                $.get($(this).attr(\"href\"), function(data) {bootbox.alert(data);});
            });
        });
        </script>
        ";
    $tool_content .= "<a class='pull-left add-unit-btn' id='help-btn' href=\"" . $urlAppend . "modules/help/help.php?language=$language&amp;topic=course_units\" data-toggle='tooltip' data-placement='top' title='$langHelp'>
            <span class='fa fa-question-circle'></span>
        </a>";

    if ($is_editor and $course_info->view_type == 'units') {
        $link = "{$urlServer}modules/units/info.php?course=$course_code";
        $tool_content .= "<a href='$link' class='pull-left add-unit-btn' data-toggle='tooltip' data-placement='top' title='$langAddUnit'>
              <span class='fa fa-plus-circle'></span><span class='hidden'>.</span></a>";
    }

    $tool_content .= "
            </div>
        </div>
        <div class='row boxlist no-list' id='boxlistSort'>
            $cunits_content
        </div>
    </div>";
}

$tool_content .= "
    <div class='col-md-$cunits_sidebar_columns'>
        <div class='row'>";

// display course completion status (if defined)
if (isset($course_completion_id) and $course_completion_id > 0) {
        $tool_content .= "<div class='col-md-$cunits_sidebar_subcolumns'>
            <div class='content-title'>$langCourseCompletion</div>
            <div class='panel'>
                <div class='panel-body'>
                    <div class='text-center'>
                        <div class='col-sm-12'>";
                        if ($is_editor) {
                            $tool_content .= "<i class='fa fa-trophy fa-2x'></i><h5><a href='{$urlServer}modules/progress/index.php?course=$course_code&badge_id=$course_completion_id&progressall=true'>$langHasBeenCompleted $certified_users/$studentUsers $langUsersS</a></h5>";
                        } else {
                            $tool_content .= "<div class='center-block' style='display:inline-block;'>
                                <a style='text-decoration:none;' href='{$urlServer}modules/progress/index.php?course=$course_code&badge_id=$course_completion_id&u=$uid'>";
                            if ($percentage == '100%') {
                                $tool_content .= "<span class='fa fa-check-circle fa-5x state_success'></span>";
                            } else {
                                $tool_content .= "<div class='course_completion_panel_percentage'>$percentage</div>";
                            }
                            $tool_content .= "</a></div>";
                        }
                            $tool_content .= "
                        </div>
                    </div>
                </div>
            </div>
        </div>";
}



// display open course level if exist
if (isset($level) && !empty($level)) {
    $tool_content .= "
            <div class='col-md-$cunits_sidebar_subcolumns'>
                <h3 class='content-title'>$langOpenCourseShort</h3>
                <div class='panel'>
                    <div class='panel-body'>
                        $opencourses_level
                    </div>
                    <div class='panel-footer'>
                        $opencourses_level_footer
                    </div>
                </div>
            </div>";
}

if ($displayCalendar) {
    //BEGIN - Get user personal calendar
    $today = getdate();
    $day = $today['mday'];
    $month = $today['mon'];
    $year = $today['year'];
    if (isset($uid)) {
        Calendar_Events::get_calendar_settings();
    }
    $user_personal_calendar = Calendar_Events::small_month_calendar($day, $month, $year);
    //END - Get personal calendar
    $tool_content .= "
            <div class='col-md-$cunits_sidebar_subcolumns'>
                <h3 class='content-title'>$langCalendar</h3>
                <div class='panel'>
                    <div class='panel-body'>
                        $user_personal_calendar
                    </div>
                    <div class='panel-footer'>
                        <div class='row'>
                            <div class='col-sm-6 event-legend'>
                                <div>
                                    <span class='event event-important'></span><span>$langAgendaDueDay</span>
                                </div>
                                <div>
                                    <span class='event event-info'></span><span>$langAgendaCourseEvent</span>
                                </div>
                            </div>
                            <div class='col-sm-6 event-legend'>
                                <div>
                                    <span class='event event-success'></span><span>$langAgendaSystemEvent</span>
                                </div>
                                <div>
                                    <span class='event event-special'></span><span>$langAgendaPersonalEvent</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>";
}

if ($displayAnnouncements) {
    $tool_content .= "
            <div class='col-md-$cunits_sidebar_subcolumns'>
                <h3 class='content-title'>$langAnnouncements</h3>
                <div class='panel'>
                    <div class='panel-body'>
                        <ul class='tablelist'>" . course_announcements() . "</ul>
                    </div>
                    <div class='panel-footer clearfix'>
                        <div class='pull-right'>
                            <a href='{$urlAppend}modules/announcements/?course=$course_code'><small>$langMore&hellip;</small></a>
                        </div>
                    </div>
                </div>
            </div>";
}
$tool_content .= "
        </div>
    </div>
</div>";

draw($tool_content, 2, null, $head_content);

/**
 * @brief fetch course announcements
 * @global type $course_id
 * @global type $course_code
 * @global type $langNoAnnounce
 * @global type $urlAppend
 * @global type $dateFormatLong
 * @return string
 */
function course_announcements() {
    global $course_id, $course_code, $langNoAnnounce, $urlAppend, $dateFormatLong;

    if (visible_module(MODULE_ID_ANNOUNCE)) {
        $q = Database::get()->queryArray("SELECT title, `date`, id
                            FROM announcement
                            WHERE course_id = ?d
                                AND visible = 1
                                AND (start_display <= NOW() OR start_display IS NULL)
                                AND (stop_display >= NOW() OR stop_display IS NULL)
                            ORDER BY `date` DESC LIMIT 5", $course_id);
        if ($q) { // if announcements exist
            $ann_content = '';
            foreach ($q as $ann) {
                $ann_url = $urlAppend . "modules/announcements/?course=$course_code&amp;an_id=" . $ann->id;
                $ann_date = claro_format_locale_date($dateFormatLong, strtotime($ann->date));
                $ann_content .= "<li class='list-item'>
                                    <span class='item-wholeline'><div class='text-title'><a href='$ann_url'>" . q(ellipsize($ann->title, 60)) ."</a></div>$ann_date</span>
                                </li>";
            }
            return $ann_content;
        }
    }
    return "<li class='list-item'><span class='item-wholeline'><div class='text-title not_visible'> - $langNoAnnounce - </div></span></li>";
}
