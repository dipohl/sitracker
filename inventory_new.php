<?php
// inventory_new.php - Add inventory items
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

$permission = 0;

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

$title = "$strInventory - $strNew";

if (!empty($_GET['site']))
{
    $siteid = clean_int($_GET['site']);
}
$newsite = cleanvar($_GET['newsite']);

if (!empty($_POST['submit']) AND !empty($_POST['name']) AND $_POST['site'] != 0)
{
    $post = cleanvar($_POST);

    $sql = "INSERT INTO `{$dbInventory}`(address, username, password, type,";
    $sql .= " notes, created, createdby, modified, modifiedby, active,";
    $sql .= " name, siteid, privacy, identifier, contactid) VALUES('{$post['address']}', ";
    $sql .= "'{$post['username']}', '{$post['password']}', ";
    $sql .= "'{$post['type']}', ";
    $sql .= "'{$post['notes']}', NOW(), '{$sit[2]}', NOW(), ";
    $sql .= "'{$sit[2]}', '1', '{$post['name']}', '{$post['site']}', ";
    $sql .= "'{$post['privacy']}', '{$post['identifier']}', '{$post['owner']}')";

    mysql_query($sql);
    $id = mysql_insert_id();
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    else html_redirect("inventory_view.php?id={$id}");
}
else
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    if (!empty($_POST['submit']) AND empty($_POST['name']))
    {
        echo "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strName)."</p>";
    }
    elseif (!empty($_POST['submit']) AND $_POST['site'] == 0)
    {
        echo "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strSite)."</p>";
    }
    echo "<h2>".icon('new', 32)." {$strNew}</h2>";

    $url = "{$_SERVER['PHP_SELF']}?action=new";
    if (!empty($_GET['site']))
    {
        $siteid = intval($_GET['site']);
        $url = $url."&amp;site={$siteid}";
    }

    echo "<form action='{$url}' method='post'>";
    echo "<table class='vertical' align='center'>";
    echo "<tr><th>{$strName}</th>";
    echo "<td><input class='required' name='name' />";
    echo " <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strType}</th>";
    echo "<td>".array_drop_down($CONFIG['inventory_types'], 'type', '', '', TRUE)."</td></tr>";

    if (!intval($siteid))
    {
        echo "<tr><th>{$strSite}</th><td>";
        echo site_drop_down('site', 0, TRUE);
        echo " <span class='required'>{$strRequired}</span></td></tr>";
        echo "<tr><th>{$strOwner}</th><td>";
        echo contact_site_drop_down('owner', '');
        echo "</td></tr>";
    }
    else
    {
        echo "<tr><th>{$strOwner}</th><td>";
        echo "<input type='hidden' id='site' name='site' value='{$siteid}' />";
        echo contact_site_drop_down('owner', '', $siteid, NULL, FALSE);
        echo "</td></tr>";
    }
    echo "<tr><th>{$strID} ".help_link('InventoryID')."</th>";
    echo "<td><input name='identifier' /></td></tr>";
    echo "<tr><th>{$strAddress}</th>";
    echo "<td><input name='address' /></td></tr>";
    echo "<tr><th>{$strUsername}</th>";
    echo "<td><input name='username' /></td></tr>";
    echo "<tr><th>{$strPassword}</th>";
    echo "<td><input name='password' /></td></tr>";


    echo "<tr><th>{$strNotes}</th>";
    echo "<td>";
    echo bbcode_toolbar('inventorynotes');
    echo "<textarea id='inventorynotes' rows='15' cols='60' name='notes'></textarea></td></tr>";

    echo "<tr><th>{$strPrivacy} ".help_link('InventoryPrivacy')."</th>";
    echo "<td><input type='radio' name='privacy' value='private' />{$strPrivate}<br />";

    echo "<input type='radio' name='privacy' value='adminonly' />";
    echo "{$strAdminOnly}<br />";

    echo "<input type='radio' name='privacy' value='none'";
    if (!$selected)
    {
        echo " checked='checked' ";
    }
    echo "/>";
    echo "{$strNone}<br />";
    echo "</td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'>";
    echo "<input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>";
    echo "<p align='center'>";

    if ($newsite)
    {
        echo icon('site', 16);
        echo " <a href='inventory.php'>{$strBackToSites}</a>";
    }
    else
    {
        echo "<a href='inventory_site.php?id={$siteid}'>{$strReturnWithoutSaving}</a>";
    }
    echo "</p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>