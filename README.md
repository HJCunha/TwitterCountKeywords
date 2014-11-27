TwitterKeywordCounter
---------------------
PHP APP to call the Twitter's API and retrieve an X amount of tweets and count 
how many times all the keywords appear.

Flow Overview
=============
1. Initialize all objects with the configuration from the ini file.
2. Call the API to retrieve the tweets from the account passed as argument.
3. Count the keywords.
4. Print the keyword list to the console.

Parameters
==========
All the parameters/ settings should be added on the ini file.
Create the config.ini with this structure:

[twitter_app_settings]
oauth_access_token          = 
oauth_access_token_secret   = 
consumer_key                = 
consumer_secret             = 

[url_settings]
url                         = 

[counter_settings]
number_of_tweets            = 


Usage
=====
The app should be called from the command line with 1 single argument, 
like this:

    "$php run.php SCREEN_NAME"

(Where "SCREEN_NAME" is the Twitter's account name which tweets should be 
retrieved)

Contributors
============

* [Hugo Cunha] - Developer