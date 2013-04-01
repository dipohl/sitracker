<?php
// billing_unused.inc.php - functions relating to billing that are not currenly used
//                        placed here to allow for easier refactoring
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}



/**
 * Reserve monies from a serviceid
 * @author Paul Heaney
 * @param int $serviceid - The serviceID to reserve monies from
 * @param int $linktype - The type of link to create between the transaction and the reserve type
 * @param int $linkref - The ID to link this transaction to
 * @param int $amount - The positive amount of money to reserve
 * @param string $description - A description to put on the reservation
 * @return int - The transaction ID
 */
function reserve_monies($serviceid, $linktype, $linkref, $amount, $description)
{
    global $now, $sit;
    $rtnvalue = FALSE;
    $balance = get_service_balance($serviceid, TRUE, TRUE);

    $amount *= -1;

    if ($balance != FALSE)
    {
        $sql = "INSERT INTO `{$GLOBALS['dbTransactions']}` (serviceid, amount, description, userid, dateupdated, transactionstatus) ";
        $sql .= "VALUES ('{$serviceid}', '{$amount}', '{$description}', '{$_SESSION['userid']}', '".date('Y-m-d H:i:s', $now)."', '".BILLING_RESERVED."')";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("Error inserting transaction. ".mysql_error(), E_USER_WARNING);
            $rtnvalue = FALSE;
        }

        $rtnvalue = mysql_insert_id();

        if ($rtnvalue != FALSE)
        {

            $sql = "INSERT INTO `{$GLOBALS['dbLinks']}` VALUES ({$linktype}, {$rtnvalue}, {$linkref}, 'left', '{$_SESSION['userid']}')";
            mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(),E_USER_ERROR);
                $rtnvalue = FALSE;
            }
            if (mysql_affected_rows() < 1)
            {
                trigger_error("Link reservation failed",E_USER_ERROR);
                $rtnvalue = FALSE;
            }
        }
    }

    return $rtnvalue;
}


/**
 * Transitions reserved monies to awaitingapproval
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to transition
 * @param int $amount The final amount to charge
 * @param string $description (optional) The description to update the transaction with
 * @return bool TRUE on sucess FALSE otherwise
 */
function transition_reserved_monites($transactionid, $amount, $description='')
{
    $rtnvalue = TRUE;
    $sql = "UPDATE `{$GLOBALS['dbTransactions']}` SET amount = {$amount}, transactionstatus = ".BILLING_AWAITINGAPPROVAL." ";
    if (!empty($description))
    {
        $sql .= ", description = '{$description}' ";
    }
    $sql .= "WHERE transactionid = {$transactionid} AND transactionstatus = ".BILLING_RESERVED;
    mysql_query($sql);

    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
        $rtnvalue = FALSE;
    }
    if (mysql_affected_rows() < 1)
    {
        trigger_error("Transition reserved monies failed {$sql}",E_USER_ERROR);
        $rtnvalue = FALSE;
    }

    return $rtnvalue;
}


/**
 * Unreserve a reserved transaction, this removes the transaction thus removing the reservation
 * @author Paul Heaney
 * @param int $transactionid - The transaction to unreserv
 * @return bool TRUE on sucess FALSE otherwise
 */
function unreserve_monies($transactionid, $linktype)
{
    $rtnvalue = FALSE;
    $sql = "DELETE FROM `{$GLOBALS['dbTransactions']}` WHERE transactionid = {$transactionid} AND transactionstatus = ".BILLING_RESERVED;
    mysql_query($sql);

    if (mysql_error()) trigger_error("Error unreserving monies ".mysql_error(), E_USER_ERROR);
    if (mysql_affected_rows() == 1) $rtnvalue = TRUE;

    if ($rtnvalue != FALSE)
    {
        $sql = "DELETE FROM `{$GLOBALS['dbLinks']}` WHERE linktype =  {$linktype} AND origcolref = {$transactionid}";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_ERROR);
            $rtnvalue = FALSE;
        }
        if (mysql_affected_rows() < 1)
        {
            trigger_error("Link deletion failed",E_USER_ERROR);
            $rtnvalue = FALSE;
        }
    }

    return $rtnvalue;
}


/**
 * Produces a HTML dropdown of all valid services for a contract
 * @author Paul Heaney
 * @param int $contractid The contract ID to report on
 * @param int $name name for the dropdown
 * @param int $selected The service ID to select
 * @return string HTML for the dropdown
 */
function service_dropdown_contract($contractid, $name, $selected=0)
{
    global $now, $CONFIG;
    $date = ldate('Y-m-d', $now);

    $sql = "SELECT * FROM `{$GLOBALS['dbService']}` WHERE contractid = {$contractid} ";
    $sql .= "AND '{$date}' BETWEEN startdate AND enddate ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);

    $html = FALSE;

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select name='{$name}' id={$name}>\n";
        $html .= "<option value='0' ";
        if ($selected == 0) $html .= " selected='selected' ";
        $html .= "></option>";
        while ($obj = mysql_fetch_object($result))
        {
            $html .= "<option value='{$obj->serviceid}' ";
            if ($selected == $obj->serviceid) $html .= " selected='selected' ";
            $html .= ">{$CONFIG['currency_symbol']}".get_service_balance($obj->serviceid, TRUE, TRUE);
            $html .= " ({$obj->startdate} - {$obj->enddate})</option>";
        }
        $html .= "</select>\n";
    }

    return $html;
}


/**
 * Produces a HTML dropdown of all valid services for a site
 * @author Paul Heaney
 * @param int $contractid The contract ID to report on
 * @param int $name name for the dropdown
 * @param int $selected The service ID to select
 * @return string HTML for the dropdown
 */
function service_dropdown_site($siteid, $name, $selected=0)
{
    global $now, $CONFIG;
    $date = ldate('Y-m-d', $now);

    $sql = "SELECT s.* FROM `{$GLOBALS['dbService']}` AS s, `{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE s.contractid = m.id AND  m.site = {$siteid} ";
    $sql .= "AND '{$date}' BETWEEN startdate AND enddate ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);

    $html = FALSE;

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select name='{$name}' id={$name}>\n";
        $html .= "<option value='0' ";
        if ($selected == 0) $html .= " selected='selected' ";
        $html .= "></option>";
        while ($obj = mysql_fetch_object($result))
        {
            $html .= "<option value='{$obj->serviceid}' ";
            if ($selected == $obj->serviceid) $html .= " selected='selected' ";
            $html .= ">{$CONFIG['currency_symbol']}".get_service_balance($obj->serviceid, TRUE, TRUE);
            $html .= " ({$obj->startdate} - {$obj->enddate})</option>";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html = "No services currently valid";
    }

    return $html;
}


/**
 * Identify if a transaction has been approved or not
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to check
 * @return bool TRUE if approved FALSE otherwise
 */
function is_transaction_approved($transactionid)
{
    $sql = "SELECT transactionid FROM `{$GLOBALS['dbTransactions']}` WHERE transactionid = {$transactionid} AND transactionstaus = ".BILLING_APPROVED;
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0) return TRUE;
    else return FALSE;
}