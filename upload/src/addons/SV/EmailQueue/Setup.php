<?php

namespace SV\EmailQueue;

use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function upgrade2000000Step1()
    {
        $this->cleanupOldTable(false);
    }

    public function upgrade2030000Step1()
    {
        $this->cleanupOldTable();
    }

    public function upgrade2030000Step2()
    {
        $this->renameOption('sv_emailqueue_failures_to_error','svEmailQueue_retryToAbandon');
    }

    public function uninstallStep1()
    {
        $this->cleanupOldTable();
    }

    protected function cleanupOldTable(bool $requeueContents = true)
    {
        if ($requeueContents)
        {
            $this->db()->query('
                INSERT INTO xf_mail_queue (`mail_data`,`queue_date`)
                SELECT `mail_data`,`queue_date`
                FROM xf_mail_queue_failed
                WHERE dispatched = 0
            ');
        }
        $this->db()->query('DROP TABLE IF EXISTS xf_mail_queue_failed');
    }
}