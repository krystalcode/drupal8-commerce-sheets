<?php

namespace Drupal\commerce_sheets\Action;

use Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface;
use Drupal\commerce_sheets\Event\EntityFormatEvents;
use Drupal\commerce_sheets\Event\EntityFormatPreConstructEvent;
use Drupal\commerce_sheets\Sheet\WriterInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base class for all Commerce Sheets VBO action plugins.
 *
 * Provides a base mechanism for using the Writer service to export the selected
 * entities to a spreadsheet file.
 */
abstract class ExportBase extends ViewsBulkOperationsActionBase implements
  ContainerFactoryPluginInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity format plugin manager.
   *
   * @var \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface
   */
  protected $formatManager;

  /**
   * The Commerce Sheets writer service.
   *
   * @var \Drupal\commerce_sheets\Sheet\WriterInterface
   */
  protected $writer;

  /**
   * Constructs a new ExportBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface $format_manager
   *   The entity format plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_sheets\Sheet\WriterInterface $writer
   *   The Commerce Sheets writer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFormatManagerInterface $format_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    WriterInterface $writer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formatManager = $format_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->writer = $writer;
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
      $container->get('plugin.manager.commerce_sheets_entity_format'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher'),
      $container->get('commerce_sheets.writer')
    );
  }

  /**
   * Performs any validation required before processing the given entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities that will be processed.
   */
  abstract protected function validateEntities(array $entities);

  /**
   * Returns the ID of the entity format plugin that will be used.
   *
   * @return string
   *   The plugin ID.
   */
  abstract protected function getFormatPluginId();

  /**
   * Performs any alterations desired to the entity format plugin configuration.
   *
   * @param array $format_configuration
   *   The plugin configuration.
   */
  abstract protected function alterFormatconfiguration(
    array &$format_configuration
  );

  /**
   * {@inheritdoc}
   */
  public function execute() {}

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->validateEntities($entities);

    $entity = current($entities);

    $format_plugin_id = $this->getFormatPluginId();
    $format_configuration = [
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_bundle' => $entity->bundle(),
    ];
    $this->alterFormatConfiguration($format_configuration);

    $format = $this->formatManager->createInstance(
      $format_plugin_id,
      $format_configuration
    );

    $writer = $this->writer->write($entities, $format);
  }

}
