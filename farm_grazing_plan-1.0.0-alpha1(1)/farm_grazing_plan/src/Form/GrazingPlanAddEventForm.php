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
      $container->get('request_stack')->getCurrentRequest(),
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

    $form['alert_options'] = [
      '#type' => 'details',
      '#title'=> $this->t('Alert Settings'),
      '#open' => TRUE,
    ];
    //ADDED
    $form['alert_options']['enable_time_conflict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable time conflicts'),
      //'#description' => $this->('When checked, you will enable time conflicts'),
      '#default_value' => FALSE,
    ];


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

    // If a log ID was provided via query parameter, load it and set the
    // form value.
    $log_id = $this->request->get('log');
    if ($log_id) {
      $log = $this->entityTypeManager->getStorage('log')->load($log_id);
      if (!empty($log) && $log->bundle() == 'activity') {
        $form['log']['#default_value'] = $log;
        $form_state->setValue('log', $log_id);
      }
    }

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

    $form['details']['start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Planned start date/time'),
      '#default_value' => $default_values['start'],
      '#required' => TRUE,
    ];

    $form['details']['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Duration (hours)'),
      '#step' => 1,
      '#min' => 1,
      '#max' => 8760,
      '#default_value' => $default_values['duration'],
      '#required' => TRUE,
    ];

    $form['details']['recovery'] = [
      '#type' => 'number',
      '#title' => $this->t('Recovery (hours)'),
      '#step' => 1,
      '#min' => 1,
      '#max' => 8760,
      '#default_value' => $default_values['recovery'],
    ];

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
   */
  public function grazingEventDefaultValues($log = NULL) {

    // Start with defaults.
    $values = [
      'start' => new DrupalDateTime('midnight', $this->currentUser()->getTimeZone()),
      'duration' => NULL,
      'recovery' => NULL,
    ];

    // If a log was provided, load the start date from it.
    if (!is_null($log) && $log instanceof LogInterface) {
      $values['start'] = DrupalDateTime::createFromTimestamp($log->get('timestamp')->value);
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Require log.
    $log_id = $form_state->getValue('log');
    if (empty($log_id)) {
      $form_state->setErrorByName('log', $this->t('Select a movement log.'));
      return;
    }
  
    // Check for existing grazing_event records for the plan and log.
    $plan_id = $form_state->get('plan_id');
    $existing = $this->entityTypeManager->getStorage('plan_record')->getQuery()
      ->accessCheck(FALSE)
      ->condition('plan', $plan_id)
      ->condition('log', $log_id)
      ->count()
      ->execute();
    if ($existing > 0) {
      $form_state->setErrorByName('log', $this->t('This log is already part of the plan.'));
    }
    \Drupal::logger('farm_grazing_plan')->debug('Form values: ' . json_encode($form_state->getValues()));

    // Only check for time conflicts if the enable_time_conflict checkbox is NOT checked
    if (!$form_state->getValue('enable_time_conflict')) {
      $start_time = $form_state->getValue('start')->getTimestamp();
      $duration_hours = $form_state->getValue('duration');
      $end_timestamp = $start_time + (intval($duration_hours) * 3600);
      
      // Log for debugging
      \Drupal::logger('farm_grazing_plan')->debug('Checking time conflicts: Start @start, End @end', [
        '@start' => date('Y-m-d H:i:s', $start_time),
        '@end' => date('Y-m-d H:i:s', $end_timestamp),
      ]);
      
      // Load all plan records of type grazing_event
      $record_ids = $this->entityTypeManager->getStorage('plan_record')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'grazing_event')
        ->condition('plan', $plan_id)
        ->execute();
        
      if (!empty($record_ids)) {
        $records = $this->entityTypeManager->getStorage('plan_record')->loadMultiple($record_ids);
        
        foreach ($records as $existing_record) {
          // Skip if this is the same log
          if ($existing_record->get('log')->target_id == $log_id) {
            continue;
          }
          
          $existing_start = $existing_record->get('start')->value;
          $existing_duration = $existing_record->get('duration')->value;
          
          if (empty($existing_duration)) {
            continue; // Skip if no duration
          }
          
          $existing_end = $existing_start + ($existing_duration * 3600);
          
          // checking for conflict -- new start is before existing end AND new end is after existing start
          if ($start_time < $existing_end && $end_timestamp > $existing_start) {
            $form_state->setErrorByName('details][start', $this->t("This time conflicts with an existing log",));
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
    $details = $form_state->getValue('details');
    $enable_time_conflict = (bool)$form_state->getValue('enable_time_conflict');
    $shift_overlapping = !$enable_time_conflict;
    \Drupal::state()->set('log_reschedule.shift_overlapping', $shift_overlapping);
    \Drupal::logger('addevent')->notice('enable_time_conflict: ' . ($enable_time_conflict ? 'TRUE' : 'FALSE'));
    
    $plan_id = $form_state->get('plan_id');
    $log = $form_state->getValue('log');
    $record = PlanRecord::create([
      'type' => 'grazing_event',
      'plan' => $plan_id,
      'log' => $log,
      'start' => $form_state->getValue('start')->getTimestamp(),
      'duration' => $form_state->getValue('duration'),
      'recovery' => $form_state->getValue('recovery'),
    ]);
    $record->save();
    $this->messenger()->addMessage($this->t('Added @grazing_event', ['@grazing_event' => $record->label()]));
    $form_state->setRedirect('entity.plan.canonical', ['plan' => $plan_id]);

  
    }
    


  //ADDED
  /**
   * Check if the proposed timestamp conflicts with existing logs
   * @param int $proposed_timestamp
   *  Proposed timestamp from user
   * @param FormStateInterface $form_state
   */
  /**protected function checkTimeConflicts($proposed_timestamp){
    $proposed_timestamp = getTimeStamp()
    $query = $this->entityTypeManager->getStorage('log')->getQuery()
    ->accessCheck(FALSE)
    ->condition('timestamp', $proposed_timestamp)
    ->range(0,1);
    $results = $query->execute();
    \Drupal::logger('log')->info('Checking conflicts for timestamp: ' . $proposed_timestamp . '. Found logs: ' . json_encode($results));

    if (!empty($results)){
      return TRUE;
    }
    return FALSE;

  } */
}
