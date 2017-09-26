<?php

namespace api\modules\v1\controllers;

use yii;
use yii\rest\ActiveController;
use api\modules\v1\models\Project;
use api\modules\v1\models\Issue;
use api\modules\v1\models\IssueLabel;
use api\modules\v1\models\IssueActivity;
use api\modules\v1\models\GithubUser;

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
        // 'actionUpdateStatus' => [
        //     'project_name',
        //     'github_issue_id',
        //     'status_label',
        // ],
        // 'actionUpdateType' => [
        //     'project_name',
        //     'github_issue_id',
        //     'type_label',
        // ],
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

    public function actionRunWebHook()
    {
        $request = Yii::$app->request;
        $params = $request->post();
        $data = json_decode( $params['payload'] );
        $result = false;

        // On new issue created
        if ( isset($data->action) ) {

            $assignees = array();
            if ( isset($data->issue->assignees) ) {
                foreach ( $data->issue->assignees as $assignee ) {
                    if ( isset($assignee->login) ) {
                        $assignees[] = $assignee->login;
                    }
                }
            }

            switch ($data->action) {
                case 'opened':
                    $params = array(
                        'project_name' => $data->repository->full_name,
                        'github_issue_id' => $data->issue->id,
                        'title' => $data->issue->title,
                        'open_date' => $data->issue->created_at,
                        'assignees' => $assignees,
                    );
                    $result = $this->findAndCreate( $params );
                    break;

                case 'assigned':
                    // $params = array(
                    //     'assignees'
                    // );
                    break;
                
                default:
                    # code...
                    break;
            }
        }

        return $result;
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

        // If request params has 'assignees', set assignment activity to user with 'Open' status
        if (!empty($params['assignees'])) {
            $params['labels'] = array('Open');
            // $this->setAssignee($issue, $params['assignees']);
        }

        $issue->save();

        if (! empty($params['labels']) ) {
            // $status_change_date must be set from request
            $status_change_date = date('Y-m-d H:i:s');
            $this->updateIssueLabels(
                $issue,
                $params['labels'],
                $params['assignees'],
                $status_change_date
            );
        }

        // if (!empty($params['type'])) {
        //     // $type_change_date must be set from request
        //     $type_change_date = date('Y-m-d H:i:s');
        //     $this->updateIssueType(
        //         $issue,
        //         $params['type'],
        //         $type_change_date
        //     );
        // }

        return $issue;
    }

    // public function actionUpdateStatus()
    // {
    //      // $project_id, $github_issue_id, $status_label, $assignee = null, $status_change_date = null
    //     $params = $this->validatePostParams();

    //     if (!$params) {
    //         return false;
    //     }

    //     $project = Project::findOne([ 'name' => $params['project_name'] ]);

    //     if (!$project) {
    //         throw new \yii\base\UserException('Project not found');
    //         return false;
    //     }

    //     $issue = Issue::findOne(
    //         [
    //             'project_id' => $project->id,
    //             'github_issue_id' => $params['github_issue_id']
    //         ]
    //     );
        
    //     return $this->updateIssueLabels(
    //         $issue,
    //         $params['status_label'],
    //         !empty($params['assignee']) ? $params['assignee'] : null,
    //         !empty($params['status_change_date']) ? $params['status_change_date'] : null
    //     );
    // }

    // public function actionUpdateType()
    // {
    //      // $project_id, $github_issue_id, $status_label, $assignee = null, $status_change_date = null
    //     $params = $this->validatePostParams();

    //     if (!$params) {
    //         return false;
    //     }

    //     $project = Project::findOne([ 'name' => $params['project_name'] ]);

    //     if (!$project) {
    //         throw new \yii\base\UserException('Project not found');
    //         return false;
    //     }

    //     $issue = Issue::findOne(
    //         [
    //             'project_id' => $project->id,
    //             'github_issue_id' => $params['github_issue_id']
    //         ]
    //     );
        
    //     return $this->updateIssueType(
    //         $issue,
    //         $params['type_label'],
    //         !empty($params['type_change_date']) ? $params['type_change_date'] : null
    //     );
    // }

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

    protected function updateIssueLabels(&$issue, $input_labels, $assignees = null, $status_change_date = null)
    {
        if (!$issue) {
            throw new \yii\base\UserException('Issue not found');
            return false;
        }

        $current_issue_labels = json_decode($issue->labels);

        if ( !is_array($current_issue_labels) ) {
            $current_issue_labels = array();
        }

        $new_issue_label_ids = array();
        $removed_issue_label_ids = array();
        $input_label_ids = array();

        foreach ($input_labels as $status_label) {
            $issue_label = $this->__findOrCreateIssueLabel($status_label, $issue->project_id);
            $input_label_ids[] = $issue_label->id;
            if ( !in_array($issue_label->id, $current_issue_labels) ) {
                $new_issue_label_ids[] = $issue_label->id;
            }
        }

        foreach ($current_issue_labels as $label_id) {
            if ( !in_array($label_id, $input_label_ids) ) {
                $removed_issue_label_ids[] = $input_label_ids;
            }
        }

        /**
         * Update issue activity
         *
         * $issue->status : old issue status
         * Update end_date of old issue status
         */
        foreach ($new_issue_label_ids as $label_id) {
            $issueActivity                 = new IssueActivity();
            $issueActivity->issue_id       = $issue->id;
            $issueActivity->issue_label_id = $label_id;
            $issueActivity->start_date     = ! empty($status_change_date) ? $status_change_date : date('Y-m-d H:i:s');

            if ( ! empty($assignees) ) {
                $issueActivity->assign_to  = json_encode($assignees);
            }

            $issueActivity->save();
        }

        /**
         * Find and close some activities that related to $removed_issue_label_ids
         */
        if ( count($removed_issue_label_ids) > 0 ) {
            $issueActivities = IssueActivity::find()->where(
                [
                    'issue_id'          => $issue->id,
                    'issue_label_id'    => $removed_issue_label_ids,
                    'end_date'          => null,
                ]
            )->one();
            $issueActivities->end_date =  ! empty($status_change_date) ? $status_change_date : date('Y-m-d H:i:s');
            $issueActivities->save();
        }

        // Update issue again
        $issue->labels = json_encode($new_issue_label_ids);
        $this->setAssignee($issue, $assignees);
        $issue->save();

        return $issue;
    }

    protected function setAssignee(&$issue, $assignees = null)
    {
        if (!$issue) {
            return false;
        }

        $personInCharge = json_decode($issue->person_in_charge);

        if (!is_array($personInCharge)) {
            $personInCharge = array();
        }

        if ( !empty($assignees) ) {
            if ( !is_array($assignees) ) {
                $assignees = array($assignees);
            }
            foreach ( $assignees as $assignee ) {
                if ( !in_array($assignee, $personInCharge) ) {
                    $personInCharge[] = $assignee;
                }
            }
        }

        $issue->assignee = json_encode($assignees);
        $issue->person_in_charge = json_encode($personInCharge);

        $this->updateUserFromPersonInCharge($personInCharge);

        return true;
    }

    private function updateUserFromPersonInCharge($personInCharge)
    {
        if ( !is_array($personInCharge) ) {
            $personInCharge = array($personInCharge);
        }
        foreach ( $personInCharge as $ghUser ) {
            $user = GithubUser::findOne([ 'username' => $ghUser ]);
            if (!$user) {
                $user        = new GithubUser();
                $user->username  = $ghUser;
                $user->save();
            }
        }
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
