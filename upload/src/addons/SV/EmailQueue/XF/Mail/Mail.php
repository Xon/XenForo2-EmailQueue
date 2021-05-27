<?php

namespace SV\EmailQueue\XF\Mail;

/**
 * @property Mailer $mailer
 */
class Mail extends XFCP_Mail
{
    /** @var array|null */
    protected $svEmailQueueExclude = null;

    /** @noinspection PhpMissingReturnTypeInspection */
    /**
     * @param string $subject
     * @param string $htmlBody
     * @param string|null $textBody
     * @return Mail
     */
    public function setContent($subject, $htmlBody, $textBody = null)
    {
        // ensure body is cast to string to avoid trying to send phrases as content
        $htmlBody = (string)$htmlBody;
        $textBody = (string)$textBody;
        if (!\strlen($textBody))
        {
            $textBody = null;
        }

        return parent::setContent($subject, $htmlBody, $textBody);
    }

    public function send(\Swift_Transport $transport = null, $allowRetry = true)
    {
        if ($this->setupError)
        {
            $this->logSetupError($this->setupError);
            return 0;
        }

        $options = \XF::options();
        if ($this->svEmailQueueExclude === null)
        {
            $this->svEmailQueueExclude = array_fill_keys($options->sv_emailqueue_exclude, true);
        }

        if ($options->sv_emailqueue_force && empty($this->svEmailQueueExclude[$this->templateName]))
        {
            return $this->queue();
        }

        $sent = parent::send($transport, false);
        if ($sent)
        {
            return $sent;
        }

        // getSendableMessage only renders once
        /** @var \FinalSwiftMimeMessage $message */
        $message = $this->getSendableMessage();

        return $this->mailer->getQueue()->queueFailed($message);
    }
}