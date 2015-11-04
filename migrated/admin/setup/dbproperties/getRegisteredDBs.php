<?php

/**
* getRegisteredDBs.php
* Returns all databases and their URLs that are registered in the master Heurist index server,
* SIDE ONLY. ONLY ALLOW IN HEURISTSCHOLAR.ORG index database
*
* NOTE: A similar but much shorter file appears in migrated/structure/import/getRegisteredDBs.php
*       One file was copied from the other as indicated by identical comments in the header and at the end
*
* @package     Heurist academic knowledge management system
* @link        http://HeuristNetwork.org
* @copyright   (C) 2005-2015 University of Sydney
* @author      Tom Murtagh
* @author      Kim Jackson
* @author      Ian Johnson   <ian.johnson@sydney.edu.au>
* @author      Stephen White   <stephen.white@sydney.edu.au>
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
* @version     4
*/

/*
* Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except in compliance
* with the License. You may obtain a copy of the License at http://www.gnu.org/licenses/gpl-3.0.txt
* Unless required by applicable law or agreed to in writing, software distributed under the License is
* distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied
* See the License for the specific language governing permissions and limitations under the License.
*/


if (!@$_REQUEST['db']){// be sure to override default this should only be called on the Master Index server so point db master index dbname
    $_REQUEST['db'] = "Heurist_Master_Index";
}
require_once(dirname(__FILE__)."/../../../common/config/initialise.php");
require_once(dirname(__FILE__).'/../../../common/php/dbMySqlWrappers.php');
require_once(dirname(__FILE__).'/../../../records/files/fileUtils.php');


if($_REQUEST['db']!="Heurist_Master_Index"){ //this is request from outside - redirect to master index

    $reg_url =  HEURIST_INDEX_BASE_URL . "migrated/admin/setup/dbproperties/getRegisteredDBs.php?t=11"; //HEURIST_INDEX_BASE_URL POINTS TO http://heurist.sydney.edu.au/h4/

    //get json array of registered databases
    $data = loadRemoteURLContent($reg_url, true); //without proxy

    if($data){
        $registeredDBs = json_decode($data);
        if(!is_array($registeredDBs)){
            if(defined("HEURIST_HTTP_PROXY")){
                $data = loadRemoteURLContent($reg_url, false); //with proxy
                if($data){
                    $registeredDBs = json_decode($data);
                    if(!is_array($data)){
                        $registeredDBs = array();
                    }
                }
            }
        }
    }

}else{ // this is a connection on the same server as the master index

    mysql_connection_select("hdb_Heurist_Master_Index");

    $is_named = (@$_REQUEST['named']==1); //return assiated array
    //except specified databases
    if(@$_REQUEST['id']){
        $exclude = explode(",",$_REQUEST['id']);
    }else{
        $exclude = array();
    }

    // Return all registered databases as a json string
    $res = mysql_query("select rec_ID, rec_URL, rec_Title, rec_Popularity, dtl_value as version from Records left join recDetails on rec_ID=dtl_RecID and dtl_DetailTypeID=335 where `rec_RecTypeID`=22");
    $registeredDBs = array();
    while($registeredDB = mysql_fetch_array($res, MYSQL_ASSOC)) {

        if(!array_search( $registeredDB['rec_ID'], $exclude )){

            if($is_named){
                array_push($registeredDBs, $registeredDB);
            }else{

                if(array_key_exists('version',$registeredDB) &&    ($registeredDB['version']==null || $registeredDB['version']<HEURIST_DBVERSION))
                {
                    continue;
                }

                $rawURL = $registeredDB['rec_URL'];
                $splittedURL = explode("?", $rawURL);

                $dbID = $registeredDB['rec_ID'];
                $dbURL = $splittedURL[0];
                preg_match("/db=([^&]*).*$/", $rawURL,$match);
                $dbName = $match[1];
                if (preg_match("/prefix=([^&]*).*$/", $rawURL,$match)){
                    $dbPrefix = $match[1];
                }else{
                    unset($dbPrefix);
                }
                $dbTitle = $registeredDB['rec_Title'];
                $dbPopularity = $registeredDB['rec_Popularity'];

                array_push($registeredDBs, array(
                    $dbID, $dbURL, $dbName, $dbTitle, $dbPopularity, @$dbPrefix
                ));
            }

        }
    }

}

header('Content-type: text/javascript');
print json_encode($registeredDBs);

//BEWARE: TODO: If there is some sort of error, nothing gets returned and this should be trapped at the other end (selectDBForImport.php)
?>