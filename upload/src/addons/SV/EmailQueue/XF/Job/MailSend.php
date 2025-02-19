<?php

namespace SV\EmailQueue\XF\Job;

/**
 * @extends \XF\Job\MailSend
 */
class MailSend extends XFCP_MailSend
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

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function calculateNextAttemptDate($previousAttempts): ?int
    {
        $previousFailCount = (int)$previousAttempts;
        $retryToAbandon = (int)(\XF::options()->sv_emailqueue_failures_to_error ?? 0);
        if ($retryToAbandon > 0 && $previousFailCount > $retryToAbandon)
        {
            return null;
        }

        return \XF::$time + (int)($this->svRetryThresholds[$previousFailCount] ?? $this->svFinalRetryBucket);
    }
}