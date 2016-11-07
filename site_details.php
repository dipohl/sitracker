<?php
// site_details.php - Show all site details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Created: 9th March 2001
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05

require ('core.php');
$permission = PERM_SITE_VIEW; // View Sites
require (APPLICATION_LIBPATH . 'functions.inc.php');
require_once (APPLICATION_LIBPATH . 'billing.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$id = clean_int($_REQUEST['id']);
$showinactivecontacts = clean_fixed_list($_REQUEST['showinactivecontacts'], array("", "yes"), true);
$showinactivecontracts = clean_fixed_list($_REQUEST['showinactivecontracts'], array("", "yes"), true);

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if ($id == '')
{
    echo "<p class='error'>{$strMustSelectASite}</p>";
    exit;
}

plugin_do('site_details');

// Display site
echo "<table class='maintable vertical'>";
$sql="SELECT * FROM `{$dbSites}` WHERE id='{$id}' ";
$siteresult = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
while ($siteobj = mysqli_fetch_object($siteresult))
{
    echo "<tr><th>{$strSite}:</th><td>";
    echo "<h3>".icon('site', 32)." ".htmlentities($siteobj->name)."</h3>";
    echo "</td></tr>";
    if ($siteobj->active == 'false')
    {
        echo "<tr><th>{$strStatus}:</th><td><span class='expired'>{$strInactive}</span></td></tr>";
    }
    $tags = list_tags($id, TAG_SITE, TRUE);
    if (!empty($tags))
    {
        echo "<tr><th>{$strTags}:</th><td>{$tags}</td></tr>";
    }

    echo "<tr><th>{$strDepartment}:</th><td>{$siteobj->department}</td></tr>";
    echo "<tr><th>{$strAddress1}:</th><td>{$siteobj->address1}</td></tr>";
    echo "<tr><th>{$strAddress2}:</th><td>{$siteobj->address2}</td></tr>";
    echo "<tr><th>{$strCity}:</th><td>{$siteobj->city}</td></tr>";
    echo "<tr><th>{$strCounty}:</th><td>{$siteobj->county}</td></tr>";
    echo "<tr><th>{$strCountry}:</th><td>".get_country_name($siteobj->country)."</td></tr>";
    echo "<tr><th>{$strPostcode}:</th><td>{$siteobj->postcode} ";
    if (!empty($siteobj->address1))
    {
        $address = "{$siteobj->address1}";
        $count = 1;

        if (!empty($siteobj->address2))
        {
            $address .= ", {$siteobj->address2}";
            $count++;
        }
        if (!empty($siteobj->postcode))
        {
            $address .= ", {$siteobj->postcode}";
            $count++;
        }
        if (!empty($siteobj->city))
        {
            $address .= ", {$siteobj->city}";
            $count++;
        }
        if (!empty($siteobj->country))
        {
            $address .= ", {$siteobj->country}";
            $count++;
        }
        if (!empty($siteobj->county))
        {
            $address .= ", {$siteobj->county}";
            $count++;
        }

        if ($count >= $CONFIG['address_components_to_map']) echo "(".map_link($address).")";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strTelephone}:</th><td>{$siteobj->telephone}</td></tr>";
    echo "<tr><th>{$strFax}:</th><td>{$siteobj->fax}</td></tr>";
    echo "<tr><th>{$strEmail}:</th><td><a href=\"mailto:{$siteobj->email}\">{$siteobj->email}</a></td></tr>";
    echo "<tr><th>{$strWebsite}:</th><td>";
    if (!empty($siteobj->websiteurl))
    {
        if (preg_match('/^http|^https/', $siteobj->websiteurl)) $prefix = '';
        else $prefix = 'http://';
        echo "<a href=\"{$prefix}{$siteobj->websiteurl}\">{$siteobj->websiteurl}</a>";
    }

    echo "</td></tr>";
    echo "<tr><th>{$strNotes}:</th><td>".nl2br(htmlentities($siteobj->notes))."</td></tr>";
    echo "<tr><td colspan='2'>&nbsp;</td></tr>";
    echo "<tr><th>{$strIncidents}:</th>";
    echo "<td>".site_count_incidents($id)." <a href=\"contact_support.php?id={$siteobj->id}&amp;mode=site\">{$strSeeHere}</a> (".site_count_incidents($id, TRUE)." <a href=\"contact_support.php?id={$siteobj->id}&amp;mode=site&amp;status=open\">{$strOpen})</a></td></tr>";
    echo "<tr><th>{$strBillableIncidents}:</th><td><a href='transactions.php?site={$siteobj->id}'>{$strSeeHere}</a></td></tr>";

    $balance = $awaiting = $reserved = 0;

    $billable_contract = get_site_billable_contract_id($id);

    if ($billable_contract != -1)
    {
        $balance = contract_balance($billable_contract, TRUE, TRUE, TRUE);
        $awaiting = contract_transaction_total($billable_contract, BILLING_AWAITINGAPPROVAL);
        $reserved = contract_transaction_total($billable_contract, BILLING_RESERVED);
    }

    echo "<tr><th>{$strServiceBalance}</th><td>";
    echo "{$GLOBALS['strBalance']}: {$CONFIG['currency_symbol']}".number_format($balance, 2);
    if ($awaiting > 0) echo "<br />{$GLOBALS['strAwaitingApproval']}: {$CONFIG['currency_symbol']}".number_format($awaiting, 2);
    if ($reserved > 0) echo "<br />{$GLOBALS['strReserved']}: {$CONFIG['currency_symbol']}".number_format($reserved, 2);

    echo "</td></tr>";

    echo "<tr><th>{$strActivities}:</th><td>".open_activities_for_site($siteobj->id)." <a href='tasks.php?siteid={$siteobj->id}'>{$strSeeHere}</a></td></tr>";
    echo "<tr><th>{$strInventory}:</th>";
    echo "<td>".site_count_inventory_items($id);
    echo " <a href='inventory_site.php?id={$id}'>{$strSeeHere}</a></td></tr>";
    $billableunits = amount_used_site($siteobj->id, $now - 2678400); // Last 31 days
    if (!empty($billableunits))
    {
        echo "<tr><th>".sprintf($strUnitsUsedLastXdays, 31).":</th><td>{$billableunits}</td></tr>"; // More appropriate label
    }
    echo "<tr><th>{$strIncidentPool}:</th><td>".sprintf($strRemaining, $siteobj->freesupport)."</td></tr>";
    echo "<tr><th>{$strSalesperson}:</th><td>";
    if ($siteobj->owner >= 1)
    {
        echo user_realname($siteobj->owner, TRUE);
    }
    else
    {
        echo $strNotSet;
    }

    echo "</td></tr>\n";
}

plugin_do('site_details_table');
mysqli_free_result($siteresult);

echo "</table>\n";
echo "<p align='center'><a href='site_edit.php?action=edit&amp;site={$id}'>{$strEdit}</a> | ";
echo "<a href='site_delete.php?id={$id}'>{$strDelete}</a>";
echo "</p>";

echo "<h3>{$strContacts}</h3>";

// List Contacts

$sql = "SELECT * FROM `{$dbContacts}` WHERE siteid='{$id}' ";
if ($showinactivecontacts != 'yes' AND $_SESSION['userconfig']['show_inactive_data'] != 'TRUE' )
{
    $sqldisabled = $sql . " AND active = 'false' ORDER BY active, surname, forenames";
    $sql .= "AND active = 'true' ";
}
$sql .= "ORDER BY active, surname, forenames";
$contactresult = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

$countdisablecontacts = 0;
if ($_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
{
    $contactresultdisabled = mysqli_query($db, $sqldisabled);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $countdisablecontacts = mysqli_num_rows($contactresultdisabled);
}

$countcontacts = mysqli_num_rows($contactresult);
if ($countcontacts > 0 OR $countdisablecontacts > 0)
{
    echo "<p align='center'>".sprintf($strContactsMulti, $countcontacts);
    if ($countdisablecontacts > 0) echo " (" . sprintf($strInactive, $countdisablecontacts) . " <a href='{$_SERVER['REQUEST_URI']}&amp;showinactivecontacts=yes'>{$strView}</a>)";
    echo "</p>";
    echo "<table class='maintable'>";
    echo "<tr><th>{$strName}</th><th>{$strJobTitle}</th>";
    echo "<th>{$strDepartment}</th><th>{$strTelephone}</th>";
    echo "<th>{$strEmail}</th><th>{$strAddress}</th>";
    echo "<th>{$strDataProtection}</th><th>{$strNotes}</th></tr>";

    $shade = 'shade1';

    while ($contactobj = mysqli_fetch_object($contactresult))
    {
        if ($contactobj->active == 'false') $shade='expired';
        echo "<tr class='{$shade}'>";
        echo "<td>".icon('contact', 16, $strContact);
        echo " <a href='contact_details.php?id={$contactobj->id}'>{$contactobj->forenames} {$contactobj->surname}</a></td>";
        echo "<td>{$contactobj->jobtitle}</td>";
        echo "<td>{$contactobj->department}</td>";
        if ($contactobj->dataprotection_phone != 'Yes')
        {
            echo "<td>{$contactobj->phone}</td>";
        }
        else
        {
            echo "<td><strong>{$strWithheld}</strong></td>";
        }

        if ($contactobj->dataprotection_email != 'Yes')
        {
            echo "<td>{$contactobj->email}</td>";
        }
        else
        {
            echo "<td><strong>{$strWithheld}</strong></td>";
        }

        if ($contactobj->dataprotection_address != 'Yes')
        {
            echo "<td>";
            if (!empty($contactobj->address1))
            {
                echo $contactobj->address1;
            }
            echo "</td>";
        }
        else echo "<td><strong>{$strWithheld}</strong></td>";

        echo "<td>";
        if ($contactobj->dataprotection_email == 'Yes')
        {
            echo "<strong>{$strNoEmail}</strong>, ";
        }

        if ($contactobj->dataprotection_phone == 'Yes')
        {
            echo "<strong>{$strNoCalls}</strong>, ";
        }

        if ($contactobj->dataprotection_address == 'Yes')
        {
            echo "<strong>{$strNoPost}</strong>";
        }

        echo "</td>";
        echo "<td>".nl2br(mb_substr($contactobj->notes, 0, 500))."</td>";
        echo "</tr>";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>\n";
}
else
{
    echo "<p align='center'>{$strNoContactsForSite}</p>";
}
echo "<p align='center'><a href='contact_new.php?siteid={$id}'>{$strNewContact}</a></p>";

// Valid user, check perms
if (user_permission($sit[2], PERM_CONTRACT_VIEW)) // View contracts
{
    echo "<h3>{$strContracts}<a id='contracts'></a></h3>";

    // Display contracts
    $sql  = "SELECT m.id AS maintid, m.term AS term, p.name AS product, r.name AS reseller, ";
    $sql .= "licence_quantity, lt.name AS licence_type, expirydate, admincontact, ";
    $sql .= "c.forenames AS admincontactsforenames, c.surname AS admincontactssurname, m.notes AS maintnotes ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbMaintenance}` AS m ";
    $sql .= "LEFT JOIN `{$dbLicenceTypes}` AS lt ON m.licence_type = lt.id ";
    $sql .= "LEFT JOIN `{$dbResellers}` AS r ON r.id = m.reseller ";
    $sql .= "WHERE m.product = p.id ";
    $sql .= "AND admincontact = c.id AND m.site = '{$id}' ";
    if ($showinactivecontracts != 'yes' AND $_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
    {
        $sqldisabled = $sql;
        $sql .= "AND m.term != 'yes' AND (m.expirydate > {$now} OR m.expirydate = -1) ";
        $sqldisabled .= "AND (m.term = 'yes' OR m.expirydate < {$now}) ORDER BY expirydate DESC ";
    }
    $sql .= "ORDER BY expirydate DESC";

    // connect to database and execute query
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $countcontracts = mysqli_num_rows($result);
    
    $disabledcountcontracts = 0;
    if (!empty($sqldisabled))
    {
        $resultdisabled = mysqli_query($db, $sqldisabled);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $disabledcountcontracts = mysqli_num_rows($resultdisabled);
    }

    echo "<p align='center'>";
    echo "{$countcontracts} {$strContracts}";
    if ($disabledcountcontracts > 0) echo " (".sprintf($strInactive, $disabledcountcontracts)." <a href='{$_SERVER['REQUEST_URI']}&amp;showinactivecontracts=yes'>{$strView}</a>)";
    echo "</p>";

    if ($countcontracts > 0 OR $disabledcountcontracts > 0)
    {
        echo "<table class='maintable'>
        <tr>
            <th>{$strContractID}</th>
            <th>{$strProduct}</th>
            <th>{$strReseller}</th>
            <th>{$strLicense}</th>
            <th>{$strExpiryDate}</th>
            <th>{$strAdminContact}</th>
            <th>{$strNotes}</th>
        </tr>";
        $shade = 'shade1';
        while ($results = mysqli_fetch_object($result))
        {
            if ($results->term == 'yes' OR
                ($results->expirydate < $now AND
                $results->expirydate != -1))
            {
            	$shade = "expired";
            }
            echo "<tr>";
            echo "<td class='{$shade}'>".icon('contract', 16)." ";
            echo "<a href='contract_details.php?id={$results->maintid}'>{$strContract} {$results->maintid}</a></td>";
            echo "<td class='{$shade}'>{$results->product}</td>";
            echo "<td class='{$shade}'>";
            if (empty($results->reseller))
            {
                echo $strNoReseller;
            }
            else
            {
                echo $results->reseller;
            }

            echo "</td>";
            echo "<td class='{$shade}'>";

            if (empty($results->licence_type))
            {
                echo $strNoLicense;
            }
            else
            {
                if ($results->licence_quantity == 0)
                {
                    echo "{$strUnlimited} ";
                }
                else
                {
                    echo "{$results->licence_quantity} ";
                }
                echo $results->licence_type;
            }

            echo "</td>";
            echo "<td class='{$shade}'>";
            if ($results->expirydate == -1)
            {
                echo $strUnlimited;
            }
            else
            {
                echo ldate($CONFIG['dateformat_date'], $results->expirydate);
            }
            echo "</td>";
            echo "<td class='{$shade}'>{$results->admincontactsforenames}  {$results->admincontactssurname}</td>";
            echo "<td class='{$shade}'>";
            if ($results->maintnotes == '')
            {
                echo '&nbsp;';
            }
            else
            {
                echo nl2br($results->maintnotes);
            }
            echo "</td>";
            echo "</tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>\n";
    }
    else
    {
        echo "<p align='center'>{$strNoContractsForSite}</p>";
    }

    echo "<p align='center'><a href='contract_new.php?action=showform&amp;siteid={$id}'>{$strNewContract}</a></p>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>