<?php
// search_expired.php - Search expired contracts
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
$permission = PERM_CONTRACT_VIEW; // View Contracts
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strShowExpiredContracts;

// External variables
$expired = clean_int($_REQUEST['expired']);
$show = clean_fixed_list($_REQUEST['show'], array('','terminated'));
$output = clean_fixed_list($_REQUEST['output'], array('screen','csv'));

// show search expired maintenance form
// NOTE had to be the REQUEST variable otheriwse clean_fixed_list returns screen and is note empty
if (empty($_REQUEST['output']))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('contract', 32)." {$strShowExpiredContracts}</h2>";
    echo show_form_errors('searchexpired');
    clear_form_errors('searchexpired');

    echo "<form action='{$_SERVER['PHP_SELF']}' name='searchexpired' method='get' >";
    printf("<p>{$strContractsExpiredXdaysAgo}", "<input maxlength='4' name='expired' size='3' type='text' value='30' />");
    echo "<p><input name='show' type='checkbox' value='terminated'> {$strTerminated}</p>";

    echo "<p align='center'>{$strOutput}: ";
    echo "<select name='output'>";
    echo "<option value='screen'>{$strScreen}</option>";
    // echo "<option value='printer'>Printer</option>";
    echo "<option value='csv'>{$strCSVfile}</option>";
    echo "</select>";
    echo "</p>";
    echo "<p><input name='submit' type='submit' value=\"{$strSearch}\" /></p>\n";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // perform search
    // check input
    $errors = 0;
    
    if ($expired == '')
    {
        $_SESSION['formerrors']['searchexpired']['expired'] = sprintf($strFieldMustNotBeBlank, $strDays);
        $errors++;
    }
    elseif (!is_numeric($expired))
    {
        $_SESSION['formerrors']['searchexpired']['expired'] = $strEnterNumericValue;
        $errors++;
    }

    if ($errors != 0)
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
    else
    {
        // convert number of days into a timestamp
        $now = time();
        $min_expiry = $now - ($expired * 86400);

        // build SQL
        $sql  = "SELECT m.id AS maintid, s.name AS site, p.name AS product, r.name AS reseller, ";
        $sql .= "licence_quantity, l.name AS licence_type, expirydate, admincontact, ";
        $sql .= "c.forenames AS admincontactforenames, c.surname AS admincontactsurname, ";
        $sql .= "c.email AS admincontactemail, c.phone AS admincontactphone, m.notes ";
        $sql .= "c.dataprotection_email, c.dataprotection_phone, c.dataprotection_address ";
        $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, `{$dbContacts}` AS c, ";
        $sql .= "`{$dbProducts}` AS p, `{$dbLicenceTypes}` AS l, `{$dbResellers}` AS r WHERE ";
        $sql .= "(siteid = s.id AND product = p.id AND reseller = r.id AND (licence_type = l.id OR licence_type = NULL) AND admincontact = c.id) AND ";
        $sql .= "expirydate >= {$min_expiry} AND expirydate <= {$now} ";
        if ($show == "terminated") $sql .= "AND term='yes' ";
        else $sql .= "AND term != 'yes' ";
        $sql .= "ORDER BY expirydate ASC";

        // connect to database
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        if ($show == "") $pagetitle = "<h2>".icon('contract', 32)." {$strNonTerminatedContractsExpiredXdaysAgo}</h2>\n";
        else if ($show == "terminated") $pagetitle = "<h2>".icon('contract', 32)." {$strTerminatedContractsExpiredXdaysAgo}</h2>\n";

        if (mysqli_num_rows($result) == 0)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            printf ($pagetitle, $expired);
            echo "<p class='error'>{$strNoResults}</p>\n";
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            if ($output == 'screen')
            {
                include (APPLICATION_INCPATH . 'htmlheader.inc.php');

                printf ($pagetitle, $expired);

                echo "<h3>{$strSearchYielded} ".mysqli_num_rows($result);
                if (mysqli_num_rows($result) == 1)
                {
                    echo " {$strResult}</h3>";
                }
                else
                {
                    echo " {$strResults}</h3>";
                }

                echo "<table class='maintable'>
                <tr>
                <th>{$strContract}</th>
                <th>{$strSite}</th>
                <th>{$strProduct}</th>
                <th>{$strReseller}</th>
                <th>{$strLicense}</th>
                <th>{$strExpiryDate}</th>
                <th>{$strAdminContact}</th>
                <th>{$strTelephone}</th>
                <th>{$strEmail}</th>
                <th>{$strNotes}</th>
                </tr>\n";

                $shade = 'shade1';
                while ($results = mysqli_fetch_object($result))
                {
                    echo "<tr>";
                    echo "<td align='center' class='{$shade}' width='50'><a href='contract_details.php?id={$results->maintid}'>{$results->maintid}</a></td>";
                    echo "<td align='center' class='{$shade}' width='100'>{$results->site}</td>";
                    echo "<td align='center' class='{$shade}' width='100'>{$results->product}</td>";
                    echo "<td align='center' class='{$shade}' width='100'>{$results->reseller}</td>";

                    echo "<td align='center' class='{$shade}' width='75'>{$results->licence_quantity} {$results->licence_type}</td>";
                    echo "<td align='center' class='{$shade}' width='100'>".ldate($CONFIG['dateformat_date'], $results->expirydate)."</td>";
                    echo "<td align='center' class='{$shade}' width='100'><a href=\"javascript: wt_winpopup('contact_details.php?contactid={$results->admincontact}')\">{$results->admincontactforenames} {$results->admincontactsurname}</a></td>";

                    echo "<td class='{$shade}'>";
                    if ($contact->dataprotection_phone != 'Yes') echo $results->admincontactphone;
                    echo "</td>";
                    echo "<td class='{$shade}'>";
                    if ($contact->dataprotection_email != 'Yes') echo $results->admincontactemail;
                    echo "</td>";

                    echo "<td align='center' class='{$shade}' width='150'>";
                    if ($results->notes == '')
                    {
                        echo "&nbsp;";
                    }
                    else
                    {
                        echo nl2br($results->notes);
                    }

                    echo "</td></tr>";
                    // invert shade
                    if ($shade == 'shade1;') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>\n";
                echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}'>{$strSearchAgain}</a></p>\n";
                include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            }
            else
            {
                $csvfieldheaders = "{$strContract},{$strSite},{$strProduct},{$strReseller},{$strLicense},{$strExpiryDate},{$strAdminContact},{$strTelephone},{$strEmail},{$strNotes}\n";
                while ($row = mysqli_fetch_object($result))
                {
                    $csv .= "{$row->maintid},{$row->site},{$row->product},{$row->reseller},{$row->license_quantity} {$row->licence_type},";
                    $csv .= date($CONFIG['dateformat_date'], $row->expirydate);
                    $csv .= ",{$row->admincontactforenames} {$row->admincontactsurname},{$row->admincontactphone},{$row->admincontactemail},";
                    $notes = nl2br($row->notes);
                    $notes = str_replace(","," ",$notes);
                    $notes = str_replace("\n"," ",$notes);
                    $notes = str_replace("\r"," ",$notes);
                    $notes = str_replace("<br />"," ",$notes);
                    $csv .= "{$notes}\n";
                }
                // --- CSV File HTTP Header
                header("Content-type: text/csv\r\n");
                header("Content-disposition-type: attachment\r\n");
                header("Content-disposition: filename=expired_report.csv");
                echo $csvfieldheaders;
                echo $csv;
            }
        }
    }
}
?>