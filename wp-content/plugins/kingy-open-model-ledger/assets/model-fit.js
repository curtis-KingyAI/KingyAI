(function () {
    'use strict';

    var app = document.querySelector('[data-koml-fit-app]');
    if (!app) {
        return;
    }

    var form = app.querySelector('[data-koml-fit-form]');
    var model = app.querySelector('[data-koml-model]');
    var total = app.querySelector('[data-koml-total]');
    var active = app.querySelector('[data-koml-active]');
    var bits = app.querySelector('[data-koml-bits]');
    var context = app.querySelector('[data-koml-context]');
    var kvBits = app.querySelector('[data-koml-kv]');
    var vram = app.querySelector('[data-koml-vram]');
    var ram = app.querySelector('[data-koml-ram]');
    var offload = app.querySelector('[data-koml-offload]');
    var status = app.querySelector('[data-koml-status]');
    var summary = app.querySelector('[data-koml-summary]');
    var recordLink = app.querySelector('[data-koml-record-link]');
    var observations = app.querySelector('[data-koml-observations]');

    function numberValue(element) {
        return Math.max(0, parseFloat(element.value) || 0);
    }

    function billions(value) {
        var text = String(value || '').trim().toLowerCase().replace(/,/g, '');
        var parsed = parseFloat(text);
        if (!parsed) {
            return 0;
        }
        if (text.indexOf('m') > -1) {
            return parsed / 1000;
        }
        if (text.indexOf('b') > -1) {
            return parsed;
        }
        if (parsed > 1000000) {
            return parsed / 1000000000;
        }
        return parsed;
    }

    function integerValue(value) {
        var parsed = parseFloat(String(value || '').replace(/,/g, ''));
        return parsed > 0 ? parsed : 0;
    }

    function gb(value) {
        return value.toFixed(1) + ' GB';
    }

    function updateFromModel() {
        var option = model.options[model.selectedIndex];
        if (!option || !option.value) {
            recordLink.hidden = true;
            observations.textContent = '';
            return;
        }
        var selectedTotal = billions(option.getAttribute('data-total'));
        var selectedActive = billions(option.getAttribute('data-active')) || selectedTotal;
        var selectedContext = integerValue(option.getAttribute('data-context'));
        if (selectedTotal) {
            total.value = selectedTotal;
        }
        if (selectedActive) {
            active.value = selectedActive;
        }
        if (selectedContext) {
            context.value = selectedContext;
        }
        recordLink.href = option.getAttribute('data-url') || '#';
        recordLink.hidden = !option.getAttribute('data-url');
        var count = parseInt(option.getAttribute('data-observations') || '0', 10);
        observations.textContent = count ? count + (count === 1 ? ' recorded hardware observation' : ' recorded hardware observations') : 'No recorded hardware observation yet';
    }

    function calculate(event) {
        if (event) {
            event.preventDefault();
        }
        var totalB = numberValue(total);
        var activeB = numberValue(active) || totalB;
        var weightBits = numberValue(bits);
        var contextTokens = numberValue(context);
        var cacheBits = numberValue(kvBits);
        var gpuMemory = numberValue(vram);
        var systemMemory = numberValue(ram);

        var weightMemory = totalB * (weightBits / 8) * 1.08;
        var contextK = contextTokens / 1024;
        var kvMemory = activeB * contextK * 0.016 * (cacheBits / 16);
        var runtimeOverhead = Math.max(1.5, weightMemory * 0.08);
        var subtotal = weightMemory + kvMemory + runtimeOverhead;
        var headroom = subtotal * 0.15;
        var workingSet = subtotal + headroom;
        var available = offload.value === 'none' ? gpuMemory : (gpuMemory + systemMemory * (offload.value === 'partial' ? 0.7 : 0.85));
        var ratio = available > 0 ? workingSet / available : 99;
        var label;
        var detail;
        var className;

        if (offload.value === 'none' && workingSet <= gpuMemory * 0.85) {
            label = 'Comfortable fit';
            detail = 'The estimated working set leaves at least 15% of the selected GPU or unified-memory capacity free.';
            className = 'is-comfortable';
        } else if (offload.value === 'none' && workingSet <= gpuMemory) {
            label = 'Tight fit';
            detail = 'The estimate fits on paper but leaves little margin for allocator spikes, larger batches or multimodal components.';
            className = 'is-tight';
        } else if (offload.value !== 'none' && ratio <= 0.85) {
            label = 'Offload required';
            detail = 'The model exceeds GPU-only capacity but fits the selected GPU-plus-system-memory plan. Expect lower throughput than full acceleration.';
            className = 'is-offload';
        } else {
            label = 'Does not fit this plan';
            detail = 'The estimated working set exceeds the usable capacity under these assumptions. Reduce precision or context, choose a smaller variant, or move up a memory tier.';
            className = 'is-no-fit';
        }

        status.className = 'koml-fit-result__status ' + className;
        status.textContent = label;
        summary.textContent = detail;
        app.querySelector('[data-koml-weight-memory]').textContent = gb(weightMemory);
        app.querySelector('[data-koml-kv-memory]').textContent = gb(kvMemory);
        app.querySelector('[data-koml-overhead]').textContent = gb(runtimeOverhead);
        app.querySelector('[data-koml-headroom]').textContent = gb(headroom);
        app.querySelector('[data-koml-total-memory]').textContent = gb(workingSet);
    }

    model.addEventListener('change', function () {
        updateFromModel();
        calculate();
    });
    form.addEventListener('submit', calculate);
    form.addEventListener('input', function (event) {
        if (event.target !== model) {
            calculate();
        }
    });

    updateFromModel();
    calculate();
}());
