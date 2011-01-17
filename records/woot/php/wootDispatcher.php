<?php

/**
 * filename, brief description, date of creation, by whom
 * @copyright (C) 2005-2010 University of Sydney Digital Innovation Unit.
 * @link: http://HeuristScholar.org
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Heurist academic knowledge management system
 * @todo
 **/

?>

<?php

$legalMethods = array(
	"loadWoot",
	"saveWoot",
	"searchWoots",
	"fetchChunk"
);

function outputAsRedirect($text) {
	global $baseURL, $rxlen;

	$val = base64_encode($text);

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	if (strlen($val) > 10000 || ($rxlen  &&  strlen($val) > $rxlen)) {
		$token = sprintf("data%08x%08x", rand(), rand());

		session_start();
		$_SESSION[$token] = $text;

		header("Location: " . $baseURL . "common/html/blank.html#token=" . $token);
	}
	else {
		header("Location: " . $baseURL . "common/html/blank.html#data=" . urlencode($val));
	}

	return "";
}

function outputAsScript($text) {
	global $callback;

	preg_match_all('/.{1,1024}(?:[\x0-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF][\x80-\xBF])/', $text, $matches, PREG_PATTERN_ORDER);
	$bits = $matches[0];

	$output = $callback . "(";
	$output .= json_encode($bits[0]);
	for ($i=1; $i < count($bits); ++$i) {
		$output .= "\n+ " . json_encode($bits[$i]);
	}
	$output .= ");\n";

	return $output;
}


$rxlen = intval(@$_REQUEST["rxlen"]);
$callback = @$_REQUEST["cb"];
if ($callback  &&  preg_match('/^cb[0-9]+$/', $callback)) {
	ob_start("outputAsScript");
} else {
	ob_start("outputAsRedirect");
}

$method = @$_REQUEST["method"];
$key = @$_REQUEST["key"];

require_once(dirname(__FILE__)."/../../../common/connect/applyCredentials.php");
require_once(dirname(__FILE__)."/../../../common/php/dbMySqlWrappers.php");
require_once("authorise.php");

if (! ($auth = get_location($key))) {
	print "{\"error\":\"unknown API key\"}";
	return;
}
// error_log(print_r($auth, 1));
$baseURL = HEURIST_URL_BASE;
//$baseURL = $auth["hl_location"];

define_constants($auth["hl_instance"]);

error_log(" woot xss baseURL = ".$baseURL." Heurist base = ".HEURIST_URL_BASE);


if (! @$method  ||  ! in_array($method, $legalMethods)) {
	print "{\"error\":\"unknown method\"}";
	return;
}

define('USING-XSS', 1);

require_once("$method.php");

ob_end_flush();

?>
