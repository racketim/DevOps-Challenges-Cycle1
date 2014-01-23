<?php

require '/root/vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\DNS\Service;
use OpenCloud\DNS\Resource;

$credsfile = substr(shell_exec('echo $HOME'), 0 ,-1) . '/.rackspace_cloud_credentials';
$file = fopen($credsfile,"r") or exit("Failed to open creds");
$username = fgets($file);
$apikey = fgets($file);
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => "$username",
    'apiKey'   => substr($apikey, 0, -1) 
));
$service = $client->dnsService();
$dlist = $service->domainList();
$domarray = array();

foreach ($dlist as $domain) {
     $domarray[] = $domain->Name();
}
$domarray = array_unique($domarray);
$count = 0;
foreach ($domarray as $domain) {
    echo $count . ' => ' . $domain . PHP_EOL;
    $count += 1;
}
$domid = readline("Enter the number for the domain you wish to make a new A record for:  ");
readline_add_history($domid);
$subname = readline("Enter the subdomain: ");
readline_add_history($subname);
$IP = readline("Enter the IP: ");
readline_add_history($IP);
$ttl = readline("Enter the TTL: ");
readline_add_history($ttl);

$dname = $domarray[$domid];
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => "$username",
    'apiKey'   => substr($apikey, 0, -1)
));
$service = $client->dnsService();
$dlist = $service->domainList();
foreach ($dlist as $domain) {
    if ($domain->Name() == $dname) {
        $subname = $subname . '.' . $dname;
        $record = $domain->record();
        $response = $record->Create(array(
            'type' => 'A',
            'name' => $subname,
            'ttl' => $ttl,
            'data' => $IP
        ));
    echo "Update completed";
    exit; 
    }
}











?>
