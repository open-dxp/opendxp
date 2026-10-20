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

opendxp.registerNS('opendxp.bundle.seo.siteDeclarations');
/**
 * @private
 */
opendxp.bundle.seo.siteDeclarations = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        Ext.getCmp('opendxp_panel_tabs').setActiveItem('opendxp_site_declarations');
    },

    getTabPanel: function () {
        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: 'opendxp_site_declarations',
                title: t('site_declarations'),
                iconCls: 'opendxp_icon_robots',
                border: false,
                layout: 'border',
                closable: true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp('opendxp_panel_tabs');
            tabPanel.add(this.panel);
            tabPanel.setActiveItem('opendxp_site_declarations');

            this.panel.on('destroy', function () {
                opendxp.globalmanager.remove('bundle_seo_site_declarations');
            });

            opendxp.layout.refresh();
        }

        return this.panel;
    },

    getTree: function () {
        if (!this.tree) {
            var children = opendxp.settings.site_declarations.map(function (declaration) {
                return {
                    id: declaration.name,
                    text: Ext.util.Format.htmlEncode(declaration.path.split('/').pop()),
                    path: declaration.path,
                    iconCls: 'opendxp_icon_robots',
                    leaf: true
                };
            });

            this.tree = Ext.create('Ext.tree.Panel', {
                region: 'west',
                width: 250,
                split: true,
                autoScroll: true,
                rootVisible: false,
                root: {
                    expanded: true,
                    children: children
                },
                listeners: {
                    itemclick: this.openDeclaration.bind(this)
                }
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: 'center'
            });
        }

        return this.editPanel;
    },

    openDeclaration: function (tree, record) {
        var tabId = 'opendxp_site_declaration_' + record.getId();
        var tab = Ext.getCmp(tabId);

        if (!tab) {
            tab = new opendxp.bundle.seo.siteDeclaration(
                record.getId(),
                record.get('text'),
                record.get('path')
            ).getPanel(tabId);
            this.editPanel.add(tab);
        }

        this.editPanel.setActiveTab(tab);
    }
});
