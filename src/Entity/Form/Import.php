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

}
