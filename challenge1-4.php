<?php
// NOTES: i know i could use sync, but this was more fun :D
// plus, this uploads the actual 0 byte files for dirs
// Reach is broken, will not show populated file # in view,
// but clicking on container shows all the files. Not sure why.
require '/root/vendor/autoload.php';
use OpenCloud\Rackspace;

// need to find a way to not have to shell exec here :\
// instancing connection...
$credsfile = substr(shell_exec('echo $HOME'), 0 ,-1) . '/.rackspace_cloud_credentials';
$file = fopen($credsfile,"r") or exit("Failed to open creds");
$username = fgets($file);
$apikey = fgets($file);
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
    'username' => "$username",
    'apiKey'   => substr($apikey, 0, -1)
));
$service = $client->ObjectStoreService('cloudfiles');

// starting user input, container name
$conname = readline('Enter the container name you wish to create: ');
readline_add_history($conname);
try {
    $namecheck = $service->checkContainerName($conname);
    } catch (Exception $e) {
    echo 'Invalid name or other error!  Full info:' . PHP_EOL;
    print_r($e);
    exit;
}

// container exists check
try {
    $result = $service->getcontainer($conname);
    if ($result != null) {
        echo 'Container exists! Exiting...' . PHP_EOL;
        exit;
    }
} catch (Exception $e) {
    echo 'Container does not exist, continuing ...' . PHP_EOL;
}

// make container
try {
    $makeit = $service->createContainer($conname);
    if ($makeit != null) {
        echo "Container created..." . PHP_EOL;
    } 
} catch (Exception $e) {
        echo 'Container failed to create! Full info:' . PHP_EOL;
        print_r($e);
        exit;
    }

// read ttl
$ttl = readline('Enter the TTL in seconds: ');
readline_add_history($ttl);
$container = $service->getContainer($conname);
if (!is_numeric($ttl) || $ttl > 1577836800 || $ttl < 900) {
    echo 'That is not a valid number! Must be between 900 and 1577836800' . PHP_EOL;
    exit;
}

//enable cnd
try {
    $enablecdn = $container->enableCdn($ttl);
    } catch (Exception $e) {
    echo 'Failed to enable CDN! Full info: ' . PHP_EOL;
    print_r($e);
    exit;
}
try {
    $cdn = $container->getCdn();
    $cdnurl = $cdn->getCdnUri();
    echo 'The CDN URL is: ' . $cdnurl . PHP_EOL;
} catch (Exception $e) {
    echo ' Failure to get CDN URL! Full info: ' . PHP_EOL;
    print_r($e);   
}

// read directory and fix trailing slash if omitted
$directory = readline('Enter the local directory to upload (full path, . for current): ');
readline_add_history($directory);
if ($directory == ".") {
    $directory = getcwd();
}
if (substr($directory, -1) != "/") {
    $directory = $directory . "/";
}

// declare us some variables and adjust for . input
$dirs = array();
$files = array();
$cfdirs = array();
$len = strlen($directory);
if ($directory == ".") {
    $directory = getcwd(); 
}

// set the directory list and upload the 0 byte files for pseudo dirs
// NOTE: BROKEN! you MUST upload individually with uploadObject, not
// in array with uploadObjects as files are later because the latter
// POOPS itself on non-readable path/body for 0 byte files :\
$listdirs = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);
foreach ($listdirs as $path => $dir) {
    if ($dir->isDir()) {
        $path = substr_replace($path, '', 0, $len);
        $path = $path . '/';
        $container->uploadObject($path, '');
    }
}

// files are listed and set to variables here
$listfiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory));
foreach($listfiles as $name => $object){
    if (substr($name, -1) != '.') {
        $files[] = array(
            'name' => substr_replace($name, '', 0, $len),
            'path' => $name
        );
    }
}

// here goes the upload for files, running from the full array
try {
    $container->uploadObjects($files);
} catch (Exception $e) {
    echo 'Failure to upload! Full info: ' . PHP_EOL;
    $container->delete();
    print_r($e);
    exit;
}
echo 'Upload complete!' . PHP_EOL;
?>
