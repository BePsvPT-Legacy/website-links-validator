<?php

namespace Bepsvpt\WebsiteLinksValidator;

use League\CLImate\CLImate;

class Console
{
    /**
     * @var CLImate
     */
    protected $console;

    /**
     * CLI arguments.
     *
     * @var array
     */
    private $arguments = [
        'deep' => [
            'longPrefix'   => 'deep',
            'description'  => 'How deep it should validate',
            'defaultValue' => 3,
            'castTo'      => 'int',
        ],
        'timeout' => [
            'longPrefix'  => 'timeout',
            'description' => 'The http timeout seconds',
            'defaultValue' => 10.0,
            'castTo'      => 'float',
        ],
        'outputPath' => [
            'longPrefix'  => 'output-path',
            'description' => 'The path to save the result file (use stdout to display on console)',
            'defaultValue' => './',
            'castTo'      => 'string',
        ],
        'help' => [
            'prefix'      => 'h',
            'longPrefix'  => 'help',
            'description' => 'Display this information',
            'noValue'     => true,
        ],
    ];

    /**
     * Console constructor.
     */
    public function __construct()
    {
        $this->console = new CLImate;

        $this->console->arguments->add($this->arguments);
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
            $results[$url] = Validator::validate($url, $config);
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
        $this->console->arguments->parse();

        if ($this->console->arguments->defined('help')) {
            $this->console->usage();

            exit();
        }

        return [
            'deep' => $this->console->arguments->get('deep'),
            'timeout' => $this->console->arguments->get('timeout'),
            'savePath' => $this->console->arguments->get('outputPath'),
        ];
    }

    /**
     * Get the urls that should validate.
     *
     * @return array
     */
    protected function getUrls()
    {
        $input = $this->console->input('Please choose the url source: [1-file / 2-console]');

        $input->accept(['1', '2']);

        if ('1' === $input->prompt()) {
            return $this->getSourceFromFile();
        }

        return $this->getSourceFromConsole();
    }

    /**
     * Prompt user to provide the file path.
     *
     * @return array
     */
    protected function getSourceFromFile()
    {
        $path = realpath($this->console->input('Please enter the file path:')->prompt());

        if (false === $path || ! is_file($path)) {
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
            $this->console->border();

            $this->outputToConsole($results);
        } else {
            if (! ends_with($path, '/')) {
                $path = $path.'/';
            }

            file_put_contents($path.'result.json', json_encode($results));
        }
    }

    /**
     * Output the validate results to console.
     *
     * @param array|string $results
     * @param int $tab
     * @param int $recursive
     */
    protected function outputToConsole($results, $tab = 0, $recursive = 0)
    {
        if (! is_array($results)) {
            $this->tab($tab)->out($results);
        } else {
            foreach ($results as $key => $value) {
                if (is_array($value) && 0 === count($value)) {
                    return;
                } elseif (is_int($key) && ! is_array($value)) {
                    $this->tab($tab)->out($value);
                } else {
                    $this->tab($tab)->out($key);

                    $this->outputToConsole($value, $tab + 1, $recursive + 1);

                    if (0 === $recursive) {
                        $this->console->br();
                    }
                }
            }
        }
    }

    /**
     * Inserts tabs before a line.
     *
     * @param int $tab
     * @return CLImate
     */
    protected function tab($tab)
    {
        if ($tab > 0) {
            $this->console->tab($tab);
        }

        return $this->console;
    }
}
