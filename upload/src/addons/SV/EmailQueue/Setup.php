<?php

namespace SV\EmailQueue;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
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
            $this->addOrChangeColumn($table,'dispatched', 'BIT')->setDefault(0);
            $table->addKey('dispatched');
            $table->addKey('last_fail_date');
        };

        return $tables;
    }

    /**
     * @param Create|Alter $table
     * @param string       $name
     * @param string|null  $type
     * @param string|null  $length
     * @return \XF\Db\Schema\Column
     * @throws \LogicException If table is unknown schema object
     */
    protected function addOrChangeColumn($table, $name, $type = null, $length = null)
    {
        if ($table instanceof Create)
        {
            $table->checkExists(true);

            return $table->addColumn($name, $type, $length);
        }
        else if ($table instanceof Alter)
        {
            if ($table->getColumnDefinition($name))
            {
                return $table->changeColumn($name, $type, $length);
            }

            return $table->addColumn($name, $type, $length);
        }
        else
        {
            throw new \LogicException('Unknown schema DDL type ' . \get_class($table));
        }
    }
}