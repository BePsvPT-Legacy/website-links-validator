<?php

namespace Bepsvpt\WebsiteUrlChecker;

use League\CLImate\CLImate;

class Console
{
    /**
     * @var CLImate
     */
    protected $console;

    /**
     * Console constructor.
     */
    public function __construct()
    {
        $this->console = new CLImate;
    }

    /**
     * Start checker.
     *
     * @return void
     */
    public function start()
    {
        $config = $this->getConfig();

        $results = [];

        foreach ($this->getUrls() as $url) {
            $results[$url] = Checker::check($url, $config);
        }

        $this->outputResult($config['savePath'], $results);
    }

    /**
     * Prompt user to provide the config data.
     *
     * @return array
     */
    protected function getConfig()
    {
        return [
            'deep' => intval($this->console->input('How deep should we check [3]:')->prompt()) ?: 3,
            'timeout' => floatval($this->console->input('The http timeout seconds [10.0]:')->prompt()) ?: 10.0,
            'savePath' => $this->console->input('The path to save the result file (use stdout to show to console) [./]:')->prompt() ?: './',
        ];
    }

    /**
     * Get the urls that should check.
     *
     * @return array
     */
    protected function getUrls()
    {
        $options = ['file', 'console'];

        $response = $this->console->radio('Please choose the url source:', $options)->prompt();

        switch ($response) {
            case 'file':
                return $this->getSourceFromFile();

            case 'console':
                return $this->getSourceFromConsole();

            default:
                $this->console->to('error')->red('Incorrect input.');

                exit();
        }
    }

    /**
     * Prompt user to provide the file path.
     *
     * @return array
     */
    protected function getSourceFromFile()
    {
        $path = realpath($this->console->input('Please enter the file path:')->prompt());

        if (false === $path || ! file_exists($path)) {
            $this->console->red('File not found.');

            exit();
        }

        return $this->loadUrlsFromFile($path);
    }

    /**
     * Load urls from file.
     *
     * @param string $path
     * @return array
     */
    protected function loadUrlsFromFile($path)
    {
        $urls = [];

        $fp = fopen($path, 'r');

        while ($url = fgets($fp)) {
            $url = trim($url);

            if (false !== filter_var($url, FILTER_VALIDATE_URL)) {
                $urls[] = $url;
            }
        }

        fclose($fp);

        return $urls;
    }

    /**
     * Prompt user to provide urls.
     *
     * @return array
     */
    protected function getSourceFromConsole()
    {
        $urls = [];

        while (true) {
            $url = $this->console->input('Please enter the url:')->prompt();

            if (0 === strlen($url)) {
                break;
            } elseif (false === filter_var($url, FILTER_VALIDATE_URL)) {
                $this->console->red('Invalid url, please enter the full url.');
            } else {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * Output the result.
     *
     * @param string $path
     * @param array $results
     */
    protected function outputResult($path, $results)
    {
        if ('stdout' === $path) {
            $this->console->json($results);
        } else {
            if (! ends_with($path, '/')) {
                $path = $path.'/';
            }

            file_put_contents($path.'result.json', json_encode($results));
        }
    }
}
