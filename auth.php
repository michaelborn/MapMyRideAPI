<html>
    <head>
    </head>
    <body>
<?php

// Tokens
$keys = array(
  "clientid"=>"ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ",
  "clientsecret"=>"ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ"
);

// URLs for authorizing, etc.
$uris = array(
  "authorize"=>"https://www.mapmyfitness.com/v7.1/oauth2/authorize/",
  "redirect"=>"https://biking.michaelborn.me/apis/MapMyRide.php",
  "accesstoken"=>"https://api.mapmyfitness.com/v7.1/oauth2/access_token/"
);


// Get the access token and refresh token from the API
// based on the url code provided
if (isset($_GET["code"])) {
  $authFields = array(
    "grant_type"=>"authorization_code",
    "client_id"=>$keys["clientid"],
    "client_secret"=>$keys["clientsecret"],
    "code"=>$_GET["code"]
  );

  //url-ify the data for the POST
  foreach($authFields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  rtrim($fields_string, '&');

  //open connection
  $ch = curl_init();

  //set the url, number of POST vars, POST data
  curl_setopt($ch,CURLOPT_URL, $uris["accesstoken"]);
  curl_setopt($ch,CURLOPT_POST, count($fields));
  curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

  // "Data must be sent with a Content-Type of application/x-www-form-urlencoded"
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
  
  //execute post
  $result = curl_exec($ch);

  // hopefully, we get a string of tokens.
  print "<h1>Access tokens</h1>";
  print "<p>Please save these (in tokens.json) for later use.</p>";
  var_dump($result);
  
  //close connection
  curl_close($ch);

} else {

?>
      <form method="GET" action="<?= $uris["authorize"] ?>">
        Client Key: <input type="text" name="client_id" value="<?= $keys["clientid"] ?>" /><br />
        Response Type: <input type="text" name="response_type" value="code" /><br />
        <br />
        Redirect URI: <input type="text" name="redirect_uri" value="<?= $uris["redirect"] ?>" /><br />
        <input type="submit" />
      </form>

<?php
}
?>
    </body>
</html>
