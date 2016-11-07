<?php

// external_engineers.php - Shows incidents that have been escalated
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Paul Heaney <paul[at]sitracker.org>
//          Kieran Hogg <kieran[at]sitracker.org>
// heavily based on the Salford Report by Paul Heaney

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
include (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strExternalEngineerCallDistribution;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$filterby = clean_fixed_list($_REQUEST['filterby'], array('','sla','maintenanceid','softwareid','product'));
$filter = cleanvar($_REQUEST['filter']);

echo "<h2>".icon('reports', 32)." {$strExternalEngineerCallDistribution}</h2>";

$filterSQL = '';

if (!empty($filterby))
{
    switch ($filterby)
    {
        case 'sla':
            $filterSQL = "AND i.servicelevel = '{$filter}' ";
            $slaChecked = "checked='yes'";
            break;
        case 'maintenanceid':
            $filterSQL = "AND i.maintenanceid = '{$filter}' ";
            $maintenanceChecked = "checked='yes'";
            break;
        case 'softwareid':
            $filterSQL = "AND i.softwareid = '{$filter}' ";
            $softwareChecked = "checked='yes'";
            break;
        case 'product':
            $filterSQL = "AND i.product = '{$filter}' ";
            $productCheck = "checked='yes'";
            break;
        default:
            $noneChecked = "checked='yes'";
            break;
     }
}


echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='filterform'><p align='center'>\n";
echo "{$strFilter}:\n";
echo "<label><input type='radio' name='filterby' value='none' checked='checked' onclick=\"set_object_visibility('filter', true);\" {$nonChecked} />{$strNone}</label> \n";
echo "<label><input type='radio' name='filterby' value='sla' onclick=\"get_and_display('ajaxdata.php?action=slas', 'filter'); set_object_visibility('filter', false);\" {$slaChecked} />{$strBySLA}</label> \n";
echo "<label><input type='radio' name='filterby' value='softwareid' onclick=\"get_and_display('ajaxdata.php?action=skills', 'filter'); set_object_visibility('filter', false);\" {$softwareChecked} />{$strBySkill}</label> \n";
echo "<label><input type='radio' name='filterby' value='product' onclick=\"get_and_display('ajaxdata.php?action=products', 'filter'); set_object_visibility('filter', false);\" {$productCheck} />{$strByProduct}</label> \n";
echo "<br /><br />\n";
echo "<select id='filter' name='filter' style='display:none;'>\n";
echo "<option />";
echo "</select>\n";

if (!empty($filterby))
{
    echo "<script type='text/javascript'>\n//<![CDATA[\n";
    switch ($filterby)
    {
        case 'sla':
            echo "get_and_display('ajaxdata.php?action=slas&selected={$filter}', 'filter'); set_object_visibility('filter', false);";
            break;
        case 'softwareid':
            echo "get_and_display('ajaxdata.php?action=skills&selected={$filter}', 'filter'); set_object_visibility('filter', false);";
            break;
        case 'product':
            echo "get_and_display('ajaxdata.php?action=products&selected={$filter}', 'filter'); set_object_visibility('filter', false);";
            break;
        default:
            echo "hide_filter(true);";
            break;
     }
     echo "\n//]]>\n</script>";
}
echo "<br /><br /><input type='submit' name='go' value='{$strRunReport}' />";
echo "</p></form>";

$sql = "SELECT id, name FROM `{$dbEscalationPaths}`";
$escs = mysqli_query($db, $sql);
while ($escalations = mysqli_fetch_object($escs))
{
    $c['4'] = 0;
    $c['3'] = 0;
    $c['2'] = 0;
    $c['1'] = 0;
    $total = 0;

    $html .= "<h3>{$escalations->name}</h3>";

    $sql = "SELECT i.*, sw.name, c.forenames, c.surname, s.name AS siteName ";
    $sql .= "FROM `{$dbIncidents}` AS i, `{$dbSoftware}` AS sw, `{$dbContacts}` AS c, `{$dbSites}` AS s ";
    $sql .= "WHERE escalationpath = '{$escalations->id}' AND closed = '0' AND sw.id = i.softwareid ";
    $sql .= " AND i.contact = c.id AND c.siteid = s.id ";

    $sql .= $filterSQL;

    $sql .= "ORDER BY externalengineer";

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

    $i = 0;
    while ($obj = mysqli_fetch_object($result))
    {
        $name = $obj->externalengineer;
        if (empty($name)) $name = $strNoNameAssociated;
        $esc[$name]['name'] = $name;
        $esc[$name]['count']++;
        $esc[$name][$obj->priority]++;
        $str = "<span><strong>".$obj->forenames." ".$obj->surname."</strong><br />{$obj->siteName}</span>";
        $esc[$name]['calls'][$i]['text'] = "<a href=\"javascript:incident_details_window('{$obj->id}', 'sit_popup')\"  title=\"{$obj->title}\" class='info'>[{$obj->id}]{$str}</a> #{$obj->externalid} {$obj->title}";
        $esc[$name]['calls'][$i]['text'] .= "<br />".contact_realname($obj->contact).', '.contact_site($obj->contact);
        $esc[$name]['calls'][$i]['software'] = $obj->name;
        $esc[$name]['calls'][$i]['status'] = $obj->status;
        $esc[$name]['calls'][$i]['localowner'] = $obj->owner;
        $esc[$name]['calls'][$i]['salfordtowner'] = $obj->towner;
        $i++;
    }
    if (!empty($esc))
    {
        $html .= "<table class='maintable'>";
        $html .= "<tr><th>{$strExternalEngineersName}</th><th>{$strNumOfCalls}</th>";
        $html .= "<th align='center'>".priority_icon(PRIORITY_CRITICAL)."</th>";
        $html .= "<th align='center'>".priority_icon(PRIORITY_HIGH)."</th>";
        $html .= "<th align='center'>".priority_icon(PRIORITY_MEDIUM)."</th>";
        $html .= "<th align='center'>".priority_icon(PRIORITY_LOW)."</th>";
        $html .= "<td>";
        $html .= "<table width='100%'><tr><th width='50%'>{$strIncident}</th>";
        $html .= "<th width='12%'>{$strInternalEngineer}</th><th width='25%'>{$strSoftware}</th>";
        $html .= "<th>{$strStatus}</th></table>\n";
        $html .= "</td>";
        $html .= "</tr>\n";

        foreach ($esc AS $engineer)
        {
            if (empty($engineer['4']))  $engineer['4'] = 0;
            if (empty($engineer['3']))  $engineer['3'] = 0;
            if (empty($engineer['2']))  $engineer['2'] = 0;
            if (empty($engineer['1']))  $engineer['1'] = 0;

            $html .= "<tr>";
            $html .= "<td class='shade1'>{$engineer['name']}</td>";
            $html .= "<td class='shade1'>".$engineer['count']."</td>";
            $html .= "<td class='shade1'>".$engineer['4']."</td>";
            $html .= "<td class='shade1'>".$engineer['3']."</td>";
            $html .= "<td class='shade1'>".$engineer['2']."</td>";
            $html .= "<td class='shade1'>".$engineer['1']."</td>";
            $html .= "<td  class='shade1' >";
            $html .= "<table width='100%'>";
            foreach ($engineer['calls'] AS $call)
            {
                $replace = array("Response","Action");
                $html .= "<tr><td width='50%'>{$call['text']}</td>";
                $html .= "<td width='12%'>".user_realname($call['localowner']);
                if (!empty($call['salfordtowner']))
                {
                    $html .= "<br />T: ".user_realname($call['salfordtowner']);
                }
                $html .= "</td><td width='25%'>".$call['software']."</td>";
                $html .= "<td>".str_replace($replace,"",incidentstatus_name($call['status']))."</td></tr>";
            }
            $html .= "</table>\n\n";
            $html .= "</td>";
            $total += $engineer['count'];
            $c['4'] += $engineer['4'];
            $c['3'] += $engineer['3'];
            $c['2'] += $engineer['2'];
            $c['1'] += $engineer['1'];
            $html .= "</tr>\n";
        }
        $html .= "<tr><td>{$strTotal}:</td><td>{$total}</td>";

        if (empty($c['4'])) $c['4'] = 0;
        if (empty($c['3'])) $c['3'] = 0;
        if (empty($c['2'])) $c['2'] = 0;
        if (empty($c['1'])) $c['1'] = 0;

        $html .= "<td>".$c['4']."</td>";
        $html .= "<td>".$c['3']."</td>";
        $html .= "<td>".$c['2']."</td>";
        $html .= "<td>".$c['1']."</td>";
        $html .= "</tr>\n";
        $html .= "</table>\n\n";
    }
    else
    {
        $html .= "<p align='center'>{$strNoIncidents}</p>";
    }
    unset($obj);
    unset($esc);
}
echo $html;
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>