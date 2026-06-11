/**
 * SmartWake - JavaScript principal
 * Mise à jour temps réel, graphiques Chart.js, navigation mobile
 */

/* ============================================================
   1. Navigation mobile (burger menu)
   ============================================================ */
(function () {
  'use strict';

  const toggle = document.getElementById('navbar-toggle');
  const nav    = document.getElementById('navbar-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen.toString());
      toggle.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
    });

    // Fermer le menu au clic extérieur
    document.addEventListener('click', (e) => {
      if (!toggle.contains(e.target) && !nav.contains(e.target)) {
        nav.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }
})();

/* ============================================================
   2. Mise à jour temps réel du dashboard (polling toutes les 5s)
   ============================================================ */
(function () {
  'use strict';

  const BASE_URL = (window.SMARTWAKE_BASE || '') + '/smartwake/api/latest.php';

  const elLux       = document.getElementById('live-lux');
  const elLuxHero   = document.getElementById('live-lux-hero');
  const elStatus    = document.getElementById('live-status');
  const elStatusTxt = document.getElementById('live-status-text');
  const elTimestamp = document.getElementById('live-timestamp');
  const elLuxFill   = document.getElementById('lux-bar-fill');
  const elLuxCircle = document.getElementById('lux-circle');
  const elWakeMsg   = document.getElementById('wake-message');
  const elWakeDet   = document.getElementById('wake-detail');
  const elWakeCard  = document.getElementById('wake-card');
  const elWakeIcon  = document.getElementById('wake-icon');

  if (!elLux) return; // Pas sur le dashboard, on s'arrête

  // Mapping niveau -> couleur barre de progression
  const LEVEL_COLORS = {
    NIGHT_FULL : '#1e1e2e',
    NIGHT_DIM  : '#4f46e5',
    DAWN       : '#f97316',
    MORNING    : '#facc15',
    DAY        : '#60a5fa',
    ALERT      : '#f43f5e',
  };

  /**
   * Met à jour l'interface avec les dernières données capteur.
   * @param {Object} data - Réponse JSON de l'API
   */
  function updateUI(data) {
    const lux    = data.light_value ?? 0;
    const status = data.status     ?? 'UNKNOWN';
    const level  = data.lux_level  ?? 'NIGHT_FULL';
    const label  = data.lux_label  ?? 'Inconnu';
    const icon   = data.lux_icon   ?? '⬛';
    const ts     = data.timestamp  ?? '';
    const wake   = data.wake_recommendation ?? {};
    const color  = LEVEL_COLORS[level] || '#60a5fa';

    // --- Luminosité ---
    if (elLux) {
      elLux.innerHTML = lux + ' <small>lux</small>';
      elLux.style.color = color;
    }
    if (elLuxHero) {
      elLuxHero.textContent = lux + ' lux';
    }

    // --- Barre de progression ---
    if (elLuxFill) {
      const MAX_LUX = 600;
      const pct = Math.min(Math.round((lux / MAX_LUX) * 100), 100);
      elLuxFill.style.width      = pct + '%';
      elLuxFill.style.background = `linear-gradient(90deg, ${color}99, ${color})`;
    }

    // --- Badge statut ---
    if (elStatus) {
      elStatus.innerHTML = `<span class="badge badge-level level-${level.toLowerCase().replace('_','-')}">${icon} ${label}</span>`;
    }

    // --- Texte statut (aria) ---
    if (elStatusTxt) {
      elStatusTxt.textContent = label;
    }

    // --- Cercle lumineux ---
    if (elLuxCircle) {
      elLuxCircle.className = 'lux-circle level-' + level.toLowerCase().replace('_', '-');
      elLuxCircle.style.setProperty('--level-color', color);
      const iconEl = elLuxCircle.querySelector('.lux-icon');
      if (iconEl) iconEl.textContent = icon;
      const valEl  = elLuxCircle.querySelector('.lux-val');
      if (valEl)  valEl.textContent  = lux + ' lux';
      const lblEl  = elLuxCircle.querySelector('.lux-label');
      if (lblEl)  lblEl.textContent  = label;
    }

    // --- Horodatage ---
    if (elTimestamp && ts) {
      const d = new Date(ts.replace(' ', 'T'));
      elTimestamp.textContent = isNaN(d) ? ts : d.toLocaleString('fr-FR');
    }

    // --- Section réveil intelligent ---
    if (elWakeCard && wake) {
      const isOptimal = wake.optimal === true;
      const action    = wake.action  ?? '';
      elWakeCard.className = 'card metric-card wake-card ' + (isOptimal ? 'optimal' : 'not-optimal') + ' action-' + action;
      if (elWakeIcon) elWakeIcon.textContent = {
        sleep         : '😴',
        simulate_dawn : '🌙',
        soft_alarm    : '🌅',
        main_alarm    : '🔔',
        day_mode      : '☀️',
        alert         : '⚠️',
      }[action] || '😴';
      if (elWakeMsg) elWakeMsg.textContent = wake.message ?? '';
      if (elWakeDet) elWakeDet.textContent = wake.detail  ?? '';
      
      const footer = elWakeCard.querySelector('.wake-footer');
      if (footer) {
        footer.innerHTML = isOptimal 
          ? '<span class="badge badge-success">✅ Conditions optimales</span>'
          : '<span class="badge badge-night">💤 Pas encore</span>';
      }
    }

    // --- Alerte lumière soudaine : flash de la page ---
    if (level === 'ALERT') {
      document.body.classList.add('alert-flash');
      setTimeout(() => document.body.classList.remove('alert-flash'), 1000);
    }
  }

  /**
   * Récupère les dernières données depuis l'API.
   */
  async function fetchLatest() {
    try {
      const resp = await fetch(BASE_URL, { cache: 'no-store' });
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      const data = await resp.json();
      updateUI(data);
    } catch (err) {
      console.warn('[SmartWake] Erreur fetch :', err.message);
    }
  }

  // Premier appel immédiat, puis toutes les 5 secondes
  fetchLatest();
  setInterval(fetchLatest, 5000);
})();

/* ============================================================
   3. Graphiques Chart.js
   ============================================================ */
(function () {
  'use strict';

  // Couleurs globales Chart.js
  const CHART_COLORS = {
    line:    'rgba(96, 165, 250, 1)',
    fill:    'rgba(37, 99, 235, 0.15)',
    grid:    'rgba(59, 130, 246, 0.1)',
    text:    'rgba(148, 163, 184, 0.9)',
    day:     'rgba(251, 191, 36, 0.8)',
    night:   'rgba(129, 140, 248, 0.8)',
  };

  // Options communes pour les graphiques
  function baseOptions(titleText) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        title: {
          display: !!titleText,
          text: titleText,
          color: CHART_COLORS.text,
          font: { size: 13 },
        },
        tooltip: {
          backgroundColor: 'rgba(8, 12, 20, 0.9)',
          borderColor: 'rgba(59, 130, 246, 0.4)',
          borderWidth: 1,
          titleColor: '#e2e8f0',
          bodyColor: '#94a3b8',
          callbacks: {
            label: (ctx) => ` ${ctx.parsed.y} lux`,
          },
        },
      },
      scales: {
        x: {
          grid: { color: CHART_COLORS.grid },
          ticks: { color: CHART_COLORS.text, maxTicksLimit: 8, maxRotation: 30 },
        },
        y: {
          grid: { color: CHART_COLORS.grid },
          ticks: { color: CHART_COLORS.text, callback: (v) => v + ' lux' },
          min: 0,
        },
      },
      animation: { duration: 600, easing: 'easeInOutQuart' },
    };
  }

  /**
   * Crée un graphique en ligne (luminosité).
   *
   * @param {string} canvasId  - ID du <canvas>
   * @param {Array}  labels    - Étiquettes de l'axe X
   * @param {Array}  values    - Valeurs de luminosité
   * @param {string} title     - Titre du graphique
   */
  function createLineChart(canvasId, labels, values, title) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    // Couleur des points selon le seuil
    const pointColors = values.map(v => v > 500 ? CHART_COLORS.day : CHART_COLORS.night);

    return new Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Luminosité (lux)',
          data: values,
          borderColor: CHART_COLORS.line,
          backgroundColor: CHART_COLORS.fill,
          pointBackgroundColor: pointColors,
          pointBorderColor: pointColors,
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2.5,
          tension: 0.35,
          fill: true,
        }],
      },
      options: {
        ...baseOptions(title),
        plugins: {
          ...baseOptions(title).plugins,
          annotation: {
            annotations: {
              threshold: {
                type: 'line',
                yMin: 500, yMax: 500,
                borderColor: 'rgba(251, 191, 36, 0.5)',
                borderWidth: 1.5,
                borderDash: [6, 3],
                label: {
                  display: true,
                  content: 'Seuil JOUR/NUIT (500 lux)',
                  color: 'rgba(251, 191, 36, 0.8)',
                  backgroundColor: 'transparent',
                  font: { size: 10 },
                },
              },
            },
          },
        },
      },
    });
  }

  // --- Graphique 1 : 24 dernières heures ---
  if (typeof CHART_24H_DATA !== 'undefined') {
    const labels = CHART_24H_DATA.map(r => {
      const d = new Date(r.created_at.replace(' ', 'T'));
      return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    });
    const values = CHART_24H_DATA.map(r => parseInt(r.light_value, 10));
    createLineChart('chart-24h', labels, values, '');
  }

  // --- Graphique 2 : 100 dernières mesures ---
  if (typeof CHART_100_DATA !== 'undefined') {
    const labels = CHART_100_DATA.map((r, i) => '#' + (i + 1));
    const values = CHART_100_DATA.map(r => parseInt(r.light_value, 10));
    createLineChart('chart-100', labels, values, '');
  }
})();

/* ============================================================
   4. Auto-refresh de la page historique
   ============================================================ */
(function () {
  'use strict';
  const historyTable = document.getElementById('history-table');
  if (!historyTable) return;

  // Highlight de la ligne la plus récente
  const firstRow = historyTable.querySelector('tbody tr:first-child');
  if (firstRow) {
    firstRow.style.borderLeft = '3px solid rgba(96, 165, 250, 0.6)';
  }
})();
