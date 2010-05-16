<?php
/* Login and credential management */

if (@$_REQUEST["instance"]) {
	define("HEURIST_INSTANCE", $_REQUEST["instance"]);
	define("HOST", $_SERVER["HTTP_HOST"]);
}

require_once('heurist-instances.php');


if (! defined('COOKIE_VERSION'))
	define('COOKIE_VERSION', 1);		// increment to force re-login when required

if (@$_COOKIE['heurist-sessionid']) {
	session_id($_COOKIE['heurist-sessionid']);
} else {
	session_id(sha1(rand()));
	setcookie('heurist-sessionid', session_id(), 0, '/', HOST_BASE);
}

session_cache_limiter('none');
session_start();

if (_is_logged_in()) {
	if ((! defined('SAVE_URI'))  ||  strtoupper(SAVE_URI) != 'DISABLED') {
		if (defined('HEURIST_INSTANCE_PREFIX')) {
			$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['last_uri'] = $_SERVER['REQUEST_URI'];
		}
	}
	// update cookie expiry time
	if (@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['keepalive']) {
		$rv = setcookie('heurist-sessionid', session_id(), time() + 7*24*60*60, '/', HOST_BASE);
	}
}
session_write_close();

if (! defined('BASE_PATH')  &&  defined('HOST')) {
	define('BASE_PATH', 'http://'.HOST.'/heurist-test/');
}


function is_cookie_current_version() {
	return (@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['cookie_version'] == COOKIE_VERSION);
}

function get_roles() {
	if (@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_access'])
		return $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_access'];
	else
		return NULL;
}


function _is_logged_in() {
	return (!!@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_name']  &&
			(!defined('HEURIST_USER_GROUP_ID')  ||  @$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_access'][HEURIST_USER_GROUP_ID])  &&
			is_cookie_current_version());
}

if (!_is_logged_in()  &&  defined("BYPASS_LOGIN")) {
	// bypass all security!
	function get_user_id() { return 0; }
	function get_user_name() { return ''; }
	function get_user_username() { return ''; }
	function get_group_ids() { return (defined("HEURIST_USER_GROUP_ID") ? array(HEURIST_USER_GROUP_ID) : array()); }
	function is_admin() { return false; }
	function is_logged_in() { return true; }
}
else {
	function is_logged_in() {
		return _is_logged_in();
	}

	function is_admin() {
		if (defined('HEURIST_ADMIN_GROUP_ID'))
			return is_logged_in()  &&  @$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_access'][HEURIST_ADMIN_GROUP_ID] == 'admin';
		else if (defined('APP_OWNER')  &&  APP_OWNER)
			return is_logged_in()  &&  @$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_name'] == APP_OWNER;
	}

	function get_user_id() {
		if (@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_id']) return $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_id']; else return -1;
	}

	function get_group_ids() {
		if (@$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']["user_access"]) {
			return array_keys($_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']["user_access"]);
		}
		else {
			return array();
		}
	}

	function get_user_name() { return @$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_realname']; }
	function get_user_username() { return @$_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['user_name']; }
}

function get_user_access() {
	if (is_admin()) return 'admin';
	if (is_logged_in()) return 'user';
	return 'not-logged-in';
}


function get_access_levels() {
	return array(
		'modeluser' => is_modeluser() ? 1 : 0,
		'admin' => is_admin() ? 1 : 0,
		'user'=> is_logged_in() ? 1 : 0
	);
}

function is_modeluser() { return (get_user_username() == 'model_user'); }



function jump_sessions() {
	/* Some pages store a LOT of data in the session variables;
	 * this cripples the server whenever a page with session data is loaded, because PHP insists on parsing the session file.
	 * So,
         *        "Any problem in computer science can be solved with another layer of indirection.
	 *         But that usually will create another problem." 
         *                                    --David Wheeler
	 * 
	 * In the main session data, we store the ID of another session, which we use as persistent scratch space for
	 * (especially) the import functionality.  Call jump_sessions() to load this alternative session.
	 * Note that we haven't yet found out what the "another problem" is in this case.
	 */

	// variables to copy from the regular session to the alt
	$copy_vars = array('user_name', 'user_access', 'user_realname', 'user_id');

	$alt_sessionid = $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['alt-sessionid'];
	if (! $alt_sessionid) {
		session_start();
		$alt_sessionid = $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist']['alt-sessionid'] = sha1('import:' . rand());
		session_write_close();
	}

	$tmp = array();
	foreach ($copy_vars as $varname) $tmp[$varname] = $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist'][$varname];

	session_id($alt_sessionid);
	session_start();

	foreach ($copy_vars as $varname) $_SESSION[HEURIST_INSTANCE_PREFIX.'heurist'][$varname] = $tmp[$varname];
}

?>
