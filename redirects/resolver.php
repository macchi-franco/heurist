<?php

/**
*
* resolver.php
* Acts as a PID redirector to an XML rendition of the record (database on current server).
* Future version will resolve to remote databases via a lookup on the Heurist master index
* and caching of remote server URLs to avoid undue load on the Heurist master index.
*
* Note: up to Dec 2015 V4.1.3, resolver.php redirected to a human-readable form, viewRecord.php
*       from Jan 2016 V4.1.4, resolver.php is intended to return a machine consumable XML rendition
*
* @package     Heurist academic knowledge management system
* @link        http://HeuristNetwork.org
* @copyright   (C) 2005-2020 University of Sydney
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
* @author      Ian Johnson     <ian.johnson@sydney.edu.au>
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
// Input is of the form .../redirects/resolver.php?db=mydatabase&recID=3456

// TODO: future form accepting recID=123-3456 which redirects to record 3456 on database 123.
//       This will require qizzing the central Heurist index to find out the location of database 123.
//       The location of database 123 should then be cached so it does not require a hit on the
//       master index server for every record. By proceeding in this way, every Heurist database
//       becomes a potential global resolver.
// Redirect to .../records/view/viewRecordAsXML.php (TODO:)
// TODO: write /redirects/resolver.php as an XML feed with parameterisation for a human-readable view
// TODO: the following is a temporary redirect to viewRecord.php which renders a human-readable form


//redirection for CMS 
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
if(count($requestUri)>2 && $requestUri[1]=='web'){
/*
To enable this redirection add to httpd.conf

RewriteEngine On
#if URI starts with web/ redirect it to controller/web.php
RewriteRule ^/heurist/web/(.*)$ /h6-ao/index.php

https://HeuristRef.net/web/johns_test_63/1463/2382     
→ https://heuristref.net/heurist/?db=johns_test_063&website&id=1463&pageid=2382         

The IDs for the website and the pageid are optional, so in most cases, w
here the website is the first or only one for the database, 
all that is needed is the database name like this:

https://HeuristRef.net/web/johns_test_63

http://127.0.0.1/h6-ao/web/osmak_9c/17/12

$requestUri:
0 - "heurist"
1 - "web" 
2 - database
3 - website id
4 - page id
*/    
    $_REQUEST = array();
    $_REQUEST['db'] = $requestUri[2];
    $_REQUEST['website'] = 1;
    
    $redirect = substr($_SERVER['SCRIPT_URI'],0,strpos($_SERVER['SCRIPT_URI'],$requestUri[0]))
                .$requestUri[0].'/?website&db='.$requestUri[2];
    if(@$requestUri[3]>0){
        $_REQUEST['id'] = $requestUri[3];    
        $redirect .= '&id='.$requestUri[3];    
    } 
    if(@$requestUri[4]>0) {
        $_REQUEST['pageid'] = $requestUri[4];   
        $redirect .= '&pageid='.$requestUri[4];    
    }
    header('Location: '.$redirect);  
    exit();
    
    /*
    define('PDIR', 'http://127.0.0.1/h6-ao/');
    $_SERVER["SCRIPT_NAME"] = '/h6-ao/index.php';
    $_SERVER["SCRIPT_URL"] = '/h6-ao/';
    $_SERVER['REQUEST_URI'] = '/h6-ao/';
    */
}



if(@$_REQUEST['fmt']){
    $format = $_REQUEST['fmt'];    
}elseif(@$_REQUEST['format']){
    $format = $_REQUEST['format'];        
}else{
    $format = 'xml';
}
$entity = null;
$recid = null;         
$database_id = 0;

if(@$_REQUEST['recID']){
    $recid = $_REQUEST['recID'];    
}else if(@$_REQUEST['recid']){
    $recid = $_REQUEST['recid'];        
}else if (@$_REQUEST['rty'] || @$_REQUEST['dty'] || @$_REQUEST['trm']){
    
    if(@$_REQUEST['rty']) $entity = 'rty';
    else if(@$_REQUEST['dty']) $entity = 'dty';
    else if(@$_REQUEST['trm']) $entity = 'trm';
    
    $recid = $_REQUEST[$entity];
    $format = 'xml';

    if(strpos($recid, '-')>0){    
        $vals = explode('-', $recid);
        if(count($vals)==3){
            $database_id = $vals[0];
            $recid = $vals[1].'-'.$vals[2];
        }
    }
    
}

//form accepting recID=123-3456 which redirects to record 3456 on database 123
if(!$entity && strpos($recid, '-')>0){
    list($database_id, $recid) = explode('-', $recid, 2);
}else if (is_int(@$_REQUEST['db'])){
    $database_id = $_REQUEST['db'];
}

$database_url = null;    

if ($database_id>0) {
    
    $to_include = dirname(__FILE__).'/../admin/setup/dbproperties/getDatabaseURL.php';
    if (is_file($to_include)) {
        include $to_include;
    }
    
    if(isset($error_msg)){
        header('Location:../hclient/framecontent/infoPage.php?error='.rawurlencode($error_msg));
        exit();
    }

}

if($database_url!=null){ //redirect to resolver for another database
    if($entity!=null){
        $redirect = $database_url.'&'.$entity.'='.$recid;        
    }else{
        $redirect = $database_url.'&recID='.$recid.'&fmt'.$format;    
    }
}else if($entity!=null){
    
    $redirect = '../admin/describe/getDBStructureAsXML.php?db='.$_REQUEST['db'].'&'.$entity.'='.$recid;
    
}else if($format=='html'){
    $redirect = '../viewers/record/viewRecord.php?db='.$_REQUEST['db'].'&recID='.$recid;
    
}else if($format=='web' || $format=='website'){
    
    $redirect = '../hclient/widgets/cms/websiteRecord.php?db='.$_REQUEST['db'].'&recID='.$recid;
    if(@$_REQUEST['field']>0){
        $redirect = $redirect.'&field='.$_REQUEST['field'];    
    }
    
}else if($format=='edit'){
    //todo include resolver recordSearchReplacement
    $redirect = '../hclient/framecontent/recordEdit.php?'.$_SERVER['QUERY_STRING'];
}else{
    //todo include resolver  recordSearchReplacement
    $redirect = '../export/xml/flathml.php?db='.$_REQUEST['db'].'&depth=1&w=a&q=ids:'.$recid;
}

header('Location: '.$redirect);
return;
?>
