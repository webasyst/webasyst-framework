<?php

class contactsHelper {
    public static function getAppPath($path) {
        /** $wa->getApp() returns `contacts` or `contacts_full` depending on current environment. */
        return wa()->getAppPath($path, wa()->getApp());
    }
}
