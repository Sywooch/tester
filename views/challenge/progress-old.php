<?php
use yii\widgets\ActiveForm;
use app\widgets\AnswerEditor;

$currentQuestion = $session->getCurrentQuestionNumber();
$totalQuestions = $challenge->getQuestionsCount();

$question = $session->getCurrentQuestion();

$summary = \app\helpers\ChallengeSummarizer::fromSession( $session );

/**
 * @var \app\helpers\ChallengeSummarizer $summary
 * @var \app\models\Question $question
 * @var \app\models\Challenge $challenge
 * @var \app\helpers\ChallengeSession $session
 */
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= $challenge->name ?>
        <div class="pull-right text-right" style="width: 30%;">
            Задание <?= $currentQuestion + 1 ?> из <?= $totalQuestions ?>
        </div>
        <div class="progress">
            <?php if( $challenge->settings->immediate_result ): ?>
                <?php $correctness = $summary->getCorrectness() ?>
                <?php foreach( $summary->getQuestions() as $i => $q ): ?>

                    <?php $comment = ["<strong>Задание ".( $i+1 )." из $totalQuestions</strong>"] ?>
                    <?php if( $correctness[$q->id] ):?>
                        <?php $comment[] = 'Выполнено правильно' ?>
                    <?php else: ?>
                        <?php $comment[] = 'Выполнено с ошибками' ?>
                        <?php $comment[] = $q->getComment(true) ?>
                        <?php if(count($mistakes = $summary->getMistakes($q))) $comment[] = '<ul><li>'.implode( '<li>', $mistakes ).'</ul>' ?>

                    <?php endif; ?>

                    <div
                        class="progress-bar progress-bar-<?= $correctness[$q->id] ? 'success' : 'danger' ?>"
                        style="width: <?= floor( 100 / $totalQuestions ) ?>%"
                        data-toggle="tooltip"
                        data-placement="bottom"
                        data-html="true"
                        title="<?= htmlspecialchars( implode( '<p></p>', $comment ) ) ?>"
                    ></div>
                <?php endforeach;?>
            <?php else: ?>
                <div class="progress-bar progress-bar-info" style="width: <?= floor( $currentQuestion / $totalQuestions * 100) ?>%"></div>
            <?php endif;?>
        </div>
    </div>
    <div class="panel-body">
        <?= $question->getText() ?>

        <?php $form = ActiveForm::begin([
            'action' => ['challenge/answer', 'id' => $challenge->id],
            'method' => 'post'
        ]); ?>

        <?php echo AnswerEditor::widget([
            'name' => 'answer',
            'question' => $question
        ]) ?>

        <div class="hint-content" style="display: none;">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="general-item-list">
                        <div class="item">
                            <div class="item-head">
                                <div class="item-details">
                                    <img class="item-pic" src="/i/hintemoticon.jpg">
                                    <div class="item-name primary-link"><strong>Кошка подсказывает:</strong></div>
                                </div>
                            </div>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if( $challenge->settings->immediate_result ): ?>
            <?php// $correctness = $summary->getCorrectness() ?>
            <?php// foreach( $summary->getQuestions() as $i => $q ): ?>
            <?php// if( $correctness[$q->id] ):?>

            <div class="comment-content" style="display: none; border: solid; border-color: <?php// $correctness[$q->id] ? 'green' : 'red' ?>">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="general-item-list">
                            <div class="item">
                                <div class="item-head">
                                    <div class="item-details">
                                        <img class="item-pic" src="/i/hintemoticon.jpg">
                                        <div class="item-name primary-link"><strong>Кошка объясняет:</strong></div>
                                    </div>
                                </div>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php //endif; ?>
            <?php// endforeach;?>
        <?php endif; ?>

        <div class="row question-buttons">
            <div class="col-xs-6 col-md-6 text-left">
                <a href="#" class="btn btn-primary answer-button comment-button">Ответить</a>
                <?php if( $session->isCommentUsed() ): ?>
                    <input type="submit" class="btn btn-success continue-button" value="Продолжить">
                <?php endif; ?>
            </div>
            <div class="col-xs-6 col-md-6 text-right">
                <a href="#" class="btn btn-primary hint-button">Подсказать</a>

                <?php if( $session->getCurrentQuestionNumber() < $challenge->getQuestionsCount() - 1 ): ?>
                    <a href="<?= \yii\helpers\Url::toRoute(['challenge/skip', 'id' => $challenge->id]) ?>" class="btn btn-warning ">Пропустить</a>
                <?php endif; ?>
                <a href="<?= \yii\helpers\Url::toRoute(['challenge/finish', 'id' => $challenge->id]) ?>" class="btn btn-danger">Завершить</a>
            </div>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>

<script>
    $(function() {

        function showHint(hint) {
            $('.hint-content span').html(hint);
            $('.hint-content').show();
            $('.hint-button').hide();
        }

        $('.hint-button').click( function() {
            $.get('<?= \yii\helpers\Url::to(['challenge/hint', 'id' => $challenge->id]) ?>', function(data) {
                showHint(data);
            });

            return false;
        } );

        function showComment(comment) {
            $('.comment-content span').html(comment);
            $('.comment-content').show();
            $('.comment-button').hide();
        }

        $('.comment-button').click( function() {
            $.get('<?= \yii\helpers\Url::to(['challenge/comment', 'id' => $challenge->id]) ?>', function(data) {
                showComment(data);
            });

            return false;
        } );

        function showAnswer(comment) {
            $('.comment-content span').html(comment);
            $('.comment-content').show();
            $('.answer-button').hide();
        }

        $('.answer-button').click( function() {
            $.get('<?= \yii\helpers\Url::to(['challenge/preanswer', 'id' => $challenge->id]) ?>', function(data) {
                showAnswer(data);
            });

            return false;
        } );

        <?php if( $session->isHintUsed() ): ?>
        showHint(<?= \yii\helpers\Json::encode( $session->hint() ) ?>);
        <?php endif;?>

        <?php if( $session->isCommentUsed() ): ?>
        showAnswer(<?= \yii\helpers\Json::encode( $session->comment() ) ?>);
        <?php endif;?>

        <?php if($challenge->settings->immediate_result ): ?>
        $('.progress-bar[data-toggle="tooltip"]').tooltip();
        <?php endif; ?>
    });
</script>

<?php //\yii\helpers\VarDumper::dump($summary->getCorrectness(), 10, true)?>
<?php \yii\helpers\VarDumper::dump($summary->getQuestions(), 10, true)?>
<?php foreach ($summary->getQuestions() as $number => $question):?>
    <?= $number ?>
<?php endforeach; ?>