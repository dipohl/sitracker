<?php
// sla.inc.php - functions relating to SLA / Service Levels
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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
 * Create an array containing the service level history
 * @author Ivan Lucas, Tom Gerrard
 * @return array
 */
function incident_sla_history($incidentid)
{
    global $CONFIG, $dbIncidents, $dbServiceLevels, $dbUpdates, $db;
    $working_day_mins = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;

    // Not the most efficient but..
    $sql = "SELECT * FROM `{$dbIncidents}` WHERE id='{$incidentid}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $incident = mysqli_fetch_object($result);

    // Get service levels
    $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$incident->servicelevel}' AND priority='{$incident->priority}' ";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $level = mysqli_fetch_object($result);

    // Loop through the updates in ascending order looking for service level events
    $sql = "SELECT * FROM `{$dbUpdates}` WHERE sla IS NOT Null AND incidentid='{$incidentid}' ORDER BY id ASC, timestamp ASC";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $prevtime = 0;
    $idx = 0;
    while ($history = mysqli_fetch_object($result))
    {
        $slahistory[$idx]['targetsla'] = $history->sla;
        switch ($history->sla)
        {
            case 'initialresponse':
                $slahistory[$idx]['targettime'] = $level->initial_response_mins;
                break;
            case 'probdef':
                $slahistory[$idx]['targettime'] = $level->prob_determ_mins;
                break;
            case 'actionplan':
                $slahistory[$idx]['targettime'] = $level->action_plan_mins;
                break;
            case 'solution':
                $slahistory[$idx]['targettime'] = ($level->resolution_days * $working_day_mins);
                break;
            default:
                $slahistory[$idx]['targettime'] = 0;
        }

        if ($prevtime > 0)
        {
            $slahistory[$idx]['actualtime'] = calculate_incident_working_time($incidentid, $prevtime, $history->timestamp);
        }
        else
        {
            $slahistory[$idx]['actualtime'] = 0;
        }

        $slahistory[$idx]['timestamp'] = $history->timestamp;
        $slahistory[$idx]['userid'] = $history->userid;
        if (intval($slahistory[$idx]['targettime']) === 0)
        {
            // If the target is disabled show it as met
            $slahistory[$idx]['targetmet'] = TRUE;
            $slahistory[$idx]['targettime'] = $slahistory[$idx]['actualtime']+1; 
        }
        else if ($slahistory[$idx]['actualtime'] <= $slahistory[$idx]['targettime'])
        {
            $slahistory[$idx]['targetmet'] = TRUE;
        }
        else
        {
            $slahistory[$idx]['targetmet'] = FALSE;
        }

        $prevtime = $history->timestamp;
        $idx++;
    }
    // Get next target, but only if incident is still open
    if ($incident->status != STATUS_CLOSED AND $incident->status != STATUS_CLOSING)
    {
        $target = incident_get_next_target($incidentid);
        $slahistory[$idx]['targetsla'] = $target->type;
        switch ($target->type)
        {
            case 'initialresponse':
                $slahistory[$idx]['targettime'] = $level->initial_response_mins;
                break;
            case 'probdef':
                $slahistory[$idx]['targettime'] = $level->prob_determ_mins;
                break;
            case 'actionplan':
                $slahistory[$idx]['targettime'] = $level->action_plan_mins;
                break;
            case 'solution':
                $slahistory[$idx]['targettime'] = ($level->resolution_days * $working_day_mins);
                break;
            default:
                $slahistory[$idx]['targettime'] = 0;
        }
        $slahistory[$idx]['actualtime'] = $target->since;
        if (intval($slahistory[$idx]['targettime']) === 0)
        {
            // If the target is disabled show it as met
            $slahistory[$idx]['targetmet'] = TRUE;
        }
        else if ($slahistory[$idx]['actualtime'] <= $slahistory[$idx]['targettime'])
        {
            $slahistory[$idx]['targetmet'] = TRUE;
        }
        else
        {
            $slahistory[$idx]['targetmet'] = FALSE;
        }

        $slahistory[$idx]['timestamp'] = 0;
    }
    return $slahistory;
}


/**
 * @param string $name name of select
 * @param string $tag The tag which should be chosen
 * @param bool $collapse Only show the tag rather than tag + priority
 * @param string $select additional parameter to the select clause e.g. onchange code
 * @param boolean $allowtimestatuschange Whether to allow changing from a timed to a non timed SLA or vica versa
 * @return String HTML of the SLA drop down
 */
function servicelevel_drop_down($name, $tag = '', $collapse = TRUE, $select = '', $allowtimestatuschange = TRUE)
{
    global $dbServiceLevels, $db;

    if ($collapse)
    {
        $sql = "SELECT DISTINCT tag FROM `{$dbServiceLevels}` WHERE active = 'true' ";
    }
    else
    {
        $sql  = "SELECT tag, priority FROM `{$dbServiceLevels}` WHERE active = 'true' ";
    }
    
    if (!$allowtimestatuschange AND !empty($tag))
    {
        $sql .= " AND timed = (SELECT DISTINCT timed FROM `{$dbServiceLevels}` WHERE tag = '{$tag}')";
    }
    
    $sql .= "ORDER BY tag";
    
    $result = mysqli_query($db, $sql);

    $html = "<select id='{$name}' name='{$name}' {$select}>";
    // INL 30Mar06 Removed this ability to select a null service level
    // if ($id == 0) $html .= "<option selected='selected' value='0'></option>\n";
    while ($servicelevels = mysqli_fetch_object($result))
    {
        $html .= "<option ";
        $html .= "value='{$servicelevels->tag}' ";
        if ($servicelevels->tag == $tag)
        {
            $html .= "selected='selected'";
        }

        $html .= ">";
        if ($collapse)
        {
            $html .= $servicelevels->tag;
        }
        else
        {
            $html .= "{$servicelevels->tag} ".priority_name($servicelevels->priority);
        }

        $html .= "</option>";
    }
    $html .= "</select>";
    return $html;
}


function serviceleveltag_drop_down($name, $tag, $collapse = FALSE)
{
    global $dbServiceLevels, $db;

    if ($collapse)
    {
        $sql = "SELECT DISTINCT tag FROM `{$dbServiceLevels}`";
    }
    else
    {
        $sql  = "SELECT tag, priority FROM `{$dbServiceLevels}`";
    }
    $result = mysqli_query($db, $sql);


    $html = "<select name='$name'>\n";
    if ($tag == '')
    {
        $html .= "<option selected='selected' value=''></option>\n";
    }

    while ($servicelevels = mysqli_fetch_object($result))
    {
        $html .= "<option ";
        $html .= "value='{$servicelevels->tag}' ";
        if ($servicelevels->tag == $tag)
        {
            $html .= "selected='selected'";
        }

        $html .= ">";
        if ($collapse)
        {
            $html .= $servicelevels->tag;
        }
        else
        {
            $html .= "{$servicelevels->tag} ".priority_name($servicelevels->priority);
        }

        $html .= "</option>\n";
    }
    $html .= "</select>";
    return $html;
}


/**
 * Return the name of the SLA, if tag is empty the default SLA is returned else the tag is returned
 * @param string $tag The tag
 * @return string Tag Name
 */
function get_sla_name($tag)
{
    global $CONFIG;

    if ($tag == '') $tag = $CONFIG['default_service_level'];

    return $tag;
}

/**
 * Find whether a given servicelevel is timed
 * @author Ivan Lucas
 * @param string Service level tag
 * @return. bool. TRUE if any part of the service level is timed, otherwise returns FALSE
 */
function servicelevel_timed($sltag)
{
    global $dbServiceLevels, $db;
    $timed = FALSE;

    $sql = "SELECT COUNT(tag) FROM `{$dbServiceLevels}` WHERE tag = '{$sltag}' AND timed = 'yes'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    list($count) = mysqli_fetch_row($result);
    if ($count > 0) $timed = TRUE;

    return $timed;
}


/**
 * @author Paul Heaney
 * @param string $slatag The SLA to find the max priority of
 * @return int The maximum priority of an SLA, 0 if invalid SLA
 */
function servicelevel_maxpriority($slatag)
{
    global $dbServiceLevels, $db;
    $priority = 0;

    $sql = "SELECT MAX(priority) FROM `{$dbServiceLevels}` WHERE tag = '{$slatag}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    list($priority) = mysqli_fetch_row($result);
    return $priority;
}


/**
 * Calcualtes a unix timestamp for the time of next action
 * @param int $days Number of days in the future
 * @param int $hours Number of hours in the future
 * @param int $minutes Number of minutes in the future
 * @return int unix timestamp of time of next action  
 */
function calculate_time_of_next_action($days, $hours, $minutes)
{
    $now = time();
    $return_value = $now + ($days * 86400) + ($hours * 3600) + ($minutes * 60);
    return ($return_value);
}


/**
 * Retrieves the service level tag of a given maintenance contract
 * @author Paul Heaney
 * @param int $maintid. Contract ID
 * @param String $typeid - Type of incident
 * @return. string Service Level Tag
 * @todo add concept of default ServiceLevel so we can remove =1 and select the one marked as default
 */
function maintenance_servicelevel_tag($maintid, $typeid=1)
{
    global $CONFIG, $dbMaintenanceServiceLevels, $db;
    $sql = "SELECT servicelevel FROM `{$dbMaintenanceServiceLevels}` WHERE maintenanceid='{$maintid}' ";
    $sql .= "AND incidenttypeid = {$typeid}";

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) < 1)
    {
        // in case there is no maintenance contract associated with the incident, use default service level
        // if there is a maintenance contract then we should throw an error because there should be
        // service level
        if ($maintid == 0)
        {
            $servicelevelid = $CONFIG['default_service_level'];
        }
    }
    else
    {
        list($servicelevel) = mysqli_fetch_row($result);
    }
    return $servicelevel;
}

/**
 * Calculate the working time between two timestamps
 * @author Tom Gerrard, Ivan Lucas, Paul Heaney
 * @param int $t1. The start timestamp (earliest date/time)
 * @param int $t2. The ending timetamp (latest date/time)
 * @return integer. the number of working minutes (minutes in the working day)
 */
function calculate_working_time($t1, $t2, $publicholidays)
{
    // PH 16/12/07 Old function commented out, rewritten to support public holidays. Old code to be removed once we're happy this is stable
    // KH 13/07/08 Use old function again for 3.35 beta
    // Note that this won't work if we have something
    // more complicated than a weekend

    global $CONFIG;
    $swd = $CONFIG['start_working_day'] / 3600;
    $ewd = $CONFIG['end_working_day'] / 3600;

    // Just in case the time params are the wrong way around ...
    if ( $t1 > $t2 )
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
    }

    // We don't need all the elements here.  hours, days and year are used
    // later on to calculate the difference.  wday is just used in this
    // section
    $at1 = getdate($t1);
    $at2 = getdate($t2);

    // Make sure that the start time is on a valid day and within normal hours
    // if it isn't then move it forward to the next work minute
    if ($at1['hours'] > $ewd)
    {
        do
        {
            $at1['yday'] ++;
            $at1['wday'] ++;
            $at1['wday'] %= 7;
            if ($at1['yday'] > 365)
            {
                $at1['year'] ++;
                $at1['yday'] = 0;
            }
        } while (!in_array($at1['wday'], $CONFIG['working_days']));

        $at1['hours']=$swd;
        $at1['minutes']=0;

    }
    else
    {
        if (($at1['hours'] < $swd) || (!in_array($at1['wday'], $CONFIG['working_days'])))
        {
            while (!in_array($at1['wday'], $CONFIG['working_days']))
            {
                $at1['yday'] ++;
                $at1['wday'] ++;
                $at1['wday'] %= 7;
                if ($at1['days']>365)
                {
                    $at1['year'] ++;
                    $at1['yday'] = 0;
                }
            }
            $at1['hours'] = $swd;
            $at1['minutes'] = 0;
        }
    }

    // Same again but for the end time
    // if it isn't then move it backward to the previous work minute
    if ( $at2['hours'] < $swd)
    {
        do
        {
            $at2['yday'] --;
            $at2['wday'] --;
            if ($at2['wday'] < 0) $at2['wday'] = 6;
            if ($at2['yday'] < 0)
            {
                $at2['yday'] = 365;
                $at2['year'] --;
            }
        } while (!in_array($at2['wday'], $CONFIG['working_days']));

        $at2['hours'] = $ewd;
        $at2['minutes'] = 0;
    }
    else
    {
        if (($at2['hours'] > $ewd) || (!in_array($at2['wday'], $CONFIG['working_days'])))
        {
            while (!in_array($at2['wday'],$CONFIG['working_days']))
            {
                $at2['yday'] --;
                $at2['wday'] --;
                if ($at2['wday'] < 0) $at2['wday'] = 6;
                if ($at2['yday'] < 0)
                {
                    $at2['yday'] = 365;
                    $at2['year'] --;
                }
            }
            $at2['hours'] = $ewd;
            $at2['minutes'] = 0;
        }
    }

    $t1 = mktime($at1['hours'], $at1['minutes'], 0, 1, $at1['yday'] + 1, $at1['year']);
    $t2 = mktime($at2['hours'], $at2['minutes'], 0, 1, $at2['yday'] + 1, $at2['year']);

    $weeks = floor(($t2 - $t1) / (60 * 60 * 24 * 7));
    $t1 += $weeks * 60 * 60 * 24 * 7;

    while ( date('z', $t2) != date('z', $t1) )
    {
        if (in_array(date('w', $t1), $CONFIG['working_days'])) $days++;
        $t1 += (60 * 60 * 24);
    }

    // this could be negative and that's not ok
    $coefficient = 1;
    if ($t2 < $t1)
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
        $coefficient =- 1;
    }

    $min = floor( ($t2 - $t1) / 60 ) * $coefficient;

    $minutes = $min + ($weeks * count($CONFIG['working_days']) + $days ) * ($ewd - $swd) * 60;

    return $minutes;

//new version below
/*
    global $CONFIG;
    $swd = $CONFIG['start_working_day']/3600;
    $ewd = $CONFIG['end_working_day']/3600;

// Just in case they are the wrong way around ...

    if ( $t1 > $t2 )
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
    }

    $currenttime = $t1;

    $timeworked = 0;

    $t2date = getdate($t2);

    $midnight = 1440; // 24 * 60  minutes

    while ($currenttime < $t2) // was <=
    {
        $time = getdate($currenttime);

        $ph = 0;

        if (in_array($time['wday'], $CONFIG['working_days']) AND $time['hours'] >= $swd
            AND $time['hours'] <= $ewd AND (($ph = is_public_holiday($currenttime, $publicholidays)) == 0))
        {
            if ($t2date['yday'] == $time['yday'] AND $t2date['year'] == $time['year'])
            {
                // if end same day as time
                $c = $t2 - $currenttime;
                $timeworked += $c/60;
                $currenttime += $c;
            }
            else
            {
                // End on a different day
                $secondsintoday = (($t2date['hours']*60)*60)+($t2date['minutes']*60)+$t2date['seconds'];

                $timeworked += ($CONFIG['end_working_day']-$secondsintoday)/60;

                $currenttime += ($midnight*$secondsintoday)+$swd;
            }
        }
        else
        {
            // Jump closer to the next work minute
            if (!in_array($time['wday'], $CONFIG['working_days']))
            {
                // Move to next day
                $c = ($time['hours'] * 60) + $time['minutes'];
                $diff = $midnight - $c;
                $currenttime += ($diff * 60); // to seconds

                // Jump to start of working day
                $currenttime += ($swd * 60);
            }
            else if ($time['hours'] < $swd)
            {
                // jump to beginning of working day
                $c = ($time['hours'] * 60) + $time['minutes'];
                $diff = ($swd * 60) - $c;
                $currenttime += ($diff * 60); // to seconds
            }
            else if ($time['hours'] > $ewd)
            {
                // Jump to the start of the next working day
                $c = ($midnight - (($time['hours'] * 60) + $time['minutes'])) + ($swd * 60);
                $currenttime += ($c * 60);
            }
            else if ($ph != 0)
            {
                // jump to the minute after the public holiday
                $currenttime += $ph + 60;

                // Jump to start of working day
                $currenttime += ($swd * 60);
            }
            else
            {
                $currenttime += 60;  // move to the next minute
            }
        }
    }

    return $timeworked;
 */
}


/**
 * Calculate the engineer working time between two timestamps for a given incident
 i.e. ignore times when customer has action
 * @author Ivan Lucas
 @param int $incidentid - The incident ID to perform a calculation on
 @param int $t1 - UNIX Timestamp. Start of range
 @param int $t2 - UNIX Timestamp. End of range
 @param array $states (optional) Does not count time when the incident is set to
 any of the states in this array. (Default is closed, awaiting closure and awaiting customer action)
 */
function calculate_incident_working_time($incidentid, $t1, $t2, $states=array(STATUS_CLOSED, STATUS_CLOSING, STATUS_CUSTOMER))
{
    global $db;
    if ( $t1 > $t2 )
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
    }

    $startofday = mktime(0, 0, 0, date("m", $t1), date("d", $t1), date("Y", $t1));
    $endofday = mktime(23, 59, 59, date("m", $t2), date("d", $t2), date("Y", $t2));

    $publicholidays = get_public_holidays($startofday, $endofday);

    $sql = "SELECT id, currentstatus, timestamp FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$incidentid}' ORDER BY id ASC";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    $time = 0;
    $timeptr = 0;
    $laststatus = STATUS_CLOSED; // closed
    while ($update = mysqli_fetch_object($result))
    {
        //  if ($t1<=$update->timestamp'])
        if ($t1 <= $update->timestamp)
        {
            if ($timeptr == 0)
            {
                // This is the first update
                // If it's active, set the ptr = t1
                // otherwise set to current timestamp ???
                if (is_active_status($laststatus, $states))
                {
                    $timeptr = $t1;
                }
                else
                {
                    $timeptr = $update->timestamp;
                }
            }

            if ($t2 < $update->timestamp)
            {
                // If we have reached the very end of the range, increment time to end of range, break
                if (is_active_status($laststatus, $states))
                {
                    $time += calculate_working_time($timeptr, $t2, $publicholidays);
                }
                break;
            }

            // if status has changed or this is the first (active update)
            if (is_active_status($laststatus, $states) != is_active_status($update->currentstatus, $states))
            {
                // If it's active and we've not reached the end of the range, increment time
                if (is_active_status($laststatus, $states) && ($t2 >= $update->timestamp))
                {
                    $time += calculate_working_time($timeptr, $update->timestamp, $publicholidays);
                }
                else
                {
                    $timeptr = $update->timestamp;
                }
                // if it's not active set the ptr
            }
        }
        $laststatus = $update->currentstatus;
    }
    mysqli_free_result($result);

    // Calculate remainder
    if (is_active_status($laststatus, $states) AND ($t2 >= $update->timestamp))
    {
        $time += calculate_working_time($timeptr, $t2, $publicholidays);
    }

    return $time;
}


/**
 * @author Ivan Lucas
 */
function is_active_status($status, $states)
{
    if (in_array($status, $states)) return false;
    else return true;
}


/**
 * Returns the number of SLAs defined within SiT!
 * @return int number of SLAs defined
 * @author Paul Heaney
 */
function number_of_slas()
{
    global $dbServiceLevels, $db;
    $sql = "SELECT DISTINCT tag FROM `{$dbServiceLevels}`";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    return mysqli_num_rows($result);
}

?>