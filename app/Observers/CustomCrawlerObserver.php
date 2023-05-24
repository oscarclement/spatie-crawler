<?php

namespace App\Observers;

use DOMDocument;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class CustomCrawlerObserver extends CrawlObserver {

    private $content;

    public function __construct() {
        $this->content = NULL;
    }  
    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     */
    public function willCrawl(UriInterface $url): void
    {
        // Log::info('willCrawl',['url'=>$url]);
    }

    /**
     * Called when the crawler has crawled the given url successfully.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    ) : void
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($response->getBody());
        //# save HTML 
        $content = $doc->saveHTML();
        //# convert encoding
        $content1 = mb_convert_encoding($content,'UTF-8',mb_detect_encoding($content,'UTF-8, ISO-8859-1',true));
        //# strip all javascript
        $content2 = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content1);
        //# strip all style
        $content3 = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content2);
        //# strip tags
        $content4 = str_replace('<',' <',$content3);
        $content5 = strip_tags($content4);
        $content6 = str_replace( '  ', ' ', $content5 );
        //# strip white spaces and line breaks
        $content7 = preg_replace('/\s+/S', " ", $content6);
        //# html entity decode - ö was shown as &ouml;
        $html = html_entity_decode($content7);
        //# append
        // $this->content .= $html;
        $this->content .= $html;
    }

     /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \GuzzleHttp\Exception\RequestException $requestException
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    ) : void
    {
        Log::error('crawlFailed',['url'=>$url,'error'=>$requestException->getMessage()]);
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling() : void
    {

        $text = $this->content ?? "";

        //Crawled Email Addresses
        $emailResults = [];
        preg_match_all('/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i', $text, $email_matches); 
        if ($text != "") {
            if (sizeof($email_matches) > 0) {
                for ($i=0; $i<sizeof($email_matches); $i++ ) {
                    for ($a=0; $a<sizeof($email_matches[$i]); $a++ ) {
                        if (
                            filter_var($email_matches[$i][$a], FILTER_VALIDATE_EMAIL) && 
                            !in_array($email_matches[$i][$a], $emailResults)
                            ) {
                                $emailResults[] = $email_matches[$i][$a];
                        }
                    }
                    
                }
                
                echo 'Email Addresses:', print_r($emailResults);
            }
        }

        //Crawled Phone Numbers:
        $phoneResults = [];
        preg_match_all('/[0-9]{3}[\-][0-9]{6}|[0-9]{3}[\s][0-9]{6}|[0-9]{3}[\s][0-9]{3}[\s][0-9]{4}|[0-9]{9}|[0-9]{3}[\-][0-9]{3}[\-][0-9]{4}/', $text, $phone_matches); 
        if ($text != "") {
            if (sizeof($phone_matches) > 0) {
                for ($i=0; $i<sizeof($phone_matches); $i++ ) {
                    for ($a=0; $a<sizeof($phone_matches[$i]); $a++ ) {
                        if (
                            !in_array($phone_matches[$i][$a], $phoneResults)
                            ) {
                                $phoneResults[] = $this->formatPhoneNumberWithSubstr($phone_matches[$i][$a]);
                        }
                    }
                    
                }
                
                echo 'Phone Numbers:', print_r($phoneResults);
            }
        }
        
    }
    public function formatPhoneNumberWithSubstr($phoneNumber) 
    {
        $phoneNumber = trim(str_replace([' ','-'], ['',''], $phoneNumber));
        if (!is_numeric($phoneNumber)) {
          return false;
        }
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($phoneNumber) !== 10) {
            if (strlen($phoneNumber) == 9) {
                return substr($phoneNumber, 0, 3).' '.substr($phoneNumber, 3, 3).' '.substr($phoneNumber, 5);
            }
            return $phoneNumber;
        }
        $areaCode = substr($phoneNumber, 0, 3);
        $prefix = substr($phoneNumber, 3, 3);
        $lineNumber = substr($phoneNumber, 6);
         return '(' . $areaCode . ') ' . $prefix . '-' . $lineNumber;
     }
}