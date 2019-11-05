<?php

namespace Drupal\commerce_sheets\Entity\ListBuilder;

use Drupal\commerce_sheets\Entity\ImportType;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the Import entity type.
 */
class Import extends EntityListBuilder {

  /**
   * The Import Type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $typeStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ImportListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Core\Entity\ConfigEntityStorageInterface $type_storage
   *   The entity type storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    ConfigEntityStorageInterface $type_storage,
    DateFormatterInterface $date_formatter,
    RedirectDestinationInterface $redirect_destination
  ) {
    parent::__construct($entity_type, $storage);

    $this->typeStorage = $type_storage;
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id()),
      $entity_type_manager->getStorage($entity_type->getBundleEntityType()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['type'] = $this->t('Type');
    $header['state'] = $this->t('State');
    $header['uid'] = $this->t('Creator');
    $header['created'] = $this->t('Created');
    $header['completed'] = $this->t('Completed');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->toLink();
    $row['type'] = $this->typeStorage->load($entity->bundle())->label();
    $row['state'] = $entity->getState()->getLabel();
    $row['uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];

    // Times.
    $row['created'] = $this->dateFormatter->format(
      $entity->getCreatedTime(),
      'short'
    );
    $row['completed'] = '';
    $completed_time = $entity->getCompletedTime();
    if ($completed_time) {
      $row['completed'] = $this->dateFormatter->format(
        $entity->getCompletedTime(),
        'short'
      );
    }

    return $row + parent::buildRow($entity);
  }

}
