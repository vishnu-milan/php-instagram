This PHP class provide you a simple way to interact with [Instagr.am API](http://instagram.com/developer/).

## Requirements ##
  * php 5.3
  * curl

## How can I use it? ##
Example is included. Here is simple version of it.
<pre>try {<br>
﻿$instagram = new CheInstagram($client_id, $client_secret, $access_token);<br>
﻿$result = $instagram->get('/users/self/feed', array('count'=>10));<br>
<br>
$my_feed = $result->response['data'];<br>
foreach ($my_feed as $photo) {<br>
echo $photo['link'];<br>
}<br>
﻿} catch (InstagramException $e) {<br>
﻿  echo $e->getMessage();<br>
﻿}<br>
</pre>