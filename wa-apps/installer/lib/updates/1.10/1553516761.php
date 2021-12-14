<?php

$file_paths = array(
    wa()->getAppPath('js/layout.js', 'installer'),
    wa()->getAppPath('lib/actions/apps/installerApps.action.php', 'installer'),
    wa()->getAppPath('lib/actions/apps/installerAppsInfo.action.php', 'installer'),
    wa()->getAppPath('lib/actions/backend/installerBackend.controller.php', 'installer'),
    wa()->getAppPath('lib/actions/backend/installerBackendDefault.action.php', 'installer'),
    wa()->getAppPath('lib/actions/featured/installerFeatured.action.php', 'installer'),
    wa()->getAppPath('lib/actions/plugins/installerPlugins.action.php', 'installer'),
    wa()->getAppPath('lib/actions/plugins/installerPluginsInfo.action.php', 'installer'),
    wa()->getAppPath('lib/actions/themes/installerThemes.action.php', 'installer'),
    wa()->getAppPath('lib/actions/themes/installerThemesInfo.action.php', 'installer'),
    wa()->getAppPath('lib/actions/widgets/installerWidgets.action.php', 'installer'),
    wa()->getAppPath('lib/actions/widgets/installerWidgetsInfo.action.php', 'installer'),
    wa()->getAppPath('lib/layouts/installerBackend.layout.php', 'installer'),
    wa()->getAppPath('templates/actions/apps/Apps.html', 'installer'),
    wa()->getAppPath('templates/actions/apps/Apps.include.html', 'installer'),
    wa()->getAppPath('templates/actions/apps/AppsInfo.html', 'installer'),
    wa()->getAppPath('templates/actions/backend/BackendDefault.html', 'installer'),
    wa()->getAppPath('templates/actions/featured/Featured.html', 'installer'),
    wa()->getAppPath('templates/actions/plugins/Plugins.html', 'installer'),
    wa()->getAppPath('templates/actions/plugins/Plugins.include.html', 'installer'),
    wa()->getAppPath('templates/actions/plugins/PluginsInfo.html', 'installer'),
    wa()->getAppPath('templates/actions/plugins/PluginsView.html', 'installer'),
    wa()->getAppPath('templates/actions/themes/Themes.html', 'installer'),
    wa()->getAppPath('templates/actions/themes/Themes.include.html', 'installer'),
    wa()->getAppPath('templates/actions/themes/ThemesInfo.html', 'installer'),
    wa()->getAppPath('templates/actions/themes/ThemesView.html', 'installer'),
    wa()->getAppPath('templates/actions/widgets/Widgets.html', 'installer'),
    wa()->getAppPath('templates/actions/widgets/Widgets.include.html', 'installer'),
    wa()->getAppPath('templates/actions/widgets/WidgetsInfo.html', 'installer'),
    wa()->getAppPath('templates/layouts/Backend.html', 'installer'),
);

foreach ($file_paths as $_file_path) {
    if (file_exists($_file_path)) {
        try {
            waFiles::delete($_file_path);
        } catch (Exception $e) {
        }
    }
}

waAppConfig::clearAutoloadCache('installer');