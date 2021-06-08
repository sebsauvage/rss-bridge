<?php

/**
 * Why:
 *
 * Tumblr has an RSS feature, and it's simple: username.tumblr.com/rss
 * However, when Verizon bought Yahoo, Oath placed an interstitial in
 * front of most of Tumbl's content, including (!) the RSS feeds.
 * Unles... you're google. Or you look like Google.
 * This is a dead simple wrapper that has the 'Googlebot/' string in the
 * User Agent to be able to fetch Tumblr RSS feeds.
 *
 */

class TumblrBridge extends FeedExpander {
    const NAME        = 'Tumblr Bridge';
    const URI         = 'https://tumblr.com/';
    const DESCRIPTION = 'Tumblr Bridge';
    const MAINTAINER  = 'petermolnar';
    const PARAMETERS = array(
        array(
            'searchUsername' => array(
            'name' => 'Blog name',
            'required' => true,
            'type' => 'text'
        )
    ));

    public function getName(){
        return $this->getInput('searchUsername');
    }

    public function getURI(){
        return 'https://' . $this->getInput('searchUsername') . '.tumblr.com/';
    }

    public function collectData() {
        $url = 'https://' . $this->getInput('searchUsername') . '.tumblr.com/rss';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'rss-bridge (Googlebot/ for Tumblr)');
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        $data = curl_exec($ch);
        $errorCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $curlInfo = curl_getinfo($ch);
        if($data === false) {
            fDebug::log("Cant't download {$url} cUrl error: {$curlError} ({$curlErrno})");
        }
        curl_close($ch);
        $rssContent = simplexml_load_string(trim($data));
        $rssContent = $rssContent->channel[0];

        Debug::log(
            'RSS content is ===========\n'
            . var_export($rssContent, true)
            . '==========='
        );

        $this->load_RSS_2_0_feed_data($rssContent);
        foreach($rssContent->item as $item) {
            Debug::log('parsing item ' . var_export($item, true));
            $tmp_item = $this->parseRSS_2_0_Item($item);
            if (!empty($tmp_item)) {
                $this->items[] = $tmp_item;
            }
        }
        return $this;
    }
}
