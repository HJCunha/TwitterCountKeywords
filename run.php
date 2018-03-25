<?php
/**
 * Twitter Keyword Counter : This file starts and execute the system.
 * 
 * TEST
 * The account name ( or Screen Name ) must be passed as an argument from the 
 * command line.
 *
 * PHP version 5.4.6
 * 
 * @package  Twitter Keyword Counter
 * @author   Hugo Cunha <hugocunha@newline.pt>
 * @since    09 November 2014
 *
 */

require( 'TwitterConnect.php' );
require( 'CountKeywords.php' );

try{

	if( sizeof( $argv ) <= 1 )
	{
		throw new Exception( 'You have to pass the Twitter account\'s '
				. ' ScreenName as an argument' );
	}

	$counter = new CountKeywords( $argv[ 1 ] );
	
	$counter->run();
}
catch( Exception $e )
{
	echo "ERROR: " . $e;
}


