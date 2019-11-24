<?php

namespace Drupal\commerce_sheets\Entity\Form;

use Drupal\commerce_sheets\Entity\ImportInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the Import entity add/edit forms.
 *
 * Only the add form is supported at the moment. Imports are not meant to be
 * updated.
 */
class Import extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $import = $this->getEntity();
    $insert = $import->isNew();
    $import->save();

    $this->runOrQueueImport($import);
    $this->messageAndLog($import);

    $form_state->setRedirect(
      'entity.commerce_sheets_import.canonical',
      ['commerce_sheets_import' => $import->id()]
    );
  }

  /**
   * Runs the import or adds it to the queue depending on module settings.
   *
   * @param \Drupal\commerce_sheets\Entity\ImportInterface $import
   *   The import to run or queue.
   *
   * @throws \Exception
   *   When no import mode or an unsupported import mode is set in the module
   *   settings.
   */
  protected function runOrQueueImport(ImportInterface $import) {
    $config = \Drupal::service('config.factory')
      ->get('commerce_sheets.settings');
    $import_mode = $config->get('import.mode');
    switch ($import_mode) {
      case ImportInterface::IMPORT_MODE_ON_CREATION:
        $this->runImport($import);
        break;

      case ImportInterface::IMPORT_MODE_QUEUE:
        $this->queueImport($import);
        break;

      default:
        throw new \Exception(
          sprintf(
            'Unsupported Commerce Sheets import mode "%s"',
            $import_mode
          )
        );
    }
  }

  /**
   * Runs the given import.
   *
   * @param \Drupal\commerce_sheets\Entity\ImportInterface $import
   *   The import to run.
   */
  protected function runImport(ImportInterface $import) {
    $state_item = $import->get('state')->first();
    $state_item->applyTransitionById('run');
    $import->save();

    try {
      // @I Properly inject service through constructor dependency injection
      $reader = \Drupal::service('commerce_sheets.reader');
      $reader->read($import);
    }
    catch (\Exception $e) {
      $message = sprintf(
        'An error occurred while executing the Import with ID "%s" of type "%s"
         with message: %s',
        $import->id(),
        get_class($e),
        $e->getMessage()
      );
      \Drupal::service('logger.channel.commerce_sheets')->error($message);

      $state_item->applyTransitionById('fail');
      $import->save();
      return;
    }

    $state_item->applyTransitionById('complete');
    $import->save();
  }

  /**
   * Adds the given import to the queue.
   *
   * @param \Drupal\commerce_sheets\Entity\ImportInterface $import
   *   The import to queue.
   */
  protected function queueImport(ImportInterface $import) {
    // Add to queue.
    $queue = \Drupal::service('queue')->get('commerce_sheets_import');
    $item = new \stdClass();
    $item->id = $import->id();
    $queue->createItem($item);

    // Change state to Scheduled.
    $state_item = $import->get('state')->first();
    $state_item->applyTransitionById('schedule');
    $import->save();
  }

  /**
   * Displays a message to the user and logs the Import creation.
   *
   * @param \Drupal\commerce_sheets\Entity\ImportInterface $import
   *   The import that was created.
   */
  protected function messageAndLog(ImportInterface $import) {
    $import_bundle_label = $this->entityTypeManager
      ->getStorage('commerce_sheets_import_type')
      ->load($import->bundle())
      ->label();

    // Display a message to the user and redirect to the Import page.
    $message_arguments = [
      '%link' => $import->toLink($import_bundle_label)->toString(),
    ];
    $logger_context = [
      '@type' => $import->bundle(),
      '%id' => $import->id(),
    ];

    // We should only have Import creations; editing Imports is not allowed.
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

}
