<?php

namespace Drupal\commerce_sheets\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * The event object for events that allow defining the value of a property.
 */
class WriterPropertyValueEvent extends Event {

  /**
   * The entity to which the property belongs.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The name of the property.
   *
   * @var string
   */
  protected $property;

  /**
   * The value of the property.
   *
   * @var mixed
   */
  protected $propertyValue;

  /**
   * Constructs a new WriterPropertyValueEvent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to which the property belongs.
   * @param string $property
   *   The name of the property.
   */
  public function __construct(EntityInterface $entity, $property) {
    $this->entity = $entity;
    $this->property = $property;
  }

  /**
   * Returns the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns the name of the property.
   *
   * @return string
   *   The name of the property.
   */
  public function getProperty() {
    return $this->property;
  }

  /**
   * Returns the value of the property.
   *
   * @return mixed
   *   The value of the property.
   */
  public function getPropertyValue() {
    return $this->propertyValue;
  }

  /**
   * Sets the value of the property.
   *
   * @param mixed $property_value
   *   The value of the property.
   */
  public function setPropertyValue($property_value) {
    $this->propertyValue = $property_value;
  }

}
