const icons = {
  "add": "icon-plus-circle",
  "alloc": "icon-alloc",
  "button_ok": "icon-button-ok",
  "cal": "icon-cal",
  "cancel": "icon-block-flipped",
  "closed": "icon-lock",
  "credit": "icon-credit",
  "delete": "icon-delete",
  "download": "icon-download",
  "edit": "icon-pencil",
  "escape": "icon-cancel",
  "gl": "icon-gl",
  "help": "icon-help-outline",
  "invoice": "icon-invoice",
  "locate": "icon-search",
  "lock": "icon-lock",
  "log": "icon-log",
  "login": "icon-logout",
  "menu_entry": "icon-data",
  "menu_inquiry": "icon-view",
  "menu_maintenance": "icon-setup-master",
  "menu_report": "icon-reports",
  "menu_settings": "icon-setup-master",
  "menu_system": "icon-tools",
  "menu_transaction": "icon-feature",
  "menu_update": "icon-globe",
  "money": "icon-banknote",
  "ok": "icon-button-ok",
  "pdf": "icon-file-pdf",
  "preferences": "icon-prefs",
  "print": "icon-print",
  "receive": "icon-receive",
  "remove": "icon-minus-outline",
  "report": "icon-reports",
  "right": "icon-deliver",
  "sort_asc": "icon-sort-asc",
  "sort_desc": "icon-sort-desc",
  "sort_none": "icon-sort-none",
  "spacer": "icon-spacer",
  "view": "icon-view",
  "warning": "icon-warning"
};
function initUI() {
  function refreshUI() {
    document.querySelectorAll("td > table, th > table").forEach((table) => {
      table.parentElement.classList.add("has-table");
    });
    document.querySelectorAll("span > select").forEach((select) => {
      select.parentElement.classList.add("has-select");
    });
    document.querySelectorAll("span + input[name$='_update']").forEach((update) => {
      update.previousElementSibling.classList.add("has-updatable-sibling");
    });
    document.querySelectorAll("span + img").forEach((img) => {
      img.previousElementSibling.classList.add("has-img-sibling");
    });
    document.querySelectorAll("ul.ajaxtabs li > button.current").forEach((current) => {
      current.parentElement.classList.add("has-current");
    });
    document.querySelectorAll('button > img, input[name$="_update"] + img, span.has-select + img, input + a[href^="javascript:date_picker"] > img').forEach((img) => {
      const baseName = img.src.split("/").pop().split(".").shift();
      const parent = img.parentElement;
      if (!icons[baseName]) return;
      const icon = document.createElement("span");
      icon.classList.add("cu-icon", icons[baseName], "text-lg");
      icon.title = img.title;
      icon.onclick = img.onclick;
      parent.dataset.icon = baseName;
      parent.classList.add("has-img-icon");
      parent.replaceChild(icon, img);
      if (parent.children.length == 1) {
        parent.classList.add("is-icon");
      }
    });
  }
  function monkeyPatchFADatePicker() {
    const originalDatePicker = window.date_picker;
    if (!originalDatePicker) {
      return;
    }
    const containerEl = document.querySelector(".main-content");
    const calendarId = "CC";
    let currentInput = null;
    let currentBounds = null;
    let lastScrollTop = containerEl.scrollTop;
    window.date_picker = function date_picker(textField) {
      var _a, _b;
      currentInput = textField;
      currentBounds = new positionInfo(containerEl);
      currentBounds = {
        t: currentBounds.getElementTop(),
        l: currentBounds.getElementLeft(),
        w: currentBounds.getElementWidth(),
        h: currentBounds.getElementHeight(),
        r: currentBounds.getElementRight(),
        b: currentBounds.getElementBottom()
      };
      originalDatePicker.apply(this, arguments);
      if ((_b = (_a = window.cC) == null ? void 0 : _a.visible) == null ? void 0 : _b.call(_a)) {
        const calendar = document.getElementById(calendarId);
        let calendarPos = new positionInfo(calendar);
        calendarPos = {
          t: calendarPos.getElementTop(),
          l: calendarPos.getElementLeft(),
          w: calendarPos.getElementWidth(),
          h: calendarPos.getElementHeight(),
          r: calendarPos.getElementRight(),
          b: calendarPos.getElementBottom()
        };
        let fieldPos = new positionInfo(currentInput);
        fieldPos = {
          t: fieldPos.getElementTop(),
          l: fieldPos.getElementLeft(),
          w: fieldPos.getElementWidth(),
          h: fieldPos.getElementHeight(),
          r: fieldPos.getElementRight(),
          b: fieldPos.getElementBottom()
        };
        const outOfBounds = {
          t: calendarPos.t < currentBounds.t,
          l: calendarPos.l < currentBounds.l,
          r: calendarPos.r > currentBounds.r,
          b: calendarPos.b > currentBounds.b
        };
        if (outOfBounds.r) {
          calendar.style.left = fieldPos.r - calendarPos.w + "px";
          calendar.style.right = "auto";
        }
        if (outOfBounds.b && fieldPos.t - calendarPos.h > currentBounds.t) {
          calendar.style.top = fieldPos.t - calendarPos.h + "px";
          calendar.style.bottom = "auto";
        }
      }
    };
    containerEl.addEventListener("scroll", function() {
      var _a, _b;
      let scrollTop = containerEl.scrollTop;
      let dir = scrollTop > lastScrollTop ? "up" : "down";
      let scrolled = Math.abs(scrollTop - lastScrollTop);
      if ((_b = (_a = window.cC) == null ? void 0 : _a.visible) == null ? void 0 : _b.call(_a)) {
        const calendar = document.getElementById(calendarId);
        let calendarPos = new positionInfo(calendar);
        calendarPos = {
          t: calendarPos.getElementTop(),
          l: calendarPos.getElementLeft(),
          w: calendarPos.getElementWidth(),
          h: calendarPos.getElementHeight(),
          r: calendarPos.getElementRight(),
          b: calendarPos.getElementBottom()
        };
        let fieldPos = new positionInfo(currentInput);
        fieldPos = {
          t: fieldPos.getElementTop(),
          l: fieldPos.getElementLeft(),
          w: fieldPos.getElementWidth(),
          h: fieldPos.getElementHeight(),
          r: fieldPos.getElementRight(),
          b: fieldPos.getElementBottom()
        };
        const outOfBounds = {
          t: calendarPos.t < currentBounds.t,
          l: calendarPos.l < currentBounds.l,
          r: calendarPos.r > currentBounds.r,
          b: calendarPos.b > currentBounds.b
        };
        if (dir === "up") {
          calendar.style.top = calendarPos.t - scrolled + "px";
          calendar.style.bottom = "auto";
          if (outOfBounds.t && fieldPos.b + calendarPos.h < currentBounds.b) {
            calendar.style.top = fieldPos.b + "px";
            calendar.style.bottom = "auto";
          }
        } else {
          calendar.style.top = calendarPos.t + scrolled + "px";
          calendar.style.bottom = "auto";
          if (outOfBounds.b && fieldPos.t - calendarPos.h > currentBounds.t) {
            calendar.style.top = fieldPos.t - calendarPos.h + "px";
            calendar.style.bottom = "auto";
          }
        }
      }
      lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });
  }
  function monkeyPatchFASetMark() {
    const originalSetMark = window.set_mark;
    if (!originalSetMark) {
      return;
    }
    window.set_mark = function set_mark(img) {
      originalSetMark.apply(this, arguments);
      if (!img) {
        setTimeout(refreshUI);
      }
    };
  }
  function handleMinimizeSidebarButton() {
    const btn = document.getElementById("sidebar-toggle");
    if (!btn) {
      return;
    }
    const container = document.querySelector(".main-container");
    if (!container) {
      return;
    }
    btn.addEventListener("click", function() {
      const collapsed = container.classList.toggle("sidebar-collapsed");
      document.cookie = "sidebar_collapsed=" + (collapsed ? "1" : "0") + "; path=/; SameSite=Lax";
    });
    if (document.cookie.match(/sidebar_collapsed=1/)) {
      container.classList.add("sidebar-collapsed");
    }
  }
  document.addEventListener("DOMContentLoaded", function() {
    refreshUI();
    monkeyPatchFADatePicker();
    monkeyPatchFASetMark();
    handleMinimizeSidebarButton();
  });
}
initUI();
