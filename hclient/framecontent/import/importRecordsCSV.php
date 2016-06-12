<?php

    /**
    * Import terms from CSV
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

define('LOGIN_REQUIRED',1);
define('PDIR','../../../');    

require_once(dirname(__FILE__)."/../initPage.php");

$post_max_size = get_config_bytes(ini_get('post_max_size'));
$file_max_size = get_config_bytes(ini_get('upload_max_filesize'));

function fix_integer_overflow($size) {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
}
function get_config_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return fix_integer_overflow($val);
}
?>
        <script type="text/javascript" src="importRecordsCSV.js"></script>

        <script type="text/javascript">

            var importRecordsCSV;
            // Callback function after initialization
            function onPageInit(success){
                if(success){
                    var max_size = Math.min(<?php echo $file_max_size;?>, <?php echo $post_max_size;?>);
                    importRecordsCSV = new hImportRecordsCSV(
                                top.HEURIST4.util.getUrlParameter('imp_ID', window.location.search), 
                                max_size);
                }
            }
        
        
        </script>
        <style type="text/css">
            .subh
            {
                border-left: 1px solid lightgray;
                border-bottom: 1px solid lightgray;
                border-top: 1px solid lightgray;
            }
            .tbmain th
            {
                border-left: 1px solid lightgray;
                border-bottom: none;
                padding:2px;
            }
            .tbmain td
            {
                border-left: 1px solid lightgray;
                padding:2px;
            }
            .tbmain
            {
                border-top: 1px solid gray;
                border-right: 1px solid gray;
                border-bottom: 1px solid gray;
                border-left: 1px solid gray;
                font-size: 1em;
            }        
            .tbpreview{
                font-size:0.9em;
                margin:10px;                
            }
            .tbpreview, .tbpreview td
            {
                border: 1px solid lightgray;
            }
            .tbfields{
               font-size: 0.9em;
               border-collapse: collapse;
               text-align: left;
            }
            .tbfields td, .tbpreview th{
                padding: 2px;
            }
            .tbresults, .tbresults td{
               font-size: 1em;
               border:none;
            }
            .action_buttons{
               position: absolute;
               right:20px;
               top:2px;
            }   
        </style>
    </head>

    <!-- HTML -->
    <body class="ui-heurist-bg-light" style="overflow:hidden;">

<!-- STEP 1 upload data/select session -->    
<div style="width:100%; height:100%;" id="divStep1">
    <div class="ent_header" style="height:16em;">
        <h2 style="display:inline-block;padding:5px;width:230px;text-align:right;">Select previously uploaded file</h2>
            <select id="selImportId" class="text ui-widget-content ui-corner-all"></select>
            <a href="#" id="btnClearAllSessions"
                            style="margin-right: 10px;">Clear all files</a>        

        <h2 style="padding:10 0 10 80">OR</h2>
        <h2 style="display:inline-block;padding:5px;width:230px;text-align:right;">Upload new file (CSV/TSV)</h2>
            <input type="file" id="uploadFile" style="display:none">
            <div id="btnUploadFile">Upload File</div>
        
        <h2 style="padding:10 0 10 80">OR</h2>
        <h2 style="display:inline-block;padding:5px;width:230px;text-align:right;">Paste delimited data in area below</h2>
        <div id="btnUploadData">Upload Data</div>
    </div>
    <textarea id="sourceContent" class="ent_content_full" style="top:14em;width:100%;resize:none">
    
    </textarea>
</div>
<!-- STEP 2 parse uploaded data -->
<div style="width:100%; height:100%;display:none;" id="divStep2">
    <div class="ent_header" style="height:26em;">
    
        <div id="btnBackToStart2"
                title="Return to the upload screen to select a new delimited file to upload to the server for processing">
                Back to start</div>
    
        <h2 style="padding: 10px 0 10px;">Define parse parameters</h2>
        <fieldset style="display:inline-block;width:380px">
                <div>
                    <div class="header" style="min-width: 50px;"><label>Encoding:</label></div>
                    <div class="input-cell">        
                        <select id="csv_encoding" class="text ui-widget-content ui-corner-all" style="width:120px;">
<option>UTF-8</option>
<option>UTF-16</option>
<option>UTF-16BE</option>
<option>UTF-16LE</option>
<option>CP1251</option>
<option>CP1252</option>
<option>KOI8-R</option>
<option>UCS-4</option>
<option>UCS-4BE</option>
<option>UCS-4LE</option>
<option>UCS-2</option>
<option>UCS-2BE</option>
<option>UCS-2LE</option>
<option>UTF-32</option>
<option>UTF-32BE</option>
<option>UTF-32LE</option>
<option>UTF-7</option>
<option>UTF7-IMAP</option>
<option>ASCII</option>
<option>EUC-JP</option>
<option>SJIS</option>
<option>eucJP-win</option>
<option>SJIS-win</option>
<option>ISO-2022-JP</option>
<option>ISO-2022-JP-MS</option>
<option>CP932</option>
<option>CP51932</option>
<option>MacJapanese</option>
<option>SJIS-DOCOMO</option>
<option>SJIS-KDDI</option>
<option>SJIS-SOFTBANK</option>
<option>UTF-8-DOCOMO</option>
<option>UTF-8-KDDI</option>
<option>UTF-8-SOFTBANK</option>
<option>ISO-2022-JP-KDDI</option>
<option>JIS</option>
<option>JIS-ms</option>
<option>CP50220</option>
<option>CP50220raw</option>
<option>CP50221</option>
<option>CP50222</option>
<option>ISO-8859-1</option>
<option>ISO-8859-2</option>
<option>ISO-8859-3</option>
<option>ISO-8859-4</option>
<option>ISO-8859-5</option>
<option>ISO-8859-6</option>
<option>ISO-8859-7</option>
<option>ISO-8859-8</option>
<option>ISO-8859-9</option>
<option>ISO-8859-10</option>
<option>ISO-8859-13</option>
<option>ISO-8859-14</option>
<option>ISO-8859-15</option>
<option>byte2be</option>
<option>byte2le</option>
<option>byte4be</option>
<option>byte4le</option>
<option>BASE64</option>
<option>HTML-ENTITIES</option>
<option>7bit</option>
<option>8bit</option>
<option>EUC-CN</option>
<option>CP936</option>
<option>GB18030</option>
<option>HZ</option>
<option>EUC-TW</option>
<option>CP950</option>
<option>BIG-5</option>
<option>EUC-KR</option>
<option>UHC</option>
<option>ISO-2022-KR</option>
<option>CP866</option>
                    </select>
                    </div>
                </div>
                <div>
                    <div class="header" style="min-width: 50px;"><label>Field separator:</label></div>
                    <div class="input-cell">  
                        <select id="csv_delimiter" class="text ui-widget-content ui-corner-all" style="width:120px;">
                                <option value="," selected>comma</option>
                                <option value="tab">tab</option>
                                <option value=";">semicolon</option>
                        </select>                          
                    </div>
                </div>
                <div>
                    <div class="header" style="min-width: 50px;"><label>Fields enclosed in:</label></div>
                    <div class="input-cell">   
                        <select id="csv_enclosure" class="text ui-widget-content ui-corner-all" style="width:120px;">
                            <option selected value='2'>"</option><option value="1">'</option>
                        </select>                         
                    </div>
                </div>
                <div>
                    <div class="header" style="min-width: 50px;"><label>Line separator:</label></div>
                    <div class="input-cell">        
                        <select id="csv_linebreak" class="text ui-widget-content ui-corner-all" style="width:120px;">
                            <option selected value="auto">auto detect</option>
                            <option value="win">Windows</option>
                            <option value="nix">Unix</option>
                            <option value="mac">Mac</option>
                        </select>
                    </div>
                </div>
                <div>
                    <div class="header" style="min-width: 50px;"><label>Multivalue separator:</label></div>
                    <div class="input-cell">        
                        <select id="csv_mvsep" class="text ui-widget-content ui-corner-all" style="width:120px;">
                            <option value="|" selected>|</option>
                            <option value=";">;</option>
                            <option value=":">:</option>
                            <option value="/">/</option>
                            <!-- option value=",">,</option -->
                        </select>
                    </div>
                </div>
                <div>
                    <div class="header" style="min-width: 50px;"><label>Date format:</label></div>
                    <div class="input-cell">        
                        <select id="csv_dateformat" class="text ui-widget-content ui-corner-all" style="width:120px;">
                            <option selected value='1'>dd/mm/yyyy</option><option value="2">mm/dd/yyyy</option></select>
                        </select>
                        <div class="heurist-helper1" style="display: block;">
                            Also supports ISO yyyy-mm-dd (and optional hh:mm:ss)
                            and human friendly dates such as 1827, 1st Sept 1827, 1 sep 1827
                        </div>
                    </div>
                </div>
                
                
                <div id="btnParseStep1" style="position:absolute;bottom:2px;left:50px">Preview Parse</div>
        </fieldset>            
        <div style="position:absolute;width:520px;right:10px;top:3em;bottom:0">
                <div class="ent_header" style="border:none">
                    <h2>and Field roles as Identifiers and Dates</h2><br>
                    <table class="tbfields" style="font-weight:bold"><tr>
                                <td style="width:200px">Column</td>
                                <td style="width:50px;text-align:center">Is date?</td>
                                <td style="width:50px;text-align:center">Is key?</td>
                                <td style="width:200px">For record type</td></tr></table>
                </div>
                <div class="ent_content" id="divFieldRoles" style="top:3.5em;padding: 0.2em 0.5em;">
                    list of field roles
                </div>
                <div  class="ent_footer">
                    <div id="btnParseStep2" style="position:absolute;bottom:2px;right:10px">Parse and Proceed</div>
                </div>
        </div>
        
    </div>
    <div class="ent_content_full" style="top:26em;padding:0.5em" id="divParsePreview">
    
    </div>
</div>
<!-- STEP 3 matching and import -->
<div style="width:100%; height:100%;display:none;" id="divStep3">
    <div class="ent_header" style="height:11.5em;border:none;">
    
        <div>
            <div id="btnBackToStart"
                title="Return to the upload screen to select a new delimited file to upload to the server for processing">
                Back to start</div>
            <div id="btnDownloadFile"
                title="Download the data as currently displayed (including matching/IDs) to a new delimited file for further desktop editing">
                Download data to file</div>
                
            <div id="btnClearFile"  style="float: right;"
                title="Clear the data for this uploaded file from the server">
                Clear uploaded file</div>
                
            <div class="heurist-helper1" style="float: right; padding: 0.5em;">
                Note: Data is retained between sessions until cleared
            </div>    
    
        </div>
        <h2 class="step3" style="padding-top: 0.5em;">Step 1: Match fields and create Heurist IDs</h2>
        <h2 class="step4 step5" style="padding-top: 0.5em;display:none;">Step 2: Update records / Create new as required</h2>
        
        <fieldset>
        <div>
            <div class="header optional" style="min-width: 100px; width: 100px;"><label>Select record type:</label></div>
            <div class="input-cell">
                <div class="heurist-prompt ui-state-error" style="display: none; height: auto; padding: 0.2em; margin-bottom: 0.2em;"></div>
                <div class="input-div">
                    <select id="sa_rectype" class="text ui-widget-content ui-corner-all" style="width: 32ex;">
                    </select>
                </div>
                <div class="heurist-helper1" style="display: block;">
                    If a record type is not shown in the pulldown, check the 'Show' column in Database > Manage Structure
                </div>
            </div>        
        </div>                    
        </fieldset>
        <table class="tbmain" style="width:100%" cellspacing="0" cellpadding="2">
            <thead><tr> <!-- Table headings -->
                <th style="width:75px;">Use&nbsp;<br/>value</th>
                <th style="width:75px;">Unique&nbsp;<br/>values</th>
                <th style="width:300px;">Column</th>
                <th style="width:300px;">Column to Field Mapping</th>
                <!-- last column allows step through imported data records-->
                <th style="text-align: left;padding-left: 16px;">
                    <a href="#" class="navigation" style="display: inline-block;"><span data-dest="0" class="ui-icon ui-icon-seek-first"/></a>
                    <a href="#" class="navigation" style="display:inline-block;"><span data-dest="-1" class="ui-icon ui-icon-triangle-1-w"/></a>
                    <div style="display: inline-block;vertical-align: super;">Values in row <span id="current_row"></span></div>
                    <a href="#" class="navigation" style="display: inline-block;"><span data-dest="1" class="ui-icon ui-icon-triangle-1-e"/></a>
                    <a href="#" class="navigation" style="display: inline-block;"><span data-dest="last" class="ui-icon ui-icon-seek-end"/></a>
                </th></tr>
            </thead>
        </table>    
    </div>
    <div class="ent_content" style="padding: 0em 0.5em;bottom:16em;top:12.2em" id="divFieldMapping">
                <table id="tblFieldMapping" class="tbmain" style="width:100%" cellspacing="0" cellpadding="2">
                    <!-- <thead><tr>
                        <th style="width:75px;">Use&nbsp;<br/>value</th>
                        <th width="75px">Unique&nbsp;<br/>values</th>
                        <th width="300px">Column</th>
                        <th width="300px">Column to Field Mapping</th>
                        <th></th></tr></thead> -->
                    <tbody>
                    
                    </tbody>
                </table>    
    </div>
    <div class="ent_footer" style="height:16em;padding: 0em 0.5em;">

        <div id="divFieldMapping2"></div>
        
        <div id="divMatchingResult" style="display:none;">
            <table class="tbresults">
                <tbody>
                                    <tr><td width="130">Records matched</td>
                                        <td width="50" id="mr_cnt_update"></td>
                                        <td width="50" class="mr_update">rows:</td>
                                        <td width="50" class="mr_update" id="mr_cnt_update_rows"></td>
                                        <td width="50" class="mr_update"><a href="#" onclick="importRecordsCSV.showRecords('update')">show</a></td>
                                        <td width="50" class="mr_update"><a href="#" onclick="importRecordsCSV.downloadRecords('update')">download</a></td>
                                    </tr>
                                    <tr><td>New records to create</td>
                                        <td width="50" id="mr_cnt_insert"></td>
                                        <td width="50" class="mr_insert">rows:</td>
                                        <td width="50" class="mr_insert" id="mr_cnt_insert_rows"></td>
                                        <td width="50" class="mr_insert"><a href="#" onclick="importRecordsCSV.showRecords('insert')">show</a></td>
                                        <td width="50" class="mr_insert"><a href="#" onclick="importRecordsCSV.downloadRecords('insert')">download</a></td>
                                    </tr>
                                    <tr style="display:none"><td style="color:red">Ambiguous matches</td>
                                        <td>&nbsp;</td>
                                        <td>rows:</td>
                                        <td style="color:red" id="mr_cnt_disamb"></td>
                                        <td><a href="#" onclick="importRecordsCSV.showRecords('disamb')">show</a></td>
                                    </tr>
                                    <tr style="display:none"><td style="color:red">Field errors</td>
                                        <td>&nbsp;</td>
                                        <td>rows:</td>
                                        <td style="color:red" id="mr_cnt_error"></td>
                                        <td><a href="#" onclick="importRecordsCSV.showRecords('error')">show</a></td>
                                    </tr>
                </tbody>
            </table>
        </div>
    
        <div  id="divPrepareResult" style="display:none;">
            Prepare results
        </div>
        
        <div  id="divImportSetting" class="step5" style="position:absolute;top:3em;right:20px;display:none;">
            <input type="radio" checked="" name="sa_upd" id="sa_upd0" value="0" class="text" onchange="{importRecordsCSV.onUpdateModeSet()}">&nbsp;
            <label for="sa_upd0">Retain existing values and append distinct new data as repeat values
                (existing values are not duplicated)</label><br>

            <input type="radio" name="sa_upd" id="sa_upd1" value="1" class="text" onchange="{importRecordsCSV.onUpdateModeSet()}">&nbsp;
            <label for="sa_upd1">Add new data only if field is empty (new data ignored for non-empty fields)</label><br>

            <input type="radio" name="sa_upd" id="sa_upd2" value="2" class="text" onchange="{importRecordsCSV.onUpdateModeSet()}">&nbsp;
            <label for="sa_upd2">Add and replace all existing value(s) for the record with new data</label>
            
                    <div style="padding-left: 60px; font-size: 0.9em; vertical-align: top; display: none;" id="divImport2">
                        <input type="radio" checked="" name="sa_upd2" id="sa_upd20" value="0" class="text">&nbsp;
                        <label for="sa_upd20" style="font-size:0.9em;">Retain existing if no new data supplied for record</label><br>

                        <input type="radio" name="sa_upd2" id="sa_upd21" value="1" class="text">&nbsp;
                        <label for="sa_upd21" style="font-size:0.9em;">Delete existing even if no new data supplied for record</label>
                    </div>            
        </div>
    
        <div  id="divActionsMatching" class="action_buttons step3">
            
            <div id="btnMatchingSkip" class="normal"
                title="">
                Import as new (skip matching)</div>
            <div id="btnMatchingStart" class="normal"
                title="">
                Match against existing records</div>

            <div id="btnBackToMatching2" class="need_resolve"
                title="">
                Back: Match Again 2</div>
            <div id="btnResolveAmbiguous" class="need_resolve"
                title="">
                Resolve ambiguous matches</div>
            
        </div>
        
        <div  id="divActionsImport" style="display:none;" class="action_buttons step4 step5">
            <div id="btnBackToMatching"
                title="">
                Back: Match Again 1</div>
            <div id="btnPrepareStart" class="step4"
                title="">
                Prepare Insert/Update</div>
            <div id="btnImportStart" style="display:none" class="step5"
                title="">
                Start Insert/Update</div>
        </div>
        
    </div>
</div>

<div style="width:100%; height:100%;display:none;" id="divStep0" class="loading">

<div id="progressbar_div" style="width:80%;height:40px;padding:5px;text-align:center;margin:auto;margin-top:20%;display:none;">
    <div id="progressbar">
        <div class="progress-label">Loading data...</div>
    </div>
    <div id="progress_stop" style="text-align:center;margin-top:4px">Abort</div>
</div>

</div>

<div id="divPopupPreview" style="display:none">
</div>


</body>
</html>