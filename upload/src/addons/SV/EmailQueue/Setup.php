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

    public function upgrade2030000Step1(): void
    {
        $this->db()->query('DROP TABLE IF EXISTS xf_mail_queue_failed');
    }

    public function uninstallStep1(): void
    {
        $this->db()->query('DROP TABLE IF EXISTS xf_mail_queue_failed');
    }
}