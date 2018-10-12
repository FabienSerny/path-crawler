<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class PathCrawler
{
    private $email;
    private $password;

    private $guzzle;
    private $cookieJar;
    private $allow_redirects;


    public function __construct($email, $password)
    {
        $this->email = $email;
        $this->password = $password;

        $this->dir_cache = __DIR__.'/../../cache/';

        $this->allow_redirects = [
            'max'             => 10,        // allow at most 10 redirects.
            'strict'          => true,      // use "strict" RFC compliant redirects.
            'referer'         => true,      // add a Referer header
            'protocols'       => ['https'], // only allow https URLs
            'track_redirects' => true
        ];
        $this->cookieJar = new CookieJar(true);
        $this->guzzle = new Client([
            'base_uri' => 'https://path.com',
            'headers' => ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.117 Safari/537.36'],
            'cookies' => $this->cookieJar
        ]);
    }


    private function login()
    {
        $this->guzzle->request('GET', '/');
        sleep(1);

        $this->guzzle->request('POST', '/a/login', [
            'form_params' => [
                'emailId' => $this->email,
                'password' => $this->password
            ],
            'allow_redirects' => $this->allow_redirects
        ]);
        sleep(1);
    }

    private function getNews($older_than = '')
    {
        usleep(500);
        $this->speak('Getting news older than '.($older_than != '' ? date('y-m-d-h-i-s', $older_than) : date('y-m-d-h-i-s')));

        $key = 'home-'.date('y-m-d-h-i-s');
        $request_url = '/a/feed/home?ww=1440&wh=519&meId=563483c32a892289e7d963c5';
        if ($older_than != '') {
            $request_url = '/a/feed/home?ww=1440&wh=156&older_than='.$older_than.'&meId=563483c32a892289e7d963c5';
            $key = 'home-'.date('y-m-d-h-i-s', $older_than);
        }
        $response = $this->guzzle->request('GET', $request_url, [ 'allow_redirects' => $this->allow_redirects ]);
        $data = $response->getBody();

        $this->storeData($key, $data);

        $content = json_decode($data, true);
        if (isset($content['momentSet']) && !empty($content['momentSet'])) {
            $last_moment = array_values(array_slice($content['momentSet'], -1))[0];
            $this->getNews($last_moment['created']);
        }
    }

    private function storeData($key, $data)
    {
        // Create directory and store content
        mkdir($this->dir_cache.$key.'/');
        @unlink($this->dir_cache.$key.'/content.json');
        file_put_contents($this->dir_cache.$key.'/content.json', $data);

        // Store moment
        $content = json_decode($data, true);
        foreach ($content['momentSet'] as $moment_id => $moment) {
            if (isset($moment['photo']['photo'])) {
                $url = $moment['photo']['photo']['url'].'/'.$moment['photo']['photo']['original']['file'];
                copy($url, $this->dir_cache.$key.'/'.$moment_id.'.jpg');
            }
        }
    }



    public function speak($sentence, $loading = false)
    {
        echo '['.date('Y-m-d H:i:s').'] '.$sentence.($loading ? "\r" : "\n");
    }

    public function run()
    {
        $this->speak('Knock, knock. Let me enter please.');
        $this->login();
        $this->speak('Get news');
        $this->getNews();
        $this->speak('And thanks for the fish ! Bye !');
    }
}