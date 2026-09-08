const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const nodes = new Map();
function node(selector) {
  if (!nodes.has(selector)) nodes.set(selector, {
    value: '', hidden: false, disabled: false, textContent: '', files: [], events: {},
    addEventListener(name, callback) { this.events[name] = callback; },
    append() {}, replaceChildren() {}, focus() {},
    reportValidity() { return this.value.includes('@'); },
  });
  return nodes.get(selector);
}
const form = node('#email-series-form');
form.elements = Object.fromEntries(['subject', 'body', 'frequency', 'monthDay', 'weekDay', 'sendTime', 'attachment', 'recipientMode', 'recipientEmail']
  .map(name => [name, node(name)]));
let resets = 0;
form.reset = () => { resets++; };
const dialog = node('#email-series-dialog');
dialog.showModal = () => { dialog.open = true; };
dialog.close = () => { dialog.open = false; };
const document = { querySelector: node, createElement: node, addEventListener(name, callback) { this[name] = callback; } };
const calls = [];
const fixture = { csrfToken: 'local-test', series: null, customer: {name: 'Fixture', email: 'contract@example.invalid'}, eligible: true, runs: [],
  deliverySettings: {customerEmailsEnabled: false, contractEmailsEnabled: false, testEmailsEnabled: false}, canManageDelivery: true };
const context = {
  document, Intl, Date,
  FormData: class extends Map { constructor() { super(Object.entries(form.elements).map(([name, control]) => [name, control.value])); } },
  fetch: async (url, options) => {
    calls.push(options);

    return {ok: true, json: async () => options.method === 'POST' ? {...fixture, testSent: true, recipient: options.body.get('recipientEmail')} : fixture};
  },
};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../customer-email-series.js'), 'utf8'), context);
const settle = () => new Promise(resolve => setImmediate(resolve));
(async () => {
  assert.equal(calls.length, 0, 'No API request before opening');
  document.click({target: {closest: () => ({dataset: {customerId: 'fixture'}, focus() {}})}});
  await settle();
  assert.equal(form.elements.recipientMode.value, 'contract');
  assert.equal(node('#series-manual-recipient').hidden, true);
  form.elements.recipientMode.value = 'manual';
  form.elements.recipientMode.events.change();
  assert.equal(node('#series-manual-recipient').hidden, false);
  form.elements.subject.value = 'Unsaved subject';
  form.elements.body.value = 'Unsaved body';
  form.elements.attachment.value = 'new-checklist.txt';
  await form.events.submit({preventDefault() {}, submitter: {value: 'test'}});
  assert.equal(calls.length, 1, 'Empty manual address must not send');
  form.elements.recipientEmail.value = 'manual@example.invalid';
  const before = resets;
  await form.events.submit({preventDefault() {}, submitter: {value: 'test'}});
  assert.equal(calls[1].body.get('action'), 'test');
  assert.equal(calls[1].body.get('recipientEmail'), 'manual@example.invalid');
  assert.equal(calls[1].body.get('subject'), 'Unsaved subject');
  assert.equal(calls[1].body.get('attachment'), 'new-checklist.txt');
  assert.equal(resets, before, 'Test must preserve unsaved editor values');
  assert.equal(form.elements.body.value, 'Unsaved body');
  assert.match(node('#series-test-status').textContent, /manual@example.invalid/);
  assert.equal(node('#email-series-fields').disabled, false);
  const toggle = node('#series-enabled');
  assert.equal(toggle.checked, false);
  assert.equal(node('#series-test').disabled, false, 'Paused series and global restrictions must not block tests');
  toggle.checked = true;
  toggle.events.change();
  assert.equal(calls.length, 2, 'Switch is persisted only when saving');
  assert.equal(resets, before);
  assert.equal(node('#series-enabled-label').textContent, 'Ein');
  await form.events.submit({preventDefault() {}, submitter: {value: 'save'}});
  assert.equal(calls.at(-1).body.get('action'), 'activate');
  toggle.checked = false;
  toggle.events.change();
  await form.events.submit({preventDefault() {}, submitter: {value: 'save'}});
  assert.equal(calls.at(-1).body.get('action'), 'draft');
  assert.equal(node('#series-test').disabled, false);
  const previousBody = form.elements.body.value;
  node('#series-insert-template').events.click();
  assert.ok(form.elements.body.value.startsWith(previousBody), 'Template insertion must keep existing text');
  assert.match(form.elements.body.value, /\{\{firma\}\}/);
  console.log('PASS: recipient validation and unsaved test input preserved');
  console.log('PASS: one per-customer switch, save activation/pause, tests allowed with global restrictions off');
})().catch(error => { console.error(error); process.exitCode = 1; });
