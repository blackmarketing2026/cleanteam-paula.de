// Druck- und Kopiersperre fuer alle HTML-Seiten. Ausdrucke laufen nur ueber die exportierten PDFs.
(function () {
  var noticeTimer = null;

  function isEditable(target) {
    return !!(target && target.closest && target.closest("input, textarea, select, [contenteditable]"));
  }

  function showNotice(text) {
    var notice = document.querySelector(".print-block-notice");
    if (!notice) {
      notice = document.createElement("div");
      notice.className = "print-block-notice";
      notice.setAttribute("role", "status");
      document.body.appendChild(notice);
    }
    notice.textContent = text;
    notice.hidden = false;
    window.clearTimeout(noticeTimer);
    noticeTimer = window.setTimeout(function () {
      notice.hidden = true;
    }, 3000);
  }

  document.addEventListener("keydown", function (event) {
    var key = (event.key || "").toLowerCase();
    if ((event.ctrlKey || event.metaKey) && !event.shiftKey && key === "p") {
      event.preventDefault();
      showNotice("Drucken ist nicht möglich. Bitte laden Sie das PDF herunter.");
    }
  }, true);

  ["contextmenu", "copy", "cut", "dragstart"].forEach(function (type) {
    document.addEventListener(type, function (event) {
      if (isEditable(event.target)) return;
      event.preventDefault();
    }, true);
  });
})();
