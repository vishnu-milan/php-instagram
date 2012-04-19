<?php
	// Including classes
	require_once 'EpiCurl.php';
	require_once 'CheInstagram.php';
	
	// Insert Here your client id from
	// http://instagram.com/developer/client/register/
	$client_id     = 'paste-here';
	$client_secret = 'and-here';
	$redirectUri   = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

	try {
		$instagram = new CheInstagram($client_id, $client_secret);
	  	
	  	// Authorization and getting user's access_token
	  	if (!isset($_COOKIE['access_token'])) {
		  	if (isset($_GET['code'])) {
				$token = $instagram->getAccessToken($_GET['code'], $redirectUri);
				setcookie('access_token', $token->access_token);
				
				header('Location: '.$redirectUri);
			} else {
				$url = $instagram->getAuthorizeUrl($redirectUri, array('likes'));
		  		
		  		echo "<a href=\"$url\">Authorize with Instagr.am account</a>";
			}

			die;
		}

		// OK, we have access token of user
		$instagram->setAccessToken($_COOKIE['access_token']);

		// and now we can make api calls
		// you can find endpoints here http://instagram.com/developer/endpoints/
		$result = $instagram->get('/users/self/feed', array('count'=>10));

		// if you want response as array
		$my_feed = $result->response['data'];
		foreach ($my_feed as $photo) {
			echo $photo['link'];
		}

		// or if you want to work with response as an Object
		$my_feed = $result->data;
		foreach ($my_feed as $photo) {
			echo $photo->link;
		}
	} catch (InstagramNotFoundException $e) {
		echo 'You entered bad URL in request';
	} catch (InstagramException $e) {
		echo $e->getMessage();
	}
?>
