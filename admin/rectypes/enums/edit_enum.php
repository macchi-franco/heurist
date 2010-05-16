<?php

define('SAVE_URI', 'disabled');

require_once('../php/modules/cred.php');
require_once('t1000.php');

if (! is_logged_in()  ||  ! is_admin()  ||  HEURIST_INSTANCE != "") {
	header('Location: ' . BASE_PATH . 'php/login.php');
	return;
}

mysql_connection_db_overwrite("`heurist-common`");

$delete_bdl_id = intval(@$_REQUEST['delete_bdl_field']);
if ($delete_bdl_id) {
	mysql_query('delete from rec_detail_lookups where rdl_id = ' . $delete_bdl_id);
	header('Location: ' . BASE_PATH . 'legacy/edit_enum.php?rdt_id=' . $_REQUEST['rdt_id']);
	return;
}

$update_bdl_id = intval(@$_REQUEST['rdl_id']);
if ($update_bdl_id) {
	$set_commands = 'set' . (@$_REQUEST['rdl_value'] ? ' rdl_value = "'. $_REQUEST['rdl_value'].'"' : '');
	$set_commands .= (@$_REQUEST[ 'rdl_description'] ? ($set_commands?',':'').' rdl_description = '. $_REQUEST['rdl_description'] : '');
	$set_commands .= (@$_REQUEST[ 'bd_rdl_ont_id_'.@$_REQUEST['rdl_id']] ? ($set_commands?',':'').' rdl_ont_id = '. $_REQUEST[ 'bd_rdl_ont_id_'.@$_REQUEST['rdl_id']] : '');

	mysql_query('update rec_detail_lookups '. $set_commands. ' where rdl_id = ' . $update_bdl_id);
	header('Location: ' . BASE_PATH . 'legacy/edit_enum.php?rdt_id=' . @$_REQUEST['rdt_id'].'&updating='.$set_commands );
	return;
}

//define('T1000_DEBUG', 1);

$_REQUEST['_bdl_search'] = 1;
define('bdl-RESULTS_PER_PAGE', 100000);

$template = file_get_contents('templates/edit_enum.html');
$lexer = new Lexer($template);

$body = new BodyScope($lexer);
$body->global_vars['rdt_id'] = @$_REQUEST['rdt_id']? $_REQUEST['rdt_id'] : 0;

$body->verify();
if (@$_REQUEST['_new_bdl_submit'] ) {
	$body->input_check();
	if ($body->satisfied) $body->execute();
}
$body->render();

?>
