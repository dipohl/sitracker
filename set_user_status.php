<?php
// set_user_status.php - Change the users status
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Note: This script no longer sets user status, this functionality
// was moved to ajaxdata.php on 17Apr10, the rest of the code on this
// page ought to be moved somewhere else as well.

$permission = 42;  // Review/Delete Incident Updates

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$mode = cleanvar($_REQUEST['mode']);
$incidentid = cleanvar($_REQUEST['incidentid']);
$originalowner = cleanvar($_REQUEST['originalowner']);

switch ($mode)
{
    case 'deleteassign':
        // this may not be the very best place for this functionality but it's all i could find - inl 19jan05
        // hide a record from tempassign as requested by clicking 'ignore' in the holding queue
        $sql = "UPDATE `{$dbTempAssigns}` SET assigned='yes' ";
        $sql .= "WHERE incidentid='{$incidentid}' AND originalowner='{$originalowner}' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        header("Location: holding_queue.php");
        break;
}
?>