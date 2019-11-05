<?php

namespace Drupal\commerce_sheets\Entity;

use Drupal\user\UserInterface;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the default Import entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_sheets_import",
 *   label = @Translation("Import"),
 *   label_collection = @Translation("Imports"),
 *   label_singular = @Translation("import"),
 *   label_plural = @Translation("imports"),
 *   label_count = @PluralTranslation(
 *     singular = "@count import",
 *     plural = "@count imports",
 *   ),
 *   bundle_label = @Translation("Import type"),
 *   handlers = {
 *     "access" = "Drupal\commerce_sheets\Entity\Access\Import",
 *     "list_builder" = "Drupal\commerce_sheets\Entity\ListBuilder\Import",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "form" = {
 *       "add" = "Drupal\commerce_sheets\Entity\Form\Import",
 *       "edit" = "Drupal\commerce_sheets\Entity\Form\Import",
 *       "duplicate" = "Drupal\commerce_sheets\Entity\Form\Import",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "commerce_sheets_import",
 *   admin_permission = "administer commerce_sheets_import",
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "bundle" = "bundle",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/sheets/imports/add/{commerce_sheets_import_type}",
 *     "add-page" = "/admin/commerce/sheets/imports/add",
 *     "canonical" = "/commerce_sheets_import/{commerce_sheets_import}",
 *     "edit-form" = "/admin/commerce/sheets/imports/{commerce_sheets_import}/edit",
 *     "collection" = "/admin/commerce/sheets/imports"
 *   },
 *   bundle_entity_type = "commerce_sheets_import_type"
 * )
 */
class Import extends ContentEntityBase implements ImportInterface {

  /**
   * {@inheritdoc}
   *
   * When a new import entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller,
    array &$values
  ) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedTime() {
    return $this->get('completed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedTime($timestamp) {
    $this->set('completed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(
    EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Creator'))
      ->setDescription(t('The user ID of the import creator.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the import was created.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 4,
      ]);

    $fields['completed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed'))
      ->setDescription(t(
        'The time that the import was completed (executed or canceled).'
      ))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 5,
      ]);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the import.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'type' => 'basic_string',
        'weight' => 1,
      ]);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDescription(t(
        'The import file containing the entities that will be imported or
        updated.'
      ))
      ->setSetting('uri_scheme', 'private')
      ->setSetting('file_extensions', 'xlsx')
      ->setSetting(
        'file_directory',
        'commerce_sheets/imports/[date:custom:Y]-[date:custom:m]'
      )
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'type' => 'file_default',
        'weight' => 1,
      ]);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The import state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setSetting('workflow', 'commerce_sheets_import')
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'weight' => 2,
      ]);

    return $fields;
  }

}
