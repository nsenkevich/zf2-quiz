<?php

namespace Quiz\Repository;

use Doctrine\ORM\EntityRepository,
    Quiz\Entity\User;

/**
 * Quiz
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Quiz extends EntityRepository
{
    public function getQuestions(User $user)
    {
        $em = $this->getEntityManager();

        $dql = 'SELECT q, a FROM Quiz\Entity\Question q JOIN q.answers a';

        /** @var $q  \Doctrine\ORM\QueryBuilder */
        $q = $em->createQuery($dql);
        $q->setMaxResults(10*3);

        $result = array();

        try {
            $result = $q->getArrayResult();
        } catch (\Exception $e) {
            
        }

        $maxQuestion = 10;

        /*
         * DQL do not allowe me to sqlect only a.name and a.id and mantaine array structure.
         */
        foreach($result as &$question)
        {
            foreach($question['answers'] as &$answer)
            {
                unset($answer['isCorrect']);
            }

            if (--$maxQuestion < 1) {
                break;
            }
        }

        $quiz = new \Quiz\Entity\Quiz();
        $quiz->setUser($user);
        $quiz->setIsClose(false);

        try
        {
            $em->persist($quiz);
            $em->flush();
        } catch (\Exception $e) {
            # todo log
            return false;
        }

        return array(
            'questions' => $result,
            'quizId' => $quiz->getId()
        );
    }

    public function getResultsForThisWeek()
    {
        $em = $this->getEntityManager();

        $startDate = date('Y-m-d', mktime(0,0,0, date('m'), date('d') - date('N') + 1, date('Y')));
        $endDate = date('Y-m-d', mktime(0,0,0, date('m'), date('d') - (date('N') - 7), date('Y')));

        $dql = 'SELECT SUM(a.second) points, COALESCE(u.fullname, u.username) fullname, u.username FROM Quiz\Entity\Quiz q '.
               'JOIN q.user u '.
               'JOIN q.answers a '.
               'JOIN a.answer aa '.
               'WHERE q.date BETWEEN :startData AND :endDate '.
               'AND q.isClose = true AND aa.isCorrect = true '.
               'GROUP BY u.id, u.fullname, u.username ' .
               'ORDER BY points DESC';

        /** @var $q  \Doctrine\ORM\Query */
        $q = $em->createQuery($dql);
        $q->setMaxResults(10);
        $q->setParameter('startData', $startDate);
        $q->setParameter('endDate', $endDate);

        $result = array();

        try {
            $result = $q->getArrayResult();
        } catch (\Exception $e) {
            # todo log
            return false;
        }

        return $result;
    }

    public function saveAnswersForQuiz($quizId, $facebookUserId, array $answers)
    {
        /** @var $quiz \Quiz\Entity\Quiz */
        $quiz = $this->find($quizId);
        if ($quiz->getIsClose()) {
            # todo log
            return false;
        }

        if ($quiz->getUser()->getFacebookId() != $facebookUserId) {
            # todo log
            return false;
        }

        $em = $this->getEntityManager();

        /** @var $answerRepository \Quiz\Entity\Answer */
        $answerRepository = $em->getRepository('Quiz\Entity\Answer');

        foreach($answers as $data)
        {
            $quizAnswer = new \Quiz\Entity\QuizAnswer();

            if (isset($data['answerId']))
            {
                $questionId = (int) $data['questionId'];
                $answerId   = (int) $data['answerId'];
                $second     = (int) $data['second'];

                /** @var $answer \Quiz\Entity\Answer  */
                $answer = $answerRepository->find($answerId);
                if (!$answer instanceof \Quiz\Entity\Answer) {
                    # todo log
                    return false;
                }

                if ($answer->getQuestion()->getId() != $questionId) {
                    # todo log
                    return false;
                }

                $quizAnswer->setAnswer($answer);
            }
            else
            {
                $answerId   = null;
                $second     = 0;
            }

            $quizAnswer->setSecond($second);

            $quiz->addAnswer($quizAnswer);
        }

        $quiz->setIsClose(true);

        try
        {
            $em->persist($quiz);
            $em->flush($quiz);
        } catch (\Exception $e) {
            # todo log
            return false;
        }

        return true;
    }

    public function getResults()
    {
        $dql = 'SELECT q, a FROM Quiz\Entity\Question q JOIN q.answers a';


        /** @var $q  \Doctrine\ORM\QueryBuilder */
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setMaxResults(10);

        $result = array();

        try {
            $result = $q->getArrayResult();
        } catch (\Exception $e) {

        }

        return $result;
    }
}