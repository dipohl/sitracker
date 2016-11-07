<?php
// delete_note.php - Delete note
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
$permission = PERM_NOT_REQUIRED; // Allow all auth users
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$id = clean_int($_REQUEST['id']);
$rpath = cleanvar($_REQUEST['rpath']);

$sql = "DELETE FROM `{$dbNotes}` WHERE id='{$id}' AND userid='{$sit[2]}' LIMIT 1";
mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);
if (mysqli_affected_rows($db) >= 1) html_redirect($rpath);
else html_redirect($rpath, FALSE);
?>