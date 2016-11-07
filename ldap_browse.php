<?php
// ldap_browse.php
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
$permission = PERM_ADMIN; // Administrate
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

include (APPLICATION_INCPATH . 'minimal_header.inc.php');

echo "<h2>{$strLDAP}</h2>";

// Only allow certain characters in these fields to avoid JS injection (Mantis 1655)
$base = cleanvar(preg_replace("[^A-Za-z0-9,=]", "", $_REQUEST['field']));
$field = cleanvar(preg_replace("[^A-Za-z0-9]", "", $_REQUEST['field']));

$ldap_type = cleanvar($_REQUEST['ldap_type']);
$ldap_host = cleanvar($_REQUEST['ldap_host']);
$ldap_port = clean_int($_REQUEST['ldap_port']);
$ldap_protocol = clean_int($_REQUEST['ldap_protocol']);
$ldap_security = cleanvar($_REQUEST['ldap_security']);
$ldap_bind_user = cleanvar($_REQUEST['ldap_bind_user']);
$ldap_bind_pass = cleanvar($_REQUEST['ldap_bind_pass']);

echo "<div id='ldap_browse_contents' />";

?>
    <script type='text/javascript'>
    //<![CDATA[
        ldap_browse_select_container('<?php echo $base ?>', '<?php echo $field ?>');
    //]]>
    </script>
<?php

include (APPLICATION_INCPATH . 'minimal_footer.inc.php');

?>