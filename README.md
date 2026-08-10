# Quiz Notifications

## Introduction

This module carries MakeHaven's behaviour on top of the contributed Quiz module. It does two things: it sends configurable email notifications when a quiz is passed, and it removes the feedback page that a correct answer does not need.

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

## Taking flow: feedback only when it teaches something

Quiz's "show feedback after the question" setting is all-or-nothing. Turn it on and every submitted answer, right or wrong, is followed by a page of its own whose only control is a **Next question** button.

On MakeHaven's New Member Quiz that setting is on, and the quiz is also set to *repeat until correct* with a 100% pass rate. Those two together mean a **wrong** answer never reaches the feedback page at all — Quiz catches it in validation, redisplays the question with the explanation inline, and refuses to advance. So the feedback page a member actually saw was always the one confirming they were right: six questions cost twelve page loads, half of them telling members what they already knew. Members said so.

This module lets Quiz queue the feedback page as usual and then takes it back out of the way when there is nothing to explain. On submit, if every graded question on the page is marked correct, the redirect goes straight to the next question instead — or, on the last question, straight to the score page. If anything is wrong, unanswered, or still awaiting manual grading, the feedback page is left alone.

The behaviour is site-wide and needs no configuration: it only has an effect on quizzes that have per-question feedback enabled, and it never suppresses feedback about a wrong answer. As of writing, the New Member Quiz is the only quiz on the site with that setting on; the badge quizzes have it off and are unaffected.

There is deliberately no settings form. If a future quiz genuinely wants to reinforce correct answers with an interstitial, that is the moment to add a per-quiz opt-out rather than carry an unused config object until then.

It is implemented as a single `hook_form_FORM_ID_alter()` on `quiz_question_answering_form` that appends a submit handler after Quiz's own. Nothing is subclassed or overridden, so a Quiz module update cannot silently invert the behaviour — at worst the redirect it inspects changes name and the feature stops having an effect.

Covered by `playwright-tests/tests/quiz-flow.spec.ts` in the main site repository.
