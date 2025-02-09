<?php

    /**
    * dbAnnotations 
    * 
    *
    * @package     Heurist academic knowledge management system
    * @link        https://HeuristNetwork.org
    * @copyright   (C) 2005-2023 University of Sydney
    * @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
    * @license     https://www.gnu.org/licenses/gpl-3.0.txt GNU License 3.0
    * @version     4.0
    */

    /*
    * Licensed under the GNU License, Version 3.0 (the "License"); you may not use this file except in compliance
    * with the License. You may obtain a copy of the License at https://www.gnu.org/licenses/gpl-3.0.txt
    * Unless required by applicable law or agreed to in writing, software distributed under the License is
    * distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied
    * See the License for the specific language governing permissions and limitations under the License.
    */

require_once (dirname(__FILE__).'/../System.php');
require_once (dirname(__FILE__).'/dbEntityBase.php');

class DbAnnotations extends DbEntityBase 
{
    
    function __construct( $system, $data ) {
        $this->system = $system;
        $this->data = $data;

        //error_log(print_r($data, true));  

        $this->system->defineConstant('RT_ANNOTATION');
        $this->system->defineConstant('RT_MAP_ANNOTATION');
        $this->system->defineConstant('DT_NAME');
        $this->system->defineConstant('DT_URL');
        $this->system->defineConstant('DT_ORIGINAL_RECORD_ID');
        $this->system->defineConstant('DT_EXTENDED_DESCRIPTION');
        $this->system->defineConstant('DT_SHORT_SUMMARY');

        $this->init();
    }

    public function isvalid(){
        return true;
    }
    
    /**
    *  Search all annotaions for given uri (IIIF manifest)
    *  or particular annotaion id
    * 
    * 
    *  @todo overwrite
    */
    public function search(){

        $sjson = array('id'=>"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", 
                        'type' => 'AnnotationPage');
                       

        //find all annotation for given uri 
                    
        if($this->data['recID']=='pages'){
            $sjson['items'] = array();
            
            if($this->data['uri']){
                $this->data['uri'] = substr($_SERVER['QUERY_STRING'],4);
            }
            $uri = $this->data['uri']; //.(@$this->data['file']?'&file='.$this->data['file']:'');
            $items = $this->findItems_by_Canvas($uri);
            if(is_array($items) && count($items)>0){
                
                foreach($items as $item){
                    $sjson['items'][] = json_decode($item, true);
                }
                
            }else{
                $sjson['items'] = array(); 
            }
            
        }else{
            $item = $this->findItem_by_UUID($this->data['recID']);
            if($item!=null){
                $sjson['items'] = array(json_decode($item, true));     
            }
        }
            
//error_log(print_r($sjson, true));            
            
        return $sjson;
    }

    //
    //
    //    
    private function findItems_by_Canvas($canvasUri){
        $query = 'SELECT d2.dtl_Value FROM recDetails d1, recDetails d2 WHERE '
        .'d1.dtl_DetailTypeID='.DT_URL .' AND d1.dtl_Value="'.$canvasUri.'"'
        .' AND d1.dtl_RecID=d2.dtl_RecID'
        .' AND d2.dtl_DetailTypeID='.DT_EXTENDED_DESCRIPTION;
        $items = mysql__select_list2($this->system->get_mysqli(), $query);
        return $items;
    }

    //
    //
    //    
    private function findItem_by_UUID($uuid){
        $query = 'SELECT d2.dtl_Value FROM recDetails d1, recDetails d2 WHERE '
        .'d1.dtl_DetailTypeID='.DT_ORIGINAL_RECORD_ID .' AND d1.dtl_Value="'.$uuid.'"'
        .' AND d1.dtl_RecID=d2.dtl_RecID'
        .' AND d2.dtl_DetailTypeID='.DT_EXTENDED_DESCRIPTION;
        $item = mysql__select_value($this->system->get_mysqli(), $query);
        return $item;
    }
    
    //
    //
    //    
    private function findRecID_by_UUID($uuid){
        $query = 'SELECT dtl_RecID FROM recDetails WHERE dtl_DetailTypeID='.DT_ORIGINAL_RECORD_ID.' AND dtl_Value="'.$uuid.'"';
        $recordId = mysql__select_value($this->system->get_mysqli(), $query);
        return $recordId;
    }
    
    //
    //
    //
    public function delete($disable_foreign_checks = false){
        
        if($this->data['recID']){  //annotation UUID
            
            //validate permission for current user and set of records see $this->recordIDs
            if(!$this->_validatePermission()){
                return false;
            }
            
            //remove annotation with given ID
            $recordId = $this->findRecID_by_UUID($this->data['recID']);
            if($recordId>0){
                return recordDelete($this->system, $recordId);
            }else{
                $this->system->addError(HEURIST_NOT_FOUND, 'Annotation record to be deleted not found');
                return false;
            }
            
        }else{
            $this->system->addError(HEURIST_INVALID_REQUEST, 'Invalid annotation identificator');
            return false;
        }        
    }
    
    
    public function save(){
         //validate permission for current user and set of records see $this->recordIDs
        if(!$this->_validatePermission()){
            return false;
        }
/*        
        annotation: {
          canvas: this.canvasId,
          data: JSON.stringify(annotation),
          uuid: annotation.id,
        },
*/      
        if( !(defined('RT_MAP_ANNOTATION') || defined('RT_MAP_ANNOTATION')) ){
            $this->system->addError(HEURIST_ACTION_BLOCKED, 
                    'Can not add annotation. This database does not have either "Map/Image Annotation" or "Annotation" record type. '
                    .'Import required record type');
            return false;
        }


        $anno = $this->data['fields']['annotation'];
//error_log(print_r($anno, true));  
        
        $details = array();
        
        if($this->is_addition){
             $recordId = 0;
        }else{     
            //find record id by annotation UID
            $recordId = $this->findRecID_by_UUID($anno['uuid']);

            if($recordId>0){
                //@todo make common function findOriginalRecord (see importAction)
                $query = "SELECT dtl_Id, dtl_DetailTypeID, dtl_Value, ST_asWKT(dtl_Geo), dtl_UploadedFileID FROM recDetails WHERE dtl_RecID=$recordId ORDER BY dtl_DetailTypeID";
                $dets = mysql__select_all($this->system->get_mysqli(), $query);
                if($dets){
                    foreach ($dets as $row){
                        $bd_id = $row[0];
                        $field_type = $row[1];
                        if($row[4]){ //dtl_UploadedFileID
                            $value = $row[4];
                        }else if($row[3]){
                            $value = $row[2].' '.$row[3];
                        }else{
                            $value = $row[2];
                        }
                        if(!@$details["t:".$field_type]) $details["t:".$field_type] = array();
                        $details["t:".$field_type][] = $value;
                    }
                }            
            }else{
               $recordId = 0; //add new annotation 
            }
        }
        //"body":{"type":"TextualBody","value":"<p>VOKZAL</p>"},
        $anno_dec = json_decode($anno['data'], true);
        if(is_array($anno_dec) && @$anno_dec['body']['type']=='TextualBody'){
            $details[DT_NAME][] = substr(strip_tags($anno_dec['body']['value']),0,50);
            $details[DT_SHORT_SUMMARY][] = $anno_dec['body']['value'];
        }
        if(@$anno['canvas']){
            $details[DT_URL][] = $anno['canvas'];
        }
        $details[DT_EXTENDED_DESCRIPTION][] = $anno['data'];
        $details[DT_ORIGINAL_RECORD_ID][] = $anno['uuid'];
        
        $record = array();
        $record['ID'] = $recordId;
        $record['RecTypeID'] = defined('RT_MAP_ANNOTATION')?RT_MAP_ANNOTATION:RT_ANNOTATION;
        $record['no_validation'] = true;
        $record['details'] = $details;
        
        $out = recordSave($this->system, $record, false, true);
//error_log(print_r($record, true));        
//error_log(print_r($out, true));        
        return true;
    }
}
?>
