/**
 * CampusLend - Main Client-Side Application Script
 * Theme Manager, Live Countdowns, Toast Notifications, Bell Polling
 */

(function () {
  'use strict';

  window.CampusLend = {
    // Toast Notification Dispatcher
    toast: function (message, type = 'info') {
      let container = document.getElementById('toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.className = `toast toast-${type}`;

      const iconMap = {
        success: '✅',
        error: '⚠️',
        warning: '🔔',
        info: 'ℹ️',
      };

      toast.innerHTML = `
        <span style="font-size: 1.2rem;">${iconMap[type] || 'ℹ️'}</span>
        <div style="flex: 1; font-weight: 500;">${message}</div>
        <button type="button" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem;" onclick="this.parentElement.remove()">✕</button>
      `;

      container.appendChild(toast);

      setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
      }, 5000);
    },

    // Theme Switcher (Dark / Light)
    initTheme: function () {
      const savedTheme = localStorage.getItem('campuslend_theme') || 
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      
      document.documentElement.setAttribute('data-theme', savedTheme);
      this.updateThemeIcon(savedTheme);

      const toggleBtn = document.getElementById('theme-toggle-btn');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
          const current = document.documentElement.getAttribute('data-theme') || 'light';
          const next = current === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', next);
          localStorage.setItem('campuslend_theme', next);
          this.updateThemeIcon(next);
        });
      }
    },

    updateThemeIcon: function (theme) {
      const toggleBtn = document.getElementById('theme-toggle-btn');
      if (!toggleBtn) return;
      toggleBtn.innerHTML = theme === 'dark' 
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>` 
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
    },

    // Live Countdown Timers for Urgent Requests
    initCountdowns: function () {
      const elements = document.querySelectorAll('[data-countdown]');
      if (!elements.length) return;

      const updateTimers = () => {
        const now = Math.floor(Date.now() / 1000);
        elements.forEach((el) => {
          const target = parseInt(el.getAttribute('data-countdown'), 10);
          const diff = target - now;

          if (diff <= 0) {
            el.innerHTML = '<span class="text-danger font-bold">⏰ Expired</span>';
            return;
          }

          const hours = Math.floor(diff / 3600);
          const mins = Math.floor((diff % 3600) / 60);
          const secs = diff % 60;

          if (hours > 0) {
            el.textContent = `⏱️ ${hours}h ${mins}m left`;
          } else {
            el.textContent = `⏱️ ${mins}m ${secs < 10 ? '0' : ''}${secs}s left`;
          }
        });
      };

      updateTimers();
      setInterval(updateTimers, 1000);
    },

    // Notification Bell Polling
    initNotifications: function () {
      const bellBadge = document.getElementById('notification-badge');
      if (!bellBadge) return;

      const checkUnread = async () => {
        try {
          const res = await fetch('/api/notifications/unread-count', {
            headers: { 'Accept': 'application/json' }
          });
          if (res.ok) {
            const data = await res.json();
            if (data.count > 0) {
              bellBadge.textContent = data.count > 99 ? '99+' : data.count;
              bellBadge.style.display = 'inline-flex';
            } else {
              bellBadge.style.display = 'none';
            }
          }
        } catch (e) {
          // Silent fallback on connection interruption
        }
      };
      checkUnread();
      setInterval(checkUnread, 12000);
    },

    // Ripple Effect on Buttons
    initRipple: function () {
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn');
        if (!btn) return;
        const ripple = document.createElement('span');
        ripple.className = 'ripple-effect';
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        btn.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
      });
    },

    // PWA Service Worker Registration
    initPWA: function () {
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js').then((reg) => {
            console.log('CampusLend PWA SW registered:', reg.scope);
          }).catch((err) => {
            console.warn('CampusLend SW registration failed:', err);
          });
        });
      }
    },
  };

  // Initialize on DOM Ready
  document.addEventListener('DOMContentLoaded', function () {
    window.CampusLend.initTheme();
    window.CampusLend.initCountdowns();
    window.CampusLend.initNotifications();
    window.CampusLend.initRipple();
    window.CampusLend.initPWA();
  });
})();

