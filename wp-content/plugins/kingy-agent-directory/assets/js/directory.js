(function () {
    "use strict";

    var app = document.querySelector("[data-kad-app]");
    if (!app) {
        return;
    }

    function asArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function initTabs() {
        var tabs = asArray(app.querySelectorAll("[data-kad-tab]"));
        var panels = asArray(app.querySelectorAll("[data-kad-panel]"));
        if (!tabs.length || !panels.length) {
            return;
        }

        function activate(name, moveFocus, updateHash) {
            tabs.forEach(function (tab) {
                var selected = tab.getAttribute("data-kad-tab") === name;
                tab.setAttribute("aria-selected", selected ? "true" : "false");
                tab.setAttribute("tabindex", selected ? "0" : "-1");
                if (selected && moveFocus) {
                    tab.focus();
                }
            });

            panels.forEach(function (panel) {
                panel.hidden = panel.getAttribute("data-kad-panel") !== name;
            });

            if (updateHash && window.history && window.history.replaceState) {
                window.history.replaceState(null, "", "#kad-" + name);
            }
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener("click", function () {
                activate(tab.getAttribute("data-kad-tab"), false, true);
            });

            tab.addEventListener("keydown", function (event) {
                var nextIndex = index;
                if (event.key === "ArrowRight") {
                    nextIndex = (index + 1) % tabs.length;
                } else if (event.key === "ArrowLeft") {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === "Home") {
                    nextIndex = 0;
                } else if (event.key === "End") {
                    nextIndex = tabs.length - 1;
                } else {
                    return;
                }

                event.preventDefault();
                activate(tabs[nextIndex].getAttribute("data-kad-tab"), true, true);
            });
        });

        var requested = window.location.hash.replace(/^#kad-/, "");
        var available = tabs.some(function (tab) {
            return tab.getAttribute("data-kad-tab") === requested;
        });
        activate(available ? requested : "directory", false, false);
    }

    function initFilters() {
        var grid = app.querySelector("[data-kad-grid]");
        if (!grid) {
            return;
        }

        var cards = asArray(grid.querySelectorAll("[data-agent-card]"));
        var search = app.querySelector("[data-kad-search]");
        var category = app.querySelector("[data-kad-category]");
        var audience = app.querySelector("[data-kad-audience]");
        var sort = app.querySelector("[data-kad-sort]");
        var reset = app.querySelector("[data-kad-reset]");
        var count = app.querySelector("[data-kad-count]");
        var empty = app.querySelector("[data-kad-empty]");
        var pagination = app.querySelector("[data-kad-pagination]");
        var previous = app.querySelector("[data-page-previous]");
        var next = app.querySelector("[data-page-next]");
        var pageStatus = app.querySelector("[data-kad-page-status]");
        var pageSize = 6;
        var currentPage = 1;

        function includesToken(haystack, needle) {
            if (!needle) {
                return true;
            }
            return ("|" + haystack + "|").indexOf("|" + needle + "|") !== -1;
        }

        function update(resetPage) {
            var query = search ? search.value.trim().toLowerCase() : "";
            var selectedCategory = category ? category.value : "";
            var selectedAudience = audience ? audience.value : "";
            var direction = sort ? sort.value : "az";
            var matching = [];

            if (resetPage) {
                currentPage = 1;
            }

            cards.sort(function (left, right) {
                var leftName = left.getAttribute("data-agent-name") || "";
                var rightName = right.getAttribute("data-agent-name") || "";
                var comparison = leftName.localeCompare(rightName, undefined, { sensitivity: "base" });
                return direction === "za" ? -comparison : comparison;
            }).forEach(function (card) {
                grid.appendChild(card);
                var text = card.getAttribute("data-agent-search") || "";
                var categories = card.getAttribute("data-agent-categories") || "";
                var audiences = card.getAttribute("data-agent-audiences") || "";
                var matches = (!query || text.indexOf(query) !== -1) &&
                    includesToken(categories, selectedCategory) &&
                    includesToken(audiences, selectedAudience);
                if (matches) {
                    matching.push(card);
                }
            });

            var totalPages = Math.max(1, Math.ceil(matching.length / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            var start = (currentPage - 1) * pageSize;
            var end = Math.min(start + pageSize, matching.length);

            cards.forEach(function (card) {
                card.hidden = true;
            });
            matching.slice(start, end).forEach(function (card) {
                card.hidden = false;
            });

            if (count) {
                var qualifier = query || selectedCategory || selectedAudience ? "matching" : "verified";
                count.textContent = "Showing " + (matching.length ? (end - start) : 0) + " of " + matching.length + " " + qualifier + " AI " + (matching.length === 1 ? "agent" : "agents");
            }
            if (empty) {
                empty.hidden = matching.length !== 0;
            }
            if (pagination) {
                pagination.hidden = matching.length <= pageSize;
            }
            if (previous) {
                previous.disabled = currentPage <= 1;
            }
            if (next) {
                next.disabled = currentPage >= totalPages;
            }
            if (pageStatus) {
                pageStatus.textContent = matching.length ? "Page " + currentPage + " of " + totalPages : "No result pages";
            }
        }

        [search, category, audience, sort].forEach(function (control) {
            if (!control) {
                return;
            }
            control.addEventListener(control === search ? "input" : "change", function () {
                update(true);
            });
        });

        if (reset) {
            reset.addEventListener("click", function () {
                if (search) { search.value = ""; }
                if (category) { category.value = ""; }
                if (audience) { audience.value = ""; }
                if (sort) { sort.value = "az"; }
                update(true);
                if (search) { search.focus(); }
            });
        }

        if (previous) {
            previous.addEventListener("click", function () {
                if (currentPage > 1) {
                    currentPage -= 1;
                    update(false);
                    grid.scrollIntoView({ block: "start" });
                }
            });
        }

        if (next) {
            next.addEventListener("click", function () {
                currentPage += 1;
                update(false);
                grid.scrollIntoView({ block: "start" });
            });
        }

        update(true);
    }

    function initScorecard() {
        var form = app.querySelector("[data-kad-scorecard]");
        var result = app.querySelector("[data-kad-score-result]");
        if (!form || !result) {
            return;
        }

        var groupLabels = {
            clarity: "task clarity",
            repeatability: "repeatability",
            context: "context and data",
            tools: "tools and permissions",
            risk: "risk and approval",
            value: "business value"
        };

        var recommendations = {
            clarity: "Write a one-sentence task definition, example outputs, and measurable acceptance criteria before selecting a tool.",
            repeatability: "Start with a narrower task that happens often enough to measure and has a stable sequence of steps.",
            context: "Identify the source of truth, remove stale inputs, and define exactly what context the agent may access.",
            tools: "Confirm supported integrations and begin with limited, revocable permissions in a sandbox or test workspace.",
            risk: "Add a human approval gate before any external, destructive, financial, security-sensitive, or customer-facing action.",
            value: "Name an accountable owner and define the time, cost, revenue, or quality measure the pilot should improve."
        };

        function resetResult() {
            result.textContent = "";
            var kicker = document.createElement("p");
            kicker.className = "kad-result-kicker";
            kicker.textContent = "Your readiness report";
            var title = document.createElement("h3");
            title.textContent = "Complete the questions to calculate a score.";
            var copy = document.createElement("p");
            copy.textContent = "The score is a planning aid, not a guarantee of product performance or safety.";
            result.appendChild(kicker);
            result.appendChild(title);
            result.appendChild(copy);
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();
            if (!form.reportValidity()) {
                return;
            }

            var fields = asArray(form.querySelectorAll("[data-score-group]"));
            var groups = {};
            var earned = 0;
            var possible = 0;

            fields.forEach(function (field) {
                var group = field.getAttribute("data-score-group");
                var value = Number(field.value);
                if (!groups[group]) {
                    groups[group] = { earned: 0, possible: 0 };
                }
                groups[group].earned += value;
                groups[group].possible += 2;
                earned += value;
                possible += 2;
            });

            var score = Math.round((earned / possible) * 100);
            var weakest = Object.keys(groups).sort(function (left, right) {
                return (groups[left].earned / groups[left].possible) - (groups[right].earned / groups[right].possible);
            })[0];
            var band = score >= 80 ? "Ready for a bounded pilot" :
                score >= 60 ? "Prepare, then pilot carefully" :
                    score >= 40 ? "Fix the main blockers first" : "Not ready for an agent pilot";

            result.textContent = "";
            var kicker = document.createElement("p");
            kicker.className = "kad-result-kicker";
            kicker.textContent = score + "/100 readiness score";
            var title = document.createElement("h3");
            title.textContent = band;
            var summary = document.createElement("p");
            summary.textContent = "The weakest readiness area is " + groupLabels[weakest] + ".";
            var list = document.createElement("ul");
            var first = document.createElement("li");
            first.textContent = recommendations[weakest];
            var second = document.createElement("li");
            second.textContent = "Run the first pilot with a small data set, a named owner, a rollback plan, and human approval before important actions.";
            var third = document.createElement("li");
            third.textContent = "Measure one outcome for 30 days before expanding permissions, scope, or spend.";
            list.appendChild(first);
            list.appendChild(second);
            list.appendChild(third);
            result.appendChild(kicker);
            result.appendChild(title);
            result.appendChild(summary);
            result.appendChild(list);
            result.focus({ preventScroll: true });
        });

        form.addEventListener("reset", function () {
            window.setTimeout(resetResult, 0);
        });
    }

    initTabs();
    initFilters();
    initScorecard();
}());
