<?php
/**
 * Twitter Keyword Counter : This app was build to fetch the Twitter API for a 
 * specific account and retrieve the last number_of_tweets and count how many 
 * times there are repeated keywords.
 * 
 * PHP version 5.4.6
 * 
 * @package  Twitter Keyword Counter
 * @author   Hugo Cunha <hugocunha@newline.pt>
 * @since    09 November 2014
 *
 */

define( "CONFIG_INI" , "config.ini" );

class CountKeywords
{
	private $twitter 	  = NULL;
	private $config  	  = NULL;
	private $accountName  = NULL;
	private $jsonResponse = NULL;
	private $keywords 	  = array();

	/**
     * Check if the ini file exist and reads it into the object. 
     * Initialize the class so the run function can be called.
     * 
     * @param string $accountName with the screen name to be fetched
     * @return void
     */
	public function __construct( $accountName )
    {
    	/* Get the configuration values from the ini file */
    	if( ! file_exists( CONFIG_INI ) )
    	{
    		throw new Exception( 'Make sure you have the configuration file '
    				. 'and the path is correctly defined'  );
    	}

    	$config = parse_ini_file( CONFIG_INI , true );
		
		if( empty( $config[ 'twitter_app_settings' ] ) )
		{
			throw new Exception( 'Your configuration file must have the '
					. 'twitter_app_settings section' );
		}

		if( empty( $config[ 'url_settings' ] ) )
		{
			throw new Exception( 'Your configuration file must have the '
					. 'url_settings section' );
		}

		$this->config = $config;

		$this->accountName = $accountName;

    }

    /**
    * function to be called from the runtime file. Processes the workflow of the
    * system
    * 
    * @return void
    */
    public function run()
    {
    	/* 1. Set all the parameters and initialize all objects*/
    	$this->init();

    	/* 2. Execute request to the Twitter API */
    	$this->jsonResponse = $this->twitter->performRequest();

    	/* 3. If success, extract the desired values from the response */
    	$this->processResponse();

    	/* 4. Show the results to the command line */
    	$this->showResults();

    }

	/**
    * Checks if the config file is well structured and all the data are setted.
    * Assigns all the values to the object variables
    * 
    * @return void
    */
    private function init()
    {
    	$settings = $this->config[ 'twitter_app_settings' ];
    	$url_config = $this->config[ 'url_settings' ];
    	$counter_config = $this->config[ 'counter_settings' ];
		
		/* Check if all the necessary variables are set */
		if ( ! isset( $settings[ 'oauth_access_token' ] )
            || ! isset( $settings[ 'oauth_access_token_secret' ] )
            || ! isset( $settings[ 'consumer_key' ] )
            || ! isset( $settings[ 'consumer_secret' ] )
            || ! isset( $url_config[ 'url' ] )
            || ! isset( $counter_config[ 'number_of_tweets' ] ) 
            )
        {
            throw new Exception( 'Make sure you are setting all the necessary '
                    . 'parameters on the configuration file' );
        }

		$getfield = '?count=' . $counter_config[ 'number_of_tweets' ] 
			. '&screen_name=' . $this->accountName;

		$this->twitter = new TwitterConnect( $settings );

		$this->twitter->setGetfield( $getfield );
    	$this->twitter->buildOauth( $url_config[ 'url' ] );	

    }

    /**
    * For every tweet returned, checks the keywords in it and builds and array
    * of all the keywords as array key and the number of occurences as the value
    * 
    * @return void
    */
    private function processResponse()
    {
		foreach ( $this->jsonResponse as $value ) 
		{
			foreach ( $value->entities->hashtags as $hash ) 
			{
				if( array_key_exists( $hash->text, $this->keywords ) )
				{
					$this->keywords[ $hash->text ] ++;
				}
				else
				{
					$this->keywords[ $hash->text ] = 1;
				}
			}
		}

		/* Most frequent keyword on the top */
		array_multisort( $this->keywords, SORT_DESC );
    }

    /**
    * Echos to the command line the list of keywords and the number of occurences
    * If no keywords were found, warns the user.
    * 
    * @return void
    */
    private function showResults()
    {
    	if( sizeof( $this->keywords ) > 0 )
		{
			foreach ( $this->keywords as $key => $value ) 
			{
				echo $key . "," . $value . "\n";
			}	
		}
		else
		{
			echo "There are no records to show, sorry";
		}
    }
}