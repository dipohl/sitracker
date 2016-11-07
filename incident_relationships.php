<?php
// incident_relationships.php - Displays and allows editing of incident relationships
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
$permission = PERM_INCIDENT_VIEW; // View Incident Details
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);

$title = $strRelations;
include (APPLICATION_INCPATH . 'incident_html_top.inc.php');


// External variables
$action = clean_fixed_list($_REQUEST['action'], array('','add','delete'));
$relatedid = clean_int($_POST['relatedid']);
$relation = clean_fixed_list($_POST['relation'], array('child','parent','sibling'));
$rid = clean_int($_REQUEST['rid']);

switch ($action)
{
    case 'add':
        // First check that the incident we're trying to relate to actually exists
        $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE id = {$relatedid}";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
        list($countincidents) = mysqli_fetch_row($result);
        if ($countincidents > 0)
        {
            // Next check there isn't already a relationship to that incident
            $sql = "SELECT id FROM `{$dbRelatedIncidents}` WHERE (incidentid='{$relatedid}' AND relatedid='{$id}') OR (relatedid='{$relatedid}' AND incidentid='{$id}')";
            $result = mysqli_query($db, $sql);
            if (mysqli_num_rows($result) < 1 AND $relatedid!=$id)
            {
                switch ($relation)
                {
                    case 'sibling':
                        $sql = "INSERT INTO `{$dbRelatedIncidents}` (incidentid, relation, relatedid, owner) ";
                        $sql .= "VALUES ('$id', 'sibling', '$relatedid', '{$_SESSION['userid']}')";
                        mysqli_query($db, $sql);
                        if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);

                        $onwer = incident_owner($id);
                        $status = incident_status($id);
                        // Insert an entry into the update log for this incident
                        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, bodytext) ";
                        $sql .= "VALUES ('{$id}', '{$sit[2]}', 'editing', '{$now}', '{$owner}', '{$status}', 'hide', '" . sprintf($SYSLANG['strAddedRelationshipIncidentX'], $relatedid) . "')";
                        mysqli_query($db, $sql);
                        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

                        $status = incident_status($relatedid);
                        // Insert an entry into the update log for the related incident
                        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, bodytext) ";
                        $sql .= "VALUES ('{$relatedid}', '{$sit[2]}', 'editing', '{$now}', '{$owner}', '{$status}', 'hide', '" . sprintf($SYSLANG['strAddedRelationshipIncidentX'], $id) . "')";
                        mysqli_query($db, $sql);
                        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                        break;
                }
                // TODO v3.2x Child/Parent incident relationships
            }
            else
            {
                $_SESSION['formerrors']['incidentrelationships']['duplicate'] = $strADuplicateAlreadyExists;
            }
        }
        else
        {
            $_SESSION['formerrors']['incidentrelationships']['duplicate'] = sprintf($strNoResultsFor, sprintf($strIncidentNum, $relatedid));
        }
        break;
    case 'delete':
        // Retrieve details of the relationship
        $sql = "SELECT * FROM `{$dbRelatedIncidents}` WHERE id='{$rid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $relation = mysqli_fetch_object($result);

        $sql = "DELETE FROM `{$dbRelatedIncidents}` WHERE id='{$rid}'";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        $onwer = incident_owner($id);
        $status = incident_status($id);
        // Insert an entry into the update log for this incident
        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, bodytext) ";
        $sql .= "VALUES ('{$relation->incidentid}', '{$sit[2]}', 'editing', '{$now}', '{$owner}', '{$status}', 'hide', 'Removed relationship with Incident {$relation->relatedid}')";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        // Insert an entry into the update log for the related incident
        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, bodytext) ";
        $sql .= "VALUES ('{$relation->relatedid}', '{$sit[2]}', 'editing', '{$now}', '{$owner}', '{$status}', 'hide', 'Removed relationship with Incident {$relation->incidentid}')";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        echo "<br /><p class='info' align='center'>{$strRelationshipRemoved}</p>";
        break;
    default:
        // do nothing
}

echo "<h2>{$strRelatedIncidents}</h2>";

echo show_form_errors('incidentrelationships');
clear_form_errors('incidentrelationships');

// Incident relationships
$rsql = "SELECT * FROM `{$dbRelatedIncidents}` WHERE incidentid='{$id}' OR relatedid='{$id}'";
$rresult = mysqli_query($db, $rsql);
if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
if (mysqli_num_rows($rresult) >= 1)
{
    echo "<table summary='Related Incidents' class='maintable'>";
    echo "<tr><th>{$strIncidentID}</th><th>{$strTitle}</th>";
    echo "<th>{$strRelationship}</th><th>{$strCreatedBy}</th>";
    echo "<th>{$strAction}</th></tr>\n";
    while ($related = mysqli_fetch_object($rresult))
    {
        echo "<tr>";
        if ($related->relatedid == $id)
        {
            $incidenttitle = incident_title($related->incidentid);
            if ($related->relation == 'child') $relationship = 'Child';
            else $relationship = 'Sibling';
            echo "<td><a href='incident_details.php?id={$related->incidentid}'>{$related->incidentid}</a></td>";
        }
        else
        {
            $incidenttitle = incident_title($related->relatedid);
            if ($related->relation == 'child') $relationship = 'Parent';
            else $relationship = 'Sibling';
            echo "<td><a href='incident_details.php?id={$related->relatedid}'>{$related->relatedid}</a></td>";
        }
        echo "<td>$incidenttitle</td>";
        echo "<td>$relationship</td>";
        echo "<td>".user_realname($related->owner,TRUE)."</td>";
        echo "<td><a href='incident_relationships.php?id={$id}&amp;rid={$related->id}&amp;action=delete'>{$strRemove}</a></td>";
        echo "</tr>\n";
    }
    echo "</table>";
}
else echo "<p align='center'>{$strNoResults}</p>";
echo "<br /><hr/>";
echo "\n<form action='incident_relationships.php' name='incidentrelationships' method='post'>";
echo "<h2>".icon('new', 32)." {$strNew}</h2>";
echo "<table summary='Add a relationship' class='vertical'>";
echo "<tr><th>{$strIncidentID}</th><td><input type='text' name='relatedid' size='10' class='required' /> ";
echo "<span class='required'>{$strRequired}</span></td></tr>\n";
// TODO v3.24 Child/Parent incident relationships
// echo "<tr><th>Relationship to this incident</th><td>";
// echo "<select name='relation'>";
// echo "<option value='child'>Child</option>";
// echo "<option value='parent'>Parent</option>";
// echo "<option value='sibling'>Sibling</option>";
// echo "</select>";
// echo "</td></tr>\n";
echo "</table>\n";
echo "<input type='hidden' name='action' value='add' />";
echo "<input type='hidden' name='id' value='{$id}' />";
echo "<input type='hidden' name='relation' value='sibling' />";
echo "<p><input type='submit' value='{$strSave}' /></p>";
echo "</form>";

include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
?>