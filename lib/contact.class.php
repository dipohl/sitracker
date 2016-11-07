<?php
// contact.class.php - The contact class for SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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

    var $emailonadd; // Boolean - defaults to false
    
    function Contact($id=0)
    {
        global $CONFIG;
        debug_log("Contact({$id})");
        if ($id > 0)
        {
            $this->id = $id;
            $this->retrieveDetails();
        }
        else
        {
            $this->emailonadd = false;
        }
    }

    function retrieveDetails()
    {
        global $CONFIG;
        global $db;
        $sql = "SELECT c.* ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c ";
        $sql .= "WHERE c.id = {$this->id}";
        debug_log("RetrieveDetails " . $sql);
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

        if (mysqli_num_rows($result) == 1)
        {
            $obj = mysqli_fetch_object($result);
            $this->username = $obj->username;
            $this->notify_contact = $obj->notify_contact;
            $this->forenames = $obj->forenames;
            $this->surname = $obj->surname;
            $this->courtesytitle = $obj->courtesytitle;
            $this->siteid = $obj->siteid;
            $this->department = $obj->department;
            $this->address1 = $obj->address1;
            $this->address2 = $obj->address2;
            $this->city = $obj->city;
            $this->county = $obj->county;
            $this->country = $obj->country;
            $this->postcode = $obj->postcode;
            $dpEmail = false;
            if ($obj->dataprotection_email == 'Yes') $dpEmail = true;
            $dpPhone = false;
            if ($obj->dataprotection_phone == 'Yes') $dpPhone = true;
            $dpAddress = false;
            if ($obj->dataprotection_address == 'Yes') $dpAddress = true;
            $this->dataprotection_email = $dbEmail; ///< boolean
            $this->dataprotection_phone = $dpPhone; ///< boolean
            $this->dataprotection_address = $dpAddress; ///< boolean
            $this->notes = $obj->notes;
            $this->active = $obj->active;            
        }
        else
        {
        	$this->id = 0;
        }
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
     * @return mixed int for contactID if successful, false otherwise
     */
    function add()
    {
        global $now, $sit, $db;
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
            $sql .= "VALUES ('".clean_dbstring($this->username)."', MD5('".clean_dbstring($this->password)."'), ".$this->getStringToInsert('courtesytitle').", ".$this->getStringToInsert('forenames').", ".$this->getStringToInsert('surname').", ".$this->getStringToInsert('jobtitle').", ";
            $sql .= "'".clean_int($this->siteid)."'," . $this->getStringToInsert('address1')."," . $this->getStringToInsert('address2')."," . $this->getStringToInsert('city')."," . $this->getStringToInsert('county')."," . $this->getStringToInsert('country')."," . $this->getStringToInsert('postcode')."," . $this->getStringToInsert('email').", ";
            $sql .= clean_dbstring('phone')."," . $this->getStringToInsert('mobile')."," . $this->getStringToInsert('fax')."," . $this->getStringToInsert('department')."," . $this->getStringToInsert('notes').", '".clean_dbstring($dp['email'])."', ";
            $sql .= "'".clean_dbstring($dp['phone'])."', '".clean_dbstring($dp['address'])."', '".clean_int($now)."', '".clean_int($now)."', NOW(), '".clean_int($_SESSION['userid'])."', NOW(), '".clean_int($_SESSION['userid'])."'," . $this->getStringToInsert('source').")";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

            $newid = mysqli_insert_id($db);

            $toReturn = $newid;

            if ($generate_username)
            {
                // concatenate username with insert id to make unique
                $username = $username . $newid;
                $sql = "UPDATE `{$GLOBALS['dbContacts']}` SET username='{$username}' WHERE id='{$newid}'";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
            }

            if ($this->emailonadd) $emaildetails = 1;
            else $emaildetails = 0;

            if (class_exists("TriggerEvent"))
            {
                // When doing JIT the class hasn't been included yet as you don't have a session, not sure if this is quite right
                $t = new TriggerEvent('TRIGGER_NEW_CONTACT', array('contactid' => $newid,
                                     'prepassword' => $this->password,
                                     'userid' => $sit[2],
                                     'emaildetails' => $emaildetails
                                     ));
            }
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
        global $now, $db;

        $toReturn = false;

        if (!empty($this->id) AND is_numeric($this->id))
        {
            $dp = $this->get_dataprotection();

            if (!empty($this->username)) $s[] = "username = '".clean_dbstring($this->username)."'";
            if (!empty($this->password)) $s[] = "password = MD5('".clean_dbstring($this->password)."')";
            /*if (!empty($this->jobtitle))*/ $s[] = "jobtitle = ". $this->getStringToInsert('jobtitle'); //'".clean_dbstring($this->jobtitle)."'";
            if (!empty($this->email)) $s[] = "email = '".clean_dbstring($this->email)."'";
            if (!empty($this->phone)) $s[] = "phone = '".clean_dbstring($this->phone)."'";
            if (!empty($this->mobile)) $s[] = "mobile = '".clean_dbstring($this->mobile)."'";
            if (!empty($this->fax)) $s[] = "fax = '".clean_dbstring($this->fax)."'";
            if (!empty($this->notify_contact)) $s[] = "notify_contactid = ".clean_int($this->motify_contact)."'";
            if (!empty($this->forenames)) $s[] = "forenames = '".clean_dbstring($this->forenames)."'";
            if (!empty($this->surname)) $s[] = "surname = '".clean_dbstring($this->surname)."'";
            if (!empty($this->courtesytitle)) $s[] = "courtesytitle = '".clean_dbstring($this->courtesytitle)."'";
            if (!empty($this->siteid)) $s[] = "siteid = ".clean_int($this->siteid)."";
            if (!empty($this->department)) $s[] = "department = '".clean_dbstring($this->department)."'";
            if (!empty($this->address1)) $s[] = "address1 = '".clean_dbstring($this->address1)."'";
            if (!empty($this->address2)) $s[] = "address2 = '".clean_dbstring($this->address2)."'";
            if (!empty($this->city)) $s[] = "city = '".clean_dbstring($this->city)."'";
            if (!empty($this->county)) $s[] = "county = '".clean_dbstring($this->county)."'";
            if (!empty($this->country)) $s[] = "country = '".clean_dbstring($this->country)."'";
            if (!empty($this->postcode)) $s[] = "postcode = '".clean_dbstring($this->postcode)."'";
            if (!empty($this->dataprotection_email)) $s[] = "dataprotection_email = '".clean_dbstring($dp['email'])."'";
            if (!empty($this->dataprotection_phone)) $s[] = "dataprotection_phone = '".clean_dbstring($dp['phone'])."'";
            if (!empty($this->dataprotection_address)) $s[] = "dataprotection_address = '".clean_dbstring($dp['address'])."'";
            if (!empty($this->notes)) $s[] = "notes = '".clean_dbstring($this->notes)."'";
            if (!empty($this->source)) $s[] = "contact_source = '".clean_dbstring($this->source)."'";
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
            debug_log("Updating contact with SQL " . $sql);
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db))
            {
                trigger_error(mysqli_error($db), E_USER_WARNING);
                $toReturn = false;
            }
            else
            {
                $toReturn = true;
            }
        }
        else
        {
            debug_log("Can't update as no ID passed");
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
        global $db;
        $toReturn = true;
        if (!empty($this->id))
        {
            $sql = "UPDATE `{$GLOBALS['dbContacts']}` SET active = 'false' WHERE id = {$this->id}";

            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
            if (mysqli_affected_rows($db) != 1)
            {
                $sql = "SELECT active FROM `{$GLOBALS['dbContacts']}` WHERE id = {$this->id} AND active = 'false'";
                $result = mysqli_query($db, $sql);
                if (mysqli_num_rows($result) == 0)
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
        global $db;
        // Check this is not a duplicate
        $sql = "SELECT id FROM `{$GLOBALS['dbContacts']}` WHERE email='{$this->email}' AND LCASE(surname)=LCASE('{$this->surname}') LIMIT 1";
        $result = mysqli_query($db, $sql);
        if (mysqli_num_rows($result) >= 1) return true;
        else return false;
    }


    function getSOAPArray()
    {
        trigger_error("Contact.getSOAPArray() not yet implemented");
    }
}

?>