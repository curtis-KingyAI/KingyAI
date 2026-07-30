(function (blocks, element, components, blockEditor, i18n) {
    'use strict';

    if (!blocks || !element || !components || !blockEditor || !i18n) {
        return;
    }

    var el = element.createElement;
    var __ = i18n.__;
    var definitions = [
        ['kingy-ai-launch-intelligence/kali-tool-facts', __('KALI tool facts', 'kingy-ai-launch-intelligence')],
        ['kingy-ai-launch-intelligence/kali-tool-pricing', __('KALI tool pricing', 'kingy-ai-launch-intelligence')],
        ['kingy-ai-launch-intelligence/kali-tool-features', __('KALI tool features', 'kingy-ai-launch-intelligence')],
        ['kingy-ai-launch-intelligence/kali-tool-verification', __('KALI verification', 'kingy-ai-launch-intelligence')],
        ['kingy-ai-launch-intelligence/kali-tool-sources', __('KALI tool sources', 'kingy-ai-launch-intelligence')],
        ['kingy-ai-launch-intelligence/kali-tool-launch-history', __('KALI tool launch history', 'kingy-ai-launch-intelligence')]
    ];

    definitions.forEach(function (definition) {
        blocks.registerBlockType(definition[0], {
            apiVersion: 2,
            title: definition[1],
            category: 'widgets',
            icon: 'database',
            attributes: {
                tool: { type: 'string', default: '' },
                mode: { type: 'string', default: 'live' },
                asOf: { type: 'string', default: '' },
                limit: { type: 'number', default: 12 }
            },
            edit: function (props) {
                var attributes = props.attributes;
                var controls = el(
                    blockEditor.InspectorControls,
                    {},
                    el(
                        components.PanelBody,
                        { title: __('KALI module settings', 'kingy-ai-launch-intelligence'), initialOpen: true },
                        el(components.TextControl, {
                            label: __('Tool ID or slug', 'kingy-ai-launch-intelligence'),
                            value: attributes.tool,
                            onChange: function (value) { props.setAttributes({ tool: value }); }
                        }),
                        el(components.SelectControl, {
                            label: __('Mode', 'kingy-ai-launch-intelligence'),
                            value: attributes.mode,
                            options: [
                                { label: __('Live', 'kingy-ai-launch-intelligence'), value: 'live' },
                                { label: __('Snapshot', 'kingy-ai-launch-intelligence'), value: 'snapshot' }
                            ],
                            onChange: function (value) { props.setAttributes({ mode: value }); }
                        }),
                        el(components.TextControl, {
                            label: __('As of (YYYY-MM-DD)', 'kingy-ai-launch-intelligence'),
                            value: attributes.asOf,
                            onChange: function (value) { props.setAttributes({ asOf: value }); }
                        })
                    )
                );

                return el(
                    element.Fragment,
                    {},
                    controls,
                    el(
                        components.Placeholder,
                        { icon: 'database', label: definition[1] },
                        el('p', {}, attributes.tool
                            ? __('Tool: ', 'kingy-ai-launch-intelligence') + attributes.tool
                            : __('Choose a published tool by ID or slug.', 'kingy-ai-launch-intelligence')),
                        el('p', {}, __('Snapshot/as-of requests fail closed unless verified historical data exists.', 'kingy-ai-launch-intelligence'))
                    )
                );
            },
            save: function () {
                return null;
            }
        });
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n);
