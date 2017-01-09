<?php

    /**
    * elasticSearchFunctions.php: Functions to interact with ElasticSearch
    *
    * @package     Heurist academic knowledge management system
    * @link        http://HeuristNetwork.org
    * @copyright   (C) 2005-2017 University of Sydney
    * @author      Ian Johnson     <ian.johnson@sydney.edu.au>
    * @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
    * @author      Jan Jaap de Groot    <jjedegroot@gmail.com>
    * @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
    * @version     4.0
    */

    /*
    * Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except in compliance
    * with the License. You may obtain a copy of the License at http://www.gnu.org/licenses/gpl-3.0.txt
    * Unless required by applicable law or agreed to in writing, software distributed under the License is
    * distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied
    * See the License for the specific language governing permissions and limitations under the License.
    */


    /*
    Elastic Search creates indexes automatically and updates the index record when new data is supplied for an existing key
    However we do need to delete keys explicitely if 1. the record type changes or 2. we delete the record altogether
    Test for installation allows us to fall back to ordinary searching or render warning that feature is not supported

    ACTIONS TODO:

    Call updateRecordIndexEntry whenever a record is written (new or updated, from record edit or record import or record recode)

    Call buildAllIndices as part of database upgrade 1.1.0 to 1.2.0

    Call deleteRecordIndexEntry whenever a record is saved with a different type (editRecord)

    Call deleteRecordIndexEntry whenever a record is deleted (Search Actions)


    FUNCTION CALLS:
    testElasticSearchOK()
    - not used

    sanitiseForES()
    - used by updateRecordIndexEntry()

    updateRecordIndexEntry()
    - saveRecord.php
    - saveRecordDetails.php

    deleteRecordIndexEntry()
    - deleteRecordInfo.php

    deleteIndexForRectype()
    - used by buildIndexForRectype()

    buildIndexForRectype()
    - used by buildAllIndicies()

    buildAllIndices()
    - cloneDatabase.php
    - rebuildLuceneIndices.php

    */

    require_once(dirname(__FILE__).'/../../common/connect/applyCredentials.php');
    require_once('elasticSearchHelper.php');



    // ****************************************************************************************************************
    /**
    * Add a new key or update an existing key - Elastic Search adds or updates as appropriate, no need to specify
    * By reading record from database we ensure that we are indexing only records which have been successfully written
    * @param $dbName        The name of the Heurist database, excluding prefix
    * @param $recTypeID     The record type ID of the record being indexed
    * @return               True if successful
    */
    function updateRecordIndexEntry ($dbName, $recTypeID, $recID) {
        error_log("[elasticSearchFunctions.php] updateRecordIndexEntry for database $dbName recTypeID=$recTypeID recID=$recID");

        if(isElasticEnabled()) {
            $record = new stdClass();

            // Retrieve record level data
            $query="SELECT rec_URL,rec_Added,rec_Modified,rec_Title,rec_RecTypeID,rec_AddedByUGrpID,rec_AddedByImport,rec_Popularity,".
            "rec_FlagTemporary,rec_OwnerUGrpID,rec_NonOwnerVisibility,rec_URLLastVerified,rec_URLErrorMessage,rec_URLExtensionForMimeType ".
            "from Records where rec_ID=$recID"; // omits scratchpad
            $res = mysql_query($query);

            // Check if query has succeed ed
            if ($res) {
                $row = mysql_fetch_array($res); // Fetch record data

                // Construct record
                $record->URL            = $row[0];
                $record->Added          = $row[1];
                $record->Modified       = $row[2];
                $record->Title          = $row[3];
                $record->RecTypeID      = $row[4];
                $record->AddedBy        = $row[5];
                $record->Imported       = $row[6];
                $record->Popularity     = $row[7];
                $record->Temporary      = $row[8];
                $record->OwnerUGrpID    = $row[9];
                $record->NonOwnerVis    = $row[10];
                $record->URLLastVerif   = $row[11];
                $record->URLErrMsg      = $row[12];
                $record->URLExtMimeType = $row[13];

                // Check if recTypeID has stayed the same 
                if($record->RecTypeID != $recTypeID) {
                    // TODO: Delete index for old record type before updating index for new record type 
                }
            } else {
                error_log("[elasticSearchFunctions.php] updateRecordIndexEntry --> record query failed: $query");
                return false;
            }

            // Retrieve detail level data
            $query = "SELECT dtl_DetailTypeID,dtl_Value,dtl_UploadedFileID,dtl_Geo from recDetails where dtl_RecID=$recID";
            $res = mysql_query($query);

            // Check if query has succeeded
            if ($res) {
                while (($row = mysql_fetch_array($res))) {
                    // Detail ID is used as key, together with dtl_Value, dtl_UploadedFileID and dtl_Geo
                    // TODO: should use dtl_Value OR dtl_UploadedFileID OT dtl_Geo according to detail type
                    $record->$row[0] = $row[1].$row[2].$row[3];
                }
            }else{
                error_log("[elasticSearchFunctions.php] updateRecordIndexEntry --> details query failed: $query");
                return false;
            }

            // PUT data to ElasticSearch
            $address = getElasticAddress($dbName, $recTypeID, $recID);
            $json = putElastic($address, $record);
            $response = json_decode($json);
            return property_exists($response, 'created');
        }
        return false;
    } // addUpdateRecordIndex



    // ****************************************************************************************************************
    // Note: Reported bug in PHP @ 18/11/13: must reset to NULL to obtain internal default.
    //       Resetting directly to eg. PUT or GET will not reset, it will remain set as DELETE
    // ****************************************************************************************************************


    // ****************************************************************************************************************
    /**
    * Delete the index entry for a specified record - use when record type changed or record deleted
    * @param $dbName        The name of the Heurist databasem, excluding prefix
    * @param $recTypeID     The record type ID of the record being deleted from the index
    * @param $recID         The record to be deleted from the index
    * @return               curl return code, 0 = success
    */
    function deleteRecordIndexEntry ($dbName, $recTypeID, $recID ) {
        error_log("[elasticSearchFunctions.php] deleteRecordIndexEntry for database $dbName recTypeID=$recTypeID recID=$recID");

        if(isElasticEnabled()) {
            global $indexServerAddress, $indexServerPort;

            $url="$indexServerAddress:$indexServerPort/$dbName/$recTypeID/$recID";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            if ($error) {
                $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                //print "<br />ERROR: deleteRecordIndexEntry: $error ($code)" . " url = ". $url;
                curl_close($ch);
                return $code;
            } else {
                curl_close(ch); // is this necessary?
            }
        }
        return(0);
    } // deleteRecordIndex


    // ****************************************************************************************************************
    /**
    * Delete the index for a specified record type
    * @param $dbName       The name of the Heurist databasem, excluding prefix
    *  @param $recTypeID    The record type ID of the record being deleted from the index
    */
    function deleteIndexForRectype ($dbName, $recTypeID) {
        error_log("[elasticSearchFunctions.php] deleteIndexForRectype for database $dbName recTypeID=$recTypeID");

        if(isElasticEnabled()) {
            // TODO: check that this is correct spec for deletion of the index for a record type
            global $indexServerAddress, $indexServerPort;

            $url="$indexServerAddress:$indexServerPort/$dbName/$recTypeID";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            if ($error) {
                $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                //print "<br />ERROR: deleteIndexForRectype: $error ($code)" . " url = ". $url;
                curl_close($ch);
                return $code;
            } else {
                curl_close(ch); // is this necessary?
            }
        }
        return(0);
    } // deleteIndexForRectype


    // ****************************************************************************************************************
    /**
    * Delete the index for a specified database
    * @param $dbName       The name of the Heurist databasem, excluding prefix
    */
    function deleteIndexForDatabase ($dbName) {
        error_log("[elasticSearchFunctions.php] deleteIndexForDatabase for database $dbName");

        if(isElasticEnabled()) {
            // TODO: check that this is correct spec for deletion of the index for a database
            global $indexServerAddress, $indexServerPort;

            $url="$indexServerAddress:$indexServerPort/$dbName";
            print "Deleting all Elasticsearch indices for $dbName at $url<br />";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            $error = curl_error($ch);
            if ($error) {
                $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                //print "<br />ERROR: deleteIndexForDatabase: $error ($code)" . " url = ". $url;
                curl_close($ch);
                return($code);
            } else {
                curl_close(ch); // is this necessary?
            }
        }
        return(0);
    } // deleteIndexForDatabase


    // ****************************************************************************************************************
    /**
    * Rebuild the index for a specified record type
    * @param $dbName       The name of the Heurist databasem, excluding prefix
    * @param
    * @returns 0 = OK, any other = error
    */
    function buildIndexForRectype ($dbName, $recTypeID) {
        error_log("[elasticSearchFunctions.php] buildIndexForRectype for database $dbName recTypeID=$recTypeID");

        if(isElasticEnabled()) {
            //print "buildIndexForRectype: indexing record type $recTypeID for $dbName<br />";
            deleteIndexForRectype ($dbName, $recTypeID); // clear the existing index

            $query="Select rec_ID from Records where rec_RecTypeID = $recTypeID";
            $res = mysql_query($query);
            if ($res) {
                while (($row = mysql_fetch_array($res))) { // fetch records
                    $code = updateRecordIndexEntry ($dbName, $recTypeID, $row[0]/*recID*/);
                    if($code != 0) {
                        //print "<br />ERROR while updating record index; code = $code, dbName = $dbname, rectypeID = $recTypeID, row = " + $row[0] + "<br />";
                        return($code); // curl error code
                    }
                }
                return(0);
            }
        }
        return(-1);
    } // buildIndexForRectype


    // ****************************************************************************************************************
    /**
    * Rebuild the index for all record types
    * @param    $dbName  The name of the Heurist databasem, excluding prefix
    * @returns  0 = OK, 1 = error
    */
    function buildAllIndices ($dbName) {
        error_log("[elasticSearchFunctions.php] buildAllIndices for database $dbName");

        if(isElasticEnabled()) {
            print "Building all Elasticsearch indices for: $dbName<br />";

            $query="Select MAX(rec_RecTypeID) from Records where 1";
            $res = mysql_query($query);
            if ($res) {
                $row = mysql_fetch_array($res);
                $maxRecTypeID = $row[0];
                //print "<br />MaxRecTypeID = $maxRecTypeID<br />";
                for ($i = 1; $i <= $maxRecTypeID; $i++) { // call index function for each record type
                    $code = buildIndexForRectype ($dbName, $i);
                    if($code != 0) {
                        //print "<br />ERROR while building index for rectype; code = $code, dbName = $dbName, i = $i<br />";
                        return($code);
                    }
                }
                return(0);
            }
        }
        return(-1);
    } // buildAllIndices

?>
