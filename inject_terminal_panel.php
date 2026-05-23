<?php
/**
 * Inyecta el panel lateral de terminal en Moodle
 * Ejecutar dentro del contenedor: php inject_terminal_panel.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

$TERMINAL_URL = 'http://192.168.56.104:7681';

// ── CSS en <head> ─────────────────────────────────────────────────────────
$head_css = <<<'CSS'
<style id="eva-term-styles">
/* Boton flotante - por encima del "?" de Moodle que esta en bottom:20px right:20px */
#eva-term-btn {
  position: fixed !important;
  bottom: 70px !important;
  right: 20px !important;
  z-index: 9999 !important;
  background: #161b22 !important;
  color: #3fb950 !important;
  border: 2px solid #3fb950 !important;
  padding: 8px 16px !important;
  border-radius: 8px !important;
  cursor: pointer !important;
  font-family: 'Courier New', monospace !important;
  font-size: 13px !important;
  font-weight: bold !important;
  box-shadow: 0 4px 16px rgba(0,0,0,0.5) !important;
  transition: background 0.2s, color 0.2s !important;
  line-height: 1.4 !important;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}
#eva-term-btn:hover { background: #3fb950 !important; color: #0d1117 !important; }

/* Panel lateral */
#eva-term-panel {
  position: fixed !important;
  top: 0 !important;
  right: 0 !important;
  width: 42vw !important;
  height: 100vh !important;
  min-width: 300px !important;
  background: #0d1117 !important;
  z-index: 9998 !important;
  display: flex !important;
  flex-direction: column !important;
  box-shadow: -8px 0 40px rgba(0,0,0,0.7) !important;
  border-left: 2px solid #3fb950 !important;
  transform: translateX(110%) !important;
  transition: transform 0.3s ease !important;
}
#eva-term-panel.eva-open {
  transform: translateX(0) !important;
}

/* Cuando el panel esta abierto, encoger el body para hacer split real */
body.eva-term-open {
  padding-right: 42vw !important;
  box-sizing: border-box !important;
  transition: padding-right 0.3s ease !important;
}
body.eva-term-open #page,
body.eva-term-open #page-wrapper,
body.eva-term-open .pagelayout-frontpage #page {
  max-width: 100% !important;
  margin-right: 0 !important;
}

#eva-term-header {
  background: #161b22 !important;
  color: #3fb950 !important;
  padding: 8px 12px !important;
  font-family: 'Courier New', monospace !important;
  font-size: 12px !important;
  display: flex !important;
  justify-content: space-between !important;
  align-items: center !important;
  border-bottom: 1px solid #30363d !important;
  flex-shrink: 0 !important;
}
#eva-term-reload {
  background: none; border: 1px solid #3fb950; color: #3fb950;
  border-radius: 4px; padding: 2px 7px; cursor: pointer;
  font-family: monospace; font-size: 11px; margin-right: 6px;
}
#eva-term-reload:hover { background: #3fb950; color: #0d1117; }
#eva-term-close {
  background: none; border: none; color: #f85149;
  cursor: pointer; font-size: 18px; line-height: 1; padding: 0;
}
#eva-term-frame {
  flex: 1 1 auto !important;
  border: none !important;
  width: 100% !important;
  height: 100% !important;
  background: #0d1117 !important;
}
#eva-term-resizer {
  position: absolute !important;
  left: -4px !important;
  top: 0 !important;
  width: 8px !important;
  height: 100% !important;
  cursor: ew-resize !important;
  z-index: 2 !important;
}
#eva-term-resizer:hover { background: rgba(63,185,80,0.4) !important; }
</style>
CSS;

// ── HTML + JS al final del <body> ─────────────────────────────────────────
$footer_html = <<<HTML
<!-- EVA Terminal Panel -->
<div id="eva-term-panel">
  <div id="eva-term-resizer"></div>
  <div id="eva-term-header">
    <span style="font-weight:bold">&#9654; Terminal EVA — Infraestructura</span>
    <span>
      <button id="eva-term-reload" title="Nueva sesion">&#8635; Reset</button>
      <button id="eva-term-close" title="Cerrar">&#10005;</button>
    </span>
  </div>
  <iframe id="eva-term-frame" src="about:blank"
    sandbox="allow-same-origin allow-scripts allow-forms allow-modals allow-popups"
    allowfullscreen></iframe>
</div>
<button id="eva-term-btn">&#9000; Terminal</button>

<script>
(function(){
  var TERM    = 'TERM_URL';
  var panel   = document.getElementById('eva-term-panel');
  var frame   = document.getElementById('eva-term-frame');
  var btn     = document.getElementById('eva-term-btn');
  var closeBt = document.getElementById('eva-term-close');
  var reloadB = document.getElementById('eva-term-reload');
  var resizer = document.getElementById('eva-term-resizer');
  var loaded  = false;

  function openPanel() {
    if (!loaded) { frame.src = TERM; loaded = true; }
    panel.classList.add('eva-open');
    document.body.classList.add('eva-term-open');
    btn.innerHTML = '&#10005; Cerrar';
    try { localStorage.setItem('eva_term','1'); } catch(e){}
  }
  function closePanel() {
    panel.classList.remove('eva-open');
    document.body.classList.remove('eva-term-open');
    btn.innerHTML = '&#9000; Terminal';
    try { localStorage.setItem('eva_term','0'); } catch(e){}
  }

  btn.addEventListener('click', function(){ panel.classList.contains('eva-open') ? closePanel() : openPanel(); });
  closeBt.addEventListener('click', closePanel);
  reloadB.addEventListener('click', function(){
    loaded = false; frame.src = 'about:blank';
    setTimeout(function(){ frame.src = TERM; loaded = true; }, 100);
  });

  // Restaurar estado al navegar entre paginas
  try { if (localStorage.getItem('eva_term') === '1') openPanel(); } catch(e){}

  // Redimensionar panel arrastrando el borde
  var drag=false, sx, sw;
  resizer.addEventListener('mousedown', function(e){ drag=true; sx=e.clientX; sw=panel.offsetWidth; e.preventDefault(); });
  document.addEventListener('mousemove', function(e){
    if (!drag) return;
    var nw = Math.min(Math.max(sw+(sx-e.clientX), 280), window.innerWidth*0.75);
    var pct = (nw/window.innerWidth*100).toFixed(1)+'vw';
    panel.style.width = pct;
    if (document.body.classList.contains('eva-term-open')) document.body.style.paddingRight = pct;
  });
  document.addEventListener('mouseup', function(){ drag=false; });
})();
</script>
HTML;

$footer_html = str_replace('TERM_URL', $TERMINAL_URL, $footer_html);

set_config('additionalhtmlhead',   $head_css);
set_config('additionalhtmlfooter', $footer_html);
purge_all_caches();

echo "Listo. Boton 'Terminal' aparece encima del '?' de Moodle (bottom:70px right:20px).\n";
echo "Al abrirse, el contenido se encoge a la izquierda y la terminal ocupa la derecha.\n";
