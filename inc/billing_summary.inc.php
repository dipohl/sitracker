<?php
// billing/summary.php - Summary page - to show
// Summary of all sites and their balances and expiry date.(sf 1931092)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paul[at]sitracker.org>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$display = clean_fixed_list($_REQUEST['display'], array('','html','csv'));
$showfoc = clean_fixed_list($_REQUEST['foc'], array('','show'));
$focaszero = clean_fixed_list($_REQUEST['focaszero'], array('','show'));
$expiredaszero = clean_fixed_list($_REQUEST['expiredaszero'], array('','show'));

if (empty($display)) $display = 'html';

$sql = "SELECT DISTINCT(CONCAT(m.id,sl.tag)), m.site, m.product, m.expirydate AS maintexpiry, m.billingmatrix, m.billingtype, s.* ";
$sql .= "FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS sl, `{$dbService}` AS s, `{$dbSites}` AS site ";
$sql .= "WHERE m.servicelevel = sl.tag AND sl.timed = 'yes' AND m.id = s.contractid AND m.site = site.id ";

if (empty($showfoc) OR $showfoc != 'show')
{
    $sql .= "AND s.foc = 'no' ";
}

$sitestr = '';

$csv_currency = html_entity_decode($CONFIG['currency_symbol'], ENT_NOQUOTES);

if (!empty($sites))
{
    foreach ($sites AS $s)
    {
        if (empty($sitestr)) $sitestr .= "m.site = {$s} ";
        else $sitestr .= "OR m.site = {$s} ";
    }

    $sql .= "AND {$sitestr} ";
}

$sql .= "ORDER BY site.name, s.enddate";

$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

if (mysqli_num_rows($result) > 0)
{
    if ($display == 'html')
    {
        $str .= "<table class='maintable'><tr><th>{$strSiteName}</th><th>{$strProduct}</th><th>{$strBilling}</th>";
        $str .= "<th>{$strExpiryDate}</th><th>{$strCustomerReference}</th><th>{$strStartDate}</th><th>{$strEndDate}</th>";
        $str .= "<th>{$strFreeOfCharge}</th><th>{$strCreditAmount}</th><th>{$strBalance}</th>";
        $str .= "<th>{$strAwaitingApproval}</th><th>{$strReserved}</th><th>{$strAvailableBalance}</th>";
        $str .= "<th>{$strUnitRate}</th><th>{$strUnitsRemaingSingleTime}</th></tr>\n";
    }
    elseif ($display == 'csv')
    {
        // NOTE: do not seperate each of these entries with spaces some apps can't decode properly (OpenOffice) and you get " in the entries
        $str .= "\"{$strSiteName}\",\"{$strProduct}\",\"{$strBilling}\",\"{$strExpiryDate}\",\"{$strCustomerReference}\",\"{$strStartDate}\",";
        $str .= "\"{$strEndDate}\",\"{$strFreeOfCharge}\",\"{$strCreditAmount}\",\"{$strBalance}\",\"{$strAwaitingApproval}\",";
        $str .= "\"{$strReserved}\",\"{$strAvailableBalance}\",\"{$strUnitRate}\",\"{$strUnitsRemaingSingleTime}\"\n";
    }

    $lastsite = '';
    $lastproduct = '';

    $shade = 'shade1';
    while ($obj = mysqli_fetch_object($result))
    {
        $billingObj = get_billable_incident_object($obj->billingtype);
        
        if ($obj->foc == 'yes' AND !empty($focaszero))
        {
            $obj->creditamount = 0;
            $obj->balance = 0;
        }

        if (!empty($expiredaszero) AND strtotime($obj->enddate) < $now)
        {
            $obj->balance = 0;
            $unitsat1times = 0;
            $actual = 0;
        }

        $totalcredit += $obj->creditamount;
        $totalbalance += $obj->balance;
        $awaitingapproval = service_transaction_total($obj->serviceid, BILLING_AWAITINGAPPROVAL)  * -1;
        $totalawaitingapproval += $awaitingapproval;
        $reserved = service_transaction_total($obj->serviceid, BILLING_RESERVED) * -1;
        $totalreserved += $reserved;

        $actual = ($obj->balance - $awaitingapproval) - $reserved;
        $totalactual += $actual;

        if ($obj->rate != 0) $unitsat1times = round(($actual / $obj->rate), 2);
        else $unitsat1times = 0;

        $remainingunits += $unitsat1times;

        if ($display == 'html')
        {
            if ($obj->site != $lastsite OR $obj->product != $lastproduct)
            {
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }

            $str .= "<tr class='{$shade}'>";
            
            $billingmatrix = "";
            if (!empty($obj->billingmatrix)) $billingmatrix = "({$obj->billingmatrix})";
            
            if ($obj->site != $lastsite)
            {
                $str .= "<td>".site_name($obj->site)."</td>";
                $str .= "<td>".product_name($obj->product)."</td>";
                $str .= "<td>".$billingObj->display_name()."<br />{$billingmatrix}</td>";
            }
            else
            {
                $str .= "<td></td>";
                if ($obj->product != $lastproduct)
                {
                    $str .= "<td>".product_name($obj->product)."</td>";
                    $str .= "<td>".$billingObj->display_name()."<br />{$billingmatrix}</td>";
                }
                else
                {
                    $str .= "<td></td><td></td>";
                }
            }
            $str .= "<td>" . ldate($CONFIG['dateformat_date'], $obj->maintexpiry) . "</td>";

            $str .= "<td>{$obj->cust_ref}</td>";
            $str .= "<td>" . ldate($CONFIG['dateformat_date'], mysql2date($obj->startdate)) . "</td>";
            $str .= "<td>" . ldate($CONFIG['dateformat_date'], mysql2date($obj->enddate)) . "</td>";
            if ($obj->foc == 'yes') $str .= "<td>{$strYes}</td>";
            else $str .= "<td>{$strNo}</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($obj->creditamount,2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($obj->balance,2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($awaitingapproval, 2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($reserved, 2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($actual, 2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}{$obj->rate}</td>";
            $str .= "<td>{$unitsat1times}</td></tr>\n";

            $lastsite = $obj->site;
            $lastproduct = $obj->product;
        }
        elseif ($display == 'csv')
        {
            if ($obj->site != $lastsite)
            {
                $str .= "\"".site_name($obj->site)."\",";
                $str .= "\"".product_name($obj->product)."\",";
                $str .= "\"".$billingObj->display_name()."\",";
            }
            else
            {
                $str .= ",";
                if ($obj->product != $lastproduct)
                {
                    $str .= product_name($obj->product).",";
                }
                else
                {
                    $str .= ",";
                }
                $str .= "\"".$billingObj->display_name()."\",";
            }

            $str .= "\"".ldate($CONFIG['dateformat_date'], $obj->maintexpiry)."\",";
            $str .= "\"{$obj->cust_ref}\",\"" . ldate($CONFIG['dateformat_date'], mysql2date($obj->startdate));
            $str .= "\",\"" . ldate($CONFIG['dateformat_date'], mysql2date($obj->enddate)) . "\",";
            if ($obj->foc == 'yes') $str .= "\"{$strYes}\",";
            else $str .= "\"{$strNo}\",";
            $str .= "\"{$csv_currency}{$obj->creditamount}\",\"{$csv_currency}{$obj->balance}\",";
            $str .= "\"{$awaitingapproval}\",\"{$reserved}\",\"{$actual}\",";
            $str .= "\"{$csv_currency}{$obj->rate}\",";
            $str .= "\"{$unitsat1times}\"\n";
        }
    }

    if ($display == 'html')
    {
        $str .= "<tfoot><tr><td colspan='8' align='right'><strong>{$strTOTALS}</strong></td><td>{$CONFIG['currency_symbol']}".number_format($totalcredit, 2)."</td>";
        $str .= "<td>{$CONFIG['currency_symbol']}".number_format($totalbalance, 2)."</td><td>{$CONFIG['currency_symbol']}".number_format($totalawaitingapproval, 2)."</td>";
        $str .= "<td>{$CONFIG['currency_symbol']}".number_format($totalreserved, 2)."</td><td>{$CONFIG['currency_symbol']}".number_format($totalactual, 2)."</td><td></td><td>{$remainingunits}</td></tr></tfoot>";
        $str .= "</table>";
        $str .= "<p class='return'><a href='" . htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, $i18ncharset) . "'>{$strReturnToPreviousPage}</a></p>";
    }
    elseif ($display == 'csv')
    {
        $str .= ",,,,,\"{$strTOTALS}\",\"{$csv_currency}{$totalcredit}\",";
        $str .= "\"{$csv_currency}{$totalbalance}\",\"{$totalawaitingapproval}\",\"{$totalreserved}\",\"{$totalactual}\",,\"{$remainingunits}\"\n";
    }
}
else
{
    $str = $strNone;
}

if ($display == 'html')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strBillingSummary}</h2>";
    echo $str;
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($display == 'csv')
{
    header("Content-type: text/csv\r\n");
    header("Content-disposition-type: attachment\r\n");
    header("Content-disposition: filename=billing_summary.csv");
    echo $str;
}

?>