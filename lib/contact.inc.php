<?php
// contact.inc.php - functions relating to contacts
//
// NOTE: once we move to a more OO model these functions will be merged into contact.class.php
//       Moving this functions here as a short term measure (PH 2010-04-11)
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
 * Returns an array of all active contracts from contact id
 * @author Carsten Jensen
 * @param int $userid. Contact ID
 * @return array|bool array of contract id's or false if none
 */
function contact_active_contracts($userid)
{
    global $db;
    $sql  = "SELECT sc.maintenanceid AS maintenanceid ";
    $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
    $sql .= "`{$GLOBALS['dbSupportContacts']}` AS sc, ";
    $sql .= "`{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE c.id = '{$userid}' ";
    $sql .= "AND (sc.maintenanceid=m.id AND sc.contactid='{$userid}') ";
    $sql .= "AND m.term <> 'yes' ";
    $sql .= "AND (m.expirydate > '" . time() . "' OR m.expirydate = '-1') ";
    // Contracts we're an 'all supported' on
    $sql .= "UNION ";
    $sql .= "SELECT m.id AS maintenanceid ";
    $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
    $sql .= "`{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE c.id = '{$userid}' AND c.siteid = m.site ";
    $sql .= "AND m.allcontactssupported = 'yes' ";
    $sql .= "AND m.term <> 'yes' ";
    $sql .= "AND (m.expirydate > '" . time() . "' OR m.expirydate = '-1') ";

    $result = mysqli_query($db, $sql);
    if (!mysqli_error($db))
    {
        if (mysqli_num_rows($result) > 0)
        {
            $return = array();
            while ($obj = mysqli_fetch_object($result))
            {
                $return[] = $obj->maintenanceid;
            }
            return $return;
        }
    }
    return false;
}


/**
 * Find a contacts real name
 * @author Ivan Lucas
 * @param int $id. Contact ID
 * @return string. Full name or 'Unknown'
 */
function contact_realname($id)
{
    global $dbContacts, $db;
    $sql = "SELECT forenames, surname FROM `{$dbContacts}` WHERE id='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) == 0)
    {
        mysqli_free_result($result);
        return ($GLOBALS['strUnknown']);
    }
    else
    {
        $contact = mysqli_fetch_object($result);
        $realname = "{$contact->forenames} {$contact->surname}";
        mysqli_free_result($result);
        return $realname;
    }
}


/**
 * Return a contacts site name
 * @author Ivan Lucas
 * @param int $id. Contact ID
 * @return string. Full site name or 'Unknown'
 * @note this returns the site _NAME_ not the siteid for the site id use contact_siteid()
 */
function contact_site($id)
{
    global $dbContacts, $dbSites, $db;
    //
    $sql = "SELECT s.name FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id AND c.id = '{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) == 0)
    {
        mysqli_free_result($result);
        return $GLOBALS['strUnknown'];
    }
    else
    {
        list($contactsite) = mysqli_fetch_row($result);
        mysqli_free_result($result);
        $contactsite = $contactsite;
        return $contactsite;
    }
}


/**
 * Return a contacts site ID
 * @author Ivan Lucas
 * @param int $id. Contact ID
 * @return int. Site ID
 */
function contact_siteid($id)
{
    return db_read_column('siteid', $GLOBALS['dbContacts'], $id);
}


/**
 * Return a contacts email address
 * @author Ivan Lucas
 * @param int $id. Contact ID
 * @return string Email address
 */
function contact_email($id)
{
    return db_read_column('email', $GLOBALS['dbContacts'], $id);
}


/**
 * Return a contacts phone number
 * @author Ivan Lucas
 * @param integer $id. Contact ID
 * @return string. Phone number
 */
function contact_phone($id)
{
    return db_read_column('phone', $GLOBALS['dbContacts'], $id);
}


/**
 * Return a contacts fax number
 * @author Ivan Lucas
 * @param int $id. Contact ID
 * @return string. Fax number
 */
function contact_fax($id)
{
    return db_read_column('fax', $GLOBALS['dbContacts'], $id);
}


/**
 * Returns yes/no if contact wants to receive feedback
 * @author Carsten Jensen
 * @param int $id the id of the contact
 * @return yes/no or FALSE if no results
 * @retval string yes if contact wants to receive feedback
 * @retval string no if contact doesn't want to receive feedback
 */
function contact_feedback($id)
{
    global $dbContactConfig, $db;
    $sql = "SELECT `value` FROM `{$dbContactConfig}` WHERE contactid = {$id} AND config = 'feedback_enable' LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) == 0)
    {
        // Contact hasn't opted out so we assume yes
        $answer = "yes";
    }
    else
    {
        list($answer) = mysqli_fetch_row($result);
        $answer = strtolower($answer);
    }
    return $answer;
}


/**
 * Return the number of incidents ever logged against a contact
 * @author Ivan Lucas
 * @param int $id. Contact ID
 * @return int.
 */
function contact_count_incidents($id)
{
    global $dbIncidents, $db;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE contact='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    else list($count) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $count;
}


/**
 * Return the number of inventory items for a contact
 * @author Kieran Hogg
 * @param int $id. Contact ID
 * @return int.
 */
function contact_count_inventory_items($id)
{
    global $dbInventory, $db;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbInventory}` WHERE contactid='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    else list($count) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $count;
}


/**
 * The number representing the total number of currently OPEN incidents submitted by a given contact.
 * @author Ivan Lucas
 * @param int $id. The Contact ID to check
 * @return integer. The number of currently OPEN incidents for the given contact
 */
function contact_count_open_incidents($id)
{
    global $dbIncidents, $db;
    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE contact={$id} AND status<>2";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    list($count) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $count;
}


/**
 * Creates a vcard electronic business card for the given contact
 * @author Ivan Lucas
 * @param int $id Contact ID
 * @return string vcard
 */
function contact_vcard($id)
{
    global $dbContacts, $dbSites, $db;
    $sql = "SELECT *, s.name AS sitename, s.address1 AS siteaddress1, s.address2 AS siteaddress2, ";
    $sql .= "s.city AS sitecity, s.county AS sitecounty, s.country AS sitecountry, s.postcode AS sitepostcode ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
    $sql .= "WHERE c.siteid = s.id AND c.id = '{$id}' LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    $contact = mysqli_fetch_object($result);
    $vcard = "BEGIN:VCARD\r\n";
    $vcard .= "N:{$contact->surname};{$contact->forenames};{$contact->courtesytitle}\r\n";
    $vcard .= "FN:{$contact->forenames} {$contact->surname}\r\n";
    if (!empty($contact->jobtitle)) $vcard .= "TITLE:{$contact->jobtitle}\r\n";
    if (!empty($contact->sitename)) $vcard .= "ORG:{$contact->sitename}\r\n";
    if ($contact->dataprotection_phone != 'Yes') $vcard .= "TEL;TYPE=WORK:{$contact->phone}\r\n";
    if ($contact->dataprotection_phone != 'Yes' AND !empty($contact->fax))
    {
        $vcard .= "TEL;TYPE=WORK;TYPE=FAX:{$contact->fax}\r\n";
    }

    if ($contact->dataprotection_phone != 'Yes' AND !empty($contact->mobile))
    {
        $vcard .= "TEL;TYPE=WORK;TYPE=CELL:{$contact->mobile}\r\n";
    }

    if ($contact->dataprotection_email != 'Yes' AND !empty($contact->email))
    {
        $vcard .= "EMAIL;TYPE=INTERNET:{$contact->email}\r\n";
    }

    if ($contact->dataprotection_address != 'Yes')
    {
        if ($contact->address1 != '')
        {
            $vcard .= "ADR;WORK:{$contact->address1};{$contact->address2};{$contact->city};{$contact->county};{$contact->postcode};{$contact->country}\r\n";
        }
        else
        {
            $vcard .= "ADR;WORK:{$contact->siteaddress1};{$contact->siteaddress2};{$contact->sitecity};{$contact->sitecounty};{$contact->sitepostcode};{$contact->sitecountry}\r\n";
        }
    }

    if (!empty($contact->notes))
    {
        $vcard .= "NOTE:{$contact->notes}\r\n";
    }

    $vcard .= "REV:".iso_8601_date($contact->timestamp_modified)."\r\n";
    $vcard .= "END:VCARD\r\n";
    return $vcard;
}


/**
 * prints the HTML for a drop down list of contacts, with the given name
 * and with the given id  selected.
 * @author Ivan Lucas
 */
function contact_drop_down($name, $id = '', $showsite = FALSE, $required = FALSE)
{
    global $dbContacts, $dbSites, $db;
    if ($showsite)
    {
        $sql  = "SELECT c.id AS contactid, s.id AS siteid, surname, forenames, ";
        $sql .= "s.name AS sitename, s.department AS department ";
        $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id AND c.active = 'true' ";
        $sql .= "AND s.active = 'true' ";
        $sql .= "ORDER BY s.name, s.department, surname ASC, forenames ASC";
    }
    else
    {
        $sql  = "SELECT c.id AS contactid, surname, forenames FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
        $sql .= "WHERE c.siteid = s.id AND s.active = 'true' AND c.active = 'true' ";
        $sql .= "ORDER BY forenames ASC, surname ASC";
    }

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    $html = "<select name='{$name}' id='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">\n";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    $prevsite = 0;
    while ($contacts = mysqli_fetch_object($result))
    {
        if ($showsite AND $prevsite != $contacts->siteid AND $prevsite != 0)
        {
            $html .= "</optgroup>\n";
        }

        if ($showsite AND $prevsite != $contacts->siteid)
        {
            $html .= "<optgroup label=\"{$contacts->sitename}, {$contacts->department}\">";
        }

        $realname = "{$contacts->forenames} {$contacts->surname}";
        $html .= "<option ";
        if ($contacts->contactid == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$contacts->contactid}'>{$realname}";
        $html .= "</option>\n";

        $prevsite = $contacts->siteid;
    }

    if ($showsite)
    {
        $html.= "</optgroup>";
    }

    $html .= "</select>\n";
    return $html;
}


/**
 * prints the HTML for a drop down list of contacts along with their site, with the given name and
 * and with the given id selected.
 * @author Ivan Lucas
 * @param string $name. The name of the field
 * @param int $id. Select this contactID by default
 * @param int $siteid. (optional) Filter list to show contacts from this siteID only
 * @param mixed $exclude int|array (optional) Do not show this contactID in the list, accepts an integer or array of integers
 * @param bool $showsite (optional) Suffix the name with the site name
 * @param bool $allownone (optional) Allow 'none' to be selected (blank value)
 * @return string.  HTML select
 */
function contact_site_drop_down($name, $id, $siteid='', $exclude='', $showsite=TRUE, $allownone=FALSE)
{
    global $dbContacts, $dbSites, $db;
    $sql  = "SELECT c.id AS contactid, forenames, surname, siteid, s.name AS sitename ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
    $sql .= "WHERE c.siteid = s.id AND c.active = 'true' AND s.active = 'true' ";
    if (!empty($siteid)) $sql .= "AND s.id='{$siteid}' ";
    if (!empty($exclude))
    {
        if (is_array($exclude))
        {
            foreach ($exclude AS $contactid)
            {
                $sql .= "AND c.id != {$contactid} ";
            }
        }
        else
        {
            $sql .= "AND c.id != {$exclude} ";
        }
    }
    $sql .= "ORDER BY surname ASC";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    $html = "<select name='$name'>";

    if (mysqli_num_rows($result) > 0)
    {
        if ($allownone) $html .= "<option value='' selected='selected'>{$GLOBALS['strNone']}</option>";
        while ($contacts = mysqli_fetch_object($result))
        {
            $html .= "<option ";
            if ($contacts->contactid == $id)
            {
                $html .= "selected='selected' ";
            }

            $html .= "value='{$contacts->contactid}'>";
            if ($showsite)
            {
                $html .= htmlspecialchars("{$contacts->surname}, {$contacts->forenames} - {$contacts->sitename}");
            }
            else
            {
                $html .= htmlspecialchars("{$contacts->surname}, {$contacts->forenames}");
            }
            $html .= "</option>\n";
        }
    }
    else
    {
        $html .= "<option value=''>{$GLOBALS['strNone']}</option>";
    }

    $html .= "</select>\n";
    return $html;
}


/**
 * Return the email address of the notify contact of the given contact
 * @author Ivan Lucas
 * @param int $contactid Contact ID
 * @return string. email address.
 */
function contact_notify_email($contactid)
{
    global $dbContacts, $db;
    $sql = "SELECT notify_contactid FROM `{$dbContacts}` WHERE id='{$contactid}' LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    list($notify_contactid) = mysqli_fetch_row($result);

    $sql = "SELECT email FROM `{$dbContacts}` WHERE id='{$notify_contactid}' LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    list($email) = mysqli_fetch_row($result);

    return $email;
}


/**
 * Returns the contact ID of the notify contact for the given contact ID
 * @author Ivan Lucas
 * @param int $contactid. Contact ID
 * @param int $level. Number of levels to recurse upwards
 * @note If Level is specified and is >= 1 then the notify contact is
 * found recursively, ie. the notify contact of the notify contact etc.
 */
function contact_notify($contactid, $level=0)
{
    global $dbContacts, $db;
    $notify_contactid = 0;
    if ($level == 0)
    {
        return $contactid;
    }
    else
    {
        $sql = "SELECT notify_contactid FROM `{$dbContacts}` WHERE id='{$contactid}' LIMIT 1";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        list($notify_contactid) = mysqli_fetch_row($result);

        if ($level > 0)
        {
            $newlevel = $level -1;
            $notify_contactid = contact_notify($notify_contactid, $newlevel);

        }
        return $notify_contactid;
    }
}


/**
 * Returns the contacts's portal username
 *
 * @param int $userid ID of the contact
 * @return string username
 * @author Kieran Hogg
 */
function contact_username($userid)
{
    $userid = intval($userid);
    return db_read_column('username', $GLOBALS['dbContacts'], $userid);
}


/**
 * Proceses form data for a new contact and add it the database
 *
 * @author Kieran Hogg
 * @param string $mode. Set to 'internal' for internal SiT! interface, or 'external' for portal.
 */
function process_new_contact($mode = 'internal')
{
    global $now, $CONFIG, $dbContacts, $sit, $db;
    // Add new contact
    // External variables
    $siteid = clean_int($_REQUEST['siteid']);
    $email = strtolower(clean_dbstring($_REQUEST['email']));
    $dataprotection_email = mysqli_real_escape_string($db, $_REQUEST['dataprotection_email']);
    $dataprotection_phone = mysqli_real_escape_string($db, $_REQUEST['dataprotection_phone']);
    $dataprotection_address = mysqli_real_escape_string($db, $_REQUEST['dataprotection_address']);
    $username = cleanvar($_REQUEST['username']);
    $courtesytitle = cleanvar($_REQUEST['courtesytitle']);
    $forenames = cleanvar($_REQUEST['forenames']);
    $surname = cleanvar($_REQUEST['surname']);
    $jobtitle = cleanvar($_REQUEST['jobtitle']);
    $address1 = convert_string_null_safe(cleanvar($_REQUEST['address1']));
    $address2 = convert_string_null_safe(cleanvar($_REQUEST['address2']));
    $city = convert_string_null_safe(cleanvar($_REQUEST['city']));
    $county = convert_string_null_safe(cleanvar($_REQUEST['county']));
    if (!empty($address1))
    {
        $country = convert_string_null_safe(cleanvar($_REQUEST['country']));
    }
    else
    {
        $country = 'Null';
    }
    $postcode = convert_string_null_safe(cleanvar($_REQUEST['postcode']));
    $phone = convert_string_null_safe(cleanvar($_REQUEST['phone']));
    $mobile = convert_string_null_safe(cleanvar($_REQUEST['mobile']));
    $fax = convert_string_null_safe(cleanvar($_REQUEST['fax']));
    $department = convert_string_null_safe(cleanvar($_REQUEST['department']));
    $notes = convert_string_null_safe(cleanvar($_REQUEST['notes']));
    $returnpage = cleanvar($_REQUEST['return']);
    $_SESSION['formdata']['new_contact'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;
    // check for blank name
    if ($forenames == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_contact']['forenames'] = sprintf($GLOBALS['strFieldMustNotBeBlank'], $GLOBALS['strForenames']);
    }
    if ($surname == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_contact']['surname'] = sprintf($GLOBALS['strFieldMustNotBeBlank'], $GLOBALS['strSurname']);
    }
    // check for blank site
    if ($siteid == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_contact']['siteid'] = sprintf($GLOBALS['strFieldMustNotBeBlank'], $GLOBALS['strSite']);
    }
    // check for blank email
    if ($email == '' OR $email == 'none' OR $email == 'n/a')
    {
        $errors++;
        $_SESSION['formerrors']['new_contact']['email'] = sprintf($GLOBALS['strFieldMustNotBeBlank'], $GLOBALS['strEmail']);
    }
    if ($siteid == 0 OR $siteid == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_contact']['siteid'] = sprintf($GLOBALS['strFieldMustNotBeBlank'], $GLOBALS['strSite']);
    }
    // Check this is not a duplicate
    $sql = "SELECT id FROM `{$dbContacts}` WHERE email='$email' AND LCASE(surname)=LCASE('$surname') LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_num_rows($result) >= 1)
    {
        $errors++;
        $_SESSION['formerrors']['new_contact']['duplicate'] = $GLOBALS['strContactRecordExists'];
    }

    plugin_do('contact_new_submitted');

    // add contact if no errors
    if ($errors == 0)
    {
        if (!empty($dataprotection_email))
        {
            $dataprotection_email = 'Yes';
        }
        else
        {
            $dataprotection_email = 'No';
        }

        if (!empty($dataprotection_phone))
        {
            $dataprotection_phone = 'Yes';
        }
        else
        {
            $dataprotection_phone = 'No';
        }

        if (!empty($dataprotection_address))
        {
            $dataprotection_address = 'Yes';
        }
        else
        {
            $dataprotection_address = 'No';
        }

        // generate username and password

        $username = mb_strtolower(mb_substr($surname, 0, strcspn($surname, " "), 'UTF-8'));
        $prepassword = generate_password();

        $password = md5($prepassword);

        $sql  = "INSERT INTO `{$dbContacts}` (username, password, courtesytitle, forenames, surname, jobtitle, ";
        $sql .= "siteid, address1, address2, city, county, country, postcode, email, phone, mobile, fax, ";
        $sql .= "department, notes, dataprotection_email, dataprotection_phone, dataprotection_address, ";
        $sql .= "timestamp_added, timestamp_modified, created, createdby) ";
        $sql .= "VALUES ('{$username}', '{$password}', '{$courtesytitle}', '{$forenames}', '{$surname}', '{$jobtitle}', ";
        $sql .= "'{$siteid}', {$address1}, {$address2}, {$city}, {$county}, {$country}, {$postcode}, '{$email}', ";
        $sql .= "{$phone}, {$mobile}, {$fax}, {$department}, {$notes}, '{$dataprotection_email}', ";
        $sql .= "'{$dataprotection_phone}', '{$dataprotection_address}', '{$now}', '{$now}', now(), '{$sit[2]}')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        if (!$result)
        {
            if ($mode == 'internal')
            {
                html_redirect("contact_new.php", FALSE);
            }
            else
            {
                html_redirect("newcontact.php", FALSE);
            }
        }
        // concatenate username with insert id to make unique
        $newid = mysqli_insert_id($db);
        $username = $username . $newid;
        $sql = "UPDATE `{$dbContacts}` SET username='{$username}' WHERE id='{$newid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        if (!$result)
        {
            if ($mode == 'internal')
            {
                html_redirect("contact_new.php", FALSE);
            }
            else
            {
                html_redirect("newcontact.php", FALSE);
            }
        }
        else
        {
            plugin_do('contact_new_saved');
            clear_form_data('new_contact');
            clear_form_errors('new_contact');
            $sql = "SELECT username, password FROM `{$dbContacts}` WHERE id={$newid}";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            else
            {
                if ($CONFIG['portal'] AND $_POST['emaildetails'] == 'on')
                {
                    $emaildetails = 1;
                }
                else
                {
                    $emaildetails = 0;
                }

                if ($returnpage == 'addincident')
                {
                    html_redirect("incident_new.php?action=findcontact&contactid={$newid}");
                    exit;
                }
                elseif ($mode == 'internal')
                {
                    html_redirect("contact_details.php?id={$newid}");
                    exit;
                }
                else
                {
                    html_redirect("contactdetails.php?id={$newid}");
                    exit;
                }
            }
        }

    }
    else
    {
        if ($mode == 'internal')
        {
            html_redirect('contact_new.php', FALSE);
        }
        else
        {
            html_redirect('newcontact.php', FALSE);
        }
    }
}


/**
 * Return an array of contracts which the contact is an admin contact for
 * @author Kieran Hogg
 * @param int $contactid - ID of the contract
 * @param int $siteid - The ID of the site
 * @return array of contract ID's for which the given contactid is an admin contact, NULL if none
 */
function admin_contact_contracts($contactid, $siteid)
{
    global $db;
    $sql = "SELECT DISTINCT m.id ";
    $sql .= "FROM `{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE m.admincontact={$contactid} ";
    $sql .= "AND m.site={$siteid} ";

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if ($result)
    {
        while ($row = mysqli_fetch_object($result))
        {
            $contractsarray[] = $row->id;
        }
    }

    return $contractsarray;
}


/**
 * Return an array of contracts which the contact is an named contact for
 * @author Kieran Hogg
 * @param int $contactid - ID of the Contact
 * @param int $siteid - ID of the site
 * @param int $checkvisible - 
 * @return array of supported contracts, NULL if none
 */
function contact_contracts($contactid, $siteid, $checkvisible = TRUE)
{
    global $db;
    $sql = "SELECT DISTINCT m.id AS id
            FROM `{$GLOBALS['dbMaintenance']}` AS m,
            `{$GLOBALS['dbContacts']}` AS c,
            `{$GLOBALS['dbSupportContacts']}` AS sc
            WHERE m.site={$siteid}
            AND sc.maintenanceid=m.id
            AND sc.contactid=c.id 
            AND c.id = {$contactid} ";
    if ($checkvisible)
    {
        $sql .= "AND m.var_incident_visible_contacts = 'yes'";
    }

    if ($result = mysqli_query($db, $sql))
    {
        while ($row = mysqli_fetch_object($result))
        {
            $contractsarray[] = $row->id;
        }
    }
    return $contractsarray;
}



?>