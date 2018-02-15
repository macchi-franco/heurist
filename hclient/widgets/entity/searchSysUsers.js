/**
* Search header for manageSysUsers manager
*
* @package     Heurist academic knowledge management system
* @link        http://HeuristNetwork.org
* @copyright   (C) 2005-2016 University of Sydney
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

$.widget( "heurist.searchSysUsers", $.heurist.searchEntity, {

    //
    _initControls: function() {
        
        var that = this;
        
        this.input_search_group = this.element.find('#input_search_group');   //user group
        if(!window.hWin.HAPI4.currentUser.usr_GroupsList){
            window.hWin.HEURIST4.ui.createUserGroupsSelect(this.input_search_group[0], null, [{key:'any',title:'any group'}],
                function(){ that._initControls() });
            return;    
        }else{
            window.hWin.HEURIST4.ui.createUserGroupsSelect(this.input_search_group[0], null, [{key:'any',title:'any group'}]);
        }
         
        
        this._super();
        
        this.btn_add_record = this.element.find('#btn_add_record');
        this.btn_find_record = this.element.find('#btn_find_record');

        if(this.options.edit_mode=='none'){
            this.btn_add_record.hide();
            this.btn_find_record.hide();
        }else{
            this.btn_add_record.css({'min-width':'9m','z-index':2})
                    .button({label: window.hWin.HR("Add New User"), icon: "ui-icon-plus"})
                .click(function(e) {
                    that._trigger( "onadd" );
                }); 

            this.btn_find_record.css({'min-width':'9m','z-index':2})
                    .button({label: window.hWin.HR("Find/Add User"), icon: "ui-icon-search"})
                .click(function(e) {
                    that._trigger( "onfind" );
                }); 
                
            //@todo proper alignment
            if(this.options.edit_mode=='inline'){
                this.btn_add_record.css({'float':'left','border-bottom':'1px lightgray solid',
                'min-height': '2.4em', 'margin-bottom': '0.4em'});    
            }                       
        }
        
        this.input_search_inactive = this.element.find('#input_search_inactive');
        this.input_search_role = this.element.find('#input_search_role');

        this._on(this.input_search_group,  { change:this.startSearch });
        this._on(this.input_search_role,  { change:this.startSearch });
        this._on(this.input_search_inactive,  { change:this.startSearch });
        
        if( this.options.ugl_GroupID>0 ){
            this.input_search_group.parent().hide();
            this.input_search_group.val(this.options.ugl_GroupID);
            
            this.input_search_role.parent().show();
            
            if(!window.hWin.HAPI4.is_admin()){
                this.btn_add_record.hide();
                this.btn_find_record.hide();
            }
        }else if( this.options.ugl_GroupID<0 ){  //addition of users to group
            //find any user not in given group
            //exclude this group from selector
            this.input_search_group.find('option[value="'+Math.abs(this.options.ugl_GroupID)+'"]').remove();
        }else{
            this.btn_find_record.hide();
        }
             
        this.input_sort_type = this.element.find('#input_sort_type');
        this._on(this.input_sort_type,  { change:this.startSearch });
                      
        this.startSearch();            
    },  

    
    //
    // public methods
    //
    startSearch: function(){
        
            this._super();
            
            var request = {}
        
            if(this.input_search.val()!=''){
                request['ugr_Name'] = this.input_search.val();
            }
            
            if( this.options.ugl_GroupID<0 ){
                //find any user not in given group
                request['not:ugl_GroupID'] = Math.abs(this.options.ugl_GroupID);
            }
        
            if(this.input_search_group.val()>0){
                
                request['ugl_GroupID'] = this.input_search_group.val();
                
                this.input_search_role.parent().show();

                var gr_role = this.input_search_role.val();
                if(gr_role!='' && gr_role!='any'){
                    
                    if(gr_role=='admin'){
                        request['ugl_Role'] = 'admin';
                    }else
                    if(gr_role=='member'){  
                        request['ugl_Role'] = 'member';
                    }
                }
                
                if( ( window.hWin.HAPI4.has_access(this.input_search_group.val())>0 )
                    && this.options.edit_mode!='none'){
                    this.btn_find_record.show();
                }
            }else{
                this.input_search_role.parent().hide();
                this.btn_find_record.hide(); 
            }
            
            if(this.input_search_inactive.is(':checked')){
                request['ugr_Enabled'] = 'n';
            }
            
            
            this.input_sort_type = this.element.find('#input_sort_type');
            if(this.input_sort_type.val()=='lastname'){
                request['sort:ugr_LastName'] = '1' 
            }else if(this.input_sort_type.val()=='recent'){
                request['sort:ugr_ID'] = '-1' 
            }else{
                request['sort:ugr_Name'] = '1';   
            }
                 
            
/*
            if(this.element.find('#cb_selected').is(':checked')){
                request['ugr_ID'] = window.hWin.HAPI4.get_prefs('recent_Users');
            }
            if(this.element.find('#cb_modified').is(':checked')){
                var d = new Date(); 
                //d = d.setDate(d.getDate()-7);
                d.setTime(d.getTime()-7*24*60*60*1000);
                request['ugr_Modified'] = '>'+d.toISOString();
            }
*/            
            
            
            
            if(false && $.isEmptyObject(request)){
                this._trigger( "onresult", null, {recordset:new hRecordSet()} );
            }else{
                this._trigger( "onstart" );
        
                request['a']          = 'search'; //action
                request['entity']     = this.options.entity.entityName;
                request['details']    = 'id'; //'id';
                request['request_id'] = window.hWin.HEURIST4.util.random();
                
                //we may search users in any database
                request['db']     = this.options.database;

                //request['DBGSESSID'] = '423997564615200001;d=1,p=0,c=0';

                var that = this;                                                
           
                window.hWin.HAPI4.EntityMgr.doRequest(request, 
                    function(response){
                        if(response.status == window.hWin.HAPI4.ResponseStatus.OK){
                            that._trigger( "onresult", null, 
                                {recordset:new hRecordSet(response.data), request:request} );
                        }else{
                            window.hWin.HEURIST4.msg.showMsgErr(response);
                        }
                    });
                    
            }            
    }
});
