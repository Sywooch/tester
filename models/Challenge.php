<?php

namespace app\models;

use app\helpers\Subset;
use Yii;
use yii\db\ActiveQuery;

/**
 * @inheritdoc
 *
 * @property string $mode
 * @property ChallengeSettings $settings
 */
class Challenge extends \app\models\ar\Challenge
{
    const MODE_STATIC = 'static';
    const MODE_DYNAMIC = 'dynamic';
    const MODE_RANDOM = 'random';

    private $_settings = null;

    /**
     * Get free chalanges
     * @return ActiveQuery
     */
    static public function findFree()
    {
        return self::find()->with([
            'challengeSettings' => function (\yii\db\ActiveQuery $query) {
                $query->andWhere([
                    'registration_required' => false,
                    'subscription_required' => false,
                ]);
            }
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'course_id' => Yii::t('course', 'Course'),
            'challenge_type_id' => Yii::t('challengeType', 'Challenge Type'),
            'element_id' => Yii::t('element', 'Element'),
            'subject_id' => Yii::t('subject', 'Subject'),
            'grade_number' => Yii::t('challenge', 'Grade Number'),
            'name' => Yii::t('challenge', 'Name'),
            'description' => Yii::t('challenge', 'Description'),
            'exercise_number' => Yii::t('challenge', 'Exercise Number'),
            'exercise_challenge_number' => Yii::t('challenge', 'Exercise Challenge Number'),
            'challengeHasQuestions' => Yii::t('challenge', 'Challenge Has Questions'),
            'challengeGenerations' => Yii::t('challenge', 'Challenge Generations'),
        ];
    }

    /**
     * Get modes list
     * @return array
     */
    public function modeLabels()
    {
        return [
            self::MODE_STATIC => Yii::t('challenge', 'Mode Static'),
            self::MODE_DYNAMIC => Yii::t('challenge', 'Mode Dynamic'),
            self::MODE_RANDOM => Yii::t('challenge', 'Mode Random'),
        ];
    }

    /**
     * @return int
     */
    public function getChallengeGenerationsCount()
    {
        $count = 0;
        $questions = parent::getChallengeGenerations()->all();
        foreach ($questions as $question) {
            /** @var ChallengeHasQuestion $question */
            $q = $question->getQuestion()->one();
            /** @var \app\models\ar\Question $q */
            if ($q->question_type_id === QuestionType::TYPE_THREE_QUESTION) {
                $count += 3;
            } else {
                $count ++;
            }
        }

        return $count;
    }

    /**
     * @return int
     */
    public function getChallengeHasQuestionsCount()
    {
        $count = 0;
        $questions = parent::getChallengeHasQuestions()->orderBy(['position' => SORT_ASC])->all();
        foreach ($questions as $question) {
            /** @var ChallengeHasQuestion $question */
            $q = $question->getQuestion()->one();
            /** @var \app\models\ar\Question $q */
            if ($q->question_type_id === QuestionType::TYPE_THREE_QUESTION) {
                $count += 3;
            } else {
                $count ++;
            }
        }
        return $count;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this
            ->hasMany(Question::className(), ['id' => 'question_id'])
            ->viaTable('challenge_has_question', ['challenge_id' => 'id'], function ($query) {
                $query->orderBy(['position' => SORT_ASC]);
            });
    }

    /**
     * @return ChallengeSettings
     */
    public function getSettings() {
        if ( is_null($this->_settings) ) {
            $this->_settings = $this->hasOne(ChallengeSettings::className(), ['challenge_id' => 'id'])->inverseOf('challenge')->one();
        }

        return $this->_settings;
    }

    /**
     * Get question generation mode
     * @return string
     */
    public function getMode()
    {
        $questions = $this->getChallengeHasQuestionsCount();
        $rules = $this->getChallengeGenerationsCount();

        if ($questions && $rules) {
            return self::MODE_DYNAMIC;
        } elseif ($questions) {
            return self::MODE_STATIC;
        } elseif ($rules) {
            return self::MODE_RANDOM;
        } else {
            return self::MODE_STATIC;
        }
    }

    /**
     * Set question generation mode
     * @param $mode
     * @param array $data
     */
    public function setMode($mode, $data = null)
    {
        ChallengeGeneration::deleteAll(['challenge_id' => $this->id]);
        ChallengeHasQuestion::deleteAll(['challenge_id' => $this->id]);

        switch ($mode) {
            case self::MODE_STATIC:
                Subset::save(
                    ChallengeHasQuestion::className(),
                    $data,
                    ['challenge_id' => $this->id]
                );
                break;
            case self::MODE_DYNAMIC:
                Subset::save(
                    ChallengeHasQuestion::className(),
                    $data,
                    ['challenge_id' => $this->id]
                );
                Subset::save(
                    ChallengeGeneration::className(),
                    $data,
                    ['challenge_id' => $this->id]
                );
                break;
            case self::MODE_RANDOM:
                Subset::save(
                    ChallengeGeneration::className(),
                    $data,
                    ['challenge_id' => $this->id]
                );
                break;
        }
    }

    /**
     * Get questions count in this challenge
     * @return int
     */
    public function getQuestionsCount()
    {
        switch ($this->getMode()) {
            case self::MODE_STATIC:
            case self::MODE_DYNAMIC:
                return $this->getChallengeHasQuestionsCount();

            case self::MODE_RANDOM:
                $result = 0;
                foreach ($this->getChallengeGenerations() as $rule) {
                    try {
                        $result += $rule->question_count;
                    } catch (\Exception $e) {
                        $result += 0;
                    }
                }
                return $result;

            default:
                return 0;
        }
    }

    public function getAttemptsCount($user)
    {
        return $this->getAttempts()->where(['user_id' => is_object($user) ? $user->id : $user])->count();
    }

    public function getAttemptsElementsCount($element_id, $challenge_id, $challenge_element_id)
    {
        $attempts = [];
        if ($element_id == 1 && $challenge_element_id == 1) {
            $attempts = Attempt::find()->where(['challenge_id' => $challenge_id])->andWhere(['user_id' =>  Yii::$app->user->id])->all();
        }
        if ($element_id == 2 && $challenge_element_id == 2) {
            $attempts = Attempt::find()->where(['challenge_id' => $challenge_id])->andWhere(['user_id' =>  Yii::$app->user->id])->all();
        }
        return count($attempts);
    }

    public function getElementChallengesCount($course_id, $element_id){
        $challenges = Challenge::find()->where(['course_id' => $course_id])->andWhere(['element_id' => $element_id])->all();
        return count($challenges);
    }
    
    public function getChallengesByWeeks($courseChallenges) {
        $testByWeeks = [];
        foreach ($courseChallenges as $number => $challenge) {
            if ($challenge->week == $number) {
                $testByWeeks[$challenge->week][] = $challenge->id;
            }
        }
        return $testByWeeks;
    }

    public function getMarks($user_id, $challenge_id)
    {
        return Attempt::find()->select(['mark'])
            ->where(['user_id' => $user_id])
            ->andWhere(['challenge_id' => $challenge_id])
            ->all();
    }

    public function getAllChallengeAttempts($challenge_id)
    {
        return Attempt::find()->select(['id'])
            ->where(['challenge_id' => $challenge_id])
            ->all();
    }

    public function getAllChallengeMarks($challenge_id)
    {
        return Attempt::find()->select(['mark'])
            ->where(['challenge_id' => $challenge_id])
            ->all();
    }

    public function getCourseName($course_ids, $challenge_course_id)
    {
        $name = '';
        foreach($course_ids as $course_id) {
            if ($course_id->id == $challenge_course_id->course_id) {
                $name = $challenge_course_id->name;
                break;
            }
        }
        return $name;
    }

    public function getAllChallengeUsers($challenge_id)
    {
        return Attempt::find()
            ->select(['user_id'])
            ->where(['challenge_id' => $challenge_id])
            ->groupBy('user_id')
            ->all();
    }

    public function getUserById($id)
    {
        return User::find()->where(['id' => $id]);
    }

    public function getChallengeFood($id)
    {
        //$food_id = ChallengeFood::find()->select('food_id')->where(['challenge_id' => $id])->one();
        //$challengeFood = Food::find()->select('food_name')->where(['id' => $food_id])->one();

        $challengeElementsItem = Challenge::find()->select('elements_item_id')->where(['id' => $id])->one();
        $challengeFood = ElementsItem::find()->select('name')->where(['id' => $challengeElementsItem])->one();
        return $challengeFood;
    }

    public function getChallengeClean($id)
    {
        //$food_id = ChallengeFood::find()->select('food_id')->where(['challenge_id' => $id])->one();
        //$challengeFood = Food::find()->select('food_name')->where(['id' => $food_id])->one();

        $challengeElementsItem = Challenge::find()->select('elements_item_id')->where(['id' => $id])->one();
        $challengeClean = ElementsItem::find()->select('name')->where(['id' => $challengeElementsItem])->one();
        return $challengeClean;
    }

}
