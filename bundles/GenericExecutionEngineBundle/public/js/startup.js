opendxp.registerNS('opendxp.bundle.genericexecutionengine.startup');

/**
 * fired after a job run started from the admin
 * the ID of the job run is passed as parameter
 */
opendxp.events.postStartJobRun = 'opendxp.jobRun.postStart';

opendxp.bundle.genericexecutionengine.startup = Class.create({

    initialize: function () {
        document.addEventListener(opendxp.events.preMenuBuild, this.preMenuBuild.bind(this));
        document.addEventListener(opendxp.events.opendxpReady, this.opendxpReady.bind(this));
    },

    opendxpReady: function () {
        new opendxp.bundle.genericexecutionengine.jobrun.menu();
    },

    preMenuBuild: function (e) {
        const user = opendxp.globalmanager.get('user');
        const perspectiveCfg = opendxp.globalmanager.get('perspective');
        let menu = e.detail.menu;

        if (user.isAllowed('gee_job_run_permission') && perspectiveCfg.inToolbar('extras.jobruns')) {
            menu.extras.items.push({
                text: t('gee_job_runs'),
                iconCls: 'opendxp_nav_icon_job_runs',
                itemId: 'opendxp_menu_extras_job_runs',
                handler: this.showJobRuns
            });
        }
    },

    showJobRuns: function () {
        try {
            opendxp.globalmanager.get('opendxp_job_runs_panel').activate();
        } catch (e) {
            opendxp.globalmanager.add('opendxp_job_runs_panel', new opendxp.bundle.genericexecutionengine.jobrun.panel());
        }
    }
});

new opendxp.bundle.genericexecutionengine.startup();
