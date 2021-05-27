<?php
/**
 * @noinspection PhpUnnecessaryLeadingBackslashInUseStatementInspection
 */

namespace SV\EmailQueue\XF\Mail;

use \Swift_Mime_SimpleMessage as SwiftMimeSimpleMessage;
use \Swift_Mime_Message as SwiftMimeMessage;

\class_alias(\XF::$versionId < 2020010 ? SwiftMimeMessage::class : SwiftMimeSimpleMessage::class, '\FinalSwiftMimeMessage');

class Queue extends XFCP_Queue
{
    public function queueFailed(\FinalSwiftMimeMessage $message): bool
    {
        $toEmails = implode(', ', array_keys($message->getTo()));

        try
        {
            $rawMailObj = serialize($message);
            $mailId = $this->getFailedItemKey($rawMailObj, \XF::$time);
            $this->insertFailedMailQueue($mailId, $rawMailObj, \XF::$time);
        }
        catch (\Exception $e)
        {
            \XF::logException($e, false, "Exception when attempting to queue failed email for Email to $toEmails: ");
        }

        $jobManager = \XF::app()->jobManager();
        if (!$jobManager->getUniqueJob('MailQueue'))
        {
            try
            {
                $jobManager->enqueueUnique('MailQueue', 'XF\Job\MailQueue', [], false);
            }
            catch (\Exception $e)
            {
                // need to just ignore this and let it get picked up later;
                // not doing this could lose email on a deadlock
            }
        }

        return true;
    }

    public function run($maxRunTime)
    {
        $s = microtime(true);
        $db = $this->db;
        $mailer = \XF::mailer();
        $options = \XF::options();

        $batchSize = (int)($options->sv_emailqueue_batchsize ?? 100);

        $transport = $mailer->getDefaultTransport();
        do
        {
            $queue = $this->getQueue($batchSize);

            foreach ($queue AS $id => $record)
            {
                if (!$db->delete('xf_mail_queue', 'mail_queue_id = ?', $id))
                {
                    // already been deleted - run elsewhere
                    continue;
                }

                $message = @unserialize($record['mail_data']);
                if (!($message instanceof \FinalSwiftMimeMessage))
                {
                    continue;
                }

                $emailId = $this->getFailedItemKey($record['mail_data'], $record['queue_date']);

                if ($mailer->send($message, $transport, null, false))
                {
                    $this->deleteFailedMail($emailId);
                }
                else
                {
                    $this->deliveryFailure($message, $emailId, $record);
                    if ($transport->isStarted())
                    {
                        try
                        {
                            $transport->stop();
                        }
                        catch (\Throwable $null)
                        {
                            // queue re-processing will be triggered again
                            return;
                        }
                        $transport->start();
                    }
                }

                if ($maxRunTime && microtime(true) - $s > $maxRunTime)
                {
                    return;
                }
            }
        }
        while ($queue);
    }

    public function runFailed(bool $doBackOff = true)
    {
        $latestFailedTime = $this->getLatestFailedTimestamp();
        if ($latestFailedTime)
        {
            $options = \XF::options();
            $backOffSeconds = $doBackOff ? $options->sv_emailqueue_backoff * 60 : 0;
            if ((!$backOffSeconds || microtime(true) > $latestFailedTime + $backOffSeconds))
            {
                $this->db->beginTransaction();

                $this->db->query('
                    INSERT INTO xf_mail_queue (`mail_data`,`queue_date`)
                    SELECT `mail_data`,`queue_date`
                    FROM xf_mail_queue_failed
                    WHERE dispatched = 0
                    FOR UPDATE
                ');

                $this->db->query('
                    UPDATE xf_mail_queue_failed
                    SET dispatched = 1
                    WHERE dispatched = 0
                ');

                $this->db->commit();
            }
        }

        $jobManager = \XF::app()->jobManager();
        if (!$jobManager->getUniqueJob('MailQueue'))
        {
            try
            {
                $jobManager->enqueueUnique('MailQueue', 'XF\Job\MailQueue', [], false);
            }
            catch (\Exception $e)
            {
                // need to just ignore this and let it get picked up later;
                // not doing this could lose email on a deadlock
            }
        }
    }

    protected function insertFailedMailQueue(string $mailId, $rawMailObj, int $queueDate): bool
    {
        $this->db->query('
            INSERT INTO xf_mail_queue_failed
                (mail_id, mail_data, queue_date, fail_count, last_fail_date)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                dispatched = 0,
                fail_count = fail_count + 1,
                last_fail_date = VALUES(last_fail_date)
        ', [
            $mailId, $rawMailObj, $queueDate, 1, \XF::$time
        ]);

        return true;
    }

    /**
     * @param \FinalSwiftMimeMessage $mailObj
     * @param  string                $mailId
     * @param  array                 $record
     */
    function deliveryFailure(\FinalSwiftMimeMessage $mailObj, string $mailId, array $record)
    {
        // queue the failed email
        $this->insertFailedMailQueue($mailId, $record['mail_data'], $record['queue_date']);
        $toEmails = implode(', ', array_keys($mailObj->getTo()));
        $failedCount = $this->getFailedMailCount($mailId);
        $options = \XF::options();
        if ($options->sv_emailqueue_failures_to_error && $failedCount >= $options->sv_emailqueue_failures_to_error)
        {
            $this->deleteFailedMail($mailId);
            \XF::logError("Abandoning, Email to $toEmails failed");
        }
        else if ($options->sv_emailqueue_failures_to_warn && $failedCount >= $options->sv_emailqueue_failures_to_warn)
        {
            \XF::logError("Queued, Email to $toEmails failed");
        }
    }

    /**
     * @param mixed $rawMailObj
     * @param int   $queueDate
     * @return string
     */
    public function getFailedItemKey($rawMailObj, int $queueDate): string
    {
        return sha1($queueDate . $rawMailObj, true);
    }

    public function getLatestFailedTimestamp(): int
    {
        return \intval($this->db->fetchOne("SELECT max(last_fail_date) FROM xf_mail_queue_failed"));
    }

    public function getFailedMailCount(string $mailId): int
    {
        return \intval($this->db->fetchOne('
            SELECT fail_count
            FROM xf_mail_queue_failed
            WHERE mail_id = ?
        ', $mailId));
    }

    protected function deleteFailedMail(string $mailId)
    {
        $this->db->query('
            DELETE
            FROM xf_mail_queue_failed
            WHERE mail_id = ?
        ', $mailId);
    }
}