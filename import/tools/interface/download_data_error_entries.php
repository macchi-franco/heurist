<?php

if (! $_REQUEST['import_id']) return;	// no time for funny buggers

// Make sure these are loaded before the session data is loaded, so that the class definitions are in place
require_once('HeuristImport.php');
require_once('HeuristReferImport.php');
require_once('HeuristEndnoteReferImport.php');
require_once('HeuristZoteroImport.php');
require_once('HeuristKMLImport.php');

require_once('../php/modules/db.php');
require_once('../php/modules/cred.php');


jump_sessions();

$session_data = &$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['heurist-import-' . $_REQUEST['import_id']];
if (! $session_data) return;	// print out something about session expiry, even though that's really unlikely

header('Content-type: text/plain');

print $session_data['parser']->outputEntries($session_data['data_error_entries']);

?>
