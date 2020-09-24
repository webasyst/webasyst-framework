<?php

return array(
    'waAPIException'                    => 'api/waAPIException.class.php',
    'waAPIController'                   => 'api/waAPIController.class.php',
    'waAPIDecorator'                    => 'api/waAPIDecorator.class.php',
    'waAPIDecoratorXML'                 => 'api/waAPIDecoratorXML.class.php',
    'waAPIDecoratorJSON'                => 'api/waAPIDecoratorJSON.class.php',
    'waAPIRightsMethod'                 => 'api/waAPIRightsMethod.class.php',
    'waAPIMethod'                       => 'api/waAPIMethod.class.php',

    'waAuth'                            => 'auth/waAuth.class.php',
    'waAuthAdapter'                     => 'auth/waAuthAdapter.class.php',
    'waOAuth2Adapter'                   => 'auth/waOAuth2Adapter.class.php',
    'waiAuth'                           => 'auth/waiAuth.interface.php',
    'waAuthException'                   => 'auth/exceptions/waAuthException.class.php',
    'waAuthConfirmEmailException'       => 'auth/exceptions/waAuthConfirmEmailException.class.php',
    'waAuthConfirmPhoneException'       => 'auth/exceptions/waAuthConfirmPhoneException.class.php',
    'waAuthInvalidCredentialsException' => 'auth/exceptions/waAuthInvalidCredentialsException.class.php',
    'waAuthRunOutOfTriesException'      => 'auth/exceptions/waAuthRunOutOfTriesException.class.php',

    'waAutoload'                        => 'autoload/waAutoload.class.php',

    'waFunctionCache'                   => 'cache/waFunctionCache.class.php',
    'waFileCache'                       => 'cache/waFileCache.class.php',
    'waMemcachedCacheAdapter'           => 'cache/adapters/waMemcachedCacheAdapter.class.php',
    'waFileCacheAdapter'                => 'cache/adapters/waFileCacheAdapter.class.php',
    'waXcacheCacheAdapter'              => 'cache/adapters/waXcacheCacheAdapter.class.php',
    'waRuntimeCache'                    => 'cache/waRuntimeCache.class.php',
    'waSerializeCache'                  => 'cache/waSerializeCache.class.php',
    'waSystemCache'                     => 'cache/waSystemCache.class.php',
    'waVarExportCache'                  => 'cache/waVarExportCache.class.php',
    'waiCache'                          => 'cache/waiCache.interface.php',

    'waAppConfig'                       => 'config/waAppConfig.class.php',
    'waConfig'                          => 'config/waConfig.class.php',
    'waRightConfig'                     => 'config/waRightConfig.class.php',
    'waSystemConfig'                    => 'config/waSystemConfig.class.php',
    'waDomainAuthConfig'                => 'config/waDomainAuthConfig.class.php',
    'waBackendAuthConfig'               => 'config/waBackendAuthConfig.class.php',
    'waAuthConfig'                      => 'config/waAuthConfig.class.php',

    'waAction'                          => 'controller/waAction.class.php',
    'waActions'                         => 'controller/waActions.class.php',
    'waController'                      => 'controller/waController.class.php',
    'waDefaultViewController'           => 'controller/waDefaultViewController.class.php',
    'waFrontController'                 => 'controller/waFrontController.class.php',
    'waJsonActions'                     => 'controller/waJsonActions.class.php',
    'waJsonController'                  => 'controller/waJsonController.class.php',
    'waUploadJsonController'            => 'controller/waUploadJsonController.class.php',
    'waLongActionController'            => 'controller/waLongActionController.class.php',
    'waMyNavAction'                     => 'controller/waMyNavAction.class.php',
    'waMyProfileAction'                 => 'controller/waMyProfileAction.class.php',
    'waViewAction'                      => 'controller/waViewAction.class.php',
    'waViewActions'                     => 'controller/waViewActions.class.php',
    'waViewController'                  => 'controller/waViewController.class.php',
    'waDispatch'                        => 'controller/waDispatch.class.php',
    'waWidget'                          => 'widget/waWidget.class.php',

    'waCurrency'                        => 'currency/waCurrency.class.php',

    'waCdn'                             => 'cdn/waCdn.class.php',

    'waCaptcha'                         => 'captcha/waCaptcha.class.php',
    'waReCaptcha'                       => 'captcha/recaptcha/waReCaptcha.class.php',
    'waPHPCaptcha'                      => 'captcha/phpcaptcha/waPHPCaptcha.class.php',

    'waModel'                           => 'database/waModel.class.php',
    'waModelExpr'                       => 'database/waModelExpr.class.php',
    'waNestedSetModel'                  => 'database/waNestedSetModel.class.php',
    'waParamsModel'                     => 'database/waParamsModel.class.php',
    'waSystemPluginModel'               => 'plugin/waSystemPluginModel.class.php',
    'waSystemPluginAction'              => 'plugin/waSystemPluginAction.class.php',
    'waSystemPluginActions'             => 'plugin/waSystemPluginActions.class.php',

    'waSMS'                             => 'sms/waSMS.class.php',
    'waSMSAdapter'                      => 'sms/waSMSAdapter.class.php',

    'waDateTime'                        => 'datetime/waDateTime.class.php',

    'waEvent'                           => 'event/waEvent.class.php',
    'waEventHandler'                    => 'event/waEventHandler.class.php',

    'waDbException'                     => 'exception/waDbException.class.php',
    'waException'                       => 'exception/waException.class.php',
    'waRightsException'                 => 'exception/waRightsException.class.php',

    'waFiles'                           => 'file/waFiles.class.php',
    'waNet'                             => 'file/waNet.class.php',
    'waArchiveTar'                      => 'file/waArchiveTar.class.php',
    'waTheme'                           => 'file/waTheme.class.php',

    'waLayout'                          => 'layout/waLayout.class.php',

    'waiLocaleAdapter'                  => 'locale/waiLocaleAdapter.interface.php',
    'waGettext'                         => 'locale/waGettext.class.php',
    'waLocale'                          => 'locale/waLocale.class.php',
    'waLocaleAdapter'                   => 'locale/waLocaleAdapter.class.php',

    'waLocaleParseEntityInterface' => 'locale/parse/entity/waLocaleParseEntity.interface.php',
    'waLocaleParseEntity'          => 'locale/parse/entity/waLocaleParseEntity.class.php',
    'waLocaleParseEntityWebasyst'  => 'locale/parse/entity/waLocaleParseEntityWebasyst.class.php',
    'waLocaleParseEntityApp'       => 'locale/parse/entity/waLocaleParseEntityApp.class.php',
    'waLocaleParseEntityPlugins'   => 'locale/parse/entity/waLocaleParseEntityPlugins.class.php',
    'waLocaleParseEntityWidgets'   => 'locale/parse/entity/waLocaleParseEntityWidgets.class.php',
    'waLocaleParseEntityWaPlugins' => 'locale/parse/entity/waLocaleParseEntityWaPlugins.class.php',
    'waLocaleParseEntityWaWidgets' => 'locale/parse/entity/waLocaleParseEntityWaWidgets.class.php',
    'waLocaleParseEntityTheme'     => 'locale/parse/entity/waLocaleParseEntityTheme.class.php',
    'waGettextParser'              => 'locale/waGettextParser.class.php',

    // <LOGIN MODULE>

    // <classes>

    // login
    'waLoginFormRenderer'          => 'login/classes/waLoginFormRenderer.class.php',
    'waLoginForm'                  => 'login/classes/login/waLoginForm.class.php',
    'waBackendLoginForm'           => 'login/classes/login/waBackendLoginForm.class.php',
    'waFrontendLoginForm'          => 'login/classes/login/waFrontendLoginForm.class.php',

    // forgotpassword
    'waForgotPasswordForm'              => 'login/classes/forgotpassword/waForgotPasswordForm.class.php',
    'waBackendForgotPasswordForm'       => 'login/classes/forgotpassword/waBackendForgotPasswordForm.class.php',
    'waFrontendForgotPasswordForm'      => 'login/classes/forgotpassword/waFrontendForgotPasswordForm.class.php',

    // setpassword
    'waSetPasswordForm'                 => 'login/classes/setpassword/waSetPasswordForm.class.php',
    'waFrontendSetPasswordForm'         => 'login/classes/setpassword/waFrontendSetPasswordForm.class.php',
    'waBackendSetPasswordForm'          => 'login/classes/setpassword/waBackendSetPasswordForm.class.php',

    // </classes>

    // <actions>

    // login
    'waLoginModuleController'           => 'login/actions/waLoginModule.controller.php',
    'waBaseLoginAction'                 => 'login/actions/login/waBaseLogin.action.php',
    'waBackendLoginAction'              => 'login/actions/login/waBackendLogin.action.php',
    'waLoginAction'                     => 'login/actions/login/waLogin.action.php',

    // forgotpassword
    'waBaseForgotPasswordAction'        => 'login/actions/forgotpassword/waBaseForgotPassword.action.php',
    'waBackendForgotPasswordAction'     => 'login/actions/forgotpassword/waBackendForgotPassword.action.php',
    'waForgotPasswordAction'            => 'login/actions/forgotpassword/waForgotPassword.action.php',

    // </actions>

    // </LOGIN MODULE>

    'waAppPayment'                      => 'payment/waAppPayment.class.php',
    'waOrder'                           => 'payment/waOrder.class.php',
    'waPayment'                         => 'payment/waPayment.class.php',

    'waRequest'                         => 'request/waRequest.class.php',
    'waRequestFile'                     => 'request/waRequestFile.class.php',
    'waRequestFileIterator'             => 'request/waRequestFileIterator.class.php',

    'waResponse'                        => 'response/waResponse.class.php',

    'waSignupAction'                    => 'signup/actions/waSignup.action.php',
    'waSignupForm'                      => 'signup/classes/waSignupForm.class.php',

    'waSessionStorage'                  => 'storage/waSessionStorage.class.php',
    'waStorage'                         => 'storage/waStorage.class.php',

    'waAuthUser'                        => 'user/waAuthUser.class.php',
    'waUser'                            => 'user/waUser.class.php',

    'waArrayObject'                     => 'util/waArrayObject.class.php',
    'waArrayObjectDiff'                 => 'util/waArrayObjectDiff.class.php',
    'waLazyDisplay'                     => 'util/waLazyDisplay.class.php',
    'waCSV'                             => 'util/waCSV.class.php',
    'waHtmlControl'                     => 'util/waHtmlControl.class.php',
    'waString'                          => 'util/waString.class.php',
    'waUtils'                           => 'util/waUtils.class.php',

    'waDateValidator'                   => 'validator/waDateValidator.class.php',
    'waEmailValidator'                  => 'validator/waEmailValidator.class.php',
    'waLoginValidator'                  => 'validator/waLoginValidator.class.php',
    'waNumberValidator'                 => 'validator/waNumberValidator.class.php',
    'waPhoneNumberValidator'            => 'validator/waPhoneNumberValidator.class.php',
    'waTimeValidator'                   => 'validator/waTimeValidator.class.php',
    'waRegexValidator'                  => 'validator/waRegexValidator.class.php',
    'waStringValidator'                 => 'validator/waStringValidator.class.php',
    'waUrlValidator'                    => 'validator/waUrlValidator.class.php',
    'waValidator'                       => 'validator/waValidator.class.php',

    'waIdna'                            => 'vendors/idna/waIdna.class.php',
    'Smarty'                            => 'vendors/smarty3/Smarty.class.php',

    'waVerificationChannelModel'        => 'verification/models/waVerificationChannel.model.php',
    'waVerificationChannelParamsModel'  => 'verification/models/waVerificationChannelParams.model.php',
    'waVerificationChannelAssetsModel'  => 'verification/models/waVerificationChannelAssets.model.php',
    'waVerificationChannel'             => 'verification/classes/waVerificationChannel.class.php',
    'waVerificationChannelEmail'        => 'verification/classes/waVerificationChannelEmail.class.php',
    'waVerificationChannelSMS'          => 'verification/classes/waVerificationChannelSMS.class.php',
    'waVerificationChannelNull'         => 'verification/classes/waVerificationChannelNull.class.php',

    'waSmarty3View'                     => 'view/waSmarty3View.class.php',
    'waView'                            => 'view/waView.class.php',
    'waViewHelper'                      => 'view/waViewHelper.class.php',
    'waAppViewHelper'                   => 'view/waAppViewHelper.class.php',

    'waWorkflow'                        => 'workflow/waWorkflow.class.php',
    'waWorkflowAction'                  => 'workflow/waWorkflowAction.class.php',
    'waWorkflowEntity'                  => 'workflow/waWorkflowEntity.class.php',
    'waWorkflowState'                   => 'workflow/waWorkflowState.class.php',

    'waSystem'                          => 'waSystem.class.php',

    'waPageModel'                       => 'page/models/waPage.model.php',
    'waPageParamsModel'                 => 'page/models/waPageParams.model.php',
    'waPageAction'                      => 'page/actions/waPage.action.php',
    'waPageActions'                     => 'page/actions/waPage.actions.php',

    'waDesignActions'                   => 'design/actions/waDesign.actions.php',
    'waPluginsActions'                  => 'plugin/actions/waPlugins.actions.php',

    'waMapAdapter'                      => 'map/waMapAdapter.class.php',
    'waDisabledMapAdapter'              => 'map/adapters/waDisabledMapAdapter.class.php',

    // <WEBASYST ID MODULE>

    'waWebasystIDConfig'                       => 'waid/waWebasystIDConfig.class.php',
    'waWebasystIDException'                    => 'waid/exceptions/waWebasystIDException.class.php',
    'waWebasystIDAuthException'                => 'waid/exceptions/waWebasystIDAuthException.class.php',
    'waWebasystIDAccessDeniedAuthException'    => 'waid/exceptions/waWebasystIDAccessDeniedAuthException.class.php',
    'waWebasystIDClientManager'                => 'waid/waWebasystIDClientManager.class.php',
    'waWebasystIDAuthAdapter'                  => 'waid/waWebasystIDAuthAdapter.class.php',
    'waWebasystIDAuth'                         => 'waid/waWebasystIDAuth.class.php',
    'waWebasystIDWAAuth'                       => 'waid/waWebasystIDWAAuth.class.php',
    'waWebasystIDSiteAuth'                     => 'waid/waWebasystIDSiteAuth.class.php',
    'waWebasystIDWAAuthController'             => 'waid/waWebasystIDWAAuthController.class.php',
    'waWebasystIDAccessTokenManager'           => 'waid/waWebasystIDAccessTokenManager.class.php',
    'waWebasystIDApi'                          => 'waid/waWebasystIDApi.class.php',
    'waWebasystIDCustomerCenterAuthController' => 'waid/waWebasystIDCustomerCenterAuth.controller.php',
    'waWebasystIDUserInviting'                 => 'waid/waWebasystIDUserInviting.class.php'

    // </WEBASYST ID MODULE>
);

