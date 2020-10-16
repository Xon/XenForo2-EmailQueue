<?php

namespace SV\EmailQueue\XF\Mail;

/**
 * @property Mailer $mailer
 */
class Mail extends XFCP_Mail
{
    /** @var array|null */
    protected $svEmailQueueExclude = null;

    public function setContent($subject, $htmlBody, $textBody = null)
    {
        return parent::setContent($subject, (string)$htmlBody, $textBody === null ? null : (string)$textBody);
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
        $message = $this->getSendableMessage();

        return $this->mailer->getQueue()->queueFailed($message);
    }
}