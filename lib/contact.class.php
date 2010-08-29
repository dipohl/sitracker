<?php
// contact.class.php - The contact class for SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>


// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Represents a contact within SiT! adding the necessary details unique to contacts
 * @author Paul Heaney
 */
class Contact extends Person {
    var $notify_contact;
    var $forenames;
    var $surname;
    var $courtesytitle;
    var $siteid;
    var $department;
    var $address1;
    var $address2;
    var $city;
    var $county;
    var $country;
    var $postcode;
    var $dataprotection_email; ///< boolean
    var $dataprotection_phone; ///< boolean
    var $dataprotection_address; ///< boolean
    var $notes;
    var $active;
    
    var $emailonadd; // Boolean - default sto false
    
    function __construct()
    {
        $this->emailonadd = false;
    }

    function retrieveDetails()
    {
        trigger_error("Contact.retrieveDetails() not yet implemented");
    }

    /**
     * Checks to see if the required fields are present and optionally that the user is unique
     * @author Paul Heaney
     * @param bool $duplicate Whether to check if this contact is a duplicate, defaults to true
     * @return bool true indicates valid contact, false otherwise
     */
    function check_valid($duplicate=true)
    {
        $errors = 0;
        if (empty($this->siteid))
        {
            $errors++;
            trigger_error('Site ID was empty', E_USER_ERROR);
        }

        if (empty($this->surname))
        {
            $errors++;
            trigger_error('Surname was blank', E_USER_ERROR);
        }

        if ($duplicate AND $this->is_duplicate())
        {
            $errors++;
            trigger_error('Record already exists', E_USER_ERROR);
        }

        if ($errors > 0) return false;
        else return true;
    }


    /**
     * Generates an array of insertable values for the contacts data protection settings
     * @author Paul Heaney
     * @return array an array with keys email, phone, address with either Yes or No as values
     */
    function get_dataprotection()
    {
        $dp['email'] = 'Yes';
        $dp['phone'] = 'Yes';
        $dp['address'] = 'Yes';

        if (!$this->dataprotection_email) $dp['email'] = 'No';
        if (!$this->dataprotection_phone) $dp['phone'] = 'No';
        if (!$this->dataprotection_address) $dp['address'] = 'No';

        return $dp;
    }


    /**
     * Performs the addition of the contact to SiT! this performs validity checks before adding the contact
     * @author Paul Heaney
     * @return mixed int for contactID if sucsesful, false otherwise
     */
    function add()
    {
        global $now, $sit;
        $toReturn = false;
        $generate_username = false;

        if ($this->check_valid())
        {
            $dp = $this->get_dataprotection();

            if (empty($this->source)) $this->source = 'sit';

            if (empty($this->username))
            {
                $generate_username = true;
                $this->username = strtolower($this->surname).$now;
            }

            if (empty($this->password)) $this->password = generate_password(16);

            $sql  = "INSERT INTO `{$GLOBALS['dbContacts']}` (username, password, courtesytitle, forenames, surname, jobtitle, ";
            $sql .= "siteid, address1, address2, city, county, country, postcode, email, phone, mobile, fax, ";
            $sql .= "department, notes, dataprotection_email, dataprotection_phone, dataprotection_address, ";
            $sql .= "timestamp_added, timestamp_modified, created, createdby, modified, modifiedby, contact_source) ";
            $sql .= "VALUES ('".cleanvar($this->username)."', MD5('".cleanvar($this->password)."'), '".cleanvar($this->courtesytitle)."', '".cleanvar($this->forenames)."', '".cleanvar($this->surname)."', '".cleanvar($this->jobtitle)."', ";
            $sql .= "'".cleanvar($this->siteid)."', '".cleanvar($this->address1)."', '".cleanvar($this->address2)."', '".cleanvar($this->city)."', '".cleanvar($this->county)."', '".cleanvar($this->country)."', '".cleanvar($this->postcode)."', '".cleanvar($this->email)."', ";
            $sql .= "'".cleanvar($this->phone)."', '".cleanvar($this->mobile)."', '".cleanvar($this->fax)."', '".cleanvar($this->department)."', '".cleanvar($this->notes)."', '".cleanvar($dp['email'])."', ";
            $sql .= "'".cleanvar($dp['phone'])."', '".cleanvar($dp['address'])."', '".cleanvar($now)."', '".cleanvar($now)."', NOW(), '".cleanvar($_SESSION['userid'])."', NOW(), '".cleanvar($_SESSION['userid'])."', '".cleanvar($this->source)."')";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            $newid = mysql_insert_id();

            $toReturn = $newid;

            if ($generate_username)
            {
                // concatenate username with insert id to make unique
                $username = $username . $newid;
                $sql = "UPDATE `{$GLOBALS['dbContacts']}` SET username='{$username}' WHERE id='{$newid}'";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            }
            
            if ($this->emailonadd) $emaildetails = 1;
            else $emaildetails = 0;
            
            trigger('TRIGGER_NEW_CONTACT', array('contactid' => $newid,
                                     'prepassword' => $this->password,
                                     'userid' => $sit[2],
                                     'emaildetails' => $emaildetails
                                     ));
        }

        return $toReturn;
    }


    /**
     * Updates the details of an existing contact within SiT!
     * @author Paul Heaney
     * @return bool. true on sucess, false otherwise
     */
    function edit()
    {
        global $now;

        $toReturn = false;

        if (!empty($this->id) AND is_numeric($this->id))
        {
            $dp = $this->get_dataprotection();

            if (!empty($this->username)) $s[] = "username = '".cleanvar($this->username)."'";
            if (!empty($this->password)) $s[] = "password = MD5('".cleanvar($this->password)."')";
            if (!empty($this->jobtitle)) $s[] = "jobtitle = '".cleanvar($this->jobtitle)."'";
            if (!empty($this->email)) $s[] = "email = '".cleanvar($this->email)."'";
            if (!empty($this->phone)) $s[] = "phone = '".cleanvar($this->phone)."'";
            if (!empty($this->mobile)) $s[] = "mobile = '".cleanvar($this->mobile)."'";
            if (!empty($this->fax)) $s[] = "fax = '".cleanvar($this->fax)."'";
            if (!empty($this->notify_contact)) $s[] = "notify_contactid = ".cleanvar($this->motify_contact)."'";
            if (!empty($this->forenames)) $s[] = "forenames = '".cleanvar($this->forenames)."'";
            if (!empty($this->surname)) $s[] = "surname = '".cleanvar($this->surname)."'";
            if (!empty($this->courtesytitle)) $s[] = "courtesytitle = '".cleanvar($this->courtesytitle)."'";
            if (!empty($this->siteid)) $s[] = "siteid = ".cleanvar($this->siteid)."";
            if (!empty($this->department)) $s[] = "department = '".cleanvar($this->department)."'";
            if (!empty($this->address1)) $s[] = "address1 = '".cleanvar($this->address1)."'";
            if (!empty($this->address2)) $s[] = "address2 = '".cleanvar($this->address2)."'";
            if (!empty($this->city)) $s[] = "city = '".cleanvar($this->city)."'";
            if (!empty($this->county)) $s[] = "county = '".cleanvar($this->county)."'";
            if (!empty($this->country)) $s[] = "country = '".cleanvar($this->country)."'";
            if (!empty($this->postcode)) $s[] = "postcode = '".cleanvar($this->postcode)."'";
            if (!empty($this->dataprotection_email)) $s[] = "dataprotection_email = '".cleanvar($db['email'])."'";
            if (!empty($this->dataprotection_phone)) $s[] = "dataprotection_phone = '".cleanvar($db['phone'])."'";
            if (!empty($this->dataprotection_address)) $s[] = "dataprotection_address = '".cleanvar($db['address'])."'";
            if (!empty($this->notes)) $s[] = "notes = '".cleanvar($this->notes)."'";
            if (!empty($this->source)) $s[] = "contact_source = '".cleanvar($this->source)."'";
            if (!empty($this->active))
            {
                if ($this->active) $s[] = "active = 'true'";
                else $s[] = "active = 'false'";
            }
            $s[] = "modified = NOW()";
            $s[] = "timestamp_modified = {$now}";
            if (!empty($_SESSION['userid']))
            {
                // If LDAP is doing this then we dont have the details
                $s[] = "modifiedby = {$_SESSION['userid']}";
            }

            $sql = "UPDATE `{$GLOBALS['dbContacts']}` SET ".implode(", ", $s)." WHERE id = {$this->id}";
            $result = mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_WARNING);
                $toReturn = false;
            }
            else
            {
                $toReturn = true;
            }
        }
        else
        {
            $toReturn = false;
        }

        return $toReturn;
    }

    /**
     * Disabled this contact in SiT!
     * @author Paul Heaney
     * @return bool True if disabled, false otherwise
     */
    function disable()
    {
        $toReturn = true;
        if (!empty($this->id))
        {
        $sql = "UPDATE `{$GLOBALS['dbContacts']}` SET active = 'false' WHERE id = {$this->id}";

            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_affected_rows() != 1)
            {
                $sql = "SELECT active FROM `{$GLOBALS['dbContacts']}` WHERE id = {$this->id} AND active = 'false'";
                $result = mysql_query($sql);
                if (mysql_num_rows($result) == 0)
                {
                    trigger_error("Failed to disable contact {$this->username}", E_USER_WARNING);
                    $toReturn = false;
                }
                else
                {
                    // The contact was already disabled
                    $toReturn = true;
                }
            }
            else
            {
                $toReturn = true;
            }
        }

        return $toReturn;
    }

    /**
     * Checks to see if the contact is a duplicate within SiT!
     * @author Paul Heaney
     * @return bool. true for duplicate, false otherwise
     */
    function is_duplicate()
    {
        // Check this is not a duplicate
        $sql = "SELECT id FROM `{$GLOBALS['dbContacts']}` WHERE email='{$this->email}' AND LCASE(surname)=LCASE('{$this->surname}') LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_num_rows($result) >= 1) return true;
        else return false;
    }


    function getSOAPArray()
    {
        trigger_error("Contact.getSOAPArray() not yet implemented");
    }
}

?>