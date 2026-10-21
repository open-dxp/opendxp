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

opendxp.registerNS('opendxp.bundle.seo.siteDeclaration');
/**
 * @private
 */
opendxp.bundle.seo.siteDeclaration = Class.create({

    initialize: function (name, label, path) {
        this.name = name;
        this.label = label;
        this.path = path;
        this.editors = {};
        this.sites = [];
    },

    getPanel: function (id) {
        this.panel = new Ext.Panel({
            id: id,
            title: this.label,
            iconCls: 'opendxp_icon_robots',
            layout: 'fit',
            closable: true,
            items: []
        });

        this.panel.on('afterrender', this.load.bind(this));

        return this.panel;
    },

    load: function () {
        this.panel.setLoading(true);

        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_seo_settings_sitedeclaration', {name: this.name}),
            success: function (response) {
                var data = Ext.decode(response.responseText);
                this.sites = data.sites;
                this.editorMode = data.contentType.indexOf('application/json') === 0 ? 'ace/mode/json' : 'ace/mode/text';
                this.panel.add(this.getForm(data));
                this.panel.setLoading(false);
            }.bind(this)
        });
    },

    getForm: function (data) {
        var buttons = [];

        if (data.onFileSystem) {
            buttons.push(t('site_declaration_exists_on_filesystem'));
        }

        buttons.push({
            text: t('save'),
            iconCls: 'opendxp_icon_apply',
            disabled: data.onFileSystem,
            handler: this.save.bind(this)
        });

        this.siteTabs = new Ext.TabPanel({
            items: this.sites.map(function (site) {
                return this.getEditor(site, data.data[site.id] || '');
            }.bind(this))
        });

        return new Ext.Panel({
            layout: 'fit',
            tbar: this.getToolbar(data.placeholders),
            items: [this.siteTabs],
            buttons: buttons
        });
    },

    getToolbar: function (placeholders) {
        var items = [{
            xtype: 'tbtext',
            text: Ext.util.Format.htmlEncode(this.path)
        }];

        if (placeholders.length > 0) {
            items.push('-');
        }

        placeholders.forEach(function (placeholder) {
            var text = '%' + placeholder.name + '%';

            items.push({
                text: text,
                iconCls: 'opendxp_icon_add',
                tooltip: Ext.util.Format.htmlEncode(t(placeholder.tooltip) + ': ' + placeholder.value),
                handler: function () {
                    this.editors[this.siteTabs.getActiveTab().siteId].insert(text);
                }.bind(this)
            });
        }.bind(this));

        return items;
    },

    getEditor: function (site, content) {
        var editorId = 'site_declaration_editor_' + this.name + '_' + site.id;

        var tab = new Ext.Panel({
            title: Ext.util.Format.htmlEncode(site.domain),
            layout: 'fit',
            bodyStyle: 'padding: 10px;',
            items: [{
                xtype: 'component',
                html: '<div id="' + editorId + '" style="height:100%;width:100%"></div>',
                listeners: {
                    afterrender: function () {
                        var editor = ace.edit(editorId);
                        editor.setTheme('ace/theme/chrome');
                        editor.getSession().setMode(this.editorMode);
                        editor.setOptions({
                            showLineNumbers: true,
                            showPrintMargin: false,
                            wrap: true,
                            fontFamily: 'Courier New, Courier, monospace;'
                        });
                        editor.setValue(content);
                        editor.clearSelection();
                        this.editors[site.id] = editor;
                    }.bind(this)
                }
            }]
        });

        tab.siteId = site.id;
        tab.on('resize', function () {
            if (this.editors[site.id]) {
                this.editors[site.id].resize();
            }
        }.bind(this));

        return tab;
    },

    save: function () {
        var params = {};
        Ext.Object.each(this.editors, function (siteId, editor) {
            params['data[' + siteId + ']'] = editor.getValue();
        });

        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_seo_settings_sitedeclaration_update', {name: this.name}),
            method: 'PUT',
            params: params,
            success: function (response) {
                var data = Ext.decode(response.responseText);

                if (data.success) {
                    opendxp.helpers.showNotification(t('success'), t('saved_successfully'), 'success');

                    return;
                }

                opendxp.helpers.showNotification(t('error'), this.getErrorMessage(data.errors), 'error');
            }.bind(this)
        });
    },

    getErrorMessage: function (errors) {
        var messages = [];

        this.sites.forEach(function (site) {
            (errors[site.id] || []).forEach(function (error) {
                messages.push(Ext.util.Format.htmlEncode(site.domain + ': ' + t(error)));
            });
        });

        return messages.join('<br>');
    }
});
