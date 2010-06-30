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
document.php
 * @version $Id$
@last update: 20-12-2006 by Evelthon Prodromou
@authors list: Agorastos Sakis <th_agorastos@hotmail.com>
*/

$require_current_course = TRUE;
$guest_allowed = true;

include '../../include/baseTheme.php';
include '../../include/lib/forcedownload.php';
include "../../include/lib/fileDisplayLib.inc.php";
include "../../include/lib/fileManageLib.inc.php";
include "../../include/lib/fileUploadLib.inc.php";

/**** The following is added for statistics purposes ***/
include('../../include/action.php');
$action = new action();
$action->record('MODULE_ID_DOCS');
/**************************************/

$tool_content = "";
$nameTools = $langDoc;
$dbTable = 'document';

$require_help = TRUE;
$helpTopic = 'Doc';

// check for quotas
mysql_select_db($mysqlMainDb);
$d = mysql_fetch_row(mysql_query("SELECT doc_quota FROM cours WHERE code='$currentCourseID'"));
$diskQuotaDocument = $d[0];
mysql_select_db($currentCourseID);

$basedir = $webDir . 'courses/' . $currentCourseID . '/document';
$diskUsed = dir_total_space($basedir);
if (isset($_GET['showQuota'])) {
	$nameTools = $langQuotaBar;
	$navigation[] = array ("url"=>"document.php", "name"=> $langDoc);
	$tool_content .= showquota($diskQuotaDocument, $diskUsed);
	draw($tool_content, 2);
	exit;
}

// -------------------------
// download action2
// --------------------------
if (@$action2=="download")
{
	$real_file = $basedir . $id;
	if (strpos($real_file, '/../') === FALSE) {
		//fortwma tou pragmatikou onomatos tou arxeiou pou vrisketai apothikevmeno sth vash
		$result = mysql_query ("SELECT filename FROM document WHERE path LIKE '%$id%'");
		$row = mysql_fetch_array($result);
		if (!empty($row['filename']))
		{
			$id = $row['filename'];
		}
		send_file_to_client($real_file, my_basename($id));
		exit;
	} else {
		header("Refresh: ${urlServer}modules/document/document.php");
	}
}


if($is_adminOfCourse)  {
	if (@$uncompress == 1)
		include("../../include/pclzip/pclzip.lib.php");
}

// file manager basic variables definition
$local_head = '
<script type="text/javascript">
function confirmation (name)
{
    if (confirm("'.$langConfirmDelete.'" + name))
        {return true;}
    else
        {return false;}
}
</script>
';


// Actions to do before extracting file from zip archive
// Create database entries and set extracted file path to
// a new safe filename
function process_extracted_file($p_event, &$p_header) {

        global $file_comment, $file_category, $file_creator, $file_date, $file_subject,
               $file_title, $file_description, $file_author, $file_language,
               $file_copyrighted, $uploadPath, $realFileSize, $basedir;

        $realFileSize += $p_header['size'];
        $stored_filename = $p_header['stored_filename'];
        if (invalid_utf8($stored_filename)) {
                $stored_filename = cp737_to_utf8($stored_filename);
        }
        $path_components = explode('/', $stored_filename);
        $filename = array_pop($path_components);
        $file_date = date("Y\-m\-d G\:i\:s", $p_header['mtime']);
        $path = make_path($uploadPath, $path_components);
        if ($p_header['folder']) {
                // Directory has been created by make_path(),
                // no need to do anything else
                return 0;
        } else {
                $format = get_file_extension($filename);
                $path .= '/' . safe_filename($format);
                db_query("INSERT INTO document SET
                                 path = '$path',
                                 filename = " . quote($filename) .",
                                 visibility = 'v',
                                 comment = " . quote($file_comment) . ",
                                 category = " . quote($file_category) . ",
                                 title = " . quote($file_title) . ",
                                 creator = " . quote($file_creator) . ",
                                 date = " . quote($file_date) . ",
                                 date_modified = " . quote($file_date) . ",
                                 subject = " . quote($file_subject) . ",
                                 description = " . quote($file_description) . ",
                                 author = " . quote($file_author) . ",
                                 format = '$format',
                                 language = " . quote($file_language) . ",
                                 copyrighted = " . quote($file_copyrighted));
                // File will be extracted with new encoded filename
                $p_header['filename'] = $basedir . $path;
                return 1;
        }
}


// Create a path with directory names given in array $path_components
// under base path $path, inserting the appropriate entries in 
// document table.
// Returns the full encoded path created.
function make_path($path, $path_components)
{
        global $basedir, $nom, $prenom, $path_already_exists;

        $path_already_exists = true;
        $depth = 1 + substr_count($path, '/');
        foreach ($path_components as $component) {
                $q = db_query("SELECT path, visibility, format,
                                (LENGTH(path) - LENGTH(REPLACE(path, '/', ''))) AS depth
                                FROM document WHERE filename = " . quote($component) .
                                " AND path LIKE '$path%' HAVING depth = $depth");
                if (mysql_num_rows($q) > 0) {
                        // Path component already exists in database
                        $r = mysql_fetch_array($q);
                        $path = $r['path'];
                        $depth++;
                } else {
                        // Path component must be created
                        $path .= '/' . safe_filename();
                        mkdir($basedir . $path, 0775);
                        db_query("INSERT INTO document SET
    				  path='$path',
                                  filename=" . quote($component) . ",
    				  visibility='v',
                                  creator=" . quote($prenom." ".$nom) . ",
                                  date=NOW(),
                                  date_modified=NOW(),
                                  format='.dir'");
                        $path_already_exists = false;
                }
        }
        return $path;
}

/*** clean information submited by the user from antislash ***/
// stripSubmitValue($_POST);
// stripSubmitValue($_GET);
/*****************************************************************************/

if($is_adminOfCourse) {
	/*********************************************************************
	UPLOAD FILE

        Ousiastika dhmiourgei ena safe_fileName xrhsimopoiwntas ta DATETIME
        wste na mhn dhmiourgeitai provlhma sto filesystem apo to onoma tou
        arxeiou. Parola afta to palio filename pernaei apo 'filtrarisma' wste
        na apofefxthoun 'epikyndynoi' xarakthres.
	***********************************************************************/

	$dialogBox = '';
	if (isset($_FILES['userFile']) and is_uploaded_file($_FILES['userFile']['tmp_name'])) {
                $userFile = $_FILES['userFile']['tmp_name'];
		// check for disk quotas
		$diskUsed = dir_total_space($basedir);
		if ($diskUsed + @$_FILES['userFile']['size'] > $diskQuotaDocument) {
			$dialogBox .= "<p class='caution_small'>$langNoSpace</p>";
		} else {
                        // check for dangerous extensions and file types
                        if (preg_match('/\.(ade|adp|bas|bat|chm|cmd|com|cpl|crt|exe|hlp|hta|' .
                        'inf|ins|isp|jse|lnk|mdb|mde|msc|msi|msp|mst|pcd|pif|reg|scr|sct|shs|' .
                        'shb|url|vbe|vbs|wsc|wsf|wsh)$/', $_FILES['userFile']['name'])) {
                                $dialogBox .= "$langUnwantedFiletype: {$_FILES['userFile']['name']}";
                        }
                        /*** Unzipping stage ***/
                        elseif (isset($_POST['uncompress']) and $_POST['uncompress'] == 1
                                and preg_match('/\.zip$/i', $_FILES['userFile']['name'])) {
                                $zipFile = new pclZip($userFile);
                                $realFileSize = 0;
                                $zipFile->extract(PCLZIP_CB_PRE_EXTRACT, 'process_extracted_file');
                                if ($diskUsed + $realFileSize > $diskQuotaDocument) {
                                        $dialogBox .= $langNoSpace;
                                } else {
                                        $dialogBox .= "<p class='success_small'>$langDownloadAndZipEnd</p><br />";
                                }
                        } else {
                                $error = false;
                                $fileName = canonicalize_whitespace($_FILES['userFile']['name']);
                                $uploadPath = $_POST['uploadPath'];
                                // Check if upload path exists
                                if (!empty($uploadPath)) {
                                        $result = mysql_fetch_row(db_query("SELECT count(*) FROM document
                                                                            WHERE path = " . autoquote($uploadPath)));
                                        if (!$result[0]) {
                                                $error = $langImpossible;
                                        }
                                }
                                if (!$error) {
                                        // Check if file already exists
                                        $result = db_query("SELECT filename FROM document WHERE path REGEXP '" .
                                                            escapeSimple($uploadPath) . "/.*$' AND
                                                            filename = " . autoquote($fileName));
                                        if (mysql_num_rows($result) > 0) {
                                                $error = $langFileExists;
                                        }
                                }
                                if (!$error) {
                                        //to arxeio den vrethike sth vash ara mporoume na proxwrhsoume me to upload
                                        /*** Try to add an extension to files witout extension ***/
                                        $fileName = add_ext_on_mime($fileName);
                                        /*** Handle PHP files ***/
                                        $fileName = php2phps($fileName);
                                        // to onoma afto tha xrhsimopoiei sto filesystem kai sto pedio path
                                        $safe_fileName = safe_filename(get_file_extension($fileName));
                                        //prosthiki eggrafhs kai metadedomenwn gia to eggrafo sth vash
                                        if ($uploadPath == ".") {
                                                $uploadPath2 = "/".$safe_fileName;
                                        } else {
                                                $uploadPath2 = $uploadPath."/".$safe_fileName;
                                        }
                                        // san file format vres to extension tou arxeiou
                                        $file_format = get_file_extension($fileName);
                                        // san date you arxeiou xrhsimopoihse thn shmerinh hm/nia
                                        $file_date = date("Y\-m\-d G\:i\:s");
                                        db_query("INSERT INTO $dbTable SET
                                                        path = " . quote($uploadPath2) . ",
                                                        filename = " . autoquote($fileName) . ",
                                                        visibility = 'v',
                                                        comment = " . autoquote($_POST['file_comment']) . ",
                                                        category = " . intval($_POST['file_category']) . ",
                                                        title =	" . autoquote($_POST['file_title']) . ",
                                                        creator	= " . autoquote($_POST['file_creator']) . ",
                                                        date = '$file_date',
                                                        date_modified =	'$file_date',
                                                        subject	= " . autoquote($_POST['file_subject']) . ",
                                                        description = " . autoquote($_POST['file_description']) . ",
                                                        author = " . autoquote($_POST['file_author']) . ",
                                                        format = " . autoquote($file_format) . ",
                                                        language = " . autoquote($_POST['file_language']) . ",
                                                        copyrighted = " . intval($_POST['file_copyrighted']));

                                        /*** Copy the file to the desired destination ***/
                                        copy ($userFile, $basedir.$uploadPath.'/'.$safe_fileName);
                                        $dialogBox .= "<p class='success_small'>$langDownloadEnd</p><br />";
                                } else {
                                        $dialogBox .= "<p class='caution_small'>$error</p><br />";
                                }
                        }
                }
	} // end if is_uploaded_file

	/**************************************
	MOVE FILE OR DIRECTORY
	**************************************/
	/*-------------------------------------
	MOVE FILE OR DIRECTORY : STEP 2
	--------------------------------------*/
        if (isset($_POST['moveTo'])) {
                $moveTo = $_POST['moveTo'];
                $source = $_POST['source'];
		//elegxos ean source kai destintation einai to idio
		if($basedir . $source != $basedir . $moveTo or $basedir . $source != $basedir . $moveTo) {
			if (move($basedir . $source, $basedir . $moveTo)) {
				update_db_info('document', 'update', $source, $moveTo.'/'.my_basename($source));
				$dialogBox = "<p class='success_small'>$langDirMv</p><br />";
			} else {
				$dialogBox = "<p class='caution_small'>$langImpossible</p><br />";
				/*** return to step 1 ***/
				$move = $source;
				unset ($moveTo);
			}
		}
	}

	/*-------------------------------------
	MOVE FILE OR DIRECTORY : STEP 1
	--------------------------------------*/
        if (isset($_GET['move'])) {
                $move = $_GET['move'];
		//h $move periexei to onoma tou arxeiou. anazhthsh onomatos arxeiou sth vash
		$result = mysql_query("SELECT * FROM $dbTable WHERE path=" . autoquote($move));
		$res = mysql_fetch_array($result);
		$moveFileNameAlias = $res['filename'];
		@$dialogBox .= form_dir_list_exclude($dbTable, "source", $move, "moveTo", $basedir, $move);
	}

	/**************************************
	DELETE FILE OR DIRECTORY
	**************************************/
        if (isset($_POST['delete'])) {
                $delete = str_replace('..', '', $_POST['delete']);
		if (my_delete($basedir . $delete)) {
                        update_db_info('document', 'delete', $delete);
			$dialogBox = "<p class='success_small'>$langDocDeleted</p><br />";
		}
	}

	/*****************************************
	RENAME
	******************************************/
	// Step 2: Rename file by updating record in database
	if (isset($_POST['renameTo'])) {
		db_query("UPDATE $dbTable SET filename=" .
                         autoquote(canonicalize_whitespace($_POST['renameTo'])) .
                         " WHERE path=" . autoquote($_POST['sourceFile']));
		$dialogBox = "<p class='caution_small'>$langElRen</p><br />";
	}

	// Step 1: Show rename dialog box
        if (isset($_GET['rename'])) {
		$result = mysql_query("SELECT * FROM $dbTable WHERE path=" . autoquote($_GET['rename']));
		$res = mysql_fetch_array($result);
		$fileName = $res['filename'];
		@$dialogBox .= "<form method='post' action='document.php'>\n";
		$dialogBox .= "<input type='hidden' name='sourceFile' value='$_GET[rename]' />
        	<table class='FormData' width='99%'><tbody><tr>
          	<th class='left' width='200'>$langRename:</th>
          	<td class='left'>$langRename ".q($fileName)." $langIn: <input type='text' name='renameTo' value='$fileName' class='FormData_InputText' size='50' /></td>
          	<td class='left' width='1'><input type='submit' value='$langRename' /></td>
        	</tr></tbody></table></form><br />";
	}

	// create directory
	// step 2: create the new directory
	if (isset($_POST['newDirPath'])) {
                $newDirName = canonicalize_whitespace($_POST['newDirName']);
                if (!empty($newDirName)) {
                        make_path($_POST['newDirPath'], array($newDirName));
                        // $path_already_exists: global variable set by make_path()
                        if ($path_already_exists) {
                                $dialogBox = "<p class='caution_small'>$langFileExists</p>";
                        } else {
                                $dialogBox = "<p class='success_small'>$langDirCr</p>";
                        }
                }
	}

	// step 1: display a field to enter the new dir name
        if (isset($_GET['createDir'])) {
                $createDir = q($_GET['createDir']);
		$dialogBox .= "<form action='document.php' method='post'>\n";
		$dialogBox .= "<input type='hidden' name='newDirPath' value='$createDir' />\n";
		$dialogBox .= "<table class='FormData' width='99%'>
        	<tbody><tr><th class='left' width='200'>$langNameDir:</th>
          	<td class='left' width='1'><input type='text' name='newDirName' class='FormData_InputText' /></td>
          	<td class='left'><input type='submit' value='$langCreateDir' /></td>
  		</tr></tbody></table></form><br />";
	}

	// add/update/remove comment
	// h $commentPath periexei to path tou arxeiou gia to opoio tha epikyrothoun ta metadata
	if (isset($_POST['commentPath'])) {
                $commentPath = $_POST['commentPath'];
		//elegxos ean yparxei eggrafh sth vash gia to arxeio
		$result = db_query("SELECT * FROM $dbTable WHERE path=" . autoquote($commentPath));
		$res = mysql_fetch_array($result);
		if(!empty($res)) {
                        if (!isset($language_codes[$_POST['file_language']])) {
                                $file_language = langname_to_code($language);
                        } else {
                                $file_language = $_POST['file_language'];
                        }
			db_query("UPDATE $dbTable SET
                                                comment = " . autoquote($_POST['file_comment']) . ",
                                                category = " . intval($_POST['file_category']) . ",
                                                title = " . autoquote($_POST['file_title']) . ",
                                                date_modified = NOW(),
                                                subject = " . autoquote($_POST['file_subject']) . ",
                                                description = " . autoquote($_POST['file_description']) . ",
                                                author = " . autoquote($_POST['file_author']) . ",
                                                language = '$file_language',
                                                copyrighted = " . intval($_POST['file_copyrighted']) . "
                                        WHERE path = '$commentPath'");
                }
	}

	// Emfanish ths formas gia tropopoihsh comment
	if (isset($_GET['comment'])) {
                $comment = $_GET['comment'];
		$oldComment='';
		/*** Retrieve the old comment and metadata ***/
		$result = db_query("SELECT * FROM $dbTable WHERE path = " . autoquote($comment));
                if (mysql_num_rows($result) > 0) {
                        $row = mysql_fetch_array($result);
                        $oldFilename = q($row['filename']);
                        $oldComment = q($row['comment']);
                        $oldCategory = $row['category'];
                        $oldTitle = q($row['title']);
                        $oldCreator = q($row['creator']);
                        $oldDate = q($row['date']);
                        $oldSubject = q($row['subject']);
                        $oldDescription = q($row['description']);
                        $oldAuthor = q($row['author']);
                        $oldLanguage = q($row['language']);
                        $oldCopyrighted = $row['copyrighted'];

                        // filsystem compability: ean gia to arxeio den yparxoun dedomena sto pedio filename
                        // (ara to arxeio den exei safe_filename (=alfarithmitiko onoma)) xrhsimopoihse to
                        // $fileName gia thn provolh tou onomatos arxeiou
                        $fileName = my_basename($comment);
                        if (empty($oldFilename)) $oldFilename = $fileName;
                        $dialogBox .= "<form method='post' action='document.php'>
                                <input type='hidden' name='commentPath' value='" . q($comment) . "' />
                                <input type='hidden' size='80' name='file_filename' value='$oldFilename' />
                                <table  class='FormData' width='99%'>
                                <tbody><tr><th>&nbsp;</th>
                                <td><b>$langAddComment:</b> $oldFilename</td>
                                </tr><tr>
                                <th class='left'>$langComment:</th>
                                <td><input type='text' size='60' name='file_comment' value='$oldComment' class='FormData_InputText' /></td>
                                </tr><tr>
                                <th class='left'>$langTitle:</th>
                                <td><input type='text' size='60' name='file_title' value='$oldTitle' class='FormData_InputText' /></td>
                                </tr>
                                <tr><th class='left'>$langCategory:</th><td>";
                        //ektypwsh tou combobox gia thn epilogh kathgorias tou eggrafou
                        $dialogBox .= "<select name='file_category' class='auth_input'>
                                <option"; if($oldCategory=="0") $dialogBox .= " selected='selected'"; $dialogBox .= " value='0'>$langCategoryOther";
                        $dialogBox .= "	<option";
                        if($oldCategory=="1") $dialogBox .= " selected='selected'"; $dialogBox .= " value='1'>$langCategoryExcercise
                        <option"; if($oldCategory=="1") $dialogBox .= " selected='selected'"; $dialogBox .= " value='2'>$langCategoryLecture
                        <option"; if($oldCategory=="2") $dialogBox .= " selected='selected'"; $dialogBox .= " value='3'>$langCategoryEssay
                        <option"; if($oldCategory=="3") $dialogBox .= " selected='selected'"; $dialogBox .= " value='4'>$langCategoryDescription
                        <option"; if($oldCategory=="4") $dialogBox .= " selected='selected'"; $dialogBox .= " value='5'>$langCategoryExample
                        <option"; if($oldCategory=="5") $dialogBox .= " selected='selected'"; $dialogBox .= " value='6'>$langCategoryTheory
                        </select></td></tr>";
                        $dialogBox .= "
                                <tr><th class='left'>$langSubject : </th><td>
                                <input type='text' size='60' name='file_subject' value='$oldSubject' class='FormData_InputText' />
                                </td></tr><tr><th class='left'>$langDescription : </th><td>
                                <input type='text' size='60' name='file_description' value='$oldDescription' class='FormData_InputText' /></td></tr>
                                <tr><th class='left'>$langAuthor : </th><td>
                                <input type='text' size='60' name='file_author' value='$oldAuthor' class='FormData_InputText' />
                                </td></tr>";

                        $dialogBox .= "<tr><th class='left'>$langCopyrighted : </th>
                                <td><input name='file_copyrighted' type='radio' value='0' ";
                        if ($oldCopyrighted=="0" || empty($oldCopyrighted)) $dialogBox .= " checked='checked' "; $dialogBox .= " /> $langCopyrightedUnknown <input name='file_copyrighted' type='radio' value='2' "; if ($oldCopyrighted=="2") $dialogBox .= " checked='checked' "; $dialogBox .= " /> $langCopyrightedFree <input name='file_copyrighted' type='radio' value='1' ";

                        if ($oldCopyrighted=="1") $dialogBox .= " checked='checked' "; $dialogBox .= "/> $langCopyrightedNotFree
                        </td></tr>
";
                        //ektypwsh tou combox gia epilogh glwssas
                        $dialogBox .= "	<tr><th class='left'>$langLanguage :</th><td>" .
                                selection(array('en' => $langEnglish,
                                                'fr' => $langFrench,
                                                'de' => $langGerman,
                                                'el' => $langGreek,
                                                'it' => $langItalian,
                                                'es' => $langSpanish), 'file_language', $oldLanguage) .
                                "</td></tr>
                                <tr><th>&nbsp;</th>
                                <td><input type='submit' value='$langOkComment' />&nbsp;&nbsp;&nbsp;$langNotRequired</td>
                                </tr></tbody></table>
                                <input type='hidden' size='80' name='file_creator' value='$oldCreator' />
                                <input type='hidden' size='80' name='file_date' value='$oldDate' />
                                <input type='hidden' size='80' name='file_oldLanguage' value='$oldLanguage' />
                                </form><br />";
                } else {
                        $dialogBox = "<p class='caution_small'>$langFileNotFound</p><br />";
                }
        }

	// Visibility commands
	if (isset($_GET['mkVisibl']) || isset($_GET['mkInvisibl'])) {
		if (isset($_GET['mkVisibl'])) {
                        $newVisibilityStatus = "v";
                        $visibilityPath = $_GET['mkVisibl'];
                } else {
                        $newVisibilityStatus = "i";
                        $visibilityPath = $_GET['mkInvisibl'];
                }
		db_query("UPDATE $dbTable SET visibility='$newVisibilityStatus' WHERE path = " . autoquote($visibilityPath));
		$dialogBox = "<p class='success_small'>$langViMod</p><br />";
	}
} // teacher only

// Common for teachers and students
// define current directory

// Check if $var is set and return it - if $is_file, then return only dirname part
function pathvar(&$var, $is_file = false)
{
        static $found = false;
        if ($found) {
                return '';
        }
        if (isset($var)) {
                $found = true;
                $var = str_replace('..', '', $var);
                if ($is_file) {
                        return dirname($var);
                } else {
                        return $var;
                }
        }
        return '';
}

$curDirPath = 
        pathvar($_GET['openDir'], false) .
        pathvar($_GET['createDir'], false) .
        pathvar($_POST['moveTo'], false) .
        pathvar($_POST['newDirPath'], false) .
        pathvar($_POST['uploadPath'], false) .
        pathvar($_POST['delete'], true) .
        pathvar($_GET['move'], true) .
        pathvar($_GET['rename'], true) .
        pathvar($_GET['comment'], true) .
        pathvar($_GET['mkInvisibl'], true) .
        pathvar($_GET['mkVisibl'], true) .
        pathvar($_POST['sourceFile'], true) .
        pathvar($_POST['commentPath'], true);

if ($curDirPath == '/' or $curDirPath == '\\') {
        $curDirPath = '';
}
$curDirName = my_basename($curDirPath);
$parentDir = dirname($curDirPath);
if ($parentDir == '\\') {
        $parentDir = '/';
}

if (strpos($curDirName, '/../') !== false or
    !is_dir(realpath($basedir . $curDirPath))) {
	$tool_content .=  $langInvalidDir;
        draw($tool_content, 2);
        exit;
}

$order = 'ORDER BY filename';
$sort = 'name';
$reverse = false;
if (isset($_GET['sort'])) {
        if ($_GET['sort'] == 'type') {
                $order = 'ORDER BY format';
                $sort = 'type';
        } elseif ($_GET['sort'] == 'date') {
                $order = 'ORDER BY date_modified';
                $sort = 'date';
        }
}
if (isset($_GET['rev'])) {
        $order .= ' DESC';
        $reverse = true;
}

/*** Retrieve file info for current directory from database and disk ***/
$result = db_query("SELECT * FROM $dbTable
    	WHERE path LIKE '$curDirPath/%'
        AND path NOT LIKE '$curDirPath/%/%' $order");

$fileinfo = array();
while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $fileinfo[] = array(
                'is_dir' => is_dir($basedir . $row['path']),
                'size' => filesize($basedir . $row['path']),
                'title' => $row['title'],
                'filename' => $row['filename'],
                'format' => $row['format'],
                'path' => $row['path'],
                'visible' => ($row['visibility'] == 'v'),
                'comment' => $row['comment'],
                'copyrighted' => $row['copyrighted'],
                'date' => strtotime($row['date_modified']));
}

// end of common to teachers and students

// ----------------------------------------------
// Display
// ----------------------------------------------

$dspCurDirName = htmlspecialchars($curDirName);
$cmdCurDirPath = rawurlencode($curDirPath);
$cmdParentDir  = rawurlencode($parentDir);

if($is_adminOfCourse) {
	/*----------------------------------------------------------------
	UPLOAD SECTION (ektypwnei th forma me ta stoixeia gia upload eggrafou + ola ta pedia
	gia ta metadata symfwna me Dublin Core)
	------------------------------------------------------------------*/
	$tool_content .= "\n  <div id='operations_container'>\n    <ul id='opslist'>";
	$tool_content .= "\n      <li><a href='upload.php?uploadPath=$curDirPath'>$langDownloadFile</a></li>";
	/*----------------------------------------
	Create new folder
	--------------------------------------*/
	$tool_content .= "\n<li><a href='$_SERVER[PHP_SELF]?createDir=".$cmdCurDirPath."'>$langCreateDir</a></li>";
	$diskQuotaDocument = $diskQuotaDocument * 1024 / 1024;
	$tool_content .= "\n<li><a href='$_SERVER[PHP_SELF]?showQuota=TRUE'>$langQuotaBar</a></li>";
	$tool_content .= "\n</ul>\n</div>\n";

	// Dialog Box
	if (!empty($dialogBox))
	{
		$tool_content .=  $dialogBox . "\n";
	}
}

// check if there are documents
if($is_adminOfCourse) {
	$sql = db_query("SELECT * FROM document");
} else {
	$sql = db_query("SELECT * FROM document WHERE visibility = 'v'");
}
if (mysql_num_rows($sql) == 0) {
	$tool_content .= "\n<p class='alert1'>$langNoDocuments</p>";
} else {

	// Current Directory Line
	$tool_content .= "<br /><div class='fileman'>\n" .
	                 "<form action='document.php' method='post'>\n" .
	                 "<table width='99%' align='left' class='Documents'>\n" .
                         "<tbody>\n";

        if ($is_adminOfCourse) {
                $cols = 4;
        } else {
                $cols = 3;
        }

	$tool_content .= "\n  <tr>";
        $tool_content .= "\n    <th height='18' colspan='$cols'><div align=\"left\">$langDirectory: ".make_clickable_path($dbTable, $curDirPath). "</div></th>";
        $tool_content .= "\n    <th><div align='right'>";

        // Link for sortable table headings
        function headlink($label, $this_sort)
        {
                global $sort, $reverse, $curDirPath;

                if (empty($curDirPath)) {
                        $path = '/';
                } else {
                        $path = $curDirPath;
                }
                if ($sort == $this_sort) {
                        $this_reverse = !$reverse;
                        $indicator = ' <img src="../../template/classic/img/arrow_' . 
                                ($reverse? 'up': 'down') . '.gif" />';
                } else {
                        $this_reverse = $reverse;
                        $indicator = '';
                }
                return '<a href=\'' . $_SERVER['PHP_SELF'] . '?openDir=' . $path .
                       '&amp;sort=' . $this_sort . ($this_reverse? '&amp;rev=1': '') .
                       '\'>' . $label . $indicator . '</a>';
        }

	/*** go to parent directory ***/
        if ($curDirName) // if the $curDirName is empty, we're in the root point and we can't go to a parent dir
        {
                $parentlink = $_SERVER['PHP_SELF'] . '?openDir=' . $cmdParentDir;
                $tool_content .=  "<a href='$parentlink'>$langUp</a> <a href='$parentlink'><img src='../../template/classic/img/parent.gif' height='20' width='20' /></a>";
        }
        $tool_content .= "</div></th>";
        $tool_content .= "\n  </tr>";
        $tool_content .= "\n  <tr>";
        $tool_content .= "\n    <td width='10%' class='DocHead'><div align='center'><b>" .
                         headlink($langType, 'type') . '</b></div></td>';
        $tool_content .= "\n    <td class='DocHead'><div align='left'><b>" .
                         headlink($langName, 'name') . '</b></div></td>';
        $tool_content .= "\n    <td width='15%' class='DocHead'><div align='center'><b>$langSize</b></div></td>";
        $tool_content .= "\n    <td width='15%' class='DocHead'><div align='center'><b>" . 
                         headlink($langDate, 'date') . '</b></div></td>';
	if($is_adminOfCourse) {
		$tool_content .= "\n    <td width='20%' class='DocHead'><div align='center'><b>$langCommands</b></div></td>";
	}
	$tool_content .= "\n  </tr>";

        // -------------------------------------
        // Display directories first, then files
        // -------------------------------------
        foreach (array(true, false) as $is_dir) {
                foreach ($fileinfo as $entry) {
                        if (($entry['is_dir'] != $is_dir) or
                                        (!$is_adminOfCourse and !$entry['visible'])) {
                                continue;
                        }
                        $cmdDirName = $entry['path'];
                        if ($entry['visible']) {
                                $style = '';
                        } else {
                                $style = ' class="invisible"';
                        }
                        $copyright_icon = '';
                        if ($is_dir) {
                                $image = '../../template/classic/img/folder.gif';
                                $file_url = "$_SERVER[PHP_SELF]?openDir=$cmdDirName";
                                $link_extra = '';

                                $link_text = $entry['filename'];
                        } else {
                                $image = 'img/' . choose_image('.' . $entry['format']);
                                $file_url = file_url($cmdDirName, $entry['filename']);
                                $link_extra = " title='$langSave' target='_blank'";
                                if (empty($entry['title'])) {
                                        $link_text = $entry['filename'];
                                } else {
                                        $link_text = q($entry['title']);
                                }
                                if ($entry['copyrighted']) {
                                        $link_text .= " <img src='./img/copyrighted.jpg' />";
                                }
                        }
                        $tool_content .= "\n  <tr$style>";
                        $tool_content .= "\n    <td width='1%' valign='top'><a href='$file_url'$style$link_extra><img src='$image' /></a></td>";
                        $tool_content .= "\n    <td><div align='left'><a href='$file_url'$style$link_extra>$link_text</a>";

                        /*** comments ***/
                        if (!empty($entry['comment'])) {
                                $tool_content .= "<br /><span class='comment'>" .
                                        nl2br(htmlspecialchars($entry['comment'])) .
                                        "</span>\n";
                        }
                        $tool_content .= "</div></td>\n";
                        if ($is_dir) {
                                // skip display of date and time for directories
                                $tool_content .= "<td>&nbsp;</td><td>&nbsp;</td>";
                        } else {
                                $size = format_file_size($entry['size']);
                                $date = format_date($entry['date']);
                                $tool_content .= "<td>$size</td><td>$date</td>";
                        }
                        if ($is_adminOfCourse) {
                                /*** delete command ***/
                                $tool_content .= "<td><input type='image' src='../../template/classic/img/delete.gif' title='$langDelete' name='delete' value='$cmdDirName' onClick=\"return confirmation('".addslashes($entry['filename'])."');\" />&nbsp;";
                                /*** copy command ***/
                                $tool_content .= "<a href='$_SERVER[PHP_SELF]?move=$cmdDirName'>";
                                $tool_content .= "<img src='../../template/classic/img/move_doc.gif' title='$langMove' /></a>&nbsp;";
                                /*** rename command ***/
                                $tool_content .=  "<a href='$_SERVER[PHP_SELF]?rename=$cmdDirName'>";
                                $tool_content .=  "<img src='../../template/classic/img/edit.gif' title='$langRename' /></a>&nbsp;";
                                /*** comment command ***/
                                $tool_content .= "<a href='$_SERVER[PHP_SELF]?comment=$cmdDirName'>";
                                $tool_content .= "<img src='../../template/classic/img/information.gif' title='$langComment' /></a>&nbsp;";
                                /*** visibility command ***/
                                if ($entry['visible']) {
                                        $tool_content .= "<a href='$_SERVER[PHP_SELF]?mkInvisibl=$cmdDirName'>";
                                        $tool_content .= "<img src='../../template/classic/img/visible.gif' title='$langVisible' /></a>";
                                } else {
                                        $tool_content .= "<a href='$_SERVER[PHP_SELF]?mkVisibl=$cmdDirName'>";
                                        $tool_content .= "<img src='../../template/classic/img/invisible.gif' title='$langVisible' /></a>";
                                }
                                $tool_content .= "</td>";
                                $tool_content .= "\n  </tr>";
                        }
                }
        }
        $tool_content .=  "\n</tbody>\n</table>\n</form>\n";
	if ($is_adminOfCourse) {
		$tool_content .= "<p align='right'><small>$langMaxFileSize ".ini_get('upload_max_filesize')."</small></p>";
	}
        $tool_content .=  "\n</div>";
}
add_units_navigation(TRUE);
draw($tool_content, 2, '', $local_head);
