<?php
/**
 * Inyecta el panel lateral de terminal en Moodle
 * Ejecutar dentro del contenedor: php inject_terminal_panel.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

// ── CSS en <head> ─────────────────────────────────────────────────────────
$head_css = <<<'CSS'
<style id="eva-term-styles">
#eva-term-btn {
  position: fixed !important;
  bottom: 70px !important;
  right: 20px !important;
  z-index: 99999 !important;
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
  line-height: 1.4 !important;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  pointer-events: all !important;
}
#eva-term-btn:hover { background: #3fb950 !important; color: #0d1117 !important; }
#eva-term-panel {
  position: fixed !important;
  top: 0 !important;
  right: 0 !important;
  width: 42vw !important;
  height: 100vh !important;
  min-width: 300px !important;
  background: #0d1117 !important;
  z-index: 99998 !important;
  display: flex !important;
  flex-direction: column !important;
  box-shadow: -8px 0 40px rgba(0,0,0,0.7) !important;
  border-left: 2px solid #3fb950 !important;
  transform: translateX(110%) !important;
  transition: transform 0.3s ease !important;
  pointer-events: all !important;
}
#eva-term-panel.eva-open {
  transform: translateX(0) !important;
}
body.eva-term-open {
  padding-right: 42vw !important;
  box-sizing: border-box !important;
  transition: padding-right 0.3s ease !important;
}
body.eva-term-open #page,
body.eva-term-open .pagelayout-standard #page {
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
  pointer-events: all !important;
}
#eva-term-reload {
  background: none; border: 1px solid #3fb950; color: #3fb950;
  border-radius: 4px; padding: 2px 7px; cursor: pointer;
  font-family: monospace; font-size: 11px; margin-right: 6px;
  pointer-events: all;
}
#eva-term-reload:hover { background: #3fb950; color: #0d1117; }
#eva-term-close {
  background: none; border: none; color: #f85149;
  cursor: pointer; font-size: 18px; line-height: 1; padding: 0;
  pointer-events: all;
}
#eva-term-frame {
  flex: 1 1 auto !important;
  border: none !important;
  width: 100% !important;
  height: 100% !important;
  background: #0d1117 !important;
  pointer-events: all !important;
}
#eva-term-resizer {
  position: absolute !important;
  left: -4px !important; top: 0 !important;
  width: 8px !important; height: 100% !important;
  cursor: ew-resize !important; z-index: 2 !important;
}
#eva-term-resizer:hover { background: rgba(63,185,80,0.4) !important; }
</style>
CSS;

// ── HTML + referencia al JS externo (sin inline scripts) ──────────────────
$footer_html = <<<'HTML'
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
<script src="/eva_terminal.js"></script>
HTML;

set_config('additionalhtmlhead',   $head_css);
set_config('additionalhtmlfooter', $footer_html);
purge_all_caches();

echo "Inyectado. JS movido a /eva_terminal.js (mismo origen, sin bloqueo CSP).\n";
