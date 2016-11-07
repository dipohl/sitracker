<?php
// holiday_new.php - Adds a holiday to the database
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
$permission = PERM_CALENDAR_VIEW; // View your calendar
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// Valid user

// External Variables
$day = clean_int($_REQUEST['day']);
$month = clean_int($_REQUEST['month']);
$year = clean_int($_REQUEST['year']);
$user = clean_int($_REQUEST['user']);
$type = clean_int($_REQUEST['type']);
$length = cleanvar($_REQUEST['length']);
$return = cleanvar($_REQUEST['return']);
$time = cleanvar($_REQUEST['time']);
$title = $strCalendar;

// startdate in unix format
$startdate = mktime(0, 0, 0, $month, $day, $year);
$enddate = mktime(23, 59, 59, $month, $day, $year);
if ($length == '') $length = 'day';

if (user_permission($sit[2], PERM_HOLIDAY_APPROVE)) $approver = TRUE;
else $approver = FALSE;
if (user_permission($sit[2], PERM_ADMIN)) $adminuser = TRUE;
else $adminuser = FALSE;

// Holiday types (for reference)
// 1 = Holiday
// 2 = Sickness
// 3 = Working Away
// 4 = Training
// 5 - Compassionate/Free

// check to see if there is a holiday on this day already, if there is retrieve it
list($dtype, $dlength, $dapproved, $dapprovedby) = user_holiday($user, 0, $year, $month, $day, FALSE);

// allow approver (or admin) to unbook holidays already approved
if ($length == '0' AND (($approver == TRUE AND ($dapprovedby = $sit[2] OR $adminuser == TRUE))
                   OR ($user == $sit[2] AND mysql2date("{$year}-{$month}-{$day}") >= $today)))
{
    // Delete the holiday
    $sql = "DELETE FROM `{$dbHolidays}` ";
    $sql .= "WHERE userid='{$user}' AND `date` = '{$year}-{$month}-{$day}' ";
    $sql .= "AND type='{$type}' ";
    if (!$adminuser) $sql .= "AND (approvedby='{$sit[2]}' OR userid={$sit[2]}) ";
    $result = mysqli_query($db, $sql);
    // echo $sql;
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
    $dlength = 0;
    $dapproved = 0;
}
else
{
    if (empty($dapproved))
    {
        // Only allow these types to be modified
        if ($dtype == HOL_HOLIDAY || $dtype == HOL_WORKING_AWAY || $dtype == HOL_TRAINING)
        {
            if ($length == '0' AND $user == $sit[2])
            {
                // Cancel Holiday
               
                if ($dlength == 'day' AND in_array($time, array('am', 'pm')))
                {
                    if ($time == 'am') $length = 'pm';
                    else $length = 'am';
                    
                    // there is an existing booking so alter it
                    $sql = "UPDATE `{$dbHolidays}` SET length='{$length}' ";
                    $sql .= "WHERE userid='{$user}' AND `date` = '{$year}-{$month}-{$day}' AND type='{$type}' AND length='{$dlength}'";
                    $result = mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                    
                    
                }
                else
                {
                    $sql = "DELETE FROM `{$dbHolidays}` ";
                    $sql .= "WHERE userid='{$user}' AND `date` = '{$year}-{$month}-{$day}' AND type='{$type}' ";
                    $result = mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                }
                $dlength = 0;
                $dapproved = 0;
                plugin_do('holiday_cancelled_action');
            }
            else
            {
                if ($length != $dlength)
                {
                    // We have a current booking that is for that same day but for a different period so we need to book the full day
                    $length = 'day';
                }
                
                // there is an existing booking so alter it
                $sql = "UPDATE `{$dbHolidays}` SET length='{$length}' ";
                $sql .= "WHERE userid='{$user}' AND `date` = '{$year}-{$month}-{$day}' AND type='{$type}' AND length='{$dlength}'";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                $dlength = $length;
            }
        }
        elseif ($type == HOL_NORMAL)
        {
            // If there is a holiday here, remove it on request
            $sql = "DELETE FROM `{$dbHolidays}` ";
            $sql .= "WHERE userid='{$user}' AND `date` = '{$year}-{$month}-{$day}'";
            $result = mysqli_query($db, $sql);
            $dlength = $length;
            $approved = 0;
        }
        else
        {
            // there is no holiday on this day, so make one
            $sql = "INSERT INTO `{$dbHolidays}` ";
            $sql .= "SET userid='{$user}', type='{$type}', `date` = '{$year}-{$month}-{$day}', length='{$length}' ";
            $result = mysqli_query($db, $sql);
            $dlength = $length;
            $approved = 0;
        }
    }
}

if ($return == 'list')
{
    header("Location: calendar.php?display=list&type={$type}&user={$user}");
    exit;
}
else
{
    $url = $_SERVER['HTTP_REFERER'];
    header("Location: {$url}");
    exit;
}
?>