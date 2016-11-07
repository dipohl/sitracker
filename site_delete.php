<?php
// delete_site.php - Form for deleting site, moves any associated records to another site the user chooses
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!

require ('core.php');
$permission = PERM_SITE_DELETE; // Delete Sites/Contacts
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$id = clean_int($_REQUEST['id']);
$destinationid = clean_int($_REQUEST['destinationid']);

if (empty($id))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strDeleteSite}</h2>";
    plugin_do('site_delete');
    echo "<form action='{$_SERVER['PHP_SELF']}?action=delete' method='post'>";
    echo "<table class='maintable'>";
    echo "<tr><th>{$strSite}:</th><td>".site_drop_down('id', 0)."</td></tr>";
    plugin_do('site_delete_form');
    echo "</table>";
    echo "<p><input name='submit' type='submit' value='{$strDelete}' /></p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // Look for associated contacts
    $sql = "SELECT COUNT(id) FROM `{$dbContacts}` WHERE siteid='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    list($numcontacts) = mysqli_fetch_row($result);
    
    // Look for associated maintenance contracts
    $sql = "SELECT COUNT(id) FROM `{$dbMaintenance}` WHERE site='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    list($numcontracts) = mysqli_fetch_row($result);
    
    if (empty($destinationid) AND ($numcontacts > 0 OR $numcontracts > 0))
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>{$strDeleteSite}</h2>";
        plugin_do('site_delete');
        $sql = "SELECT * FROM `{$dbSites}` WHERE id='{$id}' LIMIT 1";
        $siteresult = mysqli_query($db, $sql);
        $site = mysqli_fetch_object($siteresult);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        echo "<table class='maintable vertical'>";
        echo "<tr><th>{$strSite}:</th><td><h3>{$site->name}</h3></td></tr>";
        echo "<tr><th>{$strDepartment}:</th><td>{$site->department}</td></tr>";
        echo "<tr><th>{$strAddress1}:</th><td>{$site->address1}</td></tr>";
        echo "</table>";

        plugin_do('site_delete_submitted');

        if ($numcontacts > 0)
        {
            echo "<p align='center' class='warning'>".sprintf($strNumContactsAssignedToSite, $numcontacts)."</p>";
        }


        if ($numcontracts > 0)
        {
            echo "<p align='center' class='warning'>".sprintf($strNumContractsAssignedToSite, $numcontracts)."</p>";
        }

        echo "<p align='center'>{$strInOrderToDelete}</p>";
        echo "<form action='{$_SERVER['PHP_SELF']}?action=delete' method='post'>";
        echo "<table class='maintable'>";
        echo "<tr><th>{$strSite}:</th><td>".site_drop_down('destinationid', 0)."</td></tr>";
        echo "</table>";
        echo "<input type='hidden' name='id' value='{$id}' />";
        echo "<p><input name='submit' type='submit' value='{$strDelete}' /></p>";
        echo "</form>";
            
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else if (empty($destinationid))
    {
        $sql = "DELETE FROM `{$dbSites}` WHERE id='{$id}' LIMIT 1";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
        else
        {
            plugin_do('site_delete_saved');
            html_redirect("sites.php");
        }
    }
    else
    {
        // Records need moving before we delete
        // Move contacts
        $sql = "UPDATE `{$dbContacts}` SET siteid='{$destinationid}' WHERE siteid='{$id}'";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        // Move contracts
        $sql = "UPDATE `{$dbMaintenance}` SET site='{$destinationid}' WHERE site='{$id}'";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        $sql = "DELETE FROM `{$dbSites}` WHERE id='{$id}' LIMIT 1";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        journal(CFG_LOGGING_NORMAL, 'Site Deleted', "Site {$id} was deleted", CFG_JOURNAL_SITES, $id);

        html_redirect("sites.php");
    }
}

?>