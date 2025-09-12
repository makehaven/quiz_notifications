<?php

namespace Drupal\quiz_notifications\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\quiz\Entity\QuizResult;

class QuizResultSubscriber implements EventSubscriberInterface {

  protected $configFactory;
  protected $mailManager;
  protected $token;

  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, Token $token) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->token = $token;
  }

  public static function getSubscribedEvents() {
    // Use the raw event name string 'entity.insert' instead of the class constant.
    // This is more compatible across all Drupal 10/11 versions.
    return [
      'entity.insert' => 'onEntityInsert',
    ];
  }

  public function onEntityInsert(EntityInterface $entity) {
    if (!$entity instanceof QuizResult) {
      return;
    }

    /** @var \Drupal\quiz\Entity\QuizResult $quiz_result */
    $quiz_result = $entity;

    // We only care about 100% scores.
    if ($quiz_result->getScore() != 100) {
      return;
    }

    $config = $this->configFactory->get('quiz_notifications.settings');
    $rules = $config->get('rules') ?? [];
    $quiz = $quiz_result->getQuiz();
    $completed_quiz_id = $quiz->id();
    foreach ($rules as $rule) {
      if (!empty($rule['quiz_id']) && (int) $rule['quiz_id'] === (int) $completed_quiz_id) {
        $this->sendNotificationEmail($rule, $quiz_result);
      }
    }
    
  }

  protected function sendNotificationEmail(array $rule, QuizResult $quiz_result) {
    $user = $quiz_result->getOwner();
    $to = $user->getEmail();

    // Use the Token service to replace any tokens in the subject and body.
    $token_data = [
        'user' => $user,
        'quiz' => $quiz_result->getQuiz(),
      ];
      $subject = $this->token->replace($rule['subject'], $token_data);
      $body = $this->token->replace($rule['body']['value'], $token_data);
      

    $params = [
      'subject' => $subject,
      'body' => $body,
    ];

    $this->mailManager->mail('quiz_notifications', 'quiz_passed_100', $to, $user->getPreferredLangcode(), $params, null, true);
  }
}