<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/4/28
 * Time: 下午9:23
 */

namespace inhere\library\gearman\jobs;


use inhere\library\gearman\Job;
use inhere\library\gearman\WorkerManager;

/**
 * Class ImageResizeJob
 * @package inhere\library\gearman\jobs
 */
class ImageResizeJob extends Job
{
    /**
     * {@inheritDoc}
     */
    public function run($workload, WorkerManager $manger, \GearmanJob $job)
    {
        $data = unserialize($workload);

        if (!$data['src'] || !$data['dst'] || !$data['x']) {
            $job->sendFail();
            print_r($data);

            return false;
        }

        echo $job->handle() . " - creating: $data[dest] x:$data[x] y:$data[y]\n";

        $im = new \Imagick();
        $im->readImage($data['src']);
        $im->thumbnailImage($data['x'], $data['y']);
        $im->writeImage($data['dst']);
        $im->destroy();

        $job->sendStatus(1, 1);

        return $data['dst'];
    }
}