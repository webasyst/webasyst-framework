<?php

/**
 * Application events.
 */
class developerBackendEventsAction extends developerAction
{
    public function execute()
    {
        $appId = waRequest::get('app') ?: $this->getApp();
        $this->layout->assign('page', 'events');
        $this->view->assign([
            'selectedApp' => $appId,
            'events' => $this->getAppEvents($appId),
        ]);
    }

    /**
     * @param string $appId the application identifier
     * @return array
     */
    protected function getAppEvents(string $appId): array
    {
        $cache = new waSerializeCache('events_' . $appId, 3600, $this->getApp());
        $events = $cache->get();
        if ($events) {
            return $events;
        }

        $files = [];
        $excludedDirs = ['vendor', 'vendors', 'updates', 'config', 'handlers', 'actions-mobile'];
        $appDir = $appId == 'wa' ? waConfig::get('wa_path_system') : wa($appId)->getAppPath('lib');
        foreach (glob($appDir . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $path) {
            if (!in_array(basename($path), $excludedDirs, true)) {
                $template = $path . '/{/,/*/,/*/*/}' . $appId . '*.{class,model,controller,action,cli,layout}.php';
                $files[] = glob($template, GLOB_BRACE | GLOB_NOSORT);
            }
        }
        $files = array_merge([], ...array_filter($files));

        $context = stream_context_create(['http' => ['method'=> 'GET']]);

        $regexp = '\/\*\*\s+\* @event\s+(?<descr>.+?)(\*\/|\n).+?->event\([\'"](?<name>[.\w]+)[\'"](,\s*(?<args>.+?))?\)(;|\)[^;])';
        $regexp2 = '->event\([\'"](?<name>[.\w]+)[\'"](,\s*(?<args>.+?))?\)(;|\)[^;])';
        $pattern = '/(?:(' . $regexp . ')|(' . $regexp2 . '))/suJ';

        $events = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $event) {
                    $event['name'] = trim($event['name']);
                    if (isset($events[$event['name']])) {
                        continue;
                    }
                    $event = array_filter($event, 'is_string', ARRAY_FILTER_USE_KEY);
                    $url = 'https://developers.webasyst.ru/hooks/' . $appId. '/' . $event['name'] . '/';
                    $content = @file_get_contents($url, false, $context);
                    if ($content) {
                        $content = preg_split(
                            '/(?:<p class="bigger">|<div class="plugin-dummy">.+?<div class="value">\s*)/uis',
                            $content,
                            -1,
                            PREG_SPLIT_NO_EMPTY
                        );
                        [$event['descr']] = explode('</p>', $content[1], 2);
                        [$args] = explode('</div>', $content[2], 2);
                        if ($args) {
                            $event['args'] = $args;
                        }
                    } else {
                        if ($event['name'] == $event['descr']) {
                            $event['descr'] = '';
                        }
                    }
                    $events[$event['name']] = $event;
                }
            }
        }

        $cache->set($events);

        return $events;
    }
}
