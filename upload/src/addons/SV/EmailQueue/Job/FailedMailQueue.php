<?php

namespace SV\EmailQueue\Job;

use XF\Job\AbstractJob;

class FailedMailQueue extends AbstractJob
{
    protected $defaultData = [
        'backOff' => true
    ];

    public function run($maxRunTime)
    {
        /** @var \SV\EmailQueue\XF\Mail\Queue $queue */
        if ($queue = $this->app->mailQueue())
        {
            $queue->runFailed($this->data['backOff']);
        }

        return $this->complete();
    }

    public function getStatusMessage()
    {
        return '';
    }

    public function canCancel()
    {
        return false;
    }

    public function canTriggerByChoice()
    {
        return true;
    }
}