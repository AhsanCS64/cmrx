/* ============================================================================
   CMRx UI Interaction Library
   Toast notifications, modals, theme toggle, collapsible sidebar,
   client-side search, keyboard shortcuts, smooth scroll, FAB menu.
   ============================================================================ */

const CMRx = (function () {
  "use strict";

  // ---------- Theme ----------
  const theme = {
    init() {
      const root = document.documentElement;
      const btn = document.getElementById("themeToggle");
      const icon = document.getElementById("themeIcon");
      const saved = sessionStorage.getItem("cmrx_theme") || localStorage.getItem("cmrx_theme") || "light";
      this.apply(saved, icon);
      if (btn) {
        btn.addEventListener("click", () => {
          const cur = root.getAttribute("data-theme");
          const next = cur === "dark" ? "light" : "dark";
          this.apply(next, icon);
          sessionStorage.setItem("cmrx_theme", next);
          localStorage.setItem("cmrx_theme", next);
          window.dispatchEvent(new Event("cmrx:themechange"));
        });
      }
      window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", (e) => {
        if (!sessionStorage.getItem("cmrx_theme") && !localStorage.getItem("cmrx_theme")) {
          this.apply(e.matches ? "dark" : "light", icon);
        }
      });
    },
    apply(next, icon) {
      const root = document.documentElement;
      root.setAttribute("data-theme", next);
      if (icon) icon.className = next === "dark" ? "fas fa-sun" : "fas fa-moon";
    },
  };

  // ---------- Live date ----------
  const liveDate = {
    init() {
      const el = document.getElementById("liveDate");
      if (!el) return;
      const opts = { weekday: "short", year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit" };
      const tick = () => { el.textContent = new Date().toLocaleString(undefined, opts); };
      tick();
      setInterval(tick, 1000);
    },
  };

  // ---------- Toast ----------
  const toast = {
    container: null,
    ensure() {
      if (this.container) return this.container;
      this.container = document.createElement("div");
      this.container.className = "toast-container";
      document.body.appendChild(this.container);
      return this.container;
    },
    show(message, type = "info", duration = 4000) {
      const box = document.createElement("div");
      box.className = `toast toast-${type}`;
      const icons = { success: "fa-check-circle", error: "fa-exclamation-circle", info: "fa-info-circle" };
      box.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${this.escape(message)}</span>
        <button class="toast-close" aria-label="Dismiss">&times;</button>`;
      const close = box.querySelector(".toast-close");
      close.addEventListener("click", () => this.remove(box));
      this.ensure().appendChild(box);
      if (duration > 0) {
        setTimeout(() => this.remove(box), duration);
      }
      return box;
    },
    remove(box) {
      if (!box || !box.isConnected) return;
      box.style.opacity = "0";
      box.style.transform = "translateX(20px)";
      setTimeout(() => { if (box.isConnected) box.remove(); }, 300);
    },
    escape(str) {
      const d = document.createElement("div");
      d.textContent = str;
      return d.innerHTML;
    },
  };

  // ---------- Modal ----------
  const modal = {
    open(id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.add("open");
      el.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    },
    close(id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.remove("open");
      el.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    },
    init() {
      document.querySelectorAll(".modal-backdrop").forEach((m) => {
        m.addEventListener("click", (e) => {
          if (e.target === m) this.close(m.id);
        });
        m.querySelectorAll("[data-modal-close]").forEach((b) => {
          b.addEventListener("click", () => this.close(m.id));
        });
      });
    },
  };

  // ---------- Sidebar collapse (desktop) ----------
  const sidebar = {
    init() {
      const btn = document.getElementById("sidebarToggle");
      const side = document.querySelector(".sidebar");
      if (!btn || !side) return;
      const collapsed = sessionStorage.getItem("cmrx_sidebar_collapsed") === "1";
      if (collapsed) side.classList.add("collapsed");
      btn.addEventListener("click", () => {
        const next = !side.classList.contains("collapsed");
        side.classList.toggle("collapsed");
        sessionStorage.setItem("cmrx_sidebar_collapsed", next ? "1" : "0");
        btn.setAttribute("aria-expanded", String(!next));
      });
    },
  };

  // ---------- Client-side search ----------
  const search = {
    init() {
      const input = document.querySelector(".nav-search input");
      const cards = document.querySelectorAll(".info-item[data-search]");
      if (!input || !cards.length) return;
      input.addEventListener("input", (e) => {
        const q = e.target.value.toLowerCase().trim();
        cards.forEach((c) => {
          const text = c.dataset.search.toLowerCase();
          c.style.display = q === "" || text.includes(q) ? "" : "none";
        });
      });
    },
  };

  // ---------- Keyboard shortcuts ----------
  const shortcuts = {
    init() {
      const hints = document.querySelectorAll("[data-shortcut]");
      document.addEventListener("keydown", (e) => {
        if (!e.altKey) return;
        hints.forEach((h) => {
          const key = h.dataset.shortcut;
          if (e.key.toLowerCase() === key.toLowerCase()) {
            e.preventDefault();
            const href = h.dataset.href || h.getAttribute("href");
            if (href) location.href = href;
            else h.click?.();
          }
        });
      });
    },
  };

  // ---------- Smooth scroll ----------
  const scrollTo = (id) => {
    const el = document.querySelector(id);
    if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  // ---------- Public API ----------
  return {
    theme,
    liveDate,
    toast,
    modal,
    sidebar,
    search,
    shortcuts,
    scrollTo,
    init() {
      this.theme.init();
      this.liveDate.init();
      this.sidebar.init();
      this.search.init();
      this.shortcuts.init();
      this.modal.init();
    },
  };
})();

document.addEventListener("DOMContentLoaded", () => CMRx.init());

// Global helpers for inline HTML onclick attributes
window.cmrxToast = (msg, type) => CMRx.toast.show(msg, type);
window.cmrxModalOpen = (id) => CMRx.modal.open(id);
window.cmrxModalClose = (id) => CMRx.modal.close(id);
window.cmrxScrollTo = (id) => CMRx.scrollTo(id);
