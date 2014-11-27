<?php

/**
 * This class connects to the Twitter API using the OAuth algorithm.
 * 
 * PHP version 5.4.6
 * 
 * @package  Twitter Keyword Counter
 * @author   Hugo Cunha <hugocunha@newline.pt>
 * @since    09 November 2014
 *
 */

class TwitterConnect
{
    private $oauth_access_token;
    private $oauth_access_token_secret;
    private $consumer_key;
    private $consumer_secret;
    private $getfield;
    protected $oauth;
    public $url;

    /**
     * Create the API access object. Requires an array of settings::
     * oauth access token, oauth access token secret, consumer key, consumer 
     * secret
     * These are all available by creating your own application on 
     * dev.twitter.com
     * Requires the cURL library
     * 
     * @param array $settings
     * @return void
     */
    public function __construct( array $settings )
    {
        if ( ! in_array( 'curl' , get_loaded_extensions() ) ) 
        {
            throw new Exception( 'You need to install cURL, '
                    . 'see: http://curl.haxx.se/docs/install.html' );
        }

        $this->oauth_access_token = $settings[ 'oauth_access_token' ];
        
        $this->oauth_access_token_secret = 
            $settings[ 'oauth_access_token_secret' ];
        
        $this->consumer_key = $settings[ 'consumer_key' ];
        
        $this->consumer_secret = $settings[ 'consumer_secret' ];
    }
        
    /**
     * Set getfield string, example: '?screen_name=HJCunha'
     * 
     * @param string $string Get key and value pairs as string
     * 
     * @return \TwitterConnect Instance of self for method chaining
     */
    public function setGetfield( $string )
    {
        $search = array( '#' , ',' , '+' , ':' );
        $replace = array( '%23' , '%2C' , '%2B' , '%3A' );
        $string = str_replace( $search , $replace, $string );  
        
        $this->getfield = $string;
        
        return $this;
    }
    
    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     * 
     * @param string $url The API url to use. Example: 
     *        https://api.twitter.com/1.1/search/tweets.json
     * @return \TwitterConnect Instance of self for method chaining
     */
    public function buildOauth( $url )
    {
        $requestMethod = 'GET';

        $consumer_key = $this->consumer_key;
        $consumer_secret = $this->consumer_secret;
        $oauth_access_token = $this->oauth_access_token;
        $oauth_access_token_secret = $this->oauth_access_token_secret;
        
        $oauth = array( 
            'oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );
        
        if ( ! is_null( $this->getfield ) )
        {
            $getfields = str_replace( '?' , '' , explode( '&' , 
                    $this->getfield ) );

            foreach ( $getfields as $g )
            {
                $split = explode( '=' , $g );
                $oauth[ $split[ 0 ] ] = $split[ 1 ];
            }
        }
        
        $base_info = $this->buildBaseString( $url, $requestMethod, $oauth );
        
        $composite_key = rawurlencode( $consumer_secret ) . '&' 
            . rawurlencode( $oauth_access_token_secret );
        
        $oauth_signature = base64_encode( 
                hash_hmac( 'sha1' , $base_info, $composite_key, true ) );
        
        $oauth[ 'oauth_signature' ] = $oauth_signature;
        
        $this->url = $url;
        $this->oauth = $oauth;
        
        return $this;
    }
    
    /**
     * Private method to generate the base string used by cURL
     * 
     * @param string $baseURI
     * @param string $method
     * @param array $params
     * 
     * @return string Built base string
     */
    private function buildBaseString( $baseURI, $method, $params ) 
    {
        $return = array();
        ksort( $params );
        
        foreach( $params as $key=>$value )
        {
            $return[] = "$key=" . $value;
        }
        
        return $method . "&" . rawurlencode( $baseURI ) . '&' 
            . rawurlencode( implode( '&' , $return ) ); 
    }
    
    /**
     * Private method to generate authorization header used by cURL
     * 
     * @param array $oauth Array of oauth data generated by buildOauth()
     * 
     * @return string $return Header used by cURL for request
     */    
    private function buildAuthorizationHeader( $oauth ) 
    {
        $return = 'Authorization: OAuth ';
        $values = array();
        
        foreach( $oauth as $key => $value )
        {
            $values[] = "$key=\"" . rawurlencode( $value ) . "\"";
        }
        
        $return .= implode( ', ' , $values );
        return $return;
    }

    /**
    * Perform the actual data retrieval from the API
    * 
    * @return json object with data from the API response.
    */
    public function performRequest()
    {
        $header = array( $this->buildAuthorizationHeader( $this->oauth ), 
                'Expect:' );
        
        $options = array( 
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
        );

        if ( $this->getfield !== '' )
        {
            $options[ CURLOPT_URL ] .= $this->getfield;
        }

        $feed = curl_init();
        curl_setopt_array( $feed, $options );
        $json = curl_exec( $feed );
        curl_close( $feed );
        
        if ( $json === false ) 
        {
            throw new Exception( curl_error( $feed ) );
        }

        $decodedResponse = json_decode( $json );

        /* Check if the Twitter API returned 1 or more errors */
        if( ! empty( $decodedResponse->errors ) )
        {
            $errorMessages = '';
            $sep = '';
            foreach ( $decodedResponse->errors as $error ) 
            {
                $errorMessages .= $sep . $error->message;    
                $sep = ',';
            }

            throw new Exception( $errorMessages );
        }

        return  $decodedResponse;
    }
}