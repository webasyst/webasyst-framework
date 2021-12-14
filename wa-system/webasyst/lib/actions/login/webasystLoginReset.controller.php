<?php

/**
 * Class webasystLoginResetController
 *
 * Clear all states related to webasyst ID auth process so login page back to primary view
 *
 * @see waOAuthController
 * @see webasystLoginAction
 *
 */
class webasystLoginResetController extends waJsonController
{
    public function execute()
    {
        webasystLoginAction::clearWebasystIDAuthProcessState();
    }
}
