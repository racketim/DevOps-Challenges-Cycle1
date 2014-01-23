<?php
require '/root/vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

// Just getting us some vars

$credsfile = getenv('HOME') . '/.rackspace_cloud_credentials';
$file = fopen($credsfile,"r") or exit("Failed to open creds");
$username = fgets($file);
$apikey = fgets($file);
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => "$username",
    'apiKey'   => substr($apikey, 0, -1) 
));
$compute = $client->computeService('cloudServersOpenStack', 'IAD');
$gentoos = $compute->image('73764eb8-3c1c-42a9-8fff-71f6beefc6a7');
$fivetwelve = $compute->flavor('2');
$server = $compute->server();

// Run server create

try {
    $response = $server->create(array(
        'name'     => 'challenge1-1',
        'image'    => $gentoos,
        'flavor'   => $fivetwelve,
        'networks' => array(
            $compute->network(Network::RAX_PUBLIC),
            $compute->network(Network::RAX_PRIVATE)
        )
    ));
    $dehpeew = $server->adminPass;

} catch (\Guzzle\Http\Exception\BadResponseException $e) {

    $responseBody = (string) $e->getResponse()->getBody();
    $statusCode   = $e->getResponse()->getStatusCode();
    $headers      = $e->getResponse()->getHeaderLines();

    echo sprintf('Status: %s\nBody: %s\nHeaders: %s', $statusCode, $responseBody, implode(', ', $headers));
}

// Begin Polling section:

$callback = function($server) {
    if (!empty($server->error)) {
        var_dump($server->error);
        exit;
    } else {
        echo sprintf(
            "Waiting on %s/%-12s %4s%%\n",
            $server->name(),
            $server->status(),
            isset($server->progress) ? $server->progress : 0
        );
    }
};

$server->waitFor(ServerState::ACTIVE, 600, $callback);
$dehipvfour = $server->accessIPv4;
echo 'Server IP: ' . $dehipvfour . ' and pw: ' . $dehpeew . PHP_EOL;

// did you want echo since it's a cli script?
// or return return?
#$return = array("IP"=>"$dehipvfour", "pass"=>"$dehpeew");
#return $return;
?>
