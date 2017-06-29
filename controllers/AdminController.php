<?php

namespace app\controllers;

use app\components\BaseAdminController;
use Yii;

/**
 * Class AdminController
 * @package app\controllers
 */
class AdminController extends BaseAdminController
{

    /**
     * Displays admin index page.
     *
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'metronic';
        return $this->render('index');
    }

}
