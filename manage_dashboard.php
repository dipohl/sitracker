<?php
// manager_dashboard.php - Page to install a new dashboard component
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_DASHLET_INSTALL; // Install dashboard components
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strManageDashboardComponents;


$action = clean_fixed_list($_REQUEST['action'], array('', 'install', 'installdashboard', 'enable', 'upgradecomponent'));


// TODO A duplicate of that in setup.php - Probably wants moving to functions.inc.php eventually PH 9/12/07
function setup_exec_sql($sqlquerylist)
{
    global $CONFIG;
    if (!empty($sqlquerylist))
    {
        $sqlqueries = explode( ';', $sqlquerylist);
        // We don't need the last entry it's blank, as we end with a ;
        array_pop($sqlqueries);
        foreach ($sqlqueries AS $sql)
        {
            mysqli_query($db, $sql);
            if (mysqli_error($db))
            {
                $str = "A MySQL error occurred, this could be because the MySQL user '{$CONFIG['db_username']}' does not have appropriate permission to modify the database schema. ";
                $str .= "An error might also be caused by an attempt to upgrade a version that is not supported by this script.<br />";
                $str .= "Alternatively, you may have found a bug, if you think this is the case please report it.</p>";
                $str .= "The error was ".mysqli_error($db)." and the SQL was ".htmlspecialchars($sql);
                trigger_error($str, E_USER_ERROR);
            }
            else $html .= "<p><strong>{$strOK}:</strong> ".htmlspecialchars($sql)."</p>";
        }
    }
    return $html;
}

switch ($action)
{
    case 'install':
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        $sql = "SELECT name FROM `{$dbDashboard}`";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

        echo "<h2>".icon('dashboard', 32)." ";
        echo $strInstallDashboardComponents."</h2>";
        echo "<p align='center'>".sprintf($strComponentMustBePlacedInDashboardDir, "<var>dashboard_NAME</var>")."</p>";
        while ($dashboardnames = mysqli_fetch_object($result))
        {
            $dashboard[$dashboardnames->name] = $dashboardnames->name;
        }

        $path = APPLICATION_PLUGINPATH;

        $dir_handle = @opendir($path) or trigger_error("Unable to open dashboard directory {$path}", E_USER_ERROR);

        while ($file = readdir($dir_handle))
        {
            if (beginsWith($file, "dashboard_") AND endsWith($file, ".php"))
            {
                if (empty($dashboard[mb_substr($file, 10, mb_strlen($file) - 14)]))  //this is 14 due to .php =4 and dashboard_ = 10
                {
                    $html .= "<option value='".mb_substr($file, 10, mb_strlen($file) - 14)."'>".mb_substr($file, 10, mb_strlen($file) - 14)." ({$file})</option>";
                }
            }
        }

        closedir($dir_handle);

        if (empty($html))
        {
            echo "<p align='center'>{$strNoNewDashboardComponentsToInstall}</p>";
            echo "<p align='center'><a href='manage_dashboard.php'>{$strBackToList}</a></p>";
        }
        else
        {
            echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n";
            echo "<table class='maintable vertical'><tr><td>\n";
            echo "<select name='comp[]' multiple='multiple' size='20'>\n";
            echo $html;
            echo "</select>\n";
            echo "</td></tr></table>\n";
            echo "<input type='hidden' name='action' value='installdashboard' />";
            echo "<p class='formbuttons'><input type='submit' value='{$strInstall}' /></p>";
            echo "</form>\n";
        }

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

        break;
    case 'installdashboard':
        $dashboardcomponents = cleanvar($_REQUEST['comp']);
        if (is_array($dashboardcomponents))
        {
            $count = count($dashboardcomponents);

            $sql = "INSERT INTO `{$dbDashboard}` (`name`, `enabled`) VALUES ";
            for($i = 0; $i < $count; $i++)
            {
                $sql .= "('{$dashboardcomponents[$i]}', 'true'), ";
            }
            $result = mysqli_query($db, mb_substr($sql, 0, mb_strlen($sql) - 2));
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

            if (!$result)
            {
                html_redirect("manage_dashboard.php", false, $strFailed);
                exit;
            }
            else
            {
                $installed = TRUE;
                // run the post install components
                foreach ($dashboardcomponents AS $comp)
                {
                    include (APPLICATION_PLUGINPATH . "dashboard_{$comp}.php");
                    $func = "dashboard_{$comp}_install";
                    if (function_exists($func)) $installed = $func();
                    if ($installed !== TRUE)
                    {
                        // Dashboard component install failed, roll back
                        $dsql = "DELETE FROM `{$dbDashboard}` WHERE `name` = '{$comp}'";
                        mysqli_query($db, $dsql);
                        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
                    }
                }
                html_redirect("manage_dashboard.php", $installed);
            }
        }
        break;

    case 'upgradecomponent':
        $id = clean_int($_REQUEST['id']);
        $sql = "SELECT * FROM `{$dbDashboard}` WHERE id = {$id}";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

        if (mysqli_num_rows($result) > 0)
        {
            $obj = mysqli_fetch_object($result);

            $version = 1;
            include (APPLICATION_PLUGINPATH . "dashboard_{$obj->name}.php");
            $func = "dashboard_{$obj->name}_get_version";

            if (function_exists($func))
            {
                $version = $func();
            }

            if ($version > $dashboardnames->version)
            {
                // apply all upgrades since running version
                $func = "dashboard_{$obj->name}_upgrade";

                if (function_exists($func))
                {
                    $schema = $func();
                    for($i = $obj->version; $i <= $version; $i++)
                    {
                        setup_exec_sql($schema[$i]);
                    }

                    $sql = "UPDATE `{$dbDashboard}` SET version = '{$version}' WHERE id = {$obj->id}";
                    mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
                    html_redirect($_SERVER['PHP_SELF']);
                }
                else
                {
                    echo "<p class='error'>{$strNoSchemaAvailableToUpgrade}</p>";
                }
            }
            else
            {
                echo "<p class='error'>".sprintf($strNoUpgradesForDashboardComponent, $obj->name)."</p>";
            }
        }
        else
        {
            echo "<p class='error'>".sprintf($strDashboardComponentDoesntExist, $id)."</p>";
        }

        break;

    case 'enable':
        $id = clean_int($_REQUEST['id']);
        $enable = clean_fixed_list($_REQUEST['enable'], array('false', 'true'));
        $sql = "UPDATE `{$dbDashboard}` SET enabled = '{$enable}' WHERE id = '{$id}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

        if (!$result)
        {
            html_redirect("manage_dashboard.php", false, $strChangeStateFailed);
        }
        else
        {
            html_redirect("manage_dashboard.php");
        }
        break;

    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        $sql = "SELECT * FROM `{$dbDashboard}`";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

        echo "<h2>".icon('dashboard', 32)." ";
        echo "{$strManageDashboardComponents}</h2>";
        echo "<table class='vertical' align='center'><tr>";
        echo colheader('id', $strID);
        echo colheader('name', $strName);
        echo colheader('enabled', $strEnabled);
        echo colheader('version', $strVersion);
        echo colheader('upgrade', $strUpgrade);
        echo "</tr>";
        while ($dashboardnames = mysqli_fetch_object($result))
        {
            if ($dashboardnames->enabled == "true")
            {
                $opposite = "false";
            }
            else
            {
                $opposite = "true";
            }

            echo "<tr class='shade2'><td>{$dashboardnames->id}</td>";
            echo "<td>{$dashboardnames->name}</td>";
            echo "<td><a href='{$_SERVER['PHP_SELF']}?action=enable&amp;id={$dashboardnames->id}&amp;enable={$opposite}'>";
            if ($dashboardnames->enabled == 'true') echo $strYes;
            else echo $strNo;
            echo "</a></td>";

            echo "<td>{$dashboardnames->version}</td>";
            echo "<td>";

            $version = 1;
            include (APPLICATION_PLUGINPATH . "dashboard_{$dashboardnames->name}.php");
            $func = "dashboard_{$dashboardnames->name}_get_version";

            if (function_exists($func))
            {
                $version = $func();
            }

            if ($version > $dashboardnames->version)
            {
                echo "<a href='{$_SERVER['PHP_SELF']}?action=upgradecomponent&amp;id={$dashboardnames->id}'>{$strYes}</a>";
            }
            else
            {
                echo $strNo;
            }

            echo "</td></tr>";
        }
        echo "</table>";

        echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?action=install'>{$strInstall}</a></p>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
}

?>
