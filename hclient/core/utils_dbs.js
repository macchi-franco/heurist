/**
*  Utility functions for database structure
*
* @package     Heurist academic knowledge management system
* @link        http://HeuristNetwork.org
* @copyright   (C) 2005-2020 University of Sydney
* @author      Artem Osmakov   <artem.osmakov@sydney.edu.au>
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
Selectors:

TERMS
getTermById

getInverseTermById

getTermValue - returns label and code for term by id

getColorFromTermValue - Returns hex color by label or code for term by id


    trm_TreeData  - returns hierarchy for given vocabulary as a flat array, recordset or tree data
    trm_HasChildren - is given term has children
    trm_getVocabs - get all vocabularies OR for given domain
    trm_getAllVocabs - get all vocab where given term presents directly or by reference
    trm_RemoveLinks - remove all entries of term from trm_Links


RECTYPES
   

createRectypeStructureTree
getLinkedRecordTypes  -  FIX in search_faceted.js

hasFields - returns true if rectype has a field in its structure
rstField - Returns rectype header or details field values


    getLocalID
    getConceptID


*/

if (!window.hWin.HEURIST4){
    window.hWin.HEURIST4 = {};
}

//init only once
if (!window.hWin.HEURIST4.dbs) 
{

window.hWin.HEURIST4.dbs = {
    
    baseFieldType: {},
    
    needUpdateRtyCount: false,

    //
    // return vocabulary for given term - real vocabulary (not by reference)
    //    
    getTermVocab: function(trm_ID){
        var trm_ParentTermID;
        do{
            trm_ParentTermID = $Db.trm(trm_ID, 'trm_ParentTermID');
            if(trm_ParentTermID>0){
                trm_ID = trm_ParentTermID;
            }else{
                break;
            }
        }while (trm_ParentTermID>0);
        
        return trm_ID;        
    },
    
    //
    // Returns label and code for term by id
    //
    getTermValue: function(termID, withcode){
        
        var term = $Db.trm(termID);
        var termName, termCode='';

        if(term){
            termName = term['trm_Label'];
            termCode = term['trm_Code'];
            if(window.hWin.HEURIST4.util.isempty(termCode)){
                termCode = '';
            }else{
                termCode = " ("+termCode+")";
            }
        } else {
            termName = 'not found term#'+termID;
        }

        return termName+(withcode ?termCode :'');
    },
    
    //
    // get inverse term id
    //
    getInverseTermById: function(termID){
        var term = $Db.trm(termID);
        if(term){
            var invTermID = term['trm_InverseTermId'];
            if(invTermID>0) return invTermID;
            return termID;
        }
        return '';
    },
    
    //
    // Returns hex color by label or code for term by id
    //
    getColorFromTermValue: function(termID){

        var term = $Db.trm(termID);
        
        var termName, termCode='';

        if(term){

            termName = term['trm_Label'];
            termCode = term['trm_Code'];
            if(window.hWin.HEURIST4.util.isempty(termCode)){
                var cnames = window.hWin.HEURIST4.ui.getColorArr('names');
                var idx = window.hWin.HEURIST4.util.findArrayIndex(termName.toLowerCase(),cnames);
                if(idx>=0){
                    cnames = window.hWin.HEURIST4.ui.getColorArr('hexs');
                    termCode = '#'+cnames[idx]; 
                }
            }
        }

        return termCode;
    },

    //========================================================================
    /*
     
      returns rectype structure as treeview data
      there is similar method on server side - however on client side it is faster
      used for treeview in import structure, faceted search wizard
      todo - use it in smarty editor and title mask editor
     
      rectypeids - set of rty ids     
      fieldtypes - 
            array of fieldtypes, and 'all', 'header', 'header_ext'
            header - all+header fields
      $mode 
         3 - for record title mask editor @todo!!!
         4 - find reverse links and relations   
         5 - for lazy treeview with reverse links (faceted search wiz)
         6 - for lazy tree without reverse (import structure, export csv)
       returns:
         
       children:[{key: field#, type: fieldtype, title:'', code , name, conceptCode, dtyID_local, children:[]},... ]
     
    */
    createRectypeStructureTree: function( db_structure, $mode, rectypeids, fieldtypes, parentcode ) {
        
        var DT_PARENT_ENTITY  = window.hWin.HAPI4.sysinfo['dbconst']['DT_PARENT_ENTITY'];
        
        var rst_links = $Db.rst_links();
        
    //-------------------- internal functions    
        
    function __getRecordTypeTree($recTypeId, $recursion_depth, $mode, $fieldtypes, $pointer_fields){
            
            var $res = {};
            var $children = [];
            var headerFields = [];
            
            //add default fields - RECORD TYPE HEADER
            if($recursion_depth==0 && $fieldtypes.length>0){    
                 //include record header fields
                var all_header_fields = $fieldtypes.indexOf('header_ext')>=0;
                if($fieldtypes.indexOf('header')>=0){
                    $fieldtypes.push('title');
                    $fieldtypes.push('modified');
                }                 
                
                if(all_header_fields || $fieldtypes.indexOf('ID')>=0 || $fieldtypes.indexOf('rec_ID')>=0){
                    $children.push({key:'rec_ID', type:'integer',
                        title:"ID  <span style='font-size:0.7em'>(integer)</span>", 
                        code:($recTypeId+':id'), name:'Record ID'});
                }

                if(all_header_fields || $fieldtypes.indexOf('title')>=0 || $fieldtypes.indexOf('rec_Title')>=0){
                    $children.push({key:'rec_Title', type:'freetext',
                        title:"RecTitle <span style='font-size:0.7em'>(Constructed text)</span>", 
                        code:($recTypeId+':title'), name:'Record title'});
                }
                if(all_header_fields || $fieldtypes.indexOf('modified')>=0 || $fieldtypes.indexOf('rec_Modified')>=0){
                    $children.push({key:'rec_Modified', type:'date',
                        title:"Modified  <span style='font-size:0.7em'>(Date)</span>", 
                        code:($recTypeId+':modified'), name:'Record modified'});
                }
                    
                //array_push($children, array('key'=>'recURL',      'type'=>'freetext',  'title'=>'URL', 'code'=>$recTypeId.":url"));
                //array_push($children, array('key'=>'recWootText', 'type'=>'blocktext', 'title'=>'WootText', 'code'=>$recTypeId.":woot"));
                
                if(all_header_fields || $fieldtypes.indexOf('url')>=0 || $fieldtypes.indexOf('rec_URL')>=0){
                    $children.push({key:'rec_URL', type:'freetext',
                        title:"URL  <span style='font-size:0.7em'>(freetext)</span>", 
                        code:($recTypeId+':url'), name:'Record URL'});
                }
                if(all_header_fields || $fieldtypes.indexOf('tags')>=0 || $fieldtypes.indexOf('rec_Tags')>=0){
                    $children.push({key:'rec_Tags', type:'freetext',
                        title:"Tags  <span style='font-size:0.7em'>(freetext)</span>", 
                        code:($recTypeId+':tags'), name:'Record Tags'});
                }
                
            }

            if($recTypeId>0 && $Db.rty($recTypeId,'rty_Name')){//---------------

                $res['key'] = $recTypeId;
                $res['title'] = $Db.rty($recTypeId,'rty_Name');
                $res['type'] = 'rectype';
                
                $res['conceptCode'] = $Db.getConceptID('rty', $recTypeId);
                $res['rtyID_local'] = $recTypeId; //$Db.getLocalID('rty', $rt_conceptcode); //for import structure
                                
                                                                                                                  
                if(($mode<5 || $recursion_depth==0)){


                    var $details = $Db.rst($recTypeId);
                    
                    //
                    if($fieldtypes.indexOf('parent_link')>=0 && !$Db.rst($recTypeId,DT_PARENT_ENTITY)){
                        
                        //find all parent record types that refers to this record type
                        var $parent_Rts = rst_links.parents[$recTypeId];
                        
                        if($parent_Rts && $parent_Rts.length>0){
                        
                            //create fake rectype structure field
                            $ffr = {};
                            $ffr['rst_DisplayName'] = 'Parent entity';
                            $ffr['rst_PtrFilteredIDs'] = $parent_Rts.join(',');
                            //$ffr['dty_Type'] = 'resource';
                            $ffr['rst_DisplayHelpText'] = 'Reverse pointer to parent record';
                            $ffr['rst_RequirementType'] = 'optional';
                                  
                            $details.addRecord(DT_PARENT_ENTITY, $ffr)
                        }
                    }
                    
                    var $children_links = [];
                    var $new_pointer_fields = [];

                    // add details --------------------------------
                    $details.each2(function($dtID, $dtValue){
                        
                        //@TODO forbidden for import????
                        if($dtValue['rst_RequirementType']!='forbidden'){

                            var $dt_type = $Db.dty($dtID,'dty_Type');
                            if($dt_type=='resource' || $dt_type=='relmarker'){
                                    $new_pointer_fields.push( $dtID );
                            }
                            
                            $res_dt = __getDetailSection($recTypeId, $dtID, $recursion_depth, $mode, 
                                                                    $fieldtypes, null, $new_pointer_fields);
                            if($res_dt){
                                
                                if($res_dt['type']=='resource' || $res_dt['type']=='relmarker'){
                                    $children_links.push($res_dt);
                                }else{
                                    $children.push($res_dt);
                                }
                                /*
                                if(is_array($res_dt) && count($res_dt)==1){
                                $res["f".$dtID] = $res_dt[0];    
                                }else{
                                //multi-constrained pointers or simple variable
                                $res["f".$dtID] = $res_dt;
                                }
                                */
                            }
                        }
                    });//for details
                    
                    //sort bt rst_DisplayOrder
                    $children.sort(function(a,b){
                        return (a['display_order']<b['display_order'])?-1:1;
                    });
                    
                    //add resource and relation at the end of result array
                    $children = $children.concat($children_links);
                    
                    //--------------------------------------------
                    //find all reverse links and relations
                    if( ($mode==4 && $recursion_depth<2) || ($mode==5 && $recursion_depth==0) )
                    {
                        var rev_fields = {};
                        var reverse_fields = rst_links.reverse[$recTypeId]; //all:, dty_ID:[rty_ID,...]
                        var twice_only = 0;
                        while(twice_only<2){

                            for (var $dtID in reverse_fields) {
                                if($dtID>0 && 
                                    ( $pointer_fields==null ||    // to avoid recursion
                                        ($.isArray($pointer_fields) &&   
                                        window.hWin.HEURIST4.util.findArrayIndex($dtID, $pointer_fields)<0) ) )
                                {
                                    rev_fields[$dtID] = reverse_fields[$dtID];
                                }
                            }
                            reverse_fields = rst_links.rel_reverse[$recTypeId]; //all:, dty_ID:[rty_ID,...]
                            twice_only++;
                        }
                        
                        for (var $dtID in rev_fields) {
                                var $rtyIDs = rev_fields[$dtID];
                                for(var i=0; i<$rtyIDs.length; i++)  {
                                    $res_dt = __getDetailSection($rtyIDs[i], $dtID, $recursion_depth, $mode, $fieldtypes, $recTypeId, null);
                     
                                    if($res_dt){
                                        $children.push( $res_dt );
                                    }
                                }
                        }//for
                    }
                    
                    $details.removeRecord(DT_PARENT_ENTITY); //remove fake parent link
                }
                
                if($mode==3 && $recursion_depth==0){
                    $children.push(__getRecordTypeTree('Relationship', $recursion_depth+1, $mode, $fieldtypes, null));
                }   

            }
            else if($recTypeId=="Relationship") { //----------------------------

                $res['title'] = "Relationship";
                $res['type'] = "relationship";

                //add specific Relationship fields
                $children.push({key:'recRelationType', title:'RelationType'});
                $children.push({key:'recRelationNotes', title:'RelationNotes'});
                $children.push({key:'recRelationStartDate', title:'RelationStartDate'});
                $children.push({key:'recRelationEndDate', title:'RelationEndDate'});
            }else if($mode==5 || $mode==6) //-----------------------------------
            {
                //record type is array - add common fields only
                
                $res['title'] = 'Any record type';
                $res['type'] = 'rectype';
                
                if($mode==5 && $recursion_depth==0 && $recTypeId && $recTypeId.indexOf(',')>0){ //for faceted search
                    $res['key'] = $recTypeId;
                    $res['type'] = 'rectype';
                    
                    var recTypes = $recTypeId.split(',');
                    
                    $res['title'] = $Db.rty( recTypes[0],'rty_Name');
                    
                    var  $details = $Db.rst(recTypes[0]); 

                    //if there are several rectypes - find common fields only
                    //IJ wants show all fields of fist record type
                    /*  2020-04-25
                    var names = [];
                    $.each(recTypes, function(i, rtid){ 
                        names.push(rectypes.names[rtid]) 
                        if(i>0){
                            var fields = rectypes['typedefs'][rtid]['dtFields'];
                            var dtIds = Object.keys($details);
                            for (var k=0; k<dtIds.length; k++){
                                if(!fields[dtIds[k]]){
                                    //it does not exist 
                                    $details[dtIds[k]] = null;
                                    delete $details[dtIds[k]];
                                }
                            }
                        }
                    });
                    $res['title'] = names.join(', ');
                    */
                    
                    var $children_links = [];
                    var $new_pointer_fields = [];

                    $details.each2(function($dtID, $dtValue){
                        
                        if($dtValue['rst_RequirementType']!='forbidden'){

                            var $dt_type = $Db.dty($dtID,'dty_Type');
                            if($dt_type=='resource' || $dt_type=='relmarker'){
                                    $new_pointer_fields.push( $dtID );
                            }
                            
                            $res_dt = __getDetailSection(recTypes[0], $dtID, $recursion_depth, $mode, 
                                                                    $fieldtypes, null, $new_pointer_fields);
                            if($res_dt){
                                
                                var codes = $res_dt['code'].split(':');
                                codes[0] = $recTypeId;
                                $res_dt['code'] = codes.join(':');
                                
                                if($res_dt['type']=='resource' || $res_dt['type']=='relmarker'){
                                    $children_links.push($res_dt);
                                }else{
                                    $children.push($res_dt);
                                }
                            }
                        }
                    });//for details
                    
                    //sort bt rst_DisplayOrder
                    $children.sort(function(a,b){
                        return (a['display_order']<b['display_order'])?-1:1;
                    });
                    
                    //add resource and relation at the end of result array
                    $children = $children.concat($children_links);                    
                    
                }
                
            }

            
            if($mode<5 || $recursion_depth==0){
                $res['children'] = $children;
            }

            return $res;
            
        } //__getRecordTypeTree

    /*
    $dtID   - detail type ID
    $dtValue - record type structure definition
    returns display name  or if enum array
    $mode - 3 all, 4, 5 for treeview (5 lazy) , 6 - for import csv(dependencies)
    */
    function __getDetailSection($recTypeId, $dtID, $recursion_depth, $mode, $fieldtypes, $reverseRecTypeId, $pointer_fields){

        $res = null;

        var $dtValue = $Db.rst($recTypeId, $dtID);

        var $detailType = $Db.dty($dtID,'dty_Type');
        
        
        var $dt_label   = $dtValue['rst_DisplayName'];
        var $dt_title   = $dtValue['rst_DisplayName'];
        var $dt_tooltip = $dtValue['rst_DisplayHelpText']; //help text
        var $dt_conceptcode   = $Db.getConceptID('dty', $dtID);
        var $dt_display_order = $dtValue['rst_DisplayOrder'];
        
        var $pointerRecTypeId = ($dtID==DT_PARENT_ENTITY)?$dtValue['rst_PtrFilteredIDs']:$Db.dty($dtID,'dty_PtrTargetRectypeIDs');
        if(window.hWin.HEURIST4.util.isnull($pointerRecTypeId)) $pointerRecTypeId = '';
        

        var $pref = "";
        
        if (($mode==3) || $fieldtypes.indexOf('all')>=0 
            || window.hWin.HEURIST4.util.findArrayIndex($detailType, $fieldtypes)>=0) //$fieldtypes - allowed types
        {

        var $res = null;
            
        switch ($detailType) {
            case 'separator':
                return null;
            case 'enum':

                $res = {};
                if($mode==3){
                    /* todo ????
                    $res['children'] = []
                        array("text"=>"internalid"),
                        array("text"=>"code"),
                        array("text"=>"term"),
                        array("text"=>"conceptid"));
                    */    
                }
                break;

            case 'resource': // link to another record type
            case 'relmarker':
            
                var $max_depth = 2;
                if ($mode==4) //$mode==6 || 
                   $max_depth = 3;
                else if ($mode==5 || $mode==6) //make it 1 for lazy load
                   $max_depth = 1; 
                                                                
                if($recursion_depth<$max_depth){
                    
                    if($reverseRecTypeId!=null){
                            var $res = __getRecordTypeTree($recTypeId, $recursion_depth+1, $mode, $fieldtypes, $pointer_fields);
                            if($res){
                                $res['rt_ids'] = $recTypeId; //list of rectype - constraint
                                //$res['reverse'] = "yes";
                                $pref = ($detailType=="resource")?"lf":"rf";

                                $dt_title = "<span>&lt;&lt; <span style='font-weight:bold'>" 
                                        + $Db.rty($recTypeId, 'rty_Name') + "</span> . " + $dt_title + '</span>';
                                
                                if($mode==5 || $mode==6){
                                    $res['lazy'] = true;
                                }
                                $res['isreverse'] = 1;
                            }
                    }else{

                            var $pref = ($detailType=="resource")?"lt":"rt";

                            var $is_required = ($dtValue['rst_RequirementType']=='required');
                            var $rectype_ids = $pointerRecTypeId.split(",");
                             
                            if($mode==4 || $mode==5 || $mode==6){
                                $dt_title = " <span style='font-style:italic'>" + $dt_title + "</span>";
                            }
                            
                            if($pointerRecTypeId=="" || $rectype_ids.length==0){ //unconstrainded
                                                    //
                                $res = __getRecordTypeTree( null, $recursion_depth+1, $mode, $fieldtypes, $pointer_fields);
                                //$res['constraint'] = 0;

                            }else{ //constrained pointer

                                $res = {};

                                if($rectype_ids.length>1){
                                    $res['rt_ids'] = $pointerRecTypeId; //list of rectype - constraint
                                    $res['constraint'] = $rectype_ids.length;
                                    if($mode<5) $res['children'] = array();
                                }
                                if($mode==5 || $mode==6){
                                    $res['rt_ids'] = $pointerRecTypeId;
                                    $res['lazy'] = true;
                                }else{
                                
                                    for (var k in $rectype_ids){
                                        var $rtID = $rectype_ids[k];
                                        $rt_res = __getRecordTypeTree($rtID, $recursion_depth+1, $mode, $fieldtypes, $pointer_fields);
                                        if($rectype_ids.length==1){//exact one rectype constraint
                                            //avoid redundant level in tree
                                            $res = $rt_res;
                                            $res['constraint'] = 1;
                                            $res['rt_ids'] = $pointerRecTypeId; //list of rectype - constraint
                                        }else if($rt_res!=null){
                                            $res['children'].push($rt_res);
                                            $res['constraint'] = $rt_res.length;
                                        }
                                    }
                                
                                }
                            
                            }
                            $res['required'] = $is_required;

                    }
                }

                break;

            default:
                    $res = {};
        }//end switch
        }

        if($res!=null){

            if(window.hWin.HEURIST4.util.isnull($res['code'])){
              $res['code'] = (($reverseRecTypeId!=null)?$reverseRecTypeId:$recTypeId)+":"+$pref+$dtID;  //(($reverseRecTypeId!=null)?$reverseRecTypeId:$recTypeId)  
            } 
            $res['key'] = "f:"+$dtID;
            if($mode==4 || $mode==5 || $mode==6){
                    
                var $stype = ($detailType=='resource' || $detailType=='relmarker')?"":$Db.baseFieldType[$detailType];
                if($reverseRecTypeId!=null){
                    //before 2017-06-20  $stype = $stype."linked from";
                    $res['isreverse'] = 1;
                }
                if($stype!=''){
                    $stype = " <span style='font-size:0.7em'>(" + $stype + ")</span>";   
                }
                
                $res['title'] = $dt_title + $stype;
                //$res['code'] = 
            }else{
                $res['title'] = $dt_title;    
            }
            $res['type'] = $detailType;
            $res['name'] = $dt_label;
            
            $res['display_order'] = $dt_display_order;
            
            $res['conceptCode'] = $dt_conceptcode;
            $res['dtyID_local'] = $dtID; //$Db.getLocalID('dty', $dt_conceptcode); for import
        }            
        return $res;
    }
    
    //
    // add parent code to children
    //
    function __assignCodes($def){
        
        for(var $idx in $def['children']){
            $det = $def['children'][$idx];
            if(!window.hWin.HEURIST4.util.isnull($def['code'])){

                if(!window.hWin.HEURIST4.util.isnull($det['code'])){
                    $def['children'][$idx]['code'] = $def['code'] + ":" + $det['code']; 
                }else{
                    $def['children'][$idx]['code'] = $def['code'];    
                }
            }
            //debug $def['children'][$idx]['title'] = $def['children'][$idx]['code'].$det['title']; 
                 
            if($.isArray($det['children'])){
                   $def['children'][$idx] = __assignCodes($def['children'][$idx]);
            }
        }
        return $def;
    }
    //========================= end internal 
        
        if(fieldtypes==null){
            fieldtypes = ['integer','date','freetext','year','float','enum','resource','relmarker'];
        }else if(!$.isArray(fieldtypes) && fieldtypes!='all'){
            fieldtypes = fieldtypes.split(',');
        }
        
        var res = [];

        if($mode==5){ //with reverse links
            
            var def = __getRecordTypeTree(rectypeids, 0, $mode, fieldtypes, null);

            if(def!==null) {
                if(parentcode!=null){
                    if(def['code']){
                        def['code'] = parentcode+':'+def['code'];
                    }else{
                        def['code'] = parentcode;
                    }
                }
                if($.isArray(def['children'])){
                    def = __assignCodes(def);
                    res.push( def );
                }                    
            }
        
        } else {
        
            rectypeids = (!$.isArray(rectypeids)?rectypeids.split(','):rectypeids);    
            
            
            //create hierarchy tree 
            for (var k=0; k<rectypeids.length; k++) {
                var rectypeID = rectypeids[k];
                var def = __getRecordTypeTree(rectypeID, 0, $mode, fieldtypes, null);
                
                    if(def!==null) {
                        if(parentcode!=null){
                            if(def['code']){
                                def['code'] = parentcode+':'+def['code'];
                            }else{
                                def['code'] = parentcode;
                            }
                        }
                        //debug $def['title'] = @$def['code'].$def['title'];   
                        //asign codes
                        if($.isArray(def['children'])){
                            def = __assignCodes(def);
                            res.push( def );
                        }                    
                    }
            }
            
        }

        return res;    
        
    },    
    
    // use in search_faceted.js  
    //
    // returns array of record types that are resources for given record type
    //    {'linkedto':[],'relatedto':[]}
    // need_separate - returns separate array for linked and related 
    //
    getLinkedRecordTypes: function ($rt_ID, db_structure, need_separate){
        
        if(!db_structure){
            db_structure = window.hWin.HEURIST4;
        }
        
        var $dbs_rtStructs = db_structure.rectypes;
        //find all DIREreverse links (pointers and relation that point to selected rt_ID)
        var $alldetails = $dbs_rtStructs['typedefs'];
        var $fi_type = $alldetails['dtFieldNamesToIndex']['dty_Type'];
        var $fi_rectypes = $alldetails['dtFieldNamesToIndex']['rst_PtrFilteredIDs'];
        
        var $arr_rectypes = [];
        var res = {'linkedto':[],'relatedto':[]};
        
        var $details = $dbs_rtStructs['typedefs'][$rt_ID]['dtFields'];
        if($details) {
            for (var $dtID in $details) {
                
                var $dtValue = $details[$dtID];
        
                if(($dtValue[$fi_type]=='resource' || $dtValue[$fi_type]=='relmarker')){

                        //find constraints
                        var $constraints = $dtValue[$fi_rectypes];
                        if(!window.hWin.HEURIST4.util.isempty($constraints)){
                            $constraints = $constraints.split(",");
                            //verify record type exists
                            if($constraints.length>0){
                                for (var i=0; i<$constraints.length; i++) {
                                    var $recTypeId = $constraints[i];
                                    if( !$arr_rectypes[$recTypeId] && 
                                        $dbs_rtStructs['typedefs'][$recTypeId]){
                                            
                                            $arr_rectypes.push( $recTypeId );
                                            
                                            if(need_separate){
                                                var t1 = ($dtValue[$fi_type]=='resource')?'linkedto':'relatedto';
                                                res[t1].push( $recTypeId );
                                            }
                                    }
                                }                            
                            } 
                        }
                }
            }
        }
        
        return  need_separate ?res :$arr_rectypes;
        
    },

    //
    // returns true if rectype has a field in its structure
    // fieldtype - base field type
    //
    hasFields: function( $rt_ID, fieldtype, db_structure ){
        
        var is_exist = false;
        
        $Db.rst(rty_ID).each2(function(dty_ID, record){
            if($Db.dty(dty_ID,'dty_Type')==fieldtype){
                is_exist = true;
                return false;
            }
        });
        
        return is_exist;
    },

    //--------------------------------------------------------------------------
    
    /*
    shortcuts for working wit db definitions
    
    $Db = window.hWin.HEURIST4.dbs
    
    rty,dty,rst,rtg,dtg,trm = dbdef(entityName,....)  access hEntityMgr.entity_data[entityName]
    
    set(entityName, id, field, newvalue)    
        id - localcode or concept code. For rst this are 2 params rtyID, dtyID
        field - field name. If empty returns entire record
        newvalue - assign value of field
    
    */
    
    rtg: function(rec_ID, fieldName, newValue){
        return $Db.getset('defRecTypeGroups', rec_ID, fieldName, newValue);        
    },

    dtg: function(rec_ID, fieldName, newValue){
        return $Db.getset('defDetailTypeGroups', rec_ID, fieldName, newValue);        
    },

    vcg: function(rec_ID, fieldName, newValue){
        return $Db.getset('defVocabularyGroups', rec_ID, fieldName, newValue);        
    },
    
    rty: function(rec_ID, fieldName, newValue){
        return $Db.getset('defRecTypes', rec_ID, fieldName, newValue);        
    },

    dty: function(rec_ID, fieldName, newValue){
        return $Db.getset('defDetailTypes', rec_ID, fieldName, newValue);        
    },

    trm: function(rec_ID, fieldName, newValue){
        return $Db.getset('defTerms', rec_ID, fieldName, newValue);        
    },
    
    //
    //  get structures for all record types
    //
    rst_idx2: function(){
        return window.hWin.HAPI4.EntityMgr.getEntityData2('rst_Index');
    },
    
    //
    // Returns
    // direct:   rty_ID:[{all:[],dty_ID:[rty_ID,rty_ID,....],  }]
    // reverse:
    // reverse_parent:
    // rel_direct:
    // rel_reverse:
    //
    // forbidden fields are ignored
    //
    rst_links: function(){

        var rst_reverse_parent = {};  //linked FROM rectypes as a child (list of parent rectypes)
        var rst_reverse = {};    //linked FROM rectypes
        var rst_direct = {};     //linked TO rectypes

        var rst_rel_reverse = {};    //linked FROM rectypes
        var rst_rel_direct = {};     //linked TO rectypes

      
        var is_parent = false;
        var all_structs = $Db.rst_idx2();
        for (var rty_ID in all_structs){
            var recset = all_structs[rty_ID];
            recset.each2(function(dty_ID, record){

                //links
                var dty_Type = $Db.dty(dty_ID, 'dty_Type');
                if((dty_Type=='resource' || dty_Type=='relmarker') 
                    && record['rst_RequirementType']!='forbidden')
                {
                    is_parent = false;
                    
                    var ptr = $Db.dty(dty_ID, 'dty_PtrTargetRectypeIDs');
                    if(ptr) ptr = ptr.split(',');
                    if(ptr && ptr.length>0){
                        
                            var direct;
                            var reverse;
                    
                            if(dty_Type=='resource'){
                                //LINK
                                is_parent = (record['rst_CreateChildIfRecPtr']==1);
                                
                                direct = rst_direct;
                                reverse = rst_reverse;
                            }else{
                                //RELATION
                                direct = rst_rel_direct;
                                reverse = rst_rel_reverse;
                            }      
                            
                            
                            if(!direct[rty_ID]) direct[rty_ID] = {all:[]};  
                            direct[rty_ID][dty_ID] = ptr;

                            for(var i=0; i<ptr.length; i++){
                                
                                var target_rty = ptr[i];
                                
                                //all rectypes that is referenced FROM rty_ID
                                if(direct[rty_ID].all.indexOf(target_rty)<0){
                                    direct[rty_ID].all.push(target_rty);   
                                }    
                                
                                // reverse links
                                if(!reverse[target_rty]) reverse[target_rty] = {all:[]};  

                                //all rectypes that refer TO rty_ID
                                if(reverse[target_rty].all.indexOf(rty_ID)<0){
                                    reverse[target_rty].all.push(rty_ID);        
                                    
                                    if(is_parent){
                                        if(!rst_reverse_parent[target_rty]) rst_reverse_parent[target_rty] = [];
                                        if(rst_reverse_parent[target_rty].indexOf(rty_ID)<0){
                                                rst_reverse_parent[target_rty].push(rty_ID);
                                        }
                                    }
                                }
                                if(!reverse[target_rty][dty_ID]) reverse[target_rty][dty_ID] = [];
                                reverse[target_rty][dty_ID].push(rty_ID)

                            }//for constraints
                    }
                }                

            });
        }
        
        return {
            parents: rst_reverse_parent,
            reverse: rst_reverse,
            direct: rst_direct,

            rel_reverse: rst_rel_reverse,
            rel_direct: rst_rel_direct
        };
        
    },
    
    
    rst: function(rec_ID, dty_ID, fieldName, newValue){
        
        //fieldnames for backward capability
        var dfname = null;
        if(fieldName) dfname = $Db.rst_to_dtyField( fieldName );
        if(dfname){
            return $Db.dty(dty_ID, dfname);
        }else{
            
            var rectype_structure = window.hWin.HAPI4.EntityMgr.getEntityData2('rst_Index');
            
            if(rectype_structure && rectype_structure[rec_ID]){
                if(dty_ID>0){
                    return $Db.getset(rectype_structure[rec_ID], dty_ID, fieldName, newValue);                
                }else{
                    return rectype_structure[rec_ID];            
                }
            }
        }
        return null
        
    },
    
    
    getset: function(entityName, rec_ID, fieldName, newValue){
        if(typeof newValue == 'undefined'){
            return $Db.get(entityName, rec_ID, fieldName);        
        }else{
            $Db.set(entityName, rec_ID, fieldName, newValue);        
            return null;
        }
    },
    
    //
    // returns 
    // recordset if rec_ID not defined
    // record - as object if fieldName not defined
    //    
    get: function (entityName, rec_ID, fieldName){
        //it is assumed that db definitions ara always exists on client side
        var recset =  window.hWin.HEURIST4.util.isRecordSet(entityName)?entityName
                        :window.hWin.HAPI4.EntityMgr.getEntityData(entityName); 
        
        if(rec_ID>0){
            
            if(fieldName){
                return recset.fld(rec_ID, fieldName);
            }else{
                return recset.getRecord(rec_ID); //returns JSON {fieldname:value,....}
            }
            
        }else{
            return recset;
        }
        
    },

    //
    // assign value of field OR entire record
    //
    set: function (entityName, rec_ID, fieldName, newValue){

        if(rec_ID>0){
        
            var recset =  window.hWin.HEURIST4.util.isRecordSet(entityName)
                            ?entityName
                            :window.hWin.HAPI4.EntityMgr.getEntityData(entityName); 
            
            if(fieldName){
                recset.setFldById(rec_ID, fieldName, newValue);
            }else{
                recset.addRecord(rec_ID, newValue);
            }
            
        }
    },
    
    //
    // Some fields in rectype structure are taken from basefield (dty) directly
    //
    rst_to_dtyField: function(fieldName)
    {
        var dfname = null;
        if(fieldName=='rst_FilteredJsonTermIDTree') dfname='dty_JsonTermIDTree'
        else if(fieldName=='rst_PtrFilteredIDs') dfname='dty_PtrTargetRectypeIDs'
        //else if(fieldName=='rst_TermIDTreeNonSelectableIDs') dfname='dty_TermIDTreeNonSelectableIDs' //not used anymore
        else if( //fieldName=='dty_TermIDTreeNonSelectableIDs' || fieldName=='dty_FieldSetRectypeID' || 
                fieldName=='dty_Type')
        {
            dfname = fieldName; 
        } 
        
        return null;
    },
    
/*    
    //
    //
    //
    rst_set: function(rty_ID, dty_ID, fieldName, value){
        
        var dfname = $Db.rst_to_dtyField( fieldName );
        
        if(dfname){
            $Db.dty( dty_ID, dfname, value );
        }else{
        
            var recset = window.hWin.HAPI4.EntityMgr.getEntityData('defRecStructure');
            var details = window.hWin.HAPI4.EntityMgr.getEntityData2('rst_Index'); 
            if(details[rty_ID]){
                var rst_ID = details[rty_ID][dty_ID];    
                if(rst_ID>0){
                    recset.setFldById(rst_ID, fieldName, newValue);
                }else{
                    //add new basefield
                    rst_ID = recset.addRecord3({fieldName: newValue});
                    details[rty_ID][dty_ID] = rst_ID;
                }
            }
        }
    },
    //  
    // special behavior for defRecStructure
    // it returns value for given field or entire recstrucure field
    //    
    rst_idx: function(rty_ID, dty_ID, fieldName){
        
        var recset = window.hWin.HAPI4.EntityMgr.getEntityData('defRecStructure'); 
        
        if(rty_ID>0){
            
            //rty_ID:{dty_ID:rstID, ..... }
            var details = window.hWin.HAPI4.EntityMgr.getEntityData2('rst_Index');
            
            if(!details || !details[rty_ID]){
                return null;
            }else if(dty_ID>0){
                var rst_ID = details[rty_ID][dty_ID];
                
                if(!(rst_ID>0)){
                    return null;
                }else if(fieldName){
                    
                    //for backward capability
                    var dfname = $Db.rst_to_dtyField( fieldName );
                    if(dfname){
                        return $Db.dty(dty_ID, dfname);
                    }else{
                        return recset.fld(rst_ID, fieldName);        
                    }
                    
                }else{
                    return recset.getRecord(rst_ID); //json for paticular detail
                }
            }else{
                return details[rty_ID]; //array of dty_ID:rst_ID
            }
            
        }else{
            return recset;
        }
        //create group
        
        //return $Db.getset('defRecStructure', rec_ID, fieldName, newValue);        
    },
*/    
    //
    // find by concept code in local definitions
    //
    // entities - prefix for rectypes, detailtypes, terms - rty, dty, trm
    //
    // return local id or zero if not found
    //
    getLocalID: function(entity, concept_code){

        var findID = 0;
        var codes = null;
        
        if(typeof concept_code == 'string' && concept_code.indexOf('-')>0)
        {
            codes = concept_code.split('-');
            if(codes.length==2 && 
                (parseInt(codes[0])==0 || codes[0]==window.hWin.HAPI4.sysinfo['db_registeredid']) )
            {
                findID = codes[1];
            }
        }else if(parseInt(concept_code)>0){
            findID = concept_code;    
        }
        
        if(findID>0 && $Db[entity](findID)){
            return findID; 
        }
        
        if(codes && codes.length==2){
        
            var f_dbid = entity+'_OriginatingDBID';
            var f_id = entity+'_IDInOriginatingDB';
            
            var recset = $Db[entity]();
            recset.each2( function(id, record){
                if(record[f_dbid]==codes[0] && record[f_id]==codes[1]){
                    findID = id;
                    return false;
                }
            });
            
        }
        
        return findID;
    },
    
    //
    // get concept code by local id
    //
    getConceptID: function(entity, local_id){
        
        var rec = $Db[entity](local_id);
        if(rec!=null){
            var dbid = rec[entity+'_OriginatingDBID'];
            var id = rec[entity+'_IDInOriginatingDB'];
            if(parseInt(dbid)>0 && parseInt(id)>0){
                return dbid+'-'+id;
            }else if( window.hWin.HAPI4.sysinfo['db_registeredid']>0 ){
                return window.hWin.HAPI4.sysinfo['db_registeredid']+'-'+local_id;
            }else{
                return '0000-'.local_id;
            }
        }else{
            return '';
        }
    
    },

    //
    // returns true if term belongs to vocabulary (including by reference)
    //
    trm_InVocab: function(vocab_id, term_id){
        
        var all_terms = $Db.trm_TreeData(vocab_id, 'set');
        
        return (window.hWin.HEURIST4.util.findArrayIndex(term_id, all_terms)>=0);
    },
    
    //
    // Returns hierarchy for given vocabulary as a flat array, recordset or tree data
    // (it uses trm_Links)
    // vocab_id - id or "relation"
    // mode - 0, flat - returns recordset, 
    //        1, tree - returns treedata for fancytree
    //        2, select - return array of options for selector {key: title: depth: is_vocab}
    //        3, set  - array of ids 
    //        4, labels - flat array of labels in lower case 
    //
    trm_TreeData: function(vocab_id, mode){
        
        var recset = window.hWin.HAPI4.EntityMgr.getEntityData('defTerms');
        //parent:[children]
        var t_idx = window.hWin.HAPI4.EntityMgr.getEntityData('trm_Links'); 
        var trm_ids = [];
        var res = {};
        
        if(window.hWin.HEURIST4.util.isNumber(mode)){
            if(mode==1) mode='tree'
            else if(mode==2) mode='select'
            else if(mode==3) mode='set'
            else if(mode==4) mode='labels'
            else mode='flat';
        }
        

        function __addChilds(recID, lvl_parents, include_vocab){
        
            //recID = parseInt(recID);
            var node = {title: recset.fld(recID, 'trm_Label'), key: recID};
            
            if(include_vocab && lvl_parents==0){
                node.is_vocab = true;
                trm_ids.push({title: recset.fld(recID, 'trm_Label'), 
                                is_vocab: true,
                                key: recID, depth:lvl_parents});
            }

            var children = t_idx[recID]; //array of children ids trm_Links
            
            if(children && children.length>0){

                //sort children by name
                children.sort(function(a,b){
                    return recset.fld(a,'trm_Label').toLowerCase()<recset.fld(b,'trm_Label').toLowerCase()?-1:1;        
                });
                
                if(mode=='tree'){

                    var child_nodes = [];  
                    for(var i=0; i<children.length;i++){  
                        child_nodes.push( __addChilds(children[i]) );          
                    }
                    node['children'] = child_nodes;
                    node['folder'] = true;

                }else if(mode=='select'){

                    for(var i=0; i<children.length;i++){ 
                        recID = children[i];
                        trm_ids.push({title: recset.fld(recID, 'trm_Label'), 
                                      code: recset.fld(recID, 'trm_Code'),
                                      key: recID, 
                                      depth: lvl_parents});
                        __addChilds(recID, lvl_parents+1);
                    }

                }else if(mode=='set' || mode=='labels'){
                    
                    for(var i=0; i<children.length;i++){  
                        recID = children[i];
                        trm_ids.push(mode=='labels'?recset.fld(recID, 'trm_Label').toLowerCase() 
                                                   :recID);
                        __addChilds(recID);
                    }
                    
                }else{ //gather ids onlys - for recordset

                    lvl_parents = lvl_parents?lvl_parents.split(','):[];
                    lvl_parents.push(recID);

                    for(var i=0; i<children.length;i++){  
                        recID = children[i];
                        trm_ids.push(recID);

                        recset.setFldById(recID, 'trm_Parents', lvl_parents.join(','));
                        __addChilds(recID, lvl_parents.join(','));
                    }

                }
            }
            
            return node;
        }
        
        if(vocab_id=='relation'){
            //find all vocabulary with domain "relation"
            var vocab_ids = $Db.trm_getVocabs('relation');
            for (var i=0; i<vocab_ids.length; i++){
                var trm_ID = vocab_ids[i];
                res = __addChilds(trm_ID, 0, true);
            }
            
        }else{
            res = __addChilds(vocab_id, 0, false);
        }
        
        if(mode=='tree'){
            return res['children'];
        }else if(mode=='select'){
            return trm_ids;
        }else if(mode=='set' || mode=='labels'){
            return trm_ids;
        }else{
            return recset.getSubSetByIds(trm_ids);
        }
        
    },
    
    //
    // is given term has children
    //
    trm_HasChildren: function(trm_id){
        var t_idx = window.hWin.HAPI4.EntityMgr.getEntityData('trm_Links'); 
        var children = t_idx[recID];
        return (children && children.length>0);
    },


    //
    // get all vocabularies OR for given domain
    //
    trm_getVocabs: function(domain){

        var t_idx = window.hWin.HAPI4.EntityMgr.getEntityData('trm_Links'); 
        var res = [];
        var parents = Object.keys(t_idx);
        for (var i=0; i<parents.length; i++){ //first level
            var trm_ID = parents[i];
            var trm_ParentTermID = $Db.trm(trm_ID, 'trm_ParentTermID');
            if(!(trm_ParentTermID>0)){
                if(!domain || $Db.trm(trm_ID, 'trm_Domain')==domain)
                    res.push(trm_ID);    
            }
        }
        
        return res;
    },
    
    //
    // get array of vocabularies by reference
    // (where the given term directly or by referecne belongs to)
    //
    trm_getAllVocabs: function(trm_id){
        var t_idx = window.hWin.HAPI4.EntityMgr.getEntityData('trm_Links'); 
        
        var res = [];
        var parents = Object.keys(t_idx);
        for (var i=0; i<parents.length; i++){
            var parent_ID = parents[i];
            var k = window.hWin.HEURIST4.util.findArrayIndex(trm_id, t_idx[parent_ID]);
            if(k>=0){
                var trm_ParentTermID = $Db.trm(parent_ID, 'trm_ParentTermID');
                if(trm_ParentTermID>0){
                    res = res.concat($Db.trm_getAllVocabs(parent_ID));
                }else{
                    //vocabulary!
                    res.push( parent_ID );     
                }
            }
        }
        return res;
    },
    
    //
    // remove any mention of term from hierarchy (trm_Links)
    //
    trm_RemoveLinks: function(trm_id){
        var t_idx = window.hWin.HAPI4.EntityMgr.getEntityData('trm_Links'); 
        var parents = Object.keys(t_idx);
        var i = 0;
        while(i<parents.length){
            if(parents[i]==trm_id){
                delete parents[i];   
            }else{
                var k = window.hWin.HEURIST4.util.findArrayIndex(trm_id, t_idx[parents[i]]);
                if(k>=0){
                    t_idx[parents[i]].splice(k,1);
                }
                i = i +1;
            }
        }
    },
        
    //--------------------------------------------------------------------------
    //
    //
    //
    applyOrder: function(recordset, prefix, callback){

        var entityName = recordset.entityName;
        var fieldId    = prefix+'_ID'; 
        var fieldOrder = prefix+'_Order';
        
        //assign new value for vcg_Order and save on server side
        var rec_order = recordset.getOrder();
        var idx = 0, len = rec_order.length;
        var fields = [];
        for(; (idx<len); idx++) {
            var record = recordset.getById(rec_order[idx]);
            var oldval = recordset.fld(record, fieldOrder);
            var newval = String(idx+1).lpad(0,3);
            if(oldval!=newval){
                recordset.setFld(record, fieldOrder, newval);        
                var fld = {};
                fld[fieldId] = rec_order[idx];
                fld[fieldOrder] = newval;
                fields.push(fld);
            }
        }
        if(fields.length>0){

            var request = {
                'a'          : 'save',
                'entity'     : entityName,
                'request_id' : window.hWin.HEURIST4.util.random(),
                'fields'     : fields                     
            };

            window.hWin.HAPI4.EntityMgr.doRequest(request, 
                function(response){
                    if(response.status == window.hWin.ResponseStatus.OK){
                        if($.isFunction(callback)) callback.call();
                    }else{
                        window.hWin.HEURIST4.msg.showMsgErr(response);
                    }
            });

        }else{
            if($.isFunction(callback)) callback.call();
        }
    }
    

}//end dbs

}
//alias
var $Db = window.hWin.HEURIST4.dbs;    

