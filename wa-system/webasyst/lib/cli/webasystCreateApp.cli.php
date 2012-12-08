<?php

class webasystCreateAppCli extends waCliController
{
    public function execute()
    {
        $app_id = waRequest::param();
        $app_path = wa()->getAppPath(null, $app_id);
        $this->create($app_id, $app_path);
    }

    protected function create($app_id, $path)
    {
        mkdir($path.'css');
        touch($path.'css/'.$app_id.'.css');
        mkdir($path.'js');
        touch($path.'js/'.$app_id.'.js');
        mkdir($path.'img');
        // lib
        mkdir($path.'lib');
        mkdir($path.'lib/actions');
        mkdir($path.'lib/actions/backend');
        mkdir($path.'lib/models');
        // config
        mkdir($path.'lib/config');
        // app description
        file_put_contents($path.'lib/config/app.php', "<?php

return array(
    'name' => '".ucfirst($app_id)."',
    'icon' => 'img/".$app_id.".gif',
    'version' => '0.1',
);
");
        // templates
        mkdir($path.'templates');
        mkdir($path.'templates/actions');
        mkdir($path.'templates/actions/backend');
        // locale

        // themes
        mkdir($path.'themes');
        mkdir($path.'themes/default');
    }
}