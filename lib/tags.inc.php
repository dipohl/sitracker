<?php
// tags.inc.php - functions relating to Tags
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
 * Convert a tag name to a tag ID
 * @author Ivan Lucas
 * @param string $tag.
 * @returns int Tag ID
 */
function get_tag_id($tag)
{
    global $dbTags, $db;
    $sql = "SELECT tagid FROM `{$dbTags}` WHERE name = LOWER('$tag')";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    if (mysqli_num_rows($result) == 1)
    {
        $id = mysqli_fetch_row($result);
        return $id[0];
    }
    else
    {
        //need to add
        $sql = "INSERT INTO `{$dbTags}` (name) VALUES (LOWER('$tag'))";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);
        return mysqli_insert_id($db);
    }
}


/**
 * Apply a new tag to a record
 * @author Ivan Lucas
 * @param int $id. Record ID
 * @param int $type. Tag Type.
 * @param string $tag. Tag name
 * @returns bool. Success
 * @retval true.  This function always returns TRUE
 */
function new_tag($id, $type, $tag)
{
    global $dbSetTags, $db;
    /*
    TAG TYPES
    1 - contact
    2 - incident
    3 - Site
    4 - task
    5 - product
    6 - skill
    7 - kb article
    8 - report
 */
    if ($tag!='')
    {
        $tagid = get_tag_id($tag);
        // Ignore errors, die silently
        $sql = "INSERT INTO `{$dbSetTags}` VALUES ('$id', '$type', '$tagid')";
        $result = @mysqli_query($db, $sql);
    }
    return true;
}


/**
 * Remove a tag from a record. If no more records use the tag, the tag is also purged.
 * @author Ivan Lucas
 * @param int $id. Record ID
 * @param int $type Tag Type.
 * @param string $tag. Tag name
 * @returns bool. Success
 * @retval true.  This function always returns TRUE
 */
function remove_tag($id, $type, $tag)
{
    global $dbSetTags, $dbTags, $db;
    if ($tag != '')
    {
        $tagid = get_tag_id($tag);
        // Ignore errors, die silently
        $sql = "DELETE FROM `{$dbSetTags}` WHERE id = '$id' AND type = '$type' AND tagid = '$tagid'";
        $result = @mysqli_query($db, $sql);

        // Check tag usage count and remove (purge) disused tags completely
        purge_tag($tagid);
    }
    return true;
}


/**
 * Remove existing tags and replace with a new set
 * @param int $type Tag Type.
 * @param int $id. Record ID
 * @param string $tagstring. Tag name
 * @author Ivan Lucas
 */
function replace_tags($type, $id, $tagstring)
{
    global $dbSetTags, $db;
    // first remove old tags
    $sql = "DELETE FROM `{$dbSetTags}` WHERE id = '$id' AND type = '$type'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    // Change separators to spaces
    $separators = array(', ',';',',');
    $tags = str_replace($separators, ' ', trim($tagstring));
    $tag_array = explode(" ", $tags);
    foreach ($tag_array AS $tag)
    {
        new_tag($id, $type, trim($tag));
    }
}


/**
 * Purge a single tag (if needed)
 * @param int $tagid. The ID of the tag to purge
 * @author Ivan Lucas
 * @note the tag will not be purged (deleted) if it is in use
 */
function purge_tag($tagid)
{
    // Check tag usage count and remove disused tag completely
    global $dbSetTags, $dbTags, $db;
    $sql = "SELECT COUNT(id) FROM `{$dbSetTags}` WHERE tagid = '$tagid'";
    $result = mysqli_query($db, $sql);
    list($count) = mysqli_fetch_row($result);
    if ($count == 0)
    {
        $sql = "DELETE FROM `{$dbTags}` WHERE tagid = '$tagid' LIMIT 1";
        @mysqli_query($db, $sql);
    }
}


/**
 * Purge all tags (if needed)
 * @author Ivan Lucas
 * @note tags will not be purged (deleted) if it in use
 */
function purge_tags()
{
    global $dbTags, $db;
    $sql = "SELECT tagid FROM `{$dbTags}`";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        while ($tag = mysqli_fetch_object($result))
        {
            purge_tag($tag->tagid);
        }
    }
}


/**
 * Produce a list of tags
 * @author Ivan Lucas
 * @param int $recordid. The record ID to find tags for
 * @param int $type. The tag record type.
 * @param boolean $html. Return HTML when TRUE
 */
function list_tags($recordid, $type, $html = TRUE)
{
    global $CONFIG, $dbSetTags, $dbTags, $iconset, $db;

    $sql = "SELECT t.name, t.tagid FROM `{$dbSetTags}` AS s, `{$dbTags}` AS t WHERE s.tagid = t.tagid AND ";
    $sql .= "s.type = '$type' AND s.id = '$recordid'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    $numtags = mysqli_num_rows($result);

    if ($html AND $numtags > 0)
    {
        $str .= "<div class='taglist'>";
    }

    $count = 1;
    while ($tags = mysqli_fetch_object($result))
    {
        if ($html)
        {
            $str .= "<a href='view_tags.php?tagid={$tags->tagid}'>".$tags->name;
            if (array_key_exists($tags->name, $CONFIG['tag_icons']))
            {
                $str .= "&nbsp;<img src='images/icons/{$iconset}/16x16/{$CONFIG['tag_icons'][$tags->name]}.png' alt='' />";
            }
            $str .= "</a>";
        }
        else
        {
            $str .= $tags->name;
        }

        if ($count < $numtags) $str .= ", ";
        if ($html AND !($count%5)) $str .= "<br />\n";
        $count++;
    }
    if ($html AND $numtags > 0) $str .= "</div>";
    return trim($str);
}


/**
 * Return HTML to display a list of tag icons
 * @author Ivan Lucas
 * @param int $recordid. The ID of the record that tags may be attached to.
 * @param int $type. Tag type.
 * @return string. HTML
 */
function list_tag_icons($recordid, $type)
{
    global $CONFIG, $dbSetTags, $dbTags, $iconset, $db;
    $sql = "SELECT t.name, t.tagid ";
    $sql .= "FROM `{$dbSetTags}` AS st, `{$dbTags}` AS t WHERE st.tagid = t.tagid AND ";
    $sql .= "st.type = '$type' AND st.id = '$recordid' AND (";
    $counticons = count($CONFIG['tag_icons']);
    $count = 1;
    foreach ($CONFIG['tag_icons'] AS $icon)
    {
        $sql .= "t.name = '{$icon}'";
        if ($count < $counticons) $sql .= " OR ";
        $count++;
    }
    $sql .= ")";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    $numtags = mysqli_num_rows($result);
    if ($numtags > 0)
    {
        while ($tags = mysqli_fetch_object($result))
        {
            $str .= "<a href='view_tags.php?tagid={$tags->tagid}' title='{$tags->name}'>";
            $str .= "<img src='images/icons/{$iconset}/16x16/{$CONFIG['tag_icons'][$tags->name]}.png' alt='{$tags->name}' />";
            $str .= "</a> ";
        }
    }
    return $str;
}


/**
 * Generate a tag cloud
 * @author Ivan Lucas, Tom Gerrard
 * @param string $orderby. Name of column to sort by
 * @param bool $showcount. Set to TRUE to show a count of the number of tags, or FALSE to ommit
 * @returns string. HTML
*/
function show_tag_cloud($orderby="name", $showcount = FALSE)
{
    global $CONFIG, $dbTags, $dbSetTags, $iconset, $db;

    // First purge any disused tags
    purge_tags();
    $sql = "SELECT COUNT(name) AS occurrences, name, t.tagid FROM `{$dbTags}` AS t, `{$dbSetTags}` AS st WHERE t.tagid = st.tagid GROUP BY name ORDER BY $orderby";
    if ($orderby == "occurrences") $sql .= " DESC";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

    $countsql = "SELECT COUNT(id) AS counted FROM `{$dbSetTags}` GROUP BY tagid ORDER BY counted DESC LIMIT 1";
    $countresult = mysqli_query($db, $countsql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    list($max) = mysqli_fetch_row($countresult);

    $countsql = "SELECT COUNT(id) AS counted FROM `{$dbSetTags}` GROUP BY tagid ORDER BY counted ASC LIMIT 1";
    $countresult = mysqli_query($db, $countsql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
    list($min) = mysqli_fetch_row($countresult);
    unset($countsql, $countresult);

    if (mb_substr($_SERVER['SCRIPT_NAME'],-8) != "main.php")
    {
        //not in the dashboard
        $operations = array();
        $operations[$GLOBALS['strAlphabetically']] = 'view_tags.php?orderby=name';
        $operations[$GLOBALS['strPopularity']] = 'view_tags.php?orderby=occurrences';
        $html .= "<p align='center'>{$GLOBALS['strSort']}: " . html_action_links($operations) . "</p>";
    }

    if (mysqli_num_rows($result) > 0)
    {
        $html .= "<table class='maintable'><tr><td class='tagcloud'>";
        while ($obj = mysqli_fetch_object($result))
        {
            $size = round(log($obj->occurrences * 100) * 32);
            if ($size == 0) $size = 100;
            if ($size > 0 AND $size <= 100) $taglevel = 'taglevel1';
            if ($size > 100 AND $size <= 150) $taglevel = 'taglevel2';
            if ($size > 150 AND $size <= 200) $taglevel = 'taglevel3';
            if ($size > 200) $taglevel = 'taglevel4';
            $html .= "<a href='view_tags.php?tagid=$obj->tagid' class='$taglevel' style='font-size: {$size}%; font-weight: normal;' title='{$obj->occurrences}'>";
            if (array_key_exists($obj->name, $CONFIG['tag_icons']))
            {
                $html .= "{$obj->name}&nbsp;<img src='images/icons/{$iconset}/";
                if ($size <= 200)
                {
                    $html .= "16x16";
                }
                else
                {
                    $html .= "32x32";
                }
                $html .= "/{$CONFIG['tag_icons'][$obj->name]}.png' alt='' />";
            }
            else $html .= $obj->name;
            $html .= "</a>";
            if ($showcount) $html .= "({$obj->occurrences})";
            $html .= " \n";//&nbsp;\n";
        }
        $html .= "</td></tr></table>";
    }
    else $html .= user_alert($GLOBALS['strNothingToDisplay'], E_USER_NOTICE);
    return $html;
}

?>