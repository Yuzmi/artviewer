<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class Parser
{
	protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    private $userAgents = [
        "Mozilla/5.0 (Windows NT 6.2; rv:20.0) Gecko/20121202 Firefox/20.0",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.12",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:21.0) Gecko/20130330 Firefox/21.0",
        "Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0",
        "Mozilla/5.0 (Windows NT 6.1; rv:52.0) Gecko/20100101 Firefox/52.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0"
    ];

    public function getUrlData($url) {
        $data = ["success" => false];

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => $this->userAgents[array_rand($this->userAgents)]
        ));

        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 
            function($curl, $header) use(&$headers) {
                $headers[] = trim($header);
                return strlen($header);
            }
        );

        $time_start = microtime(true);
        $content = curl_exec($ch);
        $time_end = microtime(true);

        $data["duration"] = $time_end - $time_start;
        $data["http_code"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data["content_type"] = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $data["headers"] = $headers;

        if($content === false) {
            $data["error"] = curl_error($ch);
        } else {
            $data["success"] = true;
            $data["content"] = $content;
        }

        curl_close($ch);

        return $data;
    }

    public function getPieItems($url, $options = []) {
        $data = $this->getUrlData($url);
        if($data["success"]) {
            $pie = new \SimplePie();
            $pie->force_feed(true);
            $pie->set_raw_data($data["content"]);

            $pie_init = $pie->init();
            if($pie_init && $pie->get_item_quantity() > 0) {
                return $pie->get_items();
            }
        }

        return [];
    }

    public function sanitizeString($string, $maxLength = 191) {
        return $this->sanitizeText($string, $maxLength);
    }

    public function sanitizeText($text, $maxLength = 0) {
        $text = html_entity_decode(trim($text), ENT_COMPAT | ENT_HTML5, 'utf-8');

        if($maxLength > 0) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }

    public function dump($object) {
        echo "<pre>";
        \Doctrine\Common\Util\Debug::dump($object);
        echo "</pre>";
    }
}
