<?php

namespace Drupal\farm_grazing_plan\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\log\Entity\LogInterface;
use Drupal\plan\Entity\PlanInterface;
use Drupal\plan\Entity\PlanRecord;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Grazing plan add event form.
 */
class GrazingPlanAddEventForm extends FormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * GrazingPlanAddEventForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current Request object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Request $request) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_grazing_plan_add_event_form';
  }

  /**
   * Title callback.
   *
   * @param \Drupal\plan\Entity\PlanInterface|null $plan
   *   The plan entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns the title.
   */
  public function title(?PlanInterface $plan = NULL) {
    if (empty($plan)) {
      return $this->t('Add grazing event');
    }
    return $this->t('Add grazing event to @plan', ['@plan' => $plan->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?PlanInterface $plan = NULL) {
    if (empty($plan)) {
      return $form;
    }
    $form_state->set('plan_id', $plan->id());

    // Alert options: enable time conflict checking.
    $form['alert_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Alert Settings'),
      '#open' => TRUE,
    ];
    $form['alert_options']['enable_time_conflict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable time conflicts'),
      // When checked, time conflicts are NOT validated.
      '#default_value' => FALSE,
    ];

    // Movement log field.
    $form['log'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Movement log'),
      '#target_type' => 'log',
      '#selection_settings' => [
        'target_bundles' => ['activity'],
      ],
      '#ajax' => [
        'wrapper' => 'grazing-event-details',
        'callback' => [$this, 'grazingEventDetailsCallback'],
        'event' => 'autocompleteclose change',
      ],
      '#required' => TRUE,
    ];

    // If a log ID is provided via the query parameter, load it and set
    // the form value.
    $log_id = $this->request->get('log');
    if ($log_id) {
      $log = $this->entityTypeManager->getStorage('log')->load($log_id);
      if (!empty($log) && $log->bundle() == 'activity') {
        $form['log']['#default_value'] = $log;
        $form_state->setValue('log', $log_id);
      }
    }

    // Details container for the event settings.
    $form['details'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'grazing-event-details',
      ],
    ];

    // If the form is being built with a log selected, reset grazing event
    // details and populate their default values.
    $log = NULL;
    if ($form_state->getValue('log')) {
      $this->resetGrazingEventDetails($form_state);
      $log = $this->entityTypeManager->getStorage('log')->load($form_state->getValue('log'));
    }
    $default_values = $this->grazingEventDefaultValues($log);

    // Planned start time.
    $form['details']['start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Planned start date/time'),
      '#default_value' => $default_values['start'],
      '#required' => TRUE,
    ];

    // New Planned/Actual radios.
    $form['details']['planned'] = [
      '#type' => 'radios',
      '#title' => $this->t('Planned/Actual'),
      '#options' => [
        0 => $this->t('Planned'),
        1 => $this->t('Actual'),
      ],
      '#default_value' => $default_values['planned'],
      '#required' => TRUE,
    ];

    // Duration field (hours).
    $form['details']['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Duration (hours)'),
      '#step' => 1,
      '#min' => 1,
      '#max' => 8760,
      '#default_value' => $default_values['duration'],
      '#required' => TRUE,
    ];

    // Recovery field (hours).
    $form['details']['recovery'] = [
      '#type' => 'number',
      '#title' => $this->t('Recovery (hours)'),
      '#step' => 1,
      '#min' => 1,
      '#max' => 8760,
      '#default_value' => $default_values['recovery'],
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Ajax callback for grazing event details.
   */
  public function grazingEventDetailsCallback(array $form, FormStateInterface $form_state) {
    return $form['details'];
  }

  /**
   * Reset grazing event details.
   */
  public function resetGrazingEventDetails(FormStateInterface $form_state) {
    $details_fields = [
      'start',
      'planned',
      'duration',
      'recovery',
    ];
    $user_input = $form_state->getUserInput();
    foreach ($details_fields as $field_name) {
      unset($user_input[$field_name]);
    }
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }

  /**
   * Get default values for grazing event details.
   *
   * @param \Drupal\log\Entity\LogInterface|null $log
   *   A movement log (optional).
   *
   * @return array
   *   Returns a keyed array of grazing event default values, including:
   *   - start
   *   - duration
   *   - recovery
   *   - planned
   */
  public function grazingEventDefaultValues($log = NULL) {
    // Set up defaults.
    $values = [
      'start' => new DrupalDateTime('midnight', $this->currentUser()->getTimeZone()),
      'duration' => NULL,
      'recovery' => NULL,
      'planned' => 0,
    ];

    // If a log is provided, load the start date from it.
    if (!is_null($log) && $log instanceof LogInterface) {
      $values['start'] = DrupalDateTime::createFromTimestamp($log->get('timestamp')->value);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure a movement log is selected.
    $log = $form_state->getValue('log');
    if (empty($log)) {
      $form_state->setErrorByName('log', $this->t('Select a movement log.'));
      return;
    }

    // Check for duplicate grazing event records for the plan and log.
    $plan_id = $form_state->get('plan_id');
    $existing = $this->entityTypeManager->getStorage('plan_record')->getQuery()
      ->accessCheck(FALSE)
      ->condition('plan', $plan_id)
      ->condition('log', $log)
      ->count()
      ->execute();
    if ($existing > 0) {
      $form_state->setErrorByName('log', $this->t('This log is already part of the plan.'));
    }

    // Only check for time conflicts if the "enable time conflicts" checkbox
    // is NOT checked.
    if (!$form_state->getValue('enable_time_conflict')) {
      $start_time = $form_state->getValue('start')->getTimestamp();
      $duration_hours = $form_state->getValue('duration');
      $end_timestamp = $start_time + (intval($duration_hours) * 3600);

      // Log debugging information.
      \Drupal::logger('farm_grazing_plan')->debug('Checking time conflicts: Start @start, End @end', [
        '@start' => date('Y-m-d H:i:s', $start_time),
        '@end' => date('Y-m-d H:i:s', $end_timestamp),
      ]);

      // Load all grazing event records for the current plan.
      $record_ids = $this->entityTypeManager->getStorage('plan_record')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'grazing_event')
        ->condition('plan', $plan_id)
        ->execute();

      if (!empty($record_ids)) {
        $records = $this->entityTypeManager->getStorage('plan_record')->loadMultiple($record_ids);

        foreach ($records as $existing_record) {
          // Skip if this record uses the same log.
          if ($existing_record->get('log')->target_id == $log) {
            continue;
          }

          $existing_start = $existing_record->get('start')->value;
          $existing_duration = $existing_record->get('duration')->value;

          if (empty($existing_duration)) {
            continue; // Skip if no duration is set.
          }

          $existing_end = $existing_start + ($existing_duration * 3600);

          // Check for overlapping times:
          // if new start is before existing end AND new end is after existing start.
          if ($start_time < $existing_end && $end_timestamp > $existing_start) {
            $form_state->setErrorByName('details][start', $this->t("This time conflicts with an existing log"));
            break;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()){
      return;
    }
    $plan_id = $form_state->get('plan_id');
    $log = $form_state->getValue('log');

    $record = PlanRecord::create([
      'type' => 'grazing_event',
      'plan' => $plan_id,
      'log' => $log,
      'start' => $form_state->getValue('start')->getTimestamp(),
      'duration' => $form_state->getValue('duration'),
      'recovery' => $form_state->getValue('recovery'),
      'planned' => $form_state->getValue('planned'),
    ]);
    $record->save();

    $this->messenger()->addMessage($this->t('Added @grazing_event', ['@grazing_event' => $record->label()]));
    $form_state->setRedirect('entity.plan.canonical', ['plan' => $plan_id]);
  }

  // Optional: Additional time conflict checking method.
  /**
   * Check if the proposed timestamp conflicts with existing logs.
   *
   * @param int $proposed_timestamp
   *   Proposed timestamp from user.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if conflicts are found, FALSE otherwise.
   */
  /*
  protected function checkTimeConflicts($proposed_timestamp, FormStateInterface $form_state) {
    $query = $this->entityTypeManager->getStorage('log')->getQuery()
      ->accessCheck(FALSE)
      ->condition('timestamp', $proposed_timestamp)
      ->range(0, 1);
    $results = $query->execute();
    \Drupal::logger('log')->info('Checking conflicts for timestamp: ' . $proposed_timestamp . '. Found logs: ' . json_encode($results));

    return !empty($results);
  }
  */
}
