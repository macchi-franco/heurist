<?php
	/**
	* File: processAction.php Import a record type, with all its Record Structure, Field types, and terms, or crosswalk it with an existing record type
	* Juan Adriaanse 13 Apr 2011
	* @copyright 2005-2010 University of Sydney Digital Innovation Unit.
	* @link: http://HeuristScholar.org
	* @license http://www.gnu.org/licenses/gpl-3.0.txt
	* @package Heurist academic knowledge management system
	* @todo Show the import log once the importing is done, so user can see what happened, and change things where desired
	* @todo If an error occurres, delete everything that has been imported
	**/


require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
require_once(dirname(__FILE__).'/../../common/php/dbMySqlWrappers.php');

$targetDBName = @$_GET["importingTargetDBName"];
$sourceTempDBName = @$_GET["tempSourceDBName"];
$importRtyID = @$_GET["importRtyID"];
$sourceDBID = @$_GET["sourceDBID"];
$currentDate = date("d-m");
$error = false;
$importLog = array();

mysql_connection_db_insert($targetDBName);
switch($_GET["action"]) {
	case "crosswalk":
		crosswalk();
		break;
	case "import":
		import();
		break;
	case "drop":
		dropDB();
		break;
	default:
		echo "Error: Unknown action received";
}

function crosswalk() {
/*	$res = mysql_query("insert into `defCrosswalk` (`crw_SourcedbID`, `crw_SourceCode`, `crw_DefType`, `crw_LocalCode`) values ('".$_GET["crwSourceDBID"]."','".$_GET["importRtyID"]."','rectype','".$_GET["crwLocalCode"]."')");
	if(!mysql_error()) {
		echo "Successfully crosswalked rectypes (IDs: " . $_GET["importRtyID"] . " and " . $_GET["crwLocalCode"] . ")";
	} else {
		echo "Error: " . mysql_error();
	}
*/}

function import() {
	global $error, $importLog, $sourceTempDBName, $targetDBName, $sourceDBID, $importRtyID;
	if( !$sourceTempDBName || $sourceTempDBName === "" || !$targetDBName || $targetDBName === "" ||
		!$sourceDBID || !is_numeric($sourceDBID)|| !$importRtyID || !is_numeric($importRtyID)) {
		makeLogEntry("importParameters", -1, "One or more required import parameters not supplied or correct form ( ".
					"importingDBName={name of target DB} sourceDBID={reg number of source DB or 0} ".
					"importRtyID={numeric ID of rectype} tempDBName={temp db name where source DB type data are held}");
		$error = true;
	}

	if(!$error) {
		mysql_query("start transaction");

		// Get recordtype data that has to be imported
		$res = mysql_query("select * from ".$sourceTempDBName.".defRecTypes where rty_ID = ".$importRtyID);
		if(mysql_num_rows($res) == 0) {
			$error = true;
			makeLogEntry("rectype", $importRtyID, "Rectype was not found in $sourceTempDBName");
		} else {
			$importRty = mysql_fetch_assoc($res);
			//error_log("Import entity is  ".print_r($importRty,true));
		}
		// check if rectype already imported, if so return the local id.
		if(!$error && $importRty) {
			$origRtyName = $importRty["rty_Name"];
			$replacementName = @$_GET["replaceRecTypeName"];
			if($replacementName && $replacementName != "") {
				$importRty["rty_Name"] = $replacementName;
				$importRty["rty_Plural"] = ""; //TODO  need better way of determining the plural
			}
			if($importRty["rty_OriginatingDBID"] == 0 || $importRty["rty_OriginatingDBID"] == "") {
				$importRty["rty_OriginatingDBID"] = $sourceDBID;
				$importRty["rty_IDInOriginatingDB"] = $importRtyID;
				$importRty["rty_NameInOriginatingDB"] = $origRtyName;
			}
			//lookup rty in target DB
			$resRtyExist = mysql_query("select rty_ID from ".$targetDBName.".defRecTypes ".
							"where rty_OriginatingDBID = ".$importRty["rty_OriginatingDBID"].
							" AND rty_IDInOriginatingDB = ".$importRty["rty_IDInOriginatingDB"]);
			// Rectype is not in target DB so import it
			if(mysql_num_rows($resRtyExist) > 0 ) {
				$localRtyID = mysql_fetch_array($resRtyExist,MYSQL_NUM);
				$localRtyID = $localRtyID[0];
				makeLogEntry("rectype", $importRtyID, "Rectype $importRtyID was found in $targetDBName as $localRtyID");
				return $localRtyID;
			}
			$localRtyID = importRectype($importRty);
		}
	}
	// successful import
	if(!$error) {
		mysql_query("commit");
		$statusMsg = "";
		if(sizeof($importLog) > 0) {
			foreach($importLog as $logLine) {
				echo  $logLine[0].": ID-".$logLine[1]." ".$logLine[2] . "<br />";
			}
		}
		echo "<br />";
		return $localRtyID;
	// duplicate record found
	} else if (substr(mysql_error(), 0, 9) == "Duplicate") {
		mysql_query("rollback");
		echo "prompt";
	//general error condition
	} else {
		mysql_query("rollback");
		if (mysql_error()) {
			$statusMsg = "Error: " . mysql_error() . "<br />";
		} else  {
			$statusMsg = "Error:<br />";
		}
		if(sizeof($importLog) > 0) {
			foreach($importLog as $logLine) {
				if($logLine[1] == -1) {
					$statusMsg .= $logLine[0].": ".$logLine[2] . "<br />";
				}else{
					$statusMsg .= $logLine[0].": ID-".$logLine[1]." ".$logLine[2] . "<br />";
				}
			}
			$statusMsg .= "An error occurred trying to import the record type";
		}
		// TODO: Delete all information that has already been imported (retrieve from $importLog)
		echo $statusMsg;
	}
}

function importDetailType($importDty) {
	global $error, $importLog, $sourceTempDBName, $targetDBName, $sourceDBID;
	static $importDtyGroupID;

	if (!$importDtyGroupID) {
		// Create new group with todays date, which all detailtypes that the recordtype uses will be added to
		$dtyGroup = mysql_query("select dtg_ID from ".$targetDBName.".defDetailTypeGroups where dtg_Name = 'Imported'");
		if(mysql_num_rows($dtyGroup) == 0) {
			mysql_query("INSERT INTO ".$targetDBName.".defDetailTypeGroups ".
						"(dtg_Name,dtg_Order, dtg_Description) ".
						"VALUES ('Imported', '999',".
								" 'This group contains all detailtypes that were imported from external databases')");
			// Write the insert action to $logEntry, and set $error to true if one occurred
			if(mysql_error()) {
				$error = true;
				makeLogEntry("detailtypeGroup", -1, mysql_error());
			} else {
				$importDtyGroupID = mysql_insert_id();
				makeLogEntry("detailtypeGroup", $newDtyGroupID, "New detail type group was created for this import");
			}
		} else {
			$row = mysql_fetch_row($dtyGroup);
			$importDtyGroupID = $row[0];
		}
	}

	if(!$error && @$importDty['dty_JsonTermIDTree'] && $importDty['dty_JsonTermIDTree'] != '') {
		// term tree exist so need to translate to new ids
		$importDty['dty_JsonTermIDTree'] =  translateTermIDs($importDty['dty_JsonTermIDTree']);
	}

	if(!$error && @$importDty['dty_TermIDTreeNonSelectableIDs'] && $importDty['dty_TermIDTreeNonSelectableIDs'] != '') {
		// term non selectable list exist so need to translate to new ids
		$importDty['dty_TermIDTreeNonSelectableIDs'] =  translateTermIDs($importDty['dty_TermIDTreeNonSelectableIDs']);
	}

	if(!$error && @$importDty['dty_PtrTargetRectypeIDs'] && $importDty['dty_PtrTargetRectypeIDs'] != '') {
		// Target Rectype list exist so need to translate to new ids
		$importDty['dty_PtrTargetRectypeIDs'] =  translateRtyIDs($importDty['dty_PtrTargetRectypeIDs']);
	}

	if(!$error && @$importDty['dty_FieldSetRectypeID'] && $importDty['dty_FieldSetRectypeID'] != '') {
		// dty represents a base rectype so need to translate to local id
		$importDty['dty_FieldSetRectypeID'] =  translateRtyIDs("".$importDty['dty_FieldSetRectypeID']);
	}


	if (!$error) {
		// Check wether the name is already in use. If so, add a number as suffix and find a name that is unused
		$detailTypeSuffix = 2;
		while(mysql_num_rows(mysql_query("select * from ".$targetDBName.".defDetailTypes where dty_Name = '".$importDty["dty_Name"]."'")) != 0) {
			$importDty["dty_Name"] = $importDty["dty_Name"] . $detailTypeSuffix;
			makeLogEntry("detailtype", 0, "Detailtype Name used in source DB already exist in target DB. Added suffix: ".$detailTypeSuffix);
			$detailTypeSuffix++;
		}

		// Change some detailtype fields to make it suitable for the new DB, and insert
		$importDtyID = $importDty["dty_ID"];
		unset($importDty["dty_ID"]);
		$importDty["dty_DetailTypeGroupID"] = $importDtyGroupID;
		$importDty["dty_Name"] = mysql_real_escape_string($importDty["dty_Name"]);
		$importDty["dty_Documentation"] = mysql_real_escape_string($importDty["dty_Documentation"]);
		$importDty["dty_HelpText"] = mysql_real_escape_string($importDty["dty_HelpText"]);
		$importDty["dty_ExtendedDescription"] = mysql_real_escape_string($importDty["dty_ExtendedDescription"]);
		$importDty["dty_NameInOriginatingDB"] = mysql_real_escape_string($importDty["dty_NameInOriginatingDB"]);
		mysql_query("INSERT INTO ".$targetDBName.".defDetailTypes (".implode(", ",array_keys($importDty)).") VALUES ('".implode("', '",array_values($importDty))."')");
		// Write the insert action to $logEntry, and set $error to true if one occurred
		if(mysql_error()) {
			$error = true;
			makeLogEntry("detailtype", $importDtyID, mysql_error());
			break;
		} else {
			$importedDtyID = mysql_insert_id();
			makeLogEntry("detailtype", $importedDtyID, "New detailtype imported");
			return $importedDtyID;
		}
	}
}

// function that translates all rectype ids in the passed string to there local/imported value
function translateRtyIDs($strRtyIDs) {
	global $error, $importLog, $sourceTempDBName, $targetDBName, $sourceDBID;
	if (!$strRtyIDs) {
		return "";
	}
	$outputRtyIDs = array();
	$rtyIDs = explode(",",$strRtyIDs);
	foreach($rtyIDs as $importRtyID) {
	// Get recordtype data that has to be imported
		$res = mysql_query("select * from ".$sourceTempDBName.".defRecTypes where rty_ID = ".$importRtyID);
		if(mysql_num_rows($res) == 0) {
			$error = true;
			makeLogEntry("rectype", $importRtyID, "Rectype was not found in source database, please contact owner of $sourceTempDBName");
			return null; // missing rectype in importing DB
		} else {
			$importRty = mysql_fetch_assoc($res);
			//error_log("Import entity is  ".print_r($importRty,true));
		}

		// check if rectype already imported, if so return the local id.
		if(!$error && $importRty) {
			// change to global ID for lookup
			if($importRty["rty_OriginatingDBID"] == 0 || $importRty["rty_OriginatingDBID"] == "") {
					$importRty["rty_OriginatingDBID"] = $sourceDBID;
					$importRty["rty_IDInOriginatingDB"] = $importRtyID;
					$importRty["rty_NameInOriginatingDB"] = $importRty["rty_Name"];
			}
			//lookup rty in target DB
			$resRtyExist = mysql_query("select rty_ID from ".$targetDBName.".defRecTypes ".
							"where rty_OriginatingDBID = ".$importRty["rty_OriginatingDBID"].
							" AND rty_IDInOriginatingDB = ".$importRty["rty_IDInOriginatingDB"]);
							// Detailtype is not in target DB so import it
			if(mysql_num_rows($resRtyExist) == 0 ) {
				$localRtyID = importRectype($importRty);
				$msgCat = "Import RtyID";
			} else {
				$localRtyID = mysql_fetch_array($resRtyExist,MYSQL_NUM);
				$localRtyID = $localRtyID[0];
				$msgCat = "Translate RtyID";
			}
			if (!$error){
				makeLogEntry($msgCat,$importRtyID, $msgCat." $importRtyID to local ID $localRtyID ");
				array_push($outputRtyIDs, $localRtyID); // store the local ID in output array
			}
		}
	}
	return implode(",", $outputRtyIDs); // return comma separated list of local RtyIDs
}

function importRectype($importRty) {
	global $error, $importLog, $sourceTempDBName, $targetDBName, $sourceDBID;
	static $importRtyGroupID;
error_log("import rty $targetDBName");

	// Get Imported  rectypeGroupID
	if(!$error && !$importRtyGroupID) {
		// Finded 'Imported' rectype group or create it if it doesn't exist
		$rtyGroup = mysql_query("select rtg_ID from ".$targetDBName.".defRecTypeGroups where rtg_Name = 'Imported'");
error_log("import rty 1");
		if(mysql_num_rows($rtyGroup) == 0) {
			mysql_query("INSERT INTO ".$targetDBName.".defRecTypeGroups ".
						"(rtg_Name,rtg_Domain,rtg_Order, rtg_Description) ".
						"VALUES ('Imported','functionalgroup' , '999',".
								" 'This group contains all record types that were imported from external databases')");
		// Write the insert action to $logEntry, and set $error to true if one occurred
error_log("import rty 2");
			if(mysql_error()) {
				$error = true;
				makeLogEntry("rectypeGroup", -1, mysql_error());
			} else {
				$importRtyGroupID = mysql_insert_id();
				makeLogEntry("rectypeGroup", $importRtyGroupID, "'Import' record type group was created");
			}
		} else {
error_log("import rty 3");
			$row = mysql_fetch_row($rtyGroup);
			$importRtyGroupID = $row[0];
			makeLogEntry("rectypeGroup", $importRtyGroupID, "'Import' record type group was found");
		}
	}

	if(!$error) {
		// get rectype Fields and check they are not already imported
		$recStructuresByDtyID = array();
		$importRtyID = $importRty['rty_ID'];
		// get the rectypes structure
		$resRecStruct = mysql_query("select * from ".$sourceTempDBName.".defRecStructure where rst_RecTypeID = ".$importRtyID);
		while($rtsFieldDef = mysql_fetch_assoc($resRecStruct)) {
			$importDtyID = $rtsFieldDef['rst_DetailTypeID'];
			$recStructuresByDtyID[$importDtyID] = $rtsFieldDef;
			// If this recstructure field has originating DBID 0 it's an original concept
			// need to set the origin DBID to the DB it is being imported from
			if($rtsFieldDef["rst_OriginatingDBID"] == 0 || $rtsFieldDef["rst_OriginatingDBID"] == "") {
				$rtsFieldDef["rst_OriginatingDBID"] = $sourceDBID;
				$rtsFieldDef["rst_IDInOriginatingDB"] = $rtsFieldDef["rst_ID"];
			}
			// check that field don't  already exist
			$resRstExist = mysql_query("select rst_ID from ".$targetDBName.".defRecStructure ".
							"where rst_OriginatingDBID = ".$rtsFieldDef["rst_OriginatingDBID"].
							" AND rst_IDInOriginatingDB = ".$rtsFieldDef["rst_IDInOriginatingDB"]);
			if ( mysql_num_rows($resRstExist)) {
				makeLogEntry("rectypeStructure", $importDtyID, "Error: found existing rectype structure \"".$rtsFieldDef["rst_DisplayName"]."\"");
				$error = true;
			}
		}


		if(!$error) {	//import rectype
			// Change some recordtype fields to make it suitable for the new DB
			unset($importRty["rty_ID"]);
			$importRty["rty_RecTypeGroupID"] = $importRtyGroupID;
			$importRty["rty_Name"] = mysql_escape_string($importRty["rty_Name"]);
			$importRty["rty_Description"] = mysql_escape_string($importRty["rty_Description"]);
			$importRty["rty_Plural"] = mysql_escape_string($importRty["rty_Plural"]);
			$importRty["rty_NameInOriginatingDB"] = mysql_escape_string($importRty["rty_NameInOriginatingDB"]);
			$importRty["rty_ReferenceURL"] = mysql_escape_string($importRty["rty_ReferenceURL"]);
			$importRty["rty_AlternativeRecEditor"] = mysql_escape_string($importRty["rty_AlternativeRecEditor"]);

			// Insert recordtype
			mysql_query("INSERT INTO ".$targetDBName.".defRecTypes ".
						"(".implode(", ",array_keys($importRty)).") VALUES ".
						"('".implode("', '",array_values($importRty))."')");
			// Write the insert action to $logEntry, and set $error to true if one occurred
			if(mysql_error()) {
				$error = true;
				makeLogEntry("rectype", -1, mysql_error());
			} else {
				$importedRecTypeID = mysql_insert_id();
				makeLogEntry("rectype", $importedRecTypeID, "Successfully imported recordtype with name: \"".$importRty["rty_Name"]."\"");
			}
		}

		if(!$error) {
			// Import the structure for the recordtype imported
			foreach ( $recStructuresByDtyID as $dtyID => $rtsFieldDef) {
				// get import detailType for this field
				$importDty = mysql_fetch_assoc(mysql_query("select * from ".$sourceTempDBName.".defDetailTypes where dty_ID = $dtyID"));
				// If detailtype has originating DBID 0, set it to the DBID from the DB it is being imported from
				if($importDty["dty_OriginatingDBID"] == 0 || $importDty["dty_OriginatingDBID"] == "") {
					$importDty["dty_OriginatingDBID"] = $sourceDBID;
					$importDty["dty_IDInOriginatingDB"] = $importDty['dty_ID'];
					$importDty["rty_NameInOriginatingDB"] = $importDty['dty_Name'];
				}

				// Check to see if the detailType for this field exist in the target DB
				$resExistingDty = mysql_query("select dty_ID from ".$targetDBName.".defDetailTypes ".
										"where dty_OriginatingDBID = ".$importDty["dty_OriginatingDBID"].
										" AND dty_IDInOriginatingDB = ".$importDty["dty_IDInOriginatingDB"]);

				// Detailtype is not in target DB so import it
				if(mysql_num_rows($resExistingDty) == 0) {
					$rtsFieldDef["rst_DetailTypeID"] = importDetailType($importDty);
error_log("import rty 4  dtyID = ".$rtsFieldDef["rst_DetailTypeID"]);
				} else {
					$existingDtyID = mysql_fetch_array($resExistingDty);
					$rtsFieldDef["rst_DetailTypeID"] = $existingDtyID[0];
error_log("import rty 5  dtyID = ".$rtsFieldDef["rst_DetailTypeID"]);
				}

				if(!$error && @$rtsFieldDef['rst_FilteredJsonTermIDTree'] && $rtsFieldDef['rst_FilteredJsonTermIDTree'] != '') {
error_log("import rty 6  dtyID = ".$rtsFieldDef["rst_DetailTypeID"]." (".$rtsFieldDef['rst_FilteredJsonTermIDTree'].")");
					// term tree exist so need to translate to new ids
					$rtsFieldDef['rst_FilteredJsonTermIDTree'] =  translateTermIDs($rtsFieldDef['rst_FilteredJsonTermIDTree']);
				}

				if(!$error && @$rtsFieldDef['rst_TermIDTreeNonSelectableIDs'] && $rtsFieldDef['rst_TermIDTreeNonSelectableIDs'] != '') {
error_log("import rty 7  dtyID = ".$rtsFieldDef["rst_DetailTypeID"]);
					// term non selectable list exist so need to translate to new ids
error_log("non selectable = ". print_r($rtsFieldDef['rst_TermIDTreeNonSelectableIDs'],true));
					$termList = implode(",",$rtsFieldDef['rst_TermIDTreeNonSelectableIDs']);
					$translatedTermList = translateTermIDs($termList);
					if ($translatedTermList) {
						$rtsFieldDef['rst_TermIDTreeNonSelectableIDs'] = "[".$translatedTermList."]";
					}
				}

				if(!$error && @$rtsFieldDef['rst_PtrFilteredIDs'] && $rtsFieldDef['rst_PtrFilteredIDs'] != '') {
error_log("import rty 8  dtyID = ".$rtsFieldDef["rst_DetailTypeID"]);
					// Target Rectype list exist so need to translate to new ids
					$rtsFieldDef['rst_PtrFilteredIDs'] =  translateRtyIDs($rtsFieldDef['rst_PtrFilteredIDs']);
				}

				if(!$error) {
					// Adjust values of the field structure for the imported recordtype
					$importRstID = $rtsFieldDef["rst_ID"];
					unset($rtsFieldDef["rst_ID"]);
					$rtsFieldDef["rst_RecTypeID"] = $importedRecTypeID;
					$rtsFieldDef["rst_DisplayName"] = mysql_escape_string($rtsFieldDef["rst_DisplayName"]);
					$rtsFieldDef["rst_DisplayHelpText"] = mysql_escape_string($rtsFieldDef["rst_DisplayHelpText"]);
					$rtsFieldDef["rst_DisplayExtendedDescription"] = mysql_escape_string($rtsFieldDef["rst_DisplayExtendedDescription"]);
					$rtsFieldDef["rst_DefaultValue"] = mysql_escape_string($rtsFieldDef["rst_DefaultValue"]);
					$rtsFieldDef["rst_DisplayHelpText"] = mysql_escape_string($rtsFieldDef["rst_DisplayHelpText"]);
					// Import the field structure for the imported recordtype
					mysql_query("INSERT INTO ".$targetDBName.".defRecStructure (".implode(", ",array_keys($rtsFieldDef)).") VALUES ('".implode("', '",array_values($rtsFieldDef))."')");
					// Write the insert action to $logEntry, and set $error to true if one occurred
					if(mysql_error()) {
						$error = true;
						makeLogEntry("defRecStructure", $importRstID, mysql_error());
						break;
					} else {
						makeLogEntry("defRecStructure",  mysql_insert_id(), "New defRecStructure field imported");
					}
				}
			}
		}
	}
}


// function that translates all term ids in the passed string to there local/imported value
function translateTermIDs($formattedStringOfTermIDs) {
	global $error, $importLog, $sourceTempDBName, $targetDBName, $sourceDBID;
	if (!$formattedStringOfTermIDs || $formattedStringOfTermIDs == "") {
		return "";
	}
	$retJSonTermIDs = $formattedStringOfTermIDs;
	if (strpos($retJSonTermIDs,"{")!== false) {
error_log( "term tree string = ". $formattedStringOfTermIDs);
		$temp = preg_replace("/[\{\}\",]/","",$formattedStringOfTermIDs);
		if (strrpos($temp,":") == strlen($temp)-1) {
			$temp = substr($temp,0, strlen($temp)-1);
		}
		$termIDs = explode(":",$temp);
	} else {
error_log( "term array string = ". $formattedStringOfTermIDs);
		$temp = preg_replace("/[\[\]\"]/","",$formattedStringOfTermIDs);
		$termIDs = explode(",",$temp);
	}

	// Import terms
	foreach ($termIDs as $importTermID) {
		// importTerm
		$translatedTermID = importTermID($importTermID);
		// check that the term imported correctly
		if ($translatedTermID == ""){
			return "";
		}
		//replace termID in string
		$retJSonTermIDs = preg_replace("/\"".$importTermID."\"/","\"".$translatedTermID."\"",$retJSonTermIDs);
	}
	// TODO: update the ChildCounts
	makeLogEntry("term string", '', "Translated $formattedStringOfTermIDs to $retJSonTermIDs.");

	return $retJSonTermIDs;
}

function importTermID($importTermID) {
	global $error, $importLog, $sourceTempDBName, $targetDBName, $sourceDBID;
error_log( "import termID = ". $importTermID);
	if (!$importTermID){
		return "";
	}
	//the source term we want to import
	$term = mysql_fetch_assoc(mysql_query("select * from ".$sourceTempDBName.".defTerms where trm_ID = ".$importTermID));
	if(!$term || @$term['trm_ID'] != $importTermID) {
		// log the problem and return an empty string
		$error = true;
		makeLogEntry("term", $importTermID, "Unable to import term id-$importTermID doesn't exist in sourceDB.");
		if(mysql_error()) {
			makeLogEntry("term", -1, mysql_error());
		}
		return "";
	} else {
		// If term has originating DBID 0, set it to the DBID from the DB it is being imported from
		if($term["trm_OriginatingDBID"] == 0 || $term["trm_OriginatingDBID"] == "") {
			$term["trm_OriginatingDBID"] = $sourceDBID;
			$term["trm_IDInOriginatingDB"] = $importTermID;
		}
		// Check wether this term is already imported
		$resExistingTrm = mysql_query("select trm_ID from ".$targetDBName.".defTerms ".
								"where trm_OriginatingDBID = ".$term["trm_OriginatingDBID"].
								" AND trm_IDInOriginatingDB = ".$term["trm_IDInOriginatingDB"]);
		// Term is in target DB so return translated term ID
		if(mysql_num_rows($resExistingTrm) > 0) {
			$existingTermID = mysql_fetch_array($resExistingTrm);
//error_log( " existing term  = ". print_r($existingTermID,true));
			return $existingTermID[0];
		} else {
			// If parent term exist import  it first and use the save the parentID
			$sourceParentTermID = $term["trm_ParentTermID"];
			if (($sourceParentTermID != "") && ($sourceParentTermID != 0)) {
				$localParentTermID = importTermID($sourceParentTermID);
				// TODO: check that the term imported correctly
				if (($localParentTermID == "") || ($localParentTermID == 0)) {
					makeLogEntry("term", $sourceParentTermID, "Unable to import parentterm for term id-$importTermID .");
					return "";
				}else{
					$term["trm_ParentTermID"] = $localParentTermID;
					makeLogEntry("term", $localParentTermID, "Imported parentterm for term id-$sourceParentTermID .");
				}
			} else {
				unset($term["trm_ParentTermID"]);
			}
			$selfInverse = false;
			if($term["trm_InverseTermId"] == $term["trm_ID"]) {
				$selfInverse = true;
			}
			$inverseSourceTrmID = $term["trm_InverseTermId"];

			// Import the term
			unset($term["trm_ID"]);
			unset($term["trm_ChildCount"]);
			unset($term["trm_InverseTermId"]);
			$term["trm_AddedByImport"] = 1;
			$term["trm_Label"] = mysql_escape_string($term["trm_Label"]);
			$term["trm_Description"] = mysql_escape_string($term["trm_Description"]);
			$term["trm_NameInOriginatingDB"] = mysql_escape_string($term["trm_NameInOriginatingDB"]);
			mysql_query("INSERT INTO ".$targetDBName.".defTerms (".implode(", ",array_keys($term)).") ".
													"VALUES ('".implode("', '",array_values($term))."')");
			// Write the insert action to $logEntry, and set $error to true if one occurred
			if(mysql_error()) {
				$error = true;
				makeLogEntry("term", $importTermID, "Unable to insert term id-$importTermID .");
				makeLogEntry("term", -1, mysql_error());
				return "";
			} else {
				$newTermID = mysql_insert_id();
				makeLogEntry("term", $newTermID, "New term imported");
			}

			//handle inverseTerm if there is one
			if( $inverseSourceTrmID && $inverseSourceTrmID != "") {
				if($selfInverse) {
					$localInverseTermID = $newTermID;
				} else {
					$localInverseTermID = importTermID($inverseSourceTrmID);
				}
			// If there is an inverse term then update this term with it's local ID
				mysql_query("UPDATE ".$targetDBName.".defTerms SET trm_InverseTermId=".$localInverseTermID." where trm_ID=".$newTermID);
			}
			return $newTermID;
		}
	}
}

function makeLogEntry( $name = "unknown", $id = "", $msg = "no message" ) {
	global $importLog;
	array_push($importLog, array($name, $id, $msg));
}

// Checks wether passed $sourceTempDBName contains 'temp_', and if so, deletes the database
function dropDB() {
	$sourceTempDBName = $_GET["tempDBName"];
	$isTempDB = strpos($sourceTempDBName, "temp_");
	if($isTempDB !== false) {
		mysql_query("drop database ".$sourceTempDBName);
		if(!mysql_error()) {
			echo "Temporary database was sucessfully deleted";
			return true;
		} else {
			echo "Error: Something went wrong deleting the temporary database";
			return false;
		}
	} else {
		echo "Error: cannot delete a non-temporary database";
		return false;
	}
}
?>

