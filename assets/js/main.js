document.addEventListener('DOMContentLoaded', function () {
  // Sidebar toggle (expand/collapse)
  const toggles = document.querySelectorAll('nav .toggle > a');

  toggles.forEach(toggle => {
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      const subMenu = this.nextElementSibling;
      if (subMenu) {
        subMenu.style.display = (subMenu.style.display === 'block') ? 'none' : 'block';
      }
    });
  });
});

// Load side panel via AJAX when sidebar item is clicked
function loadPanelFromSidebar(event) {
  event.preventDefault();
  const url = event.currentTarget.getAttribute('data-panel');
  const panel = document.getElementById('panel-right');
  const backdrop = document.getElementById('panel-backdrop');

  if (!panel) {
    console.error("Missing #panel-right div in your layout.");
    return;
  }

  // Optional loading indicator
  panel.innerHTML = `<div style="padding:1rem;">Loading...</div>`;
  panel.classList.remove('hidden');
  document.body.style.overflow = 'hidden';

  // Show backdrop if exists
  if (backdrop) backdrop.classList.remove('hidden');

  fetch(url)
    .then(res => res.text())
    .then(html => {
      // Optional close button added automatically
      panel.innerHTML = `<button onclick="hidePanel()" style="
          float:right;
          background: none;
          border: none;
          font-size: 20px;
          margin: 10px;
          cursor: pointer;">✖</button>` + html;
    })
    .catch(err => {
      panel.innerHTML = `<div style="padding:1rem; color:red;">Failed to load panel: ${err}</div>`;
    });
}

// Hide panel and reset
function hidePanel() {
  const panel = document.getElementById('panel-right');
  const backdrop = document.getElementById('panel-backdrop');

  if (panel) {
    panel.classList.add('hidden');
    panel.innerHTML = '';
  }

  if (backdrop) {
    backdrop.classList.add('hidden');
  }

  document.body.style.overflow = 'auto';
}
function loadPanelFromNav(url) {
  const panel = document.getElementById('panel-right');
  fetch(url)
    .then(res => res.text())
    .then(html => {
      panel.innerHTML = html;
      panel.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    })
    .catch(err => {
      alert("Failed to load form.");
      console.error(err);
    });
}
function loadCategoryManager() {
  const panel = document.getElementById('panel-right');
  const url = '/POS_UG/views/products/categories.php'; // Adjust path if needed

  fetch(url)
    .then(res => res.text())
    .then(html => {
      panel.innerHTML = html;
      panel.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    });
}
