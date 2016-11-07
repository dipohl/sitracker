<?php
// recent_incidents_table.php - Report showing a list of incidents logged in the past month
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Comments: Shows a list of incidents that each site has logged


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strRecentIncidents;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$sites = array();

$monthago = time() - (60 * 60 * 24 * 30.5);

echo "<h2>{$strRecentIncidents}</h2>";
echo "<h3>({$strOpened}: ".sprintf($strSinceX, ldate($CONFIG['dateformat_date'], $monthago)).")</h3>";

$sql  = "SELECT s.name, i.id, i.opened, m.product, s.id AS siteid, i.title FROM `{$dbSites}` AS s, `{$dbContacts}` as c, `{$dbMaintenance}` AS m, `{$dbIncidents}` AS i ";
$sql .= "WHERE s.id = c.siteid ";
$sql .= "AND m.id = i.maintenanceid ";
$sql .= "AND i.contact = c.id ";
$sql .= "AND i.opened > '{$monthago}' ";
$sql .= "ORDER BY s.id, i.id";

$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error("MySQL Query Error: ".mysqli_error($db), E_USER_WARNING);

if (mysqli_num_rows($result) > 0)
{
    $prvincid = 0;
    echo "<table class='maintable' id='recentincidents'>";
    echo "<tr>";
    echo colheader('incident', $strIncident);
    echo colheader('site', $strSite);
    echo colheader('opened', $strOpened);
    echo colheader('product', $strProduct);
    echo "</tr>";
    $shade = 'shade1';
    while ($row = mysqli_fetch_object($result))
    {
        if ($prvincid != $row->id)
        {
            echo "<tr class='{$shade}'>";
            echo "<td><a href=\"javascript:incident_details_window('{$row->id}', 'sit_popup')\">[{$row->id}] {$row->title}</a></td>";
            echo "<td>{$row->name}</strong></td>";
            echo "<td>" . ldate('d M Y', $row->opened) . "</td>";
            echo "<td>".product_name($row->product) . "</td>";
            $site = $row->siteid;
            $$site++;
            $sites[] = $row->siteid;
            echo "</tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        $prvincid = $row->id;
    }
    echo "<table>";
}
else
{
    echo user_alert($strNoRecords, E_USER_NOTICE);
}

$sites = array_unique($sites);

$totals = array();

foreach ($sites AS $site => $val)
{
    if ($prev > $$val) array_push($totals, $val);
    else array_unshift($totals, $val);
    $prev=$$val;
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>