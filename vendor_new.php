<?php
// vendor_new.php - Form for adding software vendors
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
require (APPLICATION_LIBPATH.'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strNewVendor;

// External variables
$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
    // Show form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('new_vendor');
    clear_form_errors('new_vendor');
    echo "<h2>" . icon('vendor', 32, $strVendors) . " {$strNewVendor}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\")'>";
    echo "<table class='maintable vertical'>";
    echo "<tr><th>{$strVendorName}</th><td><input maxlength='50' name='name' size='30' class='required'> <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "</table>";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>";
    echo "</form>\n";
    echo "<p class='return'><a href='vendors.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    $name = cleanvar($_REQUEST['name']);
    $_SESSION['formdata'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;

    // check for blank name
    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_vendor']['name'] = sprintf($strFieldMustNotBeBlank, $strVendorName);
    }

    // add product if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbVendors}` (name) VALUES ('{$name}')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        if (!$result)
        {
            html_redirect('vendor_new.php', FAIL, $strAdditionFail);
            exit;
        }
        else
        {
            $id = mysqli_insert_id($db);
            journal(CFG_LOGGING_DEBUG, 'Vendor Added', "Vendor {$id} was added", CFG_JOURNAL_DEBUG, $id);
            html_redirect("vendors.php");
        }
        clear_form_data('new_vendor');
        clear_form_errors('new_vendor');
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>
