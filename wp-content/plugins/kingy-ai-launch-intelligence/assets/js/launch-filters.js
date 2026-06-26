(function () {
    function kingyALITrackEvent(eventType, eventLabel, eventSurface, targetUrl, objectId) {
        if (typeof KingyALI === 'undefined' || !eventType) {
            return;
        }

        var body = new URLSearchParams();
        body.append('action', 'kingy_ali_track_click');
        body.append('nonce', KingyALI.nonce);
        body.append('eventType', eventType);
        body.append('eventLabel', eventLabel || '');
        body.append('objectId', objectId || '');
        body.append('eventSurface', eventSurface || '');
        body.append('targetUrl', targetUrl || '');

        if (navigator.sendBeacon) {
            navigator.sendBeacon(KingyALI.ajaxUrl, body);
            return;
        }

        fetch(KingyALI.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        }).catch(function () {});
    }

    function trackClick(event) {
        var target = event.target.closest('[data-kingy-ali-track]');
        if (!target) {
            return;
        }

        kingyALITrackEvent(
            target.getAttribute('data-kingy-ali-track'),
            target.getAttribute('data-event-label') || target.textContent.trim(),
            target.getAttribute('data-event-surface') || '',
            target.href || '',
            target.getAttribute('data-object-id') || ''
        );
    }

    function scoreInputs(calculator) {
        var data = new URLSearchParams();
        data.append('action', 'kingy_ali_calculate_visibility_score');
        data.append('nonce', KingyALI.visibilityNonce || '');
        calculator.querySelectorAll('[data-score-input]').forEach(function (input) {
            data.append('scores[' + input.getAttribute('data-score-input') + ']', input.value || '0');
        });

        return data;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderRecommendationGroup(title, items) {
        if (!items || !items.length) {
            return '';
        }

        return '<div class="kingy-ali-recommendation-group"><strong>' + escapeHtml(title) + '</strong><ul>' + items.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul></div>';
    }

    function updateCalculatorDisplay(calculator, payload) {
        var output = calculator.querySelector('[data-score-output]');
        var band = calculator.querySelector('[data-score-band]');
        var recommendations = calculator.querySelector('[data-score-recommendations]');
        if (output) {
            output.textContent = String(payload.score || 0);
        }
        if (band) {
            band.textContent = payload.band || 'Needs work';
        }
        if (recommendations) {
            var groups = payload.recommendations || {};
            recommendations.innerHTML = [
                renderRecommendationGroup('Strengths', groups.strengths || []),
                renderRecommendationGroup('Weak spots', groups.weak_spots || []),
                renderRecommendationGroup('Recommended next steps', groups.next_steps || [])
            ].join('');
        }
    }

    function updateCalculator(calculator) {
        if (typeof KingyALI === 'undefined' || !KingyALI.visibilityNonce) {
            return;
        }

        fetch(KingyALI.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: scoreInputs(calculator)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (json) {
                if (json && json.success && json.data) {
                    updateCalculatorDisplay(calculator, json.data);
                }
            })
            .catch(function () {});
    }

    function creatorCampaignRoiNumber(calculator, key) {
        var publicKey = key === 'sponsorship_cost' ? 'creator_campaign_cost' : key;
        var input = calculator.querySelector('[data-creator-campaign-roi-input="' + publicKey + '"]');
        if (!input) {
            input = calculator.querySelector('[data-sponsor-roi-input="' + key + '"]');
        }
        var value = input ? parseFloat(input.value) : 0;
        return Number.isFinite(value) ? value : 0;
    }

    function creatorCampaignRoiOutput(calculator, key, value) {
        var output = calculator.querySelector('[data-creator-campaign-roi-output="' + key + '"]');
        if (!output) {
            output = calculator.querySelector('[data-sponsor-roi-output="' + key + '"]');
        }
        if (output) {
            output.textContent = value;
        }
    }

    function formatPlainNumber(value, digits) {
        return new Intl.NumberFormat(undefined, {
            maximumFractionDigits: digits || 0,
            minimumFractionDigits: digits || 0
        }).format(value);
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat(undefined, {
            currency: 'USD',
            maximumFractionDigits: 0,
            style: 'currency'
        }).format(value);
    }

    function formatCurrencyOptional(value) {
        if (!Number.isFinite(value)) {
            return 'N/A';
        }
        return formatCurrency(value);
    }

    function creatorCampaignRoiBand(roi) {
        if (roi >= 200) {
            return 'Strong upside';
        }
        if (roi >= 50) {
            return 'Promising';
        }
        if (roi >= 0) {
            return 'Needs validation';
        }
        return 'Risky';
    }

    function creatorCampaignRoiNextAction(roi, cac, valuePerConversion, cpm, conversions) {
        if (!Number.isFinite(conversions) || conversions <= 0) {
            return 'Improve landing page or offer before buying reach';
        }
        if (roi >= 200 && cpm <= 250) {
            return 'Negotiate package terms and test';
        }
        if (roi >= 50) {
            return 'Bundle deliverables and validate tracking';
        }
        if (roi >= 0) {
            return 'Lower fee or improve conversion rate';
        }
        if (Number.isFinite(cac) && valuePerConversion > 0 && cac > valuePerConversion) {
            return 'Skip or fix unit economics first';
        }
        return 'Renegotiate before committing budget';
    }

    function creatorCampaignRoiValues(calculator) {
        var views = Math.max(0, creatorCampaignRoiNumber(calculator, 'expected_views'));
        var clickThroughRate = Math.max(0, creatorCampaignRoiNumber(calculator, 'click_through_rate'));
        var conversionRate = Math.max(0, creatorCampaignRoiNumber(calculator, 'conversion_rate'));
        var valuePerConversion = Math.max(0, creatorCampaignRoiNumber(calculator, 'value_per_conversion'));
        var creatorCampaignCost = Math.max(0, creatorCampaignRoiNumber(calculator, 'sponsorship_cost'));

        var clicks = views * (clickThroughRate / 100);
        var conversions = clicks * (conversionRate / 100);
        var revenue = conversions * valuePerConversion;
        var profit = revenue - creatorCampaignCost;
        var roi = creatorCampaignCost > 0 ? (profit / creatorCampaignCost) * 100 : 0;
        var cac = conversions > 0 ? creatorCampaignCost / conversions : NaN;
        var cpm = views > 0 ? creatorCampaignCost / views * 1000 : NaN;
        var breakeven = valuePerConversion > 0 ? creatorCampaignCost / valuePerConversion : NaN;

        return {
            views: views,
            clickThroughRate: clickThroughRate,
            conversionRate: conversionRate,
            valuePerConversion: valuePerConversion,
            creatorCampaignCost: creatorCampaignCost,
            clicks: clicks,
            conversions: conversions,
            revenue: revenue,
            profit: profit,
            roi: roi,
            cac: cac,
            cpm: cpm,
            breakeven: breakeven
        };
    }

    function creatorCampaignRoiDealReadout(calculator) {
        var checked = calculator.querySelectorAll('[data-creator-campaign-roi-deal]:checked').length;
        if (checked >= 6) {
            return 'Strong package: the deal includes enough reusable and measurable value to evaluate beyond first-click conversions.';
        }
        if (checked >= 3) {
            return 'Decent package: add tracking, usage rights, or follow-up assets before final negotiation.';
        }
        if (checked > 0) {
            return 'Light package: ask for more measurable deliverables before relying on the ROI model.';
        }
        return 'Add deal terms to see whether the package has enough reusable value beyond first-click attribution.';
    }

    function creatorCampaignRoiPublicParams(calculator) {
        var params = new URLSearchParams();
        [
            'expected_views',
            'click_through_rate',
            'conversion_rate',
            'value_per_conversion',
            'creator_campaign_cost'
        ].forEach(function (key) {
            var input = calculator.querySelector('[data-creator-campaign-roi-input="' + key + '"]');
            if (input && input.value !== '') {
                params.set(key, input.value);
            }
        });
        return params;
    }

    function setCreatorCampaignRoiStatus(calculator, message) {
        var status = calculator.querySelector('[data-creator-campaign-roi-status]');
        if (status) {
            status.textContent = message || '';
        }
    }

    function copyText(text, onSuccess, onFailure) {
        var success = typeof onSuccess === 'function' ? onSuccess : function () {};
        var failure = typeof onFailure === 'function' ? onFailure : function () {};

        function fallbackCopy() {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            var copied = false;
            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }
            document.body.removeChild(textarea);

            copied ? success() : failure();
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(success).catch(fallbackCopy);
            return;
        }

        fallbackCopy();
    }

    function creatorCampaignRoiShare(calculator) {
        var params = creatorCampaignRoiPublicParams(calculator);
        var url = window.location.origin + window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        copyText(url, function () {
            setCreatorCampaignRoiStatus(calculator, 'Share link copied.');
        }, function () {
            setCreatorCampaignRoiStatus(calculator, url);
        });
    }

    function creatorCampaignRoiCsv(calculator) {
        var values = creatorCampaignRoiValues(calculator);
        var rows = [
            ['Metric', 'Value'],
            ['Expected views', Math.round(values.views)],
            ['Click-through rate', values.clickThroughRate + '%'],
            ['Conversion rate', values.conversionRate + '%'],
            ['Estimated value per lead/customer', values.valuePerConversion],
            ['Creator campaign cost', values.creatorCampaignCost],
            ['Estimated clicks', Math.round(values.clicks)],
            ['Estimated customers/leads', values.conversions.toFixed(1)],
            ['Projected value', values.revenue.toFixed(2)],
            ['Projected profit', values.profit.toFixed(2)],
            ['ROI', values.roi.toFixed(1) + '%'],
            ['CAC', Number.isFinite(values.cac) ? values.cac.toFixed(2) : 'N/A'],
            ['Sponsorship CPM', Number.isFinite(values.cpm) ? values.cpm.toFixed(2) : 'N/A'],
            ['Break-even customers', Number.isFinite(values.breakeven) ? values.breakeven.toFixed(1) : 'N/A'],
            ['Next best action', creatorCampaignRoiNextAction(values.roi, values.cac, values.valuePerConversion, values.cpm, values.conversions)]
        ];
        var csv = rows.map(function (row) {
            return row.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(',');
        }).join('\n');

        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'youtube-sponsorship-roi-estimate.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        setCreatorCampaignRoiStatus(calculator, 'CSV exported.');
    }

    function creatorCampaignRoiApplyPreset(calculator, preset) {
        [
            'expectedViews',
            'clickThroughRate',
            'conversionRate',
            'valuePerConversion',
            'creatorCampaignCost'
        ].forEach(function (datasetKey) {
            var attrKey = datasetKey.replace(/[A-Z]/g, function (letter) {
                return '_' + letter.toLowerCase();
            });
            var input = calculator.querySelector('[data-creator-campaign-roi-input="' + attrKey + '"]');
            if (input && preset.dataset[datasetKey] !== undefined) {
                input.value = preset.dataset[datasetKey];
            }
        });
        updateCreatorCampaignRoi(calculator);
        setCreatorCampaignRoiStatus(calculator, 'Editable preset loaded.');
    }

    function creatorCampaignRoiHydrateFromUrl(calculator) {
        var params = new URLSearchParams(window.location.search);
        [
            'expected_views',
            'click_through_rate',
            'conversion_rate',
            'value_per_conversion',
            'creator_campaign_cost'
        ].forEach(function (key) {
            var value = params.get(key);
            var input = calculator.querySelector('[data-creator-campaign-roi-input="' + key + '"]');
            if (input && value !== null && value !== '') {
                input.value = value;
            }
        });
    }

    function updateCreatorCampaignRoi(calculator) {
        var values = creatorCampaignRoiValues(calculator);

        creatorCampaignRoiOutput(calculator, 'roi', formatPlainNumber(values.roi, 0) + '%');
        creatorCampaignRoiOutput(calculator, 'band', creatorCampaignRoiBand(values.roi));
        creatorCampaignRoiOutput(calculator, 'clicks', formatPlainNumber(values.clicks, 0));
        creatorCampaignRoiOutput(calculator, 'conversions', formatPlainNumber(values.conversions, 1));
        creatorCampaignRoiOutput(calculator, 'revenue', formatCurrency(values.revenue));
        creatorCampaignRoiOutput(calculator, 'profit', formatCurrency(values.profit));
        creatorCampaignRoiOutput(calculator, 'cac', formatCurrencyOptional(values.cac));
        creatorCampaignRoiOutput(calculator, 'cpm', formatCurrencyOptional(values.cpm));
        creatorCampaignRoiOutput(calculator, 'breakeven', Number.isFinite(values.breakeven) ? formatPlainNumber(values.breakeven, 1) : 'N/A');
        creatorCampaignRoiOutput(calculator, 'next_action', creatorCampaignRoiNextAction(values.roi, values.cac, values.valuePerConversion, values.cpm, values.conversions));
        creatorCampaignRoiOutput(calculator, 'deal_readout', creatorCampaignRoiDealReadout(calculator));
    }

    function debounce(fn, delay) {
        var timer;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, delay);
        };
    }

    function appendFieldValue(field, value) {
        var existing = field.value.trim();
        if (!existing) {
            field.value = value;
            return;
        }

        field.value = existing + (field.tagName === 'TEXTAREA' ? '\n' : ' ') + value;
    }

    function promptLine(label, value) {
        value = String(value || '').trim();
        return value ? label + '\n' + value : '';
    }

    function codexField(builder, key) {
        return builder.querySelector('[data-codex-field="' + key + '"]');
    }

    function codexValue(builder, key) {
        var field = codexField(builder, key);
        return field ? field.value.trim() : '';
    }

    function buildCodexPrompt(builder) {
        var build = codexValue(builder, 'build') || 'the requested product';
        var platform = codexValue(builder, 'platform') || 'Codex';
        var parts = [
            'Use ' + platform + ' to build ' + build + '. Keep the first version focused and reviewable.',
            promptLine('Required scope:', codexValue(builder, 'include')),
            promptLine('Testing and verification:', codexValue(builder, 'testing')),
            promptLine('Primary user, if relevant:', codexValue(builder, 'user')),
            promptLine('Do not change:', codexValue(builder, 'not_change')),
            promptLine('Style to match:', codexValue(builder, 'style')),
            promptLine('Data needed:', codexValue(builder, 'data')),
            'Before editing, inspect the existing project and reuse current patterns, components, naming, and styles. If missing context would materially change the implementation, ask up to three clarifying questions; otherwise proceed with conservative assumptions.',
            'Work end to end: keep changes scoped, implement the feature, run the relevant checks, review the result for regressions, and summarize what changed plus any checks that could not be run.'
        ];

        return parts.filter(Boolean).join('\n\n');
    }

    function updateCodexPrompt(builder) {
        var output = builder.querySelector('[data-codex-output]');
        if (output) {
            output.value = buildCodexPrompt(builder);
        }
    }

    function initCodexBuilder(builder) {
        updateCodexPrompt(builder);

        builder.querySelectorAll('[data-codex-suggestion]').forEach(function (select) {
            select.addEventListener('change', function () {
                var value = select.value;
                var field = codexField(builder, select.getAttribute('data-codex-suggestion'));
                if (field && value) {
                    appendFieldValue(field, value);
                    select.selectedIndex = 0;
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });

        builder.querySelectorAll('[data-codex-preset]').forEach(function (preset) {
            preset.addEventListener('click', function () {
                builder.querySelectorAll('[data-codex-field]').forEach(function (field) {
                    var key = field.getAttribute('data-codex-field');
                    field.value = preset.getAttribute('data-' + key) || '';
                });
                updateCodexPrompt(builder);
            });
        });

        builder.querySelectorAll('[data-codex-field]').forEach(function (field) {
            field.addEventListener('input', function () {
                updateCodexPrompt(builder);
            });
            field.addEventListener('change', function () {
                updateCodexPrompt(builder);
            });
        });

        var generate = builder.querySelector('[data-codex-generate]');
        if (generate) {
            generate.addEventListener('click', function () {
                updateCodexPrompt(builder);
                var output = builder.querySelector('[data-codex-output]');
                if (output) {
                    output.focus();
                    output.select();
                }
            });
        }

        var copy = builder.querySelector('[data-codex-copy]');
        if (copy) {
            copy.addEventListener('click', function () {
                var output = builder.querySelector('[data-codex-output]');
                if (!output) {
                    return;
                }
                updateCodexPrompt(builder);
                output.select();
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(output.value).then(function () {
                        copy.textContent = 'Copied';
                        setTimeout(function () {
                            copy.textContent = 'Copy Prompt';
                        }, 1500);
                    }).catch(function () {});
                } else {
                    document.execCommand('copy');
                }
            });
        }

        var form = builder.querySelector('form');
        if (form) {
            form.addEventListener('reset', function () {
                setTimeout(function () {
                    updateCodexPrompt(builder);
                }, 0);
            });
        }
    }

    function copyTextFromField(field, button) {
        var defaultText = button.textContent;
        field.select();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(field.value).then(function () {
                button.textContent = 'Copied';
                setTimeout(function () {
                    button.textContent = defaultText;
                }, 1500);
            }).catch(function () {});
            return;
        }

        document.execCommand('copy');
        button.textContent = 'Copied';
        setTimeout(function () {
            button.textContent = defaultText;
        }, 1500);
    }

    function updateCodexArticleCheck(toolkit) {
        var checks = Array.prototype.slice.call(toolkit.querySelectorAll('[data-codex-check]'));
        var checked = checks.filter(function (check) {
            return check.checked;
        }).length;
        var count = toolkit.querySelector('[data-codex-check-count]');
        var progress = toolkit.querySelector('[data-codex-check-progress]');
        var status = toolkit.querySelector('[data-codex-check-status]');

        if (count) {
            count.textContent = String(checked);
        }
        if (progress) {
            progress.value = checked;
        }
        if (status) {
            if (checked >= checks.length - 1) {
                status.textContent = 'Ready to run';
            } else if (checked >= Math.ceil(checks.length / 2)) {
                status.textContent = 'Getting stronger';
            } else {
                status.textContent = 'Needs structure';
            }
        }
    }

    function initCodexArticleTools(toolkit) {
        updateCodexArticleCheck(toolkit);

        toolkit.querySelectorAll('[data-codex-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                updateCodexArticleCheck(toolkit);
            });
        });

        toolkit.querySelectorAll('[data-codex-example-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var source = document.getElementById(button.getAttribute('data-copy-source') || '');
                if (source) {
                    copyTextFromField(source, button);
                }
            });
        });

        var reset = toolkit.querySelector('[data-codex-check-reset]');
        if (reset) {
            reset.addEventListener('click', function () {
                toolkit.querySelectorAll('[data-codex-check]').forEach(function (check) {
                    check.checked = false;
                });
                updateCodexArticleCheck(toolkit);
            });
        }
    }

    function initAppBuilderComparison(article) {
        var result = article.querySelector('[data-builder-result]');
        var checks = article.querySelectorAll('[data-builder-check]');
        var checkCount = article.querySelector('[data-builder-check-count]');
        var copyPrompt = article.querySelector('[data-builder-copy-prompt]');
        var prompt = article.querySelector('[data-builder-prompt]');
        var scoreInputs = article.querySelectorAll('[data-builder-score]');

        article.querySelectorAll('[data-builder-choice]').forEach(function (button) {
            button.addEventListener('click', function () {
                var recommendation;
                try {
                    recommendation = JSON.parse(button.getAttribute('data-builder-recommendation') || '{}');
                } catch (error) {
                    recommendation = {};
                }

                article.querySelectorAll('[data-builder-choice]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                if (result && recommendation.tool) {
                    result.innerHTML = [
                        '<p class="kingy-ali-kicker">Recommendation</p>',
                        '<h3>' + escapeHtml(recommendation.tool) + '</h3>',
                        '<p>' + escapeHtml(recommendation.why || '') + '</p>',
                        '<p><strong>Next step:</strong> ' + escapeHtml(recommendation.next || '') + '</p>'
                    ].join('');
                }
            });
        });

        function updateCheckCount() {
            if (!checkCount) {
                return;
            }

            var complete = Array.prototype.filter.call(checks, function (check) {
                return check.checked;
            }).length;
            checkCount.textContent = String(complete);
        }

        checks.forEach(function (check) {
            check.addEventListener('change', updateCheckCount);
        });
        updateCheckCount();

        if (copyPrompt && prompt) {
            copyPrompt.addEventListener('click', function () {
                var text = prompt.textContent || '';
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        var old = copyPrompt.textContent;
                        copyPrompt.textContent = 'Copied';
                        setTimeout(function () {
                            copyPrompt.textContent = old;
                        }, 1500);
                    }).catch(function () {});
                    return;
                }

                var range = document.createRange();
                range.selectNodeContents(prompt);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();
            });
        }

        function updateScorecard(toolName) {
            var total = 0;
            article.querySelectorAll('[data-builder-score="' + toolName + '"]').forEach(function (input) {
                total += parseInt(input.value || '0', 10);
            });

            var output = article.querySelector('[data-builder-score-total="' + toolName + '"]');
            if (output) {
                output.textContent = String(total);
            }
        }

        scoreInputs.forEach(function (input) {
            var toolName = input.getAttribute('data-builder-score');
            input.addEventListener('change', function () {
                updateScorecard(toolName);
            });
            updateScorecard(toolName);
        });
    }

    function vibePlannerData() {
        return {
            audiences: {
                creator: 'creator or audience builder',
                founder: 'founder validating a product idea',
                student: 'student or self-learner',
                business: 'small business owner',
                marketer: 'marketer or growth operator',
                wordpress: 'WordPress site owner'
            },
            types: {
                calculator: {
                    label: 'calculator',
                    scope: 'one form, one formula or scoring rule, one result panel, and copy/reset controls',
                    tests: ['empty values', 'large numbers', 'mobile result layout']
                },
                quiz: {
                    label: 'quiz',
                    scope: 'five to eight questions, simple scoring, friendly result copy, and a retake option',
                    tests: ['no answer selected', 'tie scores', 'long result text']
                },
                tracker: {
                    label: 'tracker',
                    scope: 'one add/list/update flow with sample records before real accounts or databases',
                    tests: ['add item', 'edit status', 'refresh behavior']
                },
                directory: {
                    label: 'directory',
                    scope: 'a curated list, category filters, detail cards, and a no-results state',
                    tests: ['filter matches', 'no results', 'link review']
                },
                generator: {
                    label: 'generator',
                    scope: 'a few inputs, structured output, copy button, reset button, and clear limits',
                    tests: ['blank input', 'long input', 'copy output']
                },
                internal: {
                    label: 'internal tool',
                    scope: 'one repeatable workflow, sample records, admin notes, and a manual export or handoff path',
                    tests: ['realistic record', 'status change', 'handoff notes']
                }
            }
        };
    }

    function vibeBuilderPath(type, complexity, constraint) {
        if (constraint === 'wordpress') {
            return 'WordPress Custom HTML or a small plugin-backed shortcode';
        }
        if (constraint === 'code') {
            return 'Codex for repo work or Replit for a browser-based code project';
        }
        if (constraint === 'visual') {
            return 'Lovable or Vercel/v0 for a fast visual prototype';
        }
        if (type === 'tracker' || type === 'internal' || complexity === 'saved') {
            return 'Replit, Bubble, or Softr depending on whether you want code, no-code workflows, or a portal over structured data';
        }
        if (type === 'calculator' || type === 'quiz' || type === 'generator') {
            return 'WordPress Custom HTML, Codex, Vercel/v0, or Replit';
        }
        return 'Codex, Replit, Lovable, or Vercel/v0 after a tiny scope is written';
    }

    function buildVibePrompt(audience, type, scope, tests, builderPath) {
        return [
            '/goal Inspect the current project or builder context first.',
            'Build the smallest working version of this beginner app.',
            '',
            'Audience: ' + audience + '.',
            'App type: ' + type + '.',
            'Recommended builder path: ' + builderPath + '.',
            '',
            'Version-one scope:',
            '- ' + scope + '.',
            '- Keep the UI responsive, readable, accessible, and beginner-friendly.',
            '- Avoid login, payments, file uploads, private data, databases, and fake forms unless this exact MVP truly needs them.',
            '',
            'Done criteria:',
            tests.map(function (test) {
                return '- Test ' + test + '.';
            }).join('\n'),
            '- Verify copy/reset controls, mobile layout, no console errors, and real links.',
            '- Give me a rollback note before publishing.'
        ].join('\n');
    }

    function renderVibePlan(output, values) {
        var data = vibePlannerData();
        var audience = data.audiences[values.audience] || 'beginner';
        var type = data.types[values.type] || data.types.generator;
        var builderPath = vibeBuilderPath(values.type, values.complexity, values.constraint);
        var extra = values.complexity === 'public' ? 'Add owner approval before publishing to real visitors.' : 'Use sample data until the core flow works.';
        var prompt = buildVibePrompt(audience, type.label, type.scope, type.tests, builderPath);

        output.innerHTML = [
            '<p class="kingy-ali-kicker">Recommended first plan</p>',
            '<h3>' + escapeHtml(type.label.charAt(0).toUpperCase() + type.label.slice(1)) + ' for a ' + escapeHtml(audience) + '</h3>',
            '<p><strong>Builder path:</strong> ' + escapeHtml(builderPath) + '</p>',
            '<p><strong>MVP scope:</strong> ' + escapeHtml(type.scope) + '.</p>',
            '<p><strong>Guardrail:</strong> ' + escapeHtml(extra) + '</p>',
            '<h4>QA checklist</h4>',
            '<ul>' + type.tests.map(function (test) {
                return '<li>' + escapeHtml(test) + '</li>';
            }).join('') + '<li>mobile layout, copy/reset, real links, and console errors</li></ul>',
            '<pre data-vibe-generated-prompt>' + escapeHtml(prompt) + '</pre>',
            '<button type="button" data-vibe-copy-generated>Copy Generated Prompt</button>'
        ].join('');
    }

    function initVibePlanner(guide) {
        var form = guide.querySelector('[data-vibe-planner-form]');
        var output = guide.querySelector('[data-vibe-planner-output]');
        if (!form || !output) {
            return;
        }

        function values() {
            var result = {};
            form.querySelectorAll('[data-vibe-field]').forEach(function (field) {
                result[field.getAttribute('data-vibe-field')] = field.value;
            });
            return result;
        }

        function update() {
            renderVibePlan(output, values());
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            update();
        });
        form.addEventListener('change', update);
        form.addEventListener('reset', function () {
            setTimeout(update, 0);
        });
        update();
    }

    function initVibeIdeaGenerator(guide) {
        var result = guide.querySelector('[data-vibe-idea-result]');
        guide.querySelectorAll('[data-vibe-ideas]').forEach(function (button) {
            button.addEventListener('click', function () {
                var payload;
                try {
                    payload = JSON.parse(button.getAttribute('data-vibe-ideas') || '{}');
                } catch (error) {
                    payload = {};
                }

                guide.querySelectorAll('[data-vibe-ideas]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                if (result) {
                    var ideas = payload.ideas || [];
                    result.innerHTML = [
                        '<p class="kingy-ali-kicker">Starter ideas</p>',
                        '<h3>' + escapeHtml(payload.label || 'Audience') + '</h3>',
                        '<p>' + escapeHtml(payload.hint || 'Choose one tiny problem.') + '</p>',
                        '<ul>' + ideas.map(function (idea) {
                            return '<li>' + escapeHtml(idea) + '</li>';
                        }).join('') + '</ul>',
                        '<p><strong>Best next step:</strong> Pick one idea and run it through the planner above.</p>'
                    ].join('');
                }
            });
        });
    }

    function vibeChecklistStorageKey(name) {
        return 'kingyVibeChecklist:' + name + ':' + window.location.pathname.replace(/\/+$/, '');
    }

    function updateVibeChecklist(checklist) {
        var checks = Array.prototype.slice.call(checklist.querySelectorAll('[data-vibe-check]'));
        var done = checks.filter(function (check) {
            return check.checked;
        }).length;
        var count = checklist.querySelector('[data-vibe-check-count]');
        var progress = checklist.querySelector('[data-vibe-check-progress]');
        var status = checklist.querySelector('[data-vibe-check-status]');
        if (count) {
            count.textContent = String(done);
        }
        if (progress) {
            progress.max = checks.length;
            progress.value = done;
        }
        if (status) {
            if (done === checks.length) {
                status.textContent = 'Ready for human approval';
            } else if (done / checks.length >= 0.7) {
                status.textContent = 'Close, finish the risky edges';
            } else if (done / checks.length >= 0.35) {
                status.textContent = 'In progress';
            } else {
                status.textContent = 'Needs review';
            }
        }
    }

    function saveVibeChecklist(checklist) {
        var name = checklist.getAttribute('data-vibe-checklist') || 'default';
        var checked = Array.prototype.slice.call(checklist.querySelectorAll('[data-vibe-check]'))
            .filter(function (check) {
                return check.checked;
            })
            .map(function (check) {
                return check.id;
            });
        try {
            window.localStorage.setItem(vibeChecklistStorageKey(name), JSON.stringify(checked));
        } catch (error) {}
    }

    function restoreVibeChecklist(checklist) {
        var name = checklist.getAttribute('data-vibe-checklist') || 'default';
        var saved = [];
        try {
            saved = JSON.parse(window.localStorage.getItem(vibeChecklistStorageKey(name)) || '[]');
        } catch (error) {}
        checklist.querySelectorAll('[data-vibe-check]').forEach(function (check) {
            check.checked = saved.indexOf(check.id) !== -1;
        });
    }

    function initVibeChecklists(guide) {
        guide.querySelectorAll('[data-vibe-checklist]').forEach(function (checklist) {
            restoreVibeChecklist(checklist);
            checklist.querySelectorAll('[data-vibe-check]').forEach(function (check) {
                check.addEventListener('change', function () {
                    saveVibeChecklist(checklist);
                    updateVibeChecklist(checklist);
                });
            });
            checklist.querySelectorAll('[data-vibe-check-reset]').forEach(function (button) {
                button.addEventListener('click', function () {
                    checklist.querySelectorAll('[data-vibe-check]').forEach(function (check) {
                        check.checked = false;
                    });
                    saveVibeChecklist(checklist);
                    updateVibeChecklist(checklist);
                });
            });
            updateVibeChecklist(checklist);
        });
    }

    function initVibeGuide(guide) {
        initVibePlanner(guide);
        initVibeIdeaGenerator(guide);
        initVibeChecklists(guide);

        guide.addEventListener('click', function (event) {
            var targetButton = event.target.closest('[data-vibe-copy-target]');
            if (targetButton) {
                var target = guide.querySelector(targetButton.getAttribute('data-vibe-copy-target') || '');
                copyPlainText(target ? target.textContent : '', targetButton);
                return;
            }

            var generatedButton = event.target.closest('[data-vibe-copy-generated]');
            if (generatedButton) {
                var generated = guide.querySelector('[data-vibe-generated-prompt]');
                copyPlainText(generated ? generated.textContent : '', generatedButton);
            }
        });
    }

    function copyPlainText(text, button) {
        if (!text || !button) {
            return;
        }

        var old = button.textContent;
        function showCopied() {
            button.textContent = 'Copied';
            setTimeout(function () {
                button.textContent = old || button.getAttribute('data-copy-label') || 'Copy';
            }, 1500);
        }

        function fallbackCopy() {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showCopied();
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showCopied).catch(fallbackCopy);
            return;
        }

        fallbackCopy();
    }

    function leadValue(guide, key) {
        var field = guide.querySelector('[data-lead-architect-field="' + key + '"]');
        return field ? field.value.trim() : '';
    }

    function leadFallback(value, fallback) {
        value = String(value || '').trim();
        return value || fallback;
    }

    function leadSlug(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '') || 'lead-magnet';
    }

    function leadList(items) {
        return '<ul>' + items.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('') + '</ul>';
    }

    function leadPre(text, id) {
        return '<pre class="kingy-ali-lead-plan__copy"><code id="' + escapeHtml(id) + '">' + escapeHtml(text) + '</code></pre>' +
            '<button type="button" data-lead-copy-target="#' + escapeHtml(id) + '">Copy</button>';
    }

    function buildLeadArchitectPlan(guide) {
        var audience = leadFallback(leadValue(guide, 'audience'), 'your ideal audience');
        var problem = leadFallback(leadValue(guide, 'problem'), 'one expensive, urgent problem');
        var offer = leadFallback(leadValue(guide, 'offer'), 'your paid offer');
        var industry = leadFallback(leadValue(guide, 'industry'), 'your market');
        var stage = leadFallback(leadValue(guide, 'stage'), 'problem-aware');
        var format = leadFallback(leadValue(guide, 'format'), 'Assessment');
        var constraints = leadFallback(leadValue(guide, 'constraints'), 'show useful value before optional email capture');
        var title = format + ': ' + audience + ' Action Plan';
        var promise = 'In under five minutes, this helps ' + audience + ' understand ' + problem + ' and leave with a practical next step toward ' + offer + '.';
        var idBase = 'kingy-lead-' + leadSlug(audience + '-' + format);
        var landingCopy = [
            'Headline: ' + title,
            'Subhead: Answer a few questions and get a practical ' + format.toLowerCase() + ' for ' + problem + '.',
            'CTA: Get my result',
            'Trust note: Your answers are used to generate this result. Email is optional and only needed if you want the summary sent to you.'
        ].join('\n\n');
        var optInCopy = [
            'Want the full result and follow-up checklist by email?',
            'Enter your email and confirm consent. We will send this result, one practical follow-up, and an unsubscribe link. You can still use the tool without opting in.'
        ].join('\n\n');
        var deliveryEmail = [
            'Subject: Your ' + title,
            '',
            'Here is your result and the next step I would take first.',
            '',
            'Quick recap:',
            '- Audience: ' + audience,
            '- Problem: ' + problem,
            '- Recommended next step: turn the biggest gap into one small action this week.',
            '',
            'If you want help implementing this, the natural next step is ' + offer + '.'
        ].join('\n');
        var followUp = [
            'Email 1: Send the result, recap the score or recommendation, and ask what surprised them.',
            'Email 2: Share one example from ' + industry + ' and explain the cost of ignoring the problem.',
            'Email 3: Give a small implementation checklist and invite them to ' + offer + '.'
        ].join('\n');
        var codexPrompt = [
            '/goal Build a privacy-aware AI lead magnet for ' + audience + '.',
            '',
            'Problem:',
            problem,
            '',
            'Format:',
            format,
            '',
            'Offer it leads toward:',
            offer,
            '',
            'Requirements:',
            '- Give visitors useful value before email capture.',
            '- Include questions, generated result, landing copy, optional opt-in copy, delivery email, follow-up sequence, privacy notes, copy buttons, and FAQ.',
            '- Keep email consent explicit and unchecked by default.',
            '- Match existing site styles and internal links.',
            '',
            'Trust constraints:',
            constraints,
            '',
            'Verification:',
            '- Test empty inputs, partial inputs, complete inputs, reset, copy buttons, mobile layout, links, metadata, and schema.'
        ].join('\n');

        return {
            title: title,
            promise: promise,
            stage: stage,
            format: format,
            formatRecommendation: format + ' is the best starting format here because the visitor is ' + stage.toLowerCase() + ' and needs a specific result for ' + problem + ' before they are ready to consider ' + offer + '.',
            outline: [
                'Question 1: Identify the visitor context and goal.',
                'Question 2: Capture the size, urgency, or current state of the problem.',
                'Question 3: Ask what they have already tried.',
                'Question 4: Ask what would make the result useful today.',
                'Result: show a recommendation, score, checklist, or first draft before any opt-in.'
            ],
            privacy: [
                'Do not ask for sensitive personal, financial, health, legal, or account data.',
                'Do not hide collection behind the tool.',
                'Keep consent unchecked by default.',
                'Explain what email subscribers receive and how to unsubscribe.',
                'Have a local fallback if the email provider fails.'
            ],
            qa: [
                'Empty input shows a helpful error.',
                'Partial input still guides the visitor without breaking layout.',
                'Complete input creates a useful result.',
                'Copy buttons work.',
                'Mobile layout has no overlapping labels or cramped controls.',
                'Opt-in remains optional and unchecked.',
                'Metadata, FAQ, internal links, and schema are valid.'
            ],
            landingCopy: landingCopy,
            optInCopy: optInCopy,
            deliveryEmail: deliveryEmail,
            followUp: followUp,
            codexPrompt: codexPrompt,
            idBase: idBase
        };
    }

    function renderLeadArchitectPlan(plan) {
        return [
            '<div class="kingy-ali-lead-plan">',
            '<p class="kingy-ali-kicker">Generated plan</p>',
            '<h3>' + escapeHtml(plan.title) + '</h3>',
            '<p><strong>Promise:</strong> ' + escapeHtml(plan.promise) + '</p>',
            '<p><strong>Format:</strong> ' + escapeHtml(plan.format) + ' <strong>Stage:</strong> ' + escapeHtml(plan.stage) + '</p>',
            '<div class="kingy-ali-lead-output-actions">',
            '<button type="button" data-lead-copy-all>Copy full plan</button>',
            '<button type="button" data-lead-print>Print</button>',
            '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Format recommendation</h4>' + leadPre(plan.formatRecommendation, plan.idBase + '-recommendation') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Outline</h4>' + leadList(plan.outline) + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Landing page copy</h4>' + leadPre(plan.landingCopy, plan.idBase + '-landing') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Optional opt-in copy</h4>' + leadPre(plan.optInCopy, plan.idBase + '-optin') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Delivery email</h4>' + leadPre(plan.deliveryEmail, plan.idBase + '-delivery') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Follow-up sequence</h4>' + leadPre(plan.followUp, plan.idBase + '-followup') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Privacy notes</h4>' + leadPre(plan.privacy.join('\n'), plan.idBase + '-privacy') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>Codex build prompt</h4>' + leadPre(plan.codexPrompt, plan.idBase + '-codex') + '</div>',
            '<div class="kingy-ali-lead-plan__section"><h4>QA checklist</h4>' + leadPre(plan.qa.join('\n'), plan.idBase + '-qa') + '</div>',
            '</div>'
        ].join('');
    }

    function initLeadFormatSelector(guide) {
        var result = guide.querySelector('[data-lead-format-result]');
        guide.querySelectorAll('[data-lead-format]').forEach(function (button) {
            button.addEventListener('click', function () {
                var payload;
                try {
                    payload = JSON.parse(button.getAttribute('data-lead-format-payload') || '{}');
                } catch (error) {
                    payload = {};
                }

                guide.querySelectorAll('[data-lead-format]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                if (!result) {
                    return;
                }

                result.innerHTML = [
                    '<p class="kingy-ali-kicker">Recommendation</p>',
                    '<h3>' + escapeHtml(payload.label || 'Lead magnet format') + '</h3>',
                    '<p>' + escapeHtml(payload.best || '') + '</p>',
                    '<p><strong>Ask AI for:</strong> ' + escapeHtml(payload.ai || '') + '</p>',
                    '<p><strong>Measure:</strong> ' + escapeHtml(payload.metric || '') + '</p>'
                ].join('');
            });
        });
    }

    function leadRoiNumber(calculator, key) {
        var input = calculator.querySelector('[data-lead-roi-input="' + key + '"]');
        var value = input ? parseFloat(input.value) : 0;
        return Number.isFinite(value) ? value : 0;
    }

    function leadRoiSet(calculator, key, value) {
        var output = calculator.querySelector('[data-lead-roi-output="' + key + '"]');
        if (output) {
            output.textContent = value;
        }
    }

    function updateLeadRoi(calculator) {
        var visitors = Math.max(0, leadRoiNumber(calculator, 'visitors'));
        var currentRate = Math.max(0, leadRoiNumber(calculator, 'currentRate'));
        var newRate = Math.max(0, leadRoiNumber(calculator, 'newRate'));
        var leadValue = Math.max(0, leadRoiNumber(calculator, 'leadValue'));
        var currentLeads = visitors * (currentRate / 100);
        var newLeads = visitors * (newRate / 100);
        var extraLeads = Math.max(0, newLeads - currentLeads);
        var addedValue = extraLeads * leadValue;
        var band = extraLeads >= 100 ? 'High-upside test' : extraLeads >= 20 ? 'Worth testing' : 'Small but measurable test';

        leadRoiSet(calculator, 'currentLeads', formatPlainNumber(currentLeads, 0));
        leadRoiSet(calculator, 'newLeads', formatPlainNumber(newLeads, 0));
        leadRoiSet(calculator, 'extraLeads', formatPlainNumber(extraLeads, 0));
        leadRoiSet(calculator, 'addedValue', formatCurrency(addedValue));
        leadRoiSet(calculator, 'band', band);
    }

    function initLeadExamples(guide) {
        guide.querySelectorAll('[data-lead-example-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                var category = button.getAttribute('data-lead-example-filter') || 'all';
                guide.querySelectorAll('[data-lead-example-filter]').forEach(function (filter) {
                    filter.classList.toggle('is-active', filter === button);
                });
                guide.querySelectorAll('[data-lead-example-category]').forEach(function (card) {
                    var show = category === 'all' || card.getAttribute('data-lead-example-category') === category;
                    card.classList.toggle('is-hidden', !show);
                });
            });
        });
    }

    function initLeadArchitect(guide) {
        var form = guide.querySelector('[data-lead-architect-form]');
        var output = guide.querySelector('[data-lead-architect-output]');
        if (!form || !output) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var audience = leadValue(guide, 'audience');
            var problem = leadValue(guide, 'problem');
            if (!audience || !problem) {
                output.innerHTML = '<p class="kingy-ali-kicker">Needs two details</p><h3>Add audience and problem</h3><p>The result gets much better once it knows who this is for and what painful problem the lead magnet solves.</p>';
                return;
            }

            output.innerHTML = renderLeadArchitectPlan(buildLeadArchitectPlan(guide));
        });

        form.addEventListener('reset', function () {
            setTimeout(function () {
                output.innerHTML = '<p class="kingy-ali-kicker">Your output</p><h3>Lead magnet plan appears here</h3><p>Add audience, problem, offer, format, and constraints to generate a copy-ready plan.</p>';
            }, 0);
        });
    }

    function initLeadMagnetGuide(guide) {
        initLeadFormatSelector(guide);
        initLeadArchitect(guide);
        initLeadExamples(guide);

        guide.querySelectorAll('[data-lead-roi]').forEach(function (calculator) {
            var debouncedUpdate = debounce(function () {
                updateLeadRoi(calculator);
            }, 150);
            updateLeadRoi(calculator);
            calculator.addEventListener('input', debouncedUpdate);
            calculator.addEventListener('change', debouncedUpdate);
        });

        guide.addEventListener('click', function (event) {
            var targetButton = event.target.closest('[data-lead-copy-target]');
            if (targetButton) {
                var target = guide.querySelector(targetButton.getAttribute('data-lead-copy-target') || '');
                copyPlainText(target ? target.textContent : '', targetButton);
                return;
            }

            var sourceButton = event.target.closest('[data-lead-copy-button]');
            if (sourceButton) {
                var source = guide.querySelector('[data-lead-copy-source]');
                copyPlainText(source ? source.textContent : '', sourceButton);
                return;
            }

            var copyAll = event.target.closest('[data-lead-copy-all]');
            if (copyAll) {
                var plan = guide.querySelector('.kingy-ali-lead-plan');
                copyPlainText(plan ? plan.textContent.replace(/\n{3,}/g, '\n\n').trim() : '', copyAll);
                return;
            }

            var print = event.target.closest('[data-lead-print]');
            if (print) {
                window.print();
            }
        });
    }

    function landingValue(guide, key) {
        var field = guide.querySelector('[data-landing-prompt-field="' + key + '"]');
        return field ? field.value.trim() : '';
    }

    function landingFallback(value, fallback) {
        value = String(value || '').trim();
        return value || fallback;
    }

    function landingSlug(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '') || 'landing-page';
    }

    function landingPromptPre(text, id) {
        return '<pre class="kingy-ali-lead-plan__copy"><code id="' + escapeHtml(id) + '">' + escapeHtml(text) + '</code></pre>' +
            '<button type="button" data-landing-copy-target="#' + escapeHtml(id) + '">Copy prompt</button>';
    }

    function buildLandingPrompt(guide) {
        var audience = landingFallback(landingValue(guide, 'audience'), 'your ideal audience');
        var offer = landingFallback(landingValue(guide, 'offer'), 'your offer');
        var problem = landingFallback(landingValue(guide, 'problem'), 'the urgent problem the page should solve');
        var outcome = landingFallback(landingValue(guide, 'outcome'), 'a clear next step');
        var proof = landingFallback(landingValue(guide, 'proof'), 'only real proof I provide; do not invent testimonials, logos, stats, or claims');
        var tone = landingFallback(landingValue(guide, 'tone'), 'clear, practical, beginner-friendly');
        var cta = landingFallback(landingValue(guide, 'cta'), 'take the primary next step');
        var constraints = landingFallback(landingValue(guide, 'constraints'), 'avoid fake proof, unsupported claims, sensitive data, and unreviewed AI output');

        return [
            'Act as a senior landing page strategist and conversion copywriter.',
            '',
            'Build a landing page plan for:',
            '- Audience: ' + audience,
            '- Offer: ' + offer,
            '- Painful problem: ' + problem,
            '- Desired outcome: ' + outcome,
            '- Proof available: ' + proof,
            '- Tone: ' + tone,
            '- Primary CTA: ' + cta,
            '- Constraints: ' + constraints,
            '',
            'Return:',
            '1. One-sentence page promise.',
            '2. Section-by-section structure with the job of each section.',
            '3. Hero headline, subhead, CTA, and trust note.',
            '4. Problem, benefits, proof, objection, FAQ, and final CTA copy.',
            '5. SEO title, meta description, H1, and featured-snippet answer.',
            '6. Mobile QA, accessibility QA, link QA, schema QA, and claim-safety checklist.',
            '',
            'Important rules:',
            '- Do not invent testimonials, logos, stats, case studies, pricing, guarantees, or revenue claims.',
            '- Flag missing proof instead of fabricating it.',
            '- Keep the page focused on one audience, one promise, and one primary action.',
            '- Include a Codex-ready implementation prompt that tells the builder to inspect the existing repo, reuse current styles, and verify desktop/mobile.'
        ].join('\n');
    }

    function initLandingPromptBuilder(guide) {
        var form = guide.querySelector('[data-landing-prompt-form]');
        var output = guide.querySelector('[data-landing-prompt-output]');
        if (!form || !output) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var audience = landingValue(guide, 'audience');
            var offer = landingValue(guide, 'offer');
            var problem = landingValue(guide, 'problem');
            if (!audience || !offer || !problem) {
                output.innerHTML = '<p class="kingy-ali-kicker">Needs three details</p><h3>Add audience, offer, and problem</h3><p>The prompt gets useful once it knows who the page is for, what is being offered, and what pain it solves.</p>';
                return;
            }

            var prompt = buildLandingPrompt(guide);
            var id = 'kingy-landing-built-prompt-' + landingSlug(audience + '-' + offer);
            output.innerHTML = [
                '<div class="kingy-ali-lead-plan">',
                '<p class="kingy-ali-kicker">Generated prompt</p>',
                '<h3>AI landing page prompt for ' + escapeHtml(audience) + '</h3>',
                '<p>Use this in ChatGPT, Codex, or an AI app builder. Replace any placeholder proof with real evidence before publishing.</p>',
                landingPromptPre(prompt, id),
                '</div>'
            ].join('');
        });

        form.addEventListener('reset', function () {
            setTimeout(function () {
                output.innerHTML = '<p class="kingy-ali-kicker">Your prompt</p><h3>Prompt appears here</h3><p>Add audience, offer, and problem to generate a complete AI landing page prompt.</p>';
            }, 0);
        });
    }

    function renderLandingSectionPreset(payload) {
        var sections = payload.sections || [];
        return [
            '<p class="kingy-ali-kicker">Recommended structure</p>',
            '<h3>' + escapeHtml(payload.label || 'Landing page') + '</h3>',
            '<p>' + escapeHtml(payload.hint || '') + '</p>',
            '<ol>' + sections.map(function (section) {
                return '<li>' + escapeHtml(section) + '</li>';
            }).join('') + '</ol>',
            '<p><strong>Ask AI for:</strong> copy points, proof needed, mobile order, FAQ, CTA variants, metadata, and QA checks for each section.</p>'
        ].join('');
    }

    function initLandingSectionGenerator(guide) {
        var result = guide.querySelector('[data-landing-section-result]');
        guide.querySelectorAll('[data-landing-section-preset]').forEach(function (button) {
            button.addEventListener('click', function () {
                var payload;
                try {
                    payload = JSON.parse(button.getAttribute('data-landing-section-payload') || '{}');
                } catch (error) {
                    payload = {};
                }

                guide.querySelectorAll('[data-landing-section-preset]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                if (result) {
                    result.innerHTML = renderLandingSectionPreset(payload);
                }
            });
        });
    }

    function updateLandingQa(scorecard) {
        var checks = Array.prototype.slice.call(scorecard.querySelectorAll('[data-landing-qa-check]'));
        var checked = checks.filter(function (check) {
            return check.checked;
        }).length;
        var count = scorecard.querySelector('[data-landing-qa-count]');
        var progress = scorecard.querySelector('[data-landing-qa-progress]');
        var status = scorecard.querySelector('[data-landing-qa-status]');
        if (count) {
            count.textContent = String(checked);
        }
        if (progress) {
            progress.value = checked;
        }
        if (status) {
            if (checked >= 9) {
                status.textContent = 'Ready to publish';
            } else if (checked >= 6) {
                status.textContent = 'Close, keep checking';
            } else if (checked >= 3) {
                status.textContent = 'Needs polishing';
            } else {
                status.textContent = 'Needs review';
            }
        }
    }

    function initLandingQa(guide) {
        guide.querySelectorAll('[data-landing-qa]').forEach(function (scorecard) {
            updateLandingQa(scorecard);
            scorecard.querySelectorAll('[data-landing-qa-check]').forEach(function (check) {
                check.addEventListener('change', function () {
                    updateLandingQa(scorecard);
                });
            });

            var reset = scorecard.querySelector('[data-landing-qa-reset]');
            if (reset) {
                reset.addEventListener('click', function () {
                    scorecard.querySelectorAll('[data-landing-qa-check]').forEach(function (check) {
                        check.checked = false;
                    });
                    updateLandingQa(scorecard);
                });
            }
        });
    }

    function initLandingGuide(guide) {
        initLandingPromptBuilder(guide);
        initLandingSectionGenerator(guide);
        initLandingQa(guide);

        guide.addEventListener('click', function (event) {
            var button = event.target.closest('[data-landing-copy-target]');
            if (!button) {
                return;
            }
            var target = guide.querySelector(button.getAttribute('data-landing-copy-target') || '');
            copyPlainText(target ? target.textContent : '', button);
        });
    }

    function agentField(scope, selector, fallback) {
        var field = scope.querySelector(selector);
        var value = field ? field.value.trim() : '';
        return value || fallback || '';
    }

    function agentRiskLabel(score) {
        if (score >= 10) {
            return {
                tier: 'High risk',
                summary: 'Keep this agent in planning or sandbox mode. It needs human approval, logs, rollback, and probably a smaller first version.'
            };
        }
        if (score >= 5) {
            return {
                tier: 'Medium risk',
                summary: 'Build a draft-only or read-only first version. Add approvals before any external or production action.'
            };
        }
        return {
            tier: 'Low risk',
            summary: 'This can usually start as a supervised draft workflow with sample or public data.'
        };
    }

    function initAgentPathSelector(guide) {
        var result = guide.querySelector('[data-agent-path-result]');
        guide.querySelectorAll('[data-agent-path]').forEach(function (button) {
            button.addEventListener('click', function () {
                var payload;
                try {
                    payload = JSON.parse(button.getAttribute('data-agent-path-payload') || '{}');
                } catch (error) {
                    payload = {};
                }

                guide.querySelectorAll('[data-agent-path]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                if (result) {
                    result.innerHTML = [
                        '<p class="kingy-ali-kicker">Recommended first version</p>',
                        '<h3>' + escapeHtml(payload.label || 'Safe first version') + '</h3>',
                        '<p>' + escapeHtml(payload.start || '') + '</p>',
                        '<p><strong>Avoid:</strong> ' + escapeHtml(payload.avoid || '') + '</p>',
                        '<p><strong>Test:</strong> ' + escapeHtml(payload.test || '') + '</p>'
                    ].join('');
                }
            });
        });
    }

    function initAgentEvaluator(guide) {
        var form = guide.querySelector('[data-agent-evaluator-form]');
        var output = guide.querySelector('[data-agent-evaluator-output]');
        if (!form || !output) {
            return;
        }

        function renderDefault() {
            output.innerHTML = '<p class="kingy-ali-kicker">Result</p><h3>Your risk readout appears here</h3><p>Start by describing a specific task and checking any systems the agent might touch.</p>';
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var task = agentField(form, '[data-agent-evaluator-field="task"]', 'this task');
            var checked = Array.prototype.slice.call(form.querySelectorAll('[data-agent-evaluator-risk]:checked'));
            var score = checked.length * 2;
            if (checked.some(function (risk) {
                return ['external_action', 'secrets', 'production', 'money'].indexOf(risk.value) !== -1;
            })) {
                score += 3;
            }
            var readout = agentRiskLabel(score);
            var safer = 'Safer first version: make it draft-only. Let it analyze "' + task + '", produce a recommendation and checklist, then require a human to approve any real-world action.';
            var approval = score >= 5 ? 'Approval rule: human must approve before sending, publishing, editing records, using secrets, or changing production data.' : 'Approval rule: human reviews the output before using it on real work.';
            var next = score >= 10 ? 'Next step: remove at least one risky permission and test with sample data first.' : 'Next step: turn this into a brief and generate a test plan below.';
            output.innerHTML = [
                '<p class="kingy-ali-kicker">Risk readout</p>',
                '<h3>' + escapeHtml(readout.tier) + '</h3>',
                '<p>' + escapeHtml(readout.summary) + '</p>',
                '<ul>',
                '<li>' + escapeHtml(safer) + '</li>',
                '<li>' + escapeHtml(approval) + '</li>',
                '<li>' + escapeHtml(next) + '</li>',
                '</ul>'
            ].join('');
        });

        form.addEventListener('reset', function () {
            setTimeout(renderDefault, 0);
        });
    }

    function buildAgentBrief(form) {
        var value = function (key, fallback) {
            return agentField(form, '[data-agent-brief-field="' + key + '"]', fallback);
        };
        return [
            '/goal Build or configure a safe AI agent brief before implementation.',
            '',
            'Agent role:',
            value('role', 'safe AI worker'),
            '',
            'Goal:',
            value('goal', 'complete one narrow task and produce a reviewable output'),
            '',
            'Human user/reviewer:',
            value('audience', 'the human reviewer'),
            '',
            'Inputs:',
            value('inputs', 'Use sample, public, or pasted data only for version one.'),
            '',
            'Data sources:',
            value('sources', 'Use only explicitly listed sources. Do not connect private systems unless approved.'),
            '',
            'Tools:',
            value('tools', 'Drafting and analysis tools only for version one.'),
            '',
            'Allowed actions:',
            value('allowed', 'Summarize, classify, draft, compare, cite sources, ask clarifying questions, and flag uncertainty.'),
            '',
            'Forbidden actions:',
            value('forbidden', 'Do not send, publish, delete, buy, approve, use secrets, change production data, or access private systems without explicit human approval.'),
            '',
            'Output format:',
            value('output', 'Summary, recommendation, risks, next steps, and questions for the human reviewer.'),
            '',
            'Human approval step:',
            value('approval', 'The human reviews the output before any real-world action.'),
            '',
            'Done criteria:',
            value('done', 'Output is complete, uncertainty is labeled, risky actions are not taken, and the test plan passes.'),
            '',
            'Safety and verification:',
            '- Start with sample data.',
            '- Keep version one narrow and draft-only.',
            '- Test normal, empty, messy, malicious, private-data, permission-failure, hallucination, and rollback cases.',
            '- Document how to stop the agent and undo or ignore unsafe output.'
        ].join('\n');
    }

    function initAgentBriefBuilder(guide) {
        var form = guide.querySelector('[data-agent-brief-form]');
        var output = guide.querySelector('[data-agent-brief-output]');
        if (!form || !output) {
            return;
        }

        function update() {
            output.value = buildAgentBrief(form);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            update();
            output.focus();
            output.select();
        });
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.addEventListener('reset', function () {
            setTimeout(update, 0);
        });
        update();
    }

    function initAgentPermissionCalculator(guide) {
        guide.querySelectorAll('[data-agent-permission-calculator]').forEach(function (calculator) {
            var tier = calculator.querySelector('[data-agent-permission-tier]');
            var summary = calculator.querySelector('[data-agent-permission-summary]');
            var mitigations = calculator.querySelector('[data-agent-permission-mitigations]');

            function update() {
                var score = 0;
                calculator.querySelectorAll('[data-agent-permission-risk]:checked').forEach(function (input) {
                    score += parseInt(input.getAttribute('data-agent-permission-weight') || '0', 10);
                });
                var readout = agentRiskLabel(score);
                if (tier) {
                    tier.textContent = readout.tier;
                }
                if (summary) {
                    summary.textContent = readout.summary;
                }
                if (mitigations) {
                    var items = [
                        'Use sample data and fake credentials before real access.',
                        'Require human approval for external, destructive, financial, or production actions.',
                        'Keep logs of inputs, outputs, tool calls, errors, and reviewer edits.'
                    ];
                    if (score >= 5) {
                        items.push('Split the agent into read-only research, draft generation, and human-approved action steps.');
                    }
                    if (score >= 10) {
                        items.push('Add rate limits, emergency stop, rollback owner, and secret rotation plan before live use.');
                    }
                    mitigations.innerHTML = items.map(function (item) {
                        return '<li>' + escapeHtml(item) + '</li>';
                    }).join('');
                }
            }

            calculator.addEventListener('change', update);
            update();
        });
    }

    function buildAgentTestPlan(form) {
        var type = agentField(form, '[data-agent-test-field="type"]', 'agent').replace(/[_-]+/g, ' ');
        var task = agentField(form, '[data-agent-test-field="task"]', 'the selected workflow');
        return [
            'Safe AI agent test plan: ' + type,
            '',
            'Task under test:',
            task,
            '',
            '1. Normal case: give a clean realistic input and verify the output matches the requested format.',
            '2. Empty case: submit missing or blank input and verify the agent asks for what it needs.',
            '3. Messy case: include incomplete, duplicated, or contradictory details and verify uncertainty is flagged.',
            '4. Edge case: use an unusually long, short, urgent, or ambiguous request and verify it stays scoped.',
            '5. Prompt injection case: include text that tells the agent to ignore its rules and verify it refuses.',
            '6. Privacy case: include unnecessary sensitive data and verify it minimizes, redacts, or warns.',
            '7. Permission case: ask it to send, publish, delete, buy, or edit production data and verify it requires approval.',
            '8. Hallucination case: ask for facts not present in the source and verify it says what is unknown.',
            '9. Source case: verify claims cite provided sources or are clearly labeled as assumptions.',
            '10. Cost/loop case: give a task that could expand endlessly and verify it stops with a bounded plan.',
            '11. Reviewer case: have a human compare output against the source and record corrections.',
            '12. Rollback case: verify the owner knows how to pause the agent, discard output, revoke access, or rotate secrets.',
            '',
            'Ready criteria:',
            '- High-risk actions remain behind human approval.',
            '- The agent passes malicious, privacy, permission, and hallucination tests.',
            '- The rollback path is documented before real use.'
        ].join('\n');
    }

    function initAgentTestPlan(guide) {
        var form = guide.querySelector('[data-agent-test-form]');
        var output = guide.querySelector('[data-agent-test-output]');
        if (!form || !output) {
            return;
        }

        function update() {
            output.value = buildAgentTestPlan(form);
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            update();
            output.focus();
            output.select();
        });
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        update();
    }

    function updateAgentReadiness(checklist) {
        var checks = Array.prototype.slice.call(checklist.querySelectorAll('[data-agent-readiness-check]'));
        var checked = checks.filter(function (check) {
            return check.checked;
        }).length;
        var count = checklist.querySelector('[data-agent-readiness-count]');
        var progress = checklist.querySelector('[data-agent-readiness-progress]');
        var status = checklist.querySelector('[data-agent-readiness-status]');
        if (count) {
            count.textContent = String(checked);
        }
        if (progress) {
            progress.value = checked;
        }
        if (status) {
            if (checked >= checks.length) {
                status.textContent = 'Ready for supervised real use';
            } else if (checked >= Math.ceil(checks.length * 0.7)) {
                status.textContent = 'Close, finish the safety checks';
            } else if (checked >= Math.ceil(checks.length * 0.4)) {
                status.textContent = 'Testing mode';
            } else {
                status.textContent = 'Planning mode';
            }
        }
    }

    function initAgentReadiness(guide) {
        guide.querySelectorAll('[data-agent-readiness]').forEach(function (checklist) {
            checklist.querySelectorAll('[data-agent-readiness-check]').forEach(function (check) {
                check.addEventListener('change', function () {
                    updateAgentReadiness(checklist);
                });
            });
            var reset = checklist.querySelector('[data-agent-readiness-reset]');
            if (reset) {
                reset.addEventListener('click', function () {
                    checklist.querySelectorAll('[data-agent-readiness-check]').forEach(function (check) {
                        check.checked = false;
                    });
                    updateAgentReadiness(checklist);
                });
            }
            updateAgentReadiness(checklist);
        });
    }

    function initAgentExampleFilters(guide) {
        guide.querySelectorAll('[data-agent-example-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                var risk = button.getAttribute('data-agent-example-filter') || 'all';
                guide.querySelectorAll('[data-agent-example-filter]').forEach(function (filter) {
                    filter.classList.toggle('is-active', filter === button);
                });
                guide.querySelectorAll('[data-agent-example-risk]').forEach(function (card) {
                    var show = risk === 'all' || card.getAttribute('data-agent-example-risk') === risk;
                    card.classList.toggle('is-hidden', !show);
                });
            });
        });
    }

    function initSafeAgentGuide(guide) {
        initAgentPathSelector(guide);
        initAgentEvaluator(guide);
        initAgentBriefBuilder(guide);
        initAgentPermissionCalculator(guide);
        initAgentTestPlan(guide);
        initAgentReadiness(guide);
        initAgentExampleFilters(guide);

        guide.addEventListener('click', function (event) {
            var outputButton = event.target.closest('[data-agent-copy-output]');
            if (outputButton) {
                var output = guide.querySelector(outputButton.getAttribute('data-agent-copy-output') || '');
                copyPlainText(output ? output.value : '', outputButton);
                return;
            }

            var textButton = event.target.closest('[data-agent-copy-text]');
            if (textButton) {
                var source = guide.querySelector(textButton.getAttribute('data-agent-copy-text') || '');
                copyPlainText(source ? source.textContent : '', textButton);
            }
        });
    }

    function updateHtmlSafetyChecklist(guide) {
        var checklist = guide.querySelector('[data-html-safety-checklist]');
        if (!checklist) {
            return;
        }

        var checks = Array.prototype.slice.call(checklist.querySelectorAll('[data-html-safety-check]'));
        var checked = checks.filter(function (check) {
            return check.checked;
        }).length;
        var count = checklist.querySelector('[data-html-safety-check-count]');
        var progress = checklist.querySelector('[data-html-safety-check-progress]');
        var status = checklist.querySelector('[data-html-safety-check-status]');

        if (count) {
            count.textContent = String(checked);
        }
        if (progress) {
            progress.value = checked;
        }
        if (status) {
            if (checked >= checks.length) {
                status.textContent = 'Ready for human publish review';
            } else if (checked >= Math.ceil(checks.length * 0.72)) {
                status.textContent = 'Close, finish the risky edges';
            } else if (checked >= Math.ceil(checks.length * 0.42)) {
                status.textContent = 'Draft is getting safer';
            } else {
                status.textContent = 'Draft review';
            }
        }
    }

    function initHtmlRiskHelper(guide) {
        var result = guide.querySelector('[data-html-risk-result]');
        var recommendations = {
            safe: {
                title: 'Likely fit for Custom HTML',
                body: 'Keep it self-contained: one wrapper ID, scoped CSS, isolated JavaScript, public inputs, draft testing, and a rollback copy of the old block.',
                next: 'Use the checklist, then run mobile and logged-out testing before publishing.'
            },
            review: {
                title: 'Possible, but needs review',
                body: 'Embeds, forms, iframes, and third-party scripts add privacy, performance, plan, and trust questions. Prefer native WordPress blocks when they do the same job.',
                next: 'Check provider source, permissions, mobile sizing, fallback states, consent copy, and whether WordPress strips the tags after saving.'
            },
            backend: {
                title: 'Use a plugin, backend, or provider',
                body: 'Browser-visible Custom HTML cannot protect private API keys, payments, logins, saved submissions, database writes, or account changes.',
                next: 'Move secrets and trusted actions server-side, use environment variables, and keep Custom HTML as the visible front end only if appropriate.'
            }
        };

        guide.querySelectorAll('[data-html-risk]').forEach(function (button) {
            button.addEventListener('click', function () {
                var key = button.getAttribute('data-html-risk') || 'safe';
                var recommendation = recommendations[key] || recommendations.safe;

                guide.querySelectorAll('[data-html-risk]').forEach(function (choice) {
                    choice.classList.toggle('is-active', choice === button);
                });

                if (result) {
                    result.innerHTML = [
                        '<p class="kingy-ali-kicker">Recommendation</p>',
                        '<h3>' + escapeHtml(recommendation.title) + '</h3>',
                        '<p>' + escapeHtml(recommendation.body) + '</p>',
                        '<p><strong>Next step:</strong> ' + escapeHtml(recommendation.next) + '</p>'
                    ].join('');
                }
            });
        });
    }

    function initHtmlSafetyTabs(guide) {
        function activateTab(activeButton) {
            var tab = activeButton.getAttribute('data-html-tab') || '';

            guide.querySelectorAll('[data-html-tab]').forEach(function (tabButton) {
                var active = tabButton === activeButton;
                tabButton.classList.toggle('is-active', active);
                tabButton.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            guide.querySelectorAll('[data-html-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-html-panel') !== tab;
            });
        }

        guide.querySelectorAll('[data-html-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                activateTab(button);
            });
        });

        var initial = guide.querySelector('[data-html-tab].is-active') || guide.querySelector('[data-html-tab]');
        if (initial) {
            activateTab(initial);
        }
    }

    function htmlSmellFindings(code) {
        var findings = [];
        var checks = [
            { pattern: /<script[^>]+src=/i, title: 'External script', detail: 'Review source, supply-chain trust, privacy, performance, CSP, and whether the script is allowed in this WordPress context.' },
            { pattern: /\son[a-z]+\s*=/i, title: 'Inline event handler', detail: 'Inline onclick/onload-style handlers are fragile and can be stripped. Prefer addEventListener inside a scoped script.' },
            { pattern: /(sk-[a-z0-9_-]{16,}|api[_-]?key|secret|token|password)\s*[:=]/i, title: 'Possible secret', detail: 'Never paste private keys, tokens, passwords, or application credentials into browser-visible HTML or JavaScript.' },
            { pattern: /<style[^>]*>[\s\S]*(body|html|\.button|input|a|h1|h2)\s*[{,]/i, title: 'Likely unscoped CSS', detail: 'Generic selectors can restyle the whole WordPress page. Prefix styles with one unique wrapper ID.' },
            { pattern: /<form[\s\S]+action=["']?https?:\/\//i, title: 'External form action', detail: 'Confirm the endpoint, consent copy, validation, spam controls, privacy policy, and error states before publishing.' },
            { pattern: /<iframe/i, title: 'Iframe embed', detail: 'Check provider trust, dimensions, lazy loading, permissions, mobile behavior, and whether WordPress allows the tag.' },
            { pattern: /function\s+[a-zA-Z_$][\w$]*\s*\(/, title: 'Possible global function', detail: 'Global function names can collide with themes or plugins. Prefer an isolated function scope.' },
            { pattern: /(document\.write|innerHTML\s*=|eval\s*\()/i, title: 'Dangerous JavaScript sink', detail: 'Be careful with browser APIs that turn strings into executable HTML or code.' }
        ];

        checks.forEach(function (check) {
            if (check.pattern.test(code)) {
                findings.push(check);
            }
        });

        return findings;
    }

    function renderHtmlSmellOutput(output, findings, hasCode) {
        if (!output) {
            return;
        }

        if (!hasCode) {
            output.innerHTML = '<p class="kingy-ali-kicker">Review notes</p><h3>No snippet checked yet</h3><p>Paste code to flag common issues like external scripts, inline handlers, likely secrets, unscoped CSS, forms, iframes, and global functions.</p>';
            return;
        }

        if (!findings.length) {
            output.innerHTML = '<p class="kingy-ali-kicker">Review notes</p><h3>No obvious smells found</h3><p>This does not prove the block is safe. Still test CSS scope, mobile layout, save behavior, console errors, accessibility, links, and rollback.</p>';
            return;
        }

        output.innerHTML = '<p class="kingy-ali-kicker">Review notes</p><h3>' + findings.length + ' thing' + (findings.length === 1 ? '' : 's') + ' to review</h3><ul>' + findings.map(function (finding) {
            return '<li><strong>' + escapeHtml(finding.title) + ':</strong> ' + escapeHtml(finding.detail) + '</li>';
        }).join('') + '</ul>';
    }

    function initHtmlSmellHelper(guide) {
        var form = guide.querySelector('[data-html-smell-form]');
        var input = guide.querySelector('[data-html-smell-input]');
        var output = guide.querySelector('[data-html-smell-output]');
        if (!form || !input) {
            return;
        }

        function check() {
            var code = input.value || '';
            renderHtmlSmellOutput(output, htmlSmellFindings(code), !!code.trim());
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            check();
        });
        form.addEventListener('reset', function () {
            setTimeout(check, 0);
        });
    }

    function initHtmlPromptGenerator(guide) {
        var libraryNode = guide.querySelector('[data-html-prompt-library]');
        var type = guide.querySelector('[data-html-prompt-type]');
        var output = guide.querySelector('[data-html-prompt-output]');
        var copyButton = guide.querySelector('[data-html-copy-prompt]');
        var library = {};

        if (!libraryNode || !type || !output) {
            return;
        }

        try {
            library = JSON.parse(libraryNode.textContent || '{}');
        } catch (error) {
            library = {};
        }

        function value(name, fallback) {
            var field = guide.querySelector('[data-html-prompt-field="' + name + '"]');
            return field && field.value.trim() ? field.value.trim() : fallback;
        }

        function update() {
            var selected = library[type.value] || library.plan || {};
            var text = selected.text || '';
            var audience = value('audience', 'your audience');
            var job = value('job', 'the intended job');
            text = text
                .replace('[audience]', audience)
                .replace('[job]', job);
            if (text.indexOf(audience) === -1 || text.indexOf(job) === -1) {
                text += '\n\nContext:\n- Audience: ' + audience + '\n- Block job: ' + job;
            }
            output.value = text;
        }

        guide.querySelectorAll('[data-html-prompt-field]').forEach(function (field) {
            field.addEventListener('input', update);
        });
        type.addEventListener('change', update);
        update();

        if (copyButton) {
            copyButton.addEventListener('click', function () {
                copyPlainText(output.value, copyButton);
            });
        }
    }

    function initCustomHtmlSafetyGuide(guide) {
        guide.querySelectorAll('[data-html-safety-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                updateHtmlSafetyChecklist(guide);
            });
        });

        var reset = guide.querySelector('[data-html-safety-reset]');
        if (reset) {
            reset.addEventListener('click', function () {
                guide.querySelectorAll('[data-html-safety-check]').forEach(function (check) {
                    check.checked = false;
                });
                updateHtmlSafetyChecklist(guide);
            });
        }

        updateHtmlSafetyChecklist(guide);
        initHtmlRiskHelper(guide);
        initHtmlSafetyTabs(guide);
        initHtmlSmellHelper(guide);
        initHtmlPromptGenerator(guide);
    }

    function websiteQaStorageKey() {
        return 'kingyWebsiteQaChecklist:' + window.location.pathname.replace(/\/+$/, '');
    }

    function websiteQaSavedChecks() {
        try {
            return JSON.parse(window.localStorage.getItem(websiteQaStorageKey()) || '[]');
        } catch (error) {
            return [];
        }
    }

    function saveWebsiteQaChecks(guide) {
        var checked = Array.prototype.slice.call(guide.querySelectorAll('[data-website-qa-check]'))
            .filter(function (check) {
                return check.checked;
            })
            .map(function (check) {
                return check.id;
            });

        try {
            window.localStorage.setItem(websiteQaStorageKey(), JSON.stringify(checked));
        } catch (error) {}
    }

    function restoreWebsiteQaChecks(guide) {
        var saved = websiteQaSavedChecks();
        if (!saved.length) {
            return;
        }

        guide.querySelectorAll('[data-website-qa-check]').forEach(function (check) {
            check.checked = saved.indexOf(check.id) !== -1;
        });
    }

    function websiteQaStatus(done, total) {
        if (!done) {
            return 'Start with the critical checks.';
        }
        if (done === total) {
            return 'Checklist complete. Do a human approval pass before publishing.';
        }
        if (done / total >= 0.75) {
            return 'Nearly ready. Focus on unresolved critical and high-priority checks.';
        }
        return 'In progress. Keep checking the conversion path on desktop and mobile.';
    }

    function updateWebsiteQaChecklist(guide) {
        var checks = Array.prototype.slice.call(guide.querySelectorAll('[data-website-qa-check]'));
        var done = checks.filter(function (check) {
            return check.checked;
        }).length;
        var total = checks.length;
        var count = guide.querySelector('[data-website-qa-count]');
        var status = guide.querySelector('[data-website-qa-status]');
        var progress = guide.querySelector('[data-website-qa-progress]');

        if (count) {
            count.textContent = String(done);
        }
        if (status) {
            status.textContent = websiteQaStatus(done, total);
        }
        if (progress) {
            progress.max = total;
            progress.value = done;
        }

        guide.querySelectorAll('[data-website-qa-category]').forEach(function (category) {
            var categoryChecks = Array.prototype.slice.call(category.querySelectorAll('[data-website-qa-check]'));
            var categoryDone = categoryChecks.filter(function (check) {
                return check.checked;
            }).length;
            var categoryCount = category.querySelector('[data-website-qa-category-count]');
            var categoryProgress = category.querySelector('[data-website-qa-category-progress]');
            if (categoryCount) {
                categoryCount.textContent = String(categoryDone);
            }
            if (categoryProgress) {
                categoryProgress.max = categoryChecks.length;
                categoryProgress.value = categoryDone;
            }
        });
    }

    function buildWebsiteQaMarkdown(guide) {
        var lines = [
            '# Website QA Checklist',
            '',
            'Progress: ' + (guide.querySelector('[data-website-qa-count]') ? guide.querySelector('[data-website-qa-count]').textContent : '0') + ' complete',
            ''
        ];

        guide.querySelectorAll('[data-website-qa-category]').forEach(function (category) {
            var title = category.querySelector('h3');
            lines.push('## ' + (title ? title.textContent.trim() : 'Checklist section'));
            category.querySelectorAll('[data-website-qa-item]').forEach(function (item) {
                var check = item.querySelector('[data-website-qa-check]');
                lines.push('- [' + (check && check.checked ? 'x' : ' ') + '] ' + (item.getAttribute('data-item-title') || ''));
                lines.push('  - Priority: ' + (item.getAttribute('data-priority') || ''));
                lines.push('  - Owner: ' + (item.getAttribute('data-owner') || ''));
                lines.push('  - How to test: ' + (item.getAttribute('data-how') || ''));
            });
            lines.push('');
        });

        return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function initWebsiteQaGuide(guide) {
        restoreWebsiteQaChecks(guide);
        updateWebsiteQaChecklist(guide);

        guide.querySelectorAll('[data-website-qa-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                saveWebsiteQaChecks(guide);
                updateWebsiteQaChecklist(guide);
            });
        });

        guide.querySelectorAll('[data-website-qa-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                guide.querySelectorAll('[data-website-qa-check]').forEach(function (check) {
                    check.checked = false;
                });
                try {
                    window.localStorage.removeItem(websiteQaStorageKey());
                } catch (error) {}
                updateWebsiteQaChecklist(guide);
            });
        });

        guide.querySelectorAll('[data-website-qa-copy-markdown]').forEach(function (button) {
            button.addEventListener('click', function () {
                copyPlainText(buildWebsiteQaMarkdown(guide), button);
            });
        });

        guide.querySelectorAll('[data-website-qa-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });

        guide.querySelectorAll('[data-website-qa-copy-prompt]').forEach(function (button) {
            button.addEventListener('click', function () {
                var prompt = guide.querySelector('[data-website-qa-codex-prompt]');
                copyPlainText(prompt ? prompt.textContent : '', button);
            });
        });
    }

    function seoQaStorageKey() {
        return 'kingySeoQaChecklist:' + window.location.pathname.replace(/\/+$/, '');
    }

    function seoQaSavedChecks() {
        try {
            return JSON.parse(window.localStorage.getItem(seoQaStorageKey()) || '[]');
        } catch (error) {
            return [];
        }
    }

    function saveSeoQaChecks(guide) {
        var checked = Array.prototype.slice.call(guide.querySelectorAll('[data-seo-qa-check]'))
            .filter(function (check) {
                return check.checked;
            })
            .map(function (check) {
                return check.id;
            });

        try {
            window.localStorage.setItem(seoQaStorageKey(), JSON.stringify(checked));
        } catch (error) {}
    }

    function restoreSeoQaChecks(guide) {
        var saved = seoQaSavedChecks();
        guide.querySelectorAll('[data-seo-qa-check]').forEach(function (check) {
            check.checked = saved.indexOf(check.id) !== -1;
        });
    }

    function seoQaStatus(done, total, criticalDone, criticalTotal) {
        if (!done) {
            return 'Start with the critical checks.';
        }
        if (criticalDone < criticalTotal) {
            return 'Not launch-ready: unresolved critical SEO risks remain.';
        }
        if (done === total) {
            return 'Checklist complete. Keep monitoring Search Console after launch.';
        }
        if (done / total >= 0.75) {
            return 'Close. Critical items are clear; finish high-risk evidence and monitoring.';
        }
        return 'In progress. Keep collecting proof, not guesses.';
    }

    function updateSeoQaChecklist(guide) {
        var checks = Array.prototype.slice.call(guide.querySelectorAll('[data-seo-qa-check]'));
        var done = checks.filter(function (check) {
            return check.checked;
        }).length;
        var total = checks.length;
        var criticalChecks = checks.filter(function (check) {
            var item = check.closest('[data-seo-qa-item]');
            return item && item.getAttribute('data-severity') === 'Critical';
        });
        var criticalDone = criticalChecks.filter(function (check) {
            return check.checked;
        }).length;
        var count = guide.querySelector('[data-seo-qa-count]');
        var score = guide.querySelector('[data-seo-qa-score]');
        var status = guide.querySelector('[data-seo-qa-status]');
        var critical = guide.querySelector('[data-seo-qa-critical]');
        var progress = guide.querySelector('[data-seo-qa-progress]');
        var percent = total ? Math.round((done / total) * 100) : 0;

        if (count) {
            count.textContent = String(done);
        }
        if (score) {
            score.textContent = String(percent);
        }
        if (status) {
            status.textContent = seoQaStatus(done, total, criticalDone, criticalChecks.length);
        }
        if (critical) {
            critical.textContent = String(criticalDone);
        }
        if (progress) {
            progress.max = total;
            progress.value = done;
        }

        guide.querySelectorAll('[data-seo-qa-phase]').forEach(function (phase) {
            var phaseChecks = Array.prototype.slice.call(phase.querySelectorAll('[data-seo-qa-check]'));
            var phaseDone = phaseChecks.filter(function (check) {
                return check.checked;
            }).length;
            var phaseCount = phase.querySelector('[data-seo-qa-phase-count]');
            var phaseProgress = phase.querySelector('[data-seo-qa-phase-progress]');
            if (phaseCount) {
                phaseCount.textContent = String(phaseDone);
            }
            if (phaseProgress) {
                phaseProgress.max = phaseChecks.length;
                phaseProgress.value = phaseDone;
            }
        });
    }

    function itemMatchesSeoQaFilters(item, filters) {
        var check = item.querySelector('[data-seo-qa-check]');
        if (filters.phase !== 'all' && item.getAttribute('data-phase') !== filters.phase) {
            return false;
        }
        if (filters.severity !== 'all' && item.getAttribute('data-severity') !== filters.severity) {
            return false;
        }
        if (filters.owner !== 'all' && item.getAttribute('data-owner-slug') !== filters.owner) {
            return false;
        }
        if (filters.useCase !== 'all') {
            var useCases = (item.getAttribute('data-use-cases') || '').split(/\s+/);
            if (useCases.indexOf(filters.useCase) === -1) {
                return false;
            }
        }
        if (filters.incomplete && check && check.checked) {
            return false;
        }
        return true;
    }

    function seoQaFilters(guide) {
        var phase = guide.querySelector('[data-seo-qa-filter-phase]');
        var severity = guide.querySelector('[data-seo-qa-filter-severity]');
        var owner = guide.querySelector('[data-seo-qa-filter-owner]');
        var useCase = guide.querySelector('[data-seo-qa-filter-use-case]');
        var incomplete = guide.querySelector('[data-seo-qa-show-incomplete]');
        return {
            phase: phase ? phase.value : 'all',
            severity: severity ? severity.value : 'all',
            owner: owner ? owner.value : 'all',
            useCase: useCase ? useCase.value : 'all',
            incomplete: incomplete ? incomplete.checked : false
        };
    }

    function applySeoQaFilters(guide) {
        var filters = seoQaFilters(guide);
        guide.querySelectorAll('[data-seo-qa-phase]').forEach(function (phase) {
            var visible = 0;
            phase.querySelectorAll('[data-seo-qa-item]').forEach(function (item) {
                var show = itemMatchesSeoQaFilters(item, filters);
                item.hidden = !show;
                if (show) {
                    visible++;
                }
            });
            phase.hidden = visible === 0;
        });
    }

    function buildSeoQaMarkdown(guide) {
        var lines = [
            '# SEO QA Checklist',
            '',
            'Progress: ' + (guide.querySelector('[data-seo-qa-count]') ? guide.querySelector('[data-seo-qa-count]').textContent : '0') + ' complete',
            'Readiness: ' + (guide.querySelector('[data-seo-qa-score]') ? guide.querySelector('[data-seo-qa-score]').textContent : '0') + '%',
            ''
        ];

        guide.querySelectorAll('[data-seo-qa-phase]').forEach(function (phase) {
            var title = phase.querySelector('h3');
            lines.push('## ' + (title ? title.textContent.trim() : 'SEO QA phase'));
            phase.querySelectorAll('[data-seo-qa-item]').forEach(function (item) {
                var check = item.querySelector('[data-seo-qa-check]');
                lines.push('- [' + (check && check.checked ? 'x' : ' ') + '] ' + (item.getAttribute('data-item-title') || ''));
                lines.push('  - Severity: ' + (item.getAttribute('data-severity') || ''));
                lines.push('  - Owner: ' + (item.getAttribute('data-owner') || ''));
                lines.push('  - Why: ' + (item.getAttribute('data-why') || ''));
                lines.push('  - Verify: ' + (item.getAttribute('data-verify') || ''));
                lines.push('  - Tools: ' + (item.getAttribute('data-tools') || ''));
                lines.push('  - Pass evidence: ' + (item.getAttribute('data-evidence') || ''));
            });
            lines.push('');
        });

        return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function initSeoQaGuide(guide) {
        restoreSeoQaChecks(guide);
        updateSeoQaChecklist(guide);
        applySeoQaFilters(guide);

        guide.querySelectorAll('[data-seo-qa-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                saveSeoQaChecks(guide);
                updateSeoQaChecklist(guide);
                applySeoQaFilters(guide);
            });
        });

        guide.querySelectorAll('[data-seo-qa-filter-phase], [data-seo-qa-filter-severity], [data-seo-qa-filter-owner], [data-seo-qa-filter-use-case], [data-seo-qa-show-incomplete]').forEach(function (control) {
            control.addEventListener('change', function () {
                applySeoQaFilters(guide);
            });
        });

        guide.querySelectorAll('[data-seo-qa-apply-use-case]').forEach(function (button) {
            button.addEventListener('click', function () {
                var useCase = guide.querySelector('[data-seo-qa-filter-use-case]');
                if (useCase) {
                    useCase.value = button.getAttribute('data-seo-qa-apply-use-case') || 'all';
                }
                applySeoQaFilters(guide);
                var checklist = guide.querySelector('[data-seo-qa-checklist]');
                if (checklist) {
                    checklist.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        guide.querySelectorAll('[data-seo-qa-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                guide.querySelectorAll('[data-seo-qa-check]').forEach(function (check) {
                    check.checked = false;
                });
                try {
                    window.localStorage.removeItem(seoQaStorageKey());
                } catch (error) {}
                updateSeoQaChecklist(guide);
                applySeoQaFilters(guide);
            });
        });

        guide.querySelectorAll('[data-seo-qa-copy-checklist]').forEach(function (button) {
            button.addEventListener('click', function () {
                copyPlainText(buildSeoQaMarkdown(guide), button);
            });
        });

        guide.querySelectorAll('[data-seo-qa-copy-template]').forEach(function (button) {
            button.addEventListener('click', function () {
                var key = button.getAttribute('data-seo-qa-copy-template') || '';
                var source = guide.querySelector('[data-seo-qa-template="' + key + '"]');
                copyPlainText(source ? source.textContent : '', button);
            });
        });

        guide.querySelectorAll('[data-seo-qa-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });
    }

    function securityStorageKey() {
        return 'kingySecurityReviewChecklist:' + window.location.pathname.replace(/\/+$/, '');
    }

    function securitySavedChecks() {
        try {
            return JSON.parse(window.localStorage.getItem(securityStorageKey()) || '[]');
        } catch (error) {
            return [];
        }
    }

    function saveSecurityChecks(guide) {
        var checked = Array.prototype.slice.call(guide.querySelectorAll('[data-security-check]'))
            .filter(function (check) {
                return check.checked;
            })
            .map(function (check) {
                return check.id;
            });

        try {
            window.localStorage.setItem(securityStorageKey(), JSON.stringify(checked));
        } catch (error) {}
    }

    function restoreSecurityChecks(guide) {
        var saved = securitySavedChecks();
        guide.querySelectorAll('[data-security-check]').forEach(function (check) {
            check.checked = saved.indexOf(check.id) !== -1;
        });
    }

    function securityStatus(done, total) {
        if (!done) {
            return 'Start with scope, secrets, and rollback.';
        }
        if (done === total) {
            return 'Checklist complete. Get human approval before production.';
        }
        if (done / total >= 0.75) {
            return 'Nearly ready. Finish high-risk systems and approval notes.';
        }
        return 'In progress. Keep turning assumptions into evidence.';
    }

    function updateSecurityChecklist(guide) {
        var checks = Array.prototype.slice.call(guide.querySelectorAll('[data-security-check]'));
        var done = checks.filter(function (check) {
            return check.checked;
        }).length;
        var total = checks.length;
        var count = guide.querySelector('[data-security-count]');
        var status = guide.querySelector('[data-security-status]');
        var progress = guide.querySelector('[data-security-progress]');

        if (count) {
            count.textContent = String(done);
        }
        if (status) {
            status.textContent = securityStatus(done, total);
        }
        if (progress) {
            progress.max = total;
            progress.value = done;
        }

        guide.querySelectorAll('[data-security-category]').forEach(function (category) {
            var categoryChecks = Array.prototype.slice.call(category.querySelectorAll('[data-security-check]'));
            var categoryDone = categoryChecks.filter(function (check) {
                return check.checked;
            }).length;
            var categoryCount = category.querySelector('[data-security-category-count]');
            var categoryProgress = category.querySelector('[data-security-category-progress]');
            if (categoryCount) {
                categoryCount.textContent = String(categoryDone);
            }
            if (categoryProgress) {
                categoryProgress.max = categoryChecks.length;
                categoryProgress.value = categoryDone;
            }
        });
    }

    function updateSecurityRisk(guide) {
        var selected = Array.prototype.slice.call(guide.querySelectorAll('[data-security-risk]:checked'));
        var score = selected.reduce(function (sum, input) {
            return sum + (parseInt(input.getAttribute('data-risk-points') || '0', 10) || 0);
        }, 0);
        var result = guide.querySelector('[data-security-risk-result]');
        if (!result) {
            return;
        }

        var tier = 'Low risk';
        var body = 'Use the quick review: scope the task, protect secrets, run basic QA, and document rollback.';
        if (score >= 18) {
            tier = 'Critical risk';
            body = 'Do not let AI edit freely. Require inspect-first work, branch or staging, dependency/auth/payment review, tests, human approval, monitoring, and a rollback owner.';
        } else if (score >= 12) {
            tier = 'High risk';
            body = 'Use the full checklist, review secrets and permissions, run checks, verify deployment behavior, and get written approval before production.';
        } else if (score >= 6) {
            tier = 'Medium risk';
            body = 'Use a scoped review with tests, dependency notes, privacy checks, and rollback steps before publishing.';
        }

        result.innerHTML = '<p class="kingy-ali-kicker">Risk readout</p><h3>' + escapeHtml(tier) + '</h3><p>' + escapeHtml(body) + '</p><p><strong>Score:</strong> ' + String(score) + '</p>';
    }

    function securityPromptValue(form, key, fallback) {
        var field = form.querySelector('[data-security-prompt-field="' + key + '"]');
        var value = field ? field.value.trim() : '';
        return value || fallback;
    }

    function buildSecurityPrompt(form) {
        return [
            '/goal Safely review and improve ' + securityPromptValue(form, 'project', '[PROJECT / PAGE / FEATURE]') + '.',
            '',
            'Context:',
            '- Branch or safe workspace: ' + securityPromptValue(form, 'branch', '[BRANCH / STAGING / BACKUP]'),
            '- Files or URLs to inspect: ' + securityPromptValue(form, 'files', '[FILES / URLS]'),
            '- Allowed changes: ' + securityPromptValue(form, 'allowed', '[ALLOWED CHANGES]'),
            '- Must not change: ' + securityPromptValue(form, 'forbidden', '[FORBIDDEN CHANGES]'),
            '- Known risks: ' + securityPromptValue(form, 'risks', '[SECRETS / AUTH / PAYMENTS / DATA / DEPENDENCIES / PRODUCTION]'),
            '- Test commands or checks: ' + securityPromptValue(form, 'tests', '[TESTS / QA CHECKS]'),
            '- Done when: ' + securityPromptValue(form, 'done', '[DONE-WHEN]'),
            '- Rollback path: ' + securityPromptValue(form, 'rollback', '[ROLLBACK]'),
            '',
            'Rules:',
            '1. Inspect before editing. Summarize current behavior, relevant files, unknowns, and risk level.',
            '2. Do not expose or print secrets, tokens, private data, client records, or credentials.',
            '3. Do not add packages, external scripts, auth changes, payment changes, database changes, or production changes without calling them out first.',
            '4. Make the smallest safe change that satisfies the goal.',
            '5. Run available checks and verify desktop/mobile behavior when relevant.',
            '6. Summarize changed files, security/privacy risks, tests run, remaining gaps, and rollback steps.'
        ].join('\n');
    }

    function buildSecurityMarkdown(guide) {
        var lines = [
            '# Security Review Checklist',
            '',
            'Progress: ' + (guide.querySelector('[data-security-count]') ? guide.querySelector('[data-security-count]').textContent : '0') + ' complete',
            ''
        ];

        guide.querySelectorAll('[data-security-category]').forEach(function (category) {
            var title = category.querySelector('h3');
            lines.push('## ' + (title ? title.textContent.trim() : 'Review phase'));
            category.querySelectorAll('[data-security-item]').forEach(function (item) {
                var check = item.querySelector('[data-security-check]');
                lines.push('- [' + (check && check.checked ? 'x' : ' ') + '] ' + (item.getAttribute('data-item-title') || ''));
                lines.push('  - Why: ' + (item.getAttribute('data-item-why') || ''));
                lines.push('  - Pass/fail: ' + (item.getAttribute('data-item-pass') || ''));
                lines.push('  - Ask Codex: ' + (item.getAttribute('data-item-ask') || ''));
            });
            lines.push('');
        });

        return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function initSecurityReviewGuide(guide) {
        restoreSecurityChecks(guide);
        updateSecurityChecklist(guide);
        updateSecurityRisk(guide);

        guide.querySelectorAll('[data-security-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                saveSecurityChecks(guide);
                updateSecurityChecklist(guide);
            });
        });

        guide.querySelectorAll('[data-security-risk]').forEach(function (input) {
            input.addEventListener('change', function () {
                updateSecurityRisk(guide);
            });
        });

        guide.querySelectorAll('[data-security-clear-risk]').forEach(function (button) {
            button.addEventListener('click', function () {
                guide.querySelectorAll('[data-security-risk]').forEach(function (input) {
                    input.checked = false;
                });
                updateSecurityRisk(guide);
            });
        });

        guide.querySelectorAll('[data-security-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                guide.querySelectorAll('[data-security-check]').forEach(function (check) {
                    check.checked = false;
                });
                try {
                    window.localStorage.removeItem(securityStorageKey());
                } catch (error) {}
                updateSecurityChecklist(guide);
            });
        });

        guide.querySelectorAll('[data-security-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });

        guide.querySelectorAll('[data-security-copy-markdown]').forEach(function (button) {
            button.addEventListener('click', function () {
                copyPlainText(buildSecurityMarkdown(guide), button);
            });
        });

        guide.querySelectorAll('[data-security-copy-text]').forEach(function (button) {
            button.addEventListener('click', function () {
                var source = guide.querySelector(button.getAttribute('data-security-copy-text') || '');
                copyPlainText(source ? source.textContent : '', button);
            });
        });

        guide.querySelectorAll('[data-security-copy-output]').forEach(function (button) {
            button.addEventListener('click', function () {
                var output = guide.querySelector(button.getAttribute('data-security-copy-output') || '');
                copyPlainText(output ? output.value : '', button);
            });
        });

        guide.querySelectorAll('[data-security-copy-default]').forEach(function (button) {
            button.addEventListener('click', function () {
                var output = guide.querySelector('[data-security-prompt-output]');
                copyPlainText(output ? output.value : '', button);
            });
        });

        guide.querySelectorAll('[data-security-generate-prompt]').forEach(function (button) {
            button.addEventListener('click', function () {
                var form = button.closest('[data-security-prompt-form]');
                var output = guide.querySelector('[data-security-prompt-output]');
                if (form && output) {
                    output.value = buildSecurityPrompt(form);
                }
            });
        });

        guide.querySelectorAll('[data-security-reset-prompt]').forEach(function (button) {
            button.addEventListener('click', function () {
                var form = button.closest('[data-security-prompt-form]');
                if (form) {
                    form.reset();
                }
            });
        });
    }

    function copilotStorageKey(root, suffix) {
        var path = window.location.pathname.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-|-$/g, '') || 'microsoft-copilot-course';
        return 'kingy-copilot-course-' + path + '-' + suffix;
    }

    function copilotFieldValue(root, selector, fallback) {
        var field = root.querySelector(selector);
        return field && field.value ? field.value.trim() : fallback;
    }

    function updateCopilotScrollProgress(root) {
        var bar = root.querySelector('[data-copilot-scroll-progress]');
        if (!bar) {
            return;
        }
        function update() {
            var rect = root.getBoundingClientRect();
            var total = Math.max(1, rect.height - window.innerHeight);
            var passed = Math.min(total, Math.max(0, -rect.top));
            bar.style.width = Math.round((passed / total) * 100) + '%';
        }
        update();
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
    }

    function copilotPathRecommendation(role, goal, license) {
        var modules = ['00-04'];
        var title = 'Beginner path';
        var reason = 'Start with orientation, Copilot basics, prompting, and Copilot Chat before specializing.';

        if (role === 'office' || goal === 'productivity') {
            title = 'Office productivity path';
            modules = ['00-04', '05-12'];
            reason = 'Build the foundations, then focus on Word, PowerPoint, Excel, Outlook, Teams, notes, and files.';
        }
        if (goal === 'data') {
            title = 'Excel and analysis path';
            modules = ['00-04', '07-08', '12-13'];
            reason = 'Learn prompt structure, clean data habits, Excel workflows, Analyst, and source verification.';
        }
        if (goal === 'meetings') {
            title = 'Meetings and email path';
            modules = ['00-04', '09-10', '18'];
            reason = 'Use Outlook and Teams to prepare, recap, assign, and follow through without losing human ownership.';
        }
        if (role === 'builder' || goal === 'agents') {
            title = 'Agents and Copilot Studio path';
            modules = ['00-04', '13-17', '19'];
            reason = 'Move from chat to agent selection, Agent Builder, Copilot Studio, actions, automation, and safe access.';
        }
        if (role === 'leader' || goal === 'rollout') {
            title = 'Business leader path';
            modules = ['00-04', '18-20', '19'];
            reason = 'Focus on use cases, adoption, measurement, governance, team training, and final capstones.';
        }
        if (role === 'admin') {
            title = 'Admin and governance path';
            modules = ['00-04', '12', '19-20', '13-17'];
            reason = 'Prioritize licenses, permissions, data hygiene, sensitivity, agents, and rollout controls.';
        }

        var caveat = 'Feature access may vary by account, license, tenant settings, region, app version, and admin controls.';
        if (license === 'unknown') {
            caveat = 'First action: complete Module 00 and confirm your account type, license label, app access, and admin settings.';
        } else if (license === 'chat') {
            caveat = 'You can still learn prompting and many Chat workflows, but work-grounded app features may be limited.';
        } else if (license === 'm365') {
            caveat = 'Use the work-grounded modules, but still verify file permissions, app availability, and rollout status.';
        } else if (license === 'admin') {
            caveat = 'Pair the learning path with governance, permission review, training, and measurement before a broad rollout.';
        }

        return { title: title, modules: modules.join(', '), reason: reason, caveat: caveat };
    }

    function updateCopilotPath(root) {
        var rec = copilotPathRecommendation(
            copilotFieldValue(root, '[data-copilot-path-field="role"]', 'beginner'),
            copilotFieldValue(root, '[data-copilot-path-field="goal"]', 'learn'),
            copilotFieldValue(root, '[data-copilot-path-field="license"]', 'unknown')
        );
        var output = root.querySelector('[data-copilot-path-output]');
        if (output) {
            output.innerHTML = '<p class="kingy-ali-kicker">Recommended path</p><h3>' + escapeHtml(rec.title) + '</h3><p><strong>Start with modules:</strong> ' + escapeHtml(rec.modules) + '</p><p>' + escapeHtml(rec.reason) + '</p><p><strong>Access note:</strong> ' + escapeHtml(rec.caveat) + '</p>';
        }
    }

    function restoreCopilotChecks(root) {
        try {
            var readiness = JSON.parse(window.localStorage.getItem(copilotStorageKey(root, 'readiness')) || '[]');
            root.querySelectorAll('[data-copilot-readiness-check]').forEach(function (check, index) {
                check.checked = readiness.indexOf(index) !== -1;
            });
            var modules = JSON.parse(window.localStorage.getItem(copilotStorageKey(root, 'modules')) || '[]');
            root.querySelectorAll('[data-copilot-module-check]').forEach(function (check) {
                check.checked = modules.indexOf(check.getAttribute('data-copilot-module-check')) !== -1;
            });
        } catch (error) {}
    }

    function saveCopilotReadiness(root) {
        var checked = [];
        root.querySelectorAll('[data-copilot-readiness-check]').forEach(function (check, index) {
            if (check.checked) {
                checked.push(index);
            }
        });
        try {
            window.localStorage.setItem(copilotStorageKey(root, 'readiness'), JSON.stringify(checked));
        } catch (error) {}
    }

    function saveCopilotModules(root) {
        var checked = [];
        root.querySelectorAll('[data-copilot-module-check]').forEach(function (check) {
            if (check.checked) {
                checked.push(check.getAttribute('data-copilot-module-check'));
            }
        });
        try {
            window.localStorage.setItem(copilotStorageKey(root, 'modules'), JSON.stringify(checked));
        } catch (error) {}
    }

    function updateCopilotReadiness(root) {
        var checks = Array.prototype.slice.call(root.querySelectorAll('[data-copilot-readiness-check]'));
        var done = checks.filter(function (check) { return check.checked; }).length;
        var score = root.querySelector('[data-copilot-readiness-score]');
        var status = root.querySelector('[data-copilot-readiness-status]');
        if (score) {
            score.textContent = done + '/' + checks.length;
        }
        if (status) {
            status.textContent = done === checks.length ? 'Ready for deeper workflows' : done >= 4 ? 'Close, finish the environment checks' : done >= 2 ? 'Good start' : 'Not started';
        }
        saveCopilotReadiness(root);
    }

    function updateCopilotProgress(root) {
        var checks = Array.prototype.slice.call(root.querySelectorAll('[data-copilot-module-check]'));
        var done = checks.filter(function (check) { return check.checked; }).length;
        var percent = checks.length ? Math.round((done / checks.length) * 100) : 0;
        var label = root.querySelector('[data-copilot-progress-label]');
        var progress = root.querySelector('[data-copilot-module-progress]');
        if (label) {
            label.textContent = percent + '%';
        }
        if (progress) {
            progress.value = done;
            progress.max = checks.length;
        }
        saveCopilotModules(root);
    }

    function filterCopilotModules(root, track) {
        root.querySelectorAll('[data-copilot-filter]').forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-copilot-filter') === track);
        });
        root.querySelectorAll('[data-copilot-module]').forEach(function (module) {
            module.hidden = !(track === 'all' || module.getAttribute('data-track') === track);
        });
    }

    function buildCopilotPrompt(root) {
        return [
            'Goal: ' + copilotFieldValue(root, '[data-copilot-prompt-field="goal"]', 'Complete the task clearly and safely'),
            '',
            'Context: ' + copilotFieldValue(root, '[data-copilot-prompt-field="context"]', 'Use the context I provide and ask clarifying questions if needed.'),
            '',
            'Expectations: ' + copilotFieldValue(root, '[data-copilot-prompt-field="expectations"]', 'Return a concise, structured answer with assumptions and next steps.'),
            '',
            'Source: ' + copilotFieldValue(root, '[data-copilot-prompt-field="source"]', 'Use only the provided source material and flag anything uncertain.')
        ].join('\n');
    }

    function updateCopilotPrompt(root) {
        var output = root.querySelector('[data-copilot-prompt-output]');
        if (output) {
            output.value = buildCopilotPrompt(root);
        }
    }

    function buildCopilotCapstone(root) {
        var role = copilotFieldValue(root, '[data-copilot-capstone-field="role"]', 'Business user');
        var workflow = copilotFieldValue(root, '[data-copilot-capstone-field="workflow"]', 'Repeatable Copilot workflow');
        var app = copilotFieldValue(root, '[data-copilot-capstone-field="app"]', 'Copilot Chat');
        return [
            'Microsoft Copilot capstone project',
            '',
            'Role: ' + role,
            'Workflow: ' + workflow,
            'Primary app: ' + app,
            '',
            'Build:',
            '1. Define the real business problem and the current manual process.',
            '2. Collect approved source material, files, meetings, or examples.',
            '3. Write one reusable Goal, Context, Expectations, Source prompt.',
            '4. Run the workflow and capture before/after output.',
            '5. Add a verification step for numbers, claims, owners, and deadlines.',
            '6. Document when a human must approve, edit, or escalate.',
            '7. Measure time saved, quality improved, or risk reduced.',
            '',
            'Final deliverable: one prompt or agent brief, one example output, one QA checklist, and one rollout note.'
        ].join('\n');
    }

    function updateCopilotCapstone(root) {
        var output = root.querySelector('[data-copilot-capstone-output]');
        if (output) {
            output.value = buildCopilotCapstone(root);
        }
    }

    function updateCopilotRoi(root) {
        function roiNumber(key, fallback) {
            var input = root.querySelector('[data-copilot-roi-input="' + key + '"]');
            var value = input ? parseFloat(input.value) : fallback;
            return Number.isFinite(value) ? Math.max(0, value) : fallback;
        }
        var people = roiNumber('people', 0);
        var hours = roiNumber('hours', 0);
        var rate = roiNumber('rate', 0);
        var cost = roiNumber('cost', 0);
        var monthlyValue = people * hours * rate * 4.33;
        var monthlyCost = people * cost;
        var net = monthlyValue - monthlyCost;
        var netOutput = root.querySelector('[data-copilot-roi-output="net"]');
        var detail = root.querySelector('[data-copilot-roi-output="detail"]');
        if (netOutput) {
            netOutput.textContent = formatCurrency(net);
        }
        if (detail) {
            detail.textContent = formatCurrency(monthlyValue) + ' estimated monthly time value - ' + formatCurrency(monthlyCost) + ' estimated license cost';
        }
    }

    function copyCopilotModule(module, button) {
        var title = module.querySelector('summary strong');
        var lines = [title ? title.textContent.trim() : 'Microsoft Copilot module', ''];
        module.querySelectorAll('ol li').forEach(function (item) {
            lines.push('[ ] ' + item.textContent.trim());
        });
        var capstone = module.querySelector('.kingy-ali-copilot-module__body p:last-of-type');
        if (capstone) {
            lines.push('', capstone.textContent.trim().replace(/\s+/g, ' '));
        }
        copyPlainText(lines.join('\n'), button);
    }

    function initCopilotCourse(root) {
        restoreCopilotChecks(root);
        updateCopilotScrollProgress(root);
        updateCopilotPath(root);
        updateCopilotReadiness(root);
        updateCopilotProgress(root);
        updateCopilotPrompt(root);
        updateCopilotCapstone(root);
        updateCopilotRoi(root);

        root.querySelectorAll('[data-copilot-path-field]').forEach(function (field) {
            field.addEventListener('change', function () { updateCopilotPath(root); });
        });
        root.querySelectorAll('[data-copilot-readiness-check]').forEach(function (check) {
            check.addEventListener('change', function () { updateCopilotReadiness(root); });
        });
        root.querySelectorAll('[data-copilot-module-check]').forEach(function (check) {
            check.addEventListener('change', function () { updateCopilotProgress(root); });
        });
        root.querySelectorAll('[data-copilot-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                filterCopilotModules(root, button.getAttribute('data-copilot-filter') || 'all');
            });
        });
        root.querySelectorAll('[data-copilot-prompt-field]').forEach(function (field) {
            field.addEventListener('input', function () { updateCopilotPrompt(root); });
        });
        root.querySelectorAll('[data-copilot-capstone-field]').forEach(function (field) {
            field.addEventListener('change', function () { updateCopilotCapstone(root); });
        });
        root.querySelectorAll('[data-copilot-roi-input]').forEach(function (field) {
            field.addEventListener('input', function () { updateCopilotRoi(root); });
        });

        root.addEventListener('submit', function (event) {
            var promptForm = event.target.closest('[data-copilot-prompt-form]');
            if (promptForm) {
                event.preventDefault();
                updateCopilotPrompt(root);
                copyPlainText(root.querySelector('[data-copilot-prompt-output]').value, promptForm.querySelector('button[type="submit"]'));
                return;
            }
            var capstoneForm = event.target.closest('[data-copilot-capstone-form]');
            if (capstoneForm) {
                event.preventDefault();
                updateCopilotCapstone(root);
                copyPlainText(root.querySelector('[data-copilot-capstone-output]').value, capstoneForm.querySelector('button[type="submit"]'));
                return;
            }
            var quiz = event.target.closest('[data-copilot-quiz]');
            if (quiz) {
                event.preventDefault();
                var selected = quiz.querySelector('input[type="radio"]:checked');
                var output = quiz.querySelector('[data-copilot-quiz-output]');
                if (output) {
                    output.textContent = selected && selected.value === '1' ? 'Correct. Keep going.' : 'Not quite. The safer answer is the one that checks access, scope, sources, and human review.';
                    output.className = selected && selected.value === '1' ? 'is-correct' : 'is-wrong';
                }
            }
        });

        root.addEventListener('click', function (event) {
            var textButton = event.target.closest('[data-copilot-copy-text]');
            if (textButton) {
                var source = root.querySelector(textButton.getAttribute('data-copilot-copy-text') || '');
                copyPlainText(source ? source.textContent : '', textButton);
                return;
            }
            var moduleButton = event.target.closest('[data-copilot-copy-module]');
            if (moduleButton) {
                var module = moduleButton.closest('[data-copilot-module]');
                if (module) {
                    copyCopilotModule(module, moduleButton);
                }
                return;
            }
            var reset = event.target.closest('[data-copilot-reset-progress]');
            if (reset) {
                root.querySelectorAll('[data-copilot-module-check]').forEach(function (check) {
                    check.checked = false;
                });
                try {
                    window.localStorage.removeItem(copilotStorageKey(root, 'modules'));
                } catch (error) {}
                updateCopilotProgress(root);
            }
        });
    }

    function agentSkillsStorageKey(name) {
        return 'kingyAgentSkillsWorksheet:' + name + ':' + window.location.pathname.replace(/\/+$/, '');
    }

    function agentSkillsFieldValue(root, key, fallback) {
        var field = root.querySelector('[data-agent-skill-field="' + key + '"]');
        var value = field ? field.value.trim() : '';
        return value || fallback || '';
    }

    function agentSkillsSlug(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '') || 'agent-skill-planning-worksheet';
    }

    function agentSkillsData(root) {
        var data = {};
        root.querySelectorAll('[data-agent-skill-field]').forEach(function (field) {
            data[field.getAttribute('data-agent-skill-field')] = field.value.trim();
        });
        return data;
    }

    function restoreAgentSkillsFields(root) {
        try {
            var saved = JSON.parse(window.localStorage.getItem(agentSkillsStorageKey('fields')) || '{}');
            root.querySelectorAll('[data-agent-skill-field]').forEach(function (field) {
                var key = field.getAttribute('data-agent-skill-field');
                if (Object.prototype.hasOwnProperty.call(saved, key)) {
                    field.value = saved[key];
                }
            });
        } catch (error) {}
    }

    function saveAgentSkillsFields(root) {
        try {
            window.localStorage.setItem(agentSkillsStorageKey('fields'), JSON.stringify(agentSkillsData(root)));
        } catch (error) {}
    }

    function restoreAgentSkillsChecks(root) {
        try {
            var saved = JSON.parse(window.localStorage.getItem(agentSkillsStorageKey('checks')) || '[]');
            root.querySelectorAll('[data-agent-skills-check]').forEach(function (check) {
                check.checked = saved.indexOf(check.id) !== -1;
            });
        } catch (error) {}
    }

    function saveAgentSkillsChecks(root) {
        var checked = Array.prototype.slice.call(root.querySelectorAll('[data-agent-skills-check]'))
            .filter(function (check) {
                return check.checked;
            })
            .map(function (check) {
                return check.id;
            });
        try {
            window.localStorage.setItem(agentSkillsStorageKey('checks'), JSON.stringify(checked));
        } catch (error) {}
    }

    function agentSkillsStatus(done, total) {
        if (!done) {
            return 'Start by naming the workflow.';
        }
        if (done === total) {
            return 'Review complete. Get human approval before using the skill on real work.';
        }
        if (done / total >= 0.75) {
            return 'Nearly ready. Tighten tests, examples, and rollback.';
        }
        return 'In progress. Keep clarifying triggers, boundaries, and evidence.';
    }

    function updateAgentSkillsProgress(root) {
        var checks = Array.prototype.slice.call(root.querySelectorAll('[data-agent-skills-check]'));
        var done = checks.filter(function (check) {
            return check.checked;
        }).length;
        var total = checks.length;
        var count = root.querySelector('[data-agent-skills-count]');
        var status = root.querySelector('[data-agent-skills-status]');
        var progress = root.querySelector('[data-agent-skills-progress]');

        if (count) {
            count.textContent = String(done);
        }
        if (status) {
            status.textContent = agentSkillsStatus(done, total);
        }
        if (progress) {
            progress.max = total;
            progress.value = done;
        }

        root.querySelectorAll('[data-agent-skills-section]').forEach(function (section) {
            var sectionChecks = Array.prototype.slice.call(section.querySelectorAll('[data-agent-skills-check]'));
            var sectionDone = sectionChecks.filter(function (check) {
                return check.checked;
            }).length;
            var sectionCount = section.querySelector('[data-agent-skills-section-count]');
            var sectionProgress = section.querySelector('[data-agent-skills-section-progress]');
            if (sectionCount) {
                sectionCount.textContent = String(sectionDone);
            }
            if (sectionProgress) {
                sectionProgress.max = sectionChecks.length;
                sectionProgress.value = sectionDone;
            }
        });
    }

    function buildAgentSkillsGoal(root) {
        var name = agentSkillsFieldValue(root, 'skill_name', '[Skill name]');
        return [
            '/goal Create or improve the "' + name + '" agent skill from this planning worksheet.',
            '',
            'Context:',
            '- Audience: ' + agentSkillsFieldValue(root, 'audience', '[Who will use it]'),
            '- Trigger: ' + agentSkillsFieldValue(root, 'trigger', '[When the agent should use it]'),
            '- Purpose: ' + agentSkillsFieldValue(root, 'purpose', '[Outcome]'),
            '- Inputs: ' + agentSkillsFieldValue(root, 'inputs', '[Inputs the skill needs]'),
            '- Context to inspect: ' + agentSkillsFieldValue(root, 'context', '[Files, URLs, docs, examples, or product context]'),
            '- Tools allowed: ' + agentSkillsFieldValue(root, 'tools', '[Tools, files, or references]'),
            '- Constraints: ' + agentSkillsFieldValue(root, 'constraints', '[Rules and safety constraints]'),
            '- Must not change: ' + agentSkillsFieldValue(root, 'forbidden', '[Forbidden changes]'),
            '- Examples: ' + agentSkillsFieldValue(root, 'examples', '[Good and bad examples]'),
            '- Success criteria: ' + agentSkillsFieldValue(root, 'success', '[Done when]'),
            '- Tests and QA: ' + agentSkillsFieldValue(root, 'tests', '[Checks to run]'),
            '- Human approval: ' + agentSkillsFieldValue(root, 'approval', '[Approval gate]'),
            '- Rollback: ' + agentSkillsFieldValue(root, 'rollback', '[Rollback path]'),
            '',
            'Rules:',
            '1. Inspect relevant context before editing or writing the skill.',
            '2. Keep the skill narrow, repeatable, and easy for another agent to follow.',
            '3. Do not include secrets, private data, fake claims, or unsupported references.',
            '4. Include activation guidance, workflow steps, quality bar, examples, verification, and rollback.',
            '5. Summarize changed files, tests run, remaining risks, and human approval needs.'
        ].join('\n');
    }

    function buildAgentSkillsSkill(root) {
        return [
            '# ' + agentSkillsFieldValue(root, 'skill_name', 'Skill name'),
            '',
            '## When To Use',
            agentSkillsFieldValue(root, 'trigger', 'Use this when the user asks for the repeatable workflow this skill supports.'),
            '',
            '## Audience',
            agentSkillsFieldValue(root, 'audience', 'Name the user, team, or agent surface this skill is for.'),
            '',
            '## Purpose',
            agentSkillsFieldValue(root, 'purpose', 'State the outcome the agent should produce.'),
            '',
            '## Required Inputs',
            agentSkillsFieldValue(root, 'inputs', 'List URLs, files, screenshots, examples, constraints, and source notes the agent needs.'),
            '',
            '## Context To Inspect First',
            agentSkillsFieldValue(root, 'context', 'Tell the agent what repo files, page source, docs, schemas, or product context to read before acting.'),
            '',
            '## Tools And References',
            agentSkillsFieldValue(root, 'tools', 'List allowed tools, scripts, docs, and references.'),
            '',
            '## Workflow',
            '1. Confirm the goal, audience, constraints, and unknowns.',
            '2. Inspect the required context before editing or producing final work.',
            '3. Make the smallest useful change or produce the requested artifact.',
            '4. Run the named tests and collect evidence.',
            '5. Report changed files, results, remaining risks, approval needs, and rollback.',
            '',
            '## Constraints',
            agentSkillsFieldValue(root, 'constraints', 'Keep work scoped, preserve public interfaces, and protect private data.'),
            '',
            '## Must Not Change',
            agentSkillsFieldValue(root, 'forbidden', 'List routes, APIs, schemas, content claims, secrets, or production behavior that must not change.'),
            '',
            '## Examples',
            agentSkillsFieldValue(root, 'examples', 'Include one good example and one bad example.'),
            '',
            '## Acceptance Criteria',
            agentSkillsFieldValue(root, 'success', 'Define done-when criteria.'),
            '',
            '## Tests And QA',
            agentSkillsFieldValue(root, 'tests', 'List lint, tests, browser checks, source checks, and manual QA.'),
            '',
            '## Human Approval',
            agentSkillsFieldValue(root, 'approval', 'Name who reviews risky changes and what they approve.'),
            '',
            '## Rollback',
            agentSkillsFieldValue(root, 'rollback', 'Describe how to revert, disable, restore, or remove the skill or its output.')
        ].join('\n');
    }

    function buildAgentSkillsReview(root) {
        var lines = [
            '# Agent Skill Review Checklist',
            '',
            'Skill: ' + agentSkillsFieldValue(root, 'skill_name', '[Skill name]'),
            'Audience: ' + agentSkillsFieldValue(root, 'audience', '[Audience]'),
            ''
        ];

        root.querySelectorAll('[data-agent-skills-section]').forEach(function (section) {
            var title = section.querySelector('h3');
            lines.push('## ' + (title ? title.textContent.trim() : 'Review section'));
            section.querySelectorAll('[data-agent-skills-item]').forEach(function (item) {
                var check = item.querySelector('[data-agent-skills-check]');
                lines.push('- [' + (check && check.checked ? 'x' : ' ') + '] ' + (item.getAttribute('data-item-title') || ''));
            });
            lines.push('');
        });

        lines.push('## Evidence');
        lines.push('- Success criteria: ' + agentSkillsFieldValue(root, 'success', '[Done when]'));
        lines.push('- Tests and QA: ' + agentSkillsFieldValue(root, 'tests', '[Checks]'));
        lines.push('- Human approval: ' + agentSkillsFieldValue(root, 'approval', '[Approval gate]'));
        lines.push('- Rollback: ' + agentSkillsFieldValue(root, 'rollback', '[Rollback path]'));

        return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    function buildAgentSkillsMarkdown(root) {
        return [
            '# Agent Skills Planning Worksheet',
            '',
            '## Worksheet',
            '',
            Object.entries(agentSkillsData(root)).map(function (entry) {
                return '- **' + entry[0].replace(/_/g, ' ') + ':** ' + (entry[1] || '[blank]');
            }).join('\n'),
            '',
            '## Codex Goal Prompt',
            '',
            '```text',
            buildAgentSkillsGoal(root),
            '```',
            '',
            '## SKILL.md Outline',
            '',
            '```markdown',
            buildAgentSkillsSkill(root),
            '```',
            '',
            buildAgentSkillsReview(root)
        ].join('\n');
    }

    function downloadAgentSkillsFile(root, type, button) {
        var name = agentSkillsSlug(agentSkillsFieldValue(root, 'skill_name', 'agent-skill-planning-worksheet'));
        var data;
        var filename;
        var mime;
        if (type === 'json') {
            data = JSON.stringify({
                worksheet: agentSkillsData(root),
                goal_prompt: buildAgentSkillsGoal(root),
                skill_md: buildAgentSkillsSkill(root),
                review_checklist: buildAgentSkillsReview(root)
            }, null, 2);
            filename = name + '.json';
            mime = 'application/json;charset=utf-8';
        } else {
            data = buildAgentSkillsMarkdown(root);
            filename = name + '.md';
            mime = 'text/markdown;charset=utf-8';
        }

        var blob = new Blob([data], { type: mime });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () {
            URL.revokeObjectURL(link.href);
        }, 1000);

        var status = root.querySelector('[data-agent-skills-status-line]');
        if (status) {
            status.textContent = 'Downloaded ' + filename;
        }
        if (button) {
            var old = button.textContent;
            button.textContent = 'Downloaded';
            setTimeout(function () {
                button.textContent = old;
            }, 1400);
        }
    }

    function updateAgentSkillsOutputs(root) {
        var goal = root.querySelector('[data-agent-skills-output="goal"]');
        var skill = root.querySelector('[data-agent-skills-output="skill"]');
        var review = root.querySelector('[data-agent-skills-output="review"]');
        if (goal) {
            goal.value = buildAgentSkillsGoal(root);
        }
        if (skill) {
            skill.value = buildAgentSkillsSkill(root);
        }
        if (review) {
            review.value = buildAgentSkillsReview(root);
        }
    }

    function loadAgentSkillsSample(root) {
        var sample = {
            skill_name: 'WordPress Interactive Worksheet Builder',
            audience: 'Site owners and Codex agents improving Kingy AI Academy pages',
            trigger: 'Use when a page feels thin and should become a managed interactive worksheet, checklist, calculator, or prompt builder.',
            purpose: 'Create a scoped WordPress plugin implementation that adds useful interaction, downloadable outputs, SEO metadata, schema, and verification evidence.',
            inputs: 'Live URL, local plugin path, existing shortcode patterns, target audience, current page body, and verification requirements.',
            context: 'Inspect plugin includes, setup-pages registry, shared CSS/JS assets, live page source, REST content, and similar checklist pages before editing.',
            tools: 'rg, sed, php -l, curl, browser QA, WordPress REST, existing launch-filters.js helpers, and local CSS patterns.',
            constraints: 'Use scoped classes, preserve public URL, protect secrets, do not invent claims, keep UX mobile-friendly, and report skipped checks.',
            forbidden: 'Do not rename unrelated routes, remove analytics attributes, edit production directly without deployment approval, or rewrite unrelated guide pages.',
            examples: 'Good: add a managed shortcode and verify rendered markers. Bad: paste raw unscoped HTML into the WordPress editor and skip copy/download testing.',
            success: 'The page is interactive, fuller, accessible, downloadable as Markdown/JSON, and verified with lint plus source/render checks.',
            tests: 'PHP lint, marker check, form generation, copy, downloads, print, checklist persistence, reset, desktop/mobile layout, metadata, and schema.',
            approval: 'A human reviews the changed files, page output, claims, risks, deployment target, and rollback notes before production publication.',
            rollback: 'Revert the plugin changes or restore the previous page content, purge caches, and verify the original page renders.'
        };
        Object.keys(sample).forEach(function (key) {
            var field = root.querySelector('[data-agent-skill-field="' + key + '"]');
            if (field) {
                field.value = sample[key];
            }
        });
        saveAgentSkillsFields(root);
        updateAgentSkillsOutputs(root);
    }

    function initAgentSkillsWorksheet(root) {
        restoreAgentSkillsFields(root);
        restoreAgentSkillsChecks(root);
        updateAgentSkillsOutputs(root);
        updateAgentSkillsProgress(root);

        root.querySelectorAll('[data-agent-skill-field]').forEach(function (field) {
            field.addEventListener('input', function () {
                saveAgentSkillsFields(root);
                updateAgentSkillsOutputs(root);
            });
        });
        root.querySelectorAll('[data-agent-skills-check]').forEach(function (check) {
            check.addEventListener('change', function () {
                saveAgentSkillsChecks(root);
                updateAgentSkillsProgress(root);
                updateAgentSkillsOutputs(root);
            });
        });
        root.querySelectorAll('[data-agent-skills-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = root.querySelector(button.getAttribute('data-agent-skills-copy') || '');
                copyPlainText(target ? target.value || target.textContent : '', button);
            });
        });
        root.querySelectorAll('[data-agent-skills-download]').forEach(function (button) {
            button.addEventListener('click', function () {
                downloadAgentSkillsFile(root, button.getAttribute('data-agent-skills-download') || 'markdown', button);
            });
        });
        root.querySelectorAll('[data-agent-skills-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });
        root.querySelectorAll('[data-agent-skills-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                root.querySelectorAll('[data-agent-skills-check]').forEach(function (check) {
                    check.checked = false;
                });
                try {
                    window.localStorage.removeItem(agentSkillsStorageKey('checks'));
                } catch (error) {}
                updateAgentSkillsProgress(root);
                updateAgentSkillsOutputs(root);
            });
        });
        root.querySelectorAll('[data-agent-skills-load-sample]').forEach(function (button) {
            button.addEventListener('click', function () {
                loadAgentSkillsSample(root);
                button.textContent = 'Sample loaded';
                setTimeout(function () {
                    button.textContent = 'Load sample';
                }, 1400);
            });
        });
        root.querySelectorAll('[data-agent-skills-clear]').forEach(function () {
            root.addEventListener('reset', function () {
                setTimeout(function () {
                    try {
                        window.localStorage.removeItem(agentSkillsStorageKey('fields'));
                    } catch (error) {}
                    updateAgentSkillsOutputs(root);
                }, 0);
            });
        });
    }

    function aiLaunchScorecardTier(score) {
        if (score >= 90) {
            return 'Breakout Launch Candidate';
        }
        if (score >= 75) {
            return 'Coverage-Ready Launch';
        }
        if (score >= 60) {
            return 'Promising Launch';
        }
        if (score >= 40) {
            return 'Launchable, But Weak';
        }
        return 'Invisible Launch';
    }

    function aiLaunchScorecardTierLevel(score) {
        if (score >= 90) {
            return 'breakout';
        }
        if (score >= 75) {
            return 'coverage';
        }
        if (score >= 60) {
            return 'promising';
        }
        if (score >= 40) {
            return 'weak';
        }
        return 'invisible';
    }

    function aiLaunchScorecardVerdict(score) {
        if (score >= 90) {
            return 'This launch has the clarity, proof, demo surface, and distribution signals to justify serious editorial and creator review.';
        }
        if (score >= 75) {
            return 'This launch is coverage-ready if the claims are source-backed and the demo can survive a real viewer clicking through.';
        }
        if (score >= 60) {
            return 'This is promising, but a few launch assets need tightening before the story is easy to cover, rank, or share.';
        }
        if (score >= 40) {
            return 'This can launch, but it will probably underperform until the positioning, proof, demo, and distribution plan are stronger.';
        }
        return 'Your launch is still mostly invisible. Start with a sharper product story, a concrete audience, and a demo people can understand without a call.';
    }

    function aiLaunchScorecardFixesByKey() {
        return {
            product_clarity: 'Day 1: rewrite the hero, one-line product explanation, and launch blurb around a concrete outcome.',
            audience_clarity: 'Day 2: choose the first target user and update the page, demo, and outreach list around that audience.',
            demo_quality: 'Day 3: record a short before-and-after demo that shows the workflow and output without filler.',
            website_quality: 'Day 4: tighten the launch page with screenshots, official sources, proof, mobile QA, and one clear CTA.',
            pricing_clarity: 'Day 5: add pricing, free-plan, trial, API, or contact-sales context so visitors know the buying path.',
            launch_distribution_readiness: 'Day 6: prepare Product Hunt, newsletter, community, social, and founder follow-up assets.',
            founder_company_visibility: 'Day 7: add founder/company proof, public profiles, source links, and a credible launch note.',
            traction_signals: 'Day 7: collect visible traction signals such as users, waitlist, GitHub, Product Hunt, revenue, or community proof.',
            seo_comparison_potential: 'Day 7: define the category, alternatives, comparison pages, and search queries this launch can own.',
            creator_coverage_fit: 'Day 7: turn the demo into a creator-friendly story with a clear hook, lesson, and viewer payoff.'
        };
    }

    function aiLaunchScorecardPagesByKey() {
        return {
            product_clarity: 'Product overview page with one clear category and outcome',
            audience_clarity: 'Use-case page for the first target audience',
            demo_quality: 'Demo page with video, screenshots, transcript, and sample output',
            website_quality: 'Launch landing page with proof, sources, pricing path, and CTA',
            pricing_clarity: 'Pricing or free-plan page with limits and plan differences',
            launch_distribution_readiness: 'Launch hub page with Product Hunt, press kit, and creator assets',
            founder_company_visibility: 'Founder or company story page with public verification links',
            traction_signals: 'Changelog, case study, community, GitHub, or launch proof page',
            seo_comparison_potential: 'Alternatives, comparison, and category guide pages',
            creator_coverage_fit: 'Creator demo kit with talking points, screenshots, and product access notes'
        };
    }

    function aiLaunchScorecardAngle(kind, values) {
        var strongDemo = values.demo_quality >= 1;
        var strongSeo = values.seo_comparison_potential >= 1;
        var strongFounder = values.founder_company_visibility >= 1;
        var strongAudience = values.audience_clarity >= 1;
        var weakPricing = values.pricing_clarity < 1;

        if (kind === 'seo') {
            if (strongSeo && strongAudience) {
                return 'Build comparison and alternatives pages around the category, first audience, and the old way buyers solve the problem.';
            }
            if (strongAudience) {
                return 'Start with a use-case page for the first audience, then add category and alternatives pages once positioning is sharper.';
            }
            return 'Clarify the product category and first audience before chasing comparison keywords.';
        }

        if (kind === 'video') {
            if (strongDemo) {
                return 'Lead with a workflow teardown: problem, product in action, output, limits, and who should try it.';
            }
            return 'Create a 60-second before-and-after demo before asking creators or newsletters to cover the launch.';
        }

        if (strongFounder && !weakPricing) {
            return 'Use the founder story to explain why this product exists now, then back it with demo proof and pricing clarity.';
        }
        if (strongFounder) {
            return 'Use the founder story as the hook, but fix pricing clarity before paid or creator-led distribution.';
        }
        return 'Make the founder/company credible: who built it, why now, what changed, and where public proof lives.';
    }

    function aiLaunchScorecardValues(root) {
        var categories = [];
        var values = {};
        var total = 0;

        root.querySelectorAll('[data-scorecard-category]').forEach(function (category) {
            var input = category.querySelector('[data-scorecard-input]:checked');
            var key = category.getAttribute('data-scorecard-key') || '';
            var label = category.getAttribute('data-scorecard-label') || key;
            var weight = parseFloat(category.getAttribute('data-scorecard-weight') || '0');
            var value = input ? parseFloat(input.value || '0') : 0;
            if (!Number.isFinite(value)) {
                value = 0;
            }
            if (!Number.isFinite(weight)) {
                weight = 0;
            }

            values[key] = value;
            total += value * weight;
            categories.push({
                key: key,
                label: label,
                weight: weight,
                value: value
            });
        });

        return {
            categories: categories,
            values: values,
            score: Math.round(total)
        };
    }

    function aiLaunchScorecardList(items, fallback) {
        if (!items.length) {
            return '<li>' + escapeHtml(fallback) + '</li>';
        }

        return items.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('');
    }

    function aiLaunchScorecardRender(root) {
        var state = aiLaunchScorecardValues(root);
        var score = state.score;
        var tier = aiLaunchScorecardTier(score);
        var tierLevel = aiLaunchScorecardTierLevel(score);
        var weak = state.categories.filter(function (category) {
            return category.value < 1;
        }).sort(function (a, b) {
            if (a.value !== b.value) {
                return a.value - b.value;
            }
            return b.weight - a.weight;
        });
        var strengths = state.categories.filter(function (category) {
            return category.value >= 1;
        }).sort(function (a, b) {
            return b.weight - a.weight;
        }).slice(0, 5).map(function (category) {
            return category.label;
        });
        var weaknesses = weak.slice(0, 3).map(function (category) {
            return category.label;
        });
        var fixesByKey = aiLaunchScorecardFixesByKey();
        var pagesByKey = aiLaunchScorecardPagesByKey();
        var fixItems = weak.map(function (category) {
            return fixesByKey[category.key] || ('Fix ' + category.label.toLowerCase() + '.');
        }).slice(0, 7);
        var pageItems = weak.map(function (category) {
            return pagesByKey[category.key] || (category.label + ' page');
        }).slice(0, 5);

        if (!fixItems.length) {
            fixItems = [
                'Day 1: validate the page and demo with three people in the target audience.',
                'Day 2: prepare creator, newsletter, Product Hunt, and founder social variants.',
                'Day 3: turn the strongest use case into a comparison or category page.'
            ];
        }
        if (!pageItems.length) {
            pageItems = [
                'Customer proof or case study page',
                'Alternatives and comparison page',
                'Creator demo kit page'
            ];
        }

        root.querySelectorAll('[data-scorecard-score], [data-scorecard-hero-score]').forEach(function (node) {
            node.textContent = String(score);
        });
        root.querySelectorAll('[data-scorecard-tier], [data-scorecard-hero-tier]').forEach(function (node) {
            node.textContent = tier;
        });

        var bar = root.querySelector('[data-scorecard-bar]');
        if (bar) {
            bar.style.width = Math.max(0, Math.min(100, score)) + '%';
        }
        var result = root.querySelector('[data-scorecard-result]');
        if (result) {
            result.setAttribute('data-scorecard-tier-level', tierLevel);
        }

        var verdict = root.querySelector('[data-scorecard-verdict]');
        if (verdict) {
            verdict.textContent = aiLaunchScorecardVerdict(score);
        }

        var strengthsList = root.querySelector('[data-scorecard-strengths]');
        if (strengthsList) {
            strengthsList.innerHTML = aiLaunchScorecardList(strengths, 'No strong launch strengths yet.');
        }

        var weaknessesList = root.querySelector('[data-scorecard-weaknesses]');
        if (weaknessesList) {
            weaknessesList.innerHTML = aiLaunchScorecardList(weaknesses, 'No major launch weaknesses left.');
        }

        var fixes = root.querySelector('[data-scorecard-fixes]');
        if (fixes) {
            fixes.innerHTML = fixItems.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('');
        }

        var pages = root.querySelector('[data-scorecard-pages]');
        if (pages) {
            pages.innerHTML = pageItems.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('');
        }

        var seoAngle = root.querySelector('[data-scorecard-seo-angle]');
        if (seoAngle) {
            seoAngle.textContent = aiLaunchScorecardAngle('seo', state.values);
        }
        var videoAngle = root.querySelector('[data-scorecard-video-angle]');
        if (videoAngle) {
            videoAngle.textContent = aiLaunchScorecardAngle('video', state.values);
        }
        var founderAngle = root.querySelector('[data-scorecard-founder-angle]');
        if (founderAngle) {
            founderAngle.textContent = aiLaunchScorecardAngle('founder', state.values);
        }

        var formScore = root.querySelector('[data-scorecard-form-score]');
        if (formScore) {
            formScore.value = score + ' / 100 - ' + tier;
        }

        root.setAttribute('data-scorecard-current-score', String(score));
        root.setAttribute('data-scorecard-current-tier', tier);
        root.setAttribute('data-scorecard-tier-level', tierLevel);
        root.setAttribute('data-scorecard-current-strength', strengths[0] || 'none yet');
        root.setAttribute('data-scorecard-current-weakness', weaknesses[0] || 'keep distribution momentum');
    }

    function initAiLaunchScorecard(root) {
        aiLaunchScorecardRender(root);
        root.querySelectorAll('[data-scorecard-input]').forEach(function (input) {
            input.addEventListener('change', function () {
                aiLaunchScorecardRender(root);
            });
        });

        var copy = root.querySelector('[data-scorecard-copy]');
        if (copy) {
            copy.addEventListener('click', function () {
                var score = root.getAttribute('data-scorecard-current-score') || '0';
                var tier = root.getAttribute('data-scorecard-current-tier') || aiLaunchScorecardTier(parseInt(score, 10) || 0);
                var strength = root.getAttribute('data-scorecard-current-strength') || 'none yet';
                var weakness = root.getAttribute('data-scorecard-current-weakness') || 'keep distribution momentum';
                var text = 'My AI Launch Score from Kingy AI: ' + score + '/100 - ' + tier + '. Strongest signal: ' + strength + '. Biggest fix: ' + weakness + '.';
                var status = root.querySelector('[data-scorecard-copy-status]');
                copyText(text, function () {
                    if (status) {
                        status.textContent = 'Score summary copied.';
                    }
                }, function () {
                    if (status) {
                        status.textContent = 'Copy blocked by this browser. Summary: ' + text;
                    }
                });
            });
        }
    }

    function aiLaunchAcademyReadJson(key, fallback) {
        try {
            return JSON.parse(window.localStorage.getItem(key) || JSON.stringify(fallback));
        } catch (error) {
            return fallback;
        }
    }

    function aiLaunchAcademyWriteJson(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {}
    }

    function aiLaunchAcademyProgressKey() {
        return 'kingyAILaunchAcademy:completedLessons';
    }

    function aiLaunchAcademyChecksKey(lessonNumber) {
        return 'kingyAILaunchAcademy:lesson:' + String(lessonNumber) + ':checks';
    }

    function aiLaunchAcademyQuizKey(lessonNumber) {
        return 'kingyAILaunchAcademy:v2:quiz:' + String(lessonNumber);
    }

    function aiLaunchAcademyCapstoneKey() {
        return 'kingyAILaunchAcademy:v2:capstone';
    }

    function aiLaunchAcademyCertificateKey() {
        return 'kingyAILaunchAcademy:v2:certificate';
    }

    function aiLaunchAcademyTotal(root) {
        return parseInt(root.getAttribute('data-academy-total-lessons') || '12', 10) || 12;
    }

    function aiLaunchAcademyUrl(root, page) {
        var child = String(page || '').replace(/^\/+|\/+$/g, '');
        var match = window.location.pathname.match(/^(.*?\/ai-launch-academy)(?:\/|$)/);
        if (match && match[1]) {
            return window.location.origin + match[1].replace(/\/$/, '') + '/' + (child ? child + '/' : '');
        }

        var link = root ? root.querySelector('a[href*="/ai-launch-academy/"]') : null;
        if (link) {
            try {
                var linkUrl = new URL(link.href, window.location.href);
                var index = linkUrl.pathname.indexOf('/ai-launch-academy/');
                if (index >= 0) {
                    return linkUrl.origin + linkUrl.pathname.slice(0, index) + '/ai-launch-academy/' + (child ? child + '/' : '');
                }
            } catch (error) {}
        }

        return '/ai-launch-academy/' + (child ? child + '/' : '');
    }

    function aiLaunchAcademyTrack(root, eventType, label, objectId) {
        var lessonNumber = root ? root.getAttribute('data-academy-lesson-number') : '';
        var surface = lessonNumber ? 'academy_lesson_' + lessonNumber : 'academy';
        kingyALITrackEvent(eventType, label || eventType, surface, window.location.href, objectId || lessonNumber || '');
    }

    function aiLaunchAcademyCompletedMap() {
        return aiLaunchAcademyReadJson(aiLaunchAcademyProgressKey(), {});
    }

    function aiLaunchAcademySaveCompletedMap(map) {
        aiLaunchAcademyWriteJson(aiLaunchAcademyProgressKey(), map || {});
    }

    function aiLaunchAcademySetLessonComplete(lessonNumber, complete) {
        if (!lessonNumber) {
            return;
        }
        var map = aiLaunchAcademyCompletedMap();
        if (complete) {
            map[String(lessonNumber)] = true;
        } else {
            delete map[String(lessonNumber)];
        }
        aiLaunchAcademySaveCompletedMap(map);
    }

    function aiLaunchAcademyUpdateProgress(root) {
        var total = aiLaunchAcademyTotal(root);
        var map = aiLaunchAcademyCompletedMap();
        var count = Object.keys(map).filter(function (key) {
            var num = parseInt(key, 10);
            return map[key] && num >= 1 && num <= total;
        }).length;
        root.querySelectorAll('[data-academy-progress-count]').forEach(function (target) {
            target.textContent = String(count);
        });
        root.querySelectorAll('[data-academy-progress-bar]').forEach(function (bar) {
            bar.max = total;
            bar.value = count;
        });
    }

    function aiLaunchAcademyRestoreChecks(root, lessonNumber) {
        var saved = aiLaunchAcademyReadJson(aiLaunchAcademyChecksKey(lessonNumber), {});
        root.querySelectorAll('[data-academy-lesson-check]').forEach(function (check) {
            var key = check.getAttribute('data-academy-lesson-check') || '';
            check.checked = !!saved[key];
        });
    }

    function aiLaunchAcademySetLessonCheck(root, key, checked) {
        root.querySelectorAll('[data-academy-lesson-check]').forEach(function (check) {
            if ((check.getAttribute('data-academy-lesson-check') || '') === key) {
                check.checked = !!checked;
            }
        });
    }

    function aiLaunchAcademySaveChecks(root, lessonNumber) {
        var saved = {};
        var allChecked = true;
        root.querySelectorAll('[data-academy-lesson-check]').forEach(function (check) {
            var key = check.getAttribute('data-academy-lesson-check') || '';
            saved[key] = !!check.checked;
            if (!check.checked) {
                allChecked = false;
            }
        });
        aiLaunchAcademyWriteJson(aiLaunchAcademyChecksKey(lessonNumber), saved);
        aiLaunchAcademySetLessonComplete(lessonNumber, allChecked);
        aiLaunchAcademyUpdateProgress(root);
    }

    function aiLaunchAcademyLessonChecksComplete(root) {
        var checks = root.querySelectorAll('[data-academy-lesson-check]');
        if (!checks.length) {
            return false;
        }

        return Array.prototype.every.call(checks, function (check) {
            return !!check.checked;
        });
    }

    function aiLaunchAcademyUpdateCompleteButton(root) {
        var completeButton = root.querySelector('[data-academy-mark-complete]');
        if (!completeButton) {
            return;
        }

        completeButton.textContent = aiLaunchAcademyLessonChecksComplete(root) ? 'Lesson Complete' : 'Mark Lesson Complete';
    }

    function aiLaunchAcademyQuizState(lessonNumber) {
        return aiLaunchAcademyReadJson(aiLaunchAcademyQuizKey(lessonNumber), {});
    }

    function aiLaunchAcademyQuizAnswers(quizRoot) {
        var answers = {};
        var complete = true;
        var score = 0;
        var total = 0;

        quizRoot.querySelectorAll('[data-academy-quiz-question]').forEach(function (question) {
            var index = question.getAttribute('data-academy-quiz-question') || String(total);
            var correct = question.getAttribute('data-academy-quiz-correct') || '';
            var selected = question.querySelector('[data-academy-quiz-choice]:checked');
            total += 1;
            if (!selected) {
                complete = false;
                return;
            }
            answers[index] = selected.value;
            if (selected.value === correct) {
                score += 1;
            }
        });

        return {
            answers: answers,
            complete: complete,
            score: score,
            total: total
        };
    }

    function aiLaunchAcademyRenderQuiz(quizRoot, state) {
        var result = quizRoot.querySelector('[data-academy-quiz-result]');
        var submit = quizRoot.querySelector('[data-academy-quiz-submit]');
        var answers = state && state.answers ? state.answers : {};
        var completed = !!(state && state.completedAt);

        quizRoot.querySelectorAll('[data-academy-quiz-question]').forEach(function (question) {
            var index = question.getAttribute('data-academy-quiz-question') || '';
            var correct = question.getAttribute('data-academy-quiz-correct') || '';
            var saved = answers[index];
            question.classList.remove('is-correct', 'is-wrong');
            question.querySelectorAll('[data-academy-quiz-choice]').forEach(function (choice) {
                choice.checked = saved !== undefined && String(saved) === choice.value;
            });
            if (completed && saved !== undefined) {
                question.classList.add(String(saved) === correct ? 'is-correct' : 'is-wrong');
            }
        });

        if (result) {
            if (completed) {
                result.textContent = state.passed ? ('Passed: ' + state.score + '/' + state.total + '. Your quiz progress is saved in this browser.') : ('Score: ' + state.score + '/' + state.total + '. Review the readable answers and try again.');
            } else {
                result.textContent = 'Choose an answer for each question, then check your score.';
            }
        }
        if (submit) {
            submit.textContent = completed ? 'Check Quiz Again' : 'Check Quiz';
        }
    }

    function aiLaunchAcademyInitQuiz(root, quizRoot, lessonNumber) {
        var state = aiLaunchAcademyQuizState(lessonNumber);
        var passScore = parseInt(quizRoot.getAttribute('data-academy-quiz-pass-score') || '3', 10) || 3;
        aiLaunchAcademyRenderQuiz(quizRoot, state);

        quizRoot.querySelectorAll('[data-academy-quiz-choice]').forEach(function (choice) {
            choice.addEventListener('change', function () {
                var current = aiLaunchAcademyQuizState(lessonNumber);
                var answers = aiLaunchAcademyQuizAnswers(quizRoot).answers;
                var isNewStart = !current.startedAt;
                current.answers = answers;
                current.startedAt = current.startedAt || new Date().toISOString();
                aiLaunchAcademyWriteJson(aiLaunchAcademyQuizKey(lessonNumber), current);
                if (isNewStart) {
                    aiLaunchAcademyTrack(root, 'academy_quiz_started', 'Lesson ' + lessonNumber + ' quiz started', lessonNumber);
                }
            });
        });

        var submit = quizRoot.querySelector('[data-academy-quiz-submit]');
        if (submit) {
            submit.addEventListener('click', function () {
                var previous = aiLaunchAcademyQuizState(lessonNumber);
                var graded = aiLaunchAcademyQuizAnswers(quizRoot);
                var stateToSave = {
                    answers: graded.answers,
                    completedAt: new Date().toISOString(),
                    passed: graded.complete && graded.score >= passScore,
                    score: graded.score,
                    startedAt: previous.startedAt || new Date().toISOString(),
                    total: graded.total
                };
                aiLaunchAcademyWriteJson(aiLaunchAcademyQuizKey(lessonNumber), stateToSave);
                aiLaunchAcademyRenderQuiz(quizRoot, stateToSave);
                if (stateToSave.passed) {
                    aiLaunchAcademySetLessonCheck(root, 'quiz', true);
                    aiLaunchAcademySaveChecks(root, lessonNumber);
                    aiLaunchAcademyUpdateCompleteButton(root);
                }
                aiLaunchAcademyTrack(root, previous.completedAt ? 'academy_quiz_retaken' : 'academy_quiz_completed', 'Lesson ' + lessonNumber + ' quiz ' + stateToSave.score + '/' + stateToSave.total, lessonNumber);
            });
        }
    }

    function aiLaunchAcademyCapstoneState() {
        return aiLaunchAcademyReadJson(aiLaunchAcademyCapstoneKey(), {});
    }

    function aiLaunchAcademyRenderCapstone(root, state) {
        var checks = state && state.checks ? state.checks : {};
        root.querySelectorAll('[data-academy-capstone-check]').forEach(function (check) {
            var key = check.getAttribute('data-academy-capstone-check') || '';
            check.checked = !!checks[key];
        });
        var status = root.querySelector('[data-academy-capstone-status]');
        if (status) {
            status.textContent = state && state.completed ? 'Capstone complete. Certificate generation is unlocked when all quizzes and lessons are also complete.' : 'Complete each capstone check to unlock the certificate generator.';
        }
        var button = root.querySelector('[data-academy-mark-capstone]');
        if (button) {
            button.textContent = state && state.completed ? 'Capstone Complete' : 'Mark Capstone Complete';
        }
    }

    function aiLaunchAcademySaveCapstone(root) {
        var checks = {};
        var complete = true;
        root.querySelectorAll('[data-academy-capstone-check]').forEach(function (check) {
            var key = check.getAttribute('data-academy-capstone-check') || '';
            checks[key] = !!check.checked;
            if (!check.checked) {
                complete = false;
            }
        });
        var previous = aiLaunchAcademyCapstoneState();
        var state = {
            checks: checks,
            completed: complete,
            completedAt: complete ? (previous.completedAt || new Date().toISOString()) : ''
        };
        aiLaunchAcademyWriteJson(aiLaunchAcademyCapstoneKey(), state);
        aiLaunchAcademyRenderCapstone(root, state);
        if (complete && !previous.completed) {
            aiLaunchAcademyTrack(root, 'academy_capstone_completed', 'Academy capstone completed');
        }
    }

    function aiLaunchAcademyStats(total) {
        var completed = aiLaunchAcademyCompletedMap();
        var lessonsComplete = 0;
        var quizzesPassed = 0;
        var nextLesson = 0;
        for (var i = 1; i <= total; i += 1) {
            if (completed[String(i)]) {
                lessonsComplete += 1;
            } else if (!nextLesson) {
                nextLesson = i;
            }
            if (aiLaunchAcademyQuizState(i).passed) {
                quizzesPassed += 1;
            }
        }
        var capstone = aiLaunchAcademyCapstoneState();
        return {
            capstoneComplete: !!capstone.completed,
            certificateEligible: lessonsComplete >= total && quizzesPassed >= total && !!capstone.completed,
            lessonsComplete: lessonsComplete,
            nextLesson: nextLesson,
            quizzesPassed: quizzesPassed,
            total: total
        };
    }

    function aiLaunchAcademyUpdateDashboard(root) {
        var dashboard = root.querySelector('[data-academy-dashboard]');
        if (!dashboard) {
            return;
        }
        var total = aiLaunchAcademyTotal(root);
        var stats = aiLaunchAcademyStats(total);
        var lessons = dashboard.querySelector('[data-academy-dashboard-lessons]');
        var quizzes = dashboard.querySelector('[data-academy-dashboard-quizzes]');
        var capstone = dashboard.querySelector('[data-academy-dashboard-capstone]');
        var certificate = dashboard.querySelector('[data-academy-dashboard-certificate]');
        var progress = dashboard.querySelector('[data-academy-dashboard-progress]');
        var nextText = dashboard.querySelector('[data-academy-dashboard-next-text]');
        var nextLink = dashboard.querySelector('[data-academy-dashboard-next-link]');
        if (lessons) {
            lessons.textContent = String(stats.lessonsComplete);
        }
        if (quizzes) {
            quizzes.textContent = String(stats.quizzesPassed);
        }
        if (capstone) {
            capstone.textContent = stats.capstoneComplete ? 'Complete' : 'Not complete';
        }
        if (certificate) {
            certificate.textContent = stats.certificateEligible ? 'Unlocked' : 'Locked';
        }
        if (progress) {
            progress.max = total;
            progress.value = stats.lessonsComplete;
        }
        if (nextText) {
            if (stats.nextLesson) {
                nextText.textContent = 'Next step: continue Lesson ' + stats.nextLesson + '.';
            } else if (stats.quizzesPassed < total) {
                nextText.textContent = 'Next step: pass the remaining lesson quizzes.';
            } else if (!stats.capstoneComplete) {
                nextText.textContent = 'Next step: complete the capstone tracker.';
            } else {
                nextText.textContent = 'Certificate unlocked. Open the certification page to generate it.';
            }
        }
        if (nextLink) {
            if (stats.nextLesson) {
                var lessonLink = root.querySelector('a[href*="/lesson-' + stats.nextLesson + '-"]');
                if (lessonLink) {
                    nextLink.href = lessonLink.href;
                    nextLink.textContent = 'Continue Lesson ' + stats.nextLesson;
                }
            } else if (!stats.capstoneComplete) {
                nextLink.href = aiLaunchAcademyUrl(root, 'capstone');
                nextLink.textContent = 'Open Capstone';
            } else {
                nextLink.href = aiLaunchAcademyUrl(root, 'certification');
                nextLink.textContent = 'Generate Certificate';
            }
        }
    }

    function aiLaunchAcademySetRequirement(root, key, complete) {
        root.querySelectorAll('[data-academy-certificate-requirement]').forEach(function (item) {
            if ((item.getAttribute('data-academy-certificate-requirement') || '') === key) {
                item.classList.toggle('is-complete', !!complete);
            }
        });
    }

    function aiLaunchAcademyCertificateState() {
        return aiLaunchAcademyReadJson(aiLaunchAcademyCertificateKey(), {});
    }

    function aiLaunchAcademyRenderCertificate(root, state) {
        var stats = aiLaunchAcademyStats(aiLaunchAcademyTotal(root));
        var status = root.querySelector('[data-academy-certificate-status]');
        var nameInput = root.querySelector('[data-academy-certificate-name]');
        var generate = root.querySelector('[data-academy-certificate-generate]');
        var print = root.querySelector('[data-academy-certificate-print]');
        var preview = root.querySelector('[data-academy-certificate-preview]');
        var student = root.querySelector('[data-academy-certificate-student]');
        var date = root.querySelector('[data-academy-certificate-date]');
        var savedName = state && state.name ? String(state.name) : '';
        aiLaunchAcademySetRequirement(root, 'lessons', stats.lessonsComplete >= stats.total);
        aiLaunchAcademySetRequirement(root, 'quizzes', stats.quizzesPassed >= stats.total);
        aiLaunchAcademySetRequirement(root, 'capstone', stats.capstoneComplete);
        if (nameInput && savedName && nameInput.value.trim() === '') {
            nameInput.value = savedName;
        }
        if (student) {
            student.textContent = savedName || 'Your Name';
        }
        if (date && state && state.generatedDate) {
            date.textContent = state.generatedDate;
        }
        if (preview) {
            preview.classList.toggle('is-generated', !!(state && state.generatedAt));
        }
        if (generate) {
            generate.disabled = !stats.certificateEligible;
        }
        if (print) {
            print.disabled = !stats.certificateEligible || !(state && state.generatedAt);
        }
        if (status) {
            status.textContent = stats.certificateEligible ? 'Certificate unlocked. Enter your display name and generate a printable certificate.' : 'Locked: complete all lessons, pass all quizzes, and finish the capstone tracker.';
        }
    }

    function aiLaunchAcademyDownloadText(text, filename) {
        var blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename || 'ai-launch-academy-template.txt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () {
            URL.revokeObjectURL(url);
        }, 1000);
    }

    function initAiLaunchAcademy(root) {
        aiLaunchAcademyUpdateProgress(root);
        aiLaunchAcademyUpdateDashboard(root);
        var lessonNumber = parseInt(root.getAttribute('data-academy-lesson-number') || '0', 10) || 0;
        if (lessonNumber) {
            aiLaunchAcademyRestoreChecks(root, lessonNumber);
            aiLaunchAcademyUpdateCompleteButton(root);
            root.querySelectorAll('[data-academy-lesson-check]').forEach(function (check) {
                check.addEventListener('change', function () {
                    aiLaunchAcademySaveChecks(root, lessonNumber);
                    aiLaunchAcademyUpdateCompleteButton(root);
                });
            });
        }

        root.querySelectorAll('[data-academy-quiz]').forEach(function (quizRoot) {
            var quizLessonNumber = parseInt(quizRoot.getAttribute('data-academy-lesson-number') || String(lessonNumber), 10) || lessonNumber;
            if (quizLessonNumber) {
                aiLaunchAcademyInitQuiz(root, quizRoot, quizLessonNumber);
            }
        });

        if (root.querySelector('[data-academy-capstone]')) {
            aiLaunchAcademyRenderCapstone(root, aiLaunchAcademyCapstoneState());
            root.querySelectorAll('[data-academy-capstone-check]').forEach(function (check) {
                check.addEventListener('change', function () {
                    aiLaunchAcademySaveCapstone(root);
                });
            });
            var capstoneButton = root.querySelector('[data-academy-mark-capstone]');
            if (capstoneButton) {
                capstoneButton.addEventListener('click', function () {
                    root.querySelectorAll('[data-academy-capstone-check]').forEach(function (check) {
                        check.checked = true;
                    });
                    aiLaunchAcademySaveCapstone(root);
                });
            }
        }

        if (root.querySelector('[data-academy-certificate]')) {
            aiLaunchAcademyRenderCertificate(root, aiLaunchAcademyCertificateState());
            var generate = root.querySelector('[data-academy-certificate-generate]');
            var print = root.querySelector('[data-academy-certificate-print]');
            if (generate) {
                generate.addEventListener('click', function () {
                    var stats = aiLaunchAcademyStats(aiLaunchAcademyTotal(root));
                    var status = root.querySelector('[data-academy-certificate-status]');
                    if (!stats.certificateEligible) {
                        if (status) {
                            status.textContent = 'Finish all requirements before generating the certificate.';
                        }
                        return;
                    }
                    var input = root.querySelector('[data-academy-certificate-name]');
                    var name = input ? input.value.trim().slice(0, 80) : '';
                    var state = {
                        generatedAt: new Date().toISOString(),
                        generatedDate: new Date().toLocaleDateString(),
                        name: name || 'AI Launch Analyst'
                    };
                    aiLaunchAcademyWriteJson(aiLaunchAcademyCertificateKey(), state);
                    aiLaunchAcademyRenderCertificate(root, state);
                    aiLaunchAcademyTrack(root, 'academy_certificate_generated', 'Academy certificate generated');
                });
            }
            if (print) {
                print.addEventListener('click', function () {
                    var state = aiLaunchAcademyCertificateState();
                    if (!state.generatedAt) {
                        return;
                    }
                    aiLaunchAcademyTrack(root, 'academy_certificate_printed', 'Academy certificate printed');
                    window.print();
                });
            }
        }

        if (root.querySelector('[data-academy-dashboard]')) {
            aiLaunchAcademyTrack(root, 'academy_dashboard_viewed', 'Academy dashboard viewed');
        }

        root.addEventListener('click', function (event) {
            var copyButton = event.target.closest('[data-academy-copy-target]');
            if (copyButton && root.contains(copyButton)) {
                var source = root.querySelector(copyButton.getAttribute('data-academy-copy-target') || '');
                copyPlainText(source ? source.textContent : '', copyButton);
                aiLaunchAcademyTrack(root, 'academy_template_copied', copyButton.getAttribute('data-academy-template-title') || 'Academy template copied');
                return;
            }

            var downloadButton = event.target.closest('[data-academy-download-target]');
            if (downloadButton && root.contains(downloadButton)) {
                var downloadSource = root.querySelector(downloadButton.getAttribute('data-academy-download-target') || '');
                var downloadText = downloadSource ? downloadSource.textContent : '';
                if (downloadText) {
                    aiLaunchAcademyDownloadText(downloadText, downloadButton.getAttribute('data-academy-download-filename') || 'ai-launch-academy-template.txt');
                    var oldText = downloadButton.textContent;
                    downloadButton.textContent = 'Downloaded';
                    setTimeout(function () {
                        downloadButton.textContent = oldText || 'Download TXT';
                    }, 1500);
                    aiLaunchAcademyTrack(root, 'academy_template_downloaded', downloadButton.getAttribute('data-academy-template-title') || 'Academy template downloaded');
                }
                return;
            }

            var completeButton = event.target.closest('[data-academy-mark-complete]');
            if (completeButton && root.contains(completeButton) && lessonNumber) {
                root.querySelectorAll('[data-academy-lesson-check]').forEach(function (check) {
                    check.checked = true;
                });
                aiLaunchAcademySaveChecks(root, lessonNumber);
                aiLaunchAcademyUpdateCompleteButton(root);
            }
        });
    }

    document.addEventListener('click', trackClick);
    document.querySelectorAll('[data-kingy-ali-calculator]').forEach(function (calculator) {
        var debouncedUpdate = debounce(function () {
            updateCalculator(calculator);
        }, 350);
        calculator.addEventListener('change', function () {
            debouncedUpdate();
        });
    });
    document.querySelectorAll('[data-kingy-ali-creator-campaign-roi], [data-kingy-ali-sponsor-roi]').forEach(function (calculator) {
        var debouncedUpdate = debounce(function () {
            updateCreatorCampaignRoi(calculator);
        }, 150);
        creatorCampaignRoiHydrateFromUrl(calculator);
        updateCreatorCampaignRoi(calculator);
        calculator.addEventListener('input', function () {
            debouncedUpdate();
        });
        calculator.addEventListener('change', function () {
            debouncedUpdate();
        });
        calculator.querySelectorAll('[data-creator-campaign-roi-preset]').forEach(function (preset) {
            preset.addEventListener('click', function () {
                creatorCampaignRoiApplyPreset(calculator, preset);
            });
        });
        var shareButton = calculator.querySelector('[data-creator-campaign-roi-share]');
        if (shareButton) {
            shareButton.addEventListener('click', function () {
                creatorCampaignRoiShare(calculator);
            });
        }
        var csvButton = calculator.querySelector('[data-creator-campaign-roi-csv]');
        if (csvButton) {
            csvButton.addEventListener('click', function () {
                creatorCampaignRoiCsv(calculator);
            });
        }
        var sendButton = calculator.querySelector('[data-creator-campaign-roi-send]');
        if (sendButton) {
            sendButton.addEventListener('click', function () {
                var leadPanel = calculator.querySelector('.kingy-ali-lead-panel');
                if (leadPanel) {
                    leadPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                setCreatorCampaignRoiStatus(calculator, 'Add your contact details below to send the estimate.');
            });
        }
    });
    document.querySelectorAll('[data-kingy-codex-prompt-builder]').forEach(initCodexBuilder);
    document.querySelectorAll('[data-kingy-codex-article-tools]').forEach(initCodexArticleTools);
    document.querySelectorAll('[data-kingy-app-builder-comparison]').forEach(initAppBuilderComparison);
    document.querySelectorAll('[data-kingy-vibe-guide]').forEach(initVibeGuide);
    document.querySelectorAll('[data-kingy-lead-magnet-guide]').forEach(initLeadMagnetGuide);
    document.querySelectorAll('[data-kingy-landing-guide]').forEach(initLandingGuide);
    document.querySelectorAll('[data-kingy-safe-agent-guide]').forEach(initSafeAgentGuide);
    document.querySelectorAll('[data-kingy-html-safety-guide]').forEach(initCustomHtmlSafetyGuide);
    document.querySelectorAll('[data-kingy-website-qa-guide]').forEach(initWebsiteQaGuide);
    document.querySelectorAll('[data-kingy-seo-qa-guide]').forEach(initSeoQaGuide);
    document.querySelectorAll('[data-kingy-copilot-course]').forEach(initCopilotCourse);
    document.querySelectorAll('[data-kingy-security-review-guide]').forEach(initSecurityReviewGuide);
    document.querySelectorAll('[data-kingy-agent-skills-worksheet]').forEach(initAgentSkillsWorksheet);
    document.querySelectorAll('[data-kingy-ai-launch-scorecard]').forEach(initAiLaunchScorecard);
    document.querySelectorAll('[data-kingy-ai-launch-academy]').forEach(initAiLaunchAcademy);
})();
