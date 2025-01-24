<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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


/* ===========================================================================
  insertMyDoc.php
  @last update: 30-06-2006 by Thanos Kyritsis
  @authors list: Thanos Kyritsis <atkyritsis@upnet.gr>

  based on Claroline version 1.7 licensed under GPL
  copyright (c) 2001, 2006 Universite catholique de Louvain (UCL)

  original file: insertMyDoc.php Revision: 1.18.2.1

  Claroline authors: Piraux Sebastien <pir@cerdecam.be>
  Lederer Guillaume <led@cerdecam.be>
  ==============================================================================
  @Description: This script lists all available documents and the course
  admin can add them to a learning path

  @Comments:

  @todo:
  ==============================================================================
 */

$require_current_course = TRUE;
$require_editor = TRUE;

include '../../include/baseTheme.php';
require_once 'include/lib/learnPathLib.inc.php';
require_once 'include/lib/fileDisplayLib.inc.php';
require_once 'include/lib/fileManageLib.inc.php';
require_once 'include/lib/textLib.inc.php';
require_once 'modules/document/doc_init.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';

doc_init();
ModalBoxHelper::loadModalBox(true);
$head_content .= <<<EOF
<script type='text/javascript'>
$(document).ready(function() {

    $('tr').click(function(event) {
        if (event.target.type !== 'checkbox') {
            $(':checkbox', this).trigger('click');
        }
    });

});
</script>
EOF;

$pwd = getcwd();

$courseDir = "/courses/" . $course_code . "/document";
$baseWorkDir = $webDir . $courseDir;
$InfoBox = '';
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langLearningPath);
$navigation[] = array('url' => "learningPathAdmin.php?course=$course_code&amp;path_id=" . (int) $_SESSION['path_id'], 'name' => $langAdm);
$toolName = $langInsertMyDocToolName;

$tool_content .= 
         action_bar(array(
            array('title' => $langBack,
                'url' => "learningPathAdmin.php?course=$course_code&amp;path_id=" . (int) $_SESSION['path_id'],
                'icon' => 'fa-reply',
                'level' => 'primary-label'))) ;

// FUNCTION NEEDED TO BUILD THE QUERY TO SELECT THE MODULES THAT MUST BE AVAILABLE
// 1)  We select first the modules that must not be displayed because
// as they are already in this learning path

function buildRequestModules() {
    global $course_id;

    $firstSql = "SELECT `module_id` FROM `lp_rel_learnPath_module` AS LPM
              WHERE LPM.`learnPath_id` = ?d";

    $firstResult = Database::get()->queryArray($firstSql, $_SESSION['path_id']);
    
    // 2) We build the request to get the modules we need
    $sql = "SELECT M.*
         FROM `lp_module` AS M
         WHERE 1 = 1 AND M.`course_id` = " . intval($course_id);

    foreach ($firstResult as $list) {
        $sql .=" AND M.`module_id` != " . intval($list->module_id);
    }
    return $sql;
}

// -------------------------- documents list ----------------
// evaluate how many form could be sent
if (!isset($dialogBox)) {
    $dialogBox = '';
}
if (!isset($style)) {
    $style = '';
}

if (isset($_POST['submitInsertedDocument'])) {
    foreach ($_POST['document'] as $file_id) {
        $sql_doc = Database::get()->querySingle("SELECT filename, path FROM document WHERE id = ?d", $file_id);
        $filenameDocument = $sql_doc->filename;
        $sourceDoc = $sql_doc->path;        
        $basename = $webDir . '/courses/' . $course_code . '/document' . $sourceDoc;        
        if (file_exists($basename)) { // source file exists ?
            // check if a module of this course already used the same document
            $sql = "SELECT *
                    FROM `lp_module` AS M, `lp_asset` AS A
                    WHERE A.`module_id` = M.`module_id`
                      AND A.`path` LIKE ?s
                      AND M.`contentType` = ?s
                      AND M.`course_id` = ?d";
            $thisDocumentModule = Database::get()->querySingle($sql, $sourceDoc, CTDOCUMENT_, $course_id);
            if (!$thisDocumentModule) {
                
                // create new module
                $insertedModule_id = Database::get()->query("INSERT INTO `lp_module`
                        (`course_id`, `name` , `comment`, `contentType`, `launch_data`)
                        VALUES (?d, ?s, ?s, ?s, '')", $course_id, $filenameDocument, $langDefaultModuleComment, CTDOCUMENT_)->lastInsertID;

                // create new asset
                $insertedAsset_id = Database::get()->query("INSERT INTO `lp_asset`
                        (`path` , `module_id` , `comment`)
                        VALUES (?s, ?d, '')", $sourceDoc, $insertedModule_id)->lastInsertID;

                Database::get()->query("UPDATE `lp_module`
                        SET `startAsset_id` = ?d
                        WHERE `module_id` = ?d
                        AND `course_id` = ?d", $insertedAsset_id, $insertedModule_id, $course_id);

                // determine the default order of this Learning path
                $order = 1 + intval(Database::get()->querySingle("SELECT MAX(`rank`) AS max
                        FROM `lp_rel_learnPath_module`
                        WHERE `learnPath_id` = ?d", $_SESSION['path_id'])->max);

                // finally : insert in learning path
                Database::get()->query("INSERT INTO `lp_rel_learnPath_module`
                        (`learnPath_id`, `module_id`, `specificComment`, `rank`, `lock`, `visible`)
                        VALUES (?d, ?d, ?s, ?d, 'OPEN', 1)", $_SESSION['path_id'], $insertedModule_id, $langDefaultModuleAddedComment, $order);               
                Session::Messages($langInsertedAsModule, 'alert-info');               
            } else {                
                // check if this is this LP that used this document as a module
                $sql = "SELECT COUNT(*) AS count FROM `lp_rel_learnPath_module` AS LPM,
                             `lp_module` AS M,
                             `lp_asset` AS A
                        WHERE M.`module_id` =  LPM.`module_id`
                          AND M.`startAsset_id` = A.`asset_id`
                          AND A.`path` = ?s
                          AND LPM.`learnPath_id` = ?d
                          AND M.`course_id` = ?d";
                $num = Database::get()->querySingle($sql, $sourceDoc, $_SESSION['path_id'], $course_id)->count;

                if ($num == 0) { // used in another LP but not in this one, so reuse the module id reference instead of creating a new one
                    // determine the default order of this Learning path
                    $order = 1 + intval(Database::get()->querySingle("SELECT MAX(`rank`) AS max
                            FROM `lp_rel_learnPath_module`
                            WHERE `learnPath_id` = ?d", $_SESSION['path_id'])->max);

                    // finally : insert in learning path
                    Database::get()->query("INSERT INTO `lp_rel_learnPath_module`
                            (`learnPath_id`, `module_id`, `specificComment`, `rank`,`lock`, `visible`)
                            VALUES (?d, ?d, ?s, ?d, 'OPEN', 1)", $_SESSION['path_id'], $thisDocumentModule->module_id, $langDefaultModuleAddedComment, $order);

                    Session::Messages($langInsertedAsModule, 'alert-info');
                } else {
                    Session::Messages($langAlreadyUsed, 'alert-warning');
                }
            }
        }
    }
    redirect_to_home_page('modules/learnPath/learningPathAdmin.php?course=' . $course_code);
}


/* ======================================
  DEFINE CURRENT DIRECTORY
  ====================================== */

if (isset($_REQUEST['openDir'])) { // $newDirPath is from createDir command (step 2) and $uploadPath from upload command
    $curDirPath = $_REQUEST['openDir'];
} else {
    $curDirPath = '';
}

if ($curDirPath == '/' or $curDirPath == '\\' or strstr($curDirPath, '..')) {
    $curDirPath = ''; // manage the root directory problem
}

$parentDir = dirname($curDirPath);

if ($parentDir == '/' or $parentDir == '\\') {
    $parentDir = ''; // manage the root directory problem
}

/* ======================================
  READ CURRENT DIRECTORY CONTENT
  ====================================== */
/* Search infos in the DB about the current directory the user is in */
$result = Database::get()->queryArray("SELECT * FROM document
                 WHERE $group_sql AND
                       path LIKE ?s AND
                       path NOT LIKE ?s",
            $curDirPath . '/%', $curDirPath . '/%/%');

$attribute = array();
$fileList = array();

foreach ($result as $row) {
    $attribute['path'][] = $row->path;
    $attribute['visible'][] = $row->visible;
    $attribute['comment'][] = $row->comment;
    $attribute['filename'][] = $row->filename;
    $attribute['id'][] = $row->id;
}
/* --------------------------------------
  LOAD FILES AND DIRECTORIES INTO ARRAYS
  -------------------------------------- */
chdir(realpath($baseWorkDir . $curDirPath));
$handle = opendir(".");

define('A_DIRECTORY', 1);
define('A_FILE', 2);

while ($file = readdir($handle)) {
    if ($file == '.' || $file == '..') {
        continue; // Skip current and parent directories
    }

    $fileList['name'][] = $file;
    
    if (is_dir($file)) {
        $fileList['type'][] = A_DIRECTORY;
        $fileList['size'][] = false;
        $fileList['date'][] = false;
    } elseif (is_file($file)) {
        $fileList['type'][] = A_FILE;
        $fileList['size'][] = filesize($file);
        $fileList['date'][] = date('Y-m-d', filectime($file));
    }    
    /*
     * Make the correspondance between
     * info given by the file system
     * and info given by the DB
     */
    if (!isset($dirNameList)) {
        $dirNameList = array();
    }
    $keyDir = sizeof($dirNameList) - 1;

    if (isset($attribute)) {
        if (isset($attribute['path'])) {
            $keyAttribute = array_search($curDirPath . "/" . $file, $attribute['path']);
        } else {
            $keyAttribute = false;
        }
    }

    if ($keyAttribute !== false) {        
        $fileList['comment'][] = $attribute['comment'][$keyAttribute];
        $fileList['visible'][] = $attribute['visible'][$keyAttribute];
        $fileList['filename'][] = $attribute['filename'][$keyAttribute];
        $fileList['path'][] = $attribute['path'][$keyAttribute];
        $fileList['id'][] = $attribute['id'][$keyAttribute];
    } else {
        $fileList['comment'][] = false;
        $fileList['visible'][] = false;
        $fileList['filename'][] = false;                
    }
} // end while ($file = readdir($handle))

closedir($handle);
unset($attribute);

// display list of available documents
$tool_content .= display_my_documents($dialogBox, $style);

chdir($pwd);
draw($tool_content, 2, null, $head_content);