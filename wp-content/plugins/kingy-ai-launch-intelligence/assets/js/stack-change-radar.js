(function () {
  'use strict';

  var root = document.querySelector('.ksr');
  if (!root || !window.localStorage) return;

  var key = (window.KingyStackRadar && window.KingyStackRadar.storageKey) || 'kingyStackChangeRadar.v1';
  var summary = document.getElementById('ksr-saved-summary');
  var showButton = document.getElementById('ksr-show-saved');
  var clearButton = document.getElementById('ksr-clear-saved');
  var visibleCount = document.getElementById('ksr-visible-count');
  var cards = Array.prototype.slice.call(document.querySelectorAll('.ksr-card'));
  var onlySaved = false;

  function read() {
    try {
      var value = JSON.parse(localStorage.getItem(key) || '{"vendors":[],"components":[]}');
      return {
        vendors: Array.isArray(value.vendors) ? value.vendors : [],
        components: Array.isArray(value.components) ? value.components : []
      };
    } catch (error) {
      return { vendors: [], components: [] };
    }
  }

  function write(value) {
    localStorage.setItem(key, JSON.stringify(value));
  }

  function tokens(value) {
    return value.split(',').map(function (item) { return item.trim().toLowerCase(); }).filter(Boolean);
  }

  function matches(card, saved) {
    var vendor = (card.dataset.vendor || '').toLowerCase();
    var components = tokens(card.dataset.components || '');
    return saved.vendors.indexOf(vendor) !== -1 ||
      components.some(function (component) { return saved.components.indexOf(component) !== -1; });
  }

  function render() {
    var saved = read();
    var total = saved.vendors.length + saved.components.length;
    summary.textContent = total
      ? saved.vendors.concat(saved.components).join(' · ')
      : 'Nothing saved yet.';

    var visible = 0;
    cards.forEach(function (card) {
      var isMatch = matches(card, saved);
      card.hidden = onlySaved && !isMatch;
      if (!card.hidden) visible += 1;
      var button = card.querySelector('.ksr-save');
      if (button) {
        button.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
        button.textContent = isMatch ? 'Saved to your stack' : 'Save to my stack';
      }
    });
    showButton.disabled = !total;
    showButton.setAttribute('aria-pressed', onlySaved ? 'true' : 'false');
    showButton.textContent = onlySaved ? 'Show all changes' : 'Show my stack only';
    visibleCount.textContent = onlySaved ? visible + ' saved-stack matches shown' : '';
  }

  root.addEventListener('click', function (event) {
    var button = event.target.closest('.ksr-save');
    if (!button) return;
    var saved = read();
    var vendor = (button.dataset.saveVendor || '').toLowerCase();
    var components = tokens(button.dataset.saveComponents || '');
    var active = button.getAttribute('aria-pressed') === 'true';
    if (active) {
      saved.vendors = saved.vendors.filter(function (item) { return item !== vendor; });
      saved.components = saved.components.filter(function (item) { return components.indexOf(item) === -1; });
    } else {
      if (vendor && saved.vendors.indexOf(vendor) === -1) saved.vendors.push(vendor);
      components.forEach(function (component) {
        if (saved.components.indexOf(component) === -1) saved.components.push(component);
      });
    }
    write(saved);
    render();
  });

  showButton.addEventListener('click', function () {
    onlySaved = !onlySaved;
    render();
  });

  clearButton.addEventListener('click', function () {
    write({ vendors: [], components: [] });
    onlySaved = false;
    render();
  });

  render();
}());
