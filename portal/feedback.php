<?php
// feedback.php - Displays a listing of all feedback forms awaiting
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Carsten Jensen <carsten[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
$accesslevel = 'any';
require (APPLICATION_LIBPATH . 'portalauth.inc.php');

if (($CONFIG['portal_feedback_enabled'] == FALSE) OR ($CONFIG['feedback_enabled'] == FALSE AND $CONFIG['portal_feedback_enabled'] == TRUE))
{
    header("Location: index.php");
}

$sql = "SELECT formid, incidentid, created, email FROM `{$dbFeedbackRespondents}` ";
$sql .= "WHERE contactid = '{$_SESSION['contactid']}' ";
$sql .= "AND completed = 'no'";
$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
if (mysqli_num_rows($result) < 1)
{
    $html = user_alert($strNoFeedbackFormsAvailable, E_USER_INFO);
}
else
{
    $shade = 'shade1';
    $html = "<table class='maintable'>";
    $html .= "<tr>";
    $html .= colheader('created', $strDate);
    $html .= colheader('incident', $strIncident);
    $html .= colheader('email', $strEmail);
    $html .= colheader('action', $strAction);

    $html .= "</tr>";
    while ($row = mysqli_fetch_object($result))
    {
        $hashcode = feedback_hash($row->formid, $_SESSION['contactid'], $row->incidentid);
        $html .= "<tr class='{$shade}'>";
        $html .= "<td>".ldate($CONFIG['dateformat_datetime'], mysql2date($row->created))."</td>";
        $html .= "<td>[{$row->incidentid}] - ".incident_title($row->incidentid)."</td>";
        $html .= "<td>{$row->email}</td>";
        $html .= "<td><a target='_blank' href='" . application_url() . "feedback.php?ax={$hashcode}'>{$strView}</a></td></tr>";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    $html .= "</table>";
}



include (APPLICATION_INCPATH . 'portalheader.inc.php');

echo "<h2>".icon('reports', 32)." {$strFeedbackForms}</h2>";
echo $html;

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>