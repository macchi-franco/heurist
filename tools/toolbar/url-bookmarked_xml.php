<?php
//heurist toolbar call this with the url of the current load page to check if it has been bookmarked.
//this php code returns the found record (with the highest number of bookmarks) and/or bookmark (of the current user) ids in an xml structure
//  <ids>
//     	<HEURIST_url_bib_id value=recIdFound_or_null/>
//		<HEURIST_url_bkmk_id value=bkmkIdFound_or_null/>
//	</ids>

define("SAVE_URI", "disabled");

// using ob_gzhandler makes this stuff up on IE6-
ini_set("zlib.output_compression_level", 5);
//ob_start('ob_gzhandler');


require_once(dirname(__FILE__)."/../../common/connect/cred.php");
require_once(dirname(__FILE__)."/../../common/connect/db.php");

mysql_connection_db_select(DATABASE);

header("Content-type: text/xml");

echo "<?xml version=\"1.0\"?>";
?>

<ids>

<?php
if (! @$_REQUEST["url"]) return;

$url = $_REQUEST["url"];
//error_log(" url : ". $url);
if (substr($url, -1) == "/") $url = substr($url, 0, strlen($url)-1);

$res = mysql_query("select rec_ID
					  from Records
				 left join usrBookmarks on bkm_recID = rec_ID
					 where (rec_URL='".addslashes($url)."' or rec_URL='".addslashes($url)."/')
				  group by bkm_ID
				  order by count(bkm_ID), rec_ID
					 limit 1");
if ($row = mysql_fetch_assoc($res)) {
	print "<HEURIST_url_bib_id value=\"".$row["rec_ID"]."\"/>";
} else {
	print "<HEURIST_url_bib_id value=\"null\"/>";
}

$res = mysql_query("select bkm_ID
					  from usrBookmarks
				 left join Records on rec_ID = bkm_recID
					 where bkm_UGrpID=".get_user_id()."
					   and (rec_URL='".addslashes($url)."' or rec_URL='".addslashes($url)."/')
					 limit 1");
if ($row = mysql_fetch_assoc($res)) {
	print "<HEURIST_url_bkmk_id value=\"".$row["bkm_ID"]."\"/>";
} else {
	print "<HEURIST_url_bkmk_id value=\"null\"/>";
}
?>

</ids>

<?php
ob_end_flush();
?>
