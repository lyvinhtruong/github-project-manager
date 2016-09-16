<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "issue_activity".
 *
 * @property integer $id
 * @property integer $issue_id
 * @property integer $issue_label_id
 * @property string $start_date
 * @property string $end_date
 * @property string $assign_to
 *
 * @property IssueLabel $issueLabel
 * @property Issue $issue
 */
class IssueActivity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'issue_activity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['issue_id', 'issue_label_id', 'start_date'], 'required'],
            [['issue_id', 'issue_label_id'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['assign_to'], 'string', 'max' => 255],
            [['issue_id', 'issue_label_id', 'start_date'], 'unique', 'targetAttribute' => ['issue_id', 'issue_label_id', 'start_date'], 'message' => 'The combination of Issue ID, Issue Label ID and Start Date has already been taken.'],
            [['issue_label_id'], 'exist', 'skipOnError' => true, 'targetClass' => IssueLabel::className(), 'targetAttribute' => ['issue_label_id' => 'id']],
            [['issue_id'], 'exist', 'skipOnError' => true, 'targetClass' => Issue::className(), 'targetAttribute' => ['issue_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'issue_id' => 'Issue ID',
            'issue_label_id' => 'Issue Label ID',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'assign_to' => 'Assign To',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssueLabel()
    {
        return $this->hasOne(IssueLabel::className(), ['id' => 'issue_label_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIssue()
    {
        return $this->hasOne(Issue::className(), ['id' => 'issue_id']);
    }
}
