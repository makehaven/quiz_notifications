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

    // Use the form state for rules if it's been set (e.g., by an AJAX operation).
    // Otherwise, load from config. This makes AJAX operations like add/remove reliable.
    if ($form_state->isRebuilding()) {
      $rules = $form_state->get('rules');
    }
    else {
      $rules = $config->get('rules') ?? [];
      $form_state->set('rules', $rules);
    }

    $form['#tree'] = TRUE;
    $form['rules'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification Rules (for 100% Pass Rate)'),
      '#prefix' => '<div id="rules-wrapper">',
      '#suffix' => '</div>',
    ];

    // If there are no rules, start with one empty one.
    if (empty($rules)) {
      $rules = [[]];
      $form_state->set('rules', $rules);
    }

    foreach ($rules as $i => $rule) {
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
        '#required' => TRUE,
      ];
      $form['rules'][$i]['body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Email Body'),
        '#default_value' => $rule['body'] ?? '',
        '#required' => TRUE,
      ];
      $form['rules'][$i]['remove_rule'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove rule #@num', ['@num' => $i + 1]),
        '#submit' => ['::removeRule'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'rules-wrapper',
        ],
        '#name' => 'remove_rule_' . $i,
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
    // Add a new empty rule to the form state and rebuild.
    $rules = $form_state->get('rules');
    $rules[] = [];
    $form_state->set('rules', $rules);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "Remove Rule" button.
   */
  public function removeRule(array &$form, FormStateInterface $form_state) {
    // Get the index of the rule to remove from the button name.
    $triggering_element = $form_state->getTriggeringElement();
    $rule_index = $triggering_element['#parents'][1];

    // Remove the rule from the form state and rebuild.
    $rules = $form_state->get('rules');
    unset($rules[$rule_index]);
    $form_state->set('rules', array_values($rules)); // Re-index the array.

    $form_state->setRebuild();
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rules = $form_state->getValue('rules');

    // Filter out any rules where the quiz_id is not set.
    $valid_rules = array_filter($rules, function ($rule) {
        return !empty($rule['quiz_id']);
    });
    
    $this->config('quiz_notifications.settings')
      ->set('rules', array_values($valid_rules)) // Re-index after filtering.
      ->save();

    parent::submitForm($form, $form_state);
  }
}