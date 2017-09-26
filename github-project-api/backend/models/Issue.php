<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "issue".
 *
 * @property integer $id
 * @property string $title
 * @property integer $project_id
 * @property integer $github_issue_id
 * @property string $open_date
 * @property string $close_date
 * @property string $est_due
 * @property double $est_hours
 * @property string $start_date
 * @property string $end_date
 * @property double $spent_hours
 * @property string $person_in_charge
 * @property string $assignee
 * @property string $labels
 *
 * @property Project $project
 * @property IssueActivity[] $issueActivities
 */
class Issue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'issue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'project_id', 'github_issue_id', 'open_date'], 'required'],
            [['project_id', 'github_issue_id'], 'integer'],
            [['open_date', 'close_date', 'est_due', 'start_date', 'end_date'], 'safe'],
            [['est_hours', 'spent_hours'], 'number'],
            [['title', 'person_in_charge', 'assignee', 'labels'], 'string', 'max' => 255],
            [['project_id', 'github_issue_id'], 'unique', 'targetAttribute' => ['project_id', 'github_issue_id'], 'message' => 'The combination of Project ID and Github Issue ID has already been taken.'],
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
            'title' => 'Title',
            'project_id' => 'Project ID',
            'github_issue_id' => 'Github Issue ID',
            'open_date' => 'Open Date',
            'close_date' => 'Close Date',
            'est_due' => 'Est Due',
            'est_hours' => 'Est Hours',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'spent_hours' => 'Spent Hours',
            'person_in_charge' => 'Person In Charge',
            'assignee' => 'Assignee',
            'labels' => 'Labels',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['id' => 'project_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssueActivities()
    {
        return $this->hasMany(IssueActivity::className(), ['issue_id' => 'id']);
    }
}
