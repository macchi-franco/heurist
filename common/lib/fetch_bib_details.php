<?php

require_once('../php/modules/cred.php');
require_once('../php/modules/db.php');

mysql_connection_db_select(DATABASE);

$ref_detail_types = mysql__select_array('rec_detail_types', 'rdt_id', 'rdt_type="resource"');

function ref_detail_types () {
	global $ref_detail_types;
	return $ref_detail_types;
}

function parent_detail_types () {
	return array('217', '225', '226', '227', '228', '229', '236', '237', '238', '241', '242');
}

function fetch_bib_details ($rec_id, $recurse=false, $visited=array()) {

	array_push($visited, $rec_id);

	$details = array();
	$res = mysql_query('select rd_type, rd_val
	                      from rec_details
	                     where rd_rec_id = ' . $rec_id . '
	                  order by rd_type, rd_id');
	while ($row = mysql_fetch_assoc($res)) {

		$type = $row['rd_type'];
		$val = $row['rd_val'];

		if (! $details[$type]) {
			$details[$type] = $val;
		} else if ($details[$type]  &&  ! is_array($details[$type])) {
			$details[$type] = array($details[$type] , $val);
		} else if ($details[$type]  &&  is_array($details[$type])) {
			array_push($details[$type], $val);
		}
	}

	if ($recurse) {
		// fetch parent details
		foreach (ref_detail_types() as $parent_detail_type) {
			if ($details[$parent_detail_type]) {
				$parent_bib_id = $details[$parent_detail_type];
				if (! in_array($parent_detail_type, $visited))	// avoid infinite recursion
					$details[$parent_detail_type] = fetch_bib_details($parent_bib_id, true, $visited);
			}
		}
	}

	return $details;
}

