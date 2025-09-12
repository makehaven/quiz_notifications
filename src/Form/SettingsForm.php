<?php

namespace Drupal\quiz_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quiz_notifications_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['quiz_notifications.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('quiz_notifications.settings');
    $rules = $config->get('rules') ?? [];

    $form['#tree'] = TRUE;
    $form['rules'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification Rules (for 100% Pass Rate)'),
      '#prefix' => '<div id="rules-wrapper">',
      '#suffix' => '</div>',
    ];

    $rule_count = $form_state->get('rule_count');
    if ($rule_count === NULL) {
      $rule_count = count($rules) > 0 ? count($rules) : 1;
      $form_state->set('rule_count', $rule_count);
    }

    for ($i = 0; $i < $rule_count; $i++) {
      $rule = $rules[$i] ?? [];
      $form['rules'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Rule #@num', ['@num' => $i + 1]),
        '#open' => TRUE,
      ];
      $form['rules'][$i]['quiz_id'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'quiz',                // Use the Quiz entity, not node
        '#selection_handler' => 'default:quiz',  // Explicit handler
        '#title' => $this->t('Select Quiz'),
        '#default_value' => isset($rule['quiz_id'])
          ? \Drupal::entityTypeManager()->getStorage('quiz')->load($rule['quiz_id'])
          : NULL,
        '#description' => $this->t('Start typing the quiz title.'),
      ];
      
      $form['rules'][$i]['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Email Subject'),
        '#default_value' => $rule['subject'] ?? '',
        '#maxlength' => 255,
      ];
      $form['rules'][$i]['body'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Email Body'),
        '#format' => $rule['body']['format'] ?? 'basic_html',
        '#default_value' => $rule['body']['value'] ?? '',
      ];
    }

    $form['actions']['add_rule'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another rule'),
      '#submit' => ['::addRule'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'rules-wrapper',
      ],
    ];

    // Add token support.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['user', 'node'],
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to rebuild the form.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['rules'];
  }

  /**
   * Submit handler for the "Add Rule" button.
   */
  public function addRule(array &$form, FormStateInterface $form_state) {
    $rule_count = $form_state->get('rule_count');
    $form_state->set('rule_count', $rule_count + 1);
    $form_state->setRebuild();
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('rules');
    // Filter out empty rules before saving.
    $filtered_rules = array_filter($values, function($rule) {
      return !empty($rule['quiz_id']);
    });
    

    $this->config('quiz_notifications.settings')
      ->set('rules', array_values($filtered_rules)) // Re-index the array.
      ->save();
    parent::submitForm($form, $form_state);
  }
}