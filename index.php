<?php header('X-Frame-Options:SAMEORIGIN'); header('Cache-Control: no-store'); ?>
<!doctype html><html lang="de"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>RaceMonitor24h – 2026 Nürburgring</title>
<link rel="manifest" href="manifest.json"><meta name="theme-color" content="#00ff88">
<style>
:root{--bg:#000;--fg:#eaeaea;--green:#00ff88;--yellow:#ffd400;--red:#ff2a2a;--blue:#00b7ff}
*{box-sizing:border-box}html,body{margin:0;height:100%;background:var(--bg);color:var(--fg);font-family:system-ui,Segoe UI,Roboto,Arial;overflow:hidden}
header{display:flex;align-items:center;gap:12px;padding:8px 12px;border-bottom:2px solid #111}
.badge{padding:6px 10px;border-radius:8px;font-weight:800;background:#111}
.badge.green{background:var(--green);color:#000}.badge.yellow{background:var(--yellow);color:#000}.badge.red{background:var(--red);color:#fff}
.badge.blue{background:var(--blue);color:#000}
h1{font-size:18px;margin:0;flex:1}
.grid{display:grid;grid-template-columns:1.4fr .6fr;gap:8px;height:calc(100vh - 56px);padding:8px}
.card{background:#0a0a0a;border:1px solid #1a1a1a;border-radius:12px;overflow:hidden;display:flex;flex-direction:column}
.card h2{margin:0;padding:8px 10px;font-size:14px;background:#111;border-bottom:1px solid #1a1a1a}
.table{width:100%;border-collapse:collapse;font-size:13px}
.table th,.table td{padding:6px 8px;border-bottom:1px solid #151515;white-space:nowrap}
.table th{position:sticky;top:0;background:#0f0f0f;z-index:1;text-align:left}
.pos{width:36px;text-align:right}.num{width:48px;font-weight:700}
.sector{display:inline-block;padding:2px 6px;border-radius:6px;background:#222;font-size:11px}
.sector.GP{background:#004d40}.sector.N1{background:#1a237e}.sector.N2{background:#4a148c}.sector.N3{background:#b71c1c}
.right{display:grid;grid-template-rows:auto 1fr auto;gap:8px}
.flags{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;padding:8px}
.flag{padding:8px;border-radius:8px;text-align:center;font-weight:700;background:#111}
.flag.green{background:var(--green);color:#000}.flag.yellow{background:var(--yellow);color:#000}.flag.red{background:var(--red)}
.flag.blue{background:var(--blue);color:#000}
.list{overflow:auto;padding:8px;font-size:13px;line-height:1.4}
iframe{border:0;width:100%;height:100%;background:#000}
footer{padding:4px 10px;font-size:11px;color:#888;border-top:1px solid #111}
@media (orientation:portrait){.grid{grid-template-columns:1fr;grid-template-rows:1fr 1fr}}
</style></head><body>
<header>
  <div id="overall" class="badge green">GRÜN</div>
  <h1>ADAC RAVENOL 24h Nürburgring 2026 – Live Monitor</h1>
  <div id="clock" class="badge">--:--:--</div>
</header>
<div class="grid">
  <div class="card"><h2>Gesamtstand (Top 40) – 161 Fahrzeuge</h2>
    <div style="overflow:auto"><table class="table" id="tbl"><thead><tr>
      <th class="pos">#</th><th class="num">Nr</th><th>Team</th><th>Fzg</th><th>Sektor</th><th>Letzte</th><th>Gap</th><th>Pit</th>
    </tr></thead><tbody></tbody></table></div>
    <footer>Quelle: livetiming.azurewebsites.net + wige.de | Update alle 15s</footer>
  </div>
  <div class="right">
    <div class="card"><h2>Streckenabschnitte 2026</h2>
      <div class="flags" id="flags"></div>
    </div>
    <div class="card"><h2>Live-Ticker / Rennleitung</h2>
      <iframe id="ticker" src="proxy.php?source=ticker2026"></iframe>
    </div>
    <div class="card"><h2>Meldungen</h2><div class="list" id="msgs">Verbinde...</div></div>
  </div>
</div>
<script>
const $=s=>document.querySelector(s);
function clock(){ const d=new Date(); $('#clock').textContent=d.toLocaleTimeString('de-DE'); }
setInterval(clock,1000);clock();

async function load(){
 try{
  const r=await fetch('merge.php?year=2026',{cache:'no-store'}); const j=await r.json();
  // overall
  const o=$('#overall'); o.className='badge '+j.flag.class; o.textContent=j.flag.label;
  // flags
  const f=$('#flags'); f.innerHTML='';
  const order=[['GP','GP-Strecke'],['N1','Nord 1'],['N2','Nord 2'],['N3','Nord 3']];
  order.forEach(([k,l])=>{ const v=(j.sectors[k]||'green'); const d=document.createElement('div'); d.className='flag '+v; d.innerHTML=l+'<br>'+v.toUpperCase(); f.appendChild(d); });
  // table
  const tb=$('#tbl tbody'); tb.innerHTML='';
  (j.data||[]).slice(0,40).forEach(row=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td class="pos">${row.pos}</td><td class="num">${row.num}</td><td>${row.team}</td><td>${row.car}</td><td><span class="sector ${row.sector}">${row.sector}</span></td><td>${row.last}</td><td>${row.gap}</td><td>${row.pit}</td>`;
    tb.appendChild(tr);
  });
  $('#msgs').innerHTML = (j.messages||[]).map(m=>`• ${m}`).join('<br>') || 'Keine Meldungen';
 }catch(e){ $('#msgs').textContent='Fehler: '+e; }
}
load(); setInterval(load,15000);

// PWA
if('serviceWorker' in navigator){ navigator.serviceWorker.register('sw.js'); }
</script></body></html>
