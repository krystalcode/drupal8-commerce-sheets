<?php

namespace Drupal\commerce_sheets\Form;

use Drupal\commerce_sheets\Entity\ImportInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configuring general Commerce Sheets settings.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_sheets_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['commerce_sheets.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_sheets.settings');

    // Import mode. Determines whether Imports are executed upon creation or
    // added to a queue.
    $form['import_mode'] = [
      '#title' => $this->t('Import mode'),
      '#type' => 'radios',
      '#options' => [
        ImportInterface::IMPORT_MODE_ON_CREATION => $this->t(
          'Run imports immediately after creation'
        ),
        ImportInterface::IMPORT_MODE_QUEUE => $this->t(
          'Run imports in a queue'
        ),
      ],
      '#default_value' => $config->get('import.mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('commerce_sheets.settings')
      ->set('import.mode', $values['import_mode'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
