<?php

namespace SV\EmailQueue\XF\Mail;

class Mailer extends XFCP_Mailer
{
    /**
     * @return null|\XF\Mail\Queue|\SV\EmailQueue\XF\Mail\Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }
}