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

/**
 * @file messageList.php
 * @brief Functionality of chat module
 */
$require_current_course = TRUE;
$require_login = TRUE;
require_once '../../include/baseTheme.php';
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
    <base target="_parent">
    <meta http-equiv="refresh" content="30; url=<?php echo $_SERVER['SCRIPT_NAME']; ?>" />
    <title>Chat messages</title>
    <!-- jQuery -->
    <script type="text/javascript" src="<?php echo $urlServer;?>/js/jquery-2.1.1.min.js"></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="<?php echo $urlServer;?>/template/default/js/bootstrap.min.js"></script>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="<?php echo $urlServer;?>/template/default/CSS/bootstrap-custom.css">
        
    <!-- Optional theme -->
    <link rel="stylesheet" href="<?php echo $urlServer;?>/template/default/CSS/bootstrap-theme.min.css">
    
    <link rel="stylesheet" href="<?php echo $urlServer;?>/template/default/CSS/inventory.css">
        
    <style type="text/css">
        span { color: #727266; font-size: 11px; }
        div { font-size: 12px; }
        body { font-family: Verdana, Arial, Helvetica, sans-serif; }
    </style>
</head>
<body>
    <?php
    require_once 'include/lib/textLib.inc.php';

    $coursePath = $webDir . '/courses/';
    $fileChatName = $coursePath . $course_code . '/chat.txt';
    $tmpArchiveFile = $coursePath . $course_code . '/tmpChatArchive.txt';

    $nick = uid_to_name($uid);

// How many lines to show on screen
    define('MESSAGE_LINE_NB', 20);
// How many lines to keep in temporary archive
// (the rest are in the current chat file)
    define('MAX_LINE_IN_FILE', 20);

    if ($GLOBALS['language'] == 'el') {
        $timeNow = date("d-m-Y / H:i", time());
    } else {
        $timeNow = date("Y-m-d / H:i", time());
    }

    if (!file_exists($fileChatName)) {
        $fp = fopen($fileChatName, 'w') or die('<center>$langChatError</center>');
        fclose($fp);
    }

// chat commands
// reset command
    if (isset($_GET['reset']) && $is_editor) {        
        $fchat = fopen($fileChatName, 'w');

        if (flock($fchat, LOCK_EX)) {
            ftruncate($fchat, 0);
            fwrite($fchat, $timeNow . " ---- " . $langWashFrom . " ---- " . $nick . " -------- !@#$ systemMsg\n");
            fflush($fchat);
            flock($fchat, LOCK_UN);
        }
        fclose($fchat);
        @unlink($tmpArchiveFile);
        redirect_to_home_page("modules/conference/messageList.php?course=$course_code");
    }

// store
    if (isset($_GET['store']) && $is_editor) {
        require_once 'modules/document/doc_init.php';
        $saveIn = "chat." . date("Y-m-j-his") . ".txt";
        $chat_filename = '/' . safe_filename('txt');
        
        //Concat temp & chat file 
        $exportFileChat = $coursePath . $course_code . '/chat_export.txt';
        $fp = fopen($exportFileChat, 'a+');        
        $tmp_file = @file_get_contents($tmpArchiveFile);
        $tmp_file = preg_replace('/!@#\$.*/', '', $tmp_file);
        $chat_file = @file_get_contents($fileChatName);
        $chat_file = preg_replace('/!@#\$.*/', '', $chat_file);
        fwrite($fp, strip_tags($tmp_file.$chat_file));
        fclose($fp);
        
        if (copy($exportFileChat, $basedir . $chat_filename)) {            
            Database::get()->query("INSERT INTO document SET
                                course_id = ?d,
                                subsystem = ?d,
                                path = ?s,
                                filename = ?s,
                                format='txt',
                                date = NOW(),
                                date_modified = NOW()", $course_id, $subsystem, $chat_filename, $saveIn);

            $alert_div = "<div class='row margin-right-thin margin-left-thin margin-top-thin'><div class='col-xs-12'><div class='alert alert-info'>$langSaveMessage</div></div></div>";
        } else {
            $alert_div = "<div class='row margin-right-thin margin-left-thin margin-top-thin'><div class='col-xs-12'><div class='alert alert-danger'>$langSaveErrorMessage</div></div></div>";
        }
        @unlink($exportFileChat);
        echo $alert_div."</body></html>\n";
        exit;
    }
  
// add new line
    if (isset($_GET['chatLine']) and trim($_GET['chatLine']) != '') {
        $chatLine = purify($_GET['chatLine']);
        $fchat = fopen($fileChatName, 'a');
        if ($is_editor) {
            $nick = "<b>$nick</b>";
        }
        fwrite($fchat, $timeNow . ' - ' . $nick . ' : ' . stripslashes($chatLine) . " !@#$ $uid       \n");
        fclose($fchat);
        redirect_to_home_page("modules/conference/messageList.php?course=$course_code");
    }

    // display message list
    $fileContent = file($fileChatName);
    $FileNbLine = count($fileContent);
    $lineToRemove = $FileNbLine - MESSAGE_LINE_NB;
    if ($lineToRemove < 0) {
        $lineToRemove = 0;
    }
    $tmp = array_splice($fileContent, 0, $lineToRemove);
    
    $fileReverse = array_reverse($fileContent);    
    foreach ($fileReverse as $thisLine) {
        $thisLine = preg_replace_callback('/\[m\].*?\[\/m\]/s', 'math_unescape', $thisLine);
        $newline = mathfilter($thisLine, 12, '../../courses/mathimg/');
        $str_1 = explode(' !@#$ ', $newline);

        if (trim($str_1[1]) == "systemMsg") {
            echo "<div class='row margin-right-thin margin-left-thin margin-top-thin'>
                        <div class='col-xs-12'>
                            <div class='alert alert-success text-center'>
                                $str_1[0]
                            </div>
                        </div>
                  </div>\n";        
        } else {
            $user_id = (int) trim($str_1[1]);
            $str_2 = explode(' - ', $str_1[0]);
            $datetime = $str_2[0];
            $str_3 = explode(' : ', $str_2[1]);
            $username = $str_3[0];
            $usertext = $str_3[1];
            $token = token_generate($user_id, true);
            echo "<div class='row margin-right-thin margin-left-thin margin-top-thin'>
                        <div class='col-xs-12'>
                            <div class='media'>
                                <a class='media-left' href='{$urlServer}main/profile/display_profile.php?id=$user_id&token=$token'>
                                    ". profile_image($user_id, IMAGESIZE_SMALL) ."
                                </a>
                                <div class='media-body bubble'>
                                    <div class='label label-success media-heading'>$datetime</div>
                                    <small>$langBlogPostUser ". display_user($user_id, false, false) ."</small>    
                                    <div class='margin-top-thin'>
                                        " . $usertext . "
                                    </div>
                                </div>    
                            </div>
                        </div>
                  </div>\n";
        }
    }
    echo "</body></html>\n";


    /*
     * For performance reason, buffer the content
     * in a temporary archive file
     * once the chat file is too large
     */

    if ($FileNbLine > MAX_LINE_IN_FILE) {
        buffer(implode('', $tmp), $tmpArchiveFile);
        // clean the original file
        $fp = fopen($fileChatName, "w");
        fwrite($fp, implode('', $fileContent));
    }

    function buffer($content, $tmpFile) {
        $fp = fopen($tmpFile, "a");
        fwrite($fp, $content);
    }
    
