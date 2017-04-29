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
    /**
     * @event PushEvent
     */
    const EVENT_BEFORE_PUSH = 'beforePush';

    /**
     * @event PushEvent
     */
    const EVENT_AFTER_PUSH = 'afterPush';

    /**
     * @event JobEvent
     */
    const EVENT_BEFORE_WORK = 'beforeWork';

    /**
     * @event JobEvent
     */
    const EVENT_AFTER_WORK = 'afterWork';

    /**
     * @event ErrorEvent
     */
    const EVENT_AFTER_ERROR = 'afterError';

    public $host = '127.0.0.1';

    public $port = '4730';

    protected $config = [
    ];

    protected $channel = 'queue';

    public function __construct(array $config = [])
    {
        $this->config = $config;
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
        $job = unserialize($payload);

        if (!($job instanceof JobInterface)) {
            throw new \InvalidArgumentException('Message must be ' . JobInterface::class . ' object.');
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
