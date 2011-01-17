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

require_once(dirname(__FILE__).'/../../common/config/heurist-instances.php');
require_once(dirname(__FILE__).'/../../common/connect/db.php');

foreach (get_all_instances() as $instance) {

	mysql_connection_db_overwrite($instance["db"]);

	// delete usrBookmarks that are tied to temporary record entries
	mysql_query('delete usrBookmarks, Records from usrBookmarks, Records where bkm_recID=rec_ID and rec_FlagTemporary');

	// delete temporary record entries
	mysql_query('delete from Records where rec_FlagTemporary');
}

?>
