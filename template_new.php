<?php
// template_new.php - Form for adding new templates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_TEMPLATE_ADD; // Add Email Template
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

if (!empty($_POST['type']))
{
    $type = cleanvar($_POST['type']);
    $name = cleanvar($_POST['name']);

    if ($type == 'email')
    {
        // First check the template does not already exist
        $sql = "SELECT id FROM `{$dbEmailTemplates}` WHERE name = '{$name}' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($result) < 1)
        {
            $sql = "INSERT INTO `{$dbEmailTemplates}` (name, type) VALUES('{$name}', 'incident')";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
            $id = mysql_insert_id();
            header("Location: templates.php?id={$id}&action=edit&template=email");
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE, $strADuplicateAlreadyExists);
            exit;
        }
    }
    elseif ($type == 'notice')
    {
        // First check the template does not already exist
        $sql = "SELECT id FROM `{$dbNoticeTemplates}` WHERE name = '{$name}' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($result) < 1)
        {
            $sql = "INSERT INTO `{$dbNoticeTemplates}`(name) VALUES('{$name}')";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
            $id = mysql_insert_id();
            header("Location: templates.php?id={$id}&action=edit&template=notice");
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE, $strADuplicateAlreadyExists);
            exit;
        }
    }
}
$title = $strNewTemplate;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('new', 32)." {$strNewTemplate}</h2>";

echo "<form action='{$_SERVER['PHP_SELF']}?action=new' method='post'>";
echo "<p align='center'><label>{$strType}: ";
echo "<select name='type'>";
echo "<option value='email'>{$strEmail}</option>";
echo "<option value='notice'>{$strNotice}</option>";
echo "</select></label><br /><br />";
echo "<label>{$strName}: <input name='name' /></label>";
echo "<br /><br /><input type='submit' value='{$strNew}' />";
echo "</p>";
echo "</form>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>