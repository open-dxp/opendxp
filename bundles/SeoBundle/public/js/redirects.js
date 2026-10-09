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

opendxp.registerNS("opendxp.bundle.seo.redirects");
/**
 * @private
 */
opendxp.settings.redirects = Class.create({

    initialize: function () {
        this.getData();
    },

    getData: function () {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_seo_redirects_statuscodes'),
            success: function (response) {
                this.data = Ext.decode(response.responseText);
                //valid status codes
                try {
                    this.statusCodes = new Ext.data.JsonStore({
                        autoDestroy: true,
                        data: this.data.config,
                        proxy: {
                            type: 'memory',
                            reader: {
                                rootProperty: 'statuscodes'
                            }
                        },
                        fields: ['statusCode', 'display']
                    });
                } catch (e2) {
                    this.statusCodes = new Ext.data.JsonStore({
                        autoDestroy: true,
                        fields: ['statusCode', 'display']
                    });
                }


                this.getTabPanel();

            }.bind(this)
        });
    },

    activate: function () {
        var tabPanel = Ext.getCmp("opendxp_panel_tabs");
        tabPanel.setActiveItem("opendxp_redirects");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "opendxp_redirects",
                title: t("redirects"),
                iconCls: "opendxp_icon_redirects",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getRowEditor()]
            });

            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("opendxp_redirects");


            this.panel.on("destroy", function () {
                opendxp.globalmanager.remove("redirects");
            }.bind(this));

            opendxp.layout.refresh();
        }

        return this.panel;
    },

    getRowEditor: function () {
        var that = this;

        var itemsPerPage = opendxp.helpers.grid.getDefaultPageSize();
        var url = Routing.generate('opendxp_bundle_seo_redirects_redirects');

        this.store = opendxp.helpers.grid.buildDefaultStore(
            url,
            [
                {name: 'id'},
                {name: 'type', allowBlank: false},
                {name: 'source', allowBlank: false},
                {name: 'sourceSite'},
                {name: 'target', allowBlank: false},
                {name: 'targetSite'},
                {name: 'statusCode'},
                {name: 'priority', type:'int'},
                {name: 'regex'},
                {name: 'passThroughParameters'},
                {name: 'active'},
                {name: 'expiry', type: "date", convert: function (v, r) {
                    if(v && !(v instanceof Date)) {
                        var d = new Date(intval(v) * 1000);
                        return d;
                    } else {
                        return v;
                    }
                }},
                {name: 'validFrom', type: "date", convert: function (v) {
                    return opendxp.bundle.seo.redirectDate(v);
                }},
                {name: 'passThroughPath'},
                {name: 'protected'},
                {name: 'hits', persist: false},
                {name: 'lastHit', persist: false},
                {name: 'creationDate'},
                {name: 'modificationDate'}
            ],
            itemsPerPage
        );

        this.store.getProxy().setBatchActions(false);
        var redirectStore = this.store;

        // A refused redirect answers with success false. The row goes back to what was saved before.
        this.store.getProxy().on("exception", function (proxy, response) {
            var answer = {};
            try {
                answer = Ext.decode(response.responseText);
            } catch (e) {
                return;
            }

            if (answer.errors) {
                opendxp.bundle.seo.showRedirectErrors(answer.errors);
                redirectStore.rejectChanges();
            }
        });

        this.store.on("write", function (store, operation) {
            try {
                opendxp.bundle.seo.showRedirectWarnings(Ext.decode(operation.getResponse().responseText).warnings || []);
            } catch (e) {
                // a deleted redirect answers without warnings
            }
        });

        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store);

        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 400,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        var input = field;
                        var proxy = this.store.getProxy();
                        proxy.extraParams.filter = input.getValue();
                        this.pagingtoolbar.moveFirst();
                    }
                }.bind(this)
            }
        });

        var getRedirectTypeCombo = this.getRedirectTypeCombo();

        var typesColumns = [
            {
                text: t("type"),
                flex: 200,
                sortable: true,
                dataIndex: 'type',
                editor: getRedirectTypeCombo,
                renderer: function (redirectType) {
                    var store = getRedirectTypeCombo.getStore();
                    var pos = store.findExact("type", redirectType);
                    if(pos >= 0) {
                        return store.getAt(pos).get("name");
                    }
                    return redirectType;
                }
            },
            {text: t("source_site") + ' (' + t('optional') + ')', flex: 200, sortable:true, dataIndex: "sourceSite",
                editor: new Ext.form.ComboBox({
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
            {
                text: t("source"),
                flex: 200,
                sortable: true,
                dataIndex: 'source',
                editor: new Ext.form.TextField({}),
                renderer: function (value) {
                    return Ext.util.Format.htmlEncode(value);
                }
            },
            {
                text: t("target_site") + ' (' + t('optional') + ')', flex: 200, sortable: true, dataIndex: "targetSite",
                editor: new Ext.form.ComboBox({
                    store: opendxp.globalmanager.get("sites"),
                    valueField: "id",
                    displayField: "domain",
                    editable: false,
                    triggerAction: "all"
                }), renderer: function (siteId) {
                    var store = opendxp.globalmanager.get("sites");
                    var pos = store.findExact("id", siteId);
                    if (pos >= 0) {
                        return store.getAt(pos).get("domain");
                    }
                }
            },
            {
                text: t("target"), flex: 200, sortable: false, dataIndex: 'target',
                editor: {
                    xtype: 'textfield',
                    id: 'targetEditor',
                    fieldCls: "input_drop_target",
                },
                tdCls: "input_drop_target",
                renderer: function (value) {
                    return Ext.util.Format.htmlEncode(value);
                }
            },
            {text: t("status"), flex: 70, sortable: true, dataIndex: 'statusCode', editor: new Ext.form.ComboBox({
                store: this.statusCodes,
                displayField: 'display',
                valueField: 'statusCode',
                mode: "local",
                typeAhead: false,
                editable: false,
                listConfig: {minWidth: 200},
                forceSelection: true,
                triggerAction: "all"
            })},
            {text: t("priority"), flex: 60, sortable: true, dataIndex: 'priority',
                editor: opendxp.bundle.seo.redirectPriorityCombo()},
            new Ext.grid.column.Check({
                text: t("regex"),
                dataIndex: "regex",
                flex: 70,
                editor: {
                    xtype: 'checkbox',
                    id: 'regexEditor',
                    listeners: {
                        change: function (column, checked, oldChecked, eOpts) {
                            if (checked) {
                                Ext.MessageBox.show({
                                    title: t("warning"),
                                    msg: t("redirect_performance_warning"),
                                    buttons: Ext.MessageBox.YESNO,
                                    fn: function (result) {
                                        Ext.getCmp('regexEditor').setValue(result === 'yes');
                                    }.bind(this)
                                });
                            }
                        }.bind(this)
                    }
                },
            }),
            new Ext.grid.column.Check({
                text: t("pass_through_params"),
                dataIndex: "passThroughParameters",
                flex: 100,
                editor: {
                    xtype: 'checkbox',
                }
            }),
            new Ext.grid.column.Check({
                text: t("active"),
                dataIndex: "active",
                flex: 70,
                editor: {
                    xtype: 'checkbox',
                }
            }),
            {
                text: t("expiry") + ' (' + t('optional') + ')',
                flex: 150, sortable:true, dataIndex: "expiry",
                editor: {
                    xtype: 'datefield',
                    format: 'Y-m-d',
                    onChange: function(value) {
                        if(Ext.String.hasHtmlCharacters(value)) {
                            this.reset();
                        }
                    },
                },
                renderer:
                    function(d) {
                        if(d instanceof Date) {
                            return Ext.Date.format(d, "Y-m-d");
                        }
                    }
            },
            {
                text: t("redirect_valid_from") + ' (' + t('optional') + ')',
                flex: 150, sortable: true, dataIndex: "validFrom", hidden: true,
                editor: {
                    xtype: 'datefield',
                    format: 'Y-m-d'
                },
                renderer: function (d) {
                    if (d instanceof Date) {
                        return Ext.Date.format(d, "Y-m-d");
                    }
                }
            },
            {text: t("redirect_hits"), flex: 70, sortable: true, dataIndex: 'hits', align: 'right', hidden: true},
            {text: t("redirect_last_hit"), flex: 120, sortable: true, dataIndex: 'lastHit', hidden: true,
                renderer: function (d) {
                    return d ? Ext.Date.format(new Date(d * 1000), "Y-m-d H:i") : t("redirect_never");
                }
            },
            new Ext.grid.column.Check({
                text: t("redirect_protected"),
                dataIndex: "protected",
                flex: 70,
                hidden: true,
                hideable: opendxp.globalmanager.get("user").isAllowed("redirects_protected"),
                editor: {
                    xtype: 'checkbox'
                }
            }),
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false,
                hidden: true,
                flex: 150,
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
                flex: 150,
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
                menuText: t('redirect_edit_all'),
                flex: 30,
                items: [{
                    tooltip: t('redirect_edit_all'),
                    icon: "/bundles/opendxpadmin/img/flat-color-icons/edit.svg",
                    handler: function (grid, rowIndex) {
                        this.rowEditing.cancelEdit();
                        new opendxp.bundle.seo.redirectEditor(grid.getStore().getAt(rowIndex).getData(), function () {
                            this.store.reload();
                        }.bind(this));
                    }.bind(this)
                }]
            },
            {
                xtype: 'actioncolumn',
                menuText: t('delete'),
                flex: 30,
                items: [{
                    tooltip: t('delete'),
                    icon: "/bundles/opendxpadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        let data = grid.getStore().getAt(rowIndex);
                        opendxp.helpers.deleteConfirm(t('redirect'), data.data.id, function () {
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
                beforeedit: function (el, e, eOpts, i) {
                    var editorRow = el.editor.body;
                    editorRow.rowIdx = e.rowIdx;

                    let dd = new Ext.dd.DropZone(editorRow, {
                        ddGroup: "element",

                        getTargetFromEvent: function (e) {
                            return this.getEl();
                        },

                        onNodeOver: function (target, dd, e, data) {
                            if (data.records.length == 1) {
                                try {
                                    var record = data.records[0];
                                    var data = record.data;

                                    if (in_array(data.type, ["page", "link", "hardlink", "image", "text", "audio", "video", "document"])) {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }
                                } catch (e) {
                                    console.log(e);
                                }
                            }
                            return Ext.dd.DropZone.prototype.dropNotAllowed;

                        },

                        onNodeDrop: function (myRowIndex, target, dd, e1, data) {
                            if (opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                                try {
                                    var record = data.records[0];
                                    var data = record.data;

                                    if (in_array(data.type, ["page", "link", "hardlink", "image", "text", "audio", "video", "document"])) {
                                        Ext.getCmp('targetEditor').setValue(data.path);

                                        return true;
                                    }
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

        // Runs before the delayed listener above and stops it, because only a listener without delay can cancel.
        // A click on the checkbox only selects the row.
        this.rowEditing.on("beforeedit", function (editor, context) {
            return !context.column.isCheckerHd;
        }, null, {priority: 1});

        var toolbar = Ext.create('Ext.Toolbar', {
            cls: 'opendxp_main_toolbar',
            items: [
                {
                    xtype: "splitbutton",
                    text: t('add'),
                    iconCls: "opendxp_icon_add",
                    handler: this.openWizard.bind(this),
                    menu: [{
                        iconCls: "opendxp_icon_add",
                        text: t("add_expert_mode"),
                        handler: this.onAdd.bind(this)
                    },{
                        iconCls: "opendxp_icon_add",
                        text: t("add_beginner_mode"),
                        handler: this.openWizard.bind(this)
                    }]
                },
                {
                    text: t("export_csv"),
                    iconCls: "opendxp_icon_export",
                    handler: function () {
                        opendxp.helpers.download(Routing.generate('opendxp_bundle_seo_redirects_csvexport'));
                    }
                },
                {
                    text: t("import_csv"),
                    iconCls: "opendxp_icon_import",
                    handler: function () {
                        opendxp.helpers.uploadDialog(
                            Routing.generate('opendxp_bundle_seo_redirects_csvimport'), 'redirects',
                            function (res) {
                                that.store.reload();

                                var json;

                                try {
                                    json = Ext.decode(res.response.responseText);
                                } catch (e) {
                                    console.error(e);
                                }

                                if (json && json.data) {
                                    var stats = json.data;

                                    var icon = 'opendxp_icon_success';
                                    if (stats.errored > 0) {
                                        icon = 'opendxp_icon_warning';
                                    }

                                    var message = '';

                                    message += '<table class="opendxp_stats_table">';
                                    message += '<tr><th>' + t('redirects_import_total') + '</th><td class="opendxp_stats_table--number">' + stats.total + '</td></tr>';
                                    message += '<tr><th>' + t('redirects_import_created') + '</th><td class="opendxp_stats_table--number">' + stats.created + '</td></tr>';
                                    message += '<tr><th>' + t('redirects_import_updated') + '</th><td  class="opendxp_stats_table--number">' + stats.updated + '</td></tr>';

                                    if (stats.errored > 0) {
                                        message += '<tr><th>' + t('redirects_import_errored') + '</th><td class="opendxp_stats_table--number">' + stats.errored + '</td></tr>';
                                    }

                                    message += '</table>';

                                    if (stats.errors && Object.keys(stats.errors).length > 0) {
                                        message += '<h4 style="margin-top: 15px; margin-bottom: 0; color: red">' + t('redirects_import_errors') + '</h4>';
                                        message += '<table class="opendxp_stats_table">';

                                        var errorKeys = Object.keys(stats.errors);
                                        for (var i = 0; i < errorKeys.length; i++) {
                                            message += '<tr><td>' + t('redirects_import_error_line') + ' ' + errorKeys[i] + ':</td><td>' + stats.errors[errorKeys[i]].map(function (error) { return t(error); }).join(', ') + '</td></tr>';
                                        }

                                        message += '</table>';
                                    }

                                    var win = new Ext.Window({
                                        modal: true,
                                        iconCls: icon,
                                        title: t('redirects_csv_import'),
                                        width: 400,
                                        maxHeight: 500,
                                        html: message,
                                        autoScroll: true,
                                        bodyStyle: "padding: 10px;",
                                        buttonAlign: "center",
                                        shadow: false,
                                        closable: false,
                                        buttons: [{
                                            text: t("OK"),
                                            handler: function () {
                                                win.close();
                                            }
                                        }]
                                    });

                                    win.show();
                                }
                            },
                            function () {
                                Ext.MessageBox.alert(t("error"), t("error"));
                            }
                        )
                    }
                },
                {
                    text: t("redirects_expired_cleanup"),
                    iconCls: "opendxp_icon_cleanup",
                    handler: function () {
                        Ext.MessageBox.show({
                            title: t('redirects_expired_cleanup'),
                            msg: t('redirects_cleanup_warning'),
                            buttons: Ext.Msg.OKCANCEL,
                            icon: Ext.MessageBox.INFO,
                            fn: function (button) {
                                if (button == "ok") {
                                    this.cleanupExpiredRedirects();
                                }
                            }.bind(this)
                        });
                    }.bind(this)
                },
                this.selectionButton = new Ext.button.Button({
                    text: t("redirect_selection"),
                    iconCls: "opendxp_icon_checkbox",
                    disabled: true,
                    menu: [{
                        text: t("redirect_activate"),
                        iconCls: "opendxp_icon_success",
                        handler: this.setSelectedActive.bind(this, true)
                    }, {
                        text: t("redirect_deactivate"),
                        iconCls: "opendxp_icon_hide",
                        handler: this.setSelectedActive.bind(this, false)
                    }, {
                        text: t("delete"),
                        iconCls: "opendxp_icon_delete",
                        handler: this.deleteSelected.bind(this)
                    }]
                }),
                "->",
                this.getShowFilter(),
                {
                    text: t("search") + " / " + t("test_url"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField
            ]
        });

        this.selectionColumn = new Ext.selection.CheckboxModel({checkOnly: true});
        this.selectionColumn.on("selectionchange", function (model, selected) {
            this.selectionButton.setDisabled(selected.length === 0);
        }.bind(this));

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            autoScroll: true,
            store: this.store,
			columns : typesColumns,
            trackMouseOver: true,
            columnLines: true,
            bodyCls: "opendxp_editable_grid",
            selModel: this.selectionColumn,
            plugins: [
                this.rowEditing
            ],
            stripeRows: true,
            bbar: this.pagingtoolbar,
            tbar: toolbar,
            viewConfig: {
                forceFit: true,
                listeners: {
                    rowupdated: this.updateRows.bind(this),
                    refresh: this.updateRows.bind(this)
                }
            }
        });

        this.store.on("update", this.updateRows.bind(this));
        this.grid.on("viewready", this.updateRows.bind(this));
        this.grid.on('validateedit', function (editor, context) {

            if(context["field"] == 'priority' && context['newValues']['priority'] == 99) {
                Ext.MessageBox.show({
                    title: t("warning"),
                    msg: t("redirect_performance_warning"),
                    buttons: Ext.MessageBox.YESNO,
                    fn: function (result) {
                        if (result === 'yes') {
                            editor.cancelEdit();
                            context['record'].set('priority', 99);
                        }
                    }
                });

                return false;
            }

            if(context["field"] == 'type' && context['value'] == 'auto_create') {
                return false;
            }
        });

        return this.grid;
    },

    getShowFilter: function () {
        var shown = [
            ["", t("redirect_show_all")],
            ["active", t("redirect_show_active")],
            ["inactive", t("redirect_show_inactive")],
            ["scheduled", t("redirect_show_scheduled")],
            ["expired", t("redirect_show_expired")]
        ];

        if (this.data.config.countHits) {
            shown.push(["unused", t("redirect_show_unused")]);
        }

        if (opendxp.globalmanager.get("user").isAllowed("redirects_protected")) {
            shown.push(["protected", t("redirect_show_protected")]);
        }

        return new Ext.form.ComboBox({
            store: shown,
            value: "",
            queryMode: "local",
            editable: false,
            width: 220,
            style: "margin: 0 10px 0 0;",
            listeners: {
                select: function (combo) {
                    this.store.getProxy().extraParams.show = combo.getValue();
                    this.pagingtoolbar.moveFirst();
                }.bind(this)
            }
        });
    },

    setSelectedActive: function (active) {
        Ext.Array.each(this.grid.getSelectionModel().getSelection(), function (record) {
            record.set("active", active);
        });
    },

    deleteSelected: function () {
        var selection = this.grid.getSelectionModel().getSelection();
        if (selection.length === 0) {
            return;
        }

        Ext.MessageBox.confirm(t("delete"), t("redirect_delete_selection").replace("%count%", selection.length), function (button) {
            if (button === "yes") {
                this.store.remove(selection);
            }
        }.bind(this));
    },

    cleanupExpiredRedirects: function () {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_seo_redirects_cleanup'),
            method: 'DELETE',
            success: function (response) {
                try{
                    var data = Ext.decode(response.responseText);
                    if (data && data.success) {
                        this.store.reload();
                    } else {
                        opendxp.helpers.showNotification(t("error"), t("redirects_cleanup_error"), "error");
                    }
                } catch (e) {
                    opendxp.helpers.showNotification(t("error"), t("redirects_cleanup_error"), "error");
                }
            }.bind(this)
        });
    },

    updateRows: function () {

        var rows = Ext.get(this.grid.getEl().dom).query(".x-grid-row");

        for (var i = 0; i < rows.length; i++) {

            var dd = new Ext.dd.DropZone(rows[i], {
                ddGroup: "element",

                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    if (data.records.length == 1) {
                        try {
                            var record = data.records[0];
                            var data = record.data;

                            if (in_array(data.type, ["page", "link", "hardlink","image", "text", "audio", "video", "document"])) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            }
                        } catch (e) {
                            console.log(e);
                        }
                    }
                    return Ext.dd.DropZone.prototype.dropNotAllowed;

                },

                onNodeDrop : function(myRowIndex, target, dd, e, data) {
                    if (opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                        try {
                            var record = data.records[0];
                            var data = record.data;
                            if (in_array(data.type, ["page", "link", "hardlink","image", "text", "audio", "video", "document"])) {
                                var rec = this.grid.getStore().getAt(myRowIndex);
                                rec.set("target", data.path);
                                this.updateRows();
                                return true;
                            }
                        } catch (e) {
                            console.log(e);
                        }
                    }
                    return false;

                }.bind(this, i)
            });
        }

    },

    onAdd: function (btn, ev) {
        this.grid.store.insert(0, {
            source: ""
        });

		this.updateRows();
    },

    openWizard: function () {
        var typeCombo = this.getRedirectTypeCombo({
            name: 'type',
            fieldLabel: t("type"),
            value: 'path'
        });

        this.wizardForm = new Ext.form.FormPanel({
            bodyStyle: "padding:10px;",
            items: [typeCombo, {
                xtype: "textfield",
                name: "pattern",
                width: 600,
                emptyText: "/some/example/path",
                fieldLabel: t("source")
            }, {
                xtype: "textfield",
                name: "target",
                width: 600,
                emptyText: "/some/example/path",
                fieldLabel: t("target")
            }]
        });

        this.wizardWindow = new Ext.Window({
            width: 650,
            modal: true,
            items: [this.wizardForm],
            buttons: [{
                text: t("save"),
                iconCls: "opendxp_icon_accept",
                handler: this.saveWizard.bind(this)
            }]
        });

        this.wizardWindow.show();
    },

    saveWizard: function () {
        var values = this.wizardForm.getForm().getFieldValues();
        var pattern = values.pattern;

        var record = {
            type: values['type'],
            priority: 1,
            regex: false,
            active: true,
            source: pattern.replace('+', ' '),
            target: values['target']
        };

        this.grid.store.insert(0, record);
        this.updateRows();

        this.wizardWindow.close();
    },

    getRedirectTypeCombo: function (config) {
        return opendxp.bundle.seo.redirectTypeCombo(config);
    }
});
