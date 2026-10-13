opendxp.registerNS('opendxp.bundle.genericexecutionengine.jobrun.menu');

opendxp.bundle.genericexecutionengine.jobrun.menu = Class.create({

    interval: 5000,

    openMenuInterval: 2000,

    notificationTypes: {
        finished: 'success',
        cancelled: 'info'
    },

    rowTpl: new Ext.XTemplate(
        '<div class="opendxp_job_runs_row_title">{jobName:htmlEncode}</div>',
        '<div class="opendxp_job_runs_row_text">{startedAt}</div>',
        '<tpl if="percent !== null">',
        '<div class="opendxp_job_runs_row_progress"><div style="width: {percent}%"></div></div>',
        '<div class="opendxp_job_runs_row_text">{progress} / {total}</div>',
        '</tpl>',
        '<div class="opendxp_job_runs_row_text">{message:htmlEncode}</div>',
        '<tpl for="artifacts">',
        '<a href="#" class="opendxp_job_runs_row_artifact" data-artifact="{.:htmlEncode}">{.:htmlEncode}</a>',
        '</tpl>'
    ),

    initialize: function () {
        this.jobRuns = [];
        this.runningIds = [];
        this.startedIds = [];

        this.createSidebarItem();
        this.menu = Ext.create('opendxp.menu.menu', {
            cls: 'opendxp_job_runs_menu',
            plain: true,
            items: []
        });

        this.menu.on('beforeshow', this.update.bind(this));

        document.addEventListener(opendxp.events.postStartJobRun, this.addStartedJobRun.bind(this));
        document.addEventListener('visibilitychange', this.update.bind(this));

        this.update();
    },

    createSidebarItem: function () {

        const notification = Ext.get('opendxp_notification');
        const config = {
            id: 'opendxp_job_runs',
            'data-menu-tooltip': t('gee_job_runs'),
            children: [
                {tag: 'span', cls: 'opendxp_job_runs_icon'},
                {tag: 'span', id: 'opendxp_job_runs_count', style: 'display:none;'}
            ]
        };

        let item;
        if (notification && notification.isVisible()) {
            item = Ext.DomHelper.insertBefore(notification, config, true);
            Ext.getBody().addCls('opendxp_job_runs_above_notification');
        } else {
            item = Ext.DomHelper.append('opendxp_sidebar', config, true);
        }

        item.on('mousedown', function (event) {
            if (!this.menu.hidden) {
                this.menu.hide();

                return;
            }

            event.stopEvent();
            this.menu.showAt([60, item.getOffsetsTo(Ext.getBody())[1]]);
        }.bind(this));
    },

    addStartedJobRun: function (event) {
        const jobRunId = event.detail.jobRunId;

        this.startedIds.push(jobRunId);
        this.runningIds.push(jobRunId);
        this.update();
    },

    update: function () {
        window.clearTimeout(this.timeout);

        if (document.hidden) {
            return;
        }

        opendxp.backgroundConnection.request({
            url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_menu'),
            method: 'GET',
            params: {
                'ids[]': this.runningIds
            },
            success: function (response) {
                const answer = Ext.decode(response.responseText);

                answer.endedJobRuns.forEach(this.reportEnd.bind(this));
                this.setJobRuns(answer.jobRuns);
                this.scheduleUpdate();
            }.bind(this),
            failure: this.scheduleUpdate.bind(this)
        });
    },

    scheduleUpdate: function () {
        if (this.runningIds.length === 0) {
            return;
        }

        const interval = this.menu.isVisible() ? this.openMenuInterval : this.interval;
        this.timeout = window.setTimeout(this.update.bind(this), interval);
    },

    setJobRuns: function (jobRuns) {
        this.jobRuns = jobRuns;
        this.runningIds = jobRuns.filter(this.isRunning).map(function (jobRun) {
            return jobRun.id;
        });

        const count = Ext.get('opendxp_job_runs_count');
        count.update(String(this.runningIds.length));
        count.setVisible(this.runningIds.length > 0);

        this.updateMenu();
    },

    isRunning: function (jobRun) {
        return jobRun.state === 'running';
    },

    reportEnd: function (jobRun) {
        const success = jobRun.state === 'finished';

        opendxp.helpers.showNotification(
            Ext.util.Format.htmlEncode(jobRun.job_name),
            Ext.util.Format.htmlEncode(this.getMessage(jobRun)),
            this.notificationTypes[jobRun.state] || 'error'
        );

        const startedIndex = this.startedIds.indexOf(jobRun.id);
        if (startedIndex === -1) {
            return;
        }

        this.startedIds.splice(startedIndex, 1);
        if (success && jobRun.result) {
            this.downloadAll(jobRun);
        }
    },

    downloadAll: function (jobRun) {
        // The download helper of the admin uses one frame. A second download at once would cancel the first.
        jobRun.result.artifacts.forEach(function (artifact, index) {
            Ext.defer(this.download, index * 1000, this, [jobRun, artifact]);
        }.bind(this));
    },

    download: function (jobRun, artifact) {
        opendxp.helpers.download(Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_download', {
            id: jobRun.id,
            artifact: artifact
        }));

        Ext.defer(this.update, 2000, this);
    },

    updateMenu: function () {
        const signature = this.jobRuns.map(function (jobRun) {
            return jobRun.id + ':' + jobRun.state;
        }).join(',');

        // An open menu keeps its rows while only the progress changes, so a click on a button is not lost.
        if (signature === this.menuSignature) {
            this.jobRuns.forEach(function (jobRun, index) {
                this.menu.items.getAt(index).down('component').update(this.getRowData(jobRun));
            }.bind(this));

            return;
        }

        this.menuSignature = signature;
        this.menu.removeAll();

        if (this.jobRuns.length === 0) {
            this.menu.add({
                text: t('no_items_found'),
                disabled: true
            });

            return;
        }

        this.jobRuns.forEach(function (jobRun) {
            this.menu.add(this.createRow(jobRun));
        }.bind(this));
    },

    createRow: function (jobRun) {
        const running = this.isRunning(jobRun);

        return {
            xtype: 'container',
            cls: 'opendxp_job_runs_row opendxp_job_runs_row_' + jobRun.state,
            width: 360,
            layout: {
                type: 'hbox',
                align: 'middle'
            },
            items: [
                {
                    xtype: 'component',
                    flex: 1,
                    tpl: this.rowTpl,
                    data: this.getRowData(jobRun),
                    listeners: {
                        click: {
                            element: 'el',
                            fn: function (event) {
                                const link = event.getTarget('[data-artifact]');
                                if (link) {
                                    event.stopEvent();
                                    this.download(jobRun, link.getAttribute('data-artifact'));
                                }
                            }.bind(this)
                        }
                    }
                },
                {
                    xtype: 'button',
                    cls: 'opendxp_job_runs_row_action',
                    iconCls: running ? 'opendxp_icon_cancel' : 'opendxp_icon_delete',
                    tooltip: t(running ? 'cancel' : 'delete'),
                    handler: running ? this.cancel.bind(this, jobRun) : this.delete.bind(this, jobRun)
                }
            ]
        };
    },

    getRowData: function (jobRun) {
        const localeDateTime = opendxp.globalmanager.get('localeDateTime');
        const running = this.isRunning(jobRun);

        return {
            jobName: jobRun.job_name,
            startedAt: Ext.Date.format(new Date(jobRun.started_at * 1000), localeDateTime.getDateTimeFormat()),
            percent: running && jobRun.total ? Math.min(100, Math.round(jobRun.progress / jobRun.total * 100)) : null,
            progress: jobRun.progress,
            total: jobRun.total,
            message: this.getMessage(jobRun),
            artifacts: !running && jobRun.result ? jobRun.result.artifacts : []
        };
    },

    getMessage: function (jobRun) {
        if (!this.isRunning(jobRun) && jobRun.result) {
            return jobRun.result.message;
        }

        return jobRun.current_message || '';
    },

    cancel: function (jobRun) {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_cancel'),
            method: 'POST',
            params: {
                id: jobRun.id
            },
            callback: this.update.bind(this)
        });
    },

    delete: function (jobRun) {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_genericexecutionengine_jobrun_delete', {id: jobRun.id}),
            method: 'DELETE',
            callback: this.update.bind(this)
        });
    }
});
