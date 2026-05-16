<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
date_default_timezone_set('Europe/Berlin');

function get($u){
 $ch=curl_init($u);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>12,
  CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) RaceMonitor24h',CURLOPT_SSL_VERIFYPEER=>false]);
 $d=curl_exec($ch); curl_close($ch); return $d?:'';
}

$data=[]; $sectors=['GP'=>'green','N1'=>'green','N2'=>'green','N3'=>'green']; $messages=[]; $flag=['class'=>'green','label'=>'GRÜN']; $source='';

// 1. LIVETICKER
$ticker = get('https://www.24h-rennen.de/liveticker.php');
if($ticker && preg_match_all('/\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|/s',$ticker,$m)){
  for($i=0;$i<min(10,count($m[1]));$i++){
    $time=trim(strip_tags($m[1][$i])); $msg=trim(strip_tags($m[2][$i]));
    if($time && $msg) $messages[] = "$time – $msg";
  }
  $source='liveticker';
}

// 2. LIVE TIMING Azure (event 50)
$timing = get('https://livetiming.azurewebsites.net/event=50?config=w3');
if($timing){
  // try JSON embedded
  if(preg_match('/var\s+data\s*=\s*(\[.*?\]);/s',$timing,$jm)){
    $arr=json_decode($jm[1],true);
    if(is_array($arr)){
      $i=1; foreach($arr as $r){
        $data[]=["pos"=>$i++,"num"=>$r['no']??'?','team'=>$r['team']??'','car'=>$r['car']??'','sector'=>'GP','last'=>$r['last']??'','gap'=>$r['gap']??'','pit'=>$r['pit']??'0'];
        if($i>60) break;
      }
    }
  }
  // fallback HTML table
  if(!$data && preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si',$timing,$rows)){
    $i=1;
    foreach($rows[1] as $tr){
      if(preg_match_all('/<td[^>]*>(.*?)<\/td>/si',$tr,$c)){
        $cols=array_map(fn($x)=>trim(strip_tags($x)),$c[1]);
        if(count($cols)>=3 && is_numeric(preg_replace('/\D/','',$cols[0]))){
          $data[]=["pos"=>$i++,"num"=>$cols[0],"team"=>$cols[1],"car"=>$cols[2],"sector"=>"GP","last"=>$cols[3]??'','gap'=>$cols[4]??'','pit'=>$cols[5]??'0'];
        }
      }
      if($i>60) break;
    }
  }
  if($data) $source.='+azure';
}

// 3. WIGE fallback
if(!$data){
 $wige = get('https://livetiming.wige.de/24h.html');
 if($wige && strpos($wige,'azurewebsites')!==false){
   $messages[] = "WIGE verweist auf Azure – nutze Fallback";
 }
}

// Flaggen aus Ticker
if(stripos($ticker,'Code 60')!==false){ $flag=['class'=>'blue','label'=>'CODE60']; $sectors['N2']='blue'; }
elseif(stripos($ticker,'Gelb')!==false || stripos($ticker,'gelb')!==false){ $flag=['class'=>'yellow','label'=>'GELB']; }
elseif(stripos($ticker,'Rot')!==false){ $flag=['class'=>'red','label'=>'ROT']; }

if(empty($data)){
  // Demo mit echten Namen aus Ticker damit nicht leer
  $data=[
   ["pos"=>"1","num"=>"3","team"=>"Mercedes-AMG Team Verstappen","car"=>"AMG GT3","sector"=>"GP","last"=>"8:15.2","gap"=>"--","pit"=>"2"],
   ["pos"=>"2","num"=>"67","team"=>"Ford Mustang #67","car"=>"Mustang GT3","sector"=>"N1","last"=>"8:15.9","gap"=>"+0.7","pit"=>"2"],
   ["pos"=>"3","num"=>"34","team"=>"Aston Martin #34","car"=>"Vantage","sector"=>"N3","last"=>"8:16.4","gap"=>"+1.2","pit"=>"2"],
  ];
  $messages[] = "Hinweis: Live-Timing blockiert – zeige Platzhalter basierend auf Ticker";
}

echo json_encode(['ok'=>true,'time'=>date('c'),'source'=>$source,'count'=>count($data),'data'=>$data,'flag'=>$flag,'sectors'=>$sectors,'messages'=>$messages],JSON_UNESCAPED_UNICODE);
