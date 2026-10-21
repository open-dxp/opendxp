/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

/**
 * @private
 */
opendxp.registerNS('opendxp.bundle.search.element.service');

opendxp.bundle.search.element.service = Class.create({
    initialize: function () {
        this.initKeyMap();
        this.createQuickSearch();
    },

    openItemSelector: function (multiselect, callback, restrictions, config) {
        new opendxp.bundle.search.element.selector.selector(multiselect, callback, restrictions, config);
    },

    initKeyMap: function () {
        new Ext.util.KeyMap({
            target: document,
            binding: [{
                key:  Ext.event.Event.ESC,
                fn: function () {
                    this.hideQuickSearch();
                }.bind(this)
            }, {
                key: Ext.event.Event.SPACE,
                ctrl: true,
                fn: function (keyCode, e) {
                    e.stopEvent();
                    this.showQuickSearch();
                }.bind(this)
            }]
        });
    },

    getStore: function () {
        return new Ext.data.Store({
            proxy: {
                type: 'ajax',
                url: Routing.generate('opendxp_bundle_search_search_quicksearch'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                }
            },
            listeners: {
                "beforeload": function (store) {
                    var previewEl = Ext.get('opendxp_quicksearch_preview');
                    if(previewEl) {
                        previewEl.setHtml('');
                    }

                    store.getProxy().abort();
                }
            },
            fields: ["id", 'type', "subtype", "className", "fullpath"]
        });
    },

    getTemplate: function () {
        return new Ext.XTemplate(
            '<tpl for=".">',
            '<li role="option" unselectable="on" class="x-boundlist-item">' +
            '<div class="list-icon {iconCls}"><tpl if="icon"><img class="class-icon" src="{icon}"></tpl></div>' +
            '<div class="list-path" title="{fullpath}">{fullpathList}</div>' +
            '</li>',
            '</tpl>'
        );
    },

    createQuickSearch: function () {
        const quickSearchStore = this.getStore();
        const quickSearchTpl = this.getTemplate();

        const quickSearchContainer = Ext.get('opendxp_quicksearch');
        const quickSearchCombo = Ext.create('Ext.form.ComboBox', {
            width: 900,
            hideTrigger: true,
            border: false,
            shadow: false,
            tpl: quickSearchTpl,
            listConfig: {
                shadow: false,
                border: false,
                cls: 'opendxp_quicksearch_picker',
                listeners: {
                    "highlightitem": function (view, node, opts) {
                        const record = quickSearchStore.getAt(node.dataset.recordindex);
                        if (!record.get('preview')) {
                            Ext.Ajax.request({
                                url: Routing.generate('opendxp_bundle_search_search_quicksearch_by_id'),
                                method: 'GET',
                                params: {
                                    "id": record.get('id'),
                                    "type": record.get('type')
                                },
                                success: function (response) {
                                    const result = Ext.decode(response.responseText);

                                    record.preview = result.preview;
                                    Ext.get('opendxp_quicksearch_preview').setHtml(result.preview);
                                },
                                failure: function () {
                                    const previewHtml = '<div class="no_preview">' + t('preview_not_available') + '</div>';

                                    Ext.get('opendxp_quicksearch_preview').setHtml(previewHtml);
                                }
                            });
                        } else {
                            let previewHtml = record.get('preview');
                            if(!previewHtml) {
                                previewHtml = '<div class="no_preview">' + t('preview_not_available') + '</div>';
                            }

                            Ext.get('opendxp_quicksearch_preview').setHtml(previewHtml);
                        }
                    }
                }
            },
            id: 'quickSearchCombo',
            store: quickSearchStore,
            loadingText: t('searching'),
            queryDelay: 100,
            minChars: 4,
            renderTo: quickSearchContainer,
            enableKeyEvents: true,
            displayField: 'fullpath',
            valueField: "id",
            typeAhead: true,
            listeners: {
                "expand": function (combo) {
                    if(!document.getElementById('opendxp_quicksearch_preview')) {
                        combo.getPicker().getEl().insertHtml('beforeEnd', '<div id="opendxp_quicksearch_preview"></div>');
                    }
                },
                "keyup": function (field) {
                    if(field.getValue()) {
                        quickSearchContainer.addCls('filled');
                    }
                },
                "select": function (combo, record, index) {
                    opendxp.helpers.openElement(record.get('id'), record.get('type'), record.get('subtype'));
                    this.hideQuickSearch();
                }.bind(this)
            }
        });

        Ext.getBody().on('click', function (event) {
            // hide on click outside
            if(quickSearchContainer && !quickSearchContainer.isAncestor(event.target)) {
                const pickerEl = quickSearchCombo.getPicker().getEl();
                if(!pickerEl || !pickerEl.isAncestor(event.target)) {
                    this.hideQuickSearch();
                }
            }
        }.bind(this));
    },

    showQuickSearch: function () {
        // close all windows, tooltips and previews
        // we use each() because .hideAll() doesn't hide the modal (seems to be an ExtJS bug)
        Ext.WindowManager.each(function (win) {
            win.close();
        });
        opendxp.helpers.treeNodeThumbnailPreviewHide();
        opendxp.helpers.treeToolTipHide();

        const quickSearchContainer = Ext.get('opendxp_quicksearch');
        quickSearchContainer.show();
        quickSearchContainer.removeCls('filled');

        const combo = Ext.getCmp('quickSearchCombo');
        combo.reset();
        combo.focus();

        Ext.get('opendxp_body').addCls('blurry');
        Ext.get('opendxp_sidebar').addCls('blurry');
        const elem = document.createElement('div');
        elem.id = 'opendxp_quickSearch_overlay';
        elem.style.cssText = 'position:absolute;width:100vw;height:100vh;z-index:100;top:0;left:0;opacity:0';
        elem.addEventListener('click', function(e) {
            document.body.removeChild(elem);
            this.hideQuickSearch();
        }.bind(this));
        document.body.appendChild(elem);
    },

    hideQuickSearch: function () {
        const quickSearchContainer = Ext.get('opendxp_quicksearch');
        quickSearchContainer.hide();
        Ext.get('opendxp_body').removeCls('blurry');
        Ext.get('opendxp_sidebar').removeCls('blurry');
        if (Ext.get('opendxp_quickSearch_overlay')) {
            Ext.get('opendxp_quickSearch_overlay').remove();
        }
    },

    getObjectRelationInlineSearchRoute: function () {
        return Routing.generate('opendxp_bundle_search_dataobject_relation_objects_list');
    }
});