<?php
require '/root/vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;


$servernum = readline("Build how many servers? (1-3)");
readline_add_history($servernum);

function server_build($buildname, $count){
    $credsfile = getenv('HOME') . '/.rackspace_cloud_credentials';
    $file = fopen($credsfile,"r") or exit("Failed to open creds");
    $username  = fgets($file);
    $apikey  = fgets($file);
    
    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
        'username' => "$username",
        'apiKey'   => substr($apikey, 0, -1) 
    ));
    $compute = $client->computeService('cloudServersOpenStack', 'IAD');
    $gentoos = $compute->image('73764eb8-3c1c-42a9-8fff-71f6beefc6a7');
    $fivetwelve = $compute->flavor('2');
    $server = $compute->server();
    $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
        'username' => "$username",
        'apiKey'   => substr($apikey, 0, -1) 
    ));
    $compute = $client->computeService('cloudServersOpenStack', 'IAD');
    $gentoos = $compute->image('73764eb8-3c1c-42a9-8fff-71f6beefc6a7');
    $fivetwelve = $compute->flavor('performance1-1');
    $server = $compute->server();
    $sname = $buildname . $count;

//  Normally all this following keygen stuff would work
//  But there's a bug in the SDK. Wee. 
//  It's been doc'd though, so whenevre that gets fixed...
    $keygen = $compute->keypair()->create(array(
        'name' => $sname
    ));
    $allkey = $compute->listKeypairs();
    $keytotal = count($allkey);
    $keycount = 0;
    try {
        while ($keycount < $keytotal) {
            if ($allkey[$keycount]['name'] == $sname) {
                $pubkey = $allkey[$keycount];
                break;
            } 
            else {
                $keycount += 1;
            }
        }
    } catch (Exception $e) {
        echo 'Failed to get keys! Full info: ' . PHP_EOL;
        print_r($e);
        exit;
    }
// Yeah, looks like addfile is broken too :\
#    $pubkey = fopen('/root/.ssh/DehTestKey.pub', 'r');
#    $server->addFile('/root/.ssh/pubkey.pub', $pubkey);
    try {
        $response = $server->create(array(
            'name'     => $sname,
            'image'    => $gentoos,
            'flavor'   => $fivetwelve,
            'networks' => array(
                $compute->network(Network::RAX_PUBLIC),
                $compute->network(Network::RAX_PRIVATE)
            ),
            'keypair' =>  $pubkey
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
    echo 'Server IP: ' . $dehipvfour . ' with password: ' . $dehpeew;
}
$range = range(1, 3);
if (in_array($servernum, $range)){ 
    $count = 1;
    $servername = readline("Base name for servers?  ");
    readline_add_history($servername);
    while ($count <= $servernum){
        server_build($servername, $count);
        $count += 1;
    }
}
else{
    echo "Invalid number selection. Exiting! ....";
    exit;
}
?>
