<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-04-27
 * Time: 9:56
 */

namespace inhere\library\gearman;

/**
 * JobServer
 */
class JobServer
{
    protected $channel = 'queue';

    public $host = '127.0.0.1';

    public $port = '4730';

    public function __construct($channel = 'queue')
    {
        $this->channel = $channel;
    }

    /**
     * Listens gearman-queue and runs new jobs.
     */
    public function listen()
    {
        $worker = new \GearmanWorker();
        $worker->addServer($this->host, $this->port);
        $worker->setTimeout(-1);

        $worker->addFunction($this->channel, function (\GearmanJob $job) {
            $this->handleJob($job->workload());
        });

        do {
            $worker->work();
            usleep(50000);
        } while (!Signal::isExit() && $worker->returnCode() === GEARMAN_SUCCESS);
    }

    public function handleJob($payload)
    {
        $job = $this->serializer->unserialize($payload);

        if (!($job instanceof Job)) {
            throw new \InvalidArgumentException('Message must be ' . Job::class . ' object.');
        }

        $error = null;
        $this->trigger(self::EVENT_BEFORE_WORK, new JobEvent(['job' => $job]));

        try {
            $job->run();
        } catch (\Exception $error) {
            $this->trigger(self::EVENT_AFTER_ERROR, new ErrorEvent(['job' => $job, 'error' => $error]));
        }

        if (!$error) {
            $this->trigger(self::EVENT_AFTER_WORK, new JobEvent(['job' => $job]));
        }

        return !$error;
    }
}
