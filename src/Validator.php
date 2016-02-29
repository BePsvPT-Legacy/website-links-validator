<?php

namespace Bepsvpt\WebsiteLinksValidator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Validator
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $url;

    /**
     * The host of the url.
     *
     * @var string
     */
    protected $host;

    /**
     * Checker config.
     *
     * @var array
     */
    protected $config = [];

    /**
     * The urls that already visited.
     *
     * @var array
     */
    protected $visited = [];

    /**
     * Http response code is not between 200 and 299.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Check the url links.
     *
     * @param string $url
     * @param array $config
     * @return array
     *
     * @codeCoverageIgnore
     */
    public static function validate($url, array $config = [])
    {
        $validator = new self($config);

        $validator->setUrl($url)->analysis($validator->url);

        return $validator->errors;
    }

    /**
     * Checker constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $this->parseConfig($config);

        $this->client = new Client([
            'http_errors' => false,
            'verify' => false,
            'timeout' => $this->config['timeout'],
        ]);
    }

    /**
     * Set the url and host.
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $this->normalize($url);

        $this->host = \Sabre\Uri\parse($this->url)['host'];

        return $this;
    }

    /**
     * Check the config.
     *
     * @param array $config
     * @return mixed
     */
    protected function parseConfig(array $config)
    {
        $config['deep'] = isset($config['deep']) ? intval($config['deep']) : 3;
        $config['timeout'] = isset($config['timeout']) ? floatval($config['timeout']) : 10.0;

        return $config;
    }

    /**
     * Analysis.
     *
     * @param string $url
     * @param int $deep
     * @param string $parent
     *
     * @codeCoverageIgnore
     */
    protected function analysis($url, $deep = 0, $parent = 'root')
    {
        $this->visited[] = $url;

        echo "Checking(deep {$deep}): ".str_limit($url, 64).PHP_EOL;

        try {
            $response = $this->client->get($url);

            $status = $response->getStatusCode();

            if ($status < 200 || $status >= 300) {
                $this->errors[$parent][$status][] = $url;
            } elseif (starts_with($response->getHeaderLine('content-type'), 'text/html') && $this->config['deep'] > $deep) {
                $domUrls = $this->getDomUrls($url, $response->getBody()->getContents());

                foreach ($domUrls as $domUrl) {
                    if (in_array($domUrl['url'], $this->visited) || ($deep > 0 && $domUrl['external'])) {
                        continue;
                    }

                    $this->analysis($domUrl['url'], $deep + 1, $url);
                }
            }
        } catch (RequestException $e) {
            $pos = strrchr($url, '.');

            if ($pos === false || ! in_array(substr($pos, 1), ['flv', 'pdf', 'jpg'])) {
                $this->errors[$parent][504][] = $url;
            }
        }
    }

    /**
     * Get all urls from html dom.
     *
     * @param string $baseUrl
     * @param string $string
     * @return array
     */
    public function getDomUrls($baseUrl, $string)
    {
        // Remove all html comments.
        $string = preg_replace('#<!--.*-->#isU', '', $string);

        // Store all links to $matches variable.
        preg_match_all('#(src|href)="(.*)"#iU', $string, $matches);

        $isOriginalHost = $this->host === \Sabre\Uri\parse($baseUrl)['host'];

        foreach ($matches[2] as $match) {
            try {
                $url = \Sabre\Uri\resolve($baseUrl, $match);

                $components = \Sabre\Uri\parse($url);

                if (in_array($components['scheme'], ['http', 'https'])) {
                    $urls[] = [
                        'url' => is_null($components['fragment']) ? $url : strstr($url, '#', true),
                        'external' => ! $isOriginalHost,
                    ];
                }
            } catch (\Error $e) {
            }
        }

        return isset($urls) ? $urls : [];
    }

    /**
     * Normalize url.
     *
     * @param string $url
     * @return string
     */
    protected function normalize($url)
    {
        return \Sabre\Uri\normalize($url);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
