// Druck- und Kopiersperre fuer alle HTML-Seiten. Ausdrucke laufen nur ueber die exportierten PDFs.
// Screenshots lassen sich im Browser technisch nicht verhindern; die Tastenkuerzel werden nur
// abgefangen, um abzuschrecken.
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
      return;
    }

    var captureShortcut = event.key === "PrintScreen"
      || ((event.ctrlKey || event.metaKey) && event.shiftKey && ["3", "4", "5", "s"].indexOf(key) !== -1);
    if (!captureShortcut) return;
    event.preventDefault();
    document.body.style.visibility = "hidden";
    window.setTimeout(function () {
      document.body.style.visibility = "";
    }, 1200);
  }, true);

  // PrintScreen loest unter Windows oft nur keyup aus.
  document.addEventListener("keyup", function (event) {
    if (event.key !== "PrintScreen") return;
    document.body.style.visibility = "hidden";
    window.setTimeout(function () {
      document.body.style.visibility = "";
    }, 1200);
  }, true);

  ["contextmenu", "copy", "cut", "dragstart"].forEach(function (type) {
    document.addEventListener(type, function (event) {
      if (isEditable(event.target)) return;
      event.preventDefault();
    }, true);
  });
})();
