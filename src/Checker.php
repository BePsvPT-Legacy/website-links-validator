<?php

namespace Bepsvpt\WebsiteUrlChecker;

use GuzzleHttp\Client;

class Checker
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
     */
    public static function check($url, array $config = [])
    {
        $checker = new self($url, $config);

        $checker->analysis($checker->url);

        return $checker->errors;
    }

    /**
     * Checker constructor.
     *
     * @param string $url
     * @param array $config
     */
    public function __construct($url, array $config = [])
    {
        $this->init($url);

        $this->config = $this->parseConfig($config);

        $this->client = new Client([
            'http_errors' => false,
            'verify' => false,
            'timeout' => $this->config['timeout'],
        ]);
    }

    /**
     * Normalize the url and set the host.
     *
     * @param string $url
     */
    protected function init($url)
    {
        $this->url = $this->normalize($url);

        $this->host = \Sabre\Uri\parse($this->url)['host'];
    }

    /**
     * Check the config.
     *
     * @param array $config
     * @return mixed
     */
    protected function parseConfig(array $config)
    {
        $config['deep'] = isset($config['deep']) ? $config['deep'] : 3;
        $config['timeout'] = isset($config['timeout']) ? $config['timeout'] : 10.0;

        return $config;
    }

    /**
     * Analysis.
     *
     * @param string $url
     * @param int $deep
     */
    protected function analysis($url, $deep = 0)
    {
        $this->visited[] = $url;

        echo "Checking(deep {$deep}): ".str_limit($url, 64).PHP_EOL;

        try {
            $response = $this->client->get($url);

            $status = $response->getStatusCode();

            if ($status < 200 || $status >= 300) {
                $this->errors[$status][] = $url;
            } elseif ($this->config['deep'] > $deep) {
                $domUrls = $this->getDomUrls($url, $response->getBody()->getContents());

                foreach ($domUrls as $domUrl) {
                    if (in_array($domUrl['url'], $this->visited) || ($deep > 0 && $domUrl['external'])) {
                        continue;
                    }

                    $this->analysis($domUrl['url'], $deep + 1);
                }
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $pos = strrchr($url, '.');

            if ($pos === false || ! in_array(substr($pos, 1 ), ['flv', 'pdf', 'jpg'])) {
                $this->errors[504][] = $url;
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
    protected function getDomUrls($baseUrl, $string)
    {
        $string = preg_replace('#<!--.*-->#isU', '', $string);

        preg_match_all('#(src|href)="(.*)"#iU', $string, $matches);

        foreach ($matches[2] as $match) {
            try {
                $url = \Sabre\Uri\resolve($baseUrl, $match);

                $components = \Sabre\Uri\parse($url);

                if (in_array($components['scheme'], ['http', 'https'])) {
                    $urls[] = [
                        'url' => is_null($components['fragment']) ? $url : strstr($url, '#', true),
                        'external' => $this->host !== $components['host'],
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
}
