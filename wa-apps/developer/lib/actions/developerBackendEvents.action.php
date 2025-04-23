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

    private function getAppEvents(string $appId): array
    {
        $cache = new waSerializeCache('events_' . $appId, 3600 * 12, $this->getApp());
        $events = $cache->get();
        if ($events) {
            return $events;
        }

        $files = [];
        $excludedDirs = ['vendor', 'vendors', 'updates', 'handlers'];
        $appDir = $appId == 'wa' ? waConfig::get('wa_path_system') : wa($appId)->getAppPath('lib');
        foreach (glob($appDir . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $path) {
            if (!in_array(basename($path), $excludedDirs)) {
                $template = $path . '/{/,/*/,/*/*/}' . $appId . '*.{class,trait,model,controller,action,layout,cli}.php';
                $files[] = glob($template, GLOB_BRACE | GLOB_NOSORT);
            }
        }
        $files = array_merge([], ...array_filter($files));

        $context = stream_context_create(['http' => ['method'=> 'GET']]);

        $events = [];
        foreach ($files as $file) {
            $fileEvents = $this->parseEvents(file_get_contents($file));
            foreach ($fileEvents as $eventName => &$event) {
                if (isset($events[$eventName])) {
                    // Use event called with arguments if it's possible
                    if (empty($events[$eventName]['args'])) {
                        $events[$eventName] = $event;
                    }
                    continue;
                }

                // Parses description from WA site
                $url = 'https://developers.webasyst.ru/hooks/' . $appId. '/' . $eventName . '/';
                $content = @file_get_contents($url, false, $context);
                if ($content) {
                    $content = preg_split(
                        '/(?:<p class="bigger">|<div class="plugin-dummy">.+?<div class="custom-mb-24">\s*)/uis',
                        $content,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
                    [$event['descr']] = explode('</p>', $content[1], 2);
                    [$args] = explode('</div>', $content[2], 2);
                    if ($args) {
                        $event['args'] = $args;
                    }
                }

                $events[$eventName] = $event;
            }
            unset($event);
        }

        ksort($events);

        $cache->set($events);

        return $events;
    }

    private function parseEvents(string $code): array
    {
        // Quick check
        if (!strpos($code, '->event(') && !strpos($code, '@event')) {
            return [];
        }

        $events = [];

        $tokens = token_get_all($code);
        foreach ($tokens as &$token) {
            if (!is_array($token)) {
                $token = [0, $token];
            }
        }
        unset($token);

        foreach ($tokens as $i => $token) {
            if (
                !$this->checkToken($token, T_OBJECT_OPERATOR)
                || !$this->checkToken($tokens[$i + 1], 'event')
                || !$this->checkToken($tokens[$i + 2], '(')
            ) {
                continue;
            }

            $tokens2 = array_slice($tokens, $i + 3);
            $openBrace = 1;
            foreach ($tokens2 as $i2 => $token2) {
                if ($this->checkToken($token2, '(')) {
                    $openBrace++;
                } elseif ($this->checkToken($token2, ')')) {
                    $openBrace--;
                    if ($openBrace == 0) {
                        break;
                    }
                }
            }
            $tokens2 = array_slice($tokens2, 0, $i2);
            if (count($tokens2) > 1) {
                $tokens2 = $this->normalizeEventName($tokens2);
            }

            $data = explode(',', implode('', array_column($tokens2, 1)), 2);

            $name = trim($data[0], ' "\'');
            if (in_array($name, ['', '*', '*.*'])) {
                continue;
            }

            $args = isset($data[1]) ? str_replace(["\r\n","\n"], '', trim($data[1])) : '';

            $events[$name] = ['name' => $name, 'args' => $args, 'descr' => ''];
        }

        // Finds events in comments
        foreach ($tokens as $token) {
            if (!$this->checkToken($token, T_DOC_COMMENT)) {
                continue;
            }
            if (preg_match_all('/@event\s+(?<name>[._\w]+)/', $token[1], $matches)) {
                foreach ($matches['name'] as $name) {
                    if (!isset($events[$name])) {
                        $events[$name] = ['name' => $name, 'args' => '', 'descr' => ''];
                    }
                }
            }
        }

        return $events;
    }

    private function normalizeEventName(array $tokens): array
    {
        $isArray = false;
        foreach ($tokens as $token) {
            if ($this->checkToken($token, T_WHITESPACE)) {
                continue;
            }
            if ($this->checkToken($token, [T_ARRAY, '['])) {
                $isArray = true;
            }
            break;
        }

        $eventName = ['*'];
        $openBrace = 0;
        $index = 0;
        if ($isArray) {
            // Event name is array
            foreach ($tokens as $i => $token) {
                if ($this->checkToken($token, [']', ')'])) {
                    $openBrace--;
                    if ($openBrace == 0) {
                        break;
                    }
                } elseif ($this->checkToken($token, ['[', '('])) {
                    $openBrace++;
                } elseif ($openBrace == 1 && $this->checkToken($token, ',')) {
                    $index++;
                    $eventName[$index] = '*';
                } elseif ($openBrace == 1 && $this->checkToken($token, T_CONSTANT_ENCAPSED_STRING)) {
                    $eventName[$index] = trim($token[1], '"\'');
                }
            }
            $tokens = array_slice($tokens, $i);
            $tokens[0] = [0, implode('.', $eventName)];
        } else {
            // Event name is composite string
            foreach ($tokens as $i => $token) {
                if ($this->checkToken($token, [']', ')'])) {
                    $openBrace--;
                } elseif ($this->checkToken($token, ['[', '('])) {
                    $openBrace++;
                } elseif ($openBrace == 0 && $this->checkToken($token, '.')) {
                    $index++;
                    $eventName[$index] = '*';
                } elseif ($openBrace == 0 && $this->checkToken($token, T_CONSTANT_ENCAPSED_STRING)) {
                    $eventName[$index] = trim($token[1], '"\'');
                } elseif ($openBrace == 0 && $this->checkToken($token, ',')) {
                    break;
                }
            }
            $tokens = array_slice($tokens, count($tokens) - 1 == $i ? $i : $i - 1);
            $tokens[0] = [0, implode('', $eventName)];
        }

        return $tokens;
    }

    /**
     * @param array $token
     * @param int|string|array $kind
     * @return bool
     */
    private function checkToken(array $token, $kind): bool
    {
        if (!is_array($kind)) {
            $kind = [$kind];
        }

        return in_array($token[0], $kind, true) || in_array($token[1], $kind, true);
    }
}
