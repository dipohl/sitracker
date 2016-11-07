<?php
// edit_contact.php - Form for editing a contact
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  31Oct05

require ('core.php');
$permission = PERM_CONTACT_EDIT; // Edit Contacts
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strEditContact;

// External variables
$contact = clean_int($_REQUEST['contact']);
$action = clean_fixed_list($_REQUEST['action'],array('','edit','showform','update'));


// User has access
if (empty($action) OR $action == "showform" OR empty($contact))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    // Show select contact form
    echo "<h2>".icon('contact', 32)." {$strEditContact}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=edit' method='post'>";
    echo "<table class='maintable'>";
    echo "<tr><th>{$strContact}:</th><td>".contact_site_drop_down("contact", 0)."</td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'><input name='submit' type='submit' value='{$strContinue}' /></p>";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "edit" && isset($contact))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    $sql = "SELECT * FROM `{$dbContacts}` WHERE id='{$contact}' ";
    $contactresult = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    while ($contactobj = mysqli_fetch_object($contactresult))
    {
        echo show_form_errors('edit_contact');
        clear_form_errors('edit_contact');
        echo "<h2>".icon('contact', 32)." ";
        echo "{$strEditContact}: {$contact}</h2>";
        plugin_do('contact_edit');
        echo "<form name='contactform' action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit='return confirm_action(\"{$strAreYouSureMakeTheseChanges}\");'>";
        echo "<table class='maintable vertical'>";
        echo "<tr><th>{$strName}</th>\n";
        echo "<td>";
        echo "\n<table><tr><td class='tabletitle'>{$strTitle}<br />";
        echo "<input maxlength='50' name='courtesytitle' title=\"";
        echo "{$strCourtesyTitle}\" size='7' value='{$contactobj->courtesytitle}'/></td>\n";
        echo "<td class='tabletitle'>{$strForenames}<br />";
        echo "<input class='required' maxlength='100' name='forenames' value='".htmlspecialchars($contactobj->forenames, ENT_QUOTES)."' /></td>\n";
        echo "<td class='tabletitle'>{$strSurname}<br />";
        echo "<input class='required' maxlength='100' name='surname' ";
        echo "size='20' title=\"{$strSurname}\" value='".htmlspecialchars($contactobj->surname, ENT_QUOTES)."' /> <span class='required'>{$strRequired}</span></td></tr>\n";
        echo "</table>\n</td></tr>\n";
        echo "<tr><th>{$strTags}:</th><td><textarea rows='2' cols='60' name='tags'>";
        echo list_tags($contact, TAG_CONTACT, false)."</textarea></td></tr>\n";
        echo "<tr><th>{$strJobTitle}:</th><td>";
        echo "<input maxlength='255' name='jobtitle' size='40' value='{$contactobj->jobtitle}' />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strSite}: </th><td>";
        echo site_drop_down('siteid', $contactobj->siteid, TRUE)."<span class='required'>{$strRequired}</span></td></tr>\n";
        echo "<tr><th>{$strDepartment}:</th><td>";
        echo "<input maxlength='100' name='department' size='40' value='{$contactobj->department}' />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strEmail}:</th><td>";
        echo "<input class='required' maxlength='100' name='email' size='40' value='".htmlspecialchars($contactobj->email, ENT_QUOTES)."' /> ";
        echo "<span class='required'>{$strRequired}</span>";
        echo "<label>";
        echo html_checkbox('dataprotection_email', $contactobj->dataprotection_email);
        echo "{$strEmail} {$strDataProtection}</label>".help_link("EmailDataProtection");
        echo "</td></tr>\n";
        echo "<tr><th>{$strTelephone}:</th><td>";
        echo "<input maxlength='50' name='phone' size='40' value='{$contactobj->phone}' />";
        echo "<label>";
        echo html_checkbox('dataprotection_phone', $contactobj->dataprotection_phone);
        echo "{$strTelephone} {$strDataProtection}</label>".help_link("TelephoneDataProtection");
        echo "</td></tr>\n";
        echo "<tr><th>{$strMobile}:</th><td>";
        echo "<input maxlength='50' name='mobile' size='40' value='{$contactobj->mobile}' /></td></tr>\n";
        echo "<tr><th>{$strFax}:</th><td>";
        echo "<input maxlength='50' name='fax' size='40' value='{$contactobj->fax}' /></td></tr>\n";
        echo "<tr><th>{$strActive}:</th><td><input type='checkbox' name='active' ";
        if ($contactobj->active == 'true') echo "checked='checked'";
        echo " value='true' /></td></tr> <tr><th></th><td>";
        echo "<input type='checkbox' id='usesiteaddress' name='usesiteaddress' value='yes' onclick='togglecontactaddress();' ";
        if ($contactobj->address1 !='')
        {
            echo "checked='checked'";
            $extraattributes = '';
        }
        else
        {
          $extraattributes = "disabled='disabled' ";
        }
        echo "/> ";
        echo "{$strSpecifyAddress}</td></tr>\n";
        echo "<tr><th>{$strAddress}:</th><td><label>";
        echo html_checkbox('dataprotection_address', $contactobj->dataprotection_address);
        echo " {$strAddress} {$strDataProtection}</label>".help_link("AddressDataProtection")."</td></tr>\n";
        echo "<tr><th>{$strAddress1}:</th><td>";
        echo "<input maxlength='255' id='address1' name='address1' size='40' value='{$contactobj->address1}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strAddress2}:</th><td>";
        echo "<input maxlength='255' id='address2' name='address2' size='40' value='{$contactobj->address2}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strCity}:</th><td>";
        echo "<input maxlength='255' id='city' name='city' size='40' value='{$contactobj->city}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strCounty}:</th><td>";
        echo "<input maxlength='255' id='county' name='county' size='40' value='{$contactobj->county}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strPostcode}:</th><td>";
        echo "<input maxlength='255' id='postcode' name='postcode' size='40' value='{$contactobj->postcode}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strCountry}:</th><td>";
        echo country_drop_down('country', $contactobj->country, $extraattributes);
        echo "</td></tr>\n";
        echo "<tr><th>{$strNotifyContact}:</th><td>";
        echo contact_site_drop_down('notify_contactid', $contactobj->notify_contactid, $contactobj->siteid, $contact, TRUE, TRUE);
        echo "</td></tr>\n";
        echo "<tr><th>{$strNotes}:</th><td>";
        echo "<textarea rows='5' cols='60' name='notes'>{$contactobj->notes}</textarea></td></tr>\n";

        plugin_do('contact_edit_form');
        echo "</table>";

        echo "<input name='contact' type='hidden' value='{$contact}' />";

        echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' />  ";
        echo "<input name='submit' type='submit' value='{$strSave}' /></p>";

        echo "<p class='return'><a href=\"contact_details.php?id={$contact}\">{$strReturnWithoutSaving}</a></p>";
        echo "</form>\n";
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else if ($action == "update")
{
    // External variables
    $contact = clean_int($_POST['contact']);
    $courtesytitle = clean_dbstring($_POST['courtesytitle']);
    $surname = clean_dbstring($_POST['surname']);
    $forenames = clean_dbstring($_POST['forenames']);
    $siteid = clean_int($_POST['siteid']);
    $email = strtolower(clean_dbstring($_POST['email']));
    $phone = convert_string_null_safe(clean_dbstring($_POST['phone']));
    $mobile = convert_string_null_safe(clean_dbstring($_POST['mobile']));
    $fax = convert_string_null_safe(clean_dbstring($_POST['fax']));
    $address1 = convert_string_null_safe(clean_dbstring($_POST['address1']));
    $address2 = convert_string_null_safe(clean_dbstring($_POST['address2']));
    $city = convert_string_null_safe(clean_dbstring($_POST['city']));
    $county = convert_string_null_safe(clean_dbstring($_POST['county']));
    $postcode = convert_string_null_safe(clean_dbstring($_POST['postcode']));
    $country = convert_string_null_safe(clean_dbstring($_POST['country']));
    $notes = convert_string_null_safe(clean_dbstring($_POST['notes']));
    $dataprotection_email = clean_dbstring($_POST['dataprotection_email']);
    $dataprotection_address = clean_dbstring($_POST['dataprotection_address']);
    $dataprotection_phone = clean_dbstring($_POST['dataprotection_phone']);
    $active = clean_dbstring($_POST['active']);
    $jobtitle = clean_dbstring($_POST['jobtitle']);
    $department = clean_dbstring($_POST['department']);
    $notify_contactid = clean_int($_POST['notify_contactid']);
    $tags = clean_dbstring($_POST['tags']);

    // Save changes to database
    $errors = 0;

    // VALIDATION CHECKS */

    // check for blank name
    if ($surname == '')
    {
        $errors++;
        $_SESSION['formerrors']['edit_contact']['surname'] = sprintf($strFieldMustNotBeBlank, $strSurname);
    }
    // check for blank site
    if ($siteid < 1)
    {
        $errors++;
        $_SESSION['formerrors']['edit_contact']['siteid'] = sprintf($strFieldMustNotBeBlank, $strSiteName);
    }
    // check for blank name
    if ($email == '' OR $email == 'none' OR $email == 'n/a')
    {
        $errors++;
        $_SESSION['formerrors']['edit_contact']['email'] = sprintf($strFieldMustNotBeBlank, $strEmail);
    }
    // check for blank contact id
    if ($contact < 1)
    {
        // Something weird has happened, better call technical support
        trigger_error("Contact ID was blank when saving contact record", E_USER_ERROR);
        $errors++;
        $_SESSION['formerrors']['edit_contact']['contact'] = sprintf($strFieldMustNotBeBlank, $strID);
    }
    plugin_do('contact_edit_submitted');

    // edit contact if no errors
    if ($errors == 0)
    {
        // update contact
        if ($dataprotection_email != '') $dataprotection_email = 'Yes';
        else $dataprotection_email = 'No';
        if ($dataprotection_phone  != '') $dataprotection_phone = 'Yes';
        else $dataprotection_phone = 'No';
        if ($dataprotection_address  != '') $dataprotection_address = 'Yes';
        else $dataprotection_address = 'No';

        if ($active == 'true') $activeStr = 'true';
        else $activeStr = 'false';

        $oldActiveStatus = db_read_column("active", $dbContacts, $contact);
        
        /*
            TAGS
        */
        replace_tags(1, $contact, $tags);

        $sql = "UPDATE `{$dbContacts}` SET courtesytitle='{$courtesytitle}', surname='{$surname}', forenames='{$forenames}', siteid='{$siteid}', email='{$email}', phone={$phone}, mobile={$mobile}, fax={$fax}, ";
        $sql .= "address1={$address1}, address2={$address2}, city={$city}, county={$county}, postcode={$postcode}, ";
        $sql .= "country={$country}, dataprotection_email='{$dataprotection_email}', dataprotection_phone='{$dataprotection_phone}', ";
        $sql .= "notes={$notes}, dataprotection_address='{$dataprotection_address}', department='{$department}', jobtitle='{$jobtitle}', ";
        $sql .= "notify_contactid='{$notify_contactid}', ";
        $sql .= "active = '{$activeStr}', ";
        $sql .= "timestamp_modified={$now} WHERE id='{$contact}'";

        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        if (!$result)
        {
            trigger_error("Update of contact failed: {$sql}", E_USER_WARNING);
        }
        else
        {
            plugin_do('contact_edit_saved');

            if ($activeStr == 'false' AND $oldActiveStatus = 'true' AND $CONFIG['remove_from_contracts_on_disable'])
            {
                $sql = "DELETE FROM `{$dbSupportContacts}` WHERE contactid = {$contact}";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
            }
            
            journal(CFG_LOGGING_NORMAL, 'Contact Edited', "Contact {$contact} was edited", CFG_JOURNAL_CONTACTS, $contact);
            html_redirect("contact_details.php?id={$contact}");
            exit;
        }
    }
    else 
    {
        html_redirect("contact_edit.php?action=edit&contact={$contact}", FALSE);
        exit;
    }
}

?>