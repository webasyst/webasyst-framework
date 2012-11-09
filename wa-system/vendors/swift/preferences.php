<?php

/****************************************************************************/
/*                                                                          */
/* YOU MAY WISH TO MODIFY OR REMOVE THE FOLLOWING LINES WHICH SET DEFAULTS  */
/*                                                                          */
/****************************************************************************/

// Sets the default charset so that setCharset() is not needed elsewhere
Swift_Preferences::getInstance()->setCharset('utf-8');

// Without these lines the default caching mechanism is "array" but this uses a lot of memory.
// If possible, use a disk cache to enable attaching large attachments etc.
// You can override the default temporary directory by setting the TMPDIR environment variable.
$temp_path = waConfig::get('wa_path_cache').'/temp/swift';
if (!file_exists($temp_path)) {
    waFiles::create($temp_path);
}
if (is_writable($temp_path)) {
    Swift_Preferences::getInstance()-> setTempDir($temp_path)-> setCacheType('disk');
}

Swift_Preferences::getInstance()->setQPDotEscape(false);
