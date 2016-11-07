<?php
// billing_matrix_new.php - Page to add a new Unit based billing matrix
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission =  PERM_BILLING_DURATION_EDIT;  // TODO we need a permission to administer billing matrixes;  // TODO we need a permission to administer billing matrixes
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewBillingMatrix;

$action = $_REQUEST['action'];

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('billing', 32)." {$title}</h2>";
    plugin_do('billing_matrix_new');

    echo show_form_errors('billing_matrix_new');
    clear_form_errors('billing_matrix_new');

    echo "<form name='billing_matrix_new' action='{$_SERVER['PHP_SELF']}' method='post'>";

    echo "<p align='center'>{$strTag}: <input type='text' name='tag' value='".show_form_value('billing_matrix_new', 'tag')."' /></p>";

    echo "<table class='maintable'>";

    echo "<tr><th>{$strHour}</th><th>{$strMonday}</th><th>{$strTuesday}</th>";
    echo "<th>{$strWednesday}</th><th>{$strThursday}</th><th>{$strFriday}</th>";
    echo "<th>{$strSaturday}</th><th>{$strSunday}</th><th>{$strPublicHoliday}</th></tr>\n";

    $hour = 0;

    $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

    while ($hour < 24)
    {
        echo "<tr><th>{$hour}</th>";

        foreach ($days AS $day)
        {
            $id = "{$day}_{$hour}";

            if (!empty($_SESSION['formdata']['billing_matrix_new'][$id])) $i = $_SESSION['formdata']['billing_matrix_new'][$id];
            else $i = '';
            echo "<td>".billing_multiplier_dropdown($id, $i)."</td>";
        }

        echo "</tr>";
        $hour++;
    }
    plugin_do('billing_matrix_new_form');
    echo "</table>";

    echo "<input type='hidden' name='action' value='new' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' />  ";
    echo "<input type='submit' value='{$strSave}' /></p>";
    echo "<p class='return'><a href=\"billing_matrix.php\">{$strReturnWithoutSaving}</a></p>";

    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "new")
{
    $tag = clean_dbstring($_REQUEST['tag']);

    // Check input
    $errors = 0;
    if (empty($tag))
    {
        $errors++;
        $_SESSION['formerrors']['billing_matrix_new']['tag'] = sprintf($strFieldMustNotBeBlank, $strTag);
    }
    plugin_do('billing_matrix_new_submitted');

    $sql = "SELECT tag FROM `{$dbBillingMatrixUnit}` WHERE tag='{$tag}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['billing_matrix_new']['tag1'] = sprintf($strADuplicateAlreadyExists, $strTag);
    }


    if ($errors >= 1)
    {
        $_SESSION['formdata']['billing_matrix_new'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));

        // show error message if errors
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
    else
    {
        $values = array();

        $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

        $hour = 0;

        while ($hour < 24)
        {
            $values[$hour] = array();

            $mon = clean_float($_REQUEST["mon_{$hour}"]);
            $tue = clean_float($_REQUEST["tue_{$hour}"]);
            $wed = clean_float($_REQUEST["wed_{$hour}"]);
            $thu = clean_float($_REQUEST["thu_{$hour}"]);
            $fri = clean_float($_REQUEST["fri_{$hour}"]);
            $sat = clean_float($_REQUEST["sat_{$hour}"]);
            $sun = clean_float($_REQUEST["sun_{$hour}"]);
            $holiday = clean_float($_REQUEST["holiday_{$hour}"]);

            $sql = "INSERT INTO `{$dbBillingMatrixUnit}` (tag, hour, mon, tue, wed, thu, fri, sat, sun, holiday) ";
            $sql .= "VALUES ('{$tag}', {$hour}, {$mon}, {$tue}, {$wed}, {$thu}, {$fri}, {$sat}, {$sun}, {$holiday})";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db))
            {
                $errors++;
                trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
                break; // Dont try and add any more
            }

            $hour++;
        }

        if ($errors >= 1)
        {
            html_redirect("billing_matrix.php", FALSE, $strBillingMatrixAddFailed);
        }
        else
        {
            clear_form_data('billing_matrix_new');
            clear_form_errors('billing_matrix_new');
            plugin_do('billing_matrix_new_saved');
            html_redirect("billing_matrix.php", TRUE);
        }
    }
}