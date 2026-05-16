<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
date_default_timezone_set('Europe/Berlin');

function get($u){
 $ch=curl_init($u);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>10,
  CURLOPT_USERAGENT=>'Mozilla/5.0 (RaceMonitor24h 2026)',CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_ENCODING=>'']);
 $d=curl_exec($ch); $c=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
 return $c>=200&&$c<400?$d:'';
}

$data=[]; $sectors=['GP'=>'green','N1'=>'green','N2'=>'green','N3'=>'green'];
$messages=[]; $flag=['class'=>'green','label'=>'GRÜN']; $src='none';

// 2026 Quellen – Wempe ist neuer Timing-Partner, daher mehrere Fallbacks
$urls=[
 'https://www.24h-rennen.de/wp-json/24h/v1/livetiming', // vermutet
 'https://livetiming.24h-rennen.de/api/standings',
 'https://wige-livetiming.azurewebsites.net/api/race',
 'https://livetiming.azurewebsites.net/',
];

foreach($urls as $u){
 $h=get($u);
 if(!$h) continue;
 $src=$u;
 // JSON?
 if(str_starts_with(trim($h),'{') || str_starts_with(trim($h),'[')){
   $j=json_decode($h,true);
   if(isset($j['standings'])) $j=$j['standings'];
   if(is_array($j)){
     $i=1; foreach($j as $r){
       $data[]=["pos"=>$i++,"num"=>$r['number']??$r['nr']??'?','team'=>$r['team']??'','car'=>$r['vehicle']??$r['car']??'','sector'=>'GP','last'=>$r['last']??'','gap'=>$r['gap']??'','pit'=>$r['pits']??'0'];
       if($i>60) break;
     }
     break;
   }
 }
 // HTML Fallback
 if(preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si',$h,$m)){
   $i=1;
   foreach($m[1] as $tr){
     if(preg_match_all('/<td[^>]*>(.*?)<\/td>/si',$tr,$c)){
       $t=array_map(fn($x)=>trim(strip_tags($x)),$c[1]);
       if(count($t)>=4 && is_numeric($t[0]??$t[1]??'')){
         $num=is_numeric($t[0])?$t[0]:$t[1];
         $data[]=["pos"=>$i++,"num"=>$num,"team"=>$t[1]??'','car'=>$t[2]??'','sector'=>'GP','last'=>$t[3]??'','gap'=>$t[4]??'','pit'=>$t[5]??'0'];
       }
     }
     if($i>60) break;
   }
   if($data) break;
 }
}

// Sektoren + Meldungen von offizieller Seite
$live=get('https://www.24h-rennen.de/live/');
if($live){
 $map=['GP'=>'GP-Strecke','N1'=>'Hatzenbach','N2'=>'Bergwerk','N3'=>'Pflanzgarten'];
 foreach($map as $k=>$name){
   if(stripos($live,$name)!==false){
     $s=substr($live,max(0,stripos($live,$name)-100),200);
     if(stripos($s,'rot')!==false) $sectors[$k]='red';
     elseif(stripos($s,'gelb')!==false) $sectors[$k]='yellow';
     elseif(stripos($s,'code')!==false) $sectors[$k]='blue';
   }
 }
 if(preg_match_all('/<article[^>]*>(.*?)<\/article>/si',$live,$a)){
   foreach(array_slice($a[1],0,5) as $art){ $t=trim(strip_tags($art)); if(strlen($t)>10) $messages[]=mb_substr($t,0,120); }
 }
}

if(empty($data)){
 $messages[] = "Race läuft (16.05.2026), aber Timing-Quelle antwortet nicht – Quelle getestet: $src";
 $messages[] = date('H:i:s')." – prüfe Wempe-Timing";
}

echo json_encode(['ok'=>true,'time'=>date('c'),'source'=>$src,'count'=>count($data),'data'=>$data,'flag'=>$flag,'sectors'=>$sectors,'messages'=>$messages],JSON_UNESCAPED_UNICODE);
