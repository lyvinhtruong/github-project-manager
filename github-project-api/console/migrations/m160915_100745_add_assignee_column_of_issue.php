<?php

use yii\db\Migration;

class m160915_100745_add_assignee_column_of_issue extends Migration
{
    public function up()
    {
        $this->addColumn(
            'issue',
            'assignee',
            $this->string()
        );
    }

    public function down()
    {
        $this->dropColumn(
            'issue',
            'assignee'
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
