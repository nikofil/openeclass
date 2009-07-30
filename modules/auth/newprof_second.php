<?php
/*========================================================================
*   Open eClass 2.1
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2008  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*                       Yannis Exidaridis <jexi@noc.uoa.gr>
*                       Alexandros Diamantidis <adia@noc.uoa.gr>
*                       Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address:     GUnet Asynchronous eLearning Group,
*                       Network Operations Center, University of Athens,
*                       Panepistimiopolis Ilissia, 15784, Athens, Greece
*                       eMail: info@openeclass.org
* =========================================================================*/


include '../../include/baseTheme.php';
include('../../include/sendMail.inc.php');
require_once 'auth.inc.php';
$nameTools = $langReqRegProf;

// Initialise $tool_content
$tool_content = "";
// Main body

$statut=1;

$submit = isset($_POST['submit'])?$_POST['submit']:'';
$uname = preg_replace('/\ +/', ' ', trim(isset($_POST['uname'])?$_POST['uname']:''));
$password = isset($_POST['password'])?$_POST['password']:'';
$email_form = isset($_POST['email_form'])?$_POST['email_form']:'';
$nom_form = isset($_POST['nom_form'])?$_POST['nom_form']:'';
$prenom_form = isset($_POST['prenom_form'])?$_POST['prenom_form']:'';
$usercomment = isset($_POST['usercomment'])?$_POST['usercomment']:'';
$department = isset($_POST['department'])?$_POST['department']:'';
$userphone = isset($_POST['userphone'])?$_POST['userphone']:'';

if(!empty($submit)) 
{
  if ((strstr($password, "'")) or (strstr($password, '"')) or (strstr($password, '\\')) 
		or (strstr($uname, "'")) or (strstr($uname, '"')) or (strstr($uname, '\\')) )
	{
		$tool_content .= "
    <table width=\"99%\">
    <tbody>
    <tr>
      <td class=\"caution\" height='60'>
      <p>$langCharactersNotAllowed</p>
      <p><a href=\"javascript:history.go(-1)\">$langAgain</a></p>
      </td>
    </tr>
    </tbody>
    </table>";
	}
	else	// do the other checks
	{
		// check if user name exists
		$q1 = "SELECT username FROM `$mysqlMainDb`.user WHERE username='".escapeSimple($uname)."'";
		$username_check=mysql_query($q1);
		while ($myusername = mysql_fetch_array($username_check)) 
		{
			$user_exist=$myusername[0];
		}
	
		// check if passwd is too easy
		if ((strtoupper($password) == strtoupper($uname)) || (strtoupper($password) == strtoupper($nom_form))
			|| (strtoupper($password) == strtoupper($prenom_form))) 
		{
				$tool_content .= "
    <table width=\"99%\">
    <tbody>
    <tr>
      <td class=\"caution\" height='60'>
      <p>$langPassTooEasy: <strong>".substr(md5(date("Bis").$_SERVER['REMOTE_ADDR']),0,8)."</strong></p>
      <p><a href=\"javascript:history.go(-1)\">$langAgain</a></p>
      </td>
    </tr>
    </tbody>
    </table>";
		}
		// check if there are empty fields
		elseif (empty($nom_form) or empty($prenom_form) or empty($password) or empty($usercomment) or empty($department) or empty($uname) or (empty($email_form))) 
		{
			$tool_content .= "
    <table width=\"99%\">
    <tbody>
    <tr>
      <td class=\"caution\" height='60'>
      <p>$langEmptyFields</p>
    <p><a href=\"javascript:history.go(-1)\">$langAgain</a></p>
    </td>
    </tr>
    </tbody>
    </table>";
		}
		elseif(isset($user_exist) and $uname==$user_exist) 
		{
			$tool_content .= "
    <table width=\"99%\">
    <tbody>
    <tr>
      <td class=\"caution\" height='60'>
      <p>$langUserFree</p>
      <p><a href=\"javascript:history.go(-1)\">$langAgain</a></p>
      </td>
    </tr>
    </tbody>
    </table>";
	  }
		elseif(!email_seems_valid($email_form)) // check if email syntax is valid
		{
	        $tool_content .= "
    <table width=\"99%\">
    <tbody>
    <tr>
      <td class=\"caution\" height='60'>
      <p>$langEmailWrong</p>
      <p><a href=\"javascript:history.go(-1)\">$langAgain</a></p>
      </td>
    </tr>
    </tbody>
    </table>";
		}
		else 		// registration is ok
		{
			$uname = escapeSimple($uname);	// escape the characters: simple and double quote
			// ------------------- Update table prof_request ------------------------------
			$username = $uname;
			$auth = $_POST['auth'];
			if($auth!=1)
			{
				switch($auth)
				{
					case '2': $password = "pop3";
						break;
					case '3': $password = "imap";
						break;
					case '4':	$password = "ldap";
						break;
					case '5': $password = "db";
						break;
					default:	$password = "";
						break;
				}
			}
			
			$usermail = $email_form;
			$surname = $nom_form;
			$name = $prenom_form;
			
			mysql_select_db($mysqlMainDb,$db);
			$sql = "INSERT INTO prof_request(profname,profsurname,profuname,profpassword,
			profemail,proftmima,profcomm,status,date_open,comment) VALUES(
			'$name','$surname','$username','$password','$usermail','$department','$userphone','1',NOW(),'$usercomment')";
			$upd=mysql_query($sql,$db);
			//----------------------------- Email Message --------------------------
		    $MailMessage = $mailbody1 . $mailbody2 . "$name $surname\n\n" . $mailbody3 
		    . $mailbody4 . $mailbody5 . "$mailbody6\n\n" . "$langDepartment: $department\n$langComments: $usercomment\n" 
		    . "$langProfUname : $username\n$langProfEmail : $usermail\n" . "$contactphone : $userphone\n\n\n$logo\n\n";
		if (!send_mail('', '', $gunet, $emailhelpdesk, $mailsubject, $MailMessage, $charset)) 
			{
				$tool_content .= "
    <table width=\"99%\">
    <tbody>
    <tr>
      <td class=\"caution\" height='60'>
      <p>$langMailErrorMessage &nbsp; <a href=\"mailto:$emailhelpdesk\">$emailhelpdesk</a></p>
      </td>
    </tr>
    </tbody>
    </table>";
				draw($tool_content,0);
				exit();
			}
	
			//------------------------------------User Message ----------------------------------------
		$tool_content .= " <table width=\"99%\"><tbody>
  		<tr>
      <td class=\"well-done\" height='60'>
      <p>$langDearProf</p><p>$success</p><p>$infoprof</p>
      <p><a href=\"$urlServer\">$langBack</a></p>
    </td>
    </tr></tbody></table>";
		} 
	}
}
else 
{
	$tool_content .= "<br />$langRegistrationError<br>";
}
draw($tool_content,0);
?>
