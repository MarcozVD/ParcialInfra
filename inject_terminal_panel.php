<?php
/**
 * Inyecta el panel lateral de terminal en Moodle via additionalhtmlfooter
 * Ejecutar: php inject_terminal_panel.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

$TERMINAL_URL = 'http://192.168.56.104:7681';

$panel_html = <<<'HTML'
<!-- EVA Terminal Panel -->
<style>
#eva-term-btn {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 99999;
  background: #0d1117;
  color: #39d353;
  border: 2px solid #39d353;
  padding: 10px 18px;
  border-radius: 8px;
  cursor: pointer;
  font-family: 'Courier New', monospace;
  font-size: 14px;
  font-weight: bold;
  box-shadow: 0 4px 16px rgba(0,0,0,0.4);
  transition: all 0.2s;
  user-select: none;
}
#eva-term-btn:hover { background: #39d353; color: #0d1117; }
#eva-term-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 45%;
  height: 100%;
  background: #0d1117;
  z-index: 99998;
  display: flex;
  flex-direction: column;
  box-shadow: -6px 0 30px rgba(0,0,0,0.6);
  transform: translateX(100%);
  transition: transform 0.3s ease;
}
#eva-term-panel.open { transform: translateX(0); }
#eva-term-header {
  background: #161b22;
  color: #39d353;
  padding: 9px 14px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #30363d;
  flex-shrink: 0;
}
#eva-term-header span { font-weight: bold; }
#eva-term-close {
  background: none;
  border: none;
  color: #f85149;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
  padding: 0 4px;
}
#eva-term-frame {
  flex: 1;
  border: none;
  width: 100%;
  background: #0d1117;
}
body.eva-term-open #page,
body.eva-term-open .pagewrapper,
body.eva-term-open #page-wrapper {
  margin-right: 45% !important;
  transition: margin-right 0.3s ease;
}
#eva-term-resizer {
  position: absolute;
  left: 0;
  top: 0;
  width: 5px;
  height: 100%;
  cursor: ew-resize;
  background: transparent;
}
#eva-term-resizer:hover { background: rgba(57,211,83,0.3); }
</style>

<div id="eva-term-panel">
  <div id="eva-term-resizer"></div>
  <div id="eva-term-header">
    <span>&#9654; Terminal de Práctica — EVA Infraestructura</span>
    <button id="eva-term-close" title="Cerrar terminal">✕</button>
  </div>
  <iframe id="eva-term-frame" src="about:blank" allow="clipboard-read; clipboard-write"></iframe>
</div>
<button id="eva-term-btn" title="Abrir/cerrar terminal de práctica">⌨ Terminal</button>

<script>
(function() {
  var panel  = document.getElementById('eva-term-panel');
  var frame  = document.getElementById('eva-term-frame');
  var btn    = document.getElementById('eva-term-btn');
  var close  = document.getElementById('eva-term-close');
  var resizer = document.getElementById('eva-term-resizer');
  var TERM_URL = 'TERMINAL_URL_PLACEHOLDER';
  var loaded = false;

  function openPanel() {
    if (!loaded) { frame.src = TERM_URL; loaded = true; }
    panel.classList.add('open');
    document.body.classList.add('eva-term-open');
    btn.textContent = '✕ Cerrar';
    localStorage.setItem('eva_term_open', '1');
  }
  function closePanel() {
    panel.classList.remove('open');
    document.body.classList.remove('eva-term-open');
    btn.innerHTML = '⌨ Terminal';
    localStorage.setItem('eva_term_open', '0');
  }
  function toggle() {
    panel.classList.contains('open') ? closePanel() : openPanel();
  }

  btn.addEventListener('click', toggle);
  close.addEventListener('click', closePanel);

  // Restaurar estado entre páginas
  if (localStorage.getItem('eva_term_open') === '1') openPanel();

  // Drag para redimensionar panel
  var dragging = false, startX, startW;
  resizer.addEventListener('mousedown', function(e) {
    dragging = true;
    startX = e.clientX;
    startW = panel.offsetWidth;
    document.body.style.userSelect = 'none';
  });
  document.addEventListener('mousemove', function(e) {
    if (!dragging) return;
    var delta = startX - e.clientX;
    var newW = Math.min(Math.max(startW + delta, 300), window.innerWidth * 0.75);
    var pct = (newW / window.innerWidth * 100).toFixed(1) + '%';
    panel.style.width = pct;
    var pages = document.querySelectorAll('#page, .pagewrapper, #page-wrapper');
    pages.forEach(function(p) { p.style.marginRight = pct; });
  });
  document.addEventListener('mouseup', function() {
    dragging = false;
    document.body.style.userSelect = '';
  });
})();
</script>
HTML;

// Reemplazar la URL del terminal en el HTML
$panel_html = str_replace('TERMINAL_URL_PLACEHOLDER', $TERMINAL_URL, $panel_html);

// Guardar en Moodle config
set_config('additionalhtmlfooter', $panel_html);

echo "Panel de terminal inyectado correctamente.\n";
echo "URL del terminal: {$TERMINAL_URL}\n";
echo "Purga de cache...\n";

// Purgar cache de tema
purge_all_caches();

echo "Listo. Recarga cualquier pagina de Moodle para ver el boton 'Terminal'.\n";
