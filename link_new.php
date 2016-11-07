<?php
// link_new.php - Add a link between two tables
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

$title = $strNewLink;

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('', 'addlink'));
$origtab = cleanvar($_REQUEST['origtab']);
$origref = cleanvar($_REQUEST['origref']);
$linkref = cleanvar($_REQUEST['linkref']);
$linktypeid = clean_int($_REQUEST['linktype']);
$direction = cleanvar($_REQUEST['dir']);
if ($direction == '') $direction = 'left';
$redirect = cleanvar($_REQUEST['redirect']);

switch ($action)
{
    case 'addlink':
        // Insert the link
        $sql = "INSERT INTO `{$dbLinks}` ";
        $sql .= "(linktype, origcolref, linkcolref, direction, userid) ";
        $sql .= "VALUES ('{$linktypeid}', '{$origref}', '{$linkref}', '{$direction}', {$sit[2]}')";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);

        html_redirect($redirect);
        break;
    case '':
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        // Find out what kind of link we are to make
        $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE id='{$linktypeid}'";
        $result = mysqli_query($db, $sql);
        while ($linktype = mysqli_fetch_object($result))
        {
            if ($direction == 'left')
            {
                echo "<h2>Link {$linktype->lrname}</h2>";
            }
            elseif ($direction == 'right')
            {
                echo "<h2>Link {$linktype->rlname}</h2>";
            }

            echo "<p align='center'>" . sprintf($strMakeAXLinkFromOrigTabXtoX, $linktype, $origtab, $origref) . "</p>";
            $recsql = "SELECT {$linktype->linkcol} AS recordref, {$linktype->selectionsql} AS recordname FROM `{$CONFIG['db_tableprefix']}{$linktype->linktab}` ";
            $recsql .= "WHERE {$linktype->linkcol} != '{$origref}'";

            $recresult = mysqli_query($db, $recsql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
            if (mysqli_num_rows($recresult) >= 1)
            {
                echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
                echo "<p>";
                echo "<select name='linkref'>";
                while ($record = mysqli_fetch_object($recresult))
                {
                    echo "<option value='{$record->recordref}'>{$record->recordname}</option>\n";
                }
                echo "</select>";
                echo "</p>";
                echo "<p><input name='submit' type='submit' value='{$strNew}' /></p>";
                echo "<input type='hidden' name='action' value='addlink' />";
                echo "<input type='hidden' name='origtab' value='{$origtab}' />";
                echo "<input type='hidden' name='origref' value='{$origref}' />";
                echo "<input type='hidden' name='linktype' value='{$linktypeid}' />";
                echo "<input type='hidden' name='dir' value='{$direction}' />";
                echo "<input type='hidden' name='redirect' value='" . htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, $i18ncharset) . "' />";
                echo "</form>";
            }
            else echo user_alert($strNothingToLink, E_USER_WARNING);
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>