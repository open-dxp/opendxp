opendxp.registerNS("opendxp.bundle.seo.redirectEditor");

/**
 * Edits every field of a redirect, also the ones the grid does not show. The redirects grid opens it for a row, and
 * the HTTP error log opens it to create a redirect for a URL that was not found.
 *
 * @private
 */
opendxp.bundle.seo.redirectEditor = Class.create({

    /**
     * @param {Object} data the redirect, or the values to start a new one with
     * @param {Function} onSave receives the saved redirect
     */
    initialize: function (data, onSave) {
        this.data = data || {};
        this.onSave = onSave || Ext.emptyFn;

        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_seo_redirects_statuscodes'),
            success: function (response) {
                this.show(Ext.decode(response.responseText).config.statuscodes);
            }.bind(this)
        });
    },

    show: function (statusCodes) {
        const data = this.data;
        const isNew = !data.id;
        const sites = opendxp.globalmanager.get("sites");
        const siteCombo = function (name, label) {
            return {
                xtype: "combo",
                name: name,
                fieldLabel: label,
                store: sites,
                valueField: "id",
                displayField: "domain",
                editable: false,
                queryMode: "local",
                value: data[name] || null,
                triggers: {
                    clear: {
                        cls: 'x-form-clear-trigger',
                        handler: function (field) {
                            field.setValue(null);
                        }
                    }
                }
            };
        };

        this.form = new Ext.form.FormPanel({
            bodyStyle: "padding: 10px;",
            scrollable: "y",
            defaults: {
                xtype: "fieldset",
                defaults: {labelWidth: 170, anchor: "100%"}
            },
            items: [{
                title: t("source"),
                items: [
                    opendxp.bundle.seo.redirectTypeCombo({name: "type", fieldLabel: t("type"), value: data.type || "path", listeners: {
                        change: this.updateDomainFields.bind(this)
                    }}),
                    siteCombo("sourceSite", t("source_site")),
                    {xtype: "textfield", name: "source", fieldLabel: t("source"), value: data.source || "", allowBlank: false},
                    {xtype: "checkbox", name: "regex", fieldLabel: t("regex"), value: !!data.regex}
                ]
            }, {
                title: t("target"),
                items: [
                    {xtype: "textfield", name: "target", fieldLabel: t("target"), value: data.target || "", fieldCls: "input_drop_target",
                        listeners: {afterrender: this.acceptDocuments}},
                    siteCombo("targetSite", t("target_site")),
                    {xtype: "combo", name: "statusCode", fieldLabel: t("status"), store: new Ext.data.Store({fields: ["statusCode", "display"], data: statusCodes}),
                        valueField: "statusCode", displayField: "display", queryMode: "local", editable: false, value: parseInt(data.statusCode || 301, 10)},
                    {xtype: "checkbox", name: "passThroughParameters", fieldLabel: t("pass_through_params"), value: !!data.passThroughParameters},
                    {xtype: "checkbox", name: "passThroughPath", fieldLabel: t("redirect_pass_through_path"), value: !!data.passThroughPath}
                ]
            }, {
                title: t("redirect_settings"),
                items: [
                    opendxp.bundle.seo.redirectPriorityCombo({name: "priority", fieldLabel: t("priority"), value: parseInt(data.priority || 1, 10)}),
                    {xtype: "checkbox", name: "active", fieldLabel: t("active"), value: isNew ? true : !!data.active},
                    {xtype: "datefield", name: "validFrom", fieldLabel: t("redirect_valid_from"), format: "Y-m-d", value: opendxp.bundle.seo.redirectDate(data.validFrom)},
                    {xtype: "datefield", name: "expiry", fieldLabel: t("expiry"), format: "Y-m-d", value: opendxp.bundle.seo.redirectDate(data.expiry)},
                    {xtype: "checkbox", name: "protected", fieldLabel: t("redirect_protected"), value: !!data.protected,
                        hidden: !opendxp.globalmanager.get("user").isAllowed("redirects_protected")}
                ]
            }, {
                title: t("redirect_usage"),
                hidden: isNew,
                defaults: {xtype: "displayfield", labelWidth: 170, anchor: "100%"},
                items: [
                    {fieldLabel: t("redirect_hits"), value: data.hits || 0},
                    {fieldLabel: t("redirect_last_hit"), value: data.lastHit ? Ext.Date.format(new Date(data.lastHit * 1000), "Y-m-d H:i") : t("redirect_never")},
                    {fieldLabel: t("creationDate"), value: data.creationDate ? Ext.Date.format(new Date(data.creationDate * 1000), "Y-m-d H:i") : ""},
                    {fieldLabel: t("modificationDate"), value: data.modificationDate ? Ext.Date.format(new Date(data.modificationDate * 1000), "Y-m-d H:i") : ""}
                ]
            }]
        });

        this.window = new Ext.Window({
            title: isNew ? t("redirect_new") : t("redirect_number").replace("%id%", data.id),
            iconCls: "opendxp_icon_redirects",
            width: 720,
            maxHeight: Ext.getBody().getViewSize().height - 40,
            modal: true,
            layout: "fit",
            items: [this.form],
            buttons: [{
                text: t("save"),
                iconCls: "opendxp_icon_accept",
                handler: this.save.bind(this)
            }]
        });

        this.window.show();
        this.updateDomainFields();
    },

    /**
     * Fields that only a domain redirect uses are shown for that type alone.
     */
    updateDomainFields: function () {
        if (!this.form) {
            return;
        }

        const form = this.form.getForm();
        const isDomain = form.findField("type").getValue() === "domain";

        form.findField("passThroughPath").setHidden(!isDomain);
        form.findField("regex").setHidden(isDomain);
        form.findField("sourceSite").setHidden(isDomain);
    },

    acceptDocuments: function (field) {
        new Ext.dd.DropZone(field.getEl(), {
            ddGroup: "element",
            getTargetFromEvent: function () {
                return this.getEl();
            },
            onNodeOver: function (target, dd, e, data) {
                return data.records.length === 1 && in_array(data.records[0].data.type, ["page", "link", "hardlink"])
                    ? Ext.dd.DropZone.prototype.dropAllowed
                    : Ext.dd.DropZone.prototype.dropNotAllowed;
            },
            onNodeDrop: function (target, dd, e, data) {
                if (!opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                    return false;
                }

                field.setValue(data.records[0].data.path);

                return true;
            }
        });
    },

    save: function () {
        const values = this.form.getForm().getFieldValues();
        const timestamp = function (date) {
            return date instanceof Date ? Math.round(date.getTime() / 1000) : null;
        };

        const redirect = Ext.apply({}, {
            validFrom: timestamp(values.validFrom),
            expiry: timestamp(values.expiry),
            sourceSite: values.sourceSite || null,
            targetSite: values.targetSite || null
        }, values);

        if (this.data.id) {
            redirect.id = this.data.id;
        }

        Ext.Ajax.request({
            url: Routing.generate('opendxp_bundle_seo_redirects_redirects', {xaction: this.data.id ? 'update' : 'create'}),
            method: "POST",
            params: {data: Ext.encode(redirect)},
            success: function (response) {
                const answer = Ext.decode(response.responseText);

                if (!answer.success) {
                    opendxp.bundle.seo.showRedirectErrors(answer.errors || [], this.form.getForm());

                    return;
                }

                opendxp.bundle.seo.showRedirectWarnings(answer.warnings || []);
                this.window.close();
                this.onSave(answer.data);
            }.bind(this)
        });
    }
});

/**
 * The types of a redirect, each with an example of the source it compares.
 */
opendxp.bundle.seo.redirectTypeCombo = function (config) {
    return new Ext.form.ComboBox(Ext.merge({
        store: Ext.create('Ext.data.ArrayStore', {
            fields: ['type', 'name'],
            data: [
                ["entire_uri", t('redirects_type_entire_uri') + ': https://host.com/foo?key=value'],
                ["path_query", t('redirects_type_path_query') + ': /foo?key=value'],
                ["path", t('redirects_type_path') + ': /foo'],
                ["domain", t('redirects_type_domain') + ': summer.example.com'],
                ["auto_create", t('auto_create')]
            ]
        }),
        queryMode: "local",
        typeAhead: false,
        editable: false,
        displayField: 'name',
        valueField: 'type',
        listConfig: {minWidth: 350},
        forceSelection: true,
        triggerAction: "all"
    }, config || {}));
};

opendxp.bundle.seo.redirectPriorityCombo = function (config) {
    return new Ext.form.ComboBox(Ext.merge({
        store: [
            [1, "1 - " + t("lowest")], [2, 2], [3, 3], [4, 4], [5, 5], [6, 6], [7, 7], [8, 8], [9, 9],
            [10, "10 - " + t("highest")],
            [99, "99 - " + t("override_all")]
        ],
        queryMode: "local",
        typeAhead: false,
        editable: false,
        forceSelection: true,
        listConfig: {minWidth: 200},
        triggerAction: "all"
    }, config || {}));
};

opendxp.bundle.seo.redirectDate = function (value) {
    if (value instanceof Date) {
        return value;
    }

    return value ? new Date(parseInt(value, 10) * 1000) : null;
};

/**
 * @param {Array} warnings translation keys with the parameters to fill in
 */
opendxp.bundle.seo.showRedirectWarnings = function (warnings) {
    if (warnings.length === 0) {
        return;
    }

    const messages = warnings.map(function (warning) {
        let message = t(warning.message);
        Ext.Object.each(warning.parameters || {}, function (key, value) {
            message = message.replace(key, value);
        });

        return Ext.util.Format.htmlEncode(message);
    });

    opendxp.helpers.showNotification(t("warning"), messages.join("<br>"), "info");
};

/**
 * @param {Array} errors the fields and the translation keys of what is wrong with them
 * @param {Ext.form.Basic} [form] marks the fields when the redirect was edited in a form
 */
opendxp.bundle.seo.showRedirectErrors = function (errors, form) {
    const messages = errors.map(function (error) {
        if (form && form.findField(error.field)) {
            form.findField(error.field).markInvalid(t(error.message));
        }

        return Ext.util.Format.htmlEncode(t(error.message));
    });

    opendxp.helpers.showNotification(t("error"), messages.join("<br>"), "error");
};
