<?php

if (!wa()->appExists('installer')) {
    class waServicesApi extends waWebasystIDApi {
        public function isBrokenConnection() {
            return false;
        }
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
