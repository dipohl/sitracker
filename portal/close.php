<?php
// portal/close.inc.php - Request incident closure in the portal included by ../portal.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'any';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

// External vars
$id = clean_int($_REQUEST['id']);
$fail = clean_int($_POST['fail']);

// First check the portal user is allowed to access this incident
$sql = "SELECT contact FROM `{$dbIncidents}` WHERE id = {$id} LIMIT 1";
$result = mysqli_query($db, $sql);
if (mysqli_error($db) ) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
list($incidentcontact) = mysqli_fetch_row($result);
if ($incidentcontact == $_SESSION['contactid'])
{
    $id = clean_int($_REQUEST['id']);

    if (empty($_REQUEST['reason']))
    {
        include (APPLICATION_INCPATH . 'portalheader.inc.php');
        if (!empty($fail)) echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strReason}'"), E_USER_ERROR);
        echo "<h2>".icon('close', 32, $strClosureRequestForIncident);
        echo " {$strClosureRequestForIncident} {$id} - " . incident_title($id) . "</h2>";
        echo "<div id='update' align='center'><form action='{$_SERVER[PHP_SELF]}?page=close&amp;id={$id}' method='post'>";
        echo "<p>{$strReason} <span class='required'>{$strRequired}</span> </p><textarea class='required' name='reason' cols='50' rows='10'></textarea><br />";
        echo "<input type='hidden' name='fail' value='1' />";
        echo "<p><input type='submit' value=\"{$strRequestClosure}\" /></p></form></div>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        if (isset($_SESSION['syslang'])) $SYSLANG = $_SESSION['syslang'];

        $usersql = "SELECT forenames, surname FROM `{$dbContacts}` WHERE id={$_SESSION['contactid']}";
        $result = mysqli_query($db, $usersql);
        $user = mysqli_fetch_object($result);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        $reason = cleanvar("{$SYSLANG['strRequestClosureViaThePortalBy']} [b]{$user->forenames} {$user->surname}[/b]\n\n");
        $reason .= "<b>{$SYSLANG['strReason']}:</b> ".cleanvar($_REQUEST['reason']);
        $owner = incident_owner($id);
        $sql = "INSERT into `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) ";
        $sql .= "VALUES({$id}, '0', 'customerclosurerequest',  '{$owner}', '1', '{$reason}', '{$now}', 'show')";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        $t = new TriggerEvent('TRIGGER_PORTAL_INCIDENT_REQUESTCLOSURE', array('incidentid' => $id));

        //set incident back to active
        $sql = "UPDATE `{$dbIncidents}` SET status=".STATUS_ACTIVE.", lastupdated={$now} WHERE id={$id}";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        html_redirect("index.php");
    }
}
else
{
    include (APPLICATION_INCPATH . 'portalheader.inc.php');
    echo "<p class='warning'>{$strNoPermission}.</p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    exit;
}

?>