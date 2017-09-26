<?php

use yii\db\Migration;

class m170920_074055_modify_issue_table_remove_status_add_labeled_column extends Migration
{
    public function up()
    {
        $this->dropIndex(
            'idx-issue-type',
            'issue'
        );

        $this->dropColumn(
            'issue',
            'type'
        );

        $this->dropIndex(
            'idx-issue-status',
            'issue'
        );

        $this->dropColumn(
            'issue',
            'status'
        );

        $this->addColumn(
            'issue',
            'labels',
            $this->string()
        );
    }

    public function down()
    {
        $this->addColumn(
            'issue',
            'type',
            $this->integer()
        );

        $this->createIndex(
            'idx-issue-type',
            'issue',
            'type',
            false
        );

        $this->addColumn(
            'issue',
            'status',
            $this->integer()
        );

        $this->createIndex(
            'idx-issue-status',
            'issue',
            'status',
            false
        );

        $this->dropColumn(
            'issue',
            'labels'
        );
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
