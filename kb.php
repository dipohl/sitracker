<?php
// kb.php - Browse knowledge base articles
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Ivan Lucas, Tom Gerrard

// This Page Is Valid XHTML 1.0 Transitional!  1Nov05

require ('core.php');
$permission = PERM_KB_VIEW; // View KB
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$search_string = cleanvar($_REQUEST['search_string']);
$mode = cleanvar($_REQUEST['mode']);

$title = $strBrowseKB;
$rssfeedurl = "kb_rss.php?c=".md5($_SESSION['username'] . md5($CONFIG['db_password']));
$rssfeedtitle = "{$strKnowledgeBase}: {$strRecent}";
include (APPLICATION_INCPATH . 'htmlheader.inc.php');
if (empty($mode) && empty($search_string)) $mode = 'RECENT';
if (empty($search_string) AND empty($mode)) $search_string = 'a';
echo "<h2>".icon('kb', 32, $title)." ";
echo "{$title}</h2>";
plugin_do('kb');
if (strtolower($mode) == 'recent') echo "<h4>{$strArticlesPublishedRecently}</h4>";
elseif (strtolower($mode) == 'today') echo "<h4>{$strArticlesPublishedToday}</h4>";

echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";
echo "<table summary='alphamenu' class='maintable'>";
echo "<tr><td align='center'>";
echo "<input type='text' name='search_string' /><input type='submit' value=\"{$strGo}\" />";
echo "</td></tr>";
echo "<tr><td class='alphamenu'>";
echo "<a href='{$_SERVER['PHP_SELF']}?mode=RECENT'>{$strRecent}</a>";
echo alpha_index("{$_SERVER['PHP_SELF']}?search_string=");
echo " | <a href='kb_article.php'>{$strNew}</a>";
echo "</td></tr>";
echo "</table>";
echo "</form>";
echo "<br />";

// ---------------------------------------------
// SQL Queries:

if (mb_strlen(utf8_decode($search_string)) > 4)
{
    // Find Software
    $sql = "SELECT * FROM `{$dbSoftware}` WHERE name LIKE '%{$search_string}%' LIMIT 20";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    echo "<p align='center'><strong>{$strSkills}</strong>: ";
    $softcount = mysqli_num_rows($result);
    $count = 1;
    $colcount = 1;
    while ($software = mysqli_fetch_object($result))
    {
        echo "{$software->name}";
        if ($count<$softcount) echo ", ";
        if ($colcount >= 4)
        {
            $colcount = 0;
            echo "<br />";
        }
        $count++; $colcount++;
    }
    echo "</p>\n";
}
// Find Articles
$sql = "SELECT * FROM `{$dbKBArticles}` ";
if (strtolower($mode) == 'myarticles') $sql .= "WHERE author='{$sit[2]}' ";
if (!empty($search_string) AND $search_string != '*')
{
    $sql .= "WHERE ";
    $search_string_len = mb_strlen($search_string);
    if (is_numeric($search_string))
    {
        $sql .= "docid=('{$search_string}') ";
    }
    elseif (mb_strtoupper(mb_substr($search_string, 0, mb_strlen($CONFIG['kb_id_prefix']))) == mb_strtoupper($CONFIG['kb_id_prefix']))
    {
        $sql .= "docid='" . mb_substr($search_string, mb_strlen($CONFIG['kb_id_prefix']))."' ";
    }
    elseif ($search_string_len<=2)
    {
        $sql .= "SUBSTRING(title,1,$search_string_len)=('{$search_string}') ";
    }
    else
    {
        $sql .= "title LIKE '%{$search_string}%' OR keywords LIKE '%{$search_string}%' ";
    }
}
if (strtolower($mode) == 'recent') $sql .= "ORDER BY docid DESC LIMIT 20";

if (strtolower($mode) == 'today') $sql .= " WHERE published > '".date('Y-m-d')."' ORDER BY published DESC";

$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

if (mysqli_num_rows($result) >= 1)
{
    echo "<p align='center'><strong>{$strResults}</strong> :</p>";
    echo "<table align='center' width='98%'>";
    echo "<tr>";
    echo colheader('id', $strID, FALSE);
    echo colheader('title', $strTitle, FALSE);
    echo colheader('date', $strDate, FALSE);
    echo colheader('author', $strAuthor, FALSE);
    echo colheader('keywords', $strKeywords, FALSE);
    echo "</tr>\n";
    $shade = 'shade1';
    while ($kbarticle = mysqli_fetch_object($result))
    {
        if (empty($kbarticle->title)) $kbarticle->title = $strUntitled;
        else $kbarticle->title = $kbarticle->title;

        if (is_numeric($kbarticle->author))
        {
            $kbarticle->author = user_realname($kbarticle->author);
        }
        else
        {
            $kbarticle->author = $kbarticle->author;
        }
        echo "<tr class='{$shade}'>";
        echo "<td>".icon('kb', 16)." {$CONFIG['kb_id_prefix']}".leading_zero(4,$kbarticle->docid)."</td>";
        echo "<td>";
        // Lookup what software this applies to
        $ssql = "SELECT * FROM `{$dbKBSoftware}` AS kbs, `{$dbSoftware}` AS s WHERE kbs.softwareid = s.id ";
        $ssql .= "AND kbs.docid = '{$kbarticle->docid}' ORDER BY s.name";
        $sresult = mysqli_query($db, $ssql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $rowcount = mysqli_num_rows($sresult);
        if ($rowcount >= 1 AND $rowcount < 3)
        {
            $count = 1;
            while ($kbsoftware = mysqli_fetch_object($sresult))
            {
                echo $kbsoftware->name;
                if ($count < $rowcount) echo ", ";
                $count++;
            }
        }
        elseif ($rowcount >= 4)
        {
            echo "Various";
        }
        echo "<br /><a href='kb_view_article.php?id={$kbarticle->docid}' class='info'>{$kbarticle->title}";
        $asql = "SELECT LEFT(content,400) FROM `{$dbKBContent}` WHERE docid='{$kbarticle->docid}' ORDER BY id ASC LIMIT 1";
        $aresult = mysqli_query($db, $asql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        list($content) = mysqli_fetch_row($aresult);
        $content = strip_tags(remove_slashes($content));
        echo "<span>{$content}</span>";
        echo "</a>";
        echo "</td>";
        echo "<td>".ldate($CONFIG['dateformat_date'], mysql2date($kbarticle->published))."</td>";
        echo "<td>".user_realname($kbarticle->author)."</td>";
        echo "<td>{$kbarticle->keywords}</td>";
        echo "</tr>\n";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>\n";
}
else
{
    echo "<p align='center'>{$strNoResults}</p>";
}

echo "<p align='center'><a href='kb_article.php'>{$strNew}</a></p>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>