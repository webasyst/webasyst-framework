<?php

if (!wa()->appExists('installer')) {
    class waServicesApi extends waWebasystIDApi {
        public function isConnected() {
            return false;
        }
    }
    return;
}

wa('installer');
class waServicesApi extends installerServicesApi
{
}
