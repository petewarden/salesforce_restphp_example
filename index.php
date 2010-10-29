<?php

/***********************************************************************************

A minimal example showing how to handle the OAuth login process and make API calls
using the Salesforce REST interface in PHP

By Pete Warden, October 28th 2010

Freely reusable with no restrictions

************************************************************************************/

// You need to set these three to the values for your own application
define('CONSUMER_KEY', '');
define('CONSUMER_SECRET', '');
define('REDIRECT_URI', 'https://<Your site here!>/labs/salesforce_restphp_example/index.php');

if (empty(CONSUMER_KEY))
    die('You need to add your own credentials to index.php before you can run this example');

define('LOGIN_BASE_URL', 'https://login.salesforce.com');

// This example uses PHP sessions to save the authorization tokens. If you plan on
// deploying across multiple machines behind a load-balancer, be aware you'll need
// to use something more sophisticated
session_start();

$is_authorized = isset($_SESSION['access_token']);

// If we aren't yet authorized, so either we need to send the user to the login page
// or they've just logged in and Salesforce is giving us the tokens we need
if (!$is_authorized)
{
    $has_code = isset($_REQUEST['code']);

    // We haven't been given a code, so this must be the user's first visit to the
    // page, so we'll send them to the Salesforce login screen for our app
    if (!$has_code)
    {
        $auth_url = LOGIN_BASE_URL
            .'/services/oauth2/authorize?response_type=code'
            .'&client_id='.CONSUMER_KEY 
            .'&redirect_uri='.urlencode(REDIRECT_URI);
            
        // Redirect to the authorization page
        header('Location: '.$auth_url);
        
        // Exit early, since we don't want to do any more until the user's logged in
        exit();
    }

    // If we're here, Salesforce must be returning us a code that we can exchange for
    // a proper access token
    $code = $_REQUEST['code'];

    // Make our first call to the API to convert that code into an access token that
    // we can use on subsequent API calls
    $token_url = LOGIN_BASE_URL.'/services/oauth2/token';

    $post_fields = array(
        'code' => $code,
        'grant_type' => 'authorization_code',
        'client_id' => CONSUMER_KEY,
        'client_secret' => CONSUMER_SECRET,
        'redirect_uri' => REDIRECT_URI,
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    
    // Make the API call, and then extract the information from the response
    $token_request_body = curl_exec($ch) 
        or die("Call to get token from code failed: '$token_url' - ".print_r($post_fields, true));

    $token_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (($token_response_code<200)||($token_response_code>=300)||empty($token_request_body))
        die("Call to get token from code failed with $token_response_code: '$token_url' - ".print_r($post_fields, true)." - '$token_request_body'");

    $token_request_data = json_decode($token_request_body, true);
    if (empty($token_request_data))
        die("Couldn't decode '$token_request_data' as a JSON object");

    if (!isset($token_request_data['access_token'])||
        !isset($token_request_data['instance_url']))
        die("Missing expected data from ".print_r($token_request_data, true));

    // Save off the values we need for future use
    $_SESSION['access_token'] = $token_request_data['access_token'];
    $_SESSION['instance_url'] = $token_request_data['instance_url'];

    // Redirect to the main page without the code in the URL
    header('Location: '.REDIRECT_URI);
    exit();
}

// If we're here, we must have a valid session containing the access token for the
// API, so grab it ready for subsequent use
$access_token = $_SESSION['access_token'];
$instance_url = $_SESSION['instance_url'];

error_log("access_token: '$access_token'");

// Now we're going to test the API by querying some data from our accounts table
// Start by specifying the URL of the call
$query_url = $instance_url.'/services/data/v20.0/query';

// Now append the actual query we want to run
$query_url .= '?q='.urlencode('SELECT Name, Id from Account LIMIT 100');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $query_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
// We need to pass the access token in the header, *not* as a URL parameter
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth '.$access_token));

// Make the API call, and then extract the information from the response
$query_request_body = curl_exec($ch) 
    or die("Query API call failed: '$query_url'");

$query_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (($query_response_code<200)||($query_response_code>=300)||empty($query_request_body))
{
    unset($_SESSION['access_token']);
    unset($_SESSION['instance_url']);
    die("Query API call failed with $query_response_code: '$query_url' - '$query_request_body'");
}

$query_request_data = json_decode($query_request_body, true);
if (empty($query_request_data))
    die("Couldn't decode '$query_request_data' as a JSON object");

if (!isset($query_request_data['totalSize'])||
    !isset($query_request_data['records']))
    die("Missing expected data from ".print_r($query_request_data, true));

// Grab the information we're interested in
$total_size = $query_request_data['totalSize'];
$records = $query_request_data['records'];

// Now build a simple HTML page to display the results
?>
<html>
<head>
<title>PHP Sample Code for the Salesforce REST API</title>
</head>
<body>
<h2>Found <?=$total_size?> records</h2>
<div>
<?php
foreach ($records as $record)
{
    print 'Name :';
    print htmlspecialchars($record['Name']);
    print ' - ';
    print htmlspecialchars($record['Id']);
    print '<br/>';
    print "\n";
}
?>
</div>
</body>
</html>