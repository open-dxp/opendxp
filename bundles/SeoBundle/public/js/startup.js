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


opendxp.registerNS("opendxp.bundle.seo.startup");

/**
 * @private
 */
opendxp.bundle.seo.startup = Class.create({
    initialize: function () {
        document.addEventListener(opendxp.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
        document.addEventListener(opendxp.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = opendxp.globalmanager.get('user');
        const perspectiveCfg = opendxp.globalmanager.get("perspective");

        if (perspectiveCfg.inToolbar("marketing") && perspectiveCfg.inToolbar("marketing.seo")) {
            let seoMenu = [];

            if (user.isAllowed("documents") && user.isAllowed("seo_document_editor") && perspectiveCfg.inToolbar("marketing.seo.documents")) {
                seoMenu.push({
                    text: t("seo_document_editor"),
                    iconCls: "opendxp_nav_icon_document_seo",
                    itemId: 'opendxp_menu_marketing_seo_document_editor',
                    handler: this.showDocumentSeo
                });
            }

            if (this.canEditSiteDeclarations(user) && perspectiveCfg.inToolbar('marketing.seo.robots')) {
                seoMenu.push({
                    text: t('site_declarations'),
                    iconCls: "opendxp_nav_icon_robots",
                    itemId: 'opendxp_menu_marketing_seo_robots_txt',
                    handler: this.showSiteDeclarations
                });
            }

            if (user.isAllowed("http_errors") && perspectiveCfg.inToolbar("marketing.seo.httperrors")) {
                seoMenu.push({
                    text: t("http_errors"),
                    iconCls: "opendxp_nav_icon_httperrorlog",
                    itemId: 'opendxp_menu_marketing_seo_http_errors',
                    handler: this.showHttpErrorLog
                });
            }

            // get index of marketing.targeting
            if (seoMenu.length > 0) {
                menu.marketing.items.push({
                    text: t("search_engine_optimization"),
                    iconCls: "opendxp_nav_icon_seo",
                    priority: 25,
                    itemId: 'opendxp_menu_marketing_seo',
                    hideOnClick: false,
                    menu: {
                        cls: "opendxp_navigation_flyout",
                        shadow: false,
                        items: seoMenu
                    }
                });
            }
        }

        if (menu.extras && user.isAllowed("redirects") && perspectiveCfg.inToolbar("extras.redirects")) {
            menu.extras.items.push({
                text: t("redirects"),
                iconCls: "opendxp_nav_icon_redirects",
                priority: 5,
                itemId: 'opendxp_menu_extras_redirects',
                handler: this.editRedirects
            });
        }

    },

    showDocumentSeo: function () {
        try {
            opendxp.globalmanager.get("bundle_seo_seopanel").activate();
        }
        catch (e) {
            opendxp.globalmanager.add("bundle_seo_seopanel", new opendxp.bundle.seo.seopanel());
        }
    },

    canEditSiteDeclarations: function (user) {
        return opendxp.settings.site_declarations.length > 0
            && (user.isAllowed('site_declarations') || user.isAllowed('robots.txt'));
    },

    showSiteDeclarations: function () {
        try {
            opendxp.globalmanager.get('bundle_seo_site_declarations').activate();
        }
        catch (e) {
            opendxp.globalmanager.add('bundle_seo_site_declarations', new opendxp.bundle.seo.siteDeclarations());
        }
    },

    showHttpErrorLog: function () {
        try {
            opendxp.globalmanager.get("bundle_seo_http_error_log").activate();
        }
        catch (e) {
            opendxp.globalmanager.add("bundle_seo_http_error_log", new opendxp.bundle.seo.httpErrorLog());
        }
    },

    registerKeyBinding: function(e) {
        const user = opendxp.globalmanager.get('user');

        if (user.isAllowed("documents") && user.isAllowed("seo_document_editor")) {
            opendxp.helpers.keyBindingMapping.seoDocumentEditor = function() {
                opendxpBundleSeo.showDocumentSeo();
            }
        }

        if (this.canEditSiteDeclarations(user)) {
            opendxp.helpers.keyBindingMapping.robots = function() {
                opendxpBundleSeo.showSiteDeclarations();
            }
        }

        if (user.isAllowed("http_errors")) {
            opendxp.helpers.keyBindingMapping.httpErrorLog = function() {
                opendxpBundleSeo.showHttpErrorLog();
            }
        }

    },

    editRedirects: function () {

        try {
            opendxp.globalmanager.get("redirects").activate();
        }
        catch (e) {
            opendxp.globalmanager.add("redirects", new opendxp.settings.redirects());
        }
    },
})

const opendxpBundleSeo = new opendxp.bundle.seo.startup();