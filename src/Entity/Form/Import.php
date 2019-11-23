<?php

namespace Drupal\commerce_sheets\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Import entity add/edit forms.
 */
class Import extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $insert = $entity->isNew();
    $entity->save();

    $entity_bundle_label = $this->entityTypeManager
      ->getStorage('commerce_sheets_import_type')
      ->load($entity->bundle())
      ->label();

    $message_arguments = [
      '%link' => $entity->toLink($entity_bundle_label)->toString(),
    ];
    $logger_context = [
      '@type' => $entity->bundle(),
      '%id' => $entity->id(),
    ];

    // We should only have Import creations; editing Imports is not allowed.
    if ($insert) {
      $this->messenger()->addStatus(
        $this->t(
          'A new %link import for the uploaded file has been created.',
          $message_arguments
        )
      );
      $this->logger('commerce_sheets')->notice(
        '@type: a new import was created with ID %id',
        $logger_context
      );
    }

    $form_state->setRedirect(
      'entity.commerce_sheets_import.canonical',
      ['commerce_sheets_import' => $entity->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $entity = $this->getEntity();
    $state_item = $entity->get('state')->first();
    $state_item->applyTransitionById('run');
    $entity->save();

    try {
      // @I Properly inject service through constructor dependency injection
      $reader = \Drupal::service('commerce_sheets.reader');
      $reader->read($entity);
    }
    catch (\Exception $e) {
      $message = sprintf(
        'An error occurred while executing the Import with ID "%s" of type "%s"
         with message: %s',
        $entity->id(),
        get_class($e),
        $e->getMessage()
      );
      \Drupal::service('logger.channel.commerce_sheets')->error($message);

      $state_item->applyTransitionById('fail');
      $entity->save();
      return;
    }

    $state_item->applyTransitionById('complete');
    $entity->save();
  }

}
