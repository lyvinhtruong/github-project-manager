<?php

namespace api\modules\v1\controllers;

use yii\rest\ActiveController;

class ProjectController extends ActiveController
{
    public $modelClass = '\api\modules\v1\models\Project';

    public function actionGetByName($name)
    {
        $model = \api\modules\v1\models\Project::findOne(['name' => $name]);
        
        if ($model) {
            return $model;
        }
        throw new \yii\web\NotFoundHttpException('Object not found');
    }

    // public function actionCreate()
    // {
    //     return $this->render('create');
    // }

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
