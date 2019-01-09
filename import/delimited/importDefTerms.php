<?php

    /**
    * Import terms from CSV
    *
    * @package     Heurist academic knowledge management system
    * @link        http://HeuristNetwork.org
    * @copyright   (C) 2005-2019 University of Sydney
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
define('PDIR','../../');    

require_once(dirname(__FILE__)."/../../hclient/framecontent/initPage.php");
?>
        <script type="text/javascript" src="importDefTerms.js"></script>

        <script type="text/javascript">

            // Callback function after initialization
            function onPageInit(success){
                if(success){
                    var importDefTerms = new hImportDefTerms(window.hWin.HEURIST4.util.getUrlParameter('trm_ID',window.location.search));
                }
            }
        
        
        </script>
        <style>
            .tbmain td, .tbmain th
            {
                border-left: 1px solid lightgray;
                padding:3px;
                text-align:left;
            }
            .tbmain th
            {
                font-weight:bold;
                padding-top:6px;
            }
            .tbmain
            {
                border-collapse:collapse;
                font-size: 1em;
            }        
        </style>
        
    </head>

    <!-- HTML -->
    <body class="ui-heurist-bg-light" style="overflow:hidden;">
<div style="width:100%;height:60%;position:absolute;top:0">
<div style="position:absolute;left:0;right:400px; height:100%;  border-right:1px lightgray solid" id="divStep1">
    <div class="ent_header" style="height:5em;">
        <h2>Step 1</h2>
        <h2 style="padding-top:0.4em">Paste content in area below</h2>
        <h2 style="position:absolute;bottom:6px;">or</h2>
        <input type="file" id="uploadFile" style="display:none">
        <div id="btnUploadFile" style="position:absolute;bottom:6px;left:3em">Upload File</div>
        <div style="position:absolute;bottom:6px;left:13em;">encoding: 
<select id="csv_encoding" class="text ui-widget-content ui-corner-all" style="width:120px;font-size:0.9em">
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
                    </select></div>        
    </div>
    <textarea id="sourceContent" rows="0" cols="0" class="ent_content_full" 
    style="top:5em;width:100%;resize:none;padding:0.5em;border:none"></textarea>
    
    
</div>
<div style="position:absolute;right:200px;width:200px; height:100%; border-right:1px lightgray solid">
    <div class="ent_header" style="height:5em;">
        <h2>Step 2</h2>
        <div id="btnParseData" style="position:absolute;bottom:10px;">Analyse</div>
    </div>
    <fieldset class="ent_content_full" style="top:5em;padding-top:1em;">
            <div>
                <label for="csv_delimiter">Field separator:</label>
                <select id="csv_delimiter" class="text ui-widget-content ui-corner-all" style="width:120px;">
                        <option value="," selected>comma</option>
                        <option value="tab">tab</option>
                        <option value=";">semicolon</option>
                        <option value="space">space</option>
                </select>
            </div>
            <div>
                <label for="csv_enclosure">Fields enclosed in:</label>
                <select id="csv_enclosure" class="text ui-widget-content ui-corner-all" style="width:120px;">
                        <option selected value='2'>"</option><option value="1">'</option>
                </select>
            </div>
            <div>
                <label for="csv_linebreak">Line separator:</label>
                    <select id="csv_linebreak" class="text ui-widget-content ui-corner-all" style="width:120px;">
                        <option selected value="auto">auto detect</option>
                        <option value="1">No lines</option>
                        <option value="2">No lines. Group by 2</option>
                        <option value="3">No lines. Group by 3</option>
                        <!--
                        <option value="win">Windows</option>
                        <option value="nix">Unix</option>
                        <option value="mac">Mac</option>
                        -->
                    </select>
            </div>
            <div style="padding-top:1em;">
                <input id="csv_header" 
                    style="margin:1em 0.5em 0 0"
                    class="text ui-widget-content ui-corner-all" type="checkbox" value="1" checked>
                <label for="csv_header">Labels in line 1</label>
            </div>
    </fieldset>            
</div>
<div style="position:absolute;right:0px;height:100%;width:200px;"> 
    <div class="ent_header" style="height:5em;">
        <h2>Step 3</h2>
        <h2 style="padding-top:0.4em">Select field order<br>(Term is required)</h2>
    </div>
    <fieldset class="ent_content" style="top:5em;padding-top:1em;">
           
            <div>
                <label style="color:red">Term (Label)</label><br>
                <select id="field_term" class="column_roles text ui-widget-content ui-corner-all" style="width:120px;">
                </select>
            </div>
            <div>
                <label>Standard Code</label><br>
                <select id="field_code" class="column_roles text ui-widget-content ui-corner-all" style="width:120px;">
                </select>
            </div>
            <div>
                <label>Description</label><br>
                <select id="field_desc" class="column_roles text ui-widget-content ui-corner-all" style="width:120px;">
                </select>
            </div>
            
            
    </fieldset> 
    <div class="ent_footer" style="padding-left:1em">
        <div id="btnImportData">Import</div>
        <div id="preparedInfo2" style="display:inline-block;font-weight:bold;font-size:1.1em"></div>
    </div>
</div>
<div id="divCurtain" style="position:absolute;right:0px;height:100%;width:400px;" class="semitransparent">
<!-- curtain -->
</div>
</div>

<div style="width:100%;height:40%;position:absolute;bottom:0" >
    <div class="ent_header" style="height:2em;border-bottom:none;border-top:1px solid lightgray;">
        <h2 style="display:inline-block;padding-top:0.5em">Preview data to be imported</h2>
        <div id="preparedInfo" style="float:right"> <!-- div to show results of data preparation --></div>
    </div>
    <div class="ent_content_full" style="top:2.5em;font-size:0.9em;" id="divParsePreview">
    </div>
</div>



</body>
</html>