<?php

namespace SV\EmailQueue\Cron;

class FailedEmailQueue
{
    public static function run()
    {
        $jobManager = \XF::app()->jobManager();
        if (!$jobManager->getUniqueJob('FailedMailQueue'))
        {
            try
            {
                $jobManager->enqueueUnique('FailedMailQueue', 'SV\EmailQueue\Job\FailedMailQueue', [], false);
            }
            catch (\Exception $e)
            {
            }
        }
    }
}