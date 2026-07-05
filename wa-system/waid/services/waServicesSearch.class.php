<?php
/**
 * Uses (paid) WAID API to perform search over *public* frontend pages indexed on given domain,
 * then make AI answer user's question using documents found.
 *
 * Objects of this class are designed for one-time use. Object will perfom several API calls over its lifetime
 * and remember results in class fields. Subsequent call of the same method will return existing result
 * without any additional API calls. Trying to use same object for a different query throws exception.
 */
class waServicesSearch
{
    public ?string $query;

    public ?string $improved_query;
    public ?array $links;
    public ?array $documents;
    public ?string $response = null;

    public array $options = [];

    public function __construct(array $options=[])
    {
        $this->query = $options['query'] ?? null;
        $this->links = $options['links'] ?? null;
        $this->documents = $options['documents'] ?? null;
        $this->options = $options;
        $this->options['improve_query'] = '1';
        if (isset($options['improve_query'])) {
            $this->options['improve_query'] = $options['improve_query'] ? '1' : '';
        }
    }

    /**
     * Using WA API performs search over public frontend pages indexed on given domain,
     * then uses AI to answer query using documents found.
     */
    public function getAiResponse(?string $query=null, ?array $documents=null): string
    {
        $this->setAndCheckQuery($query);

        if ($this->documents !== null && $documents !== null) {
            throw new waException("Documents can only be set once");
        }
        if ($documents !== null) {
            if (!$documents) {
                throw new waException("Documents are required");
            }
            $this->documents = $documents;
        }

        if ($this->response === null) {
            if ($this->documents === null) {
                $this->getRelevantLinks();
                $this->getDocumentsByLinks();
            }
            if (!$this->documents) {
                throw new waException(_ws("Unable to find relevant documents."));
            }

            $query = $this->improved_query ?? $this->query;

            $api = new waServicesApi();
            if (!$api->isConnected()) {
                throw new waException(_ws('Webasyst ID must be connected in order to use the Webasyst search service.'));
            }
            $api_call = $api->serviceCall('AI', [
                'facility' => 'documents_question',
                'objective' => $this->query,
                'documents' => array_column($this->documents, 'text'),
                'text_format' => ifset($this->options, 'text_format', 'markdown'),
            ], 'POST', [
                'timeout' => 30,
            ]);

            $response = ifset($api_call, 'response', 'content', null);
            if ($response === null) {
                switch(ifset($api_call, 'response', 'error', '')) {
                    case 'provider_censored':
                    case 'provider_error':
                    default:
                        throw new waException(ifset($api_call, 'response', 'error_description', _ws('Service temporarily unavailable. Please try again later.')));
                }
            }
            $this->response = strval($response);
        }

        return $this->response;
    }

    /**
     * Makes API query to look for public frontend pages available at domain set in $this->options['domain'].
     * Domain is required. Search is currently limited to a single domain and all its subdomains.
     */
    public function getRelevantLinks(?string $query=null): array
    {
        $this->setAndCheckQuery($query);

        if ($this->links === null) {
            if (empty($this->options['domain'])) {
                throw new waException("Domain is required");
            }

            $api = new waServicesApi();
            if (!$api->isConnected()) {
                throw new waException('Webasyst ID must be connected in order to use the Webasyst search service.');
            }
            $api_call = $api->serviceCall('AI', [
                'facility' => 'search',
                'domain' => $this->options['domain'],
                'improve_query' => $this->options['improve_query'],
                'query' => $this->query,
            ], 'POST', [
                'timeout' => 30,
            ]);

            $this->improved_query = ifset($api_call, 'response', 'improved_query', false);
            $this->links = ifset($api_call, 'response', 'links', null);
            if ($this->links === null) {
                $this->links = [];
                switch(ifset($api_call, 'response', 'error', '')) {
                    case 'provider_censored':
                    case 'provider_error':
                    default:
                        throw new waException(ifset($api_call, 'response', 'error_description', _ws('Service temporarily unavailable. Please try again later.')));
                }
            }
        }

        return $this->links;
    }

    /**
     * Given a list of links (as returned by $this->getRelevantLinks() or saved in $this->links by previous call)
     * will search for content in local database and return a list of documents with content.
     * This does not make any API calls.
     */
    public function getDocumentsByLinks(?array $links=null): array
    {
        if ($this->links !== null && $links !== null) {
            throw new waException("Links can only be set once");
        }
        if ($links !== null) {
            $this->links = $links;
        }
        if ($this->links === null) {
            throw new waException("Links are required");
        }
        if ($this->documents === null) {
            $this->documents = [];
            $event_params = [
                'links' => [],
            ];
            foreach ($this->links as $link) {
                list($settlement, $app_route, $url_params) = wa()->getRouting()->dispatchFullUrl($link['url']);
                if (!$settlement || !$app_route) {
                    continue;
                }
                $handle = sprintf('%s/%s/%s', ifset($settlement, 'app', ''), ifset($app_route, 'module', ''), ifset($app_route, 'action', ''));
                $event_params['links'][] = [
                    'handle' => $handle,
                    'url' => $link['url'],
                    'domain' => $settlement['_domain'],
                    'settlement' => $settlement,
                    'app_route' => $app_route,
                    'url_params' => $url_params,
                    'result' => null,
                ];
            }
            if ($event_params['links']) {
                wa('webasyst')->event('search_content', $event_params);
                foreach ($event_params['links'] as $link) {
                    if (!empty($link['result'])) {
                        $this->documents[] = [
                            'url' => $link['url'],
                            'text' => $link['result'],
                        ];
                    }
                }
            }
        }

        return $this->documents;
    }

    protected function setAndCheckQuery(?string $query)
    {
        if ($query === null) {
            return;
        }
        if ($this->query && $this->query !== $query) {
            throw new waException("Query can only be set once");
        }
        $this->query = $query;
        if (empty($this->query)) {
            throw new waException("Query is required");
        }
    }
}
