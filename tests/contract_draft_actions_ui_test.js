const fs = require("fs");
const path = require("path");

const app = fs.readFileSync(path.join(__dirname, "..", "app.js"), "utf8");
const index = fs.readFileSync(path.join(__dirname, "..", "index.html"), "utf8");

function assertIncludes(source, needle, message) {
  if (!source.includes(needle)) {
    throw new Error(message);
  }
}

assertIncludes(
  app,
  'document.addEventListener("click", handleRecordAction)',
  "Die Aktionen der Vertragsentwürfe sind nicht zentral registriert.",
);

for (const action of ["edit-offer", "open-contract", "open-email-template", "copy-offer-link"]) {
  assertIncludes(app, `action === "${action}"`, `Der Handler für ${action} fehlt.`);
}

for (const action of ["edit-offer", "open-contract", "open-email-template", "copy-offer-link"]) {
  assertIncludes(index + app, `data-action="${action}"`, `Der Button für ${action} fehlt.`);
}

if (index.includes('app.js?v=discount-pricing-20260912')) {
  throw new Error("Die veraltete JavaScript-Version kann weiterhin aus dem Browsercache geladen werden.");
}

console.log("PASS: all contract draft actions use resilient delegated event handling");
