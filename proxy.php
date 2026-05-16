<?php
$src=$_GET['source']??'ticker2026';
$map=[
 'ticker2026'=>'https://www.24h-rennen.de/live-ticker/',
 'timing'=>'https://go.24h-rennen.de/LiveTiming',
 'wige'=>'https://wige-livetiming.azurewebsites.net/'
];
$url=$map[$src]??$map['ticker2026'];
$ch=curl_init($url);
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>10,
 CURLOPT_USERAGENT=>'Mozilla/5.0',CURLOPT_SSL_VERIFYPEER=>false]);
$html=curl_exec($ch); curl_close($ch);
if(!$html){ echo "<body style='background:#000;color:#0f0;font-family:monospace;padding:20px'>2026 Ticker offline – Demo-Modus aktiv</body>"; exit; }
echo $html;
