<?php
/*
* ========================================================================
* Open eClass 3.0 - E-learning and Course Management System
* ========================================================================
Copyright(c) 2003-2013  Greek Universities Network - GUnet
A full copyright notice can be read in "/info/copyright.txt".

Authors:     Costas Tsibanis <k.tsibanis@noc.uoa.gr>
Yannis Exidaridis <jexi@noc.uoa.gr>
Alexandros Diamantidis <adia@noc.uoa.gr>

For a full list of contributors, see "credits.txt".
 */

/**
* @file main.lib.php
* @brief General useful functions for eClass
* @authors many...
* Standard header included by all eClass files
* Defines standard functions and validates variables
*/

define('ECLASS_VERSION', '2.99');

// better performance while downloading very large files
define( 'PCLZIP_TEMPORARY_FILE_RATIO', 0.2);

/* course status */
define('COURSE_OPEN', 2);
define('COURSE_REGISTRATION', 1);
define('COURSE_CLOSED', 0);
define('COURSE_INACTIVE', 3);

/* user status */
define('USER_TEACHER', 1);
define('USER_STUDENT', 5);
define('USER_GUEST', 10);

// resized user image
define('IMAGESIZE_LARGE', 256);
define('IMAGESIZE_SMALL', 32);

// profile info access
define('ACCESS_PRIVATE', 0);
define('ACCESS_PROFS', 1);
define('ACCESS_USERS', 2);

// user admin rights
define('ADMIN_USER', 0); // admin user can do everything
define('POWER_USER', 1); // poweruser can admin only users and courses
define('USERMANAGE_USER', 2); // usermanage user can admin only users
define('DEPARTMENTMANAGE_USER', 3); // departmentmanage user can admin departments

// user email status
define('EMAIL_VERIFICATION_REQUIRED', 0);  /* email verification required. User cannot login */
define('EMAIL_VERIFIED', 1); // email is verified. User can login.
define('EMAIL_UNVERIFIED', 2); // email is unverified. User can login but cannot receive mail.

// course modules
define('MODULE_ID_AGENDA', 1);
define('MODULE_ID_LINKS', 2);
define('MODULE_ID_DOCS', 3);
define('MODULE_ID_VIDEO', 4);
define('MODULE_ID_ASSIGN', 5);
define('MODULE_ID_ANNOUNCE', 7);
define('MODULE_ID_USERS', 8);
define('MODULE_ID_FORUM', 9);
define('MODULE_ID_EXERCISE', 10);
define('MODULE_ID_COURSEINFO', 14);
define('MODULE_ID_GROUPS', 15);
define('MODULE_ID_DROPBOX', 16);
define('MODULE_ID_GLOSSARY', 17);
define('MODULE_ID_EBOOK', 18);
define('MODULE_ID_CHAT', 19);
define('MODULE_ID_DESCRIPTION', 20);
define('MODULE_ID_QUESTIONNAIRE', 21);
define('MODULE_ID_LP', 23);
define('MODULE_ID_USAGE', 24);
define('MODULE_ID_TOOLADMIN', 25);
define('MODULE_ID_WIKI', 26);
define('MODULE_ID_UNITS', 27);
define('MODULE_ID_SEARCH', 28);
define('MODULE_ID_CONTACT', 29);

// exercise answer types
define('UNIQUE_ANSWER',   1);
define('MULTIPLE_ANSWER', 2);
define('FILL_IN_BLANKS',  3);
define('MATCHING',        4);
define('TRUE_FALSE',      5);
// for fill in blanks questions
define('TEXTFIELD_FILL', 1);
define('LISTBOX_FILL',  2);//

// Subsystem types (used in documents)
define('MAIN', 0);
define('GROUP', 1);
define('EBOOK', 2);
define('COMMON', 3);

// Show query string and then do MySQL query
function db_query2($sql, $db = false)
{
        echo "<hr /><pre>".q($sql)."</pre><hr />";
        return db_query($sql, $db);
}

/*
 Debug MySQL queries
-------------------------------------------------------------------------
it is better to use the function below instead of the usual mysql_query()
first argument: the query
second argument (optional) : the name of the data base
If error happens just display the error and the code
-----------------------------------------------------------------------
*/

function db_query($sql, $db_name = null)
{
        if (isset($db_name)) {
                mysql_select_db($db_name);
        }
        if (defined('DEBUG_MYSQL') and DEBUG_MYSQL === 'FULL') {
                $f_sql = q(str_replace("\t", '        ', $sql));
                $start_time = microtime(true);
        }
        $r = mysql_query($sql);
        $printed_sql = false;
        if (defined('DEBUG_MYSQL') and DEBUG_MYSQL === 'FULL') {
                echo '<hr /><pre>', q($f_sql), '</pre><i>runtime: ',
                     sprintf('%0.3f', 1000 * (microtime(true) - $start_time)),
                     'ms</i><hr />';
                $printed_sql = true;
        }
        if (mysql_errno()) {
                if ((isset($GLOBALS['is_admin']) and $GLOBALS['is_admin']) or
                    (defined('DEBUG_MYSQL') and DEBUG_MYSQL)) {
                        echo '<hr />' . mysql_errno() . ': ' . q(mysql_error());
                        if (!$printed_sql) {
                                echo '<br /><pre>', q($sql), '</pre><hr />';
                        }
                } else {
                        echo '<hr />Database error<hr />';
                }
        }
        return $r;
}


// Check if a string looks like a valid email address
function email_seems_valid($email)
{
        return (preg_match('#^[0-9a-z_\.\+-]+@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,}$#i', $email)
                and !preg_match('#@.*--#', $email));
}

// Eclass SQL query wrapper returning only a single result value.
// Useful in some cases because, it avoid nested arrays of results.
function db_query_get_single_value($sqlQuery, $db = false) {
        $result = db_query($sqlQuery, $db);

        if ($result) {
                list($value) = mysql_fetch_row($result);
                mysql_free_result($result);
                return $value;
        }
        else {
                return false;
        }
}

// Claroline SQL query wrapper returning only the first row of the result
// Useful in some cases because, it avoid nested arrays of results.
function db_query_get_single_row($sqlQuery, $db = false) {
        $result = db_query($sqlQuery, $db);

        if($result) {
                $row = mysql_fetch_array($result, MYSQL_ASSOC);
                mysql_free_result($result);
                return $row;
        }
        else {
                return false;
        }
}

// Eclass SQL fetch array returning all the result rows
// in an associative array. Compared to the PHP mysql_fetch_array(),
// it proceeds in a single pass.
function db_fetch_all($sqlResultHandler, $resultType = MYSQL_ASSOC) {
        $rowList = array();

        while( $row = mysql_fetch_array($sqlResultHandler, $resultType) )
        {
                $rowList [] = $row;
        }

        mysql_free_result($sqlResultHandler);

        return $rowList;
}

// Eclass SQL query and fetch array wrapper. It returns all the result rows
// in an associative array.
function db_query_fetch_all($sqlQuery, $db = false) {
        $result = db_query($sqlQuery, $db);

        if ($result) return db_fetch_all($result);
        else         return false;
}


// ----------------------------------------------------------------------
// for safety reasons use the functions below
// ---------------------------------------------------------------------

/*
 * Quote string for SQL query
 */
function quote($s) {
        return "'".mysql_real_escape_string(canonicalize_whitespace($s))."'";
}


// Quote string for SQL query if needed (if magic quotes are off)
function autoquote($s) {
        $s = canonicalize_whitespace($s);
        if (phpversion() < '5.4' and get_magic_quotes_gpc()) {
                return "'$s'";
        } else {
                return "'".addslashes($s)."'";
        }
}

// Unquote string if needed (if magic quotes are on)
function autounquote($s) {
        $s = canonicalize_whitespace($s);
        if (phpversion() < '5.4' and get_magic_quotes_gpc()) {
                return stripslashes($s);
        } else {
                return $s;
        }
}

// Shortcut for htmlspecialchars()
function q($s)
{
        return htmlspecialchars($s, ENT_QUOTES);
}

function unescapeSimple($str)
{
        if (phpversion() < '5.4' and get_magic_quotes_gpc()) {
                return stripslashes($str);
        } else {
                return $str;
        }
}

// Escape string to use as JavaScript argument
function js_escape($s)
{
        return q(str_replace("'", "\\'", $s));
}

// Include a JavaScript file from the main js directory
function load_js($file, $init = '')
{
        global $head_content, $urlAppend, $theme;

        if ($file == 'jquery') {
                $file = 'jquery-1.8.3.min.js';
        } elseif ($file == 'jquery-ui') {
                $file = 'jquery-ui-1.8.1.custom.min.js';
        } elseif ($file == 'jquery-ui-new') {
                if ($theme == 'modern' || $theme == 'ocean')
                    $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/jquery-ui-css/redmond/jquery-ui-1.9.2.custom.min.css'>\n";
                else
                    $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/jquery-ui-css/smoothness/jquery-ui-1.9.2.custom.min.css'>\n";
                $file = 'jquery-ui-1.9.2.custom.min.js';
        } elseif ($file == 'jstree') {
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/jstree/jquery.cookie.min.js'></script>\n";
            $file = 'jstree/jquery.jstree.min.js';
        } elseif ($file == 'shadowbox') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/shadowbox/shadowbox.css'>";
            $file = 'shadowbox/shadowbox.js';
        } elseif ($file == 'fancybox2') {
            $head_content .= "<link rel='stylesheet' href='{$urlAppend}js/fancybox2/jquery.fancybox.css?v=2.0.3' type='text/css' media='screen'>";
            $file = 'fancybox2/jquery.fancybox.pack.js?v=2.0.3';
        } elseif ($file == 'colorbox') {
            $head_content .= "<link rel='stylesheet' type='text/css' href='{$urlAppend}js/colorbox/colorbox.css'>\n";
            $file = 'colorbox/jquery.colorbox-min.js';
        } elseif ($file == 'flot') {
            $head_content .= "\n<link href=\"{$urlAppend}js/flot/flot.css\" rel=\"stylesheet\" type=\"text/css\">\n";
            $head_content .= "<!--[if lte IE 8]><script language=\"javascript\" type=\"text/javascript\" src=\"{$urlAppend}js/flot/excanvas.min.js\"></script><![endif]-->\n";
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/jquery-1.8.3.min.js'></script>\n";
            $head_content .= "<script type='text/javascript' src='{$urlAppend}js/flot/jquery.flot.min.js'></script>\n";
            $file = 'flot/jquery.flot.categories.min.js';
        }
        $head_content .= "<script type='text/javascript' src='{$urlAppend}js/$file'></script>\n";

        if (strlen($init) > 0)
            $head_content .= $init;
}

// Translate uid to username
function uid_to_username($uid)
{
        global $mysqlMainDb;

        if ($r = mysql_fetch_row(db_query(
        "SELECT username FROM user WHERE user_id = '".mysql_real_escape_string($uid)."'",
        $mysqlMainDb))) {
                return $r[0];
        } else {
                return false;
        }
}


// Return HTML for a user - first parameter is either a user id (so that the
// user's info is fetched from the DB) or a hash with user_id, prenom, nom,
// email, or an array of user ids or user info arrays
function display_user($user, $print_email = false, $icon = true)
{
        global $langAnonymous, $urlAppend;

        if (count($user) == 0) {
                return '-';
        } elseif (is_array($user) and !isset($user['user_id'])) {
                $begin = true;
                $html = '';
                foreach ($user as $user_data) {
                        if ($begin) {
                                $begin = false;
                        } else {
                                $html .= ', ';
                        }
                        $html .= display_user($user_data, $print_email);
                }
                return $html;
        } elseif (!is_array($user)) {
                $r = db_query("SELECT user_id, nom, prenom, email, has_icon FROM user WHERE user_id = $user");
                if ($r and mysql_num_rows($r) > 0) {
                        $user = mysql_fetch_array($r);
                } else {
                        if ($icon)
                            return profile_image(0, IMAGESIZE_SMALL, true) . '&nbsp;'. $langAnonymous;
                        else
                            return $langAnonymous;
                }
        }

        if ($print_email) {
                $email = trim($user['email']);
                $print_email = $print_email && !empty($email);
        }
        if ($icon) {
                if ($user['has_icon']) {
                        $icon = profile_image($user['user_id'], IMAGESIZE_SMALL) . '&nbsp;';
                } else {
                        $icon = profile_image($user['user_id'], IMAGESIZE_SMALL, true) . '&nbsp;';
                }
        }
        return "$icon<a href='{$urlAppend}modules/profile/display_profile.php?id=$user[user_id]'>" . q("$user[prenom] $user[nom]") . "</a>" .
                ($print_email? (' (' . mailto(trim($user['email']), 'e-mail address hidden') . ')'): '');

}


// Translate uid to real name / surname
function uid_to_name($uid) {
    global $mysqlMainDb;

    $r = mysql_fetch_row(db_query("SELECT CONCAT(nom, ' ', prenom)
                FROM user WHERE user_id = '".mysql_real_escape_string($uid)."'", $mysqlMainDb));

    if ($r !== false) {
        return $r[0];
    } else {
        return false;
    }
}

// Translate uid to real surname
function uid_to_surname($uid) {
    global $mysqlMainDb;

    $r = mysql_fetch_row(db_query("SELECT nom
                FROM user WHERE user_id = '" . mysql_real_escape_string($uid) . "'", $mysqlMainDb));

    if ($r !== false) {
        return $r[0];
    } else {
        return false;
    }
}

// Translate uid to user email
function uid_to_email($uid) {
    global $mysqlMainDb;

    $r = mysql_fetch_row(db_query("SELECT email
                FROM user WHERE user_id = '" . mysql_real_escape_string($uid) . "'", $mysqlMainDb));

    if ($r !== false) {
        return $r[0];
    } else {
        return false;
    }
}


// Translate uid to AM (student number)
function uid_to_am($uid) {
    global $mysqlMainDb;

    $r = mysql_fetch_array(db_query("SELECT am from user
                WHERE user_id = '$uid'", $mysqlMainDb));

    if ($r !== false) {
        return $r[0];
    } else {
        return false;
    }
}


/*************************************************************
Show a selection box with departments.

The function returns a value( a formatted select box with departments)
and their values as keys in the array/select box

$department_value: the predefined/selected department value
return $departments_select : string (a formatted select box)
****************************************************************/
function list_departments($department_value)
{
        $qry = "SELECT id, name FROM hierarchy WHERE allow_course = true ORDER BY name";
        $dep = db_query($qry);
        if($dep)
        {
                $departments_select = "";
                $departments = array();
                while($row = mysql_fetch_array($dep))
                {
                        $id = $row['id'];
                        $name = $row['name'];
                        $departments[$id] = $name;
                }
                $departments_select = selection($departments, "department", $department_value);
                return $departments_select;
        } else {
                return 0;
        }
}


/********************************************
// only for http://eclass.uoa.gr
/*************************************************************
/*************************************************************
Show a selection box with division of departments.

The function returns a value( a formatted select box with division of departments)
and their values as keys in the array/select box

$division_value: the predefined/selected department value
return $divisions_select : string (a formatted select box)
****************************************************************/

function list_divisions($department_value)
{

        $qry = "SELECT id, name FROM division WHERE faculte_id = $department_value ORDER BY name";
        $div = db_query($qry);
        if($div)
        {
                $divisions_select = "";
                $divisions = array();
                while($row = mysql_fetch_array($div))
                {
                        $id = $row['id'];
                        $name = $row['name'];
                        $divisions[$id] = $name;
                }
                $divisions_select = selection($divisions, "division");
                return $divisions_select;
        } else {
                return 0;
        }
}

// Display links to the groups a user is member of
function user_groups($course_id, $user_id, $format = 'html')
{
        global $urlAppend;

        $groups = '';
        $q = db_query("SELECT `group`.id, `group`.name FROM `group`, group_members
                       WHERE `group`.course_id = $course_id AND
                             `group`.id = group_members.group_id AND
                             `group_members`.user_id = $user_id
                       ORDER BY `group`.name");
        $count = mysql_num_rows($q);
        if (!$count) {
                if ($format == 'html') {
                        return "<div style='padding-left: 15px'>-</div>";
                } else {
                        return '-';
                }
        }
        while ($r = mysql_fetch_array($q)) {
                if ($format == 'html') {
                        $groups .= (($count > 1)? '<li>': '') .
                                   "<a href='{$urlAppend}modules/group/group_space.php?group_id=$r[id]' title='" .
                                   q($r['name']) . "'>" .
                                   q(ellipsize($r['name'], 20)) . "</a>" .
                                   (($count > 1)? '</li>': '');
                } else {
                        $groups .= (empty($groups)? '': ', ') . $r['name'];
                }
        }
        if ($format == 'html') {
                if ($count > 1) {
                        return "<ol>$groups</ol>";
                } else {
                        return "<div style='padding-left: 15px'>$groups</div>";
                }
        } else {
                return $groups;
        }
}


// Find secret subdir of group gid
function group_secret($gid)
{
        global $mysqlMainDb;

        $res = db_query("SELECT secret_directory FROM `group` WHERE id = '$gid'", $mysqlMainDb);
        if ($res) {
                $secret = mysql_fetch_row($res);
                return $secret[0];
        } else {
                die("Error: group $gid doesn't exist");
        }
}


// ------------------------------------------------------------------
// Often useful function (with so many selection boxes in eClass !!)
// ------------------------------------------------------------------


// Show a selection box.
// $entries: an array of (value => label)
// $name: the name of the selection element
// $default: if it matches one of the values, specifies the default entry
// Changed by vagpits
function selection($entries, $name, $default = '', $extra = '')
{
        $retString = "";
        $retString .= "\n<select name='$name' $extra>\n";
        foreach ($entries as $value => $label) {
                if (isset($default) && ($value == $default)) {
                        $retString .= "<option selected value='" . htmlspecialchars($value) . "'>" .
                        htmlspecialchars($label) . "</option>\n";
                } else {
                        $retString .= "<option value='" . htmlspecialchars($value) . "'>" .
                        htmlspecialchars($label) . "</option>\n";
                }
        }
        $retString .= "</select>\n";
        return $retString;
}

/********************************************************************
Show a selection box. Taken from main.lib.php
Difference: the return value and not just echo the select box

$entries: an array of (value => label)
$name: the name of the selection element
$default: if it matches one of the values, specifies the default entry
 ***********************************************************************/
function selection3($entries, $name, $default = '') {
        $select_box = "<select name='$name'>\n";
        foreach ($entries as $value => $label)  {
            if ($value == $default) {
                $select_box .= "<option selected value='" . htmlspecialchars($value) . "'>" .
                                htmlspecialchars($label) . "</option>\n";
                }  else {
                $select_box .= "<option value='" . htmlspecialchars($value) . "'>" .
                                htmlspecialchars($label) . "</option>\n";
                }
        }
        $select_box .= "</select>\n";

        return $select_box;
}

// ------------------------------------------
// function to check if user is a guest user
// ------------------------------------------

function check_guest() {

    global $uid;

        if (isset($uid)) {
                $res = db_query("SELECT statut FROM user WHERE user_id = $uid");
                $g = mysql_fetch_row($res);

                if ($g[0] == USER_GUEST) {
                        return true;
                } else {
                        return false;
                }
        }
}

// ------------------------------------------------
// function to check if user is a course editor
// ------------------------------------------------

function check_editor() {

        global $uid, $course_id;

        if (isset($uid)) {
                $res = db_query("SELECT editor FROM course_user
                            WHERE user_id = $uid
                            AND course_id = $course_id");
                $s = mysql_fetch_array($res);
                if ($s['editor'] == 1) {
                        return true;
                } else {
                        return false;
                }
        } else {
            return false;
        }
}
// ---------------------------------------------------
// just make sure that the $uid variable isn't faked
// --------------------------------------------------

function check_uid() {

        global $urlServer, $require_valid_uid, $uid;

        if (isset($_SESSION['uid']))
        $uid = $_SESSION['uid'];
        else
        unset($uid);

        if ($require_valid_uid and !isset($uid)) {
                header("Location: $urlServer");
                exit;
        }

}

// -------------------------------------------------------
// Check if a user with username $login already exists
// ------------------------------------------------------

function user_exists($login) {
  global $mysqlMainDb;

  $qry = "SELECT user_id FROM `$mysqlMainDb`.user WHERE username ";
  if (get_config('case_insensitive_usernames')) {
        $qry .= "= " . quote($login);
  } else {
        $qry .= "COLLATE utf8_bin = ". quote($login);
  }
  $username_check = db_query($qry);

  return ($username_check && mysql_num_rows($username_check) > 0);
}

// ----------------------------------------------------------------
// Check if a user with username $login already applied for account
// ----------------------------------------------------------------

function user_app_exists($login) {
  global $mysqlMainDb;

  $qry = "SELECT id FROM `$mysqlMainDb`.user_request WHERE status=1 and uname ";
  if (get_config('case_insensitive_usernames')) {
        $qry .= "= " . quote($login);
  } else {
        $qry .= "COLLATE utf8_bin = ". quote($login);
  }
  $username_check = db_query($qry);

  return ($username_check && mysql_num_rows($username_check) > 0);
}

// Convert HTML to plain text

function html2text ($string)
{
        $trans_tbl = get_html_translation_table (HTML_ENTITIES);
        $trans_tbl = array_flip ($trans_tbl);

        $text = preg_replace('/</',' <',$string);
        $text = preg_replace('/>/','> ',$string);
        $desc = html_entity_decode(strip_tags($text));
        $desc = preg_replace('/[\n\r\t]/',' ',$desc);
        $desc = preg_replace('/  /',' ',$desc);

        return $desc;
        //    return strtr (strip_tags($string), $trans_tbl);
}

/*
// IMAP authentication functions                                        |
*/

function imap_auth($server, $username, $password)
{
        $auth = false;
        $fp = fsockopen($server, 143, $errno, $errstr, 10);
        if ($fp) {
                fputs ($fp, "A1 LOGIN ".imap_literal($username).
                " ".imap_literal($password)."\r\n");
                fputs ($fp, "A2 LOGOUT\r\n");
                while (!feof($fp)) {
                        $line = fgets ($fp,200);
                        if (substr($line, 0, 5) == 'A1 OK') {
                                $auth = true;
                        }
                }
                fclose ($fp);
        }
        return $auth;
}

function imap_literal($s)
{
        return "{".strlen($s)."}\r\n$s";
}


// -----------------------------------------------------------------------------------
// checking the mysql version
// note version_compare() is used for checking the php version but works for mysql too
// ------------------------------------------------------------------------------------

function mysql_version() {
        $ver = mysql_get_server_info();
        if (version_compare("4.1", $ver) <= 0)
        return true;
        else
        return false;
}


/**
 * @param $text
 * @return $text
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version June 2004
 * @desc apply parsing to content to parse tex commandos that are seperated by [tex][/tex] to make itreadable for techexplorer plugin.
*/
function parse_tex($textext)
{
        $textext=str_replace("[tex]","<EMBED TYPE='application/x-techexplorer' TEXDATA='",$textext);
        $textext=str_replace("[/tex]","' width='100%'>",$textext);
        return $textext;
}


// --------------------------------------
// Useful functions for creating courses
// -------------------------------------

// Returns the code of a faculty given its name
function find_faculty_by_name($name) {

        $code = mysql_fetch_row(db_query("SELECT code FROM hierarchy WHERE name =". quote($name) ));

        if (!$code) {
                return false;
        } else {
                return $code[0];
        }
}

// Returns the name of a faculty given its code or its name
function find_faculty_by_id($id) {

        $req = db_query("SELECT name FROM hierarchy WHERE id = ". intval($id) );

        if ($req and mysql_num_rows($req)) {
                $fac = mysql_fetch_row($req);
                return $fac[0];
        } else {
                $req = db_query("SELECT name FROM hierarchy WHERE name = '" . addslashes($id) ."'");
                if ($req and mysql_num_rows($req)) {
                        $fac = mysql_fetch_row($req);
                        return $fac[0];
                }
        }

        return false;
}

// Returns next available code for a new course in faculty with id $fac
function new_code($fac) {
        global $mysqlMainDb;

        mysql_select_db($mysqlMainDb);
        $gencode = mysql_fetch_row(db_query("SELECT code, generator FROM hierarchy WHERE id = ". intval($fac) ));

        do {
                $code = $gencode[0].$gencode[1];
                $gencode[1] += 1;
                db_query("UPDATE hierarchy SET generator = ". intval($gencode[1]) ." WHERE id = ". intval($fac) );
        } while (file_exists("courses/". $code));
        mysql_select_db($mysqlMainDb);

        // Make sure the code returned isn't empty!
        if (empty($code)) {
                die("Course Code is empty!");
        }

        return $code;
}

// due to a bug (?) to php function basename() our implementation
// handles correct multibyte characters (e.g. greek)
function my_basename($path) {
        return preg_replace('#^.*/#', '', $path);
}


/* transform the date format from "year-month-day" to "day-month-year"
 * if argument time is defined then
 * transform date time format from "year-month-day time" to "to "day-month-year time"
 */
function greek_format($date, $time = FALSE, $dont_display_time = FALSE)
{
        if ($time) {
                $datetime = explode(' ', $date);
                $new_date = implode('-', array_reverse(explode('-', $datetime[0])));
                if ($dont_display_time) {
                       return $new_date;
                } else {
                       return $new_date." ".$datetime[1];
                }
        } else {
                return implode('-', array_reverse(explode('-',$date)));
        }
}

// format the date according to language
function nice_format($date, $time = FALSE, $dont_display_time = FALSE)
{
        if ($GLOBALS['language'] == 'el') {
                return greek_format($date, $time, $dont_display_time);
        } else {
                return $date;
        }
}

// Returns user's previous login date, or today's date if no previous login
function last_login($uid)
{
        global $mysqlMainDb;

        $q = db_query("SELECT DATE_FORMAT(MAX(`when`), '%Y-%m-%d') FROM loginout
                          WHERE id_user = $uid AND action = 'LOGIN'");
        list($last_login) = mysql_fetch_row($q);
        if (!$last_login) {
                $last_login = date('Y-m-d');
        }
        return $last_login;
}


// Create a JavaScript-escaped mailto: link
function mailto($address, $alternative='(e-mail address hidden)')
{
        if (empty($address)) {
                return '&nbsp;';
        } else {
                $prog = urlenc("var a='" . urlenc(str_replace('@', '&#64;', $address)) .
                      "';document.write('<a href=\"mailto:'+unescape(a)+'\">'+unescape(a)+'</a>');");
                return "<script type='text/javascript'>eval(unescape('" .
                      q($prog) . "'));</script><noscript>" . q($alternative) . "</noscript>";
        }
}


function urlenc($string)
{
        $out = '';
        for ($i = 0; $i < strlen($string); $i++) {
                $out .= sprintf("%%%02x", ord(substr($string, $i, 1)));
        }
        return $out;
}


/*
 * Get user data on the platform
 * @param $user_id integer
 * @return  array( `user_id`, `lastname`, `firstname`, `username`, `email`, `picture`, `officialCode`, `phone`, `status` ) with user data
 * @author Mathieu Laurent <laurent@cerdecam.be>
 */

function user_get_data($user_id)
{
        global $mysqlMainDb;
        mysql_select_db($mysqlMainDb);

    $sql = 'SELECT  `user_id`,
                    `nom` AS `lastname` ,
                    `prenom`  AS `firstname`,
                    `username`,
                    `email`,
                    `phone` AS `phone`,
                    `statut` AS `status`
                        FROM   `user`
                            WHERE `user_id` = "' . (int) $user_id . '"';
    $result = db_query($sql);

    if (mysql_num_rows($result)) {
        $data = mysql_fetch_array($result);
        return $data;
    }
    else
    {
        return null;
    }
}


//function pou epistrefei tyxaious xarakthres. to orisma $length kathorizei to megethos tou apistrefomenou xarakthra
function randomkeys($length)
{
        $key = "";
        $pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
        for($i=0;$i<$length;$i++)
        {
                $key .= $pattern{rand(0,35)};
        }
        return $key;

}

// A helper function, when passed a number representing KB,
// and optionally the number of decimal places required,
// it returns a formated number string, with unit identifier.
function format_bytesize ($kbytes, $dec_places = 2)
{
        global $text;
        if ($kbytes > 1048576) {
                $result  = sprintf('%.' . $dec_places . 'f', $kbytes / 1048576);
                $result .= '&nbsp;Gb';
        } elseif ($kbytes > 1024) {
                $result  = sprintf('%.' . $dec_places . 'f', $kbytes / 1024);
                $result .= '&nbsp;Mb';
        } else {
                $result  = sprintf('%.' . $dec_places . 'f', $kbytes);
                $result .= '&nbsp;Kb';
        }
        return $result;
}


/*
 * Checks if Javascript is enabled on the client browser
 * A cookie is set on the header by javascript code.
 * If this cookie isn't set, it means javascript isn't enabled.
 *
 * return boolean enabling state of javascript
 * author Hugues Peeters <hugues.peeters@claroline.net>
 */

function is_javascript_enabled()
{
        return isset($_COOKIE['javascriptEnabled'])
                and $_COOKIE['javascriptEnabled'];
}

function add_check_if_javascript_enabled_js()
{
        return '<script type="text/javascript">document.cookie="javascriptEnabled=true";</script>';
}



/*
 * check extension and  write  if exist  in a  <LI></LI>
 * @params string       $extensionName  name  of  php extension to be checked
 * @params boolean      $echoWhenOk     true => show ok when  extension exist
 * @author Christophe Gesche
 * @desc check extension and  write  if exist  in a  <LI></LI>
 */
function warnIfExtNotLoaded($extensionName) {

        global $tool_content, $langModuleNotInstalled, $langReadHelp, $langHere;
        if (extension_loaded ($extensionName)) {
                $tool_content .= "<li><img src='../template/classic/img/tick_1.png' alt='tick' /> $extensionName <br /></li>";
        } else {
                $tool_content .= "
                <li><img src='../template/classic/img/error.png' alt='error' /> $extensionName
                <font color=\"#FF0000\"> - <b>$langModuleNotInstalled</b></font>
                (<a href=\"http://www.php.net/$extensionName\" target=_blank>$langReadHelp $langHere)</a>
                <br /></li>";
        }
}


/*
 * to create missing directory in a gived path
 *
 * @returns a resource identifier or false if the query was not executed correctly.
 * @author KilerCris@Mail.com original function from  php manual
 * @author Christophe Gesche gesche@ipm.ucl.ac.be Claroline Team
 * @since  28-Aug-2001 09:12
 * @param sting         $path           wanted path
 */
function mkpath($path)  {

        $path = str_replace("/","\\",$path);
        $dirs = explode("\\",$path);
        $path = $dirs[0];
        for ($i = 1;$i < count($dirs);$i++) {
                $path .= "/".$dirs[$i];
                if (!is_dir($path)) {
                        mkdir($path, 0775);
                }
        }
}


// check if we can display activationlink (e.g. module_id is one of our modules)
function display_activation_link($module_id) {

        global $modules;

        if (!defined('STATIC_MODULE') and array_key_exists($module_id, $modules)) {
                return true;
        } else {
                return false;
        }
}

// checks if a module is visible
function visible_module($module_id) {

        global $course_id;

        $v = mysql_fetch_array(db_query("SELECT visible FROM course_module
                                WHERE module_id = $module_id AND
                                course_id = $course_id"));

        if ($v['visible'] == 1) {
                return true;
        } else {
                return false;
        }
}


// Find the current module id from the script URL
function current_module_id()
{
        global $modules, $urlAppend, $static_module_paths;
        static $module_id;

        if (isset($module_id)) {
                return $module_id;
        }

        $module_path = str_replace($urlAppend.'modules/', '', $_SERVER['SCRIPT_NAME']);
        $link = preg_replace('|/.*$|', '', $module_path);
        if (isset($static_module_paths[$link])) {
                $module_id = $static_module_paths[$link];
                define('STATIC_MODULE', true);
                return false;
        }

        foreach ($modules as $mid => $info) {
                if ($info['link'] == $link) {
                        $module_id = $mid;
                        return $mid;
                }
        }
        return false;
}

// Returns true if a string is invalid UTF-8
function invalid_utf8($s)
{
        return !mb_detect_encoding($s, 'UTF-8', true);
}


function utf8_to_cp1253($s)
{
        // First try with iconv() directly
        $cp1253 = @iconv('UTF-8', 'Windows-1253', $s);
        if ($cp1253 === false) {
                // ... if it fails, fall back to indirect conversion
                $cp1253 = str_replace("\xB6", "\xA2", @iconv('UTF-8', 'ISO-8859-7', $s));
        }
        return $cp1253;
}


// Converts a string from Code Page 737 (DOS Greek) to UTF-8
function cp737_to_utf8($s)
{
        // First try with iconv()...
        $cp737 = @iconv('CP737', 'UTF-8', $s);
        if ($cp737 !== false) {
                return $cp737;
        } else {
                // ... if it fails, fall back to manual conversion
                return strtr($s,
                        array("\x80" => 'Ξ', "\x81" => 'Ξ', "\x82" => 'Ξ', "\x83" => 'Ξ',
                              "\x84" => 'Ξ', "\x85" => 'Ξ', "\x86" => 'Ξ', "\x87" => 'Ξ',
                              "\x88" => 'Ξ', "\x89" => 'Ξ', "\x8a" => 'Ξ', "\x8b" => 'Ξ',
                              "\x8c" => 'Ξ', "\x8d" => 'Ξ', "\x8e" => 'Ξ', "\x8f" => 'Ξ ',
                              "\x90" => 'Ξ‘', "\x91" => 'Ξ£', "\x92" => 'Ξ�', "\x93" => 'Ξ�',
                              "\x94" => 'Ξ¦', "\x95" => 'Ξ§', "\x96" => 'Ξ¨', "\x97" => 'Ξ©',
                              "\x98" => 'Ξ±', "\x99" => 'Ξ²', "\x9a" => 'Ξ³', "\x9b" => 'Ξ΄',
                              "\x9c" => 'Ξ΅', "\x9d" => 'ΞΆ', "\x9e" => 'Ξ·', "\x9f" => 'ΞΈ',
                              "\xa0" => 'ΞΉ', "\xa1" => 'ΞΊ', "\xa2" => 'Ξ»', "\xa3" => 'ΞΌ',
                              "\xa4" => 'Ξ½', "\xa5" => 'ΞΎ', "\xa6" => 'ΞΏ', "\xa7" => 'Ο',
                              "\xa8" => 'Ο', "\xa9" => 'Ο', "\xaa" => 'Ο', "\xab" => 'Ο',
                              "\xac" => 'Ο', "\xad" => 'Ο', "\xae" => 'Ο', "\xaf" => 'Ο',
                              "\xb0" => 'β', "\xb1" => 'β', "\xb2" => 'β', "\xb3" => 'β',
                              "\xb4" => 'β�', "\xb5" => 'β‘', "\xb6" => 'β’', "\xb7" => 'β',
                              "\xb8" => 'β', "\xb9" => 'β£', "\xba" => 'β', "\xbb" => 'β',
                              "\xbc" => 'β', "\xbd" => 'β', "\xbe" => 'β', "\xbf" => 'β',
                              "\xc0" => 'β', "\xc1" => 'β΄', "\xc2" => 'β¬', "\xc3" => 'β',
                              "\xc4" => 'β', "\xc5" => 'βΌ', "\xc6" => 'β', "\xc7" => 'β',
                              "\xc8" => 'β', "\xc9" => 'β', "\xca" => 'β©', "\xcb" => 'β¦',
                              "\xcc" => 'β ', "\xcd" => 'β', "\xce" => 'β¬', "\xcf" => 'β§',
                              "\xd0" => 'β¨', "\xd1" => 'β�', "\xd2" => 'β�', "\xd3" => 'β',
                              "\xd4" => 'β', "\xd5" => 'β', "\xd6" => 'β', "\xd7" => 'β«',
                              "\xd8" => 'β�', "\xd9" => 'β', "\xda" => 'β', "\xdb" => 'β',
                              "\xdc" => 'β', "\xdd" => 'β', "\xde" => 'β', "\xdf" => 'β',
                              "\xe0" => 'Ο', "\xe1" => 'Ξ¬', "\xe2" => 'Ξ­', "\xe3" => 'Ξ�',
                              "\xe4" => 'Ο', "\xe5" => 'Ξ―', "\xe6" => 'Ο', "\xe7" => 'Ο',
                              "\xe8" => 'Ο', "\xe9" => 'Ο', "\xea" => 'Ξ', "\xeb" => 'Ξ',
                              "\xec" => 'Ξ', "\xed" => 'Ξ', "\xee" => 'Ξ', "\xef" => 'Ξ',
                              "\xf0" => 'Ξ', "\xf1" => 'Β±', "\xf2" => 'β�', "\xf3" => 'β�',
                              "\xf4" => 'Ξ�', "\xf5" => 'Ξ«', "\xf6" => 'Γ·', "\xf7" => 'β',
                              "\xf8" => 'Β°', "\xf9" => 'β', "\xfa" => 'Β·', "\xfb" => 'β',
                              "\xfc" => 'βΏ', "\xfd" => 'Β²', "\xfe" => 'β ', "\xff" => 'Β '));
        }
}

/**
 * Return a new random filename, with the given extension
 * @param type $extension
 * @return string
 */
function safe_filename($extension = '')
{
        $prefix = sprintf('%08x', time()) . randomkeys(4);
        if (empty($extension)) {
                return $prefix;
        } else {
                return $prefix . '.' . $extension;
        }
}

function get_file_extension($filename)
{
        $matches = array();
        if (preg_match('/\.(tar\.(z|gz|bz|bz2))$/i', $filename, $matches)) {
                return strtolower($matches[1]);
        } elseif (preg_match('/\.([a-zA-Z0-9_-]{1,8})$/i', $filename, $matches)) {
                return strtolower($matches[1]);
        } else {
                return '';
        }
}

// Wrap each $item with single quote
function wrap_each(&$item)
{
    $item = "'$item'";
}


// Remove whitespace from start and end of string, convert
// sequences of whitespace characters to single spaces
// and remove non-printable characters, while preserving new lines
function canonicalize_whitespace($s)
{
        return str_replace(array(" \1 ", " \1", "\1 ", "\1"), "\n",
                  preg_replace('/[\t ]+/', ' ',
                     str_replace(array("\r\n", "\n", "\r"), "\1",
                        trim(preg_replace('/[\x00-\x08\x0C\x0E-\x1F\x7F]/', '', $s)))));
}

// Remove characters which can't appear in filenames
function remove_filename_unsafe_chars($s)
{
        return preg_replace('/[<>:"\/\\\\|?*]/', '',
                            canonicalize_whitespace($s));
}

/**
 * @brief check recourse accessibility
 * @global type $course_code
 * @param type $public
 * @return boolean
 */
function resource_access($visible, $public)
{
        global $course_code;
        if ($visible) {
                if ($public) {
                        return TRUE;
                } else {
                        if (isset($_SESSION['uid'])
                                and (isset($_SESSION['status'][$course_code]) and $_SESSION['status'][$course_code])) {
                                return TRUE;
                        } else {
                                return FALSE;
                        }
                }
        } else {
                return FALSE;
        }
}

# Only languages defined below are available for selection in the UI
# If you add any new languages, make sure they are defined in the
# next array as well
$native_language_names_init = array (
        'el' => 'Ελληνικά',
        'en' => 'English',
        'es' => 'Español',
        'cs' => 'Česky',
        'sq' => 'Shqip',
        'bg' => 'Български',
        'ca' => 'Català',
        'da' => 'Dansk',
        'nl' => 'Nederlands',
        'fi' => 'Suomi',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'is' => 'Íslenska',
        'it' => 'Italiano',
        'jp' => '日本語',
        'pl' => 'Polski',
        'ru' => 'Русский',
        'tr' => 'Türkçe',
        'xx' => 'Variable Names',
);

$language_codes = array(
        'el' => 'greek',
        'en' => 'english',
        'es' => 'spanish',
        'cs' => 'czech',
        'sq' => 'albanian',
        'bg' => 'bulgarian',
        'ca' => 'catalan',
        'da' => 'danish',
        'nl' => 'dutch',
        'fi' => 'finnish',
        'fr' => 'french',
        'de' => 'german',
        'is' => 'icelandic',
        'it' => 'italian',
        'jp' => 'japanese',
        'pl' => 'polish',
        'ru' => 'russian',
        'tr' => 'turkish',
        'xx' => 'variables',
);

// Convert language code to language name in English lowercase (for message files etc.)
// Returns 'english' if code is not in array
function langcode_to_name($langcode)
{
        global $language_codes;
        if (isset($language_codes[$langcode])) {
                return $language_codes[$langcode];
        } else {
                return 'english';
        }
}

// Convert language name to language code
function langname_to_code($langname)
{
        global $language_codes;
        $langcode = array_search($langname, $language_codes);
        if ($langcode) {
                return $langcode;
        } else {
                return 'en';
        }
}

// Make sure a language code is valid - if not, default language is Greek
function validate_language_code($langcode, $default = 'el')
{
        global $active_ui_languages;
        if (array_search($langcode, $active_ui_languages) === false) {
                return $default;
        } else {
                return $langcode;
        }
}

function append_units($amount, $singular, $plural)
{
        if ($amount == 1) {
                return $amount . ' ' . $singular;
        } else {
                return $amount . ' ' . $plural;
        }
}

// Convert $sec to days, hours, minutes, seconds;
function format_time_duration($sec)
{
        global $langsecond, $langseconds, $langminute, $langminutes, $langhour, $langhours, $langDay, $langDays;

        if ($sec < 60) {
                return append_units($sec, $langsecond, $langseconds);
        }
        $min = floor($sec / 60);
        $sec = $sec % 60;
        if ($min < 2) {
                return append_units($min, $langminute, $langminutes) .
                       (($sec == 0)? '': (' ' . append_units($sec, $langsecond, $langseconds)));
        }
        if ($min < 60) {
                return append_units($min, $langminute, $langminutes);
        }
        $hour = floor($min / 60);
        $min = $min % 60;
        if ($hour < 24) {
                append_units($hour, $langhour, $langhours) .
               (($min == 0)? '': (' ' . append_units($min, $langminute, $langminutes)));
        }
        $day = floor($hour / 24);
        $hour = $hour % 24;
        return (($day == 0)? '': (' ' . append_units($day, $langDay, $langDays))) .
                (($hour == 0)? '': (' ' . append_units($hour, $langhour, $langhours))) .
                (($min == 0)? '': (' ' . append_units($min, $langminute, $langminutes)));
}

// Move entry $id in $table to $direction 'up' or 'down', where
// order is in field $order_field and id in $id_field
// Use $condition as extra SQL to limit the operation
function move_order($table, $id_field, $id, $order_field, $direction, $condition = '')
{
        if ($condition) {
                $condition = ' AND ' . $condition;
        }
        if ($direction == 'down') {
                $op = '>';
                $desc = '';
        } else {
                $op = '<';
                $desc = 'DESC';
        }
        $sql = db_query("SELECT `$order_field` FROM `$table`
                         WHERE `$id_field` = '$id'");
        if (!$sql or mysql_num_rows($sql) == 0) {
                return false;
        }
        list($current) = mysql_fetch_row($sql);
        $sql = db_query("SELECT `$id_field`, `$order_field` FROM `$table`
                        WHERE `order` $op '$current' $condition
                        ORDER BY `$order_field` $desc LIMIT 1");
        if ($sql and mysql_num_rows($sql) > 0) {
                list($next_id, $next) = mysql_fetch_row($sql);
                db_query("UPDATE `$table` SET `$order_field` = $next
                          WHERE `$id_field` = $id");
                db_query("UPDATE `$table` SET `$order_field` = $current
                          WHERE `$id_field` = $next_id");
                return true;
        }
        return false;
}

// Add a link to the appropriate course unit if the page was requested
// with a unit=ID parametre. This happens if the user got to the module
// page from a unit resource link. If entry_page == true this is the initial page of module
// and is assumed that you're exiting the current unit unless $_GET['unit'] is set
function add_units_navigation($entry_page = false)
{
        global $navigation, $course_id, $is_editor, $mysqlMainDb, $course_code;
        if ($entry_page and !isset($_GET['unit'])) {
                unset($_SESSION['unit']);
                return false;
        } elseif (isset($_GET['unit']) or isset($_SESSION['unit'])) {
                if ($is_editor) {
                        $visibility_check = '';
                } else {
                        $visibility_check = "AND visible = 1";
                }
                if (isset($_GET['unit'])) {
                        $unit_id = intval($_GET['unit']);
                } elseif (isset($_SESSION['unit'])) {
                        $unit_id = intval($_SESSION['unit']);
                }
                $q = db_query("SELECT title FROM course_units
                       WHERE id = $unit_id AND course_id = $course_id " .
                       $visibility_check, $mysqlMainDb);
                if ($q and mysql_num_rows($q) > 0) {
                        list($unit_name) = mysql_fetch_row($q);
                        $navigation[] = array("url"=>"../units/index.php?course=$course_code&amp;id=$unit_id", "name"=> htmlspecialchars($unit_name));
                }
                return true;
        } else {
                return false;
        }
}

// Cut a string to be no more than $maxlen characters long, appending
// the $postfix (default: ellipsis "...") if so
function ellipsize($string, $maxlen, $postfix = '...')
{
        if (mb_strlen($string, 'UTF-8') > $maxlen) {
                return (mb_substr($string, 0, $maxlen, 'UTF-8')) . $postfix;
        } else {
                return $string;
        }
}

// Find the title of a course from its code
function course_code_to_title($code)
{
        global $mysqlMainDb;
        $r = db_query("SELECT title FROM course WHERE code = " . quote($code));
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return false;
        }
}


// Find the course id of a course from its code
function course_code_to_id($code)
{
        global $mysqlMainDb;
        $r = db_query("SELECT id FROM course WHERE code = " . quote($code), $mysqlMainDb);
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return false;
        }
}

// Find the title of a course from its id
function course_id_to_title($cid)
{
        global $mysqlMainDb;
        $r = db_query("SELECT title FROM course WHERE id = $cid", $mysqlMainDb);
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return false;
        }
}

// find the course code from its id
function course_id_to_code($cid)
{
        global $mysqlMainDb;
        $r = db_query("SELECT code FROM course WHERE id = $cid ", $mysqlMainDb);
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return false;
        }
}

// find the public course code from its id
function course_id_to_public_code($cid)
{
        global $mysqlMainDb;
        $r = db_query("SELECT public_code FROM course WHERE id = $cid ", $mysqlMainDb);
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return false;
        }
}

// Delete course with id = $cid
function delete_course($cid)
{
        global $webDir;

        $course_code = course_id_to_code($cid);

        db_query("DELETE FROM announcement WHERE course_id = $cid");
        db_query("DELETE FROM document WHERE course_id = $cid");
        db_query("DELETE FROM ebook_subsection WHERE section_id IN
                         (SELECT ebook_section.id FROM ebook_section, ebook
                                 WHERE ebook_section.ebook_id = ebook.id AND
                                       ebook.course_id = $cid)");
        db_query("DELETE FROM ebook_section WHERE id IN
                         (SELECT id FROM ebook WHERE course_id = $cid)");
        db_query("DELETE FROM ebook WHERE course_id = $cid");
        db_query("DELETE FROM forum_notify WHERE course_id = $cid");
        db_query("DELETE FROM glossary WHERE course_id = $cid");
        db_query("DELETE FROM group_members WHERE group_id IN
                         (SELECT id FROM `group` WHERE course_id = $cid)");
        db_query("DELETE FROM `group` WHERE course_id = $cid");
        db_query("DELETE FROM group_properties WHERE course_id = $cid");
        db_query("DELETE FROM link WHERE course_id = $cid");
        db_query("DELETE FROM link_category WHERE course_id = $cid");
        db_query("DELETE FROM agenda WHERE course_id = $cid");
        db_query("DELETE FROM unit_resources WHERE unit_id IN
                         (SELECT id FROM course_units WHERE course_id = $cid)");
        db_query("DELETE FROM course_units WHERE course_id = $cid");
        db_query("DELETE FROM course_user WHERE course_id = $cid");
        db_query("DELETE FROM course_department WHERE course = $cid");
        db_query("DELETE FROM course WHERE id = $cid");
        db_query("DELETE FROM video WHERE course_id = $cid");
        db_query("DELETE FROM videolinks WHERE course_id = $cid");
        db_query("DELETE FROM dropbox_person WHERE fileId IN (SELECT id FROM dropbox_file WHERE course_id = $cid)");
        db_query("DELETE FROM dropbox_post WHERE fileId IN (SELECT id FROM dropbox_file WHERE course_id = $cid)");
        db_query("DELETE FROM dropbox_file WHERE course_id = $cid");
        db_query("DELETE FROM lp_asset WHERE module_id IN (SELECT module_id FROM lp_module WHERE course_id = $cid)");
        db_query("DELETE FROM lp_rel_learnPath_module WHERE learnPath_id IN (SELECT learnPath_id FROM lp_learnPath WHERE course_id = $cid)");
        db_query("DELETE FROM lp_user_module_progress WHERE learnPath_id IN (SELECT learnPath_id FROM lp_learnPath WHERE course_id = $cid)");
        db_query("DELETE FROM lp_module WHERE course_id = $cid");
        db_query("DELETE FROM lp_learnPath WHERE course_id = $cid");
        db_query("DELETE FROM wiki_pages_content WHERE pid IN (SELECT id FROM wiki_pages WHERE wiki_id IN (SELECT id FROM wiki_properties WHERE course_id = $cid))");
        db_query("DELETE FROM wiki_pages WHERE wiki_id IN (SELECT id FROM wiki_properties WHERE course_id = $cid)");
        db_query("DELETE FROM wiki_acls WHERE wiki_id IN (SELECT id FROM wiki_properties WHERE course_id = $cid)");
        db_query("DELETE FROM wiki_properties WHERE course_id = $cid");
        db_query("DELETE FROM poll_question_answer WHERE pqid IN (SELECT pqid FROM poll_question WHERE pid IN (SELECT pid FROM poll WHERE course_id = $cid))");
        db_query("DELETE FROM poll_answer_record WHERE pid IN (SELECT pid FROM poll WHERE course_id = $cid)");
        db_query("DELETE FROM poll_question WHERE pid IN (SELECT pid FROM poll WHERE course_id = $cid)");
        db_query("DELETE FROM poll WHERE course_id = $cid");
        db_query("DELETE FROM assignment_submit WHERE assignment_id IN (SELECT id FROM assignment WHERE course_id = $cid)");
        db_query("DELETE FROM assignment WHERE course_id = $cid");
        db_query("DELETE FROM exercise_with_questions WHERE question_id IN (SELECT id FROM exercise_question WHERE course_id = $cid)");
        db_query("DELETE FROM exercise_with_questions WHERE exercise_id IN (SELECT id FROM exercise WHERE course_id = $cid)");
        db_query("DELETE FROM exercise_answer WHERE question_id IN (SELECT id FROM exercise_question WHERE course_id = $cid)");
        db_query("DELETE FROM exercise_question WHERE course_id = $cid");
        db_query("DELETE FROM exercise_user_record WHERE eid IN (SELECT id FROM exercise WHERE course_id = $cid)");
        db_query("DELETE FROM exercise WHERE course_id = $cid");
        db_query("DELETE FROM course_module WHERE course_id = $cid");

        $garbage = "$webDir/courses/garbage";
        if (!is_dir($garbage)) {
                mkdir($garbage, 0775);
        }
        rename("$webDir/courses/$course_code", "$garbage/$course_code");
        removeDir("$webDir/video/$course_code");
        // refresh index
        require_once 'modules/search/indexer.class.php';
        $idx = new Indexer();
        $idx->removeAllByCourse($cid);
}

function csv_escape($string, $force = false)
{
        global $charset;

        if ($charset != 'UTF-8') {
                if ($charset == 'Windows-1253') {
                        $string = utf8_to_cp1253($string);
                } else {
                        $string = iconv('UTF-8', $charset, $string);
                }
        }
        $string = preg_replace('/[\r\n]+/', ' ', $string);
        if (!preg_match("/[ ,!;\"'\\\\]/", $string) and !$force) {
                return $string;
        } else {
                return '"' . str_replace('"', '""', $string) . '"';

        }
}


// Return the value of a key from the config table, or a default value (or null) if not found
function get_config($key, $default=null)
{
        $r = db_query("SELECT `value` FROM config WHERE `key` = '$key'");
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return $default;
        }
}

// Set the value of a key in the config table
function set_config($key, $value)
{
        global $mysqlMainDb;

        db_query("REPLACE INTO `$mysqlMainDb`.config (`key`, `value`)
                          VALUES ('$key', " . quote($value) . ")");
}

// Copy variables from $_POST[] to $GLOBALS[], trimming and canonicalizing whitespace
// $var_array = array('var1' => true, 'var2' => false, [varname] => required...)
// Returns true if all vars with required=true are set, false if not (by default)
// If $what = 'any' returns true if any variable is set
function register_posted_variables($var_array, $what = 'all', $callback = null)
{
        global $missing_posted_variables;

        if (!isset($missing_posted_variables)) {
                $missing_posted_variables = array();
        }

        $all_set = true;
        $any_set = false;
        foreach ($var_array as $varname => $required) {
                if (isset($_POST[$varname])) {
                        $GLOBALS[$varname] = canonicalize_whitespace($_POST[$varname]);
                        if ($required and empty($GLOBALS[$varname])) {
                                $missing_posted_variables[$varname] = true;
                                $all_set = false;
                        }
                        if (!empty($GLOBALS[$varname])) {
                                $any_set = true;
                        }
                } else {
                        $GLOBALS[$varname] = '';
                        if ($required) {
                                $missing_posted_variables[$varname] = true;
                                $all_set = false;
                        }
                }
                if (is_callable($callback)) {
                        $GLOBALS[$varname] = $callback($GLOBALS[$varname]);
                }
        }
        if ($what == 'any') {
                return $any_set;
        } else {
                return $all_set;
        }
}


/**
 * Display a textarea with name $name using the rich text editor
 * Apply automatically various fixes for the text to be edited
 * @global type $head_content
 * @global type $language
 * @global type $purifier
 * @global type $urlAppend
 * @global type $course_code
 * @global type $langPopUp
 * @global type $langPopUpFrame
 * @global type $is_editor
 * @global type $is_admin
 * @param type $name
 * @param type $rows
 * @param type $cols
 * @param type $text
 * @param type $extra
 * @return type
 */
function rich_text_editor($name, $rows, $cols, $text, $extra = '')
{
        global $head_content, $language, $purifier, $urlAppend, $course_code, $langPopUp, $langPopUpFrame, $is_editor, $is_admin;

        $filebrowser = $url = '';
        $activemodule = 'document/index.php';
        if (isset($course_code) && !empty($course_code)) {
                $filebrowser = "file_browser_callback : 'openDocsPicker',";
                if (!$is_editor) {
                        $cid = course_code_to_id($course_code);
                        $sql = "SELECT * FROM course_module
                            WHERE course_id = $cid
                              AND (module_id =".MODULE_ID_DOCS." OR module_id =".MODULE_ID_VIDEO." OR module_id =".MODULE_ID_LINKS.")
                              AND VISIBLE = 1 ORDER BY module_id";

                        $result = db_query($sql);
                        $module = mysql_fetch_assoc($result);

                        if ($module === false)
                            $filebrowser = '';
                        else {
                            switch ($module['module_id']) {
                                case MODULE_ID_LINKS:
                                    $activemodule  = 'link/index.php';
                                    break;
                                case MODULE_ID_DOCS:
                                    $activemodule  = 'document/index.php';
                                    break;
                                case MODULE_ID_VIDEO:
                                    $activemodule  = 'video/index.php';
                                    break;
                                default:
                                    $filebrowser = '';
                                    break;
                            }
                        }
                }
                $url = $urlAppend."modules/$activemodule?course=$course_code&embedtype=tinymce&docsfilter=";
        } elseif ($is_admin) { /* special case for admin announcements */
                $filebrowser = "file_browser_callback : 'openDocsPicker',";
                $url = $urlAppend."modules/admin/commondocs.php?embedtype=tinymce&docsfilter=";
        }
        load_js('tinymce/jscripts/tiny_mce/tiny_mce_gzip.js');
        $head_content .= "
<script type='text/javascript'>
tinyMCE_GZ.init({
        plugins : 'pagebreak,style,save,advimage,advlink,inlinepopups,media,eclmedia,print,contextmenu,paste,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,emotions,preview,searchreplace,table,insertdatetime',
        themes : 'advanced',
        languages : '$language',
        disk_cache : true,
        debug : false
});

tinyMCE.init({
        // General options
                language : '$language',
                mode : 'specific_textareas',
                editor_deselector : 'mceNoEditor',
                theme : 'advanced',
                plugins : 'pagebreak,style,save,advimage,advlink,inlinepopups,media,eclmedia,print,contextmenu,paste,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,advlist,emotions,preview,searchreplace,table,insertdatetime',
                entity_encoding : 'raw',
                relative_urls : false,
                advlink_styles : '$langPopUp=colorbox;$langPopUpFrame=colorboxframe',
                $filebrowser

                // Theme options
                theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect,|,forecolor,backcolor',
                theme_advanced_buttons2 : 'cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,image,eclmedia,media,emotions,charmap,|,insertdate,inserttime',
                theme_advanced_buttons3 : 'tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,preview,cleanup,code,|,print',
                theme_advanced_toolbar_location : 'top',
                theme_advanced_toolbar_align : 'left',
                theme_advanced_statusbar_location : 'bottom',
                theme_advanced_resizing : true,

                // Style formats
                style_formats : [
                        {title : 'Bold text', inline : 'b'},
                        {title : 'Red text', inline : 'span', styles : {color : '#ff0000'}},
                        {title : 'Red header', block : 'h1', styles : {color : '#ff0000'}},
                        {title : 'Example 1', inline : 'span', classes : 'example1'},
                        {title : 'Example 2', inline : 'span', classes : 'example2'},
                        {title : 'Table styles'},
                        {title : 'Table row 1', selector : 'tr', classes : 'tablerow1'}
                ],

                // Replace values for the template plugin
                template_replace_values : {
                        username : 'Open eClass',
                        staffid : '991234'
                }
});

function openDocsPicker(field_name, url, type, win) {
    tinyMCE.activeEditor.windowManager.open({
        file: '$url' + type,
        title: 'Resources Browser',
        width: 700,
        height: 500,
        resizable: 'yes',
        inline: 'yes',
        close_previous: 'no',
        popup_css: false
    }, {
        window: win,
        input: field_name
    });
    return false;
}
</script>";

        /*$text = str_replace(array('<m>', '</m>', '<M>', '</M>'),
                                              array('[m]', '[/m]', '[m]', '[/m]'),
                                              $text); */

        return "<textarea name='$name' rows='$rows' cols='$cols' $extra>" .
               q(str_replace('{','&#123;', $text)) .
               "</textarea>\n";
}


// Display a simple textarea with name $name
// Apply automatically various fixes for the text to be edited
function text_area($name, $rows, $cols, $text, $extra = '')
{

        global $purifier;

        $text = str_replace(array('<m>', '</m>', '<M>', '</M>'),
                            array('[m]', '[/m]', '[m]', '[/m]'),
                            $text);
        if (strpos($extra, 'class=') === false) {
                $extra .= ' class="mceNoEditor"';
        }
        return "<textarea name='$name' rows='$rows' cols='$cols' $extra>" .
               q(str_replace('{','&#123;', $text)) .
               "</textarea>\n";
}

// Does the special course unit with course descriptions exist?
// If so, return its id, else create it first
function description_unit_id($course_id)
{
        global $langCourseDescription;

        $q = db_query("SELECT id FROM course_units
                       WHERE course_id = $course_id AND `order` = -1");
        if ($q and mysql_num_rows($q) > 0) {
                list($id) = mysql_fetch_row($q);
                return $id;
        } else {
                db_query('INSERT INTO course_units SET `order` = -1,
                                `title` = ' . quote($langCourseDescription) . ',
                                `visible` = 0,
                                `course_id` = ' . $course_id);
                return mysql_insert_id();
        }
}

function add_unit_resource_max_order($unit_id)
{
        $q = db_query("SELECT MAX(`order`) FROM unit_resources WHERE unit_id = $unit_id");
        if ($q and mysql_num_rows($q) > 0) {
                list($order) = mysql_fetch_row($q);
                return max(0, $order) + 1;
        } else {
                return 1;
        }
}


function new_description_res_id($unit_id)
{
        $q = db_query("SELECT MAX(res_id) FROM unit_resources WHERE unit_id = $unit_id");
        list($max_res_id) = mysql_fetch_row($q);
        return 1 + max(count($GLOBALS['titreBloc']), $max_res_id);
}


function add_unit_resource($unit_id, $type, $res_id, $title, $content, $visibility = 0, $date = false)
{
        if (!$date) {
                $date = 'NOW()';
        } else {
                $date = quote($date);
        }
        if ($res_id === false) {
                $res_id = new_description_res_id($unit_id);
                $order = add_unit_resource_max_order($unit_id);
        } elseif ($res_id < 0) {
                $order = $res_id;
        } else {
                $order = add_unit_resource_max_order($unit_id);
        }
        $q = db_query("SELECT id FROM unit_resources WHERE
                                `unit_id` = $unit_id AND
                                `type` = '$type' AND
                                `res_id` = $res_id");
        if ($q and mysql_num_rows($q) > 0) {
                list($id) = mysql_fetch_row($q);
                return db_query("UPDATE unit_resources SET
                                        `title` = " . quote($title) . ",
                                        `comments` = " . quote($content) . ",
                                        `date` = $date
                                 WHERE id = $id");
        }
        return db_query("INSERT INTO unit_resources SET
                                `unit_id` = $unit_id,
                                `title` = " . quote($title) . ",
                                `comments` = " . quote($content) . ",
                                `date` = $date,
                                `type` = '$type',
                                `visible` = $visibility,
                                `res_id` = $res_id,
                                `order` = $order");
}

function units_set_maxorder()
{
        global $maxorder, $course_id;
        $result = db_query("SELECT MAX(`order`) FROM course_units WHERE course_id = $course_id");
        list($maxorder) = mysql_fetch_row($result);
        if ($maxorder <= 0) {
                $maxorder = null;
        }
}

function handle_unit_info_edit()
{
        global $langCourseUnitModified, $langCourseUnitAdded, $maxorder, $course_id, $course_code;
        $title = autoquote($_REQUEST['unittitle']);
        $descr = autoquote($_REQUEST['unitdescr']);
        if (isset($_REQUEST['unit_id'])) { // update course unit
                $unit_id = intval($_REQUEST['unit_id']);
                $result = db_query("UPDATE course_units SET
                                           title = $title,
                                           comments = $descr
                                    WHERE id = $unit_id AND course_id = $course_id");
                $successmsg = $langCourseUnitModified;
        } else { // add new course unit
                $order = $maxorder + 1;
                db_query("INSERT INTO course_units SET
                                 title = $title, comments =  $descr, visible = 1,
                                 `order` = $order, course_id = $course_id");
                $successmsg = $langCourseUnitAdded;
                $unit_id = mysql_insert_id();
        }
        // update index
        global $webDir;
        require_once 'modules/search/indexer.class.php';
        require_once 'modules/search/courseindexer.class.php';
        require_once 'modules/search/unitindexer.class.php';
        $idx = new Indexer();
        $cidx = new CourseIndexer($idx);
        $uidx = new UnitIndexer($idx);
        $uidx->store($unit_id, false);
        $cidx->store($course_id, true);
        // refresh course metadata
        require_once 'modules/course_metadata/CourseXML.php';
        CourseXMLElement::refreshCourse($course_id, $course_code);

        return "<p class='success'>$successmsg</p>";
}

function math_unescape($matches)
{
        return html_entity_decode($matches[0]);
}

// Standard function to prepare some HTML text, possibly with math escapes, for display
function standard_text_escape($text, $mathimg = '../../courses/mathimg/')
{
        global $purifier;

        $text = preg_replace_callback('/\[m\].*?\[\/m\]/s', 'math_unescape', $text);
        $html = $purifier->purify(mathfilter($text, 12, $mathimg));

        if (!isset($_SESSION['glossary_terms_regexp'])) {
                return $html;
        }

        $dom = new DOMDocument();
	// workaround because DOM doesn't handle utf8 encoding correctly.
	@$dom->loadHTML('<div>' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>');

        $xpath = new DOMXpath($dom);
        $textNodes = $xpath->query('//text()');
        foreach ($textNodes as $textNode) {
                if (!empty($textNode->data)) {
                        $new_contents = glossary_expand($textNode->data);
                        if ($new_contents != $textNode->data) {
                                $newdoc = new DOMDocument();
                                $newdoc->loadXML('<span>' . $new_contents . '</span>');
                                $newnode = $dom->importNode($newdoc->getElementsByTagName('span')->item(0), true);
                                $textNode->parentNode->replaceChild($newnode, $textNode);
                                unset($newdoc);
                                unset($newnode);
                        }
                }
        }
        $base_node = $dom->getElementsByTagName('div')->item(0);
        // iframe hack
        return preg_replace(array('|^<div>(.*)</div>$|s',
                                  '#(<iframe [^>]+)/>#'),
                            array('\\1', '\\1></iframe>'),
                            dom_save_html($dom, $base_node));
}

// Workaround for $dom->saveHTML($node) not working for PHP < 5.3.6
function dom_save_html($dom, $node)
{
        if (version_compare(PHP_VERSION, '5.3.6') >= 0) {
                return $dom->saveHTML($node);
        } else {
                return $dom->saveXML($node);
        }
}

function purify($text)
{
        global $purifier;
        return $purifier->purify($text);
}

// Expand glossary terms to HTML for tooltips with the definition
function glossary_expand($text)
{
        return preg_replace_callback($_SESSION['glossary_terms_regexp'],
                'glossary_expand_callback', $text);
}

function glossary_expand_callback($matches)
{
        static $glossary_seen_terms;

        $term = mb_strtolower($matches[0], 'UTF-8');
        if (isset($glossary_seen_terms[$term])) {
                return $matches[0];
        }
        $glossary_seen_terms[$term] = true;
        if (!empty($_SESSION['glossary'][$term])) {
                $definition = ' title="' . q($_SESSION['glossary'][$term]) . '"';
        } else {
                $definition = '';
        }
        if (isset($_SESSION['glossary_url'][$term])) {
                return '<a href="' . q($_SESSION['glossary_url'][$term]) .
                       '" target="_blank" class="glossary"' .
                        $definition . '>' . $matches[0] . '</a>';
        } else {
                return '<span class="glossary"' .
                        $definition . '>' . $matches[0] . '</span>';
        }
}

function get_glossary_terms($course_id)
{
        global $mysqlMainDb;

        list($expand) = mysql_fetch_row(db_query("SELECT glossary_expand FROM `$mysqlMainDb`.course
                                                         WHERE id = $course_id"));
        if (!$expand) {
                return false;
        }

        $q = db_query("SELECT term, definition, url FROM `$mysqlMainDb`.glossary
                              WHERE course_id = $course_id GROUP BY term");

        if (mysql_num_rows($q) > intval(get_config('max_glossary_terms'))) {
                return false;
        }

        $_SESSION['glossary'] = array();
        $_SESSION['glossary_url'] = array();
        while ($row = mysql_fetch_array($q)) {
                $term = mb_strtolower($row['term'], 'UTF-8');
                $_SESSION['glossary'][$term] = $row['definition'];
                if (!empty($row['url'])) {
                        $_SESSION['glossary_url'][$term] = $row['url'];
                }
        }
        $_SESSION['glossary_course_id'] = $course_id;
        return true;
}

function set_glossary_cache()
{
        global $course_id;

        if (!isset($course_id)) {
                unset($_SESSION['glossary_terms_regexp']);
        } elseif (!isset($_SESSION['glossary']) or
                  $_SESSION['glossary_course_id'] != $course_id) {
                if (get_glossary_terms($course_id) and count($_SESSION['glossary']) > 0) {
                        // Test whether \b works correctly, workaround if not
                        if (preg_match('/α\b/u', 'α')) {
                                $spre = $spost = '\b';
                        } else {
                                $spre = '(?<=[\x01-\x40\x5B-\x60\x7B-\x7F]|^)';
                                $spost = '(?=[\x01-\x40\x5B-\x60\x7B-\x7F]|$)';
                        }
                        $_SESSION['glossary_terms_regexp'] = chr(1) . $spre . '(';
                        $begin = true;
                        foreach (array_keys($_SESSION['glossary']) as $term) {
                                $_SESSION['glossary_terms_regexp'] .= ($begin? '': '|') .
                                        preg_quote($term);
                                if ($begin) {
                                        $begin = false;
                                }
                        }
                        $_SESSION['glossary_terms_regexp'] .= ')' . $spost . chr(1) . 'ui';
                } else {
                        unset($_SESSION['glossary_terms_regexp']);
                }
        }
}

function invalidate_glossary_cache()
{
        unset($_SESSION['glossary']);
}

function redirect_to_home_page($path = '')
{
        global $urlServer;

        $path = preg_replace('+^/+', '', $path);
        header("Location: $urlServer$path");
        exit;
}


function odd_even($k, $extra='')
{
        if (!empty($extra)) {
                $extra = ' ' . $extra;
        }
        if ($k % 2 == 0) {
                return " class='even$extra'";
        } else {
                return " class='odd$extra'";
        }
}

// Translate Greek characters to Latin
function greek_to_latin($string)
{
        return str_replace(
                array(
                        'α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η', 'θ', 'ι', 'κ', 'λ', 'μ', 'ν', 'ξ', 'ο', 'π',
                        'ρ', 'σ', 'τ', 'υ', 'φ', 'χ', 'ψ', 'ω', 'Α', 'Β', 'Γ', 'Δ', 'Ε', 'Ζ', 'Η', 'Θ',
                        'Ι', 'Κ', 'Λ', 'Μ', 'Ν', 'Ξ', 'Ο', 'Π', 'Ρ', 'Σ', 'Τ', 'Υ', 'Φ', 'Χ', 'Ψ', 'Ω',
                        'ς', 'ά', 'έ', 'ή', 'ί', 'ύ', 'ό', 'ώ', 'Ά', 'Έ', 'Ή', 'Ί', 'Ύ', 'Ό', 'Ώ', 'ϊ',
                        'ΐ', 'ϋ', 'ΰ', 'Ϊ', 'Ϋ', '–'),
                array(
                        'a', 'b', 'g', 'd', 'e', 'z', 'i', 'th', 'i', 'k', 'l', 'm', 'n', 'x', 'o', 'p',
                        'r', 's', 't', 'y', 'f', 'x', 'ps', 'o', 'A', 'B', 'G', 'D', 'E', 'Z', 'H', 'Th',
                        'I', 'K', 'L', 'M', 'N', 'X', 'O', 'P', 'R', 'S', 'T', 'Y', 'F', 'X', 'Ps', 'O',
                        's', 'a', 'e', 'i', 'i', 'y', 'o', 'o', 'A', 'E', 'H', 'I', 'Y', 'O', 'O', 'i',
                        'i', 'y', 'y', 'I', 'Y', '-'),
                $string);
}

// Convert to uppercase and remove accent marks
// Limited coverage for now
function remove_accents($string)
{
        return strtr(mb_strtoupper($string, 'UTF-8'),
                array('Ά' => 'Α', 'Έ' => 'Ε', 'Ί' => 'Ι', 'Ή' => 'Η', 'Ύ' => 'Υ',
                      'Ό' => 'Ο', 'Ώ' => 'Ω', 'Ϊ' => 'Ι', 'Ϋ' => 'Υ',
                      'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
                      'Ç' => 'C', 'Ñ' => 'N', 'Ý' => 'Y',
                      'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                      'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
                      'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
                      'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U'));
}

// resize an image ($source_file) of type $type to a new size ($maxheight and $maxwidth) and copies it to path $target_file
function copy_resized_image($source_file, $type, $maxwidth, $maxheight, $target_file)
{
        if ($type == 'image/jpeg') {
                $image = @imagecreatefromjpeg($source_file);
        } elseif ($type == 'image/png') {
                $image = @imagecreatefrompng($source_file);
        } elseif ($type == 'image/gif') {
                $image = @imagecreatefromgif($source_file);
        } elseif ($type == 'image/bmp') {
                $image = @imagecreatefromwbmp($source_file);
        }
        if (!isset($image) or !$image) {
                return false;
        }
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width > $maxwidth or $height > $maxheight) {
                $xscale = $maxwidth / $width;
                $yscale = $maxheight / $height;
                if ($yscale < $xscale) {
                        $newwidth = round($width * $yscale);
                        $newheight = round($height * $yscale);
                } else {
                        $newwidth = round($width * $xscale);
                        $newheight = round($height * $xscale);
                }
                $resized = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
                return imagejpeg($resized, $target_file);
        } elseif ($type != 'image/jpeg') {
                return imagejpeg($image, $target_file);
        } else {
                return copy($source_file, $target_file);
        }
}

// Produce HTML source for an icon
function icon($name, $title=null, $link=null, $attrs=null, $format='png', $link_attrs='')
{
        global $themeimg;

        if (isset($title)) {
                $title = q($title);
                $extra = "alt='$title' title='$title'";
        } else {
                $extra = "alt=''";
        }

        if (isset($attrs)) {
                $extra .= ' ' . $attrs;
        }

        $img = "<img src='$themeimg/$name.$format' $extra>";
        if (isset($link)) {
                return "<a href='$link'$link_attrs>$img</a>";
        } else {
                return $img;
        }
}

/**
 * Link for displaying user profile
 * @param type $uid
 * @param type $size
 * @param type $default = false
 * @return type
 */
function profile_image($uid, $size, $default = false)
{
        global $urlServer, $themeimg;

        if (!$default) {
                return "<img src='${urlServer}courses/userimg/${uid}_$size.jpg' title='".q(uid_to_name($uid))."'>";
        } else {
                $name = q(uid_to_name($uid));
                return "<img src='$themeimg/default_$size.jpg' title='$name' alt='$name'>";
        }
}

function canonicalize_url($url)
{
        if (!preg_match('/^[a-zA-Z0-9_-]+:/', $url)) {
                return 'http://' . $url;
        } else {
                return $url;
        }
}

function stop_output_buffering()
{
        while (ob_get_level() > 0) {
                ob_end_flush();
        }
}

// Seed mt_rand
function make_seed() {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
}

// Generate a $len length random base64 encoded alphanumeric string
// try first /dev/urandom but if not available generate pseudo-random string
function generate_secret_key($len)
{
        if (($key = read_urandom($len)) == NULL) {
                // poor man's choice
                $key = poor_rand_string($len);
        }
        return base64_encode($key);
}

// Generate a $len length pseudo random base64 encoded alphanumeric string from ASCII table
function poor_rand_string($len) {
        mt_srand(make_seed());

        $c = "";
        for ($i=0; $i<$len; $i++) {
                $c .= chr(mt_rand(0, 127));
        }

        return $c;
}

// Read $len length random string from /dev/urandom if it's available
function read_urandom($len) {
        if (@is_readable('/dev/urandom')) {
                $f=fopen('/dev/urandom', 'r');
                $urandom=fread($f, $len);
                fclose($f);
                return $urandom;
        }
        else {
                return NULL;
        }
}

// Get user admin rights from table `admin`
function get_admin_rights($user_id) {

    global $mysqlMainDb;

    $r = db_query("SELECT privilege FROM admin
                    WHERE idUser = $user_id", $mysqlMainDb);
        if ($r and mysql_num_rows($r) > 0) {
                $row = mysql_fetch_row($r);
                return $row[0];
        } else {
                return -1;
        }
}

/**
 * @brief query course status
 * @param type $course_id
 * @return course status
 */
function course_status($course_id)
{

        $status = db_query_get_single_value("SELECT visible FROM course WHERE id = $course_id");
                
        return $status;
}


/**
 * @brief get user email verification status
 * @param type $uid
 * @return verified mail or no
 */
function get_mail_ver_status($uid) {

        $res = db_query("SELECT verified_mail FROM user WHERE user_id = $uid");
        $g = mysql_fetch_row($res);
        return $g[0];
}


// check if username match for both case sensitive/insensitive
function check_username_sensitivity($posted, $dbuser) {
        if (get_config('case_insensitive_usernames')) {
                if (mb_strtolower($posted) == mb_strtolower($dbuser)) {
                        return true;
                } else {
                        return false;
                }
        } else {
                if ($posted == $dbuser) {
                        return true;
                } else {
                        return false;
                }
        }
        return false;
}


/**
 * @brief checks if user is notified via email from a given course 
 * @param type $user_id
 * @param type $course_id
 * @return boolean
 */
function get_user_email_notification($user_id, $course_id=null)
{        

        // checks if a course is active or not
        if (isset($course_id)) {
                if (course_status($course_id) == COURSE_INACTIVE) {
                        return false;
                }
        }
        // checks if user has verified his email address
        if (get_config('email_verification_required') && get_config('dont_mail_unverified_mails')) {
                $verified_mail = get_mail_ver_status($user_id);
                if ($verified_mail == EMAIL_VERIFICATION_REQUIRED
                        or $verified_mail == EMAIL_UNVERIFIED) {
                        return false;
                }
        }
        // checks if user has choosen not to be notified by email from all courses
        if (!get_user_email_notification_from_courses($user_id)) {
                return false;
        }
        if (isset($course_id)) {
        // finally checks if user has choosen not to be notified from a specific course
                $r = db_query("SELECT receive_mail FROM course_user
                                WHERE user_id = $user_id
                                AND course_id = $course_id");
                if ($r and mysql_num_rows($r) > 0) {
                        $row = mysql_fetch_row($r);
                        return $row[0];
                } else {
                        return false;
                }
        }
        return true;
}


// checks if user is notified via email from courses
function get_user_email_notification_from_courses($user_id) {
                

        $r = db_query("SELECT receive_mail FROM user WHERE user_id = $user_id");
        list($result) = mysql_fetch_row($r);
        if ($result == 1) {
                return true;
        } else {
                return false;
        }
}


// Return a list of all subdirectories of $base which contain a file named $filename
function active_subdirs($base, $filename)
{
        $dir = opendir($base);
        $out = array();
        while (($f = readdir($dir)) !== false) {
                if (is_dir($base.'/'.$f) and
                    $f != '.' and $f != '..' and
                    file_exists($base.'/'.$f.'/'.$filename)) {
                        $out[] = $f;
                }
        }
        closedir($dir);
        return $out;
}


/*
 * Delete a directory and its whole content
 *
 * @author - Hugues Peeters
 * @param  - $dirPath (String) - the path of the directory to delete
 * @return - no return !
 */
function removeDir($dirPath)
{

        /* Try to remove the directory. If it can not manage to remove it,
         * it's probable the directory contains some files or other directories,
         * and that we must first delete them to remove the original directory.
         */

        if (!@rmdir($dirPath)) // If PHP can not manage to remove the dir...
        {
                $cwd = getcwd();
                chdir($dirPath);
                $handle = opendir($dirPath) ;

                while ($element = readdir($handle)) {
                        if ( $element == "." || $element == "..") {
                                continue;       // skip current and parent directories
                        } elseif (is_file($element)) {
                                unlink($element);
                        } elseif (is_dir($element)) {
                                $dirToRemove[] = $dirPath."/".$element;
                        }
                }

                closedir ($handle) ;
                chdir($cwd);

                if (isset($dirToRemove) and sizeof($dirToRemove) > 0) {
                        foreach($dirToRemove as $j) removeDir($j) ; // recursivity
                }

                rmdir( $dirPath ) ;
        }
}

/**
 * Generate a token verifying some info
 *
 * @param  string  $info           - The info that will be verified by the token
 * @param  boolean $need_timestamp - Whether the token will include a timestamp
 * @return string  $ret            - The new token
 */
function token_generate($info, $need_timestamp=false)
{
        if ($need_timestamp) {
                $ts = sprintf('%x-', time());
        } else {
                $ts = '';
        }
        $code_key = get_config('code_key');
        return $ts . hash_hmac('ripemd160', $ts.$info, $code_key);
}

/**
 * Validate a token verifying some info
 *
 * @param  string  $info           - The info that will be verified by the token
 * @param  string  $token          - The token to verify
 * @param  int     $ts_valid_time  - Period of validity of token in seconds, if token includes a timestamp
 * @return boolean $ret            - True if the token is valid, false otherwise
 */
function token_validate($info, $token, $ts_valid_time=0)
{
        $data = explode('-', $token);
        if (count($data) > 1) {
                $timediff = time() - hexdec($data[0]);
                if ($timediff > $ts_valid_time) {
                        return false;
                }
                $token = $data[1];
                $ts = $data[0].'-';
        } else {
                $ts = '';
        }
        $code_key = get_config('code_key');
        return $token == hash_hmac('ripemd160', $ts.$info, $code_key);
}
