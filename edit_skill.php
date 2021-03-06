<?php
// edit_skill.php - Form for editing skills
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
$permission = PERM_SKILL_ADD; // Add Software
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);
$action = clean_fixed_list($_REQUEST['action'], array('','edit','delete','save'));

if (empty($action) OR $action == 'edit')
{
    $title = $strEditSkill;
    // Show add product form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('skill', 32)." ";
    echo "{$title}</h2>";
    
    echo show_form_errors('edit_skill');
    clear_form_errors('edit_skill');
    
    $sql = "SELECT * FROM `{$dbSoftware}` WHERE id='{$id}' LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    while ($software = mysqli_fetch_object($result))
    {
        echo "<form name='editskill' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureMakeTheseChanges}\")'>";
        echo "<table class='vertical'>";
        echo "<tr><th>{$strVendor}:</th><td>".vendor_drop_down('vendor', $software->vendorid)."</td></tr>\n";
        echo "<tr><th>{$strSkill}:</th><td><input class='required' maxlength='50' name='name' size='30' value='{$software->name}' /> <span class='required'>{$strRequired}</span></td></tr>";
        echo "<tr><th>{$strLifetime}:</th><td>";
        echo "{$strFrom} <input type='text' name='lifetime_start' id='lifetime_start' size='10' value='";
        if ($software->lifetime_start > 1)
        {
            echo date('Y-m-d', mysql2date($software->lifetime_start));
        }
        echo "' /> ";
        echo date_picker('editsoftware.lifetime_start');
        echo $strTo.": ";
        echo "<input type='text' name='lifetime_end' id='lifetime_end' size='10' value='";
        if ($software->lifetime_end > 1)
        {
            echo date('Y-m-d', mysql2date($software->lifetime_end));
        }
        echo "' /> ";
        echo date_picker('editsoftware.lifetime_end');
        echo "</td></tr>";
        echo "<tr><th>{$strTags}:</th>";
        echo "<td><textarea rows='2' cols='30' name='tags'>".list_tags($id, TAG_SKILL, false)."</textarea></td></tr>\n";
        echo "</table>";
    }
    echo "<input type='hidden' name='id' value='{$id}' />";
    echo "<input type='hidden' name='action' value='save' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>\n";
    echo "<p class='return'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == 'delete')
{
    // Delete
    // First check there are no incidents using this software
    $sql = "SELECT count(id) FROM `{$dbIncidents}` WHERE softwareid={$id}";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    list($countincidents) = mysqli_fetch_row($result);
    if ($countincidents >=1)
    {
        html_redirect('products.php?display=skills', FALSE, $strCannotDeleteSkill);
    }
    else
    {
        $sql = "DELETE FROM `{$dbSoftware}` WHERE id='{$id}'";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        $sql = "DELETE FROM `{$dbSoftwareProducts}` WHERE softwareid={$id}";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        $sql = "DELETE FROM `{$dbUserSoftware}` WHERE softwareid={$id}";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        journal(CFG_LOGGING_DEBUG, 'Skill Deleted', "Skill {$id} was deleted", CFG_JOURNAL_DEBUG, $id);
        html_redirect("products.php?display=skills");
    }
}
else
{
    // Save
    $name = cleanvar($_REQUEST['name']);
    $vendor = clean_int($_REQUEST['vendor']);
    $tags = cleanvar($_REQUEST['tags']);
    if (!empty($_REQUEST['lifetime_start'])) $lifetime_start = date('Y-m-d', strtotime($_REQUEST['lifetime_start']));
    else $lifetime_start = '';
    if (!empty($_REQUEST['lifetime_end'])) $lifetime_end = date('Y-m-d', strtotime($_REQUEST['lifetime_end']));
    else $lifetime_end = '';

    // Make sure lifetime start and end are the right way around in case of user error
    if ($lifetime_end < $lifetime_start)
    {
        $s = $lifetime_start;
        $lifetime_start = $lifetime_end;
        $lifetime_end = $s;
        unset($s);
    }

    $errors = 0;

    if ($name == '')
    {
        $_SESSION['formerrors']['edit_skill']['product'] = sprintf($strFieldMustNotBeBlank, $strName);
        $errors++;
    }
    // add product if no errors
    if ($errors == 0)
    {
        replace_tags(TAG_SKILL, $id, $tags);

        $sql = "UPDATE `{$dbSoftware}` SET ";
        $sql .= "name='{$name}', vendorid='{$vendor}', lifetime_start='{$lifetime_start}', lifetime_end='{$lifetime_end}' ";
        $sql .= "WHERE id = '{$id}'";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        else
        {
            $id = mysqli_insert_id($db);
            journal(CFG_LOGGING_DEBUG, 'Skill Edited', "Skill {$id} was edited", CFG_JOURNAL_DEBUG, $id);
            html_redirect("products.php?display=skills");
        }
    }
    else
    {
        html_redirect("{$_SERVER['PHP_SELF']}?action=edit&id={$id}", FALSE);
    }
}
?>