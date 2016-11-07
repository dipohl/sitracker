<?php
// skill_new.php - Form for adding skills (skills were called software in earlier versions of SiT)
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
$permission = PERM_SKILL_ADD; // Add Skills
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewSkill;

// External variables
$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
    // Show add product form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('skill', 32)." ";
    echo "{$strNewSkill}</h2>";
    
    echo show_form_errors('new_software');
    clear_form_errors('new_software');

    echo "<form name='addsoftware' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strVendor}</th><td>";
    if ($_SESSION['formdata']['new_software']['vendor'] != '')
    {
        echo vendor_drop_down('vendor',$_SESSION['formdata']['new_software']['vendor'])."</td></tr>\n";
    }
    else
    {
        echo vendor_drop_down('vendor',$software->vendorid)."</td></tr>\n";
    }
    echo "<tr><th>{$strSkill}</th><td><input maxlength='50' name='name' size='30' class='required' /> <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr><th>{$strLifetime}</th><td>";
    echo "{$strFrom} <input type='text' name='lifetime_start' id='lifetime_start' size='10' ";
    if ($_SESSION['formdata']['new_software']['lifetime_start'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_software']['lifetime_start']}'";
    }
    echo " /> ";
    echo date_picker('addsoftware.lifetime_start');
    echo " {$strTo} ";
    echo "<input type='text' name='lifetime_end' id='lifetime_end' size='10'";
    if ($_SESSION['formdata']['new_software']['lifetime_end'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_software']['lifetime_end']}'";
    }
    echo "/> ";
    echo date_picker('addsoftware.lifetime_end');
    echo "</td></tr>\n";
    echo "<tr><th>{$strTags}</th>";
    echo "<td><textarea rows='2' cols='30' name='tags'></textarea></td></tr>\n";
    echo "</table>";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>";
    echo "</form>\n";
    echo "<p class='return'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

    $_SESSION['formdata']['new_software'] = NULL;
}
else
{
    // External variables
    $name = clean_dbstring($_REQUEST['name']);
    $tags = clean_dbstring($_REQUEST['tags']);
    $vendor = clean_int($_REQUEST['vendor']);
    if (!empty($_REQUEST['lifetime_start']))
    {
        $lifetime_start = date('Y-m-d', strtotime($_REQUEST['lifetime_start']));
    }
    else
    {
        $lifetime_start = '';
    }

    if (!empty($_REQUEST['lifetime_end']))
    {
        $lifetime_end = date('Y-m-d', strtotime($_REQUEST['lifetime_end']));
    }
    else
    {
        $lifetime_end = '';
    }

    $_SESSION['formdata']['new_software'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;

    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_software']['name'] = sprintf($strFieldMustNotBeBlank, $strSkill);
    }
    // Check this is not a duplicate
    $sql = "SELECT id FROM `{$dbSoftware}` WHERE LCASE(name)=LCASE('{$name}') LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_num_rows($result) >= 1)
    {
        $errors++;
        $_SESSION['formerrors']['new_software']['duplicate'] .= $strARecordAlreadyExistsWithTheSameName;
    }

    // add product if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbSoftware}` (name, vendorid, lifetime_start, lifetime_end) VALUES ('{$name}','{$vendor}','{$lifetime_start}','{$lifetime_end}')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        if (!$result)
        {
            echo "<p class='error'>{$strAdditionFail}</p>";
        }
        else
        {
            $id = mysqli_insert_id($db);
            replace_tags(TAG_SKILL, $id, $tags);

            journal(CFG_LOGGING_DEBUG, 'Skill Added', "Skill {$id} was added", CFG_JOURNAL_DEBUG, $id);
            html_redirect("products.php?display=skills");
            //clear form data
            $_SESSION['formdata']['new_software'] = NULL;
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>