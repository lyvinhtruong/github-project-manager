<?php

namespace api\modules\v1\models;

use Yii;

/**
 * This is the model class for table "issue_label".
 *
 * @property integer $id
 * @property integer $project_id
 * @property string $name
 *
 * @property Issue[] $issues
 * @property Issue[] $issues0
 * @property IssueActivity[] $issueActivities
 * @property Project $project
 */
class IssueLabel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'issue_label';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id', 'name'], 'required'],
            [['project_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['project_id', 'name'], 'unique', 'targetAttribute' => ['project_id', 'name'], 'message' => 'The combination of Project ID and Name has already been taken.'],
            [['project_id'], 'exist', 'skipOnError' => true, 'targetClass' => Project::className(), 'targetAttribute' => ['project_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'project_id' => 'Project ID',
            'name' => 'Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssues()
    {
        return $this->hasMany(Issue::className(), ['status' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssues0()
    {
        return $this->hasMany(Issue::className(), ['type' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssueActivities()
    {
        return $this->hasMany(IssueActivity::className(), ['issue_label_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['id' => 'project_id']);
    }
}
