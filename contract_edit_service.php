<?php
// billing/edit_service.php - Allows balances to be edited or transfered
// TODO description
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional! 24May2009
// Author:  Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission =  PERM_SERVICE_EDIT;
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
require_once (APPLICATION_LIBPATH . 'billing.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH.'auth.inc.php');


$mode = clean_fixed_list($_REQUEST['mode'], array('showform','editservice','doupdate','edit','transfer'));
$amount = clean_float($_REQUEST['amount']);
$contractid = clean_int($_REQUEST['contractid']);
$sourceservice = clean_int($_REQUEST['sourceservice']);
$destinationservice = clean_int($_REQUEST['destinationservice']);
$reason = clean_dbstring($_REQUEST['reason']);
$serviceid = clean_int($_REQUEST['serviceid']);

switch ($mode)
{
    case 'editservice':
        if (user_permission($sit[2], $permission) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=$permission");
            exit;
        }
        else
        {
            $sql = "SELECT * FROM `{$dbService}` WHERE serviceid = {$serviceid}";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);
            $title = ("$strContract - $strEditService");
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');

            if (mysqli_num_rows($result) != 1)
            {
                echo "<h2>".sprintf($strNoServiceWithIDXFound, $serviceid)."</h2>";
            }
            else
            {
                $obj = mysqli_fetch_object($result);
                $timed = is_contract_timed($contractid);

                echo show_form_errors('edit_service');
                clear_form_errors('edit_service');

                echo "<h2>{$strEditService}</h2>";

                echo "<form id='serviceform' name='serviceform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_submit(\"{$strAreYouSureMakeTheseChanges}\");'>";
                echo "<table class='maintable vertical'>\n";
                if ($timed) echo "<thead>\n";
                echo "<tr><th>{$strStartDate}</th>";
                echo "<td><input class='required' type='text' name='startdate' id='startdate' size='10' ";
                if ($_SESSION['formdata']['edit_service']['startdate'] != '')
                {
                    echo "value='{$_SESSION['formdata']['edit_service']['startdate']}' />";
                }
                else
                {
                    echo "value='{$obj->startdate}' /> ";
                }
                echo date_picker('serviceform.startdate');
                echo " <span class='required'>{$strRequired}</span></td></tr>";

                echo "<tr><th>{$strEndDate}</th>";
                echo "<td><input class='required' type='text' name='enddate' id='enddate' size='10' ";
                if ($_SESSION['formdata']['edit_service']['enddate'] != '')
                {
                    echo "value='{$_SESSION['formdata']['edit_service']['enddate']}' />";
                }
                else
                {
                    echo "value='{$obj->enddate}' /> ";
                }
                echo date_picker('serviceform.enddate');
                echo " <span class='required'>{$strRequired}</span></td></tr>\n";

                echo "<tr><th>{$strNotes}</th><td>";
                echo "<textarea rows='5' cols='20' name='notes'>{$obj->notes}</textarea></td></tr>";

                echo "<tr><th>{$strBilling}</th>";
                if ($timed)
                {
                    if ($obj->balance == $obj->creditamount)
                    {
                        echo "<td>";
                        echo "<input type='hidden' name='editbilling' id='editbilling' value='true' />";
                        echo "<input type='hidden' name='originalcredit' id='originalcredit' value='{$obj->creditamount}' />";
                        
                        $billtype = get_billable_object_from_contract_id($contractid);
                        if ($billtype instanceof Billable)
                        {
                            echo $billtype->display_name();
                        }
                        
                        echo "</td></tr>\n";
                        echo "</thead>\n";
                        echo "<tbody id='billingsection'>\n";

                        echo "<tr><th>{$strCreditAmount}</th>\n";
                        echo "<td>{$CONFIG['currency_symbol']} ";
                        echo "<input class='required' type='text' name='amount' id='amount' size='5' ";
                        if ($_SESSION['formdata']['edit_service']['amount'] != '')
                        {
                            echo "value='{$_SESSION['formdata']['edit_service']['amount']}' />";
                        }
                        else
                        {
                            echo "value='{$obj->creditamount}' />";
                        }
                        echo " <span class='required'>{$strRequired}</span></td></tr>";

                        echo "<tr id='unitratesection'><th>{$strUnitRate}</th>\n";
                        echo "<td>{$CONFIG['currency_symbol']} ";
                        echo "<input class='required' type='text' name='unitrate' id='unitrate' size='5' ";
                        if ($_SESSION['formdata']['edit_service']['unitrate'] != '')
                        {
                            echo "value='{$_SESSION['formdata']['edit_service']['unitrate']}' />";
                        }
                        else
                        {
                            echo "value='{$obj->rate}' />";
                        }
                        echo " <span class='required'>{$strRequired}</span></td></tr>";

                        $fochecked = '';
                        if ($obj->foc == 'yes') $fochecked = "checked='checked'";

                        echo "<tr>";
                        echo "<th>{$strFreeOfCharge}</th>";
                        echo "<td><input type='checkbox' id='foc' name='foc' value='yes'  {$fochecked} /> {$strAboveMustBeCompletedToAllowDeductions}</td>";
                        echo "</tr>";

                        echo "</tbody>";
                    }
                    else
                    {
                        echo "</tr></thead>";
                        echo "<input type='hidden' name='editbilling' id='editbilling' value='false' />";
                        echo "<tbody>\n";
                        echo "<tr><th colspan='2'>{$strUnableToChangeServiceAsUsed}</th></tr>\n";
                        echo "</tbody>\n";
                    }
                }
                else
                {
                    echo "<td><label>";
                    echo "<input type='radio' name='billtype' value='' checked='checked' disabled='disabled' /> ";
                    echo "{$strNone}</label>";
                    echo help_link('NewServiceNoBilling');
                    echo "</td></tr>";
                }

                echo "</table>\n\n";
                echo "<input type='hidden' name='contractid' value='{$contractid}' />";
                echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
                echo "<input name='submit' type='submit' value=\"{$strSave}\" /></p>";
                echo "<input type='hidden' name='serviceid' id='serviceid' value='{$serviceid}' />";
                echo "<input type='hidden' name='mode' id='mode' value='doupdate' />";
                echo "</form>\n";

                echo "<p class='return'><a href='contract_details.php?id={$contractid}'>{$strReturnWithoutSaving}</a></p>";
            }
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        clear_form_data('edit_service');
        break;
    case 'doupdate':
        if (user_permission($sit[2], PERM_SERVICE_EDIT) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=" . PERM_SERVICE_EDIT);
            exit;
        }

        $amount =  clean_float($_POST['amount']);
        if ($amount == '') $amount = 0;
        $unitrate =  clean_float($_POST['unitrate']);

        $billtype = clean_dbstring($_REQUEST['billtype']);

        $startdate = strtotime($_REQUEST['startdate']);
        if ($startdate > 0) $startdate = date('Y-m-d', $startdate);
        else $startdate = date('Y-m-d', $now);
        $enddate = strtotime($_REQUEST['enddate']);
        if ($enddate > 0) $enddate = date('Y-m-d', $enddate);
        else $enddate = date('Y-m-d', $now);
        
        $_SESSION['formdata']['edit_service'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE,
                                                 array("@"), array("'" => '"'));

        $errors = 0;

        if (isset($billtype) AND empty($unitrate))
        {
            $errors++;
            $_SESSION['formerrors']['new_service']['unitrate'] = sprintf($strFieldMustNotBeBlank, $strUnitRate);
        }
    
        if (isset($billtype) AND $amount == 0)
        {
            $errors++;
            $_SESSION['formerrors']['new_service']['amount'] = sprintf($strFieldMustNotBeBlank, $strCreditAmount);
        }

        if ($startdate > $enddate)
        {
            $errors++;
            $_SESSION['formerrors']['edit_service']['amount'] = $strErrorStartDateCannotBeAfterEndDate;
        }

        if ($errors === 0)
        {
            $originalcredit = clean_float($_REQUEST['originalcredit']);

            $notes = clean_dbstring($_REQUEST['notes']);

            $editbilling = clean_fixed_list($_REQUEST['editbilling'], array('','true','false'));

            $foc = clean_fixed_list($_REQUEST['foc'], array('no','yes'));

            if ($editbilling == "true")
            {
                $updateBillingSQL = ", creditamount = '{$amount}', balance = '{$amount}', rate = '{$unitrate}' ";
            }

            if ($amount != $originalcredit)
            {
                $adjust = $amount - $originalcredit;

                update_contract_balance($contractid, "Credit adjusted to", $adjust, $serviceid);
            }

            $sql = "UPDATE `{$dbService}` SET startdate = '{$startdate}', enddate = '{$enddate}' {$updateBillingSQL}";
            $sql .= ", notes = '{$notes}', foc = '{$foc}' WHERE serviceid = {$serviceid}";

            mysqli_query($db, $sql);
            if (mysqli_error($db))
            {
                trigger_error(mysqli_error($db), E_USER_ERROR);
                $errors++;
            }

            $sql = "SELECT expirydate FROM `{$dbMaintenance}` WHERE id = {$contractid}";

            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

            if (mysqli_num_rows($result) > 0)
            {
                $obj = mysqli_fetch_object($result);
                if ($obj->expirydate < strtotime($enddate))
                {
                    $update = "UPDATE `{$dbMaintenance}` ";
                    $update .= "SET expirydate = '".strtotime($enddate)."' ";
                    $update .= "WHERE id = {$contractid}";
                    mysqli_query($db, $update);
                    if (mysqli_error($db))
                    {
                        trigger_error(mysqli_error($db), E_USER_ERROR);
                        $errors++;
                    }

                    if (mysqli_affected_rows($db) < 1)
                    {
                        trigger_error("Expiry of contract update failed", E_USER_ERROR);
                        $errors++;
                    }
                }
            }
        }

        if ($errors == 0)
        {
            html_redirect("contract_details.php?id={$contractid}", TRUE);
        }
        else
        {
            html_redirect("contract_edit_service.php?mode=editservice&amp;serviceid={$serviceid}&amp;contractid={$contractid}", FALSE);
        }
        break;
    case 'showform':
        // Will be passed a $sourceservice to modify
        if (user_permission($sit[2], PERM_SERVICE_BALANCE_EDIT) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=" . PERM_SERVICE_BALANCE_EDIT);
            exit;
        }
        else
        {
            $title = ("$strContract - $strEditBalance");
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo "<h2>{$strEditBalance}</h2>";
            echo show_form_errors('edit_service');
            clear_form_errors('edit_service');

            echo "<form name='serviceform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_submit(\"{$strAreYouSureMakeTheseChanges}\");'>";

            echo "<table class='maintable vertical'>";
            echo "<tr><th>{$strServiceID}</th><td>{$sourceservice}</td></tr>";
            echo "<tr><th>{$strAction}</th><td>";
            echo "<label><input type='radio' name='mode' id='edit' value='edit' checked='checked' onclick=\"$('transfersection').hide(); $('transfersectionbtn').hide(); $('editsection').show(); \" /> {$strEdit}</label> ";

            // Only allow transfers on the same contractid
            $sql = "SELECT * FROM `{$dbService}` WHERE contractid = '{$contractid}' AND serviceid != {$sourceservice}";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

            if (mysqli_numrows($result) > 0)
            {

                echo "<label><input type='radio' name='mode' id='transfer' value='transfer' onclick=\"$('transfersection').show(); $('transfersectionbtn').show(); $('editsection').hide(); \" /> {$strTransfer} </label>";
                echo "</td></tr>";
                echo "<tbody  style='display:none' id='transfersection' >";
                echo "<tr><td colspan='2'>";
                if (get_service_balance($sourceservice) >= 0) echo $strTransferExamplePositiveService;
                else $strTransferExampleNegativeService;
                echo "</td></tr><tr><th>{$strDestinationService}</th>";
                echo "<td>";

                echo "<select name='destinationservice'>\n";

                while ($obj = mysqli_fetch_object($result))
                {
                    echo "<option value='{$obj->serviceid}'>{$obj->serviceid} - {$obj->enddate} {$CONFIG['currency_symbol']}{$obj->balance}</option>\n";
                }

                echo "</select>\n";
                echo "</td></tr></tbody>\n";
            }
            else
            {
                echo "</td></tr>";
            }

            echo "<tr><th>{$strAmountToEditBy}</th><td>{$CONFIG['currency_symbol']} <input type='text' class='required' name='amount' id='amount' size='5' /> <span class='required'>{$strRequired}</span></td></tr>";
            echo "<tr><th>{$strReason}</th><td><input type='text' name='reason' id='reason' size='60' maxlength='255' /></td></tr>";

            echo "</table>";
            echo "<p class='formbuttons'><input type='submit' style='display:none'  name='runreport' id='transfersectionbtn' value='{$strTransfer}' />";
            echo "<input type='submit' name='runreport' id='editsection' value='{$strEdit}' /></p>";

            echo "<input type='hidden' name='sourceservice' value='{$sourceservice}' />";
            echo "<input type='hidden' name='contractid' value='{$contractid}' />";

            echo "</form>";
            echo "<p class='return'><a href='contract_details.php?id={$contractid}'>{$strReturnWithoutSaving}</a></p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
    case 'edit':
        $errors = 0;
        if (user_permission($sit[2], PERM_SERVICE_BALANCE_EDIT) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=" . PERM_SERVICE_BALANCE_EDIT);
            exit;
        }
        else
        {
            if ($amount == '')
            {
                $errors++;
                $_SESSION['formerrors']['edit_service']['amount'] = sprintf($strFieldMustNotBeBlank, $strAmountToEditBy);
            }
            if ($errors == 0)
            {
                $status = update_contract_balance($contractid, $reason, $amount, $sourceservice);
            }
            else
            {
                html_redirect("{$_SERVER['PHP_SELF']}?mode=showform&sourceservice={$sourceservice}&contractid={$contractid}", FALSE);
                exit;
            }

            if ($status)
            {
                html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", TRUE, $strSuccessfullyUpdated);
                exit;
            }
            else
            {
                html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", FALSE, $strUpdateFailed);
                exit;
            }
        }
        break;
    case 'transfer':
        if (user_permission($sit[2], PERM_SERVICE_BALANCE_EDIT) == FALSE)
        {
            header("Location: {$CONFIG['application_webpath']}noaccess.php?id=" . PERM_SERVICE_BALANCE_EDIT);
            exit;
        }
        else
        {
            if ($amount == '')
            {
                $errors++;
                $_SESSION['formerrors']['edit_service']['amount'] = sprintf($strFieldMustNotBeBlank, $strAmountToEditBy);
            }
            if ($errors == 0)
            {
                $status = update_contract_balance($contractid, $reason, ($amount * -1), $sourceservice);
            }
            else
            {
                html_redirect("{$_SERVER['PHP_SELF']}?mode=showform&sourceservice={$sourceservice}&contractid={$contractid}", FALSE);
                exit;
            }

            if ($status)
            {
                $status = update_contract_balance($contractid, $reason, $amount, $destinationservice);
                if ($status) html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", TRUE);
                else html_redirect("{$CONFIG['application_webpath']}contract_details.php?id={$contractid}", FALSE);
                exit;
            }
            html_redirect('main.php', FALSE, $strFailed);
            exit;
        }
        break;

}

?>