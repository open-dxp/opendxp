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

opendxp.registerNS("opendxp.bundle.customreports.custom.report");
/**
 * @private
 */
opendxp.bundle.customreports.custom.report = Class.create(opendxp.bundle.customreports.abstract, {

    drillDownFilters: {},
    drillDownStores: [],

    matchType: function (type) {
        var types = ["global"];
        if (opendxp.bundle.customreports.abstract.prototype.matchTypeValidate(type, types)) {
            return true;
        }
        return false;
    },

    getName: function () {
        return "overview";
    },

    getIconCls: function () {
        return "opendxp_icon_sql";
    },

    prepareGridConfig: function(data) {
        this.drillDownFilters = {};
        this.drillDownStores = [];

        this.storeFields = [];
        this.gridColumns = [];

        this.drillDownFilterDefinitions = [];
        this.columnLabels = {};
        this.gridfilters = {};

        var gridColConfig = {};

        for(var f=0; f<data.columnConfiguration.length; f++) {

            var colConfig = data.columnConfiguration[f];
            this.storeFields.push(colConfig["name"]);

            if (colConfig["displayType"] == "hide") {
                continue;
            }

            this.columnLabels[colConfig["name"]] = colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"]);

            gridColConfig = {
                text: colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"]),
                hidden: !colConfig["display"],
                sortable: colConfig["order"],
                dataIndex: colConfig["name"]
            };

            if(colConfig["width"]) {
                gridColConfig["width"] = intval(colConfig["width"]);
            } else {
                gridColConfig["flex"] = 1;
            }

            if(colConfig["filter"]) {
                gridColConfig["filter"] = colConfig["filter"];
                this.gridfilters[colConfig["name"]] = colConfig["filter"];
            }

            if (colConfig["displayType"] == "text") {
                gridColConfig["renderer"] = function (key, value, metaData, record) {
                    if (value && Ext.String.hasHtmlCharacters(value)) {
                        return Ext.util.Format.htmlEncode(value);
                    } else {
                        return value;
                    }
                }.bind(this, colConfig["name"]);
            }

            if (colConfig["displayType"] == "date") {
                gridColConfig["renderer"] = function (key, value, metaData, record) {
                    if (value) {
                        var timestamp = intval(value) * 1000;
                        var date = new Date(timestamp);

                        return Ext.Date.format(date, "Y-m-d H:i");
                    }
                    return "";
                }.bind(this, colConfig["name"]);
            }


            if(colConfig["filter_drilldown"] == 'only_filter' || colConfig["filter_drilldown"] == 'filter_and_show') {
                this.drillDownFilterDefinitions.push(colConfig);
            }

            if(colConfig["filter_drilldown"] != 'only_filter') {
                this.gridColumns.push(gridColConfig);
            }

            if (colConfig["columnAction"]) {
                this.gridColumns.push({
                    text: t("open"),
                    menuText: t("open"),
                    xtype: 'actioncolumn',
                    width: 40,
                    items: [
                        {
                            tooltip: t("open") + " " + (colConfig["label"] ? t(colConfig["label"]) : t(colConfig["name"])),
                            icon: "/bundles/opendxpadmin/img/flat-color-icons/open_file.svg",
                            handler: function (colConfig, grid, rowIndex) {
                                var data = grid.getStore().getAt(rowIndex).getData();
                                var columnName = colConfig["name"];
                                var id = data[columnName];
                                var action = colConfig["columnAction"];
                                if (action === "openDocument") {
                                    opendxp.helpers.openElement(id, "document");
                                } else if (action === "openAsset") {
                                    opendxp.helpers.openElement(id, "asset");
                                } else if (action === "openObject") {
                                    opendxp.helpers.openElement(id, "object");
                                } else if (action === "openUrl") {
                                    window.open(id);
                                }
                            }.bind(this, colConfig)
                        }
                    ]
                });
            }
        }

    },

    createGrid: function() {
        var itemsPerPage = opendxp.helpers.grid.getDefaultPageSize();
        var url = Routing.generate('opendxp_bundle_customreports_customreport_data');
        this.store = opendxp.helpers.grid.buildDefaultStore(
            url, this.storeFields, itemsPerPage
        );
        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store);

        var proxy = this.store.getProxy();
        proxy.extraParams.name = this.config["name"];

        this.store.addListener('load', function() {
            var filterData = this.store.getFilters().items;

            if(this.chartStore) {
                this.chartStore.load({
                    params: {
                        name: this.config["name"],
                        filter: proxy.encodeFilters(filterData)
                    }
                });
            }

            for(var j = 0; j < this.drillDownStores.length; j++) {
                if(this.drillDownStores[j].notReload) {
                    //to prevent reopening of combo box
                    this.drillDownStores[j].notReload = false;
                } else {
                    this.drillDownStores[j].load({
                        params: {
                            filter: proxy.encodeFilters(filterData)
                        }
                    });
                }
            }

        }.bind(this));

        var topBar = this.buildTopBar(this.drillDownFilterDefinitions);

        //export button
        topBar.push("->");

        topBar.push({
            text: t("export"),
            iconCls: "opendxp_icon_export",
            handler: this.startExport.bind(this)
        });

        this.grid = new Ext.grid.GridPanel({
            region: "center",
            store: this.store,
            bbar: this.pagingtoolbar,
            columns: this.gridColumns,
            columnLines: true,
            plugins: ['opendxp.gridfilters'],
            stripeRows: true,
            trackMouseOver: true,
            forceFit: false,
            tbar: topBar,
            viewConfig: {
                enableTextSelection: true
            }
        });

        return this.grid;
    },

    initGrid: function (data) {
        this.prepareGridConfig(data);
        return this.createGrid();
    },

    buildTopBar: function(drillDownFilterDefinitions) {
        var drillDownFilterComboboxes = [];

        for(var i = 0; i < this.drillDownFilterDefinitions.length; i++) {
            drillDownFilterComboboxes.push({
                xtype: 'label',
                text: this.drillDownFilterDefinitions[i]["label"] ? t(this.drillDownFilterDefinitions[i]["label"])
                                                    : t(this.drillDownFilterDefinitions[i]["name"]),
                style: 'padding-right: 5px'
            });

            var drillDownStore = opendxp.helpers.grid.buildDefaultStore(
                Routing.generate('opendxp_bundle_customreports_customreport_drilldownoptions'),
                ['value'],
                400
            );
            var proxy = drillDownStore.getProxy();
            proxy.extraParams.name = this.config["name"];
            proxy.extraParams.field = this.drillDownFilterDefinitions[i]["name"];

            this.drillDownStores.push(drillDownStore);

            drillDownFilterComboboxes.push({
                xtype: 'combo',
                typeAhead: true,
                queryMode: 'local',
                editable: true,
                forceSelection: true,
                triggerAction: 'all',
                store: drillDownStore,
                listeners: {
                    select: function(fieldname, combo, record, index) {
                        let value = combo.getValue();

                        const lastQuery = combo.lastQuery;
                        // check last query
                        if(value == null && lastQuery && this.store.findExact(fieldname, lastQuery)) {
                            value = lastQuery;
                            combo.setValue(value)
                        }

                        this.drillDownFilters[fieldname] = value;

                        const proxy = this.store.getProxy();
                        proxy.extraParams['drillDownFilters[' + fieldname + ']'] = value;
                        if(this.chartStore) {
                            let chartProxy = this.chartStore.getProxy();
                            chartProxy.extraParams['drillDownFilters[' + fieldname + ']'] = value;
                        }
                        for(let j = 0; j < this.drillDownStores.length; j++) {
                            if(this.drillDownStores[j] != combo.getStore()) {
                                let drillDownProxy = this.drillDownStores[j].getProxy();
                                drillDownProxy.extraParams['drillDownFilters[' + fieldname + ']'] = value;
                            } else {
                                this.drillDownStores[j].notReload = true;
                            }
                        }

                        this.store.reload();
                    }.bind(this, this.drillDownFilterDefinitions[i]["name"])
                },
                valueField: 'value',
                displayField: 'name'
            });
            if(i < this.drillDownFilterDefinitions.length-1) {
                drillDownFilterComboboxes.push('-');
            }
        }
        return drillDownFilterComboboxes;
    },

    chartColors: [
        0x01841c,
        0x3D32FF,
        0xFF1000,
        0xFFEE00,
        0x00FF21,
        0x7F92FF,
        0xFFD800
    ],

    getChart: function(initData) {

        if(initData) {
            this.chartInitData = initData;
        }
        var data = this.chartInitData;

        if(data.chartType == 'line' || data.chartType == 'bar') {

            var storeFields = [];
            storeFields.push(data.xAxis);
            for(var i = 0; i < data.yAxis.length; i++) {
                storeFields.push(data.yAxis[i]);
            }

            this.chartStore = opendxp.helpers.grid.buildDefaultStore(
                Routing.generate('opendxp_bundle_customreports_customreport_chart'),
                storeFields,
                400000000
            );
            var proxy = this.chartStore.getProxy();
            proxy.extraParams.name = this.config["name"];

            var series = [];
            for(var i = 0; i < data.yAxis.length; i++) {
                var yAxis = data.yAxis[i];
                series.push({
                    title: this.columnLabels[data.yAxis[i]],
                    type: (data.chartType == 'line' ? 'line' : 'bar'),
                    xField: data.xAxis,
                    yField: yAxis,
                    marker: {
                        radius: 4
                    },
                    highlight: true,
                    tooltip: {
                        trackMouse: true,
                        renderer: function (tooltip, record, item) {
                            tooltip.setHtml(record.get(data.xAxis) + ': ' + record.get(item.field));
                        }
                    }
                });
            }


            var chart = Ext.create('Ext.chart.CartesianChart', {
                store: this.chartStore,
                width: '100%',
                height: 350,
                insetPadding: 5,
                innerPadding: 10,
                legend: {
                    docked: 'bottom'
                },
                interactions: [
                    'itemhighlight',
                    {
                        type: 'panzoom',
                        zoomOnPanGesture: true
                    }
                ],
                axes: [
                    {
                        type: 'numeric',
                        fields: data.yAxis,
                        position: 'left',
                        grid: true
                    },{
                        type: 'category',
                        fields: data.xAxis,
                        position: 'bottom'
                    }
                ],
                series: series
            });

        } else if(data.chartType == 'pie') {
            var chartFields = [];
            if (data.pieLabelColumn) {
                chartFields.push(data.pieLabelColumn);
            }
            if (data.pieColumn) {
                chartFields.push({
                    name: data.pieColumn,
                    type: "int"
                });
            }

            this.chartStore = opendxp.helpers.grid.buildDefaultStore(
                Routing.generate('opendxp_bundle_customreports_customreport_chart'),
                chartFields,
                400000000
            );
            var proxy = this.chartStore.getProxy();
            proxy.extraParams.name = this.config["name"];

            var chart = Ext.create('Ext.chart.PolarChart', {
                xtype: "polar",
                store: this.chartStore,
                theme: 'default-gradients',
                width: '100%',
                height: 350,
                innerPadding: 10,
                legend: {
                    docked: 'right'
                },
                interactions: ['rotate'],
                series: [{
                    type: 'pie',
                    xField: data.pieColumn,
                    highlight: true,
                    tooltip: {
                        trackMouse: true,
                        renderer: function (tooltip, record, item) {
                            var value = record.get(data.pieColumn);


                            var sum = this.chartStore.sum(data.pieColumn);
                            var percentage = sum > 0 ? " (" + Math.round((value * 100 / sum)) + ' %)' : "";
                            tooltip.setHtml(record.get(data.pieLabelColumn) + ': ' + value + percentage);
                        }.bind(this)
                    }
                }]
            });

            //this is needed to display correct data in legend when no label is defined
            //label cannot be defined, because there is a bug when reloading chartstore with another amount of data entries
            var series = chart.getSeries()[0];
            series.provideLegendInfo = function (target) {
                var me = this,
                    store = me.getStore();

                if (store) {
                    var items = store.getData().items,
                        labelField = data.pieLabelColumn,
                        xField = me.getXField(),
                        hidden = me.getHidden(),
                        i, style, fill;

                    for (i = 0; i < items.length; i++) {
                        style = me.getStyleByIndex(i);
                        fill = style.fillStyle;
                        if (Ext.isObject(fill)) {
                            fill = fill.stops && fill.stops[0].color;
                        }
                        target.push({
                            name: labelField ? String(items[i].get(labelField)) : xField + ' ' + i,
                            mark: fill || style.strokeStyle || 'black',
                            disabled: hidden[i],
                            series: me.getId(),
                            index: i
                        });
                    }
                }
            };


        }
        return chart;
    },

    getChartPanel: function(data) {
        this.chartPanel = new Ext.Panel({
            region: "north",
            height: 350,
            border: false,
            items: [this.getChart(data)]
        });

        return this.chartPanel;
    },

    getPanel: function () {

        if(!this.panel) {
            this.panel = new Ext.Panel({
                title: t(this.config["niceName"]),
                layout: "fit",
                border: false,
                items: []
            });


            Ext.Ajax.request({
                url: Routing.generate('opendxp_bundle_customreports_customreport_get'),
                params: {
                    name: this.config.name
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    var grid = this.initGrid(data);

                    var items = [];
                    if(data.chartType) {
                        var chartPanel = this.getChartPanel(data);
                        if(chartPanel) {
                            items.push(chartPanel);
                        }
                    }

                    items.push(grid);

                    var subPanel = new Ext.Panel({
                        layout: "border",
                        border: false,
                        items: items
                    });

                    this.panel.add(subPanel);
                    this.panel.updateLayout();

                    const postOpenReport = new CustomEvent(opendxp.events.postOpenReport, {
                        detail: {
                            grid: this.grid
                        }
                    });

                    document.dispatchEvent(postOpenReport);
                }.bind(this)
            });
        }

        return this.panel;
    },

    startExport: function () {
        new opendxp.element.gridexport.runner({
            source: "custom-reports",
            getParameters: function () {
                var parameters = opendxp.element.gridexport.runner.getStoreParameters(this.store);

                return {
                    name: this.config.name,
                    filter: parameters.filter,
                    sort: parameters.sort,
                    drillDownFilters: this.drillDownFilters
                };
            }.bind(this),
            filters: {filter: "", drillDownFilters: {}}
        }).start();
    }
});
