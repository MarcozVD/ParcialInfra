(function () {
  var TERM = 'http://192.168.56.104:7681';
  var loaded = false;

  function getEl(id) { return document.getElementById(id); }

  function openPanel() {
    var panel = getEl('eva-term-panel');
    var frame = getEl('eva-term-frame');
    var btn   = getEl('eva-term-btn');
    if (!panel || !frame || !btn) return;
    if (!loaded) { frame.src = TERM; loaded = true; }
    panel.classList.add('eva-open');
    document.body.classList.add('eva-term-open');
    btn.innerHTML = '&#10005; Cerrar';
    try { localStorage.setItem('eva_term', '1'); } catch (e) {}
  }

  function closePanel() {
    var panel = getEl('eva-term-panel');
    var btn   = getEl('eva-term-btn');
    if (!panel || !btn) return;
    panel.classList.remove('eva-open');
    document.body.classList.remove('eva-term-open');
    btn.innerHTML = '&#9000; Terminal';
    try { localStorage.setItem('eva_term', '0'); } catch (e) {}
  }

  function reloadFrame() {
    var frame = getEl('eva-term-frame');
    if (!frame) return;
    loaded = false;
    frame.src = 'about:blank';
    setTimeout(function () { frame.src = TERM; loaded = true; }, 150);
  }

  function init() {
    var btn     = getEl('eva-term-btn');
    var closeB  = getEl('eva-term-close');
    var reloadB = getEl('eva-term-reload');
    var resizer = getEl('eva-term-resizer');

    if (!btn) return; // elementos aun no existen

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var panel = getEl('eva-term-panel');
      if (panel && panel.classList.contains('eva-open')) {
        closePanel();
      } else {
        openPanel();
      }
    });

    if (closeB)  closeB.addEventListener('click',  function (e) { e.stopPropagation(); closePanel(); });
    if (reloadB) reloadB.addEventListener('click', function (e) { e.stopPropagation(); reloadFrame(); });

    // Restaurar estado entre navegaciones
    try { if (localStorage.getItem('eva_term') === '1') openPanel(); } catch (e) {}

    // Redimensionar arrastrando el borde del panel
    if (resizer) {
      var drag = false, sx = 0, sw = 0;
      resizer.addEventListener('mousedown', function (e) {
        drag = true; sx = e.clientX;
        sw = getEl('eva-term-panel').offsetWidth;
        e.preventDefault();
      });
      document.addEventListener('mousemove', function (e) {
        if (!drag) return;
        var nw = Math.min(Math.max(sw + (sx - e.clientX), 280), window.innerWidth * 0.75);
        var pct = (nw / window.innerWidth * 100).toFixed(1) + 'vw';
        getEl('eva-term-panel').style.width = pct;
        if (document.body.classList.contains('eva-term-open')) {
          document.body.style.paddingRight = pct;
        }
      });
      document.addEventListener('mouseup', function () { drag = false; });
    }
  }

  // Ejecutar cuando el DOM este listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
