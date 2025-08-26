/* Modernize-inspired small UX touches (no functional changes) */
(function () {
  'use strict';
  // Auto-dismiss alerts after a few seconds
  document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
      setTimeout(function () {
        if (alert && alert.classList.contains('show')) {
          // Use Bootstrap dismissal if available
          if (window.bootstrap) {
            try { new bootstrap.Alert(alert).close(); } catch (e) { alert.remove(); }
          } else {
            alert.remove();
          }
        }
      }, 4000);
    });
  });

  // Subtle hover elevation for cards
  const addHoverElevation = function () {
    document.querySelectorAll('.card').forEach(function (card) {
      card.addEventListener('mouseenter', function () {
        card.style.transform = 'translateY(-2px)';
        card.style.boxShadow = '0 16px 40px rgba(2,6,23,0.12)';
      });
      card.addEventListener('mouseleave', function () {
        card.style.transform = 'translateY(0)';
        card.style.boxShadow = '';
      });
    });
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addHoverElevation);
  } else {
    addHoverElevation();
  }

  // Back-to-top button behavior
  const initBackToTop = function () {
    const btn = document.querySelector('.back-to-top');
    if (!btn) return;
    const toggle = function () {
      if (window.scrollY > 200) btn.classList.add('show');
      else btn.classList.remove('show');
    };
    toggle();
    window.addEventListener('scroll', toggle, { passive: true });
    btn.addEventListener('click', function () {
      try { window.scrollTo({ top: 0, behavior: 'smooth' }); }
      catch (e) { window.scrollTo(0, 0); }
    });
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBackToTop);
  } else {
    initBackToTop();
  }
})();
