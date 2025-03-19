<?php

namespace Drupal\farm_grazing_plan\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plan\Entity\PlanInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\farm_grazing_plan\GrazingPlanInterface;
use Drupal\farm_grazing_plan\Bundle\GrazingEventInterface;

/**
 * Grazing plan remove event form
 */
class GrazingPlanRemoveEventForm extends FormBase {

    /**
     * Entity type manager
     * 
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The grazing plan service.
     *
     * @var \Drupal\farm_grazing_plan\GrazingPlanInterface
     */
    protected GrazingPlanInterface $grazingPlan;

    /**
     * GrazingPlanRemoveEventForm constructor.
     * 
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *  Entity type manager.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *  The current Request object.
     * @param \Drupal\farm_grazing_plan\GrazingPlanInterface $grazing_plan
     *  The grazing plan service
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager, GrazingPlanInterface $grazing_plan) {
        $this->entityTypeManager = $entity_type_manager;
        $this->grazingPlan = $grazing_plan;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('farm_grazing_plan'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'farm_grazing_plan_remove_event_form';
    }

    /**
     * Helper function for populating the form selection with grazing events
     * 
     * Saves the id to the form_state with the label as the key
     * Returns an array of grazing event labels
     */
    public function loadGrazingEvents(PlanInterface $plan, FormStateInterface $form_state): array {
        $grazing_events = $this->grazingPlan->getGrazingEvents($plan);
        $options = [];

        foreach ($grazing_events as $grazing_event) {
            $options[] = $grazing_event->label();
            $form_state->set($grazing_event->label()->render(), $grazing_event->id());
        }

        return $options;
    }

    /**
     * Title callback.
     * 
     * @param \Drupal\plan\Entity\PlanInterface|null $plan
     *  The plan entity.
     * 
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup
     *  Returns the title.
     */
    public function title(?PlanInterface $plan = NULL) {
        if (empty($plan)) {
            return $this->t('Remove grazing event');
        }
        return $this->t('Remove grazing event from @plan', ['@plan' => $plan->label()]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, ?PlanInterface $plan = NULL) {
        if (empty($plan)) {
            return $form;
        }
        $form_state->set('plan_id', $plan->id());

        $form['event'] = [
            '#type' => 'select',
            '#title' => $this->t('Select grazing event:'),
            '#options' => $this->loadGrazingEvents($plan, $form_state),
            '#required' => TRUE,
        ];

        $form['scope'] = [
            '#type' => 'radios',
            '#title' => $this->t('Delete:'),
            '#options' => [
                $this->t('Grazing event only'),
                $this->t('Grazing event & movement log'),
            ],
            '#required' => TRUE,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];
        
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $plan_id = $form_state->get('plan_id');

        $label = $form['event']['#options'][$form_state->getValue('event')];

        $asset_id = $form_state->get($label->render());
        $grazing_event = $this->entityTypeManager->getStorage('plan_record')->load($asset_id);
        $log = NULL;

        //\Drupal::logger('farm_grazing_plan')->debug(json_encode($form_state->getValue('scope')));
        
        if ($form_state->getValue('scope') == 1) {
            $log = $grazing_event->getLog();
        }

        $grazing_event->delete();
        if (!empty($log)) {
            $log->delete();
        }

        $this->messenger()->addMessage($this->t('Deleted @grazing_event', ['@grazing_event' => $label]));
        $form_state->setRedirect('entity.plan.canonical', ['plan' => $plan_id]);
    }
}