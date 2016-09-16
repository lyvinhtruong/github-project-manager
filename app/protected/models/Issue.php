<?php

/**
 * This is the model class for table "issue".
 *
 * The followings are the available columns in table 'issue':
 * @property integer $id
 * @property string $title
 * @property integer $project_id
 * @property integer $github_issue_id
 * @property string $open_date
 * @property string $close_date
 * @property integer $issue_label_id
 * @property string $est_due
 * @property double $est_hours
 * @property string $start_date
 * @property string $end_date
 * @property double $spent_hours
 * @property string $person_in_charge
 *
 * The followings are the available model relations:
 * @property Project $project
 * @property IssueActivity[] $issueActivities
 */
class Issue extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'issue';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('title, project_id, github_issue_id, open_date, issue_label_id', 'required'),
			array('project_id, github_issue_id, issue_label_id', 'numerical', 'integerOnly'=>true),
			array('est_hours, spent_hours', 'numerical'),
			array('title, person_in_charge', 'length', 'max'=>64),
			array('close_date, est_due, start_date, end_date', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, title, project_id, github_issue_id, open_date, close_date, issue_label_id, est_due, est_hours, start_date, end_date, spent_hours, person_in_charge', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'project' => array(self::BELONGS_TO, 'Project', 'project_id'),
			'issueActivities' => array(self::HAS_MANY, 'IssueActivity', 'issue_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'title' => 'Title',
			'project_id' => 'Project',
			'github_issue_id' => 'Github Issue',
			'open_date' => 'Open Date',
			'close_date' => 'Close Date',
			'issue_label_id' => 'Issue Label',
			'est_due' => 'Est Due',
			'est_hours' => 'Est Hours',
			'start_date' => 'Start Date',
			'end_date' => 'End Date',
			'spent_hours' => 'Spent Hours',
			'person_in_charge' => 'Person In Charge',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('project_id',$this->project_id);
		$criteria->compare('github_issue_id',$this->github_issue_id);
		$criteria->compare('open_date',$this->open_date,true);
		$criteria->compare('close_date',$this->close_date,true);
		$criteria->compare('issue_label_id',$this->issue_label_id);
		$criteria->compare('est_due',$this->est_due,true);
		$criteria->compare('est_hours',$this->est_hours);
		$criteria->compare('start_date',$this->start_date,true);
		$criteria->compare('end_date',$this->end_date,true);
		$criteria->compare('spent_hours',$this->spent_hours);
		$criteria->compare('person_in_charge',$this->person_in_charge,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Issue the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
