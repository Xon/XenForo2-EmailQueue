<?php

namespace SV\EmailQueue\XF\Mail\XF22;

use SV\EmailQueue\XF\Mail\XFCP_Mail;
use XF\Mail\Mail;
use function array_fill_keys;
use function array_key_exists;

/**
 * XF2.2
 * @extends Mail
 */
class MailPatch extends XFCP_Mail
{
    /** @var array<string,true>|null */
    protected $svEmailQueueExclude = null;
    /** @var bool */
    protected $svForceQueue = false;

    public function send(\Swift_Transport $transport = null, $allowRetry = true)
    {
        if ($this->svEmailQueueExclude === null)
        {
            $options = \XF::options();
            $this->svForceQueue = $options->sv_emailqueue_force ?? false;
            $this->svEmailQueueExclude = array_fill_keys($options->sv_emailqueue_exclude ?? [], true);
        }

        if ($this->svForceQueue && !array_key_exists($this->templateName, $this->svEmailQueueExclude))
        {
            return $this->queue();
        }

        return parent::send($transport, $allowRetry);
    }
}