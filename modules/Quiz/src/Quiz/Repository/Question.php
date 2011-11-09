<?php

namespace Quiz\Repository;

use Doctrine\ORM\EntityRepository;
use Quiz\Entity;

/**
 * Question
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Question extends EntityRepository
{
    public function create(array $data)
    {
        $question = new Entity\Question();
        $question->setTitle($data['title']);
        $question->setContent($data['content']);
        $question->setType($data['type']);

        foreach($data['answers'] as $answerNo => $answerName)
        {
            $isCorrect = (isset($data['correct']) && $data['correct'] == $answerNo);

            $answer = new Entity\Answer();
            $answer->setName($answerName);
            $answer->setIsCorrect($isCorrect);
            $question->addAnswer($answer);
        }

        $em = $this->getEntityManager();
        $em->persist($question);

        try {
            $em->flush();
        } catch (\Exception $e) {
            var_dump(__METHOD__.__LINE__);
            var_dump($e->getMessage());
        }
    }

    public function update(array $data, $id)
    {
        $em = $this->getEntityManager();

        /* @var $question \Quiz\Entity\Question */
        $question = $this->find($id);
        $question->setTitle($data['title']);
        $question->setContent($data['content']);
        $question->setType($data['type']);

		$answerNo = 0;
        foreach($question->getAnswers() as $answer) 
		{
			++$answerNo;
			if (!isset($data['answers'][$answerNo])) {
                $em->remove($answer);
				continue;
			}

			$answerName = $data['answers'][$answerNo];
            $isCorrect = (isset($data['correct']) && $data['correct'] == $answerNo);

            $answer->setName($answerName);
            $answer->setIsCorrect($isCorrect);
        }

        //$em->persist($question);

        try {
            $em->flush();
        } catch (\Exception $e) {
            var_dump(__METHOD__.__LINE__);
            var_dump($e->getMessage());
        }
    }

    public function getDataForForm($id)
    {
        /* @var $question \Quiz\Entity\Question */
        $question = $this->find($id);

        $result = array();
        $result['title'] = $question->getTitle();
        $result['content'] = $question->getContent();
        $result['type'] = $question->getType();
        $result['answers'] = array();
        $result['correct'] = null;

        $i = 0;
        foreach($question->getAnswers() as $answer)
        {
            ++$i;
            if ($answer->getIsCorrect()) {
                $result['correct'] = $i;
            }
            $result['answers'][$i] = $answer->getName();
        }

        return $result;
    }
}