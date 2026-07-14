/**
 * HI.EVENTS Widget — Embeddable event list loader.
 *
 * Usage:
 *   <div id="hi-events-widget"></div>
 *   <script src="https://example.com/js/widget.js"
 *           data-organizer="acme"
 *           data-limit="5"></script>
 *
 * All class names are prefixed with `hi-ew-` to prevent CSS leaks.
 */
(function () {
  'use strict';

  var script = document.currentScript;
  if (!script) return;

  var organizer = script.getAttribute('data-organizer');
  if (!organizer) return;

  var limit = parseInt(script.getAttribute('data-limit') || '5', 10);
  if (limit < 1) limit = 5;
  if (limit > 20) limit = 20;

  var container = document.getElementById('hi-events-widget');
  if (!container) {
    container = script.parentElement;
  }

  var baseUrl = script.src.replace('/js/widget.js', '');
  var apiUrl = baseUrl + '/api/widget/events?organizer=' + encodeURIComponent(organizer) + '&limit=' + limit;

  container.innerHTML = '<div class="hi-ew-loading">Loading events…</div>';

  var xhr = new XMLHttpRequest();
  xhr.open('GET', apiUrl, true);
  xhr.onload = function () {
    if (xhr.status >= 200 && xhr.status < 400) {
      try {
        var data = JSON.parse(xhr.responseText);
        render(container, data);
      } catch (e) {
        container.innerHTML = '<div class="hi-ew-error">Failed to load events.</div>';
      }
    } else {
      container.innerHTML = '<div class="hi-ew-error">Failed to load events.</div>';
    }
  };
  xhr.onerror = function () {
    container.innerHTML = '<div class="hi-ew-error">Failed to load events.</div>';
  };
  xhr.send();

  function render(container, data) {
    if (!data.events || data.events.length === 0) {
      container.innerHTML = '<div class="hi-ew-empty">No upcoming events.</div>';
      return;
    }

    var html = '<div class="hi-ew-list">';
    html += '<div class="hi-ew-organizer">' + escapeHtml(data.organizer.name) + '</div>';
    html += '<ul>';
    for (var i = 0; i < data.events.length; i++) {
      var ev = data.events[i];
      html += '<li class="hi-ew-item">';
      html += '<a class="hi-ew-link" href="' + escapeHtml(ev.url) + '" target="_blank" rel="noopener">';
      html += '<span class="hi-ew-title">' + escapeHtml(ev.title) + '</span>';
      if (ev.starts_at) {
        var date = new Date(ev.starts_at);
        var formatted = date.toLocaleDateString(undefined, {
          year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        html += '<span class="hi-ew-date">' + formatted + '</span>';
      }
      html += '</a>';
      html += '</li>';
    }
    html += '</ul>';
    html += '</div>';
    container.innerHTML = html;
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }
})();
