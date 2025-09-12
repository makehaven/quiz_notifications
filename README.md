# Quiz Notifications

## Introduction

This module allows site administrators to configure and send email notifications to users upon the successful completion of a quiz. A notification is triggered only when a user achieves a perfect score (100%).

## Features

*   **Rule-Based Notifications:** Create multiple notification rules, each targeting a specific quiz.
*   **Custom Email Content:** For each rule, you can define a custom email subject and body.
*   **Token Support:** The email subject and body fields support tokens, allowing you to personalize the emails with information like the user's name (`[user:name]`) or the quiz title (`[quiz:title]`).
*   **100% Score Trigger:** Emails are only sent when a user scores 100% on the quiz.
*   **Simple Configuration:** A user-friendly interface allows you to add and remove notification rules easily.

## Dependencies

This module requires the following module to be installed and enabled:
*   **Token:** (https://www.drupal.org/project/token)

## Configuration

1.  **Install the module:** Enable the Quiz Notifications module as you would any other Drupal module.
2.  **Navigate to the settings page:** Go to **Configuration > Media > Quiz Notifications** in the Drupal admin menu (or go to `/admin/config/media/quiz_notifications`).
3.  **Add a new rule:** Click the "Add another rule" button to create a new notification rule.
4.  **Select a quiz:** Start typing the name of the quiz you want this rule to apply to in the "Select Quiz" field.
5.  **Enter email content:** Fill in the "Email Subject" and "Email Body" fields. You can use the "Browse available tokens" link to find and insert tokens.
6.  **Save:** Click the "Save configuration" button at the bottom of the page.
7.  **Add more rules:** You can add as many rules as you need by clicking the "Add another rule" button. To remove a rule, simply click the "Remove rule" button next to it.
