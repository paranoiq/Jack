<?php

require __DIR__ . "/../../../BeanstalkClient/BeanstalkClient.php";

$jack = new Jack\BeanstalkClient;

echo "<pre><code>";
//var_export($jack);
//echo "\n";

$queue = 'Jack-Beanstalk-Client-Testing-Queue';

$jack->watchQueue($queue);
$jack->ignoreQueue('default');

// cleaning
while ($job = $jack->assign(0)) {
    $jack->delete($job['id']);
}

