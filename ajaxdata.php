<?php
// ajaxdata.php - Return data for AJAX calls
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_NOT_REQUIRED; // not required
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'triggers.inc.php');


// This page requires authentication
if ($_REQUEST['action'] == 'contexthelp' AND $_REQUEST['auth'] == 'portal')
{
    // Special exception for contexthelp, use the portal authentication for
    // portal help tips
    $accesslevel = 'any';
    require (APPLICATION_LIBPATH . 'portalauth.inc.php');
}
else
{
    require (APPLICATION_LIBPATH . 'auth.inc.php');
}
$action = cleanvar($_REQUEST['action']);
$selected = cleanvar(@$_REQUEST['selected']);

switch ($action)
{
    case 'auto_save':
        $userid = $_SESSION['userid'];
        $incidentid = clean_int($_REQUEST['incidentid']);
        $type = cleanvar($_REQUEST['type']);
        $draftid = clean_int($_REQUEST['draftid']);
        $meta = cleanvar($_REQUEST['meta']);
        $content = cleanvar($_REQUEST['content']);

        if ($userid == $_SESSION['userid'])
        {
            if ($draftid == -1)
            {
                $sql = "INSERT INTO `{$dbDrafts}` (userid,incidentid,type,meta,content,lastupdate) VALUES ('{$userid}','{$incidentid}','{$type}','{$meta}','{$content}','{$now}')";
            }
            else
            {
                $sql = "UPDATE `{$dbDrafts}` SET content = '{$content}', meta = '{$meta}', lastupdate = '{$now}' WHERE id = {$draftid}";
            }
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
            echo mysqli_insert_id($db);
        }
        break;
    case 'servicelevel_timed':
        $sltag = cleanvar($_REQUEST['servicelevel']);
        if (servicelevel_timed($sltag))
        {
            echo "TRUE";
        }
        else
        {
            echo "FALSE";
        }
        break;
    case 'contexthelp':
        $context = cleanvar($_REQUEST['context']);
        $helpfile = APPLICATION_HELPPATH . "{$_SESSION['lang']}". DIRECTORY_SEPARATOR . "{$context}.txt";
        // Default back to english if language helpfile isn't found
        if (!file_exists($helpfile)) $helpfile = APPLICATION_HELPPATH . "en-GB/{$context}.txt";
        if (file_exists($helpfile))
        {
            $fp = fopen(clean_fspath($helpfile), 'r', TRUE);
            $helptext = fread($fp, 1024);
            fclose($fp);
            echo nl2br($helptext);
        }
        else
        {
            echo "Error: Missing helpfile '{$_SESSION['lang']}/{$context}.txt'";
        }
        break;
    case 'dismiss_notice':
        require (APPLICATION_LIBPATH . 'auth.inc.php');
        // We don't use clean_int here as it may be a int or 'all' if its a string its not used directly
        $noticeid = clean_dbstring($_REQUEST['noticeid']);
        $userid = clean_int($_REQUEST['userid']);
        if (is_numeric($noticeid))
        {
            $sql = "DELETE FROM `{$GLOBALS['dbNotices']}` WHERE id='{$noticeid}' AND userid='{$sit[2]}'";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
            else echo "deleted {$noticeid}";
        }
        elseif ($noticeid == 'all')
        {
            $sql = "DELETE FROM `{$GLOBALS['dbNotices']}` WHERE userid={$userid} LIMIT 20"; // only delete 20 max as we only show 20 max
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
            else echo "deleted {$noticeid}";
        }
        break;
    case 'dashboard_display':
        require (APPLICATION_LIBPATH . 'auth.inc.php');
        $dashboard = clean_dbstring($_REQUEST['dashboard']);
        $dashletid = 'win'.cleanvar($_REQUEST['did']);
        if (is_dashlet_installed($dashboard))
        {
            include (APPLICATION_PLUGINPATH . "dashboard_{$dashboard}.php");
            $dashfn = "dashboard_{$dashboard}_display";
            echo $dashfn($dashletid);
        }
        break;
    case 'dashboard_save':
    case 'dashboard_edit':
        require (APPLICATION_LIBPATH . 'auth.inc.php');

        $dashboard = clean_dbstring($_REQUEST['dashboard']);
        $dashletid = 'win'.cleanvar($_REQUEST['did']);
        if (is_dashlet_installed($dashboard))
        {
            include (APPLICATION_PLUGINPATH . "dashboard_{$dashboard}.php");
            $dashfn = "dashboard_{$dashboard}_edit";
            echo $dashfn($dashletid);
        }
        break;
    case 'autocomplete_sitecontact':
        $s = clean_dbstring($_REQUEST['s']);
        $htmllist = clean_dbstring($_REQUEST['htmllist']);
        $sql = "SELECT DISTINCT forenames, surname FROM `{$dbContacts}` ";
        $sql .= "WHERE active='true' AND (forenames LIKE '{$s}%' OR surname LIKE '{$s}%')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        if ($htmllist == 'true') $str = "<ul>";
        if (mysqli_num_rows($result) > 0)
        {
            while ($obj = mysqli_fetch_object($result))
            {
                if ($htmllist == 'true') $str .= "<li>{$obj->forenames} {$obj->surname}</li>";
                else $str .= "[\"".$obj->forenames." ".$obj->surname."\"],";
            }
        }
        $sql = "SELECT DISTINCT name FROM `{$dbSites}` ";
        $sql .= "WHERE active='true' AND name LIKE '{$s}%'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($result) > 0)
        {
            while ($obj = mysqli_fetch_object($result))
            {
                if ($htmllist == 'true') $str .= "<li>{$obj->name}</li>";
                else $str .= "[\"".$obj->name."\"],";
            }
        }
        if ($htmllist == 'true') $str .= "</ul>";
        else $str .= "[".mb_substr($str, 0, -1)."]";
        echo $str;
        break;
    case 'tags':
        $sql = "SELECT DISTINCT t.name FROM `{$dbSetTags}` AS st, `{$dbTags}` AS t WHERE st.tagid = t.tagid GROUP BY t.name";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($result) > 0)
        {
            while ($obj = mysqli_fetch_object($result))
            {
                $str .= "[".$obj->name."],";
            }
        }
        echo "[".mb_substr($str,0,-1)."]";
        break;
    case 'contact' :
        $s = clean_dbstring($_REQUEST['s']);
        $htmllist = clean_dbstring($_REQUEST['htmllist']);
        $sql = "SELECT DISTINCT forenames, surname FROM `{$dbContacts}` ";
        $sql .= "WHERE active='true' AND (forenames LIKE '{$s}%' OR surname LIKE '{$s}%')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        if ($htmllist == 'true') $str = "<ul>";
        if (mysqli_num_rows($result) > 0)
        {
            while ($obj = mysqli_fetch_object($result))
            {
                if ($htmllist == 'true') $str .= "<li>{$obj->forenames} {$obj->surname}</li>";
                else $str .= "[\"".$obj->forenames." ".$obj->surname."\"],";
            }
        }
        if ($htmllist == 'true') $str .= "</ul>";
        else $str .= "[".mb_substr($str, 0, -1)."]";
        echo $str;
        break;
    case 'sites':
        $s = clean_dbstring($_REQUEST['s']);
        $htmllist = clean_dbstring($_REQUEST['htmllist']);
        $sql = "SELECT DISTINCT name FROM `{$dbSites}` ";
        $sql .= "WHERE active='true' AND name LIKE '{$s}%'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        if ($htmllist == 'true') $str = "<ul>";
        if (mysqli_num_rows($result) > 0)
        {
            while ($obj = mysqli_fetch_object($result))
            {
                if ($htmllist == 'true') $str .= "<li>{$obj->name}</li>";
                else $str .= "[\"".$obj->name."\"],";
            }
        }
        if ($htmllist == 'true') $str .= "</ul>";
        else $str .= "[".mb_substr($str, 0, -1)."]";
        echo $str; 
        break;
    case 'slas':
        $sql = "SELECT DISTINCT tag FROM `{$dbServiceLevels}`";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        while ($obj = mysqli_fetch_object($result))
        {
            $strIsSelected = '';
            if ($obj->tag == $selected)
            {
                $strIsSelected = "selected='selected'";
            }
            echo "<option value='{$obj->tag}' {$strIsSelected}>{$obj->tag}</option>";
        }
        break;
    case 'products':
        $sql = "SELECT id, name FROM `{$dbProducts}` ORDER BY name ASC";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        while ($obj = mysqli_fetch_object($result))
        {
            $strIsSelected = '';
            if ($obj->id == $selected)
            {
                $strIsSelected = "selected='selected'";
            }
            echo "<option value='{$obj->id}' {$strIsSelected}>{$obj->name}</option>";
        }
        break;
    case 'skills':
        $sql = "SELECT id, name FROM `{$dbSoftware}` ORDER BY name ASC";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        while ($obj = mysqli_fetch_object($result))
        {
            $strIsSelected = '';
            if ($obj->id == $selected)
            {
                $strIsSelected = "selected='selected'";
            }
            echo "<option value='{$obj->id}' {$strIsSelected}>{$obj->name}</option>";
        }
        break;
    case 'storedashboard':
        $id = $_SESSION['userid'];
        $val = clean_dbstring($_REQUEST['val']);

        if ($id == $_SESSION['userid'])
        {
            //check you're changing your own
            $sql = "UPDATE `{$dbUsers}` SET dashboard = '{$val}' WHERE id = {$id}";
            $contactresult = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        }
        break;
    case 'checkldap':
        $ldap_host = cleanvar($_REQUEST['ldap_host']);
        $ldap_port = clean_int($_REQUEST['ldap_port']);
        $ldap_protocol = cleanvar($_REQUEST['ldap_protocol']);
        $ldap_security = cleanvar($_REQUEST['ldap_security']);
        $ldap_type = clean_fixed_list($_REQUEST['ldap_type'], array('', 'SSL'));
        $ldap_user = clean_ldapstring($_REQUEST['ldap_bind_user']);
        $ldap_password = clean_ldapstring($_REQUEST['ldap_bind_pass']);
        $ldap_user_base = clean_ldapstring($_REQUEST['ldap_user_base']);
        $ldap_admin_group = clean_ldapstring($_REQUEST['ldap_admin_group']);
        $ldap_manager_group = clean_ldapstring($_REQUEST['ldap_manager_group']);
        $ldap_user_group = clean_ldapstring($_REQUEST['ldap_user_group']);
        $ldap_customer_group = clean_ldapstring($_REQUEST['ldap_customer_group']);

        $r = ldapOpen($ldap_host, $ldap_port, $ldap_protocol, $ldap_security, $ldap_user, $ldap_password);
        if ($r == -1) echo LDAP_PASSWORD_INCORRECT; // Failed
        else
        {
            // Check user base
            if (!ldapCheckObjectExists($ldap_user_base, "*")) echo LDAP_BASE_INCORRECT;
            else
            {
            	// Check Admin group
                if (!ldapCheckGroupExists($ldap_admin_group, $ldap_type)) echo LDAP_ADMIN_GROUP_INCORRECT;
                else
                {
                    // Check manager group
                    if (!ldapCheckGroupExists($ldap_manager_group, $ldap_type)) echo LDAP_MANAGER_GROUP_INCORRECT;
                    else
                    {
                        // Check user group
                        if (!ldapCheckGroupExists($ldap_user_group, $ldap_type)) echo LDAP_USER_GROUP_INCORRECT;
                        else
                        {
                            // Check customer group
                            if (!ldapCheckGroupExists($ldap_customer_group, $ldap_type)) echo LDAP_CUSTOMER_GROUP_INCORRECT;
                            else
                            {
                                // ALL OK
                                echo LDAP_CORRECT;
                            }
                        }
                    }
                }
            }
        }

        break;
    case 'triggerpairmatch':
        $triggertype = cleanvar($_REQUEST['triggertype']);
        $action = cleanvar($_REQUEST['triggeraction']);
        debug_log("Returning a template for {$triggertype} and {$action}", TRUE);
        if ($action == 'ACTION_EMAIL')
        {
            echo $email_pair[$triggertype];
        }
        elseif ($action == 'ACTION_NOTICE')
        {
            echo $notice_pair[$triggertype];
        }
        break;
    case 'checkhtml':
        $triggertype = cleanvar($_REQUEST['triggertype']);
        if (is_numeric($trigger_type)) $trigger_type = $trigger_type[0];
        if (is_array($trigger_types[$triggertype]['params']))
        {
            echo "<p align='left'>{$strNotifyWhen} ";
            echo "<select name='conditions'><option value='all'>{$strAllConditionsMet}</option>";
            echo "<option value='any'>{$strAnyConditionMet}</option></select></p>";
            echo "<table>";
            $i = 0;
            foreach ($trigger_types[$triggertype]['params'] as $param)
            {
                // if we return a number here, the variable is multiply-defined;
                // as the replacements are the same, we use the first one
                if (is_array($ttvararray['{'.$param.'}']) AND
                    is_numeric(key($ttvararray['{'.$param.'}'])))
                {
                    //echo "\$ttvararray[\{{$param}\}] = ".$ttvararray['{'.$param.'}'];
                    $ttvararray['{'.$param.'}'] = $ttvararray['{'.$param.'}'][0];
                }

                if (isset($ttvararray['{'.$param.'}']['checkreplace']))
                {
                    echo '<tr>';
                    echo "<td><input type='hidden' name='param[{$i}]' value='{$param}' /></td>";
                    echo '<td align="right">'.$ttvararray['{'.$param.'}']['description']. '</td>';
                    echo '<td>'.check_match_drop_down('join['.$i.']'). '</td>';
                    echo '<td>'.$ttvararray['{'.$param.'}']['checkreplace']('value['.$i.']')."</td>";
                    // put a hidden input so we can see unchecked boxes
                    echo "<td><input type='hidden' name='enabled[{$i}]' value='off' />";
                    echo "<label><input type='checkbox' name='enabled[{$i}]' /> {$strEnableCondition}</label></td></tr>";
                    $i++;
                }
            }
            echo '</table>';
        }
        // if ($html == " ") $html = "No variables available for this action.";
        echo " ";
        break;
    case 'set_user_status':
        $userstatus = cleanvar($_REQUEST['userstatus']);
        $result = set_user_status($_SESSION['userid'], $userstatus);
        if ($result === FALSE)
        {
            echo 'FALSE';
        }
        else
        {
            echo $result;
        }
        break;
    case 'delete_temp_assign':
        if (user_permission($sit[2], PERM_UPDATE_DELETE))
        {
            $incidentid = clean_int($_REQUEST['incidentid']);
            $originalowner = clean_int($_REQUEST['originalowner']);
            $sql = "UPDATE `{$dbTempAssigns}` SET assigned='yes' ";
            $sql .= "WHERE incidentid='{$incidentid}' AND originalowner='{$originalowner}' LIMIT 1";
            mysqli_query($db, $sql);
            if (mysqli_error($db))
            {
                echo "FAILED";
                trigger_error(mysqli_error($db), E_USER_ERROR);
            }
            else
            {
                echo "OK";
            }
        }
        else
        {
            echo "NOPERMISSION";
        }
        break;
    case 'ldap_browse_groups':
        $base = cleanvar($_REQUEST['base']);
        $field = cleanvar($_REQUEST['field']);
        $ldap_type = cleanvar($_REQUEST['ldap_type']);
        $ldap_host = cleanvar($_REQUEST['ldap_host']);
        $ldap_port = clean_int($_REQUEST['ldap_port']);
        $ldap_protocol = clean_int($_REQUEST['ldap_protocol']);
        $ldap_security = cleanvar($_REQUEST['ldap_security']);
        $ldap_bind_user = cleanvar($_REQUEST['ldap_bind_user']);
        $ldap_bind_pass = cleanvar($_REQUEST['ldap_bind_pass']);
        echo json_encode(ldapGroupBrowse($base, $ldap_host, $ldap_port, $ldap_type, $ldap_protocol, $ldap_security, $ldap_bind_user, $ldap_bind_pass));
        break;
    case 'display_billingmatrix':
        $billingtype = cleanvar($_REQUEST['billingtype']);
        $selected = cleanvar($_REQUEST['selected']);

        $bill = new $billingtype();
        $s = $bill->billing_matrix_selector('billing_matrix', $selected);

        if (empty($s)) 
        {
            $s = $GLOBALS['strNotApplicableAbbrev'];
        }
        else
        {
            $s = $s . "<span class='required'>{$GLOBALS['strRequired']}</span>"; 
        }

        echo $s;
        break;
    default :
        plugin_do('ajaxdata_action', array('action' => $action));
        break;
}


?>
