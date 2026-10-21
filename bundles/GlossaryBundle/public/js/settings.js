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

opendxp.registerNS("opendxp.bundle.glossary.settings");
/**
 * @private
 */
opendxp.bundle.glossary.settings = Class.create({

    initialize: function () {
        this.languages = opendxp.settings.websiteLanguages;
        this.languages.splice(0,0,"");
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("opendxp_panel_tabs");
        tabPanel.setActiveItem("opendxp_glossary");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "opendxp_glossary",
                iconCls: "opendxp_icon_glossary",
                title: t("glossary"),
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("opendxp_glossary");


            this.panel.on("destroy", function () {
                opendxp.globalmanager.remove("bundle_glossary");
            }.bind(this));

            opendxp.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {

        var itemsPerPage = opendxp.helpers.grid.getDefaultPageSize();
        this.store = opendxp.helpers.grid.buildDefaultStore(
            Routing.generate('opendxp_bundle_glossary_settings_glossary'),
            [
                'id', {name: 'text', allowBlank: false}, 'language', 'casesensitive', 'exactmatch',
                'site', 'link', 'abbr', 'creationDate', 'modificationDate'
            ],
            itemsPerPage
        );

        this.filterField = Ext.create("Ext.form.TextField", {
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.store.load();
                    }
                }.bind(this)
            }
        });

        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store);

        var casesensitiveCheck = new Ext.grid.column.Check({
            text: t("casesensitive"),
            dataIndex: "casesensitive",
            flex: 55,
            editor: {
                xtype: 'checkbox',
            }
        });

        var exactmatchCheck = new Ext.grid.column.Check({
            text: t("exactmatch"),
            dataIndex: "exactmatch",
            flex: 50,
            editor: {
                xtype: 'checkbox',
            }
        });

        var typesColumns = [
            {text: t("text"), flex: 200, sortable: true, dataIndex: 'text', editor: new Ext.form.TextField({})},
            {text: t("link"), flex: 200, sortable: true, dataIndex: 'link',
                editor: {
                    xtype: 'textfield',
                    id: 'linkEditor',
                    fieldCls: "input_drop_target",
                },
                tdCls: "opendxp_droptarget_input"
            },
            {text: t("abbr"), flex: 200, sortable: true, dataIndex: 'abbr', editor: new Ext.form.TextField({})},
            {text: t("language"), flex: 50, sortable: true, dataIndex: 'language', editor: new Ext.form.ComboBox({
                    store: this.languages,
                    mode: "local",
                    editable: false,
                    triggerAction: "all"
                })},
            casesensitiveCheck,
            exactmatchCheck,
            {text: t("site"), flex: 200, sortable:true, dataIndex: "site", editor: new Ext.form.ComboBox({
                    store: opendxp.globalmanager.get("sites"),
                    valueField: "id",
                    displayField: "domain",
                    editable: false,
                    triggerAction: "all"
                }), renderer: function (siteId) {
                    var store = opendxp.globalmanager.get("sites");
                    var pos = store.findExact("id", siteId);
                    if(pos >= 0) {
                        return store.getAt(pos).get("domain");
                    }
                }},
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false,
                hidden: true,
                renderer: function(d) {
                    if (d !== undefined) {
                        var date = new Date(d * 1000);
                        return Ext.Date.format(date, "Y-m-d H:i:s");
                    } else {
                        return "";
                    }
                }
            },
            {
                xtype: 'actioncolumn',
                menuText: t('delete'),
                width: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/opendxpadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        let data = grid.getStore().getAt(rowIndex);
                        opendxp.helpers.deleteConfirm(t('glossary'), data.data.id, function () {
                            grid.getStore().removeAt(rowIndex);
                            this.updateRows();
                        }.bind(this));

                    }.bind(this)
                }]
            }
        ];

        this.rowEditing = Ext.create('Ext.grid.plugin.RowEditing', {
            clicksToEdit: 1,
            clicksToMoveEditor: 1,
            listeners: {
                beforeedit: function(el, e, eOpts, i) {
                    var editorRow = el.editor.body;
                    editorRow.rowIdx = e.rowIdx;
                    let dd = new Ext.dd.DropZone(editorRow, {
                        ddGroup: "element",

                        getTargetFromEvent: function(e) {
                            return this.getEl();
                        },

                        onNodeOver : function(target, dd, e, data) {
                            if (data.records.length === 1) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }
                        },

                        onNodeDrop : function(myRowIndex, target, dd, e1, data) {
                            if (opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                                try {
                                    var record = data.records[0];
                                    var data = record.data;

                                    Ext.getCmp('linkEditor').setValue(data.path);

                                    return true;
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                        }.bind(this, i)
                    });
                }.bind(this),
                delay: 1
            }
        });

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'opendxp_main_toolbar',
            items: [
                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "opendxp_icon_add"
                },"->",{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            autoScroll: true,
            store: this.store,
            columns: {
                items: typesColumns,
                defaults: {
                    renderer: Ext.util.Format.htmlEncode
                },
            },
            selModel: Ext.create('Ext.selection.RowModel', {}),
            plugins: [
                this.rowEditing
            ],

            trackMouseOver: true,
            columnLines: true,
            bbar: this.pagingtoolbar,
            bodyCls: "opendxp_editable_grid",
            stripeRows: true,
            tbar: toolbar,
            viewConfig: {
                forceFit: true,
                listeners: {
                    refresh: this.updateRows.bind(this)
                }
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));

        this.store.load();

        return this.grid;
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            let dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    if (data.records.length == 1) {
                        return Ext.dd.DropZone.prototype.dropAllowed;
                    }
                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    if (opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                        try {
                            var record = data.records[0];
                            var data = record.data;

                            var rec = this.grid.getStore().getAt(myRowIndex);
                            rec.set("link", data.path);

                            this.updateRows();

                            return true;
                        } catch (e) {
                            console.log(e);
                        }
                    }
                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0,{
            name: t('/')
        });

        this.updateRows();
    }
});
