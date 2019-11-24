<?php

namespace Drupal\commerce_sheets\Plugin\QueueWorker;

use Drupal\commerce_sheets\Sheet\ReaderInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Executes an Import.
 *
 * @QueueWorker(
 *   id = "commerce_sheets_import",
 *   title = @Translation("Execute imports"),
 *   cron = {"time" = 600}
 * )
 */
class Import extends QueueWorkerBase implements
  ContainerFactoryPluginInterface {

  /**
   * The Import storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $importStorage;

  /**
   * The Commerce Sheets logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Commerce Sheets reader service.
   *
   * @var \Drupal\commerce_sheets\Sheet\ReaderInterface
   */
  protected $reader;

  /**
   * Constructs a new LocaleTranslation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Commerce Sheets logger.
   * @param \Drupal\commerce_sheets\Sheet\ReaderInterface $reader
   *   The Commerce Sheets reader service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    ReaderInterface $reader
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->importStorage = $entity_type_manager
      ->getStorage('commerce_sheets_import');

    $this->logger = $logger;
    $this->reader = $reader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.channel.commerce_sheets'),
      $container->get('commerce_sheets.reader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    if (!isset($item->id)) {
      $message = sprintf(
        'No Import ID defined for the given commerce_sheets_import queue item.
         Queue item data: %s',
        json_encode($item)
      );
      $this->logger->error($message);
      return;
    }

    $import = $this->importStorage->load($item->id);
    if (!$import) {
      $message = sprintf(
        'The Import requested by the given commerce_sheets_import queue item
         does not exist. Queue item data: %s',
        json_encode($item)
      );
      $this->logger->error($message);
      return;
    }

    $state_item = $import->get('state')->first();
    $state_item->applyTransitionById('run');
    $import->save();

    try {
      // @I Properly inject service through constructor dependency injection
      $this->reader->read($import);
    }
    catch (\Exception $e) {
      $message = sprintf(
        'An error occurred while executing the Import with ID "%s" of type "%s"
         with message: %s',
        $import->id(),
        get_class($e),
        $e->getMessage()
      );
      $this->logger->error($message);

      $state_item->applyTransitionById('fail');
      $import->save();
      return;
    }

    $state_item->applyTransitionById('complete');
    $import->save();
  }

}
