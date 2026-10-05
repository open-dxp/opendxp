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

opendxp.registerNS("opendxp.bundle.seo.httpErrorLog");
/**
 * @private
 */
opendxp.bundle.seo.httpErrorLog = Class.create({

    initialize: function(id) {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("opendxp_panel_tabs");
        tabPanel.setActiveItem("opendxp_http_error_log");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "opendxp_http_error_log",
                title: t("http_errors"),
                iconCls: "opendxp_icon_httperrorlog",
                border: false,
                layout: "fit",
                closable:true,
                items: [this.getGrid()]
            });

            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("opendxp_http_error_log");


            this.panel.on("destroy", function () {
                opendxp.globalmanager.remove("bundle_seo_http_error_log");
            }.bind(this));

            opendxp.layout.refresh();
        }

        return this.panel;
    },


    /**
     * Opens the redirect editor with the path and the site of the URL. Once the redirect is saved, the URL leaves the
     * log, because the redirect answers it from now on.
     */
    createRedirect: function (record) {
        new opendxp.bundle.seo.redirectEditor({
            type: "path",
            source: record.get("path"),
            sourceSite: record.get("siteId")
        }, function () {
            Ext.Ajax.request({
                url: Routing.generate('opendxp_bundle_seo_misc_httperrorlogentrydelete', {uri: record.get("uri")}),
                method: "DELETE",
                success: function () {
                    this.store.reload();
                }.bind(this)
            });
        }.bind(this));
    },

    getGrid: function () {

        var itemsPerPage = opendxp.helpers.grid.getDefaultPageSize();
        var url = Routing.generate('opendxp_bundle_seo_misc_httperrorlog');

        this.store = opendxp.helpers.grid.buildDefaultStore(
            url,
            ["uri", "code", "date", "count", "path", "siteId"],
            itemsPerPage
        );

        var proxy = this.store.getProxy();
        proxy.extraParams["group"] = 1;
        proxy.getReader().setRootProperty('items');

        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store);

        var typesColumns = [
            {text: "Code", width: 60, sortable: true, dataIndex: 'code'},
            {text: t("path"), flex: 1, minWidth: 300, sortable: true, dataIndex: 'uri'},
            {text: t("amount"), width: 60, sortable: true, dataIndex: 'count'},
            {text: t("date"), width: 200, sortable: true, dataIndex: 'date',
                                                                    renderer: function(d) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, "Y-m-d H:i:s");
            }},
            {
                xtype: 'actioncolumn',
                menuText: t('open'),
                width: 30,
                items: [{
                    tooltip: t('open'),
                    icon: "/bundles/opendxpadmin/img/flat-color-icons/open_file.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        window.open(data.get("uri"));
                    }.bind(this)
                }]
            },
            {
                xtype: 'actioncolumn',
                menuText: t('redirect_create'),
                width: 30,
                hidden: !opendxp.globalmanager.get("user").isAllowed("redirects"),
                items: [{
                    tooltip: t('redirect_create'),

                    getClass: function (value, meta, record) {
                        return parseInt(record.get("code"), 10) === 404 ? "opendxp_icon_redirects" : "x-hidden-display";
                    },
                    isActionDisabled: function (view, rowIndex, colIndex, item, record) {
                        return parseInt(record.get("code"), 10) !== 404;
                    },
                    handler: function (grid, rowIndex) {
                        this.createRedirect(grid.getStore().getAt(rowIndex));
                    }.bind(this)
                }]
            }
        ];


        this.filterField = new Ext.form.TextField({
            xtype: "textfield",
            width: 200,
            style: "margin: 0 10px 0 0;",
            enableKeyEvents: true,
            listeners: {
                "keydown" : function (field, key) {
                    if (key.getKey() == key.ENTER) {
                        const val = field.getValue();
                        this.store.getProxy().extraParams.filter = val ? val : "";
                        this.store.load();
                    }
                }.bind(this)
            }
        });


        this.grid = new Ext.grid.GridPanel({
            frame: false,
            autoScroll: true,
            store: this.store,
            columns : typesColumns,
            autoExpandColumn: "path",
            trackMouseOver: true,
            bbar: this.pagingtoolbar,
            columnLines: true,
            stripeRows: true,
            listeners: {
                "rowdblclick": function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    var path = Routing.generate('opendxp_bundle_seo_misc_httperrorlogdetail', {
                        uri: data.get("uri"),
                    });
                    var win = new Ext.Window({
                        closable: true,
                        width: 810,
                        autoDestroy: true,
                        height: 430,
                        modal: true,
                        html: '<iframe src="' + path + '" frameborder="0" width="100%" height="390"></iframe>'
                    });
                    win.show();
                }
            },
            viewConfig: {
                forceFit: true
            },
            tbar: {
                cls: 'opendxp_main_toolbar',
                items: [{
                    text: t("refresh"),
                    iconCls: "opendxp_icon_reload",
                    handler: this.reload.bind(this)
                }, "-",{
                    text: t("group_by_path"),
                    pressed: true,
                    iconCls: "opendxp_icon_groupby",
                    enableToggle: true,
                    handler: function (button) {
                        this.store.getProxy().extraParams.group = button.pressed ? 1 : 0;
                        this.store.load();
                    }.bind(this)
                }, "-",{
                    text: t('flush'),
                    handler: function () {
                        Ext.Ajax.request({
                            url: Routing.generate('opendxp_bundle_seo_misc_httperrorlogflush'),
                            method: "DELETE",
                            success: function () {
                                var proxy = this.store.getProxy();
                                proxy.extraParams.filter = this.filterField.getValue();
                                this.store.load();
                            }.bind(this)
                        });
                    }.bind(this),
                    iconCls: "opendxp_icon_flush_recyclebin"
                }, "-", {
                    text: t("errors_from_the_last_7_days"),
                    xtype: "tbtext"
                }, '-',"->",{
                    text: t("filter") + "/" + t("search"),
                    xtype: "tbtext",
                    style: "margin: 0 10px 0 0;"
                },
                this.filterField]
            }
        });

        return this.grid;
    },

    reload: function () {
        this.store.reload();
    }
});
