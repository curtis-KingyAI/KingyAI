(function () {
    'use strict';

    var fieldMap = {
        kali_q: 'q',
        kali_category: 'category',
        kali_period: 'period',
        kali_free_plan: 'free_plan',
        kali_api_available: 'api_available',
        kali_open_weight: 'open_source_or_open_weight',
        kali_sort: 'sort',
        kali_video_demo: 'video_demo',
        kali_youtube_potential: 'youtube_potential',
        kali_launch_type: 'launch_type',
        kali_audience: 'audience',
        kali_attribute: 'attribute',
        kali_page: 'page'
    };

    function parseQuery(wrapper) {
        try {
            var value = JSON.parse(wrapper.getAttribute('data-live-query') || '{}');
            return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
        } catch (error) {
            return {};
        }
    }

    function queryFromUrl(base) {
        var query = Object.assign({}, base || {});
        var params = new URLSearchParams(window.location.search);
        Object.keys(fieldMap).forEach(function (name) {
            if (params.has(name)) {
                query[fieldMap[name]] = params.get(name);
            }
        });
        return query;
    }

    function queryFromForm(form, base) {
        var query = Object.assign({}, base || {});
        var data = new FormData(form);
        Object.keys(fieldMap).forEach(function (name) {
            if (data.has(name)) {
                var value = String(data.get(name) || '');
                if (value) {
                    query[fieldMap[name]] = value;
                } else {
                    delete query[fieldMap[name]];
                }
            }
        });
        query.page = 1;
        return query;
    }

    function publicUrlForQuery(query) {
        var params = new URLSearchParams();
        Object.keys(fieldMap).forEach(function (name) {
            var key = fieldMap[name];
            var value = query[key];
            if (value === undefined || value === null || value === '' || value === false) {
                return;
            }
            if (key === 'sort' && value === 'newest') {
                return;
            }
            params.set(name, String(value));
        });
        var search = params.toString();
        return window.location.pathname + (search ? '?' + search : '') + window.location.hash;
    }

    function liveStatus(wrapper, message, state) {
        var status = wrapper.querySelector('[data-kingy-ali-live-status]');
        wrapper.setAttribute('data-live-state', state);
        if (status) {
            status.textContent = message;
        }
    }

    function announceLiveResult(wrapper, message) {
        var announcer = wrapper.querySelector('[data-kingy-ali-live-announcer]');
        if (!announcer || !message) {
            return;
        }

        announcer.textContent = '';
        window.setTimeout(function () {
            announcer.textContent = message;
        }, 0);
    }

    function liveResultLabel(content) {
        var summary = content.querySelector('.kingy-ali-results-summary');
        var label = summary ? summary.textContent.replace(/\s+/g, ' ').trim() : '';
        return label ? 'Launch results updated. ' + label + '.' : 'Launch results updated.';
    }

    function liveLabel(payload) {
        var generated = payload.generated_at ? new Date(payload.generated_at) : null;
        var stamp = generated && !Number.isNaN(generated.getTime())
            ? generated.toLocaleString()
            : 'just now';
        var lag = payload.coverage_lag_days;
        var lagLabel = Number.isInteger(lag) ? ' Coverage lag: ' + lag + (lag === 1 ? ' day.' : ' days.') : '';
        return 'Live launch index refreshed ' + stamp + '. Generation ' + payload.data_generation + '.' + lagLabel;
    }

    function validatePayload(payload) {
        return payload
            && payload.schema_version === 1
            && typeof payload.data_generation === 'string'
            && typeof payload.html === 'string'
            && Array.isArray(payload.items)
            && Number.isInteger(payload.total_count)
            && Number.isInteger(payload.result_count);
    }

    function requestLive(wrapper, query, options) {
        var endpoint = wrapper.getAttribute('data-live-endpoint') || '';
        var content = wrapper.querySelector('[data-kingy-ali-live-content]');
        var shouldAnnounce = Boolean(options && options.announce);
        if (!endpoint || !content || !window.fetch) {
            liveStatus(wrapper, 'Cached fallback. Live launch data could not be confirmed in this browser.', 'degraded');
            if (shouldAnnounce) {
                announceLiveResult(wrapper, 'Launch results could not be refreshed. Cached launch results remain available.');
            }
            return Promise.resolve(false);
        }

        var payload = Object.assign({}, query || {});
        payload.base_path = wrapper.getAttribute('data-live-base-path') || window.location.pathname;
        liveStatus(wrapper, 'Checking the live launch index…', 'loading');

        var controller = window.AbortController ? new AbortController() : null;
        var timeout = window.setTimeout(function () {
            if (controller) {
                controller.abort();
            }
        }, 12000);

        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            redirect: 'error',
            referrerPolicy: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
            signal: controller ? controller.signal : undefined
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('live index HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (responsePayload) {
                if (!validatePayload(responsePayload)) {
                    throw new Error('invalid live index schema');
                }
                content.innerHTML = responsePayload.html;
                wrapper.setAttribute('data-live-query', JSON.stringify(query));
                wrapper.setAttribute('data-generation', responsePayload.data_generation);
                wrapper.setAttribute('data-total-count', String(responsePayload.total_count));
                liveStatus(wrapper, liveLabel(responsePayload), 'live');
                if (shouldAnnounce) {
                    announceLiveResult(wrapper, liveResultLabel(content));
                }
                return true;
            })
            .catch(function () {
                liveStatus(wrapper, 'Cached fallback. The live launch index is temporarily unavailable, so this page is not claiming current data.', 'degraded');
                if (shouldAnnounce) {
                    announceLiveResult(wrapper, 'Launch results could not be refreshed. Cached launch results remain available.');
                }
                return false;
            })
            .finally(function () {
                window.clearTimeout(timeout);
            });
    }

    function nearestWrapper(form) {
        var section = form.closest('section');
        if (section) {
            var scoped = section.querySelector('[data-kingy-ali-live-results]');
            if (scoped) {
                return scoped;
            }
        }
        return document.querySelector('[data-kingy-ali-live-results]');
    }

    function initWrapper(wrapper) {
        var query = queryFromUrl(parseQuery(wrapper));
        requestLive(wrapper, query, {announce: false});

        wrapper.addEventListener('click', function (event) {
            var link = event.target.closest('.kingy-ali-pagination a');
            if (!link) {
                return;
            }
            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (error) {
                return;
            }
            event.preventDefault();
            var next = queryFromUrl(parseQuery(wrapper));
            var page = parseInt(url.searchParams.get('kali_page') || '1', 10);
            next.page = Number.isFinite(page) ? Math.max(1, page) : 1;
            window.history.pushState({}, '', publicUrlForQuery(next));
            requestLive(wrapper, next, {announce: true}).then(function (ok) {
                if (ok) {
                    wrapper.scrollIntoView({behavior: 'smooth', block: 'start'});
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var wrappers = Array.prototype.slice.call(document.querySelectorAll('[data-kingy-ali-live-results]'));
        wrappers.forEach(initWrapper);

        document.querySelectorAll('[data-kingy-ali-live-search]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var wrapper = nearestWrapper(form);
                if (!wrapper) {
                    return;
                }
                event.preventDefault();
                var query = queryFromForm(form, parseQuery(wrapper));
                window.history.pushState({}, '', publicUrlForQuery(query));
                requestLive(wrapper, query, {announce: true});
            });
        });

        window.addEventListener('popstate', function () {
            wrappers.forEach(function (wrapper) {
                requestLive(wrapper, queryFromUrl(parseQuery(wrapper)), {announce: true});
            });
        });
    });
}());
