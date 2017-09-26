<?php

use yii\db\Migration;

/**
 * Handles the creation for table `github_user`.
 */
class m170919_125344_create_github_user_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->createTable('github_user', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull(),
        ]);

        $this->createIndex(
            'idx-github_user-username',
            'github_user',
            ['username'],
            true
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->dropTable('github_user');
    }
}
