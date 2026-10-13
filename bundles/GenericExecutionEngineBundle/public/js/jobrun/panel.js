opendxp.registerNS('opendxp.bundle.genericexecutionengine.jobrun.panel');

opendxp.bundle.genericexecutionengine.jobrun.panel = Class.create({

    states: ['running', 'finished', 'finished_with_errors', 'failed', 'cancelled'],

    initialize: function () {
        const user = opendxp.globalmanager.get('user');

        this.userId = user.id;
        this.canSeeAll = user.isAllowed('gee_see_all_job_runs_permission');
        this.filters = {};

        this.getTabPanel();
    },

    activate: function () {
        Ext.getCmp('opendxp_panel_tabs').setActiveItem('opendxp_job_runs_panel');
    },

    getTabPanel: function () {
        if (this.panel) {
            return this.panel;
        }

        this.store = Ext.create('Ext.data.Store', {
            pageSize: opendxp.helpers.grid.getDefaultPageSize(),
            proxy: {
                type: 'ajax',
                url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_list'),
                reader: {
                    type: 'json',
                    rootProperty: 'jobRuns',
                    totalProperty: 'total'
                }
            },
            fields: [
                'id', 'job_name', 'state', 'owner', 'started_at', 'last_update_at', 'current_message', 'canCancel',
                'total', 'progress', 'result', 'log'
            ],
            autoLoad: true
        });

        this.grid = Ext.create('Ext.grid.Panel', {
            store: this.store,
            border: false,
            columns: this.getColumns(),
            viewConfig: {
                enableTextSelection: true,
                emptyText: t('no_items_found'),
                deferEmptyText: false
            },
            tbar: this.getFilterToolbar(),
            bbar: opendxp.helpers.grid.buildDefaultPagingToolbar(this.store, {hideSelection: true}),
            listeners: {
                rowdblclick: function (grid, record) {
                    this.showLog(record);
                }.bind(this),
                cellclick: function (view, td, cellIndex, record, tr, rowIndex, e) {
                    const artifact = e.getTarget('[data-artifact]');
                    if (artifact) {
                        e.stopEvent();
                        this.download(record, artifact.getAttribute('data-artifact'));
                    }
                }.bind(this)
            }
        });

        this.panel = new Ext.Panel({
            id: 'opendxp_job_runs_panel',
            title: t('gee_job_runs'),
            iconCls: 'opendxp_icon_job_runs',
            border: false,
            layout: 'fit',
            closable: true,
            items: [this.grid]
        });

        const tabPanel = Ext.getCmp('opendxp_panel_tabs');
        tabPanel.add(this.panel);
        tabPanel.setActiveItem('opendxp_job_runs_panel');

        this.panel.on('destroy', function () {
            opendxp.globalmanager.remove('opendxp_job_runs_panel');
        }.bind(this));

        opendxp.layout.refresh();

        return this.panel;
    },

    getColumns: function () {
        const localeDateTime = opendxp.globalmanager.get('localeDateTime');
        const formatDate = function (timestamp) {
            return Ext.Date.format(new Date(timestamp * 1000), localeDateTime.getDateTimeFormat());
        };

        return [
            {
                text: t('gee_job_run_job'),
                dataIndex: 'job_name',
                flex: 2,
                renderer: this.renderText
            },
            {
                text: t('gee_job_run_state'),
                dataIndex: 'state',
                width: 170,
                renderer: function (state) {
                    return Ext.String.format(
                        '<span class="opendxp_job_run_state opendxp_job_run_state_{0}">{1}</span>',
                        state,
                        Ext.util.Format.htmlEncode(t('gee_job_run_state_' + state))
                    );
                }
            },
            {
                text: t('gee_job_run_progress'),
                xtype: 'widgetcolumn',
                width: 200,
                widget: {
                    xtype: 'progressbarwidget'
                },
                onWidgetAttach: this.updateProgress.bind(this)
            },
            {
                text: t('gee_job_run_message'),
                dataIndex: 'current_message',
                flex: 3,
                renderer: this.renderMessage.bind(this)
            },
            {
                text: t('gee_job_run_files'),
                dataIndex: 'result',
                width: 160,
                renderer: this.renderArtifacts
            },
            {
                text: t('gee_job_run_owner'),
                dataIndex: 'owner',
                width: 140,
                hidden: !this.canSeeAll,
                renderer: function (owner) {
                    return Ext.util.Format.htmlEncode(owner || t('gee_job_run_system'));
                }
            },
            {
                text: t('gee_job_run_started'),
                dataIndex: 'started_at',
                width: 150,
                renderer: formatDate
            },
            {
                text: t('gee_job_run_last_activity'),
                dataIndex: 'last_update_at',
                width: 150,
                renderer: formatDate
            },
            {
                xtype: 'actioncolumn',
                width: 40,
                items: [{
                    getClass: function (value, meta, record) {
                        if (record.get('canCancel')) {
                            meta.tdAttr = 'data-qtip="' + t('cancel') + '"';

                            return 'opendxp_action_column opendxp_icon_cancel';
                        }

                        meta.tdAttr = 'data-qtip="' + t('delete') + '"';

                        return 'opendxp_action_column opendxp_icon_delete';
                    },
                    handler: function (grid, rowIndex) {
                        const record = grid.getStore().getAt(rowIndex);

                        if (record.get('canCancel')) {
                            this.cancel(record);
                        } else {
                            this.delete(record);
                        }
                    }.bind(this)
                }]
            }
        ];
    },

    getFilterToolbar: function () {
        const states = this.states.map(function (state) {
            return [state, t('gee_job_run_state_' + state)];
        });

        const items = [
            this.createFilterCombo('job', t('gee_job_run_job'), Ext.create('Ext.data.Store', {
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_jobnames'),
                    reader: {
                        type: 'json',
                        rootProperty: 'jobNames'
                    }
                },
                fields: ['name', 'label']
            }), 'name', 'label'),
            this.createFilterCombo('state', t('gee_job_run_state'), states)
        ];

        if (this.canSeeAll) {
            items.push(this.createFilterCombo('owner', t('gee_job_run_owner'), [
                [this.userId, t('gee_job_run_mine')],
                ['system', t('gee_job_run_system')]
            ]));
        }

        items.push(
            this.createDateFilter('from', t('from')),
            this.createDateFilter('until', t('to'))
        );

        return items;
    },

    createFilterCombo: function (name, emptyText, store, valueField, displayField) {
        return {
            xtype: 'combo',
            width: 180,
            emptyText: emptyText,
            store: store,
            valueField: valueField || 'field1',
            displayField: displayField || 'field2',
            queryMode: Ext.isArray(store) ? 'local' : 'remote',
            editable: false,
            triggers: {
                clear: {
                    cls: 'x-form-clear-trigger',
                    weight: -1,
                    hidden: true,
                    handler: function (combo) {
                        combo.clearValue();
                    }
                }
            },
            listeners: {
                change: function (combo, value) {
                    combo.getTrigger('clear').setHidden(Ext.isEmpty(value));
                    this.setFilter(name, value);
                }.bind(this)
            }
        };
    },

    createDateFilter: function (name, emptyText) {
        return {
            xtype: 'datefield',
            width: 130,
            emptyText: emptyText,
            listeners: {
                change: function (field, value) {
                    if (value && name === 'until') {
                        value = Ext.Date.add(value, Ext.Date.DAY, 1);
                    }

                    this.setFilter(name, value ? Math.floor(value.getTime() / 1000) : null);
                }.bind(this)
            }
        };
    },

    setFilter: function (name, value) {
        const proxy = this.store.getProxy();

        if (Ext.isEmpty(value)) {
            proxy.setExtraParam(name, undefined);
        } else {
            proxy.setExtraParam(name, value);
        }

        this.store.loadPage(1);
    },

    updateProgress: function (column, widget, record) {
        const total = record.get('total');
        const progress = record.get('progress');

        widget.setHidden(!total && !progress);
        widget.toggleCls('opendxp_job_run_progress_count', !total);
        widget.setValue(total ? Math.min(progress / total, 1) : 0);
        widget.setText(total ? progress + ' / ' + total : String(progress || ''));
    },

    renderText: function (text, meta) {
        const encoded = Ext.util.Format.htmlEncode(text || '');
        meta.tdAttr = 'data-qtip="' + Ext.util.Format.htmlEncode(encoded) + '"';

        return encoded;
    },

    renderMessage: function (currentMessage, meta, record) {
        const result = record.get('result');
        const message = record.get('state') !== 'running' && result ? result.message : currentMessage;

        return this.renderText(message, meta);
    },

    renderArtifacts: function (result) {
        if (!result) {
            return '';
        }

        return result.artifacts.map(function (artifact) {
            return Ext.String.format(
                '<a href="#" class="opendxp_job_run_artifact" data-artifact="{0}">{0}</a>',
                Ext.util.Format.htmlEncode(artifact)
            );
        }).join('<br>');
    },

    download: function (record, artifact) {
        opendxp.helpers.download(Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_download', {
            id: record.get('id'),
            artifact: artifact
        }));

        Ext.defer(this.store.reload, 2000, this.store);
    },

    cancel: function (record) {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_cancel'),
            method: 'POST',
            params: {id: record.get('id')},
            success: function () {
                this.store.reload();
            }.bind(this)
        });
    },

    delete: function (record) {
        Ext.Msg.confirm(t('delete'), t('delete_message'), function (button) {
            if (button !== 'yes') {
                return;
            }

            Ext.Ajax.request({
                url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_delete', {id: record.get('id')}),
                method: 'DELETE',
                success: function () {
                    this.store.reload();
                }.bind(this)
            });
        }.bind(this));
    },

    showLog: function (record) {
        const localeDateTime = opendxp.globalmanager.get('localeDateTime');

        new Ext.window.Window({
            title: Ext.util.Format.htmlEncode(record.get('job_name')) + ' (' + record.get('id') + ')',
            iconCls: 'opendxp_icon_job_runs',
            width: 800,
            height: 500,
            modal: true,
            layout: 'fit',
            items: [{
                xtype: 'grid',
                border: false,
                store: Ext.create('Ext.data.Store', {
                    fields: ['createdAt', 'logMessage'],
                    data: record.get('log') || []
                }),
                viewConfig: {
                    enableTextSelection: true,
                    emptyText: t('no_items_found'),
                    deferEmptyText: false
                },
                columns: [{
                    text: t('gee_job_run_time'),
                    dataIndex: 'createdAt',
                    width: 150,
                    renderer: function (createdAt) {
                        return Ext.Date.format(new Date(createdAt), localeDateTime.getDateTimeFormat());
                    }
                }, {
                    text: t('gee_job_run_log'),
                    dataIndex: 'logMessage',
                    flex: 1,
                    cellWrap: true,
                    renderer: Ext.util.Format.htmlEncode
                }]
            }]
        }).show();
    }
});
