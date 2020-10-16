<?php

namespace SV\EmailQueue;

use SV\StandardLib\InstallerHelper;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;


    public function installStep1()
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->createTable($tableName, $callback);
            $sm->alterTable($tableName, $callback);
        }
    }

    public function upgrade2000000Step1()
    {
        // can't re-use failed mail queue contents from XF1 => XF2
        if($this->schemaManager()->tableExists('xf_mail_queue_failed'))
        {
            $this->db()->query('truncate table xf_mail_queue_failed');
        }
    }

    public function upgrade2000071Step1()
    {
        $this->installStep1();
    }

    /**
     * Drops add-on tables.
     */
    public function uninstallStep1()
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->dropTable($tableName);
        }
    }

    public function getTables()
    {
        $tables = [];

        $tables['xf_mail_queue_failed'] = function ($table) {
            /** @var Create|Alter $table */
            //$this->addOrChangeColumn($table,'mail_queue_id', 'int')->autoIncrement();
            $this->addOrChangeColumn($table,'mail_id', 'VARBINARY', 20)->primaryKey();
            $this->addOrChangeColumn($table,'mail_data', 'MEDIUMBLOB');
            $this->addOrChangeColumn($table,'queue_date', 'int');
            $this->addOrChangeColumn($table,'fail_count', 'int');
            $this->addOrChangeColumn($table,'last_fail_date', 'int');
            $this->addOrChangeColumn($table,'dispatched', 'tinyint', 1)->setDefault(0);
            $table->addKey('dispatched');
            $table->addKey('last_fail_date');
        };

        return $tables;
    }
}