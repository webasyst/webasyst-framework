<?php

$files = [
  'lib/actions/siteForgotpassword.action.php',
  'lib/actions/siteLogin.action.php',
  'lib/actions/siteOAuth.controller.php',
  'lib/actions/siteSignup.action.php',
  'lib/actions/siteMyNav.action.php',
  'lib/actions/backend/siteBackendLoc.action.php',
  'lib/actions/backend/siteBackendLoc.controller.php',
  'lib/actions/blocks/siteBlocks.action.php',
  'lib/actions/blocks/siteBlocksDelete.controller.php',
  'lib/actions/blocks/siteBlocksSort.controller.php',
  'lib/actions/files/siteFiles.action.php',
  'lib/actions/files/siteFilesAddFolder.controller.php',
  'lib/actions/files/siteFilesDelete.controller.php',
  'lib/actions/files/siteFilesDownload.controller.php',
  'lib/actions/files/siteFilesList.controller.php',
  'lib/actions/files/siteFilesMove.controller.php',
  'lib/actions/files/siteFilesRename.controller.php',
  'lib/actions/files/siteFilesUpload.controller.php',
  'lib/actions/files/siteFilesUploadimage.controller.php',
  'lib/actions/pages/sitePages.actions.php',
  'lib/actions/personal/sitePersonal.action.php',
  'lib/actions/personal/sitePersonalApp.action.php',
  'lib/actions/personal/sitePersonalAppEnable.controller.php',
  'lib/actions/personal/sitePersonalAppMove.controller.php',
  'lib/actions/personal/sitePersonalAuthEnable.controller.php',
  'lib/actions/personal/sitePersonalProfile.action.php',
  'lib/actions/personal/sitePersonalProfileSave.controller.php',
  'lib/actions/personal/sitePersonalSettings.action.php',
  'lib/actions/personal/sitePersonalSettingsSave.controller.php',
  'lib/actions/plugins/sitePlugins.actions.php',
  'lib/actions/routing/siteRouting.action.php',
  'lib/actions/routing/siteRoutingDelete.controller.php',
  'lib/actions/routing/siteRoutingEdit.action.php',
  'lib/actions/routing/siteRoutingSave.controller.php',
  'lib/actions/routing/siteRoutingSort.controller.php',
  'lib/actions/settings/siteSettings.action.php',
  'lib/actions/settings/siteSettingsDelete.controller.php',
  'lib/actions/settings/siteSettingsSave.controller.php',
  'lib/layouts/siteDefault.layout.php',
];

$app_path = $this->getAppPath().'/';

foreach ($files as $file) {
    if (!file_exists($app_path.$file)) {
        continue;
    }
    waFiles::delete($app_path.$file);
}
