<?php
/**
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var \yii\web\View $this
 *
 */
$this->title = Yii::t('course', 'My courses');
?>

<div class="panel panel-default">
    <div class="panel-heading">
        Мои курсы
    </div>
    <div class="panel-body">
        <?php if( !$dataProvider->getCount() ): ?>
            <p class="text-muted text-center">
                Здесь пусто!
            </p>
            <p class="text-muted text-center">
                Мало просто зарегистрироваться, нужно ещё и <strong><a href="<?= \yii\helpers\Url::to(['subscription/all']) ?>">выбрать себе какой-нибудь курс</a></strong>.
            </p>
        <?php endif; ?>

        <?php foreach( $dataProvider->getModels() as $course ): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a href="<?= \yii\helpers\Url::to(['subscription/view', 'id' => $course->id]) ?>"><img src="/i/testcourse.jpg" style="width: 300px;" />
                    <label>Курс:</label>
                    <strong><?= $course->name ?></strong></a>
                </div>
                <div class="panel-body">
                    <?php $progress = $course->getProgress( Yii::$app->user->id ) ?>
                    <label>Выполнено по курсу:</label>
                    <strong><?= $progress ?>%</strong>
                    <div class="progress">
                        <div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $progress ?>%">
                        </div>
                    </div>
                    <div class="pull-left">
                        <a href="<?= \yii\helpers\Url::to(['subscription/view', 'id' => $course->id]) ?>" class="btn btn-primary">Перейти к программе курса</a>
                    </div>
                    <div class="pull-right">
                        <a href="<?= \yii\helpers\Url::to(['subscription/unsubscribe', 'id' => $course->id]) ?>" class="btn btn-default">Отписка! (Не получать новые тесты по курсу)</a>
                    </div>
                </div>
            </div>
        <?php endforeach;?>
    </div>
</div>