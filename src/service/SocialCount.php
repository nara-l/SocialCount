<?php
interface SocialNetwork
{
	public function getKey();
	public function getShareCount($url);
}

class Twitter implements SocialNetwork
{
	public function getKey()
	{
		return 'twitter';
	}

	public function getShareCount($url)
	{
		$contents = file_get_contents('http://urls.api.twitter.com/1/urls/count.json?url=' . $url);
		if($contents) {
			return json_decode($contents)->count;
		} else {
			return NULL;
		}
	}
}

class Facebook implements SocialNetwork {
	public function getKey()
	{
		return 'facebook';
	}

	public function getShareCount($url)
	{
		$contents = file_get_contents("http://graph.facebook.com/fql?q=SELECT%20url,%20total_count%20FROM%20link_stat%20WHERE%20url='".$url."'");
		if($contents) {
			$json = json_decode($contents);
			return isset($json->data[0]->total_count) ? $json->data[0]->total_count : 0;
		} else {
			return NULL;
		}
	}
}

class GooglePlus implements SocialNetwork
{
	public function getKey()
	{
		return 'googleplus';
	}

	public function getShareCount($url)
	{
		    //The former code returns null. The following lines work from  http://papermashup.com/google-plus-php-function/
		  
		    $contents = file_get_contents( 'https://plusone.google.com/_/+1/fastbutton?url=' .  $url );
 
		    /* pull out count variable with regex */
		    preg_match( '/window\.__SSR = {c: ([\d]+)/', $contents, $matches );
			//print_r($contents);exit;
		 
		    /* if matched, return count, else zed */
		    if( isset( $matches[0] ) )
		        return (int) str_replace( 'window.__SSR = {c: ', '', $matches[0] );
		    return 0;
	}
}

class ShareThis implements SocialNetwork {
	const PUB_KEY = 'a3cce920-3a6b-47a8-a890-d27d55cbc9e8';
	const ACCESS_KEY = '512db7bf2cce2acb63fad31b31067e27';

	public function getKey()
	{
		return 'sharethis';
	}

	public function getShareCount($url)
	{
		$contents = file_get_contents('http://rest.sharethis.com/reach/getUrlInfo.php?url=' . $url . '&pub_key=' . self::PUB_KEY . '&access_key=' . self::ACCESS_KEY);
		if($contents) {
			$json = json_decode($contents);
			return $json->total->inbound;
		} else {
			return NULL;
		}
	}
}

/*
 * SocialCount
 * Returns share, like, and comment counts for various popular social networks in a single ajax request.
 *
 * Usage:
 * 	service.php?url=http://www.google.com/
 */
class SocialCount
{
	private $url,
		$services = array();

	const EMPTY_RESULT = '""',
		REQUIRE_LOCAL_URL = FALSE;

	function __construct($url)
	{
		if(empty($url)) {
			throw new Exception('"url" required.');
		}

		$this->url = htmlspecialchars($url);
	}

	static public function isLocalUrl( $url )
	{
		return preg_match('/^http(s?):\/\/' . $_SERVER['HTTP_HOST'] . '(:\d+)?\//', $url );
	}

	public function addNetwork(SocialNetwork $network)
	{
		$this->services[] = $network;
	}

	public function toJSON() {
		$services = array();

		foreach($this->services as $service) {
			$count = $service->getShareCount($this->url);
			$services[] = '"' . $service->getKey() . '": ' . (is_null($count) ? self::EMPTY_RESULT : $count);
		}

		return '{' . implode(',', $services) . '}';
	}
}
