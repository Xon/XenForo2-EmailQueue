<?php

namespace SV\EmailQueue\XF\Admin\Controller;

/**
 * @extends \XF\Admin\Controller\Tools
 */
class Tools extends XFCP_Tools
{
    /** @noinspection PhpMissingReturnTypeInspection */
    public function actionTestEmail()
    {
        \XF::options()->sv_emailqueue_force = false;
        return parent::actionTestEmail();
    }
}