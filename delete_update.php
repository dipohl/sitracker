<?php
// delete_update.php - Deletes incident updates (log entries) from the database
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_UPDATE_DELETE; // Delete Incident Updates
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$updateid = clean_int($_REQUEST['updateid']);
$timestamp = clean_int($_REQUEST['timestamp']);
$tempid = clean_int($_REQUEST['tempid']);

if (empty($updateid)) trigger_error("!Error: Update ID was not set, not deleting!: {$updateid}", E_USER_WARNING);

$deleted_files = TRUE;
$path = $CONFIG['attachment_fspath'].'updates' . DIRECTORY_SEPARATOR;

$sql = "SELECT linkcolref, filename FROM `{$dbLinks}` as l, `{$dbFiles}` as f ";
$sql .= "WHERE origcolref = '{$updateid}' ";
$sql .= "AND linktype = 5 ";
$sql .= "AND l.linkcolref = f.id ";

if ($result = @mysqli_query($db, $sql))
{
    while ($row = mysqli_fetch_object($result))
    {
        $file = $path.$row->linkcolref . "-" . $row->filename;
        if (file_exists($file))
        {
            $del = unlink($file);
            if (!$del)
            {
                trigger_error("Deleting attachment failed", E_USER_ERROR);
                $deleted = FALSE;
            }
        }
    }
}

if ($deleted_files)
{
    // We delete using ID and timestamp to make sure we dont' delete the wrong update by accident
    $sql = "DELETE FROM `{$dbUpdates}` WHERE id='{$updateid}' AND timestamp='{$timestamp}'";  // We might in theory have more than one ...
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    $sql = "DELETE FROM `{$dbTempIncoming}` WHERE id='{$tempid}'";
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
}

journal(CFG_LOGGING_NORMAL, 'Incident Log Entry Deleted', "Incident Log Entry {$updateid} was deleted from Incident {$incidentid}", CFG_JOURNAL_INCIDENTS, $incidentid);
html_redirect("holding_queue.php");
?>