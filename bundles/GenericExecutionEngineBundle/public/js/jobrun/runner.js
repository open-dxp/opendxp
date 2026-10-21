opendxp.registerNS('opendxp.bundle.genericexecutionengine.jobrun.runner');

/**
 * Expects the route to start a job and to answer with the ID of its job run: {"jobRunId": 12}.
 */
opendxp.bundle.genericexecutionengine.jobrun.runner = {

    start: function (config) {
        Ext.Ajax.request({
            url: config.url,
            method: 'POST',
            params: config.params || {},
            success: function (response) {
                const jobRunId = Ext.decode(response.responseText).jobRunId;

                document.dispatchEvent(new CustomEvent(opendxp.events.postStartJobRun, {
                    detail: {
                        jobRunId: jobRunId
                    }
                }));
                opendxp.helpers.showNotification(config.title, config.message, 'info');

                if (config.success) {
                    config.success(jobRunId);
                }
            }
        });
    }
};
