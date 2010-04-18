<?php
// html.inc.php - functions that return generic HTML elements, e.g. input boxes
//                or convert plain text to HTML ...
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

/**
 * Generate HTML for a redirect/confirmation page
 * @author Ivan Lucas
 * @param string $url. URL to redirect to
 * @param bool $success. (optional) TRUE = Success, FALSE = Failure
 * @param string $message. (optional) HTML message to display on the page
 *               before redirection.
 *               This parameter is optional and only required if the default
 *               success/failure will not suffice
 * @return string HTML page with redirect
 * @note Replaces confirmation_page() from versions prior to 3.35
 *       If a header HTML has already been displayed a continue link is printed
 *       a meta redirect will also be inserted, which is invalid HTML but appears
 *       to work in most browswers.
 *
 * @note The recommended way to use this function is to call it without headers/footers
 *       already displayed.
 */
function html_redirect($url, $success = TRUE, $message='')
{
    global $CONFIG, $headerdisplayed, $siterrors;

    if (!empty($_REQUEST['dashboard']))
    {
        $headerdisplayed = TRUE;
    }

    if (empty($message))
    {
        $refreshtime = 1;
    }
    elseif ($success == FALSE)
    {
        $refreshtime = 3;
    }
    else
    {
        $refreshtime = 6;
    }

    // Catch all, make refresh time slow if errors are detected
    if ($siterrors > 0)
    {
        $refreshtime = 10;
    }

    $refresh = "{$refreshtime}; url={$url}";

    $title = $GLOBALS['strPleaseWaitRedirect'];
    if (!$headerdisplayed)
    {
        if ($_SESSION['portalauth'] == TRUE)
        {
            include (APPLICATION_INCPATH . 'portalheader.inc.php');
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        }
    }
    else
    {
        echo "<meta http-equiv=\"refresh\" content=\"$refreshtime; url=$url\" />\n";
    }

    echo "<h3>";
    if ($success)
    {
        echo "<span class='success'>{$GLOBALS['strSuccess']}</span>";
    }
    else
    {
        echo "<span class='failure'>{$GLOBALS['strFailed']}</span>";
    }

    if (!empty($message))
    {
        echo ": {$message}";
    }

    echo "</h3>";
    if (empty($_REQUEST['dashboard']))
    {
        echo "<h4>{$GLOBALS['strPleaseWaitRedirect']}</h4>";
        if ($headerdisplayed)
        {
            echo "<p align='center'><a href=\"{$url}\">{$GLOBALS['strContinue']}</a></p>";
        }
    }
    // TODO 3.35 Add a link to refresh the dashlet if this is run inside a dashlet

    if ($headerdisplayed)
    {
        if ($_SESSION['portalauth'] == TRUE)
        {
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
    }
}


/**
 * Returns a HTML string for a checkbox
 * @author Ivan Lucas
 * @param string $name The HTML name attribute
 * @param mixed $state
 * @param string $value. (optional) Value, state is used if blank
 * @param string $attributes. (optional) Extra attributes for input tag
 * @note the 'state' value should be a 1, yes, true or 0, no, false
 * @return string HTML
 */
function html_checkbox($name, $state, $value ='', $attributes = '')
{

    if ($state === TRUE) $state = 'TRUE';
    if ($state === FALSE) $state = 'FALSE';
    if ($state === 1 OR $state === 'Yes' OR $state === 'yes' OR
        $state === 'true' OR $state === 'TRUE')
    {
        if ($value == '') $value = $state;
        $html = "<input type='checkbox' checked='checked' name='{$name}' id='{$name}' value='{$value}' {$attributes} />" ;
    }
    else
    {
        if ($value == '') $value = $state;
        $html = "<input type='checkbox' name='{$name}' id='{$name}' value='{$value}' {$attributes} />" ;
    }
//     $html .= "(state:$state)";
    return $html;
}


/**
 * Returns HTML for a gravatar (Globally recognised avatar)
 * @author Ivan Lucas
 * @param string $email - Email address
 * @param int $size - Size in pixels (Default 32)
 * @param bool $hyperlink - Make a link back to gravatar.com, default TRUE
 * @return string - HTML img tag
 * @note See http://en.gravatar.com/site/implement/ for implementation guide
 */
function gravatar($email, $size = 32, $hyperlink = TRUE)
{
    global $CONFIG, $iconset;
    $default = $CONFIG['default_gravatar'];

    if (isset( $_SERVER['HTTPS']) && (strtolower( $_SERVER['HTTPS'] ) != 'off' ))
    {
        // Secure
        $grav_url = "https://secure.gravatar.com";
    }
    else
    {
        $grav_url = "http://www.gravatar.com";
    }
    $grav_url .= "/avatar.php?";
    $grav_url .= "gravatar_id=".md5(strtolower($email));
    $grav_url .= "&amp;default=".urlencode($CONFIG['default_gravatar']);
    $grav_url .= "&amp;size=".$size;
    $grav_url .= "&amp;rating=G";

    if ($hyperlink) $html = "<a href='http://site.gravatar.com/'>";
    $html .= "<img src='{$grav_url}' width='{$size}' height='{$size}' alt='' ";
    $html .= "class='gravatar' />";
    if ($hyperlink) $html .= "</a>";

    return $html;
}


/**
 * Produces HTML for a percentage indicator
 * @author Ivan Lucas
 * @param int $percent. Number between 0 and 100
 * @return string HTML
 */
function percent_bar($percent)
{
    if ($percent == '') $percent = 0;
    if ($percent < 0) $percent = 0;
    if ($percent > 100) $percent = 100;
    // #B4D6B4;
    $html = "<div class='percentcontainer'>";
    $html .= "<div class='percentbar' style='width: {$percent}%;'>  {$percent}&#037;";
    $html .= "</div></div>\n";
    return $html;
}

/**
 * Return HTML for a table column header (th and /th) with links for sorting
 * Filter parameter can be an assocative array containing fieldnames and values
 * to pass on the url for data filtering purposes
 * @author Ivan Lucas
 * @param string $colname. Column name
 * @param string $coltitle. Column title (to display in the table header)
 * @param bool $sort Whether to sort the column
 * @param string $order ASC or DESC
 * @param array $filter assoc. array of variables to pass on the link url
 * @param string $defaultorder The order to display by default (a = ASC, d = DESC)
 * @param string $width cell width
 * @return string HTML
 */
function colheader($colname, $coltitle, $sort = FALSE, $order='', $filter='', $defaultorder='a', $width='')
{
    global $CONFIG;
    if ($width !=  '')
    {
        $html = "<th width='".intval($width)."%'>";
    }
    else
    {
        $html = "<th>";
    }

    $qsappend='';
    if (!empty($filter) AND is_array($filter))
    {
        foreach ($filter AS $key => $var)
        {
            if ($var != '') $qsappend .= "&amp;{$key}=".urlencode($var);
        }
    }
    else
    {
        $qsappend='';
    }

    if ($sort==$colname)
    {
        //if ($order=='') $order=$defaultorder;
        if ($order=='a')
        {
            $html .= "<a href='{$_SERVER['PHP_SELF']}?sort=$colname&amp;order=d{$qsappend}'>{$coltitle}</a> ";
            $html .= "<img src='{$CONFIG['application_webpath']}images/sort_a.png' width='5' height='5' alt='{$GLOBALS['strSortAscending']}' /> ";
        }
        else
        {
            $html .= "<a href='{$_SERVER['PHP_SELF']}?sort=$colname&amp;order=a{$qsappend}'>{$coltitle}</a> ";
            $html .= "<img src='{$CONFIG['application_webpath']}images/sort_d.png' width='5' height='5' alt='{$GLOBALS['strSortDescending']}' /> ";
        }
    }
    else
    {
        if ($sort === FALSE) $html .= "{$coltitle}";
        else $html .= "<a href='{$_SERVER['PHP_SELF']}?sort=$colname&amp;order={$defaultorder}{$qsappend}'>{$coltitle}</a> ";
    }
    $html .= "</th>";
    return $html;
}


/**
 * Takes an array and makes an HTML selection box
 * @author Ivan Lucas
 * @param array $array - The array of options to display in the drop-down
 * @param string $name - The HTML name attribute (also used for id)
 * @param mixed $setting - The value to pre-select
 * @param string $attributes - Extra attributes for the select tag
 * @param mixed $usekey - (optional) Set the option value to be the array key instead
 *                        of the array value.
 *                        When TRUE the array key will be used as the option value
 *                        When FALSE the array value will be usedoption value
 *                        When NULL the function detects which is most appropriate
 * @param bool $multi - When TRUE a multiple selection box is returned and $setting
 *                      can be an array of values to pre-select
 * @retval string HTML select element
 */
function array_drop_down($array, $name, $setting='', $attributes='', $usekey = NULL, $multi = FALSE)
{
    if ($multi AND substr($name, -2) != '[]') $name .= '[]';
    $html = "<select name='$name' id='$name' ";
    if (!empty($attributes))
    {
         $html .= "$attributes ";
    }
    if ($multi)
    {
        $items = count($array);
        if ($items > 5) $size = floor($items / 3);
        if ($size > 10) $size = 10;
        $html .= "multiple='multiple' size='$size' ";
    }
    $html .= ">\n";

    if ($usekey === '')
    {
        if ((array_key_exists($setting, $array) AND
            in_array((string)$setting, $array) == FALSE) OR
            $usekey == TRUE)
        {
            $usekey = TRUE;
        }
        else
        {
            $usekey = FALSE;
        }
    }

    foreach ($array AS $key => $value)
    {
        $value = htmlentities($value, ENT_COMPAT, $GLOBALS['i18ncharset']);
        if ($usekey)
        {
            $html .= "<option value='$key'";
            if ($multi === TRUE AND is_array($setting))
            {
                if (in_array($key, $setting))
                {
                    $html .= " selected='selected'";
                }
            }
            elseif ($key == $setting)
            {
                $html .= " selected='selected'";
            }

        }
        else
        {
            $html .= "<option value='$value'";
            if ($multi === TRUE AND is_array($setting))
            {
                if (in_array($value, $setting))
                {
                    $html .= " selected='selected'";
                }
            }
            elseif ($value == $setting)
            {
                $html .= " selected='selected'";
            }
        }

        $html .= ">{$value}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Prints a user alert message, these are errors caused by users
 * that can be corrected by users, as opposed to system errors that should
 * use trigger_error() instead
 * @author Ivan Lucas
 * @param string $message The message to display
 * @param int severity. Same as php error constants so
 *                      E_USER_ERROR / 256
 *                      E_USER_WARNING / 512
 *                      E_USER_NOTICE / 1024
 * @param string $helpcontext (optional) - will display a help link. [?]
 *              to the given help context
 * @return string HTML
 * @note user_alert message should be displayed in the users local language
 * and should offer a 'next step' or help, where appropriate
 *
 *  E_USER_NOTICE would indicate pure information, nothing is wrong
 *  E_USER_WARNING would indicate that something is wrong, but nothing needs correcting
 *  E_USER_ERROR would indicate that something is wrong and needs to be corrected
 *               (not a system problem though!)
 *
 */
function user_alert($message, $severity, $helpcontext = '')
{
    switch ($severity)
    {
        case E_USER_ERROR:
            $class = 'alert error';
            $info = $GLOBALS['strError'];
        break;

        case E_USER_WARNING:
            $class = 'alert warning';
            $info = $GLOBALS['strWarning'];
        break;

        case E_USER_NOTICE:
        default:
            $class = 'alert info';
            $info = $GLOBALS['strInfo'];
    }
    $html = "<p class='{$class}'>";
    if (!empty($helpcontext)) $html .= help_link($helpcontext);
    $html .= "<strong>{$info}</strong>: {$message}";
    $html .= "</p>";

    return $html;
}


/**
 * Output the html for an icon
 *
 * @param string $filename filename of the string, minus extension, we assume .png
 * @param int $size size of the icon, from: 12, 16, 32
 * @param string $alt alt text of the icon (optional)
 * @param string $title (optional)
 * @param string $id ID attribute (optional)
 * @return string $html icon html
 * @author Kieran Hogg, Ivan Lucas
 */
function icon($filename, $size='', $alt='', $title='', $id='')
{
    global $iconset, $CONFIG;

    if (empty($iconset)) $iconset = $_SESSION['userconfig']['iconset'];
    $sizes = array(12, 16, 32);

    if (!in_array($size, $sizes) OR empty($size))
    {
        trigger_error("Incorrect image size for '{$filename}.png' ", E_USER_WARNING);
        $size = 16;
    }

    $file = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."images/icons/{$iconset}";
    $file .= "/{$size}x{$size}/{$filename}.png";

    $urlpath = "{$CONFIG['application_webpath']}images/icons/{$iconset}";
    $urlpath .= "/{$size}x{$size}/{$filename}.png";

    if (!file_exists($file))
    {
        $alt = "Missing icon: '$filename.png', ($file) size {$size}";
        if ($CONFIG['debug']) trigger_error($alt, E_USER_WARNING);
        $urlpath = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."images/icons/sit";
        $urlpath .= "/16x16/blank.png";
    }
    $icon = "<img src=\"{$urlpath}\"";
    if (!empty($alt))
    {
        $icon .= " alt=\"{$alt}\" ";
    }
    else
    {
        $alt = $filename;
        $icon .= " alt=\"{$alt}\" ";
    }
    if (!empty($title))
    {
        $icon .= " title=\"{$title}\"";
    }
    else
    {
        $icon .= " title=\"{$alt}\"";
    }

    if (!empty($id))
    {
        $icon .= " id=\"{$id}\"";
    }

    $icon .= " width=\"{$size}\" height=\"{$size}\" ";

    $icon .= " />";

    return $icon;
}


/**
 * Uses calendar.js to make a popup date picker
 * @author Ivan Lucas
 * @param string $formelement. form element id, eg. myform.dateinputbox
 * @return string HTML
 */
function date_picker($formelement)
{
    global $CONFIG, $iconset;

    $divid = "datediv".str_replace('.','',$formelement);
    $html = "<img src='{$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/pickdate.png' ";
    $html .= "onmouseup=\"toggleDatePicker('$divid','$formelement')\" width='16' height='16' alt='date picker' style='cursor: pointer; vertical-align: bottom;' />";
    $html .= "\n<div id='$divid' style='position: absolute;'></div>\n";
    return $html;
}


/**
 * Uses scriptaculous and AutoComplete.js to make a form text input
 * box autocomplete
 * @author Ivan Lucas
 * @param string $formelement. form element id, eg. textinput
 * @param string $action. ajaxdata.php action to return JSON data
 * @return string HTML javascript block
 * @note The page that calls this function MUST include the required
 * javascript libraries. e.g.
 *   $pagescripts = array('AutoComplete.js');
 */
function autocomplete($formelement, $action = 'autocomplete_sitecontact')
{
    $html .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
    // Disable browser autocomplete (it clashes)
    $html .= "$('$formelement').setAttribute(\"autocomplete\", \"off\"); \n";
    $html .= "new AutoComplete('{$formelement}', 'ajaxdata.php?action={$action}&s=', {\n";
    $html .= "delay: 0.25,\n";
    $html .= "resultFormat: AutoComplete.Options.RESULT_FORMAT_JSON\n";
    $html .= "}); \n//]]>\n</script>\n";

    return $html;
}


/**
 * Uses prototype.js and FormProtector.js to prevent navigating away from
 * an unsubmitted form
 * @author Ivan Lucas
 * @param string $formelement. form element id
 * @param string $message. (optional) Message to display in the warning popup
 * @return string HTML javascript block
 * @note The page that calls this function MUST include the required
 * javascript libraries. e.g.
 *   $pagescripts = array('FormProtector.js);
 */
function protectform($formelement, $message = '')
{
    global $strRememberToSave;
    if (empty($message)) $message = $strRememberToSave;
    $html = "\n<script type='text/javascript'>\n";
    $html .= "  var fp = new FormProtector('$formelement');\n";
    $html .= "  fp.setMessage('{$message}');\n";
    $html .= "</script>\n";

    return $html;
}


/**
 * A HTML Form and Select listbox for user groups, with javascript to reload page
 * @param int $selected. Group ID to preselect
 * @param string $urlargs. (Optional) text to pass after the '?' in the url (parameters)
 * @return int Number of groups found
 * @note outputs a HTML form directly
 */
function group_selector($selected, $urlargs='')
{
    $gsql = "SELECT * FROM `{$GLOBALS['dbGroups']}` ORDER BY name";
    $gresult = mysql_query($gsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    while ($group = mysql_fetch_object($gresult))
    {
        $grouparr[$group->id] = $group->name;
    }
    $numgroups = mysql_num_rows($gresult);

    if (!empty($urlargs)) $urlargs = "&amp;{$urlargs}";
    if ($numgroups >= 1)
    {
        echo "<form action='{$_SERVER['PHP_SELF']}?{$urlargs}' class='filterform' method='get'>";
        echo "{$GLOBALS['strGroup']}: <select name='choosegroup' onchange='window.location.href=this.options[this.selectedIndex].value'>";
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=all{$urlargs}'";
        if ($selected == 'all') echo " selected='selected'";
        echo ">{$GLOBALS['strAll']}</option>\n";
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=allonline{$urlargs}'";
        if ($selected == 'allonline') echo " selected='selected'";
        echo ">{$GLOBALS['strAllOnline']}</option>\n";
        foreach ($grouparr AS $groupid => $groupname)
        {
            echo "<option value='{$_SERVER['PHP_SELF']}?gid={$groupid}{$urlargs}'";
            if ($groupid == $selected) echo " selected='selected'";
            echo ">{$groupname}</option>\n";
        }
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=0{$urlargs}'";
        if ($selected === '0') echo " selected='selected'";
        echo ">{$GLOBALS['strUsersNoGroup']}</option>\n";
        echo "</select>\n";
        echo "</form>\n";
    }

    return $numgroups;
}


/**
 * prints the HTML for a drop down list of incident status names (EXCLUDING 'CLOSED'),
 * with the given name and with the given id selected.
 * @author Ivan Lucas
 * @param string $name. Text to use for the HTML select name and id attributes
 * @param int $id. Status ID to preselect
 * @param bool $disabled. Disable the select box when TRUE
 * @return string. HTML.
 */
function incidentstatus_drop_down($name, $id, $disabled = FALSE)
{
    global $dbIncidentStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbIncidentStatus}` WHERE id<>2 AND id<>7 AND id<>10 ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) < 1)
    {
        trigger_error("Zero rows returned", E_USER_WARNING);
    }

    $html = "<select id='{$name}' name='{$name}'";
    if ($disabled)
    {
        $html .= " disabled='disabled' ";
    }
    $html .= ">";

    while ($statuses = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($statuses->id == $id)
        {
            $html .= "selected='selected' ";
        }

        $html .= "value='{$statuses->id}'";
        $html .= ">{$GLOBALS[$statuses->name]}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Return HTML for a select box of closing statuses
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of Closing Status to pre-select. None selected if 0 or blank.
 * @todo Requires database i18n
 * @return string. HTML
 */
function closingstatus_drop_down($name, $id, $required = FALSE)
{
    global $dbClosingStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbClosingStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($statuses = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($statuses->id == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$statuses->id}'>";
        if (isset($GLOBALS[$statuses->name]))
        {
            $html .= $GLOBALS[$statuses->name];
        }
        else
        {
            $html .= $statuses->name;
        }
        $html .= "</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box of user statuses
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of User Status to pre-select. None selected if 0 or blank.
 * @param bool $userdisable. (optional). When TRUE an additional option is given to allow disabling of accounts
 * @return string. HTML
 */
function userstatus_drop_down($name, $id = 0, $userdisable = FALSE)
{
    global $dbUserStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbUserStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='$name'>\n";
    if ($userdisable)
    {
        $html .= "<option class='disable' selected='selected' value='0'>ACCOUNT DISABLED</option>\n";
    }

    while ($statuses = mysql_fetch_object($result))
    {
        if ($statuses->id > 0)
        {
            $html .= "<option ";
            if ($statuses->id == $id)
            {
                $html .= "selected='selected' ";
            }
            $html .= "value='{$statuses->id}'>";
            $html .= "{$GLOBALS[$statuses->name]}</option>\n";
        }
    }
    $html .= "</select>\n";

    return $html;
}





/**
 * Return HTML for a select box of user statuses with javascript to effect changes immediately
 * Includes two extra options for setting Accepting yes/no
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of User Status to pre-select. None selected if 0 or blank.
 * @return string. HTML
 */
function userstatus_bardrop_down($name, $id)
{
    global $dbUserStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbUserStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select id='userstatus_dropdown' name='$name' title='{$GLOBALS['strSetYourStatus']}' ";
    $html .= "onchange=\"set_user_status();\" onblur=\"toggle_status_drop_down();\">";
//onchange=\"if ";
//$html .= "(this.options[this.selectedIndex].value != 'null') { ";
//$html .= "window.open(this.options[this.selectedIndex].value,'_top') }\
    $html .= "\n";
    while ($statuses = mysql_fetch_object($result))
    {
        if ($statuses->id > 0)
        {
            $html .= "<option ";
            if ($statuses->id == $id)
            {
                $html .= "selected='selected' ";
            }

            $html .= "value='{$statuses->id}'>";
            $html .= "{$GLOBALS[$statuses->name]}</option>\n";
        }
    }
    $html .= "<option value='Yes' class='enable seperator'>";
    $html .= "{$GLOBALS['strAccepting']}</option>\n";
    $html .= "<option value='No' class='disable'>{$GLOBALS['strNotAccepting']}";
    $html .= "</option></select>\n";

    return $html;
}


/**
 * Return HTML for a select box of user email templates
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of Template to pre-select. None selected if 0 or blank.
 * @param string $type. Type to display.
 * @return string. HTML
 */
function emailtemplate_drop_down($name, $id, $type)
{
    global $dbEmailTemplates;
    // INL 22Apr05 Added a filter to only show user templates

    $sql  = "SELECT id, name, description FROM `{$dbEmailTemplates}` WHERE type='{$type}' ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name=\"{$name}\">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($template = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if (!empty($template->description))
        {
            $html .= "title='{$template->description}' ";
        }

        if ($template->id == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$template->id}'>{$template->name}</option>";
        $html .= "\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box of priority names (with icons)
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of priority to pre-select. None selected if 0 or blank.
 * @param int $max. The maximum priority ID to list.
 * @param bool $disable. Disable the control when TRUE.
 * @return string. HTML
 */
function priority_drop_down($name, $id, $max=4, $disable = FALSE)
{
    global $CONFIG, $iconset;
    // INL 8Oct02 - Removed DB Query
    $html = "<select id='priority' name='$name' ";
    if ($disable)
    {
        $html .= "disabled='disabled'";
    }

    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/low_priority.gif); background-repeat:no-repeat;' value='1'";
    if ($id == 1)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strLow']}</option>\n";
    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/med_priority.gif); background-repeat:no-repeat;' value='2'";
    if ($id == 2)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strMedium']}</option>\n";
    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/high_priority.gif); background-repeat:no-repeat;' value='3'";
    if ($id==3)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strHigh']}</option>\n";
    if ($max >= 4)
    {
        $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/crit_priority.gif); background-repeat:no-repeat;' value='4'";
        if ($id==4)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$GLOBALS['strCritical']}</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box for accepting yes/no. The given user's accepting status is displayed.
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $userid. The user ID to check
 * @return string. HTML
 */
function accepting_drop_down($name, $userid)
{
    if (user_accepting($userid) == "Yes")
    {
        $html = "<select name=\"$name\">\n";
        $html .= "<option selected='selected' value=\"Yes\">{$GLOBALS['strYes']}</option>\n";
        $html .= "<option value=\"No\">{$GLOBALS['strNo']}</option>\n";
        $html .= "</select>\n";
    }
    else
    {
        $html = "<select name=\"$name\">\n";
        $html .= "<option value=\"Yes\">{$GLOBALS['strYes']}</option>\n";
        $html .= "<option selected='selected' value=\"No\">{$GLOBALS['strNo']}</option>\n";
        $html .= "</select>\n";
}
return $html;
}


/**
 * Return HTML for a select box for escalation path
 * @param string $name. Name attribute
 * @param int $userid. The escalation path ID to pre-select
 * @return string. HTML
 */
function escalation_path_drop_down($name, $id)
{
    global $dbEscalationPaths;
    $sql  = "SELECT id, name FROM `{$dbEscalationPaths}` ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='{$name}' id='{$name}' >";
    $html .= "<option selected='selected' value='0'>{$GLOBALS['strNone']}</option>\n";
    while ($path = mysql_fetch_object($result))
    {
        $html .= "<option value='{$path->id}'";
        if ($path->id ==$id)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$path->name}</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Returns a string of HTML nicely formatted for the incident details page containing any additional
 * product info for the given incident.
 * @author Ivan Lucas
 * @param int $incidentid The incident ID
 * @return string HTML
 */
function incident_productinfo_html($incidentid)
{
    global $dbProductInfo, $dbIncidentProductInfo, $strNoProductInfo;

    // TODO extract appropriate product info rather than *
    $sql  = "SELECT *, TRIM(incidentproductinfo.information) AS info FROM `{$dbProductInfo}` AS p, {$dbIncidentProductInfo}` ipi ";
    $sql .= "WHERE incidentid = $incidentid AND productinfoid = p.id AND TRIM(p.information) !='' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        return ('<tr><td>{$strNoProductInfo}</td><td>{$strNoProductInfo}</td></tr>');
    }
    else
    {
        // generate HTML
        while ($productinfo = mysql_fetch_object($result))
        {
            if (!empty($productinfo->info))
            {
                $html = "<tr><th>{$productinfo->moreinformation}:</th><td>";
                $html .= urlencode($productinfo->info);
                $html .= "</td></tr>\n";
            }
        }
        echo $html;
    }
}


/**
 * A drop down to select from a list of open incidents
 * optionally filtered by contactid
 * @author Ivan Lucas
 * @param string $name The name attribute for the HTML select
 * @param int $id The value to select by default (not implemented yet)
 * @param int $contactid Filter the list to show incidents from a single
 contact
 * @return string HTML
 */
function incident_drop_down($name, $id, $contactid = 0)
{
    global $dbIncidents;

    $html = '';

    $sql = "SELECT * FROM `{$dbIncidents}` WHERE status != ".STATUS_CLOSED . " ";
    if ($contactid > 0) $sql .= "AND contact = {$contactid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select id='{$name}' name='{$name}' {$select}>\n";
        while ($incident = mysql_fetch_object($result))
        {
            // FIXME unfinished
            $html .= "<option value='{$incident->id}'>[{$incident->id}] - ";
            $html .= "{$incident->title}</option>";
        }

        $html .= "</select>";
    }
    else
    {
        $html = "<input type='text' name='{$name}' value='' size='10' maxlength='12' />";
    }
    return $html;
}


/**
 * Return HTML for a box to select interface style/theme
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param string $id. Chosen interface style
 * @return string.  HTML
 */
function interfacestyle_drop_down($name, $setting)
{
    $handle = opendir('.'.DIRECTORY_SEPARATOR.'styles');
    while ($file = readdir($handle))
    {
        if ($file == '.' || $file == '..')
        {
            continue;
        }
        if (is_dir('.'.DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR.$file))
        {
            $themes[$file] = ucfirst(str_replace('_', ' ', $file));
        }
    }
    asort($themes);

    $html = array_drop_down($themes, $name, $setting, '', TRUE);

    return $html;
}


/**
 * A HTML Select listbox for user groups
 * @author Ivan Lucas
 * @param string $name. name attribute to use for the select element
 * @param int $selected.  Group ID to preselect
 * @return HTML select
 */
function group_drop_down($name, $selected)
{
    global $grouparr, $numgroups;
    $html = "<select name='$name'>";
    $html .= "<option value='0'>{$GLOBALS['strNone']}</option>\n";
    if ($numgroups >= 1)
    {
        foreach ($grouparr AS $groupid => $groupname)
        {
            $html .= "<option value='$groupid'";
            if ($groupid == $selected)
            {
                $html .= " selected='selected'";
            }
            $html .= ">$groupname</option>\n";
        }
    }
    $html .= "</select>\n";
    return $html;
}



/**
 * HTML for a drop down list of products
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Product ID
 * @param bool $required.
 * @return string. HTML select
 * @note With the given name and with the given id selected.
 */
function product_drop_down($name, $id, $required = FALSE)
{
    global $dbProducts;
    // extract products
    $sql  = "SELECT id, name FROM `{$dbProducts}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='{$name}' id='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";


    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($products = mysql_fetch_object($result))
    {
        $html .= "<option value='{$products->id}'";
        if ($products->id == $id)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$products->name}</option>\n";
    }
    $html .= "</select>\n";
    return $html;

}


/**
 * HTML for a drop down list of skills (was called software)
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Software ID
 * @return HTML select
 */
function skill_drop_down($name, $id)
{
    global $now, $dbSoftware, $strEOL;

    // extract software
    $sql  = "SELECT id, name, lifetime_end FROM `{$dbSoftware}` ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='{$name}' id='{$name}' >";

    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'>{$GLOBALS['strNone']}</option>\n";
    }

    while ($software = mysql_fetch_object($result))
    {
        $html .= "<option value='{$software->id}'";
        if ($software->id == $id)
        {
            $html .= " selected='selected'";
        }

        $html .= ">{$software->name}";
        $lifetime_start = mysql2date($software->lifetime_start);
        $lifetime_end = mysql2date($software->lifetime_end);
        if ($lifetime_end > 0 AND $lifetime_end < $now)
        {
            $html .= " ({$strEOL})";
        }
        $html .= "</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}



/**
 * Generates a HTML dropdown of software products
 * @author Kieran Hogg
 * @param string $name. name/id to use for the select element
 * @return HTML select
 */
function softwareproduct_drop_down($name, $id, $productid, $visibility='internal')
{
    global $dbSoftware, $dbSoftwareProducts;
    // extract software
    $sql  = "SELECT id, name FROM `{$dbSoftware}` AS s, ";
    $sql .= "`{$dbSoftwareProducts}` AS sp WHERE s.id = sp.softwareid ";
    $sql .= "AND productid = '$productid' ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) >=1)
    {
        $html = "<select name='$name' id='$name'>";

        if ($visibility == 'internal' AND $id == 0)
        {
            $html .= "<option selected='selected' value='0'></option>\n";
        }
        elseif ($visiblity = 'external' AND $id == 0)
        {
            $html .= "<option selected='selected' value=''>{$GLOBALS['strUnknown']}</option>\n";
        }

        while ($software = mysql_fetch_object($result))
        {
            $html .= "<option";
            if ($software->id == $id)
            {
                $html .= " selected='selected'";
            }
            $html .= " value='{$software->id}'>{$software->name}</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html = "-";
    }

    return $html;
}


/**
 * A HTML Select listbox for vendors
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Vendor ID to preselect
 * @param bool $required whether the field is required
 * @return HTML select
 */
function vendor_drop_down($name, $id, $required = FALSE)
{
    global $dbVendors;
    $sql = "SELECT id, name FROM `{$dbVendors}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='$name'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($row = mysql_fetch_object($result))
    {
        $html .= "<option";
        if ($row->id == $id)
        {
            $html .= " selected='selected'";
        }
        $html .= " value='{$row->id}'>{$row->name}</option>\n";
    }
    $html .= "</select>";

    return $html;
}


/**
 * A HTML Select listbox for Site Types
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Site Type ID to preselect
 * @todo TODO i18n needed site types
 * @return HTML select
 */
function sitetype_drop_down($name, $id)
{
    global $dbSiteTypes;
    $sql = "SELECT typeid, typename FROM `{$dbSiteTypes}` ORDER BY typename ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html .= "<select name='$name'>\n";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($obj = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($obj->typeid == $id)
        {
            $html .="selected='selected' ";
        }

        $html .= "value='{$obj->typeid}'>{$obj->typename}</option>\n";
    }
    $html .= "</select>";
    return $html;
}


/**
 * Returns the HTML for a drop down list of upported products for the given contact and with the
 * given name and with the given product selected
 * @author Ivan Lucas
 * @todo FIXME this should use the contract and not the contact
 */
function supported_product_drop_down($name, $contactid, $productid)
{
    global $CONFIG, $dbSupportContacts, $dbMaintenance, $dbProducts, $strXIncidentsLeft;

    $sql = "SELECT *, p.id AS productid, p.name AS productname FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
    $sql .= "WHERE sc.maintenanceid = m.id AND m.product = p.id ";
    $sql .= "AND sc.contactid='$contactid'";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if ($CONFIG['debug']) $html .= "<!-- Original product {$productid}-->";
    $html .= "<select name=\"$name\">\n";
    if ($productid == 0)
    {
        $html .= "<option selected='selected' value='0'>No Contract - Not Product Related</option>\n";
    }

    if ($productid == -1)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($products = mysql_fetch_objecy($result))
    {
        $remainingstring = sprintf($strXIncidentsLeft, incidents_remaining($products->incidentpoolid));
        $html .= "<option ";
        if ($productid == $products->productid)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$products->productid}'>";
        $html .= servicelevel_name($products->servicelevelid)." ".$products->productname.", Exp:".date($CONFIG['dateformat_shortdate'], $products->expirydate).", $remainingstring";
        $html .= "</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * A HTML Select listbox for user roles
 * @author Ivan Lucas
 * @param string $name. name to use for the select element
 * @param int $id. Role ID to preselect
 * @return HTML select
 */
function role_drop_down($name, $id)
{

    global $dbRoles;
    $sql  = "SELECT id, rolename FROM `{$dbRoles}` ORDER BY rolename ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html = "<select name='{$name}'>";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($role = mysql_fetch_object($result))
    {
        $html .= "<option value='{$role->id}'";
        if ($role->id == $id)
        {
            $html .= " selected='selected'";
        }

        $html .= ">{$role->rolename}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Generates a HTML drop down of sites within SiT!
 * @param string $name The name of the field
 * @param int $id The ID of the selected item
 * @param bool $required Whether this is a mandetory field, defaults to false
 * @param bool $showinactive Whether to show the sites marked inactive, defaults to false
 * @return string The HTML for the drop down
 */
function site_drop_down($name, $id, $required = FALSE, $showinactive = FALSE)
{
    global $dbSites, $strEllipsis;
    $sql  = "SELECT id, name, department FROM `{$dbSites}` ";
    if (!$showinactive)  $sql .= "WHERE active = 'true' ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);

    $html = "<select name='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">\n";
    if ($id == 0)
    {
        $html .="<option selected='selected' value='0'></option>\n";
    }

    while ($sites = mysql_fetch_object($result))
    {
        $text = $sites->name;
        if (!empty($sites->department))
        {
            $text.= ", ".$sites->department;
        }

        if (strlen($text) >= 55)
        {
            $text = mb_substr(trim($text), 0, 55, 'UTF-8').$strEllipsis;
        }
        else
        {
            $text = $text;
        }

        $html .= "<option ";
        if ($sites->id == $id)
        {
            $html .= "selected='selected' ";
        }

        $html .= "value='{$sites->id}'>{$text}</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Prints the HTML for a drop down list of maintenance contracts
 * @param string $name. name of the drop down box
 * @param int $id. the contract id to preselect
 * @param int $siteid. Show records from this SiteID only, blank for all sites
 * @param array $excludes. Hide contracts with ID's in this array
 * @param bool $return. Whether to return to HTML or echo
 * @param bool $showonlyactive. True show only active (with a future expiry date), false shows all
 */
function maintenance_drop_down($name, $id, $siteid = '', $excludes = '', $return = FALSE, $showonlyactive = FALSE, $adminid = '', $sla = FALSE)
{
    global $GLOBALS, $now;
    // TODO make maintenance_drop_down a hierarchical selection box sites/contracts
    // extract all maintenance contracts
    $sql  = "SELECT s.name AS sitename, p.name AS productname, m.id AS id ";
    $sql .= "FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbSites']}` AS s, `{$GLOBALS['dbProducts']}` AS p ";
    $sql .= "WHERE site = s.id AND product = p.id ";
    if (!empty($siteid)) $sql .= "AND s.id = {$siteid} ";

    if ($showonlyactive)
    {
        $sql .= "AND (m.expirydate > {$now} OR m.expirydate = -1) ";
    }

    if ($adminid != '')
    {
      $sql .= "AND admincontact = '{$adminid}' ";
    }
	
    if ($sla !== FALSE)
    {
        $sql .= "AND servicelevelid = '{$sla}' ";
    }

    $sql .= "ORDER BY s.name ASC";
    $result = mysql_query($sql);
    $results = 0;
    // print HTML
    $html .= "<select name='{$name}'>";
    if ($id == 0 AND $results > 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($maintenance = mysql_fetch_object($result))
    {
        if (!is_array($excludes) OR (is_array($excludes) AND !in_array($maintenance->id, $excludes)))
        {
            $html .= "<option ";
            if ($maintenance->id == $id)
            {
                $html .= "selected='selected' ";
            }
            if (!empty($siteid))
            {
                $html .= "value='{$maintenance->id}'>{$maintenance->productname}</option>";
            }
            else
            {
                $html .= "value='{$maintenance->id}'>{$maintenance->sitename} | {$maintenance->productname}</option>";
            }
            $html .= "\n";
            $results++;
        }
    }

    if ($results == 0)
    {
        $html .= "<option>{$GLOBALS['strNoRecords']}</option>";
    }
    $html .= "</select>";

    if ($return)
    {
        return $html;
    }
    else
    {
        echo $html;
    }
}


//  prints the HTML for a drop down list of resellers, with the given name and with the given id
// selected.                                                  */
function reseller_drop_down($name, $id)
{
    global $dbResellers;
    $sql  = "SELECT id, name FROM `{$dbResellers}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    // print HTML
    echo "<select name='{$name}'>";

    if ($id == 0 OR empty($id))
    {
        echo "<option selected='selected' value='0'></option>\n";
    }
    else
    {
        echo "<option value='0'></option>\n";
    }

    while ($resellers = mysql_fetch_object($result))
    {
        echo "<option ";
        if ($resellers->id == $id)
        {
            echo "selected='selected' ";
        }

        echo "value='{$resellers->id}'>{$resellers->name}</option>";
        echo "\n";
    }

    echo "</select>";
}


//  prints the HTML for a drop down list of
// licence types, with the given name and with the given id
// selected.
function licence_type_drop_down($name, $id)
{
    global $dbLicenceTypes;
    $sql  = "SELECT id, name FROM `{$dbLicenceTypes}` ORDER BY name ASC";
    $result = mysql_query($sql);

    // print HTML
    echo "<select name='{$name}'>";

    if ($id == 0)
    {
        echo "<option selected='selected' value='0'></option>\n";
    }

    while ($licencetypes = mysql_fetch_object($result))
    {
        echo "<option ";
        if ($licencetypes->id == $id)
        {
            echo "selected='selected' ";
        }

        echo "value='{$licencetypes->id}'>{$licencetypes->name}</option>";
        echo "\n";
    }

    echo "</select>";
}


function holidaytype_drop_down($name, $id)
{
    $holidaytype[HOL_HOLIDAY] = $GLOBALS['strHoliday'];
    $holidaytype[HOL_SICKNESS] = $GLOBALS['strAbsentSick'];
    $holidaytype[HOL_WORKING_AWAY] = $GLOBALS['strWorkingAway'];
    $holidaytype[HOL_TRAINING] = $GLOBALS['strTraining'];
    $holidaytype[HOL_FREE] = $GLOBALS['strCompassionateLeave'];

    $html = "<select name='$name'>";
    if ($id == 0)
    {
        $html .= "<option selected value='0'></option>\n";
    }

    foreach ($holidaytype AS $htypeid => $htype)
    {
        $html .= "<option";
        if ($htypeid == $id)
        {
            $html .= " selected='selected'";
        }
        $html .= " value='{$htypeid}'>{$htype}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * HTML select box listing substitute engineers
 * @author Ivan Lucas
 */
function software_backup_dropdown($name, $userid, $softwareid, $backupid)
{
    global $dbUsers, $dbUserSoftware, $dbSoftware;
    $sql = "SELECT *, u.id AS userid FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s, `{$dbUsers}` AS u ";
    $sql .= "WHERE us.softwareid = s.id ";
    $sql .= "AND s.id = '{$softwareid}' ";
    $sql .= "AND userid != '{$userid}' AND u.status > 0 ";
    $sql .= "AND us.userid = u.id ";
    $sql .= " ORDER BY realname";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $countsw = mysql_num_rows($result);
    if ($countsw >= 1)
    {
        $html = "<select name='{$name}'>\n";
        $html .= "<option value='0'";
        if ($user->userid == 0) $html .= " selected='selected'";
        $html .= ">{$GLOBALS['strNone']}</option>\n";
        while ($user = mysql_fetch_object($result))
        {
            $html .= "<option value='{$user->userid}'";
            if ($user->userid == $backupid) $html .= " selected='selected'";
            $html .= ">{$user->realname}</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html .= "<input type='hidden' name='$name' value='0' />{$GLOBALS['strNoneAvailable']}";
    }
    return ($html);
}


// FIXME use this instead of hardcoding tabs
function draw_tabs($tabsarray, $selected='')
{
    if ($selected == '') $selected = key($tabsarray);
    $html .= "<div class='tabcontainer'>";
    $html .= "<ul class='tabnav'>";
    foreach ($tabsarray AS $tab => $url)
    {
        $html .= "<li><a href='$url'";
        if (strtolower($tab) == strtolower($selected))
        {
            $html .= " class='active'";
        }
        $tab = str_replace('_', ' ', $tab);
        $html .= ">$tab</a></li>\n";
    }
    $html .= "</ul>";
    $html .= "</div>";

    return ($html);
}


/**
 * Converts BBcode to HTML
 * @author Paul Heaney
 * @param string $text. Text with BBCode
 * @return string HTML
 */
function bbcode($text)
{
    global $CONFIG;
    $bbcode_regex = array(0 => "/\[b\](.*?)\[\/b\]/s",
                        1 => "/\[i\](.*?)\[\/i\]/s",
                        2 => "/\[u\](.*?)\[\/u\]/s",
                        3 => "/\[quote\](.*?)\[\/quote\]/s",
                        4 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        //5 => "/\[url\](.*?)\[\/url\]/s",
                        6 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        7 => "/\[img\](.*?)\[\/img\]/s",
                        8 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        9 => "/\[color\](.*?)\[\/color\]/s",
                        10 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        11 => "/\[size\](.*?)\[\/size\]/s",
                        12 => "/\[code\](.*?)\[\/code\]/s",
                        13 => "/\[hr\]/s",
                        14 => "/\[s\](.*?)\[\/s\]/s",
                        15 => "/\[\[att\=(.*?)]](.*?)\[\[\/att]]/s",
                        16 => "/\[url=(.+?)\](.+?)\[\/url\]/is");

    $bbcode_replace = array(0 => "<strong>$1</strong>",
                            1 => "<em>$1</em>",
                            2 => "<u>$1</u>",
                            3 => "<blockquote><p>$1</p></blockquote>",
                            4 => "<blockquote cite=\"$1\"><p>$1 said:<br />$2</p></blockquote>",
                            //5 => '<a href="$1" title="$1">$1</a>',
                            6 => "<a href=\"$1\" title=\"$1\">$2</a>",
                            7 => "<img src=\"$1\" alt=\"User submitted image\" />",
                            8 => "<span style=\"color:$1\">$2</span>",
                            9 => "<span style=\"color:red;\">$1</span>",
                            10 => "<span style=\"font-size:$1\">$2</span>",
                            11 => "<span style=\"font-size:large\">$1</span>",
                            12 => "<code>$1</code>",
                            13 => "<hr />",
                            14 => "<span style=\"text-decoration:line-through\">$1</span>",
                            15 => "<a href=\"{$CONFIG['application_webpath']}download.php?id=$1\">$2</a>",
                            16 => "<a href=\"$1\">$2</a>");

    $html = preg_replace($bbcode_regex, $bbcode_replace, $text);
    return $html;
}




function strip_bbcode_tooltip($text)
{
    $bbcode_regex = array(0 => '/\[url\](.*?)\[\/url\]/s',

                        1 => '/\[url\=(.*?)\](.*?)\[\/url\]/s',
                        2 => '/\[color\=(.*?)\](.*?)\[\/color\]/s',
                        3 => '/\[size\=(.*?)\](.*?)\[\/size\]/s',
                        4 => '/\[blockquote\=(.*?)\](.*?)\[\/blockquote\]/s',
                        5 => '/\[blockquote\](.*?)\[\/blockquote\]/s',
                        6 => "/\[s\](.*?)\[\/s\]/s");
    $bbcode_replace = array(0 => '$1',
                            1 => '$2',
                            2 => '$2',
                            3 => '$2',
                            4 => '$2',
                            5 => '$1',
                            6 => '$1'
                            );

    return preg_replace($bbcode_regex, $bbcode_replace, $text);
}


/**
 * Produces a HTML toolbar for use with a textarea or input box for entering bbcode
 * @author Ivan Lucas
 * @param string $elementid. HTML element ID of the textarea or input
 * @return string HTML
 */
function bbcode_toolbar($elementid)
{
    $html = "\n<div class='bbcode_toolbar'>BBCode: ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[b]', '[/b]')\">B</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[i]', '[/i]')\">I</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[u]', '[/u]')\">U</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[s]', '[/s]')\">S</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[quote]', '[/quote]')\">Quote</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[url]', '[/url]')\">Link</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[img]', '[/img]')\">Img</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[color]', '[/color]')\">Color</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[size]', '[/size]')\">Size</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[code]', '[/code]')\">Code</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '', '[hr]')\">HR</a> ";
    $html .= "</div>\n";
    return $html;
}


function parse_updatebody($updatebody, $striptags = TRUE)
{
    if (!empty($updatebody))
    {
        $updatebody = str_replace("&lt;hr&gt;", "[hr]\n", $updatebody);
        if ($striptags)
        {
            $updatebody = strip_tags($updatebody);
        }
        else
        {
            $updatebody = str_replace("<hr>", "", $updatebody);
        }
        $updatebody = nl2br($updatebody);
        $updatebody = str_replace("&amp;quot;", "&quot;", $updatebody);
        $updatebody = str_replace("&amp;gt;", "&gt;", $updatebody);
        $updatebody = str_replace("&amp;lt;", "&lt;", $updatebody);
        // Insert path to attachments
        //new style
        $updatebody = preg_replace("/\[\[att\=(.*?)\]\](.*?)\[\[\/att\]\]/","$2", $updatebody);
        //old style
        $updatebody = preg_replace("/\[\[att\]\](.*?)\[\[\/att\]\]/","$1", $updatebody);
        //remove tags that are incompatable with tool tip
        $updatebody = strip_bbcode_tooltip($updatebody);
        //then show compatable BBCode
        $updatebody = bbcode($updatebody);
        if (strlen($updatebody) > 490) $updatebody .= '...';
    }

    return $updatebody;
}


/**
 * Produces a HTML form for adding a note to an item
 * @param $linkid int The link type to be used
 * @param $refid int The ID of the item this note if for
 * @return string The HTML to display
 */
function add_note_form($linkid, $refid)
{
    global $now, $sit, $iconset;
    $html = "<form name='addnote' action='note_add.php' method='post'>";
    $html .= "<div class='detailhead note'> <div class='detaildate'>".readable_date($now)."</div>\n";
    $html .= icon('note', 16, $GLOBALS['strNote ']);
    $html .= " ".sprintf($GLOBALS['strNewNoteByX'], user_realname($sit[2]))."</div>\n";
    $html .= "<div class='detailentry note'>";
    $html .= "<textarea rows='3' cols='40' name='bodytext' style='width: 94%; margin-top: 5px; margin-bottom: 5px; margin-left: 3%; margin-right: 3%; background-color: transparent; border: 1px dashed #A2A86A;'></textarea>";
    if (!empty($linkid))
    {
        $html .= "<input type='hidden' name='link' value='{$linkid}' />";
    }
    else
    {
        $html .= "&nbsp;{$GLOBALS['strLInk']} <input type='text' name='link' size='3' />";
    }

    if (!empty($refid))
    {
        $html .= "<input type='hidden' name='refid' value='{$refid}' />";
    }
    else
    {
        $html .= "&nbsp;{$GLOBALS['strRefID']} <input type='text' name='refid' size='4' />";
    }

    $html .= "<input type='hidden' name='action' value='addnote' />";
    $html .= "<input type='hidden' name='rpath' value='{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}' />";
    $html .= "<div style='text-align: right'><input type='submit' value='{$GLOBALS['strAddNote']}' /></div>\n";
    $html .= "</div>\n";
    $html .= "</form>";
    return $html;
}


/**
 * Produces HTML of all notes assigned to an item
 * @param $linkid int The link type
 * @param $refid int The ID of the item the notes are linked to
 * @param $delete bool Whether its possible to delet notes (default TRUE)
 * @return string HTML of the notes
 */
function show_notes($linkid, $refid, $delete = TRUE)
{
    global $sit, $iconset, $dbNotes;
    $sql = "SELECT * FROM `{$dbNotes}` WHERE link='{$linkid}' AND refid='{$refid}' ORDER BY timestamp DESC, id DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $countnotes = mysql_num_rows($result);
    if ($countnotes >= 1)
    {
        while ($note = mysql_fetch_object($result))
        {
            $html .= "<div class='detailhead note'> <div class='detaildate'>".readable_date(mysqlts2date($note->timestamp));
            if ($delete)
            {
                $html .= "<a href='note_delete.php?id={$note->id}&amp;rpath=";
                $html .= "{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}' ";
                $html .= "onclick=\"return confirm_action('{$strAreYouSureDelete}', true);\">";
                $html .= icon('delete', 16)."</a>";
            }
            $html .= "</div>\n"; // /detaildate
            $html .= icon('note', 16)." ";
            $html .= sprintf($GLOBALS['strNoteAddedBy'], user_realname($note->userid,TRUE));
            $html .= "</div>\n"; // detailhead
            $html .= "<div class='detailentry note'>";
            $html .= nl2br(bbcode($note->bodytext));
            $html .= "</div>\n";
        }
    }
    return $html;
}


/**
 * Produces a HTML dashlet 'window' for display on the dashboard
 * @author Ivan Lucas
 * @param string $dashboard. Dashboard component name.
 * @param string $dashletid. The table row ID of that we are 'drawing' this dashlet into and
 *                           the ID of the dashboard component instance as recorded in the users settings
 *                           as a single string, this is received by the dashlet from dashboard_do()
 * @param string $icon. HTML for an icon to be displayed on the dashlet window
 * @param string $title. A title for the dashlet, also displayed on the dashlet window
 * @param string $link. URL of a page to link to from the dashlet window (link on the title)
 * @param string $content. HTML content to display inside the dashlet window
 * @note This function looks for the existence of two dashboard component functions
 *       dashboard_*_display() and dashboard_*_edit(), (where * is the name of the dashlet)
 *       if these are found the dashlet will use ajax and call these functions for it's
 *       main display (and refreshing) and to edit settings.
 * @return string HTML
 */
function dashlet($dashboard, $dashletid, $icon, $title='', $link='', $content='')
{
    global $strLoading;
    if (empty($icon)) $icon = icon('dashboard', 16);
    if (empty($title)) $title = $GLOBALS['strUntitled'];
    $displayfn = "dashboard_{$dashboard}_display";
    $editfn = "dashboard_{$dashboard}_edit";

    $html .= "<div class='windowbox' id='{$dashletid}'>";
    $html .= "<div class='windowtitle'>";
    $html .= "<div class='innerwindow'>";
    if (function_exists($displayfn))
    {
        $html .= "<a href=\"javascript:get_and_display('ajaxdata.php?action=dashboard_display&amp;dashboard={$dashboard}&amp;did={$dashletid}','win{$dashletid}',true);\">";
        $html .= icon('reload', 16, '', '', "refresh{$dashletid}")."</a>";
    }

    if (function_exists($editfn))
    {
        $html .= "<a href=\"javascript:get_and_display('ajaxdata.php?action=dashboard_edit&amp;dashboard={$dashboard}&amp;did={$dashletid}','win{$dashletid}',false);\">";
        $html .= icon('edit', 16)."</a>";
    }
    $html .= "</div>";
    if (!empty($link)) $html .= "<a href=\"{$link}\">{$icon}</a> <a href=\"{$link}\">{$title}</a>";
    else $html .= "{$icon} {$title}";
    $html .= "</div>\n";
    $html .= "<div class='window' id='win{$dashletid}'>";
    $html .= $content;
    $displayfn = "dashboard_{$dashboard}_display";
    if (function_exists($displayfn))
    {
        $html .= "<script type='text/javascript'>\n//<![CDATA[\nget_and_display('ajaxdata.php?action=dashboard_display&dashboard={$dashboard}','win{$dashletid}',true);\n//]]>\n</script>\n";
    }
    $html .= "</div></div>";

    return $html;
}


/**
 * Creates a link that opens within a dashlet window
 * @author Ivan Lucas
 * @param string $dashboard. Dashboard component name.
 * @param string $dashletid. The table row ID of that we are 'drawing' this dashlet into and
 *                           the ID of the dashboard component instance as recorded in the users settings
 *                           as a single string, this is received by the dashlet from dashboard_do()
 * @param string $text. The text of the hyperlink for the user to click
 * @param string $action. edit|save|display
 edit = This is a link to a dashlet config form page
 save = Submit a dashlet config form (see $formid param)
 display = Display a regular dashlet page
 * @param array $params. Associative array of parameters to pass on the URL of the link
 * @param bool $refresh. The link will be automatically refreshed when TRUE
 * @param string $formid. The form element ID to be submitted when using 'save' action
 * @return string HTML
 */
function dashlet_link($dashboard, $dashletid, $text='', $action='', $params='', $refresh = FALSE, $formid='')
{
    if ($action == 'edit') $action = 'dashboard_edit';
    elseif ($action == 'save') $action = 'dashboard_save';
    else $action = 'dashboard_display';
    if (empty($text)) $text = $GLOBALS['strUntitled'];

    // Ensure the dashlet ID is always correct, 'win' gets prepended with each subpage
    // We only need it once
    $dashletid = 'win'.str_replace('win', '', $dashletid);

    // Convert refresh boolean to javascript text for boolean
    if ($refresh) $refresh = 'true';
    else $refresh = 'false';

    if ($action == 'dashboard_save' AND $formid == '') $formid = "{$dashboard}form";

    if ($action == 'dashboard_save') $html .= "<a href=\"javascript:ajax_save(";
    else  $html .= "<a href=\"javascript:get_and_display(";
    $html .= "'ajaxdata.php?action={$action}&dashboard={$dashboard}&did={$dashletid}";
    if (is_array($params))
    {
        foreach ($params AS $pname => $pvalue)
        {
            $html .= "&{$pname}={$pvalue}";
        }
    }
    //$html .= "&editaction=do_add&type={$type}";

    if ($action != 'dashboard_save')
    {
        $html .= "', '{$dashletid}'";
        $html .= ", $refresh";
    }
    else
    {
        $html .= "', '{$formid}'";
    }
    $html .= ");\">{$text}</a>";

    return $html;
}


/**
 * @author Paul Heaney
 */
function display_drafts($type, $result)
{
    global $iconset;
    global $id;
    global $CONFIG;

    if ($type == 'update')
    {
        $page = "incident_update.php";
        $editurlspecific = '';
    }
    else if ($type == 'email')
    {
        $page = "incident_email.php";
        $editurlspecific = "&amp;step=2";
    }

    echo "<p align='center'>{$GLOBALS['strDraftChoose']}</p>";

    $html = '';

    while ($obj = mysql_fetch_object($result))
    {
        $html .= "<div class='detailhead'>";
        $html .= "<div class='detaildate'>".date($CONFIG['dateformat_datetime'], $obj->lastupdate);
        $html .= "</div>";
        $html .= "<a href='{$page}?action=editdraft&amp;draftid={$obj->id}&amp;id={$id}{$editurlspecific}' class='info'>";
        $html .= icon('edit', 16, $GLOBALS['strDraftEdit'])."</a>";
        $html .= "<a href='{$page}?action=deletedraft&amp;draftid={$obj->id}&amp;id={$id}' class='info'>";
        $html .= icon('delete', 16, $GLOBALS['strDraftDelete'])."</a>";
        $html .= "</div>";
        $html .= "<div class='detailentry'>";
        $html .= nl2br($obj->content)."</div>";
    }

    return $html;
}


/**
 * @author Kieran Hogg
 * @param string $name. name of the html entity
 * @param string $time. the time to set it to, format 12:34
 * @return string. HTML
 * @TODO perhaps merge with the new time display function?
 */
function time_dropdown($name, $time='')
{
    if ($time)
    {
        $time = explode(':', $time);
    }

    $html = "<select name='$name'>\n";
    $html .= "<option></option>";
    for ($hours = 0; $hours < 24; $hours++)
    {
        for ($mins = 0; $mins < 60; $mins+=15)
        {
            $hours = str_pad($hours, 2, "0", STR_PAD_LEFT);
            $mins = str_pad($mins, 2, "0", STR_PAD_RIGHT);

            if ($time AND $time[0] == $hours AND $time[1] == $mins)
            {
                $html .= "<option selected='selected' value='$hours:$mins'>$hours:$mins</option>";
            }
            else
            {
                if ($time AND $time[0] == $hours AND $time[1] < $mins AND $time[1] > ($mins - 15))
                {
                    $html .= "<option selected='selected' value='$time[0]:$time[1]'>$time[0]:$time[1]</option>\n";
                }
                else
                {
                    $html .= "<option value='$hours:$mins'>$hours:$mins</option>\n";
                }
            }
        }
    }
    $html .= "</select>";
    return $html;
}


/**
 * HTML for an ajax help link
 * @author Ivan Lucas
 * @param string $context. The base filename of the popup help file in
                          help/en-GB/ (without the .txt extension)
 * @return string HTML
 */
function help_link($context)
{
    global $strHelpChar;
    $html = "<span class='helplink'>[<a href='#' tabindex='-1' onmouseover=\"";
    $html .= "contexthelp(this, '$context'";
    if ($_SESSION['portalauth'] == TRUE) $html .= ",'portal'";
    else $html .= ",'standard'";
    $html .= ");return false;\">{$strHelpChar}<span>";
    $html .= "</span></a>]</span>";

    return $html;
}





/**
 * Function to return an user error message when a file fails to upload
 * @author Paul Heaney
 * @param errorcode The error code from $_FILES['file']['error']
 * @param name The file name which was uploaded from $_FILES['file']['name']
 * @return String containing the error message (in HTML)
 */
function get_file_upload_error_message($errorcode, $name)
{
    $str = "<div class='detailinfo'>\n";

    $str .=  "An error occurred while uploading <strong>{$_FILES['attachment']['name']}</strong>";

    $str .=  "<p class='error'>";
    switch ($errorcode)
    {
        case UPLOAD_ERR_INI_SIZE:
            $str .= "The file exceded the maximum size set in PHP";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $str .=  "The uploaded file was too large";
            break;
        case UPLOAD_ERR_PARTIAL:
            $str .=  "The file was only partially uploaded";
            break;
        case UPLOAD_ERR_NO_FILE:
            $str .=  "No file was uploaded";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $str .=  "Temporary folder is missing";
            break;
        default:
            $str .=  "An unknown file upload error occurred";
            break;
    }
    $str .=  "</p>";
    $str .=  "</div>";

    return $str;
}


/**
 * Return the html of contract detatils
 * @author Kieran Hogg
 * @param int $maintid - ID of the contract
 * @param string $mode. 'internal' or 'external'
 * @return array of supported contracts, NULL if none
 * @todo FIXME not quite generic enough for a function ?
 */
function contract_details($id, $mode='internal')
{
    global $CONFIG, $iconset, $dbMaintenance, $dbSites, $dbResellers, $dbLicenceTypes, $now;

    $sql  = "SELECT m.*, m.notes AS maintnotes, s.name AS sitename, ";
    $sql .= "r.name AS resellername, lt.name AS licensetypename ";
    $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, ";
    $sql .= "`{$dbResellers}` AS r, `{$dbLicenceTypes}` AS lt ";
    $sql .= "WHERE s.id = m.site ";
    $sql .= "AND m.id='{$id}' ";
    $sql .= "AND m.reseller = r.id ";
    $sql .= "AND (m.licence_type IS NULL OR m.licence_type = lt.id) ";
    if ($mode == 'external') $sql .= "AND m.site = '{$_SESSION['siteid']}'";

    $maintresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    $maint = mysql_fetch_object($maintresult);

    $html = "<table align='center' class='vertical'>";
    $html .= "<tr><th>{$GLOBALS['strContract']} {$GLOBALS['strID']}:</th>";
    $html .= "<td><h3>".icon('contract', 32)." ";
    $html .= "{$maint->id}</h3></td></tr>";
    $html .= "<tr><th>{$GLOBALS['strStatus']}:</th><td>";
    if ($maint->term == 'yes')
    {
        $html .= "<strong>{$GLOBALS['strTerminated']}</strong>";
    }
    else
    {
        $html .= $GLOBALS['strActive'];
    }

    if ($maint->expirydate < $now AND $maint->expirydate != '-1')
    {
        $html .= "<span class='expired'>, {$GLOBALS['strExpired']}</span>";
    }
    $html .= "</td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strSite']}:</th>";

    if ($mode == 'internal')
    {
        $html .= "<td><a href=\"site_details.php?id=".$maint->site."\">".$maint->sitename."</a></td></tr>";
    }
    else
    {
        $html .= "<td><a href=\"sitedetails.php\">".$maint->sitename."</a></td></tr>";
    }
    $html .= "<tr><th>{$GLOBALS['strAdminContact']}:</th>";

    if ($mode == 'internal')
    {
        $html .= "<td><a href=\"contact_details.php?id=";
        $html .= "{$maint->admincontact}\">";
        $html .= contact_realname($maint->admincontact)."</a></td></tr>";
    }
    else
    {
        $html .= "<td><a href='contactdetails.php?id={$maint->admincontact}'>";
        $html .= contact_realname($maint->admincontact)."</a></td></tr>";
    }

    $html .= "<tr><th>{$GLOBALS['strReseller']}:</th><td>";

    if (empty($maint->resellername))
    {
        $html .= $GLOBALS['strNoReseller'];
    }
    else
    {
        $html .= $maint->resellername;
    }
    $html .= "</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strProduct']}:</th><td>".product_name($maint->product)."</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strIncidents']}:</th>";
    $html .= "<td>";
    $incidents_remaining = $maint->incident_quantity - $maint->incidents_used;

    if ($maint->incident_quantity == 0)
    {
        $quantity = $GLOBALS['strUnlimited'];
    }
    else
    {
        $quantity = $maint->incident_quantity;
    }

    $html .= sprintf($GLOBALS['strUsedNofN'], $maint->incidents_used, $quantity);
    if ($maint->incidents_used >= $maint->incident_quantity AND
        $maint->incident_quantity != 0)
    {
        $html .= " ({$GLOBALS['strZeroRemaining']})";
    }

    $html .= "</td></tr>";
    if ($maint->licence_quantity != '0')
    {
        $html .= "<tr><th>{$GLOBALS['strLicense']}:</th>";
        $html .= "<td>{$maint->licence_quantity} {$maint->licensetypename}</td></tr>\n";
    }

    $html .= "<tr><th>{$GLOBALS['strServiceLevel']}:</th><td>".servicelevel_name($maint->servicelevelid)."</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strExpiryDate']}:</th><td>";
    if ($maint->expirydate == '-1')
    {
        $html .= "{$GLOBALS['strUnlimited']}";
    }
    else
    {
        $html .= ldate($CONFIG['dateformat_date'], $maint->expirydate);
    }

    $html .= "</td></tr>";

    if ($mode == 'internal')
    {
        $timed = db_read_column('timed', $GLOBALS['dbServiceLevels'], $maint->servicelevelid);
        if ($timed == 'yes') $timed = TRUE;
        else $timed = FALSE;
        $html .= "<tr><th>{$GLOBALS['strService']}</th><td>";
        $html .= contract_service_table($id, $timed);
        $html .= "</td></tr>\n";

        if ($timed)
        {
            $html .= "<tr><th>{$GLOBALS['strBalance']}</th><td>{$CONFIG['currency_symbol']}".number_format(get_contract_balance($id, TRUE, TRUE), 2);
            $multiplier = get_billable_multiplier(strtolower(date('D', $now)), date('G', $now));
            $html .= " (&cong;".contract_unit_balance($id, TRUE, TRUE)." units)";
            $html .= "</td></tr>";
        }
    }

    if ($maint->maintnotes != '' AND $mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strNotes']}:</th><td>{$maint->maintnotes}</td></tr>";
    }
    $html .= "</table>";

    if ($mode == 'internal')
    {
        $html .= "<p align='center'>";
        $html .= "<a href=\"contract_edit.php?action=edit&amp;maintid=$id\">{$GLOBALS['strEditContract']}</a> | ";
        $html .= "<a href='contract_add_service.php?contractid={$id}'>{$GLOBALS['strAddService']}</a></p>";
    }
    $html .= "<h3>{$GLOBALS['strContacts']}</h3>";

    if (mysql_num_rows($maintresult) > 0)
    {
        if ($maint->allcontactssupported == 'yes')
        {
            $html .= "<p class='info'>{$GLOBALS['strAllSiteContactsSupported']}</p>";
        }
        else
        {
            $allowedcontacts = $maint->supportedcontacts;

            $supportedcontacts = supported_contacts($id);
            $numberofcontacts = 0;

                $numberofcontacts = sizeof($supportedcontacts);
                if ($allowedcontacts == 0)
                {
                    $allowedcontacts = $GLOBALS['strUnlimited'];
                }
                $html .= "<table align='center'>";
                $supportcount = 1;

                if ($numberofcontacts > 0)
                {
                    foreach ($supportedcontacts AS $contact)
                    {
                        $html .= "<tr><th>{$GLOBALS['strContact']} #{$supportcount}:</th>";
                        $html .= "<td>".icon('contact', 16)." ";
                        if ($mode == 'internal')
                        {
                            $html .= "<a href=\"contact_details.php?";
                        }
                        else
                        {
                            $html .= "<a href=\"contactdetails.php?";
                        }
                        $html .= "id={$contact}\">".contact_realname($contact)."</a>, ";
                        $html .= contact_site($contact). "</td>";

                        if ($mode == 'internal')
                        {
                            $html .= "<td><a href=\"contract_delete_contact.php?contactid=".$contact."&amp;maintid=$id&amp;context=maintenance\">{$GLOBALS['strRemove']}</a></td></tr>\n";
                        }
                        else
                        {
                            $html .= "<td><a href=\"{$_SERVER['PHP_SELF']}?id={$id}&amp;contactid=".$contact."&amp;action=remove\">{$GLOBALS['strRemove']}</a></td></tr>\n";
                        }
                        $supportcount++;
                    }
                    $html .= "</table>";
                }
                else
                {
                    $html .= "<p class='info'>{$GLOBALS['strNoRecords']}<p>";
                }
        }
        if ($maint->allcontactssupported != 'yes')
        {
            $html .= "<p align='center'>";
            $html .= sprintf($GLOBALS['strUsedNofN'],
                            "<strong>".$numberofcontacts."</strong>",
                            "<strong>".$allowedcontacts."</strong>");
            $html .= "</p>";

            if ($numberofcontacts < $allowedcontacts OR $allowedcontacts == 0 AND $mode == 'internal')
            {
                $html .= "<p align='center'><a href='contract_add_contact.php?maintid={$id}&amp;siteid={$maint->site}&amp;context=maintenance'>";
                $html .= "{$GLOBALS['strAddContact']}</a></p>";
            }
            else
            {
                $html .= "<h3>{$GLOBALS['strAddContact']}</h3>";
                $html .= "<form action='{$_SERVER['PHP_SELF']}?id={$id}&amp;action=";
                $html .= "add' method='post' >";
                $html .= "<p align='center'>{$GLOBLAS['strAddNewSupportedContact']} ";
                $html .= contact_site_drop_down('contactid',
                                                'contactid',
                                                maintenance_siteid($id),
                                                supported_contacts($id));
                $html .= help_link('NewSupportedContact');
                $html .= " <input type='submit' value='{$GLOBALS['strAdd']}' /></p></form>";
            }
            if ($mode == 'external')
            {
                $html .= "<p align='center'><a href='addcontact.php'>";
                $html .= "{$GLOBALS['strAddNewSiteContact']}</a></p>";
            }
        }

        $html .= "<br />";
        $html .= "<h3>{$GLOBALS['strSkillsSupportedUnderContract']}:</h3>";
        // supported software
        $sql = "SELECT * FROM `{$GLOBALS[dbSoftwareProducts]}` AS sp, `{$GLOBALS[dbSoftware]}` AS s ";
        $sql .= "WHERE sp.softwareid = s.id AND productid='{$maint->product}' ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result)>0)
        {
            $html .="<table align='center'>";
            while ($software = mysql_fetch_object($result))
            {
                $software->lifetime_end = mysql2date($software->lifetime_end);
                $html .= "<tr><td> ".icon('skill', 16)." ";
                if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
                {
                    $html .= "<span class='deleted'>";
                }
                $html .= $software->name;
                if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
                {
                    $html .= "</span>";
                }
                $html .= "</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        else
        {
            $html .= "<p align='center'>{$GLOBALS['strNone']} / {$GLOBALS['strUnknown']}<p>";
        }
    }
    else
    {
        $html = "<p align='center'>{$GLOBALS['strNothingToDisplay']}</p>";
    }

    return $html;
}


/**
 * Function to return a HTML table row with two columns.
 * Giving radio boxes for groups and if the level is 'management' then you are able to view the users (de)selcting
 * @param string $title - text to go in the first column
 * @param string $level either management or engineer, management is able to (de)select users
 * @param int $groupid  Defalt group to select
 * @param string $type - Type of buttons to use either radio or checkbox
 * @return table row of format <tr><th /><td /></tr>
 * @author Paul Heaney
 */
function group_user_selector($title, $level="engineer", $groupid, $type='radio')
{
    global $dbUsers, $dbGroups;
    $str .= "<tr><th>{$title}</th>";
    $str .= "<td align='center'>";

    $sql = "SELECT DISTINCT(g.name), g.id FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $sql .= "WHERE u.status > 0 AND u.groupid = g.id ORDER BY g.name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    while ($row = mysql_fetch_object($result))
    {
        if ($type == 'radio')
        {
            $str .= "<input type='radio' name='group' id='{$row->name}' onclick='groupMemberSelect(\"{$row->name}\", \"TRUE\")' ";
        }
        elseif ($type == 'checkbox')
        {
            $str .= "<input type='checkbox' name='{$row->name}' id='{$row->name}' onclick='groupMemberSelect(\"{$row->name}\", \"FALSE\")' ";
        }

        if ($groupid == $row->id)
        {
            $str .= " checked='checked' ";
            $groupname = $row->name;
        }

        $str .= "/>{$row->name} \n";
    }

    $str .="<br />";


    $sql = "SELECT u.id, u.realname, g.name FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $sql .= "WHERE u.status > 0 AND u.groupid = g.id ORDER BY username";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if ($level == "management")
    {
        $str .= "<select name='users[]' id='include' multiple='multiple' size='20'>";
    }
    elseif ($level == "engineer")
    {
        $str .= "<select name='users[]' id='include' multiple='multiple' size='20' style='display:none'>";
    }

    while ($row = mysql_fetch_object($result))
    {
        $str .= "<option value='{$row->id}'>{$row->realname} ({$row->name})</option>\n";
    }
    $str .= "</select>";
    $str .= "<br />";
    if ($level == "engineer")
    {
        $visibility = " style='display:none'";
    }

    $str .= "<input type='button' id='selectall' onclick='doSelect(true, \"include\")' value='Select All' {$visibility} />";
    $str .= "<input type='button' id='clearselection' onclick='doSelect(false, \"include\")' value='Clear Selection' {$visibility} />";

    $str .= "</td>";
    $str .= "</tr>\n";

    // FIXME make this XHTML valid
    $str .= "<script type='text/javascript'>\n//<![CDATA[\ngroupMemberSelect(\"{$groupname}\", \"TRUE\");\n//]]>\n</script>";

    return $str;
}


/**
 * Output html for the 'time to next action' box
 * Used in add incident and update incident
 * @param string $formid. HTML ID of the form containing the controls
 * @return $html string html to output
 * @author Kieran Hogg
 * @TODO populate $id
 */
function show_next_action($formid)
{
    global $now, $strAM, $strPM;
    $html = "{$GLOBALS['strPlaceIncidentInWaitingQueue']}<br />";

    $oldtimeofnextaction = incident_timeofnextaction($id); //FIXME $id never populated
    if ($oldtimeofnextaction < 1)
    {
        $oldtimeofnextaction = $now;
    }
    $wait_time = ($oldtimeofnextaction - $now);

    $na_days = floor($wait_time / 86400);
    $na_remainder = $wait_time % 86400;
    $na_hours = floor($na_remainder / 3600);
    $na_remainder = $wait_time % 3600;
    $na_minutes = floor($na_remainder / 60);
    if ($na_days < 0) $na_days = 0;
    if ($na_hours < 0) $na_hours = 0;
    if ($na_minutes < 0) $na_minutes = 0;

    $html .= "<label>";
    $html .= "<input checked='checked' type='radio' name='timetonextaction' ";
    $html .= "id='ttna_none' onchange=\"update_ttna();\" onclick=\"this.blur();\" ";
//     $html .= "onclick=\"$('timetonextaction_days').value = ''; window.document.updateform.";
//     $html .= "timetonextaction_hours.value = ''; window.document.updateform."; timetonextaction_minutes.value = '';\"
    $html .= " value='None' />{$GLOBALS['strNo']}";
    $html .= "</label><br />";

    $html .= "<label><input type='radio' name='timetonextaction' ";
    $html .= "id='ttna_time' value='time' onchange=\"update_ttna();\" onclick=\"this.blur();\" />";
    $html .= "{$GLOBALS['strForXDaysHoursMinutes']}</label><br />\n";
    $html .= "<span id='ttnacountdown'";
    if (empty($na_days) AND
        empty($na_hours) AND
        empty($na_minutes))
    {
        $html .= " style='display: none;'";
    }
    $html .= ">";
    $html .= "&nbsp;&nbsp;&nbsp;<input name='timetonextaction_days' ";
    $html .= " id='timetonextaction_days' value='{$na_days}' maxlength='3' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strDays']}&nbsp;";
    $html .= "<input maxlength='2' name='timetonextaction_hours' ";
    $html .= "id='timetonextaction_hours' value='{$na_hours}' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strHours']}&nbsp;";
    $html .= "<input maxlength='2' name='timetonextaction_minutes' id='";
    $html .= "timetonextaction_minutes' value='{$na_minutes}' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strMinutes']}";
    $html .= "<br />\n</span>";

    $html .= "<label><input type='radio' name='timetonextaction' id='ttna_date' ";
    $html .= "value='date' onchange=\"update_ttna();\" onclick=\"this.blur();\" />";
    $html .= "{$GLOBALS['strUntilSpecificDateAndTime']}</label><br />\n";
    $html .= "<div id='ttnadate' style='display: none;'>";
    $html .= "<input name='date' id='timetonextaction_date' size='10' value='{$date}' ";
    $html .= "onclick=\"$('ttna_date').checked = true;\" /> ";
    $html .= date_picker("{$formid}.timetonextaction_date");
    $html .= " <select name='timeoffset' id='timeoffset' ";
    $html .= "onclick=\"$('ttna_date').checked = true;\" >";
    $html .= "<option value='0'></option>";
    $html .= "<option value='0'>8:00 $strAM</option>";
    $html .= "<option value='1'>9:00 $strAM</option>";
    $html .= "<option value='2'>10:00 $strAM</option>";
    $html .= "<option value='3'>11:00 $strAM</option>";
    $html .= "<option value='4'>12:00 $strPM</option>";
    $html .= "<option value='5'>1:00 $strPM</option>";
    $html .= "<option value='6'>2:00 $strPM</option>";
    $html .= "<option value='7'>3:00 $strPM</option>";
    $html .= "<option value='8'>4:00 $strPM</option>";
    $html .= "<option value='9'>5:00 $strPM</option>";
    $html .= "</select>";
    $html .= "<br />\n</div>";

    return $html;
}


/**
 * Converts emoticon text to HTML
 * Will only show emoticons if the user has chosen in their settings
 * that they would like to see them, otherwise shows original text
 * @author Kieran Hogg
 * @param string $text. Text with smileys in it
 * @return string HTML
 */
function emoticons($text)
{
    global $CONFIG;

    $html = '';
    if ($_SESSION['userconfig']['show_emoticons'] == 'TRUE')
    {
        $smiley_url = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}images/emoticons/";
        $smiley_regex = array(0 => "/\:[-]?\)/s",
                            1 => "/\:[-]?\(/s",
                            2 => "/\;[-]?\)/s",
                            3 => "/\:[-]?[pP]/s",
                            4 => "/\:[-]?@/s",
                            5 => "/\:[-]?[Oo]/s",
                            6 => "/\:[-]?\\$/s",
                            7 => "/\\([Yy]\)/s",
                            8 => "/\\([Nn]\)/s",
                            9 => "/\\([Bb]\)/s",
                            10 => "/\:[-]?[dD]/s"
                            );

        $smiley_replace = array(0 => "<img src='{$smiley_url}smile.png' alt='$1' title='$1' />",
                                1 => "<img src='{$smiley_url}sad.png' alt='$1' title='$1' />",
                                2 => "<img src='{$smiley_url}wink.png' alt='$1' title='$1' />",
                                3 => "<img src='{$smiley_url}tongue.png' alt='$1' title='$1' />",
                                4 => "<img src='{$smiley_url}angry.png' alt='$1' title='$1' />",
                                5 => "<img src='{$smiley_url}omg.png' alt='$1' title='$1' />",
                                6 => "<img src='{$smiley_url}embarassed.png' alt='$1' title='$1' />",
                                7 => "<img src='{$smiley_url}thumbs_up.png' alt='$1' title='$1' />",
                                8 => "<img src='{$smiley_url}thumbs_down.png' alt='$1' title='$1' />",
                                9 => "<img src='{$smiley_url}beer.png' alt='$1' title='$1' />",
                                10 => "<img src='{$smiley_url}teeth.png' alt='$1' title='$1' />"
                                );

        $html = preg_replace($smiley_regex, $smiley_replace, $text);
    }
    else
    {
        $html = $text;
    }

    return $html;
}


/**
 * HTML for an alphabetical index of links
 * @author Ivan Lucas
 * @param string $baseurl start of a URL, the letter will be appended to this
 * @return HTML
 */
function alpha_index($baseurl = '#')
{
    global $i18nAlphabet;

    $html = '';
    if (!empty($i18nAlphabet))
    {
        $len = utf8_strlen($i18nAlphabet);
        for ($i = 0; $i < $len; $i++)
        {
            $html .= "<a href=\"{$baseurl}";
            $html .= urlencode(utf8_substr($i18nAlphabet, $i, 1))."\">";
            $html .= utf8_substr($i18nAlphabet, $i, 1)."</a> | \n";

        }
    }
    return $html;
}


/**
 * HTML for a hyperlink to hide/reveal a password field
 * @author Ivan Lucas
 */
function password_reveal_link($id)
{
    $html = "<a href=\"javascript:password_reveal('$id')\" id=\"link{$id}\">{$GLOBALS['strReveal']}</a>";
    return $html;
}


function qtype_listbox($type)
{
    global $CONFIG, $strRating, $strOptions, $strMultipleOptions, $strText;

    $html .= "<select name='type'>\n";
    $html .= "<option value='rating'";
    if ($type == 'rating') $html .= " selected='selected'";
    $html .= ">{$strRating}</option>";

    $html .= "<option value='options'";
    if ($type=='options') $html .= " selected='selected'";
    $html .= ">{$strOptions}</option>";

    $html .= "<option value='multioptions'";
    if ($type == 'multioptions') $html .= " selected='selected'";
    $html .= ">{$strMultipleOptions}</option>";

    $html .= "<option value='text'";
    if ($type == 'text') $html .= " selected='selected'";
    $html .= ">{$strText}</option>";

    $html .= "</select>\n";

    return $html;
}



function feedback_qtype_listbox($type)
{
    global $CONFIG, $strRating, $strOptions, $strMultipleOptions, $strText;

    $html .= "<select name='type'>\n";
    $html .= "<option value='rating'";
    if ($type == 'rating') $html .= " selected='selected'";
    $html .= ">{$strRating}</option>";

    $html .= "<option value='options'";
    if ($type == 'options') $html .= " selected='selected'";
    $html .= ">{$strOptions}</option>";

    $html .= "<option value='multioptions'";
    if ($type == 'multioptions') $html .= " selected='selected'";
    $html .= ">{$strMultipleOptions}</option>";

    $html .= "<option value='text'";
    if ($type == 'text') $html .= " selected='selected'";
    $html .= ">{$strText}</option>";

    $html .= "</select>\n";

    return $html;
}


/**
 * @author Ivan Lucas
 */
function getattachmenticon($filename)
{
    global $CONFIG, $iconset;
    // Maybe sometime make this use mime typesad of file extensions
    $ext = strtolower(substr($filename, (strlen($filename)-3) , 3));
    $imageurl = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/unknown.png";

    $type_image = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/file_image.png";

    $filetype[] = "gif";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "jpg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "bmp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "png";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "pcx";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "xls";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/spreadsheet.png";
    $filetype[] = "csv";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/spreadsheet.png";
    $filetype[] = "zip";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "arj";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/zip.png";
    $filetype[] = "rar";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/rar.png";
    $filetype[] = "cab";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "lzh";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "txt";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[] = "f90";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = "f77";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = "inf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "ins";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "adm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "f95";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = "cpp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_cpp.png";
    $filetype[] = "for";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = ".pl";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_pl.png";
    $filetype[] = ".py";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_py.png";
    $filetype[] = "rtm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/misc_doc.png";
    $filetype[] = "doc";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "rtf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "wri";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "wri";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "pdf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/pdf.png";
    $filetype[] = "htm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "tml";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "wav";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[] = "mp3";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[] = "voc";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[] = "exe";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "com";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "nlm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "evt";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[] = "log";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[] = "386";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "dll";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "asc";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[] = "asp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "avi";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/video.png";
    $filetype[] = "bkf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tar.png";
    $filetype[] = "chm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/man.png";
    $filetype[] = "hlp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/man.png";
    $filetype[] = "dif";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[] = "hta";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "reg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/resource.png";
    $filetype[] = "dmp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/core.png";
    $filetype[] = "ini";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "jpe";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "mht";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "msi";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "aot";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "pgp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "dbg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "axt";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png"; // zen text
    $filetype[] = "rdp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "sig";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/document.png";
    $filetype[] = "tif";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "ttf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/font_ttf.png";
    $filetype[] = "for";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/font_bitmap.png";
    $filetype[] = "vbs";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "vbe";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "bat";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "wsf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "cmd";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "scr";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "xml";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/xml.png";
    $filetype[] = "zap";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = ".ps";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/postscript.png";
    $filetype[] = ".rm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/real_doc.png";
    $filetype[] = "ram";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/real_doc.png";
    $filetype[] = "vcf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/vcard.png";
    $filetype[] = "wmf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/vectorgfx.png";
    $filetype[] = "cer";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/document.png";
    $filetype[] = "tmp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/unknown.png";
    $filetype[] = "cap";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "tr1";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = ".gz";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "tar";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tar.png";
    $filetype[] = "nfo";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/info.png";
    $filetype[] = "pal";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/colorscm.png";
    $filetype[] = "iso";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/cdimage.png";
    $filetype[] = "jar";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/java_src.png";
    $filetype[] = "eml";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/message.png";
    $filetype[] = ".sh";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "bz2";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "out";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[] = "cfg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";

    $cnt = count($filetype);
    if ( $cnt > 0 )
    {
        $a = 0;
        $stop = FALSE;
        while ($a < $cnt && $stop == FALSE)
        {
            if ($ext == $filetype[$a])
            {
                $imageurl = $imgurl[$a];
                $stop = TRUE;
            }
            $a++;
        }
    }
    unset ($filetype);
    unset ($imgurl);
    return $imageurl;
}


/**
 * Print a listbox of countries
 * @author Ivan Lucas
 * @param string $name - HTML select 'name' attribute
 * @param string $country - Country to pre-select (default to config file setting)
 * @param string $extraattributes - Extra attributes to put on the select tag
 * @return HTML
 * @note if the $country given is not in the list, an editable input box is given instead of a select box
 * @todo TODO i18n country list (How do we do this?)
 */
function country_drop_down($name, $country, $extraattributes='')
{
    global $CONFIG;
    if ($country == '') $country = $CONFIG['home_country'];

    if ($country == 'UK') $country = 'UNITED KINGDOM';
    $countrylist[] = 'ALBANIA';
    $countrylist[] = 'ALGERIA';
    $countrylist[] = 'AMERICAN SAMOA';
    $countrylist[] = 'ANDORRA';
    $countrylist[] = 'ANGOLA';
    $countrylist[] = 'ANGUILLA';
    $countrylist[] = 'ANTIGUA';
    $countrylist[] = 'ARGENTINA';
    $countrylist[] = 'ARMENIA';
    $countrylist[] = 'ARUBA';
    $countrylist[] = 'AUSTRALIA';
    $countrylist[] = 'AUSTRIA';
    $countrylist[] = 'AZERBAIJAN';
    $countrylist[] = 'BAHAMAS';
    $countrylist[] = 'BAHRAIN';
    $countrylist[] = 'BANGLADESH';
    $countrylist[] = 'BARBADOS';
    $countrylist[] = 'BELARUS';
    $countrylist[] = 'BELGIUM';
    $countrylist[] = 'BELIZE';
    $countrylist[] = 'BENIN';
    $countrylist[] = 'BERMUDA';
    $countrylist[] = 'BHUTAN';
    $countrylist[] = 'BOLIVIA';
    $countrylist[] = 'BONAIRE';
    $countrylist[] = 'BOSNIA HERZEGOVINA';
    $countrylist[] = 'BOTSWANA';
    $countrylist[] = 'BRAZIL';
    $countrylist[] = 'BRUNEI';
    $countrylist[] = 'BULGARIA';
    $countrylist[] = 'BURKINA FASO';
    $countrylist[] = 'BURUNDI';
    $countrylist[] = 'CAMBODIA';
    $countrylist[] = 'CAMEROON';
    $countrylist[] = 'CANADA';
    $countrylist[] = 'CANARY ISLANDS';
    $countrylist[] = 'CAPE VERDE ISLANDS';
    $countrylist[] = 'CAYMAN ISLANDS';
    $countrylist[] = 'CENTRAL AFRICAN REPUBLIC';
    $countrylist[] = 'CHAD';
    $countrylist[] = 'CHANNEL ISLANDS';
    $countrylist[] = 'CHILE';
    $countrylist[] = 'CHINA';
    $countrylist[] = 'COLOMBIA';
    $countrylist[] = 'COMOROS ISLANDS';
    $countrylist[] = 'CONGO';
    $countrylist[] = 'COOK ISLANDS';
    $countrylist[] = 'COSTA RICA';
    $countrylist[] = 'CROATIA';
    $countrylist[] = 'CUBA';
    $countrylist[] = 'CURACAO';
    $countrylist[] = 'CYPRUS';
    $countrylist[] = 'CZECH REPUBLIC';
    $countrylist[] = 'DENMARK';
    $countrylist[] = 'DJIBOUTI';
    $countrylist[] = 'DOMINICA';
    $countrylist[] = 'DOMINICAN REPUBLIC';
    $countrylist[] = 'ECUADOR';
    $countrylist[] = 'EGYPT';
    $countrylist[] = 'EL SALVADOR';
    $countrylist[] = 'EQUATORIAL GUINEA';
    $countrylist[] = 'ERITREA';
    $countrylist[] = 'ESTONIA';
    $countrylist[] = 'ETHIOPIA';
    $countrylist[] = 'FAROE ISLANDS';
    $countrylist[] = 'FIJI ISLANDS';
    $countrylist[] = 'FINLAND';
    $countrylist[] = 'FRANCE';
    $countrylist[] = 'FRENCH GUINEA';
    $countrylist[] = 'GABON';
    $countrylist[] = 'GAMBIA';
    $countrylist[] = 'GEORGIA';
    $countrylist[] = 'GERMANY';
    $countrylist[] = 'GHANA';
    $countrylist[] = 'GIBRALTAR';
    $countrylist[] = 'GREECE';
    $countrylist[] = 'GREENLAND';
    $countrylist[] = 'GRENADA';
    $countrylist[] = 'GUADELOUPE';
    $countrylist[] = 'GUAM';
    $countrylist[] = 'GUATEMALA';
    $countrylist[] = 'GUINEA REPUBLIC';
    $countrylist[] = 'GUINEA-BISSAU';
    $countrylist[] = 'GUYANA';
    $countrylist[] = 'HAITI';
    $countrylist[] = 'HONDURAS REPUBLIC';
    $countrylist[] = 'HONG KONG';
    $countrylist[] = 'HUNGARY';
    $countrylist[] = 'ICELAND';
    $countrylist[] = 'INDIA';
    $countrylist[] = 'INDONESIA';
    $countrylist[] = 'IRAN';
    $countrylist[] = 'IRELAND, REPUBLIC';
    $countrylist[] = 'ISRAEL';
    $countrylist[] = 'ITALY';
    $countrylist[] = 'IVORY COAST';
    $countrylist[] = 'JAMAICA';
    $countrylist[] = 'JAPAN';
    $countrylist[] = 'JORDAN';
    $countrylist[] = 'KAZAKHSTAN';
    $countrylist[] = 'KENYA';
    $countrylist[] = 'KIRIBATI, REP OF';
    $countrylist[] = 'KOREA, SOUTH';
    $countrylist[] = 'KUWAIT';
    $countrylist[] = 'KYRGYZSTAN';
    $countrylist[] = 'LAOS';
    $countrylist[] = 'LATVIA';
    $countrylist[] = 'LEBANON';
    $countrylist[] = 'LESOTHO';
    $countrylist[] = 'LIBERIA';
    $countrylist[] = 'LIBYA';
    $countrylist[] = 'LIECHTENSTEIN';
    $countrylist[] = 'LITHUANIA';
    $countrylist[] = 'LUXEMBOURG';
    $countrylist[] = 'MACAU';
    $countrylist[] = 'MACEDONIA';
    $countrylist[] = 'MADAGASCAR';
    $countrylist[] = 'MALAWI';
    $countrylist[] = 'MALAYSIA';
    $countrylist[] = 'MALDIVES';
    $countrylist[] = 'MALI';
    $countrylist[] = 'MALTA';
    $countrylist[] = 'MARSHALL ISLANDS';
    $countrylist[] = 'MARTINIQUE';
    $countrylist[] = 'MAURITANIA';
    $countrylist[] = 'MAURITIUS';
    $countrylist[] = 'MEXICO';
    $countrylist[] = 'MOLDOVA, REP OF';
    $countrylist[] = 'MONACO';
    $countrylist[] = 'MONGOLIA';
    $countrylist[] = 'MONTSERRAT';
    $countrylist[] = 'MOROCCO';
    $countrylist[] = 'MOZAMBIQUE';
    $countrylist[] = 'MYANMAR';
    $countrylist[] = 'NAMIBIA';
    $countrylist[] = 'NAURU, REP OF';
    $countrylist[] = 'NEPAL';
    $countrylist[] = 'NETHERLANDS';
    $countrylist[] = 'NEVIS';
    $countrylist[] = 'NEW CALEDONIA';
    $countrylist[] = 'NEW ZEALAND';
    $countrylist[] = 'NICARAGUA';
    $countrylist[] = 'NIGER';
    $countrylist[] = 'NIGERIA';
    $countrylist[] = 'NIUE';
    $countrylist[] = 'NORWAY';
    $countrylist[] = 'OMAN';
    $countrylist[] = 'PAKISTAN';
    $countrylist[] = 'PANAMA';
    $countrylist[] = 'PAPUA NEW GUINEA';
    $countrylist[] = 'PARAGUAY';
    $countrylist[] = 'PERU';
    $countrylist[] = 'PHILLIPINES';
    $countrylist[] = 'POLAND';
    $countrylist[] = 'PORTUGAL';
    $countrylist[] = 'PUERTO RICO';
    $countrylist[] = 'QATAR';
    $countrylist[] = 'REUNION ISLAND';
    $countrylist[] = 'ROMANIA';
    $countrylist[] = 'RUSSIAN FEDERATION';
    $countrylist[] = 'RWANDA';
    $countrylist[] = 'SAIPAN';
    $countrylist[] = 'SAO TOME & PRINCIPE';
    $countrylist[] = 'SAUDI ARABIA';
    $countrylist[] = 'SENEGAL';
    $countrylist[] = 'SEYCHELLES';
    $countrylist[] = 'SIERRA LEONE';
    $countrylist[] = 'SINGAPORE';
    $countrylist[] = 'SLOVAKIA';
    $countrylist[] = 'SLOVENIA';
    $countrylist[] = 'SOLOMON ISLANDS';
    $countrylist[] = 'SOUTH AFRICA';
    $countrylist[] = 'SPAIN';
    $countrylist[] = 'SRI LANKA';
    $countrylist[] = 'ST BARTHELEMY';
    $countrylist[] = 'ST EUSTATIUS';
    $countrylist[] = 'ST KITTS';
    $countrylist[] = 'ST LUCIA';
    $countrylist[] = 'ST MAARTEN';
    $countrylist[] = 'ST VINCENT';
    $countrylist[] = 'SUDAN';
    $countrylist[] = 'SURINAME';
    $countrylist[] = 'SWAZILAND';
    $countrylist[] = 'SWEDEN';
    $countrylist[] = 'SWITZERLAND';
    $countrylist[] = 'SYRIA';
    $countrylist[] = 'TAHITI';
    $countrylist[] = 'TAIWAN';
    $countrylist[] = 'TAJIKISTAN';
    $countrylist[] = 'TANZANIA';
    $countrylist[] = 'THAILAND';
    $countrylist[] = 'TOGO';
    $countrylist[] = 'TONGA';
    $countrylist[] = 'TRINIDAD & TOBAGO';
    $countrylist[] = 'TURKEY';
    $countrylist[] = 'TURKMENISTAN';
    $countrylist[] = 'TURKS & CAICOS ISLANDS';
    $countrylist[] = 'TUVALU';
    $countrylist[] = 'UGANDA';
    // $countrylist[] = 'UK';
    $countrylist[] = 'UKRAINE';
    $countrylist[] = 'UNITED KINGDOM';
    $countrylist[] = 'UNITED STATES';
    $countrylist[] = 'URUGUAY';
    $countrylist[] = 'UTD ARAB EMIRATES';
    $countrylist[] = 'UZBEKISTAN';
    $countrylist[] = 'VANUATU';
    $countrylist[] = 'VENEZUELA';
    $countrylist[] = 'VIETNAM';
    $countrylist[] = 'VIRGIN ISLANDS';
    $countrylist[] = 'VIRGIN ISLANDS (UK)';
    $countrylist[] = 'WESTERN SAMOA';
    $countrylist[] = 'YEMAN, REP OF';
    $countrylist[] = 'YUGOSLAVIA';
    $countrylist[] = 'ZAIRE';
    $countrylist[] = 'ZAMBIA';
    $countrylist[] = 'ZIMBABWE';

    if (in_array(strtoupper($country), $countrylist))
    {
        // make drop down
        $html = "<select id=\"{$name}\" name=\"{$name}\" {$extraattributes}>";
        foreach ($countrylist as $key => $value)
        {
            $value = htmlspecialchars($value);
            $html .= "<option value='$value'";
            if ($value == strtoupper($country))
            {
                $html .= " selected='selected'";
            }
            $html .= ">$value</option>\n";
        }
        $html .= "</select>";
    }
    else
    {
        // make editable input box
        $html = "<input maxlength='100' name='{$name}' size='40' value='{$country}' {$extraattributes} />";
    }
    return $html;
}


/**
 * Recursive function to list links as a tree
 * @author Ivan Lucas
 */
function show_links($origtab, $colref, $level=0, $parentlinktype='', $direction='lr')
{
    global $dbLinkTypes, $dbLinks;
    // Maximum recursion
    $maxrecursions = 15;

    if ($level <= $maxrecursions)
    {
        $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE origtab='$origtab' ";
        if (!empty($parentlinktype)) $sql .= "AND id='{$parentlinktype}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($linktype = mysql_fetch_object($result))
        {
            // Look up links of this type
            $lsql = "SELECT * FROM `{$dbLinks}` WHERE linktype='{$linktype->id}' ";
            if ($direction == 'lr') $lsql .= "AND origcolref='{$colref}'";
            elseif ($direction == 'rl') $lsql .= "AND linkcolref='{$colref}'";
            $lresult = mysql_query($lsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($lresult) >= 1)
            {
                if (mysql_num_rows($lresult) >= 1)
                {
                    $html .= "<ul>";
                    $html .= "<li>";
                    while ($link = mysql_fetch_object($lresult))
                    {
                        $recsql = "SELECT {$linktype->selectionsql} AS recordname FROM {$linktype->linktab} WHERE ";
                        if ($direction == 'lr') $recsql .= "{$linktype->linkcol}='{$link->linkcolref}' ";
                        elseif ($direction == 'rl') $recsql .= "{$linktype->origcol}='{$link->origcolref}' ";
                        $recresult = mysql_query($recsql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                        while ($record = mysql_fetch_object($recresult))
                        {
                            if ($link->direction == 'bi')
                            {
                                $html .= "<strong>{$linktype->name}</strong> ";
                            }
                            elseif ($direction == 'lr')
                            {
                                $html .= "<strong>{$linktype->lrname}</strong> ";
                            }
                            elseif ($direction == 'rl')
                            {
                                $html .= "<strong>{$linktype->rlname}</strong> ";
                            }
                            else
                            {
                                $html = $GLOBALS['strError'];
                            }

                            if ($direction == 'lr')
                            {
                                $currentlinkref = $link->linkcolref;
                            }
                            elseif ($direction == 'rl')
                            {
                                $currentlinkref = $link->origcolref;
                            }

                            $viewurl = str_replace('%id%',$currentlinkref,$linktype->viewurl);

                            $html .= "{$currentlinkref}: ";
                            if (!empty($viewurl)) $html .= "<a href='$viewurl'>";
                            $html .= "{$record->recordname}";
                            if (!empty($viewurl)) $html .= "</a>";
                            $html .= " - ".user_realname($link->userid,TRUE);
                            $html .= show_links($linktype->linktab, $currentlinkref, $level+1, $linktype->id, $direction); // Recurse
                            $html .= "</li>\n";
                        }
                    }
                    $html .= "</ul>\n";
                }
                else
                {
                    $html .= "<p>{$GLOBALS['strNone']}</p>";
                }
            }
        }
    }
    else
    {
        $html .= "<p class='error'>{$GLOBALS['strError']}: Maximum number of {$maxrecursions} recursions reached</p>";
    }
    return $html;
}


/**
 * Interface for creating record 'links' (relationships)
 * @author Ivan Lucas
 */
function show_create_links($table, $ref)
{
    global $dbLinkTypes;
    $html .= "<p align='center'>{$GLOBALS['strAddLink']}: ";
    $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE origtab='$table' OR linktab='$table' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numlinktypes = mysql_num_rows($result);
    $rowcount = 1;
    while ($linktype = mysql_fetch_object($result))
    {
        if ($linktype->origtab == $table AND $linktype->linktab != $table)
        {
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->lrname}</a>";
        }
        elseif ($linktype->origtab != $table AND $linktype->linktab == $table)
        {
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->rlname}</a>";
        }
        else
        {
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->lrname}</a> | ";
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}&amp;dir=rl'>{$linktype->rlname}</a>";
        }

        if ($rowcount < $numlinktypes) $html .= " | ";
        $rowcount++;
    }
    $html .= "</p>";
    return $html;
}


/**
 * Shows errors from a form, if any
 * @author Kieran Hogg
 * @return string. HTML of the form errors stored in the users session
 */
function show_form_errors($formname)
{
    if ($_SESSION['formerrors'][$formname])
    {
        foreach ($_SESSION['formerrors'][$formname] as $error)
        {
            $html .= "<p class='error'>$error</p>";
        }
    }
    return $html;
}


/**
 * Output the html for a KB article
 *
 * @param int $id ID of the KB article
 * @param string $mode whether this is internal or external facing, defaults to internal
 * @return string $html kb article html
 * @author Kieran Hogg
 */
function kb_article($id, $mode='internal')
{
    global $CONFIG, $iconset;
    $id = intval($id);
    if (!is_number($id) OR $id == 0)
    {
        trigger_error("Incorrect KB ID", E_USER_ERROR);
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        exit;
    }

    $sql = "SELECT * FROM `{$GLOBALS['dbKBArticles']}` WHERE docid='{$id}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $kbarticle = mysql_fetch_object($result);

    if (empty($kbarticle->title))
    {
        $kbarticle->title = $GLOBALS['strUntitled'];
    }
    $html .= "<div id='kbarticle'";
    if ($kbarticle->distribution == 'private') $html .= " class='expired'";
    if ($kbarticle->distribution == 'restricted') $html .= " class='urgent'";
    $html .= ">";
    $html .= "<h2 class='kbtitle'>{$kbarticle->title}</h2>";

    if (!empty($kbarticle->distribution) AND $kbarticle->distribution != 'public')
    {
        $html .= "<h2 class='kbdistribution'>{$GLOBALS['strDistribution']}: ".ucfirst($kbarticle->distribution)."</h2>";
    }

    // Lookup what software this applies to
    $ssql = "SELECT * FROM `{$GLOBALS['dbKBSoftware']}` AS kbs, `{$GLOBALS['dbSoftware']}` AS s ";
    $ssql .= "WHERE kbs.softwareid = s.id AND kbs.docid = '{$id}' ";
    $ssql .= "ORDER BY s.name";
    $sresult = mysql_query($ssql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($sresult) >= 1)
    {
        $html .= "<h3>{$GLOBALS['strEnvironment']}</h3>";
        $html .= "<p>{$GLOBALS['strTheInfoInThisArticle']}:</p>\n";
        $html .= "<ul>\n";
        while ($kbsoftware = mysql_fetch_object($sresult))
        {
            $html .= "<li>{$kbsoftware->name}</li>\n";
        }
        $html .= "</ul>\n";
    }

    $csql = "SELECT * FROM `{$GLOBALS['dbKBContent']}` WHERE docid='{$id}' ";
    $cresult = mysql_query($csql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $restrictedcontent = 0;
    while ($kbcontent = mysql_fetch_object($cresult))
    {
        switch ($kbcontent->distribution)
        {
            case 'private':
                if ($mode != 'internal')
                {
                    echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
                    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
                    exit;
                }
                $html .= "<div class='kbprivate'><h3>{$kbcontent->header} (private)</h3>";
                $restrictedcontent++;
                break;
            case 'restricted':
                if ($mode != 'internal')
                {
                    echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
                    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
                    exit;
                }
                $html .= "<div class='kbrestricted'><h3>{$kbcontent->header}</h3>";
                $restrictedcontent++;
                break;
            default:
                $html .= "<div><h3>{$kbcontent->header}</h3>";
        }
        //$html .= "<{$kbcontent->headerstyle}>{$kbcontent->header}</{$kbcontent->headerstyle}>\n";
        $html .= '';
        $kbcontent->content=nl2br($kbcontent->content);
        $search = array("/(?<!quot;|[=\"]|:\/{2})\b((\w+:\/{2}|www\.).+?)"."(?=\W*([<>\s]|$))/i", "/(([\w\.]+))(@)([\w\.]+)\b/i");
        $replace = array("<a href=\"$1\">$1</a>", "<a href=\"mailto:$0\">$0</a>");
        $kbcontent->content = preg_replace("/href=\"www/i", "href=\"http://www", preg_replace ($search, $replace, $kbcontent->content));
        $html .= bbcode($kbcontent->content);
        $author[]=$kbcontent->ownerid;
        $html .= "</div>\n";
    }

    if ($restrictedcontent > 0)
    {
        $html .= "<h3>{$GLOBALS['strKey']}</h3>";
        $html .= "<p><span class='keykbprivate'>{$GLOBALS['strPrivate']}</span>".help_link('KBPrivate')." &nbsp; ";
        $html .= "<span class='keykbrestricted'>{$GLOBALS['strRestricted']}</span>".help_link('KBRestricted')."</p>";
    }


    $html .= "<h3>{$GLOBALS['strArticle']}</h3>";
    //$html .= "<strong>{$GLOBALS['strDocumentID']}</strong>: ";
    $html .= "<p><strong>{$CONFIG['kb_id_prefix']}".leading_zero(4,$kbarticle->docid)."</strong> ";
    $pubdate = mysql2date($kbarticle->published);
    if ($pubdate > 0)
    {
        $html .= "{$GLOBALS['strPublished']} ";
        $html .= ldate($CONFIG['dateformat_date'],$pubdate)."<br />";
    }

    if ($mode == 'internal')
    {
        if (is_array($author))
        {
            $author = array_unique($author);
            $countauthors = count($author);
            $count = 1;
            if ($countauthors > 1)
            {
                $html .= "<strong>{$GLOBALS['strAuthors']}</strong>:<br />";
            }
            else
            {
                $html .= "<strong>{$GLOBALS['strAuthor']}:</strong> ";
            }

            foreach ($author AS $authorid)
            {
                $html .= user_realname($authorid,TRUE);
                if ($count < $countauthors) $html .= ", " ;
                $count++;
            }
        }
    }

    $html .= "<br />";
    if (!empty($kbarticle->keywords))
    {
        $html .= "<strong>{$GLOBALS['strKeywords']}</strong>: ";
        if ($mode == 'internal')
        {
            $html .= preg_replace("/\[([0-9]+)\]/", "<a href=\"incident_details.php?id=$1\" target=\"_blank\">$0</a>", $kbarticle->keywords);
        }
        else
        {
            $html .= $kbarticle->keywords;
        }
        $html .= "<br />";
    }

    //$html .= "<h3>{$GLOBALS['strDisclaimer']}</h3>";
    $html .= "</p><hr />";
    $html .= $CONFIG['kb_disclaimer_html'];
    $html .= "</div>";

    if ($mode == 'internal')
    {
        $html .= "<p align='center'>";
        $html .= "<a href='kb.php'>{$GLOBALS['strBackToList']}</a> | ";
        $html .= "<a href='kb_article.php?id={$kbarticle->docid}'>{$GLOBALS['strEdit']}</a></p>";
    }
    return $html;
}

/**
 * Output the html for the edit site form
 *
 * @param int $site ID of the site
 * @param string $mode whether this is internal or external facing, defaults to internal
 * @return string $html edit site form html
 * @author Kieran Hogg
 */
function show_edit_site($site, $mode='internal')
{
    global $CONFIG;
    $sql = "SELECT * FROM `{$GLOBALS['dbSites']}` WHERE id='$site' ";
    $siteresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($siterow = mysql_fetch_array($siteresult))
    {
        if ($mode == 'internal')
        {
            $html .= "<h2>".icon('site', 32)." {$GLOBALS['strEditSite']}: {$site} - ";
            $html .= site_name($site)."</h2>";
        }
        else
        {
            $html .= "<h2>".icon('site', 32)." ".site_name($site)."</h2>";
        }

        $html .= "<form name='edit_site' action='{$_SERVER['PHP_SELF']}";
        $html .= "?action=update' method='post' onsubmit='return ";
        $html .= "confirm_action(\"{$GLOBALS['strAreYouSureMakeTheseChanges']}\")'>";
        $html .= "<table align='center' class='vertical'>";
        $html .= "<tr><th>{$GLOBALS['strName']}:</th>";
        $html .= "<td><input class='required' maxlength='50' name='name' size='40' value='{$siterow['name']}' />";
        $html .= " <span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strTags']}:</th><td><textarea rows='2' cols='60' name='tags'>";
            $html .= list_tags($site, TAG_SITE, false)."</textarea>\n";
        }
        $html .= "<tr><th>{$GLOBALS['strDepartment']}:</th>";
        $html .= "<td><input maxlength='50' name='department' size='40' value='{$siterow['department']}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strAddress1']}:</th>";
        $html .= "<td><input maxlength='50' name='address1'";
        $html .= "size='40' value='{$siterow['address1']}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strAddress2']}: </th><td><input maxlength='50' name='address2' size='40' value='{$siterow['address2']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCity']}:</th><td><input maxlength='255' name='city' size='40' value='{$siterow['city']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCounty']}:</th><td><input maxlength='255' name='county' size='40' value='{$siterow['county']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strPostcode']}:</th><td><input maxlength='255' name='postcode' size='40' value='{$siterow['postcode']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCountry']}:</th><td>".country_drop_down('country', $siterow['country'])."</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strTelephone']}:</th><td>";
        $html .= "<input class='required' maxlength='255' name='telephone' size='40' value='{$siterow['telephone']}' />";
        $html .= "<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strFax']}:</th><td>";
        $html .= "<input maxlength='255' name='fax' size='40' value='{$siterow['fax']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strEmail']}:</th><td>";
        $html .= "<input maxlength='255' name='email' size='40' value='{$siterow['email']}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strWebsite']}:</th><td>";
        $html .= "<input maxlength='255' name='websiteurl' size='40' value='{$siterow['websiteurl']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strSiteType']}:</th><td>\n";
        $html .= sitetype_drop_down('typeid', $siterow['typeid']);
        $html .= "</td></tr>\n";
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strSalesperson']}:</th><td>";
            $html .= user_drop_down('owner', $siterow['owner'], $accepting = FALSE, '', '', TRUE);
            $html .= "</td></tr>\n";
        }

        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strIncidentPool']}:</th>";
            $incident_pools = explode(',', "{$GLOBALS['strNone']},{$CONFIG['incident_pools']}");
            if (array_key_exists($siterow['freesupport'], $incident_pools) == FALSE)
            {
                array_unshift($incident_pools,$siterow['freesupport']);
            }
            $html .= "<td>".array_drop_down($incident_pools,'incident_pool',$siterow['freesupport'])."</td></tr>";
            $html .= "<tr><th>{$GLOBALS['strActive']}:</th><td><input type='checkbox' name='active' ";
            if ($siterow['active'] == 'true')
            {
                $html .= "checked='".$siterow['active']."'";
            }
            $html .= " value='true' /></td></tr>\n";
            $html .= "<tr><th>{$GLOBALS['strNotes']}:</th><td>";
            $html .= "<textarea rows='5' cols='30' name='notes'>{$siterow['notes']}</textarea>";
            $html .= "</td></tr>\n";
        }
        plugin_do('edit_site_form');
        $html .= "</table>\n";
        $html .= "<input name='site' type='hidden' value='$site' />";
        $html .= "<p><input name='submit' type='submit' value='{$GLOBALS['strSave']}' /></p>";
        $html .= "</form>";
    }
    return $html;
}


/**
 * Output the html for an add contact form
 *
 * @param int $siteid - the site you want to add the contact to
 * @param string $mode - whether this is internal or external facing, defaults to internal
 * @return string $html add contact form html
 * @author Kieran Hogg
 */
function show_add_contact($siteid = 0, $mode = 'internal')
{
    global $CONFIG;
    $returnpage = cleanvar($_REQUEST['return']);
    if (!empty($_REQUEST['name']))
    {
        $name = explode(' ',cleanvar(urldecode($_REQUEST['name'])), 2);
        $_SESSION['formdata']['add_contact']['forenames'] = ucfirst($name[0]);
        $_SESSION['formdata']['add_contact']['surname'] = ucfirst($name[1]);
    }

    $html = show_form_errors('add_contact');
    clear_form_errors('add_contact');
    $html .= "<h2>".icon('contact', 32)." ";
    $html .= "{$GLOBALS['strNewContact']}</h2>";

    if ($mode == 'internal')
    {
        $html .= "<h5 class='warning'>{$GLOBALS['strAvoidDupes']}</h5>";
    }
    $html .= "<form name='contactform' action='{$_SERVER['PHP_SELF']}' ";
    $html .= "method='post' onsubmit=\"return confirm_action('{$GLOBALS['strAreYouSureAdd']}')\">";
    $html .= "<table align='center' class='vertical'>";
    $html .= "<tr><th>{$GLOBALS['strName']}</th>\n";

    $html .= "<td>";
    $html .= "\n<table><tr><td align='center'>{$GLOBALS['strTitle']}<br />";
    $html .= "<input maxlength='50' name='courtesytitle' title=\"";
    $html .= "{$GLOBALS['strCourtesyTitle']}\" size='7'";
    if ($_SESSION['formdata']['add_contact']['courtesytitle'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['courtesytitle']}'";
    }
    $html .= "/></td>\n";

    $html .= "<td align='center'>{$GLOBALS['strForenames']}<br />";
    $html .= "<input class='required' maxlength='100' name='forenames' ";
    $html .= "size='15' title=\"{$GLOBALS['strForenames']}\"";
    if ($_SESSION['formdata']['add_contact']['forenames'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['forenames']}'";
    }
    $html .= "/></td>\n";

    $html .= "<td align='center'>{$GLOBALS['strSurname']}<br />";
    $html .= "<input class='required' maxlength='100' name='surname' ";
    $html .= "size='20' title=\"{$GLOBALS['strSurname']}\"";
    if ($_SESSION['formdata']['add_contact']['surname'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['surname']}'";
    }
    $html .= " /> <span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
    $html .= "</table>\n</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strJobTitle']}</th><td><input maxlength='255'";
    $html .= " name='jobtitle' size='35' title=\"{$GLOBALS['strJobTitle']}\"";
    if ($_SESSION['formdata']['add_contact']['jobtitle'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['jobtitle']}'";
    }
    $html .= " /></td></tr>\n";
    if ($mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strSite']}</th><td>";
        $html .= site_drop_down('siteid',$siteid, TRUE)."<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
    }
    else
    {
        // For external always force the site to be the session site
        $html .= "<input type='hidden' name='siteid' value='{$_SESSION['siteid']}' />";
    }

    $html .= "<tr><th>{$GLOBALS['strDepartment']}</th><td><input maxlength='255' name='department' size='35'";
    if ($_SESSION['formdata']['add_contact']['department'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['department']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strEmail']}</th><td>";
    $html .= "<input class='required' maxlength='100' name='email' size='35'";
    if ($_SESSION['formdata']['add_contact']['email'])
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['email']}'";
    }
    $html .= "/> <span class='required'>{$GLOBALS['strRequired']}</span> ";

    $html .= "<label>";
    $html .= html_checkbox('dataprotection_email', 'No');
    $html .= "{$GLOBALS['strEmail']} {$GLOBALS['strDataProtection']}</label>".help_link("EmailDataProtection");
    $html .= "</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strTelephone']}</th><td><input maxlength='50' name='phone' size='35'";
    if ($_SESSION['formdata']['add_contact']['phone'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['phone']}'";
    }
    $html .= "/> ";

    $html .= "<label>";
    $html .= html_checkbox('dataprotection_phone', 'No');
    $html .= "{$GLOBALS['strTelephone']} {$GLOBALS['strDataProtection']}</label>".help_link("TelephoneDataProtection");
    $html .= "</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strMobile']}</th><td><input maxlength='100' name='mobile' size='35'";
    if ($_SESSION['formdata']['add_contact']['mobile'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['mobile']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strFax']}</th><td><input maxlength='50' name='fax' size='35'";
    if ($_SESSION['formdata']['add_contact']['fax'])
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['fax']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strAddress']}</th><td><label>";
    $html .= html_checkbox('dataprotection_address', 'No');
    $html .= " {$GLOBALS['strAddress']} {$GLOBALS['strDataProtection']}</label>";
    $html .= help_link("AddressDataProtection")."</td></tr>\n";
    $html .= "<tr><th></th><td><label><input type='checkbox' name='usesiteaddress' value='yes' onclick=\"$('hidden').toggle();\" /> {$GLOBALS['strSpecifyAddress']}</label></td></tr>\n";
    $html .= "<tbody id='hidden' style='display:none'>";
    $html .= "<tr><th>{$GLOBALS['strAddress1']}</th>";
    $html .= "<td><input maxlength='255' name='address1' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strAddress2']}</th>";
    $html .= "<td><input maxlength='255' name='address2' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCity']}</th><td><input maxlength='255' name='city' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCounty']}</th><td><input maxlength='255' name='county' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCountry']}</th><td>";
    $html .= country_drop_down('country', $CONFIG['home_country'])."</td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strPostcode']}</th><td><input maxlength='255' name='postcode' size='35' /></td></tr>\n";
    $html .= "</tbody>";
    if ($mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strNotes']}</th><td><textarea cols='60' rows='5' name='notes'>";
        if ($_SESSION['formdata']['add_contact']['notes'] != '')
        {
            $html .= $_SESSION['formdata']['add_contact']['notes'];
        }
        $html .= "</textarea></td></tr>\n";
    }
    $html .= "<tr><th>{$GLOBALS['strEmailDetails']}</th>";
    // Check the box to send portal details, only if portal is enabled
    $html .= "<td><input type='checkbox' id='emaildetails' name='emaildetails'";
    if ($CONFIG['portal'] == TRUE) $html .= " checked='checked'";
    else $html .= " disabled='disabled'";
    $html .= " />";
    $html .= "<label for='emaildetails'>{$GLOBALS['strEmailContactLoginDetails']}</label></td></tr>";
    $html .= "</table>\n\n";
    if (!empty($returnpage)) $html .= "<input type='hidden' name='return' value='{$returnpage}' />";
    $html .= "<p><input name='submit' type='submit' value=\"{$GLOBALS['strAddContact']}\" /></p>";
    $html .= "</form>\n";

    //cleanup form vars
    clear_form_data('add_contact');

    return $html;
}


/**
 * Format an external ID (From an escalation partner) as HTML
 * @author Ivan Lucas
 * @param int $externalid. An external ID to format
 * @param int $escalationpath. Escalation path ID
 * @return HTML
 */
function format_external_id($externalid, $escalationpath='')
{
    global $CONFIG, $dbEscalationPaths;

    if (!empty($escalationpath))
    {
        // Extract escalation path
        $epsql = "SELECT id, name, track_url, home_url, url_title FROM `{$dbEscalationPaths}` ";
        if (!empty($escalationpath)) $epsql .= "WHERE id='$escalationpath' ";
        $epresult = mysql_query($epsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($epresult) >= 1)
        {
            while ($escalationpath = mysql_fetch_object($epresult))
            {
                $epath['name'] = $escalationpath->name;
                $epath['track_url'] = $escalationpath->track_url;
                $epath['home_url'] = $escalationpath->home_url;
                $epath['url_title'] = $escalationpath->url_title;
            }

            if (!empty($externalid))
            {
                $epathurl = str_replace('%externalid%', $externalid, $epath['track_url']);
                $html = "<a href='{$epathurl}' title='{$epath['url_title']}'>{$externalid}</a>";
            }
            else
            {
                $epathurl = $epath['home_url'];
                $html = "<a href='{$epathurl}' title='{$epath['url_title']}'>{$epath['name']}</a>";
            }
        }
    }
    else
    {
        $html = $externalid;
    }
    return $html;
}


/**
 * Outputs a contact's contract associate, if the viewing user is allowed
 * @author Kieran Hogg
 * @param int $userid ID of the contact
 * @retval string output html
 * @todo TODO should this be renamed, it has nothing to do with users
 */
function user_contracts_table($userid, $mode = 'internal')
{
    global $now, $CONFIG, $sit;
    if ((!empty($sit[2]) AND user_permission($sit[2], 30)
        OR ($_SESSION['usertype'] == 'admin'))) // view supported products
    {
        $html .= "<h4>".icon('contract', 16)." {$GLOBALS['strContracts']}:</h4>";
        // Contracts we're explicit supported contact for
        $sql  = "SELECT sc.maintenanceid AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
        $sql .= "`{$GLOBALS['dbSupportContacts']}` AS sc, ";
        $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, ";
        $sql .= "`{$GLOBALS['dbProducts']}` AS p ";
        $sql .= "WHERE c.id = '{$userid}' ";
        $sql .= "AND (sc.maintenanceid=m.id AND sc.contactid='$userid') ";
        $sql .= "AND m.product=p.id  ";
        // Contracts we're an 'all supported' on
        $sql .= "UNION ";
        $sql .= "SELECT m.id AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
        $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, ";
        $sql .= "`{$GLOBALS['dbProducts']}` AS p ";
        $sql .= "WHERE c.id = '{$userid}' AND c.siteid = m.site ";
        $sql .= "AND m.allcontactssupported = 'yes' ";
        $sql .= "AND m.product=p.id  ";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result)>0)
        {
            $html .= "<table align='center' class='vertical'>";
            $html .= "<tr>";
            $html .= "<th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strProduct']}</th><th>{$GLOBALS['strExpiryDate']}</th>";
            $html .= "</tr>\n";

            $supportcount = 1;
            $shade = 'shade2';
            while ($supportedrow = mysql_fetch_array($result))
            {
                if ($supportedrow['term'] == 'yes')
                {
                    $shade = 'expired';
                }

                if ($supportedrow['expirydate'] < $now AND $supportedrow['expirydate'] != -1)
                {
                    $shade = 'expired';
                }

                $html .= "<tr><td class='$shade'>";
                $html .= ''.icon('contract', 16)." ";
                if ($mode == 'internal')
                {
                    $html .= "<a href='contract_details.php?id=";
                }
                else
                {
                    $html .= "<a href='contracts.php?id=";
                }
                $html .= "{$supportedrow['maintenanceid']}'>";
                $html .= "{$GLOBALS['strContract']}: ";
                $html .= "{$supportedrow['maintenanceid']}</a></td>";
                $html .= "<td class='$shade'>{$supportedrow['productname']}</td>";
                $html .= "<td class='$shade'>";
                if ($supportedrow['expirydate'] == -1)
                {
                    $html .= $GLOBALS['strUnlimited'];
                }
                else
                {
                    $html .= ldate($CONFIG['dateformat_date'], $supportedrow['expirydate']);
                }
                if ($supportedrow['term'] == 'yes')
                {
                    $html .= " {$GLOBALS['strTerminated']}";
                }

                $html .= "</td>";
                $html .= "</tr>\n";
                $supportcount++;
                $shade = 'shade2';
            }
            $html .= "</table>\n";
        }
        else
        {
            $html .= "<p align='center'>{$GLOBALS['strNone']}</p>\n";
        }

        if ($mode == 'internal')
        {
            $html .= "<p align='center'>";
            $html .= "<a href='contract_add_contact.php?contactid={$userid}&amp;context=contact'>";
            $html .= "{$GLOBALS['strAssociateContactWithContract']}</a></p>\n";
        }

    }

    return $html;
}


?>
