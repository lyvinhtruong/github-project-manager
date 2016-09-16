<?php

use yii\db\Migration;

class m160913_035441_create_app_tables extends Migration
{
    public function up()
    {
        $this->createTable('project',
            [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
            ]
        );

        $this->createTable('issue_label',
            [
                'id' => $this->primaryKey(),
                'project_id' => $this->integer()->notNull(),
                'name' => $this->string()->notNull(),
            ]
        );

        $this->createIndex(
            'idx-issue_label-project_id',
            'issue_label',
            'project_id',
            false
        );

        $this->createIndex(
            'idx-issue_label-project_id-name',
            'issue_label',
            ['project_id', 'name'],
            true
        );

        $this->addForeignKey(
            'fk-issue_label-project_id',
            'issue_label',
            'project_id',
            'project',
            'id',
            'CASCADE',
            'NO ACTION'
        );

        $this->createTable('issue',
            [
                'id' => $this->primaryKey(),
                'title' => $this->string()->notNull(),
                'project_id' => $this->integer()->notNull(),
                'github_issue_id' => $this->integer()->notNull(),
                'open_date' => $this->dateTime()->notNull(),
                'close_date' => $this->dateTime(),
                'type' => $this->integer(),
                'status' => $this->integer(),
                'est_due' => $this->dateTime(),
                'est_hours' => $this->float(),
                'start_date' => $this->dateTime(),
                'end_date' => $this->dateTime(),
                'spent_hours' => $this->float(),
                'person_in_charge' => $this->string(),
            ]
        );

        $this->createIndex(
            'idx-issue-project_id-github_issue_id',
            'issue',
            ['project_id', 'github_issue_id'],
            true
        );

        $this->createIndex(
            'idx-issue-project_id',
            'issue',
            'project_id',
            false
        );

        $this->createIndex(
            'idx-issue-type',
            'issue',
            'type',
            false
        );

        $this->createIndex(
            'idx-issue-status',
            'issue',
            'status',
            false
        );

        $this->addForeignKey(
            'fk-issue-project_id',
            'issue',
            'project_id',
            'project',
            'id',
            'CASCADE',
            ' NO ACTION'
        );

        $this->createTable('issue_activity',
            [
                'id' => $this->primaryKey(),
                'issue_id' => $this->integer()->notNull(),
                'issue_label_id' => $this->integer()->notNull(),
                'start_date' => $this->dateTime()->notNull(),
                'end_date' => $this->dateTime(),
                'assign_to' => $this->string(),
            ]
        );

        $this->createIndex(
            'idx-issue_activity-issue_id-issue_label_id-start_date',
            'issue_activity',
            ['issue_id', 'issue_label_id', 'start_date'],
            true
        );

        $this->createIndex(
            'idx-issue_activity-issue_id',
            'issue_activity',
            'issue_id',
            false
        );

        $this->createIndex(
            'idx-issue_activity-issue_label_id',
            'issue_activity',
            'issue_label_id',
            false
        );

        $this->addForeignKey(
            'fk-issue_activity-issue_id',
            'issue_activity',
            'issue_id',
            'issue',
            'id',
            'CASCADE',
            'NO ACTION'
        );

        $this->addForeignKey(
            'fk-issue_activity-issue_label_id',
            'issue_activity',
            'issue_label_id',
            'issue_label',
            'id',
            'CASCADE',
            'NO ACTION'
        );
    }

    public function down()
    {
        $this->dropTable('issue_activity');
        $this->dropTable('issue');
        $this->dropTable('issue_label');
        $this->dropTable('project');
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
