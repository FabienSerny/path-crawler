<?php

ini_set('display_errors', 'on');
set_time_limit(0);

// Load requirements
require_once('../vendor/autoload.php');
require_once('../src/classes/_loader.php');
require_once('../src/classes/_config.php');

// Load and run crawler
function runCrawler()
{
    try {
        $pathCrawler = new PathCrawler(__PATH_LOGIN__, __PATH_PASSWORD__);
        $pathCrawler->run();
    } catch (\Exception $e) {
        echo $e->getMessage()."\n";
        runCrawler();
    }
    echo "\n\nWe did it!\n\n";
}

// Run
runCrawler();
