<?php

namespace SV\EmailQueue\XF\Mail;

class Queue extends XFCP_Queue
{
    /** @var int[]  */
    protected $svRetryThresholds = [
        0 => 5 * 60, // 5 minutes
        1 => 1 * 60 * 60, // 1 hour
        2 => 2 * 60 * 60, // 2 hours
        //3 => 6 * 60 * 60, // 6 hours
        //4 => 12 * 60 * 60, // 12 hours
    ];
    /** @var int */
    protected $svFinalRetryBucket = 2 * 60 * 60;

    /**
     * @param int $previousFailCount
     * @return int|null
     * @noinspection PhpMissingReturnTypeInspection
     */
    protected function calculateNextSendDate($previousFailCount)
    {
        $previousFailCount = (int)$previousFailCount;
        $retryToAbandon = (int)(\XF::options()->sv_emailqueue_failures_to_error ?? 0);
        if ($retryToAbandon > 0 && $previousFailCount > $retryToAbandon)
        {
            return null;
        }

        return \XF::$time + (int)($this->svRetryThresholds[$previousFailCount] ?? $this->svFinalRetryBucket);
    }
}