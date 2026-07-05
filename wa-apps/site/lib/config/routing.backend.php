<?php

return array(
    // backend URL after `/webasyst/site/`            => 'module/action' (empty action means 'default')
    'map/overview/?'                                  => 'map/overview',
    'settings/?'                                      => 'configure/',
    'themes/?'                                        => 'themes/',
    'plugins/?'                                       => 'extensions/',
    'files/?'                                         => 'filemanager/',
    'variables/'                                      => 'variables/',
    'files/<files_path>/?'                            => 'filemanager/',
    'htmleditor/page/<page_id:\d+>/'                  => 'htmleditor/',
    'editor/page/<page_id:\d+>/'                      => 'editor/',
    'editor/<domain_id:\d+>/<page>/'                  => 'editor/',
    'editor/<domain_id:\d+>/'                         => 'editor/',
    ''                                                => 'backend/',
);
