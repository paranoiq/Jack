<?php

require __DIR__ . '/init.php';

$jack->selectQueue($queue);

while (@++$n < 10) {
    $jack->queue("test job $n", 10 - $n);
}

while ($job = $jack->assign(0)) {
    $jack->finish($job['id']);
}
