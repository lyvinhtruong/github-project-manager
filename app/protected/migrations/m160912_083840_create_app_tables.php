<?php

class m160912_083840_create_app_tables extends CDbMigration
{
    public function safeUp()
    {
        $this->createTable('project',
            array(
                'id' => 'pk',
                'name' => 'varchar(64) NOT NULL',
            ),
            'ENGINE=InnoDB'
        );

        $this->createTable('issue_label',
            array(
                'id' => 'pk',
                'project_id' => 'int(11) NOT NULL',
                'name' => 'varchar(64) NOT NULL',
            ),
            'ENGINE=InnoDB'
        );

        $this->createIndex('project', 'issue_label', array('project_id'), false);
        $this->createIndex('name', 'issue_label', array('project_id', 'name'), true);
        $this->addForeignKey('issue_label_project', 'issue_label', 'project_id', 'project', 'id', 'CASCADE', ' NO ACTION');

        $this->createTable('issue',
            array(
                'id' => 'pk',
                'title' => 'varchar(64) NOT NULL',
                'project_id' => 'int(11) NOT NULL',
                'github_issue_id' => 'int(11) NOT NULL',
                'open_date' => 'datetime NOT NULL',
                'close_date' => 'datetime NULL',
                'issue_label_id' => 'int(11) NOT NULL',
                'est_due' => 'datetime NULL',
                'est_hours' => 'float NULL',
                'start_date' => 'datetime NULL',
                'end_date' => 'datetime NULL',
                'spent_hours' => 'float NULL',
                'person_in_charge' => 'varchar(64) NULL',
            ),
            'ENGINE=InnoDB'
        );

        $this->createIndex('project_github_issue', 'issue', array('project_id', 'github_issue_id'), true);
        $this->createIndex('project', 'issue', array('project_id'), false);
        $this->addForeignKey('issue_project', 'issue', 'project_id', 'project', 'id', 'CASCADE', ' NO ACTION');

        $this->createTable('issue_activity',
            array(
                'id' => 'pk',
                'issue_id' => 'int(11) NOT NULL',
                'issue_label_id' => 'int(11) NOT NULL',
                'start_date' => 'datetime NOT NULL',
                'end_date' => 'datetime NULL',
                'assign_to' => 'varchar(64) NULL',
            ),
            'ENGINE=InnoDB'
        );

        $this->createIndex('issue_activity_label', 'issue_activity', array('issue_id', 'issue_label_id', 'start_date'), true);
        $this->createIndex('issue', 'issue_activity', array('issue_id'), false);
        $this->createIndex('label', 'issue_activity', array('issue_label_id'), false);
        $this->addForeignKey('issue_activity_issue', 'issue_activity', 'issue_id', 'issue', 'id', 'CASCADE', ' NO ACTION');
        $this->addForeignKey('issue_activity_issue_label', 'issue_activity', 'issue_label_id', 'issue_label', 'id', 'CASCADE', ' NO ACTION');
    }

    public function safeDown()
    {
        $this->dropTable('issue_activity');
        $this->dropTable('issue');
        $this->dropTable('issue_label');
        $this->dropTable('project');
    }
}
