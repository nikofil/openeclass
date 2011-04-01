<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/

/*===========================================================================
	questionnaire.php
	@last update: 17-4-2006 by Costas Tsibanis
	@authors list: Dionysios G. Synodinos <synodinos@gmail.com>
==============================================================================
        @Description: Main script for the questionnaire tool
==============================================================================
*/

$require_login = TRUE;
$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Questionnaire';
include '../../include/baseTheme.php';

/**** The following is added for statistics purposes ***/
include('../../include/action.php');
$action = new action();
$action->record('MODULE_ID_QUESTIONNAIRE');
/**************************************/

$nameTools = $langQuestionnaire;
$head_content = '
<script>
function confirmation ()
{
    if (confirm("'.$langConfirmDelete.'"))
        {return true;}
    else
        {return false;}
}
</script>
';

// activate / dectivate polls
if (isset($_GET['visibility'])) {
		switch ($_GET['visibility']) {
		case 'activate':
			$sql = "UPDATE poll SET active='1' WHERE pid='".mysql_real_escape_string($_GET['pid'])."'";
			$result = db_query($sql,$currentCourseID);
			$GLOBALS["tool_content"] .= "".$GLOBALS["langPollActivated"]."<br>";
			break;
		case 'deactivate':
			$sql = "UPDATE poll SET active='0' WHERE pid='".mysql_real_escape_string($_GET['pid'])."'";
			$result = db_query($sql, $currentCourseID);
			$GLOBALS["tool_content"] .= "".$GLOBALS["langPollDeactivated"]."<br>";
			break;
		}
}


// delete polls
if (isset($_GET['delete']) and $_GET['delete'] == 'yes')  {
	$pid = intval($_GET['pid']);
	db_query("DELETE FROM poll_question_answer WHERE pqid IN
		(SELECT pqid FROM poll_question WHERE pid=$pid)");
	db_query("DELETE FROM poll WHERE pid=$pid");
	db_query("DELETE FROM poll_question WHERE pid='$pid'");
	db_query("DELETE FROM poll_answer_record WHERE pid='$pid'");
    $tool_content .= "<p class='success'>".$langPollDeleted."<br /><a href=\"questionnaire.php?course=$code_cours\">".$langBack."</a></p>";
	draw($tool_content, 2, '', $head_content);
	exit();
}

if ($is_adminOfCourse) {
	$tool_content .= "
        <div id=\"operations_container\">
	  <ul id=\"opslist\">
	    <li><a href='addpoll.php?course=$code_cours'>$langCreatePoll</a></li>
	  </ul>
	</div>";
}

printPolls();
add_units_navigation(TRUE);
draw($tool_content, 2, '', $head_content);


 /***************************************************************************************************
 * printPolls()
 ****************************************************************************************************/
function printPolls() {
global $tool_content, $currentCourse, $code_cours, $langCreatePoll, $langPollsActive,
	$langTitle, $langPollCreator, $langPollCreation, $langPollStart,
	$langPollEnd, $langPollNone, $is_adminOfCourse,
	$mysqlMainDb, $langEdit, $langDelete, $langActions,
	$langDeactivate, $langPollsInactive, $langPollHasEnded, $langActivate, $langParticipate, $langVisible,
	$user_id, $langHasParticipated, $langHasNotParticipated, $uid, $urlServer;

		$poll_check = 0;
		$result = db_query("SHOW TABLES FROM `$currentCourse`", $currentCourse);
		while ($row = mysql_fetch_row($result)) {
				if ($row[0] == 'poll') {
				$result = db_query("SELECT * FROM poll", $currentCourse);
					$num_rows = mysql_num_rows($result);
					if ($num_rows > 0)
						++$poll_check;
				}
		}
		if (!$poll_check) {
			$tool_content .= "\n    <p class='alert1'>".$langPollNone . "</p><br>";
		} else {
			// Print active polls
				$tool_content .= "
		      <table align='left' width='100%' class='tbl_alt'>
		      <tr>
			<th colspan='2'><div align='left'>&nbsp;$langTitle</div></th>
			<th width='150' class='center'>$langPollCreator</th>
			<th width='120' class='center'>$langPollCreation</th>
			<th width='70' class='center'>$langPollStart</th>
			<th width='70' class='center'>$langPollEnd</th>";
		
			if ($is_adminOfCourse) {
				$tool_content .= "
                        <th width=\"70\">$langActions</th>";
			} else {
				$tool_content .= "
                        <th>$langParticipate</th>";
			}
			$tool_content .= "
                      </tr>";
			$active_polls = db_query("SELECT * FROM poll", $currentCourse);
			$index_aa = 1;
			$k =0;
				while ($thepoll = mysql_fetch_array($active_polls)) {
					$visibility = $thepoll["active"];
		
				if (($visibility) or ($is_adminOfCourse)) {
					if ($visibility) {
						$visibility_css = " class=\"even\"";
						$visibility_gif = "visible";
						$visibility_func = "deactivate";
						$arrow_png = "arrow";
						$k++;
					} else {
						$visibility_css = " class=\"invisible\"";
						$visibility_gif = "invisible";
						$visibility_func = "activate";
						$arrow_png = "arrow";
						$k++;
					}
					if ($k%2 == 0) {
						$tool_content .= "
                      <tr $visibility_css>";
					} else {
						$tool_content .= "
                      <tr class=\"even\">";
					}			
					$temp_CurrentDate = date("Y-m-d");
					$temp_StartDate = $thepoll["start_date"];
					$temp_EndDate = $thepoll["end_date"];
					$temp_StartDate = mktime(0, 0, 0, substr($temp_StartDate, 5,2), substr($temp_StartDate, 8,2),substr($temp_StartDate, 0,4));
					$temp_EndDate = mktime(0, 0, 0, substr($temp_EndDate, 5,2), substr($temp_EndDate, 8,2), substr($temp_EndDate, 0,4));
					$temp_CurrentDate = mktime(0, 0 , 0,substr($temp_CurrentDate, 5,2), substr($temp_CurrentDate, 8,2),substr($temp_CurrentDate, 0,4));
					$creator_id = $thepoll["creator_id"];
					$theCreator = uid_to_name($creator_id);
					$pid = $thepoll["pid"];
					$answers = db_query("SELECT * FROM poll_answer_record WHERE pid='$pid'", $currentCourse);
					$countAnswers = mysql_num_rows($answers);
					$thepid = $thepoll["pid"];
					// check if user has participated
					$has_participated = mysql_fetch_array(mysql_query("SELECT COUNT(*) FROM poll_answer_record
							WHERE user_id='$uid' AND pid='$thepid'"));
					// check if poll has ended
					if (($temp_CurrentDate >= $temp_StartDate) && ($temp_CurrentDate < $temp_EndDate)) {
						$poll_ended = 0;
					} else {
						$poll_ended = 1;
					}
					if ($is_adminOfCourse) {
						$tool_content .= "
                        <td width='16'><img src='${urlServer}/template/classic/img/$arrow_png.png' title='bullet' /></td>
                        <td><a href='pollresults.php?course=$code_cours&amp;pid=$pid'>$thepoll[name]</a>";
					} else {
						$tool_content .= "
                        <td><img style='border:0px; padding-top:3px;' src='${urlServer}/template/classic/img/arrow.png' title='bullet' /></td>
                        <td>";
						if (($has_participated[0] == 0) and $poll_ended == 0) {
							$tool_content .= "<a href='pollparticipate.php?course=$code_cours&amp;UseCase=1&pid=$pid'>$thepoll[name]</a>";
						} else {
						       $tool_content .= "$thepoll[name]";
						}
					}
					$tool_content .= "</td>
                        <td class='center'>$theCreator</td>";
					$tool_content .= "
                        <td class='center'>".nice_format(date("Y-m-d", strtotime($thepoll["creation_date"])))."</td>";
					$tool_content .= "
                        <td class='center'>".nice_format(date("Y-m-d", strtotime($thepoll["start_date"])))."</td>";
					$tool_content .= "
                        <td class='center'>".nice_format(date("Y-m-d", strtotime($thepoll["end_date"])))."</td>";
					if ($is_adminOfCourse)  {
						$tool_content .= "
                        <td class='center'><a href='addpoll.php?course=$code_cours&amp;edit=yes&pid=$pid'><img src='../../template/classic/img/edit.png' title='$langEdit' border='0' /></a>&nbsp;<a href='$_SERVER[PHP_SELF]?course=$code_cours&amp;delete=yes&pid=$pid' onClick='return confirmation();'><img src='../../template/classic/img/delete.png' title='$langDelete' border='0' /></a>&nbsp;<a href='$_SERVER[PHP_SELF]?course=$code_cours&amp;visibility=$visibility_func&pid={$pid}'><img src='../../template/classic/img/".$visibility_gif.".gif' border='0' title=\"".$langVisible."\" /></a></td>
                      </tr>";
					} else {
						$tool_content .= "
                        <td class='center'>";
						if (($has_participated[0] == 0) and ($poll_ended == 0)) {
							$tool_content .= "$langHasNotParticipated";
						} else {
							if ($poll_ended == 1) {
								$tool_content .= $langPollHasEnded;
							} else {
								$tool_content .= $langHasParticipated;
							}
						}
						$tool_content .= "</td>
                      </tr>";
					}
				}
				$index_aa ++;
				}
				$tool_content .= "
                      </table>";
			}
}
?>
