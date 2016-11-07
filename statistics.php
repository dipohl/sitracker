<?php
// statistics.php - Over view and stats of calls logged - intended for last 24hours
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_INCIDENT_LIST; // View incidents
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title=  $strTodaysStats;

$mode = clean_fixed_list($_REQUEST['mode'], array('','detail','daybreakdown','breakdown','overview'));

/**
    * @author Paul Heaney
*/
function get_sql_statement($startdate,$enddate,$statementnumber,$count=TRUE)
{
    global $dbIncidents, $dbUpdates;

    if ($count) $count = "count(*)";
    else $count = "*";
    $sql[0] = "SELECT {$count} FROM `{$GLOBALS['dbIncidents']}` WHERE opened BETWEEN '{$startdate}' AND '{$enddate}'";
    $sql[1] = "SELECT {$count} FROM `{$GLOBALS['dbIncidents']}` WHERE closed BETWEEN '{$startdate}' AND '{$enddate}'";
    $sql[2] = "SELECT {$count} FROM `{$GLOBALS['dbIncidents']}` WHERE lastupdated BETWEEN '{$startdate}' AND '{$enddate}'";
    $sql[3] = "SELECT {$count} FROM `{$GLOBALS['dbIncidents']}` WHERE opened <= '{$enddate}' AND (closed >= '$startdate' OR closed = 0)";
    $sql[4] = "SELECT count(*), count(DISTINCT userid) FROM `{$GLOBALS['dbUpdates']}` WHERE timestamp >= '$startdate' AND timestamp <= '$enddate'";
    $sql[5] = "SELECT count(DISTINCT softwareid), count(DISTINCT owner) FROM `{$GLOBALS['dbIncidents']}` WHERE opened <= '{$enddate}' AND (closed >= '$startdate' OR closed = 0)";
    $sql[6] = "SELECT {$count} FROM `{$GLOBALS['dbUpdates']}` WHERE timestamp >= '$startdate' AND timestamp <= '$enddate' AND type='email'";
    $sql[7] = "SELECT {$count} FROM `{$dbUpdates}` WHERE timestamp >= '$startdate' AND timestamp <= '$enddate' AND type='emailin'";
    $sql[8] = "SELECT {$count} FROM `{$dbIncidents}` WHERE opened <= '{$enddate}' AND (closed >= '$startdate' OR closed = 0) AND priority >= " . PRIORITY_HIGH;
    return $sql[$statementnumber];
}


/**
    * Show Open, Closed, Updated today, this week, this month etc.
    * @author Paul Heaney
*/
function count_incidents($startdate, $enddate)
{
    global $db;
    // Counts the number of incidents opened between a start date and an end date
    // Returns an associative array
    // 0
    $sql = get_sql_statement($startdate, $enddate, 0);
    $result = mysqli_query($db, $sql);
    list($count['opened']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 1
    $sql = get_sql_statement($startdate, $enddate, 1);
    $result = mysqli_query($db, $sql);
    list($count['closed']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 2
    $sql = get_sql_statement($startdate, $enddate, 2);
    $result = mysqli_query($db, $sql);
    list($count['updated']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 3
    $sql = get_sql_statement($startdate, $enddate, 3);
    $result = mysqli_query($db, $sql);
    list($count['handled']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 4
    $sql = get_sql_statement($startdate, $enddate, 4);
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    list($count['updates'], $count['users']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 5
    $sql = get_sql_statement($startdate, $enddate, 5);
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    list($count['skills'], $count['owners']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 6
    $sql = get_sql_statement($startdate, $enddate, 6);
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    list($count['emailtx']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 7
    $sql = get_sql_statement($startdate, $enddate, 7);
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    list($count['emailrx']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    // 8
    $sql = get_sql_statement($startdate, $enddate, 8);
    $result = mysqli_query($db, $sql);
    list($count['higherpriority']) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $count;
}


/**
    * @author Paul Heaney
    * @returns string. HTML
*/
function stats_period_row($desc, $start, $end)
{
    global $shade;
    if ($shade == '') $shade = 'shade1';
    $count = count_incidents($start, $end);

    if ($count['users'] > 0)
    {
        $updatesperuser = @number_format($count['updates']/$count['users'], 2);
    }
    else
    {
        $updatesperuser = 0;
    }

    if ($count['updated'] > 0)
    {
        $updatesperincident = @number_format($count['updates']/$count['updated'], 2);
    }
    else
    {
        $updatesperincident = 0;
    }

    if ($count['owners'] > 0)
    {
        $incidentsperowner = @number_format($count['handled']/$count['owners'], 2);
    }
    else
    {
        $incidentsperowner = 0;
    }
/*
    $workload = $count['handled'] + $count['emailrx'] + $count['skills'] + $count['updates'] + $count['higherpriority'];
    $resource = $count['owners'] + $count['users'] + $count['emailtx'] + ($count['opened'] - $count['closed']);
    $busyrating = ($resource / $workload * 100);
    $busyrating = @number_format($busyrating * 4.5,1);
*/
    if ($count['updated'] > 10)
    {
        $freshness = ($count['updated'] / $count['handled'] * 100);
    }
    else
    {
        $freshness = $count['updated'];
    }

    if ($count['owners'] > 0)
    {
        $load = (($count['handled'] / $count['owners']) / $count['handled'] * 100);
    }
    else
    {
        $load = 0;
    }

    if ($count['updates'] > 10)
    {
        $busyness = (($count['updates'] / $count['users']) / $count['updates'] * 100);
    }
    else
    {
        $busyness = $count['updates'];
    }

    if ($count['users'] > 0 && $count['emailtx'] > 0)
    {
        $busyness2 = (($count['emailtx'] / $count['users']) / $count['handled'] * 100);
    }
    else
    {
        $busyness2 = 0;
    }

    $activity = ($freshness + $load + $busyness + $busyness2 / 400 * 100);
    $activity = @number_format($activity, 1);
    if ($activity > 100) $activity = 100;
    if ($activity < 0) $activity = 0;

    $html = "<tr class='{$shade}'><td>{$desc}</td>";
    $html .= "<td><a href='{$_SERVER['PHP_SELF']}?mode=breakdown&amp;query=0&amp;start={$start}&amp;end={$end}'>{$count['opened']}</a></td>";
    $html .= "<td><a href='{$_SERVER['PHP_SELF']}?mode=breakdown&amp;query=2&amp;start={$start}&amp;end={$end}'>{$count['updated']}</a></td>";
    $html .= "<td><a href='{$_SERVER['PHP_SELF']}?mode=breakdown&amp;query=1&amp;start={$start}&amp;end={$end}'>{$count['closed']}</a></td>";
    $html .= "<td>{$count['handled']}</td>";
    $html .= "<td>{$count['updates']}</td>";
    $html .= "<td>{$updatesperincident}</td>";
    $html .= "<td>{$count['skills']}</td>";
    $html .= "<td>{$count['owners']}</td>";
    $html .= "<td>{$count['users']}</td>";
    $html .= "<td>{$updatesperuser}</td>";
    $html .= "<td>{$incidentsperowner}</td>";
    $html .= "<td>{$count['emailrx']}</td><td>{$count['emailtx']}</td>";
    $html .= "<td>{$count['higherpriority']}</td>";
    $html .= "<td>".percent_bar($activity)."</td>";
    $html .= "</tr>\n";
    if ($shade == 'shade1') $shade = 'shade2';
    else $shade = 'shade1';
    return $html;
}


/**
    * @author Paul Heaney
*/
function give_overview()
{
    global $todayrecent, $mode, $CONFIG, $db;

    echo "<table class='maintable'>";
    echo "<tr><th>{$GLOBALS['strPeriod']}</th>";
    echo "<th>{$GLOBALS['strOpened']}</th><th>{$GLOBALS['strUpdated']}</th>";
    echo "<th>{$GLOBALS['strClosed']}</th><th>{$GLOBALS['strHandled']}</th>";
    echo "<th>{$GLOBALS['strUpdates']}</th><th>{$GLOBALS['strPerIncident']}</th><th>{$GLOBALS['strSkills']}</th>";
    echo "<th>{$GLOBALS['strOwners']}</th><th>{$GLOBALS['strUsers']}</th>";
    echo "<th>{$GLOBALS['strPerUser']}</th><th>{$GLOBALS['strIncidentPerOwnerAbbrev']}</th><th>{$GLOBALS['strEmailReceivedAbbrev']}</th>";
    echo "<th>{$GLOBALS['strEmailTransmittedAbbrev']}</th><th>{$GLOBALS['strHigherPriority']}</th>";
    echo "<th>{$GLOBALS['strActivity']}</th></tr>\n";

    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=0'>{$GLOBALS['strToday']}</a>", mktime(0, 0, 0, date('m'), date('d'), date('Y')), mktime(23, 59, 59, date('m'), date('d'), date('Y')));
    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=1'>{$GLOBALS['strYesterday']}</a>", mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')),mktime(23, 59, 59, date('m'), date('d') - 1, date('Y')));
    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=2'>".ldate('l',mktime(0, 0, 0, date('m'), date('d') - 2, date('Y')))."</a>", mktime(0,0,0,date('m'),date('d')-2,date('Y')),mktime(23, 59, 59, date('m'), date('d') - 2, date('Y')));
    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=3'>".ldate('l',mktime(0, 0, 0, date('m'), date('d') - 3, date('Y')))."</a>", mktime(0,0,0,date('m'),date('d')-3,date('Y')),mktime(23, 59, 59, date('m'), date('d') - 3, date('Y')));
    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=4'>".ldate('l',mktime(0, 0, 0, date('m'), date('d') - 4, date('Y')))."</a>", mktime(0,0,0,date('m'),date('d')-4,date('Y')),mktime(23, 59, 59, date('m'), date('d') - 4, date('Y')));
    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=5'>".ldate('l',mktime(0, 0, 0, date('m'), date('d') - 5, date('Y')))."</a>", mktime(0,0,0,date('m'),date('d')-5,date('Y')),mktime(23, 59, 59, date('m'), date('d') - 5, date('Y')));
    echo stats_period_row("<a href='{$_SERVER['PHP_SELF']}?mode=daybreakdown&amp;offset=6'>".ldate('l',mktime(0, 0, 0, date('m'), date('d') - 6, date('Y')))."</a>", mktime(0,0,0,date('m'),date('d')-6,date('Y')),mktime(23, 59, 59, date('m'), date('d') - 6, date('Y')));
    echo "<tr><td colspan='*'></td></tr>";
    echo stats_period_row($GLOBALS['strThisWeek'], mktime(0,0,0,date('m'),date('d')-6,date('Y')),mktime(23,59,59,date('m'),date('d'),date('Y')));
    echo stats_period_row($GLOBALS['strLastWeek'], mktime(0,0,0,date('m'),date('d')-13,date('Y')),mktime(23,59,59,date('m'),date('d')-7,date('Y')));
    echo "<tr><td colspan='*'></td></tr>";

    if ($mode == 'detail')
    {
        echo stats_period_row($GLOBALS['strThisMonth'], mktime(0, 0, 0, date('m'), 1, date('Y')), mktime(23, 59, 59, date('m'), date('d'), date('Y')));
        echo stats_period_row($GLOBALS['strLastMonth'], mktime(0, 0, 0, date('m') - 1, date('d'), date('Y')), mktime(23, 59, 59, date('m'), 0, date('Y')));
        echo stats_period_row(date('F y',mktime(0, 0, 0, date('m') -2, 1, date('Y'))), mktime(0, 0, 0, date('m') - 2, date('d'), date('Y')), mktime(23, 59, 59, date('m') -1, 0, date('Y')));
        echo stats_period_row(date('F y',mktime(0, 0, 0, date('m') -3, 1, date('Y'))), mktime(0, 0, 0, date('m') - 3, date('d'), date('Y')), mktime(23, 59, 59, date('m') -2, 0, date('Y')));
        echo stats_period_row(date('F y',mktime(0, 0, 0, date('m') -4, 1, date('Y'))), mktime(0, 0, 0, date('m') - 4, date('d'), date('Y')), mktime(23, 59, 59, date('m') -3, 0, date('Y')));
        echo stats_period_row(date('F y',mktime(0, 0, 0, date('m') -5, 1, date('Y'))), mktime(0, 0, 0, date('m') - 5, date('d'), date('Y')), mktime(23, 59, 59, date('m') -4, 0, date('Y')));
        echo stats_period_row(date('F y',mktime(0, 0, 0, date('m') -6, 1, date('Y'))), mktime(0, 0, 0, date('m') - 6, date('d'), date('Y')), mktime(23, 59, 59, date('m') -5, 0, date('Y')));
        echo "<tr><td colspan='*'></td></tr>";
        echo stats_period_row($GLOBALS['strThisYear'], mktime(0, 0, 0, 1, 1, date('Y')), mktime(23, 59, 59, date('m'), date('d'), date('Y')));
        echo stats_period_row($GLOBALS['strLastYear'], mktime(0, 0, 0, 1, 1, date('Y') - 1), mktime(23, 59, 59, 12, 31, date('Y') - 1));
        echo stats_period_row(date('Y',mktime(0, 0, 0, 1, 1, date('Y') - 2)), mktime(0, 0, 0, 1, 1, date('Y') - 2), mktime(23, 59, 59, 12, 31, date('Y') - 2));
        echo stats_period_row(date('Y',mktime(0, 0, 0, 1, 1, date('Y') - 3)), mktime(0, 0, 0, 1, 1, date('Y') - 3), mktime(23, 59, 59, 12, 31, date('Y') - 3));
        echo stats_period_row(date('Y',mktime(0, 0, 0, 1, 1, date('Y') - 4)), mktime(0, 0, 0, 1, 1, date('Y') - 4), mktime(23, 59, 59, 12, 31, date('Y') - 4));
        echo stats_period_row(date('Y',mktime(0, 0, 0, 1, 1, date('Y') - 5)), mktime(0, 0, 0, 1, 1, date('Y') - 5), mktime(23, 59, 59, 12, 31, date('Y') - 5));
    }
    echo "</table>\n";

    echo "<br />\n";

    $sql = "SELECT DISTINCT g.id AS groupid, g.name FROM `{$GLOBALS['dbGroups']}` AS g ";
    //$sql .= "WHERE (incidents.status != 2 AND incidents.status != 7) AND incidents.owner = users.id AND users.groupid = groups.id ORDER BY groups.id";

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) > 1)
    {
        echo "<h2>{$GLOBALS['strByGroup']}</h2>";
        echo "<table class='vertical maintable'><tr>";
        while ($groups = mysqli_fetch_object($result))
        {
            $sqlGroups = "SELECT COUNT(i.id) AS count, istatus.name ";
            $sqlGroups .= "FROM `{$GLOBALS['dbIncidents']}` AS i, ";
            $sqlGroups .= "`{$GLOBALS['dbIncidentStatus']}` AS istatus, ";
            $sqlGroups .= "`{$GLOBALS['dbUsers']}` AS u, `{$GLOBALS['dbGroups']}` AS g ";
            $sqlGroups .= "WHERE i.status = istatus.id AND closed = 0 AND i.owner = u.id ";
            $sqlGroups .= "AND u.groupid = g.id AND u.groupid = {$groups->groupid} ";
            $sqlGroups .= "GROUP BY i.status ";
            $resultGroups = mysqli_query($db, $sqlGroups);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

            if (mysqli_num_rows($resultGroups) > 0)
            {
                $openCallsGroup = 0;
                echo "<td style='vertical-align:top' align='center' colspan='2'><strong>{$groups->name}</strong>";
                echo "<table class='vertical maintable'>";
                while ($rowGroup = mysqli_fetch_object($resultGroups))
                {
                    echo "<tr><th>{$GLOBALS[$rowGroup->name]}</th><td class='shade2' align='left'>";
                    echo "{$rowGroup->count}</td></tr>";

                    $openCallsGroup += $rowGroup->count;
                }
                echo "<tr><th>{$GLOBALS['strTotalOpen']}</th>";
                echo "<td class='shade2' align='left'><strong>{$openCallsGroup}</strong></td></tr></table></td>";
            }
        }
        echo "</tr></table>";
    }
    plugin_do('statistics_content');

    mysqli_free_result($result);

    //count incidents by Vendor
    $sql = "SELECT DISTINCT s.vendorid, v.name FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbSoftware']}` AS s, `{$GLOBALS['dbVendors']}` AS v ";
    $sql .= "WHERE (status != 2 AND status != 7) AND i.softwareid = s.id AND v.id = s.vendorid ORDER BY vendorid";

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) > 1)
    {
        echo "<h2>{$GLOBALS['strByVendor']}</h2>";
        echo "<table class='vertical maintable'><tr>";
        while ($vendors = mysqli_fetch_object($result))
        {
            // This should use the software and relate to the product and then to the vendor
            /*
            $sqlVendor = "SELECT COUNT(incidents.id), incidentstatus.name FROM incidents, incidentstatus, products ";
            $sqlVendor .= "WHERE incidents.status = incidentstatus.id AND closed = 0 AND incidents.product = products.id ";
            $sqlVendor .= "AND products.vendorid = ".$vendors['vendorid']." ";
            $sqlVendor .= "GROUP BY incidents.status";
            */

            $sqlVendor = "SELECT COUNT(i.id) AS count, istatus.name FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbIncidentStatus']}` AS istatus, `{$GLOBALS['dbSoftware']}` AS s ";
            $sqlVendor .= "WHERE i.status = istatus.id AND closed = 0 AND i.softwareid = s.id ";
            $sqlVendor .= "AND s.vendorid = {$vendors->vendorid} ";
            $sqlVendor .= "GROUP BY i.status";

            $resultVendor = mysqli_query($db, $sqlVendor);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

            if (mysqli_num_rows($resultVendor) > 0)
            {
                $openCallsVendor = 0;
                echo "<td style='vertical-align:top' align='center' colspan='2'><strong>{$vendors->name}</strong>";
                echo "<table class='vertical maintable'>";
                while ($rowVendor = mysqli_fetch_object($resultVendor))
                {
                    echo "<tr><th>{$GLOBALS[$rowVendor->name]}</th><td class='shade2' align='left'>";
                    echo "{$rowVendor->count}</td></tr>";

                    if (strpos(strtolower($rowVendor->name), "clos") === false)
                    {
                        $openCallsVendor += $rowVendor->count;
                    }
                }
                echo "<tr><th>{$GLOBALS['strTotalOpen']}</th>";
                echo "<td class='shade2' align='left'><strong>{$openCallsVendor}</strong></td></tr></table></td>\n";
            }
        }
        echo "</tr></table>";
    }


    // Count incidents logged today
    $sql = "SELECT id FROM `{$GLOBALS['dbIncidents']}` WHERE opened > '{$todayrecent}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $todaysincidents = mysqli_num_rows($result);
    mysqli_free_result($result);

    $string = "<h4>".sprintf($GLOBALS['strIncidentsLoggedToday'], $todaysincidents)."</h4>";
    if ($todaysincidents > 0)
    {
        $string .= "<table align='center' width='50%'><tr><td colspan='2'>{$GLOBALS['strAssignedAsFollows']}</td></tr>";
        $sql = "SELECT COUNT(i.id) AS count, realname, u.id AS owner FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbUsers']}` AS u WHERE opened > '{$todayrecent}' AND i.owner = u.id GROUP BY owner DESC";

        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        while ($row = mysqli_fetch_object($result))
        {
            $sql = "SELECT id, title FROM `{$GLOBALS['dbIncidents']}` WHERE opened > '{$todayrecent}' AND owner = '{$row->owner}'";

            $string .= "<tr><th>{$row->count}</th>";
            $string .= "<td class='shade2' align='left'>";
            $string .= "<a href='incidents.php?user={$row->owner}&amp;queue=1&amp;type=support'>{$row->realname}</a> ";

            $iresult = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

            while ($irow = mysqli_fetch_object($iresult))
            {
                $string .= "<small>".html_incident_popup_link($irow->id, get_userfacing_incident_id_email($irow->id), $irow->title)."</small> ";
            }

            $string .= "</td></tr>";
        }
        $string .= "</table>";
    }


    // Count incidents closed today
    $sql = "SELECT COUNT(id) FROM `{$GLOBALS['dbIncidents']}` WHERE closed > '{$todayrecent}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
    list($todaysclosed) = mysqli_fetch_row($result);

    $string .= "<h4>".sprintf($GLOBALS['strIncidentsClosedToday'], $todaysclosed)."</h4>";
    if ($todaysclosed > 0)
    {
        $sql = "SELECT COUNT(i.id) AS count, realname, u.id AS owner FROM `{$GLOBALS['dbIncidents']}` AS i ";
        $sql .= "LEFT JOIN `{$GLOBALS['dbUsers']}` AS u ON i.owner = u.id WHERE closed > '{$todayrecent}' ";
        $sql .= "GROUP BY owner";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        $string .= "<table align='center' width='50%'>";
        $string .= "<tr><th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strTitle']}</th>";
        $string .= "<th>{$GLOBALS['strOwner']}</th><th>{$GLOBALS['strClosingStatus']}</th></tr>\n";

        while ($row = mysqli_fetch_object($result))
        {
            $string .= "<tr><th colspan='4' align='left'>{$row->count} {$GLOBALS['strClosedBy']} {$row->realname}</th></tr>\n";

            $sql = "SELECT i.id, i.title, cs.name ";
            $sql .= "FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbClosingStatus']}` AS cs ";
            $sql .= "WHERE i.closingstatus = cs.id AND closed > '{$todayrecent}' ";
            $sql .= "AND i.owner = '{$row->owner}' ";
            $sql .= "ORDER BY closed";

            $iresult = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            while ($irow = mysqli_fetch_object($iresult))
            {
                $string .= "<tr><th>".html_incident_popup_link($irow->id, "[{$irow->id}]", $irow->title)."</th>";
                $string .= "<td class='shade2' align='left'>{$irow->title}</td>";
                $string .= "<td class='shade2' align='left'>{$row->realname}</td>";
                $string .= "<td class='shade2'>{$GLOBALS[$irow->name]}</td></tr>\n";
            }
        }
        $string .= "</table>\n\n";
    }

    mysqli_free_result($result);

    $totalresult = 0;
    $numquestions = 0;
    $qsql = "SELECT * FROM `{$GLOBALS['dbFeedbackQuestions']}` WHERE formid='1' AND type='rating' ORDER BY taborder";
    $qresult = mysqli_query($db, $qsql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($qresult) >= 1)
    {
        $string .= "<h2>{$GLOBALS['strCustomerFeedback']}</h2>";
        $string .= "<table class='maintable vertical'>";
        while ($qrow = mysqli_fetch_object($qresult))
        {
            $numquestions++;
            $string .= "<tr><th>Q{$qrow->taborder}: {$qrow->question}</th>";
            $sql = "SELECT * FROM `{$GLOBALS['dbFeedbackRespondents']}` AS fr, `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbUsers']}` AS u, `{$GLOBALS['dbFeedbackResults']}` AS fres ";
            $sql .= "WHERE fr.incidentid=i.id ";
            $sql .= "AND i.owner=u.id ";
            $sql .= "AND fr.id=fres.respondentid ";
            $sql .= "AND fres.questionid='{$qrow->id}' ";
            $sql .= "AND fr.completed = 'yes' \n";
            $sql .= "ORDER BY i.owner, i.id";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
            $numsurveys = mysqli_num_rows($result);
            $numresults = 0;
            $cumul = 0;
            $percent = 0;
            $average = 0;

            while ($row = mysqli_fetch_object($result))
            {
                if (!empty($row->result))
                {
                    $cumul += $row->result;
                    $numresults++;
                }
            }
            if ($numresults > 0) $average = number_format(($cumul / $numresults), 2);
            $percent = number_format((($average -1) * (100 / ($CONFIG['feedback_max_score'] -1))), 0);
            $totalresult += $average;
            $string .= "<td>{$average}</td></tr>";
            // <strong>({$percent}%)</strong><br />";
        }
        $string .= "</table>\n";
        $total_average = number_format($totalresult / $numquestions, 2);
        $total_percent = number_format((($total_average -1) * (100 / ($CONFIG['feedback_max_score'] -1))), 0);
        if ($total_percent < 0) $total_percent = 0;
        $string .= "<p align='center'>";
        $string .= sprintf($GLOBALS['strPositivityXXfromXFormsCompleted'], $total_average, "{$total_percent}%", $numsurveys);
        $string .= "</p>";
        $surveys += $numresults;
    }
    return $string;
}

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

switch ($mode)
{
    case 'breakdown':
        $query = clean_int($_REQUEST['query']);
        // These are dates though the included file does a few different things and needs the raw UNIX Timestamp
        $startdate = clean_int($_REQUEST['start']);
        $enddate = clean_int($_REQUEST['end']);
        include (APPLICATION_INCPATH . 'statistics_breakdown.inc.php');
        break;
    case 'daybreakdown':
        $offset = clean_int($_REQUEST['offset']);
        include (APPLICATION_INCPATH . 'statistics_daybreakdown.inc.php');
        break;
    case 'overview': //this is the default so just fall though
    default:
        echo "<h2>".icon('statistics', 32)." {$title} - {$strOverview}</h2>";
        plugin_do('statistics');
        echo give_overview();
        break;
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
