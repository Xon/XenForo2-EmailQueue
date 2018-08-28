<?php

namespace SV\EmailQueue\XF\Mail;

/**
 * @property \SV\EmailQueue\XF\Mail\Mailer $mailer
 */
class Mail extends XFCP_Mail
{
    /** @var array|null */
    protected $svEmailQueueExclude = null;

    public function send(\Swift_Transport $transport = null)
    {
        $options = \XF::options();
        if ($this->svEmailQueueExclude === null)
        {
            $this->svEmailQueueExclude = array_fill_keys($options->sv_emailqueue_exclude, true);
        }

        if ($options->sv_emailqueue_force && empty($this->svEmailQueueExclude[$this->templateName]))
        {
            return $this->queue();
        }

        $message = $this->getSendableMessage();
        if (!$message->getTo())
        {
            return false;
        }
        $sent = $this->mailer->send($message, $transport);

        if ($sent)
        {
            return $sent;
        }

        return $this->mailer->getQueue()->queueFailed($message);
    }
}