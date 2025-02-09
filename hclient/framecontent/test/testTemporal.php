<html>
<head>
    <script type="text/javascript" src="../../../hclient/core/temporalObjectLibrary.js"></script>
</head>    
<body>
<?php
    require_once (dirname(__FILE__).'/../../../hsapi/System.php');
    require_once (dirname(__FILE__).'/../../../hsapi/utilities/Temporal.php');
    $system = new System();
    if(!$system->init(@$_REQUEST['db']) ) {
        $response = $system->getError();
        exit($response['message']);
    }
    
    $mysqli = $system->get_mysqli();
    
    /*
    try{   
    $t2 = new DateTime('9999-05-01');
    print $t2->format('Y-m-d H:i:s').'<br>';
    } catch (Exception  $e){
    }
    try{
    $t2 = new DateTime('0050 May');
    print $t2->format('Y-m-d H:i:s').'<br>';
    } catch (Exception  $e){
    }
    */
    
    
    //BP - before present
    //Ga, Gya, bya - gigaannus
    //Ma or Mya, mya (million years ago).  megaannus
    //ka, kya  = kylo annum 
    
    $tvals = array(
    //'10000-05-01',
    '1980-05',
    'May 1983',
    '200 May bce',
    '200 BCE',
    '-50-05',
    '-199-02-03',
    '-198',
    '-4500000000',
    '-500000000',
    '-0000050',
    '-1500000',
    '0000-1-1',
    '0-1-1',
    '50',
    '500-12-2',
    '1200',
    '1500-05-02',
    '1999',
    '30000',
    '2004/10/01',
    '1st April 1970',
    '1989.10.04',
    'today',
    'tomorrow'
    );  

    $tvals = array(
'{"start":{"earliest":"2023-05-01"},"end":{"latest":"2023-05-31"},"calendar":"Gregorian","comment":"2023-05-01","estMinDate":2023.0501,"estMaxDate":2023.0531}',
'{"start":{"earliest":"-001000","latest":"-000100"},"end":{"latest":"1000","earliest":"0100"},"calendar":"Gregorian","estMinDate":-1000,"estMaxDate":1000.0101}',
'{"timestamp":{"in":-1000000,"type":"c","bp":false,"deviation":"P100000Y"},"native":"1000000 BCE","labcode":"A123","estMinDate":-1100000,"estMaxDate":-900000}',
'{"start":{"earliest":"-000099","latest":"0001","profile":"2"},"end":{"latest":"0100-09-01","earliest":"0010","profile":"1"},"determination":"2","calendar":"Gregorian","estMinDate":-99,"estMaxDate":100.0901}'
    );
    
    foreach ($tvals as $val) {

        $dt = new Temporal($val);
        
        $dtl_Simple = '';
        if($dt->isValidSimple()){
            $dtl_Simple = $dt->getValue(true); //returns simple yyyy-mm-dd
        }
        $dtl_Value = $dt->toJSON(); //json encoded string
        
        
        $q = 'SELECT getEstDate(\''.($dtl_Simple!=''?$dtl_Simple:$dtl_Value).'\',1)';  
        $res = '';
        $res = mysql__select_value($mysqli, $q);

        print '<p>'.$val.' => '.$dtl_Simple.'  <span style="color:blue">'.$dtl_Value.'</span>   as: '.$dt->toReadable(). '  '. $res.'</p>';
        
        print '<p>'.$dt->toReadableExt('|').'</p>';
        print '<p>'.$dt->toPlain().'</p>';
    }
    
    print "\n".'<script>var tests = [';
    foreach ($tvals as $idx=>$val) {
        $dt = new Temporal($val);
        if($idx>0) print ',';
        print "'".$dt->toPlain()."'\n";        
    }
    print ']</script>';
?>
<br>
CLIENT SIDE TEST
<script>
for (var i=0; i<tests.length; i++){
    if(tests[i].search(/VER=/)!==-1){
        var temporal = new Temporal(tests[i]);
        var je = temporal.toJSON();
//console.log(JSON.stringify(je));
        document.write('<p>'+JSON.stringify(je)+'</p>');   
        document.write('<p>'+temporal.toReadableExt('|')+'</p>');        
    }else{
        var dt = new TDate(tests[i]);
                    if(!dt.getMonth()){
                        dt.setMonth(1)
                    }
                    if(!dt.getDay()){
                        dt.setDay(1)
                    }
        
        document.write('<p>'+dt.toString('yyyy-MM-ddTHH:mm:ssz')+'</p>');
    }    
}

</script>

</body>
</html>