Jack
==========

Jack is a simple client library for **Beanstalk** server written in PHP. it allows to use the complete functionality of Beanstalk server. its task is to provide understandable API and stay as simple as can be.

Beanstalk is a simple, fast work queue server. for more information about Beanstalk see: http://kr.github.com/beanstalkd/

Jack is based on a minimalistic client by David Peterson: https://github.com/davidpersson/beanstalk/

Requirements:
----------
**PHP 5.3**, no other libraries needed

if you want to use it in PHP 5.2, just delete the namespace


Usage:
----------
there are two types of client working with a queue. those who insert jobs in queue - *producers*, and those who take jobs from it - *workers*. both of them use the same client class

just instantiate the *Jack\BeanstalkClient* class. if Beanstalk runs on localhost and standard port, there is no need to setup. client will connect automatically when first needed. default settings are: 

    host: 127.0.0.1
    port: 11300
    timeout: 1 [s]
    persistent: TRUE

you can specify connection details in constructor


*producer* can open connection and insert a job in 'default' queue this way: 

    $client = new Jack\BeanstalkClient;
    $client->queue("some job data");

*worker* will then assign a job and finish it:

    $client = new Jack\BeanstalkClient;
    $job = $client->assign();
    
    // do something with $job['data']
    
    $client->finish($job['id']);


on errors the client throws *Jack\BeanstalkException*


Job lifecycle
----------

typical job lifecycle:


     queue            assign                finish
    -------> [READY] ---------> [ASSIGNED] --------> *poof*



lifecycle with more possibilities:


    
     queue (+delay)              release (+delay)
    ----------------> [DELAYED] <------------.
                          |                   |
                          | (time passes)     |
                          |                   |
     queue                v     assign        |       finish
    -----------------> [READY] ---------> [ASSIGNED] --------> *poof*
                         ^  ^                |  |
                         |   \  release      |  |
                         |    `-------------'   |
                         |                      |
                         | restore              |
                         |                      |
                         |            suspend   |
                      [SUSPENDED] <-------------'
                         |
                         |  delete
                          `--------> *poof*

Jacks terminology differs from the official protocol in some things. see protocol documentation for more info: https://github.com/kr/beanstalkd/blob/master/doc/protocol.txt


API:
----------

### producer basics:
`self selectQueue(string $queue)` - selects queue in which jobs will be inserted

`self queue(string $data, [int $priority], [int $timeToRun], [int $delay])` - insert a job in the queue


### worker basics:
`self watchQueue(string $queue)` - select queue for requesting jobs. more queues can be watched at a time, but at least one

`self ignoreQueue(string $queue)` - remove the queue from watched

`array assign([int $timeout])` - assign a job from the queue (without timeout will wait if no job is ready). returns `array(int $job, string $data)`

`self finish(int $jobId)` - finish a job. job will be deleted from the queue

`self delete(int $jobId)` - alias for finish

`self touch($jobId)` - will prevent a job from runing out of time. touch will reset the timeToRun clock, so the worker has more time before job is returned back to queue

`self release(int $jobId, [int $priority], [int $delay])` - return an assigned job to the queue (can be assigned by other wotker then)

`self suspend(int $jobId, [int $priority])` - return the job, but prevent it from being assigned again. this can be used in situatins, when a job causes an error and must be investigated before continuing

`int restore(int $number)` - will restore $number suspended jobs. the can be now assigned to worker again

`self pauseQueue(string $queue, int $delay)` - if jobs in a queue cannot be finished (eg. some service is temporarily unavailable), the whole queue can be paused for some time


### investigation:
`array showJob(int $jobId, [bool $stats])` - show a job by its id. returns `array(int $job, string $data, [array $stats])`

`array showNextReadyJob([bool $stats])` - show next ready job. returns as above…

`array showNextDelayedJob([bool $stats])` - show the job with the shortest delay left

`array showNextSuspendedJob([bool $stats])` - show the next job in the list of suspended jobs

`array getJobStats(int $jobId)` - get statistical information about a job

`array getQueueStats(string $queue)` - get statistical information about a queue

`array getServerStats()` - get statistical information about the server

`array getQueues()` - get a list of all server queues

`string getSelectedQueue()` - get name of queue currently selected for inserting jobs

`array getWatchedQueues()` - get list of watched queues


### settings:
`void __construct([$host], [$port], [$timeout], [$persistent])`

`self setDefaultPriority($priority)` - default priority for inserted jobs (default is 1024)

`self setDefaultDelay($delay)` - default delay for inserted jobs in seconds (default is 0)

`self setDefaultTimeToRun($timeToRun)` - default time to run for inserted jobs in seconds (default is 60)

`void quit()` - close connection

### notes:
*$data* can be also other type than string. other types will be automatically serialized on inserting and deserialized on reading

*$delay* can be also a DateTime object representing the time, when job will be ready for assigning

*$priority* is an integer between 0 and 2^32. the lower the priority is the sooner the job will be assigned. priority under 1024 means "urgent"

*$timeToRun* represents maximal time a job can be assigned. after this time the worker is considered to be stuck and job is returned to queue. this can be prevented by "touching" the job

for information on *statistics* show the Beanstalk protocol documentation: https://github.com/kr/beanstalkd/blob/master/doc/protocol.txt

for more information read the Jack source codes


Author:
----------
Vlasta Neubauer, https://twitter.com/#!/paranoiq
