<?php

namespace api\modules\v1\controllers;

use yii;
use yii\rest\ActiveController;
use api\modules\v1\models\Project;
use api\modules\v1\models\Issue;
use api\modules\v1\models\IssueLabel;
use api\modules\v1\models\IssueActivity;

class IssueController extends ActiveController
{
    public $modelClass = 'api\modules\v1\models\Issue';

    protected $requiredFields = [
        'actionFindAndCreate' => [
            'project_name',
            'github_issue_id',
            'title',
            'open_date'
        ],
        'actionUpdateStatus' => [
            'project_name',
            'github_issue_id',
            'status_label',
        ],
        'actionUpdateType' => [
            'project_name',
            'github_issue_id',
            'type_label',
        ],
        'actionAssign' => [
            'project_name',
            'github_issue_id',
        ],
        'actionEstimate' => [
            'project_name',
            'github_issue_id',
            'due_to',
        ],
    ];

    protected function validatePostParams()
    {
        $caller = $this->__getCaller();
        $caller = isset($caller['function']) ? $caller['function'] : null;

        if (!$caller) {
            return false;
        }

        $request = Yii::$app->request;
        $params = $request->post();

        foreach ($this->requiredFields[ $caller ] as $requiredField) {
            if (!array_key_exists($requiredField, $params)) {
                throw new \yii\base\InvalidParamException('Missing parameter: ' . $requiredField);
                return false;
            }
        }

        return $params;
    }

    public function actionFindAndCreate()
    {
        $params = $this->validatePostParams();

        if (!$params) {
            return false;
        }

        $issue = new Issue();

        // Check project exist and get it
        $project = Project::findOne([ 'name' => $params['project_name'] ]);

        if (!$project) {
            $project        = new Project();
            $project->name  = $params['project_name'];
            $project->save();
        }

        $issue->project_id      = $project->id;
        $issue->github_issue_id = $params['github_issue_id'];
        $issue->title           = $params['title'];
        $issue->open_date       = $params['open_date'];

        if (!empty($params['assignee'])) {
            $this->setAssignee($issue, $params['assignee']);
        }

        $issue->save();

        if (! empty($params['status'])) {
            // $status_change_date must be set from request
            $status_change_date = date('Y-m-d H:i:s');
            $this->updateIssueStatus(
                $issue,
                $params['status'],
                $issue->assignee,
                $status_change_date
            );
        }

        if (!empty($params['type'])) {
            // $type_change_date must be set from request
            $type_change_date = date('Y-m-d H:i:s');
            $this->updateIssueType(
                $issue,
                $params['type'],
                $type_change_date
            );
        }

        return $issue;
    }

    public function actionUpdateStatus()
    {
         // $project_id, $github_issue_id, $status_label, $assignee = null, $status_change_date = null
        $params = $this->validatePostParams();

        if (!$params) {
            return false;
        }

        $project = Project::findOne([ 'name' => $params['project_name'] ]);

        if (!$project) {
            throw new \yii\base\UserException('Project not found');
            return false;
        }

        $issue = Issue::findOne(
            [
                'project_id' => $project->id,
                'github_issue_id' => $params['github_issue_id']
            ]
        );
        
        return $this->updateIssueStatus(
            $issue,
            $params['status_label'],
            !empty($params['assignee']) ? $params['assignee'] : null,
            !empty($params['status_change_date']) ? $params['status_change_date'] : null
        );
    }

    public function actionUpdateType()
    {
         // $project_id, $github_issue_id, $status_label, $assignee = null, $status_change_date = null
        $params = $this->validatePostParams();

        if (!$params) {
            return false;
        }

        $project = Project::findOne([ 'name' => $params['project_name'] ]);

        if (!$project) {
            throw new \yii\base\UserException('Project not found');
            return false;
        }

        $issue = Issue::findOne(
            [
                'project_id' => $project->id,
                'github_issue_id' => $params['github_issue_id']
            ]
        );
        
        return $this->updateIssueType(
            $issue,
            $params['type_label'],
            !empty($params['type_change_date']) ? $params['type_change_date'] : null
        );
    }

    public function actionAssign()
    {
        $params = $this->validatePostParams();

        if (!$params) {
            return false;
        }

        $project = Project::findOne([ 'name' => $params['project_name'] ]);
        if (!$project) {
            throw new \yii\base\UserException('Project not found');
            return false;
        }

        $issue = $this->findExistingIssue($project->id, $params['github_issue_id']);
        if (!$issue) {
            return false;
        }

        $params['assignee'] = isset($params['assignee']) ? $params['assignee'] : null;
        if ($issue->assignee === $params['assignee']) {
            throw new \yii\base\UserException('Nothing to change');
            return false;
        }

        $this->setAssignee($issue, $params['assignee']);
        $issue->save();

        // If current issue is being in specific state, the related activity will be updated also
        if ($issue->status) {
            // Close old activity
            $issueActivity = IssueActivity::find()->where(
                [
                    'issue_id'          => $issue->id,
                    'issue_label_id'    => $issue->status,
                    'end_date'          => null,
                ]
            )->one();
            $issueActivity->end_date =  !empty($params['assignment_date']) ? $params['assignment_date'] : date('Y-m-d H:i:s');
            $issueActivity->save();

            // Open new activity
            $issueActivity                 = new IssueActivity();
            $issueActivity->issue_id       = $issue->id;
            $issueActivity->issue_label_id = $issue->status;
            $issueActivity->start_date     = !empty($params['assignment_date']) ? $params['assignment_date'] : date('Y-m-d H:i:s');
            $issueActivity->assign_to      = $issue->assignee;
            $issueActivity->save();
        }

        return $issue;
    }

    public function actionEstimate()
    {
        $params = $this->validatePostParams();

        if (!$params) {
            return false;
        }

        $project = Project::findOne([ 'name' => $params['project_name'] ]);
        if (!$project) {
            throw new \yii\base\UserException('Project not found');
            return false;
        }

        $issue = $this->findExistingIssue($project->id, $params['github_issue_id']);
        if (!$issue) {
            return false;
        }

        $params['duration'] = !empty($params['duration']) ? $params['duration'] : null;

        $this->setEstimation($issue, $params['due_to'], $params['duration']);

        return $issue;
    }

    protected function findExistingIssue($project_id, $github_issue_id)
    {
        $issue = Issue::findOne(
            [
                'project_id'        => $project_id,
                'github_issue_id'   => $github_issue_id,
            ]
        );

        return $issue;
    }

    protected function updateIssueStatus($issue, $status_label, $assignee = null, $status_change_date = null)
    {
        if (!$issue) {
            throw new \yii\base\UserException('Issue not found');
            return false;
        }

        $issueLabel = $this->__findOrCreateIssueLabel($status_label, $issue->project_id);

        /**
         * Update issue activity
         *
         * $issue->status : old issue status
         * Update end_date of old issue status
         */
        if ($issue->status) {
            $issueActivity = IssueActivity::find()->where(
                [
                    'issue_id'          => $issue->id,
                    'issue_label_id'    => $issue->status,
                    'end_date'          => null,
                ]
            )->one();

            if ($issueLabel->id === $issue->status && $issue->assignee === $assignee) {
                throw new \yii\base\UserException('Nothing to change');
                return false;
            }

            $issueActivity->end_date =  ! empty($status_change_date) ? $status_change_date : date('Y-m-d H:i:s');
            $issueActivity->save();
        }
        
        $issueActivity                 = new IssueActivity();
        $issueActivity->issue_id       = $issue->id;
        $issueActivity->issue_label_id = $issueLabel->id;
        $issueActivity->start_date     = ! empty($status_change_date) ? $status_change_date : date('Y-m-d H:i:s');
        $issueActivity->assign_to      = $assignee;
        $issueActivity->save();

        $issue->status = $issueLabel->id;
        $this->setAssignee($issue, $assignee);
        $issue->save();

        return $issue;
    }

    protected function updateIssueType($issue, $type_label, $type_change_date = null)
    {
        if (!$issue) {
            throw new \yii\base\UserException('Issue not found');
            return false;
        }

        $issueLabel = $this->__findOrCreateIssueLabel($type_label, $issue->project_id);

        /**
         * Update issue activity
         *
         * $issue->status : old issue status
         * Update end_date of old issue status
         */
        if ($issue->type) {
            $issueActivity = IssueActivity::find()->where(
                [
                    'issue_id'          => $issue->id,
                    'issue_label_id'    => $issue->type,
                    'end_date'          => null,
                ]
            )->one();
            if ($issueLabel->id === $issue->type) {
                throw new \yii\base\UserException('Nothing to change');
                return false;
            }
            $issueActivity->end_date =  ! empty($type_change_date) ? $type_change_date : date('Y-m-d H:i:s');
            $issueActivity->save();
        }
        
        $issueActivity                 = new IssueActivity();
        $issueActivity->issue_id       = $issue->id;
        $issueActivity->issue_label_id = $issueLabel->id;
        $issueActivity->start_date     = ! empty($type_change_date) ? $type_change_date : date('Y-m-d H:i:s');
        $issueActivity->save();

        $issue->type = $issueLabel->id;
        $issue->save();

        return $issue;
    }

    protected function setAssignee(&$issue, $assignee = null)
    {
        if (!$issue) {
            return false;
        }

        $personInCharge = json_decode($issue->person_in_charge);

        if (!is_array($personInCharge)) {
            $personInCharge = [];
        }

        if (!empty($assignee) && !in_array($assignee, $personInCharge)) {
            $personInCharge[] = $assignee;
        }

        $issue->assignee = $assignee;
        $issue->person_in_charge = json_encode($personInCharge);

        return true;
    }

    protected function setEstimation($issue, $est_due, $est_formated_time = null)
    {
        if (!$issue) {
            return false;
        }

        $hours = $this->convertWorkingHours($est_formated_time);
        $issue->est_hours = $hours;

        $issue->est_due = $est_due;
        $issue->save();
        return $issue;
    }

    protected function convertWorkingHours($time)
    {
        $pattern = '/([0-9]+)d([0-9]+)h([0-9]+)m/i';
        $test = preg_match($pattern, $time);

        if (!$test) {
            return null;
        }

        $time = preg_replace($pattern, '$1 $2 $3', $time);
        list($days, $hours, $minutes) = explode(' ', $time);
        $hours += $days * 8;
        $hours += $minutes / 60;
        return $hours;
    }

    private function __findOrCreateIssueLabel($label_name, $project_id)
    {
        $issueLabel = IssueLabel::findOne(
            [
                'name'          => $label_name,
                'project_id'    => $project_id,
            ]
        );

        if (!$issueLabel) {
            $issueLabel             = new IssueLabel();
            $issueLabel->name       = $label_name;
            $issueLabel->project_id = $project_id;
            $issueLabel->save();
        }

        return !empty($issueLabel) ? $issueLabel : false;
    }

    /**
     * Gets the caller of the function where this function is called from
     * @param string what to return? (Leave empty to get all, or specify: "class", "function", "line", "class", etc.) - options see: http://php.net/manual/en/function.debug-backtrace.php
     */
    private function __getCaller($what = null)
    {
        $trace = debug_backtrace();
        $previousCall = $trace[2]; // 0 is this call, 1 is call in previous function, 2 is caller of that function

        if (isset($what)) {
            return $previousCall[$what];
        } else {
            return $previousCall;
        }
    }

    // public function actionDelete()
    // {
    //     return $this->render('delete');
    // }

    // public function actionIndex()
    // {
    //     return $this->render('index');
    // }

    // public function actionList()
    // {
    //     return $this->render('list');
    // }

    // public function actionUpdate()
    // {
    //     return $this->render('update');
    // }

    // public function actionView()
    // {
    //     return $this->render('view');
    // }
}
