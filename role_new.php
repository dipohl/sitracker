<?php
// role_new.php - Page to add role to SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul@sitracker.org>

require ('core.php');
$permission = PERM_USER_PERMISSIONS_EDIT; // Edit User Permissions
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
    $title = $strNewRole;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('role_new');
    clear_form_errors('role_new');

    echo "<h2>{$strNewRole}</h2>";
    echo "<form method='post' action='{$_SERVER['PHP_SELF']}'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strName}</th>";
    echo "<td><input class='required' size='30' name='rolename' /> <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strDescription}</th><td><textarea name='description' id='description' rows='5' cols='30'>{$_SESSION['formdata']['role_new']['description']}</textarea></td></tr>";
    echo "<tr><th>{$strCopyFrom}</th><td>";
    if ($_SESSION['formdata']['role_new']['roleid'] != '')
    {
        echo role_drop_down('copyfrom', $_SESSION['formdata']['role_new']['roleid']);
    }
    else
    {
        echo role_drop_down('copyfrom', 0);
    }
    echo "</td></tr>";

    echo "</table>";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> <input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>";
    echo "<p class='return'><a href='edit_user_permissions.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    clear_form_data('role_new');
}
else
{
    $rolename = clean_dbstring($_REQUEST['rolename']);
    $description = clean_dbstring($_REQUEST['description']);
    $copyfrom = clean_int($_REQUEST['copyfrom']);

    $_SESSION['formdata']['role_new'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    if (empty($rolename))
    {
        $errors++;
        $_SESSION['formerrors']['role_new']['rolename'] = sprintf($strFieldMustNotBeBlank, $strName);
    }

    $sql = "SELECT * FROM `{$dbRoles}` WHERE rolename = '{$rolename}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    if (mysqli_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['role_new']['duplicaterole'] = "{$strADuplicateAlreadyExists}</p>\n";
    }

    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbRoles}` (rolename, description) VALUES ('{$rolename}', '{$description}')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        $roleid = mysqli_insert_id($db);

        if ($roleid != 0)
        {
            clear_form_data('role_new');
            clear_form_errors('role_new');

            if (!empty($copyfrom))
            {
                $sql = "INSERT INTO `{$dbRolePermissions}` (roleid, permissionid, granted)  ";
                $sql .= "SELECT '{$roleid}', permissionid, granted FROM `{$dbRolePermissions}` WHERE roleid = {$copyfrom}";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

                // Note we dont check for affected rows as you could be copying from a permissionless role
                html_redirect('edit_user_permissions.php', TRUE);
            }
            else
            {
                html_redirect('edit_user_permissions.php', TRUE);
            }
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}

?>