<?php

namespace Drupal\commerce_sheets\Action;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for all Commerce Sheets action.
 *
 * Provides a base mechanism for iterating over the given entities, creating any
 * header rows, converting each entity to one or more rows, and writing the
 * combined result to a spreadsheet file.
 */
abstract class ExportBase extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface.
   */
  protected $entityFieldManager;

  /**
   * The Commerce Sheets field handler plugin manager.
   *
   * @var \Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface.
   */
  protected $fieldHandlerManager;

  /**
   * The file storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderernterface
   */
  protected $renderer;

  /**
   * Constructs a new Export object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user_proxy
   *   The account proxy for the current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface $field_handler_manager
   *   The Commerce Sheets field handler plugin manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Render\Renderernterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user_proxy,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager,
    FieldHandlerManagerInterface $field_handler_manager,
    FileSystemInterface $file_system,
    LoggerInterface $logger,
    MessengerInterface $messenger,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user_proxy->getAccount();
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldHandlerManager = $field_handler_manager;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->renderer = $renderer;
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
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_sheets_field_handler'),
      $container->get('file_system'),
      $container->get('logger.channel.commerce_sheets'),
      $container->get('messenger'),
      $container->get('renderer')
    );
  }

  /**
   * Performs any validation required before processing the given entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities that will be processed.
   */
  abstract protected function validateEntities(array $entites);

  /**
   * Generates header rows for the given entities and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities that will be processed.
   *
   * @return int
   *   The last row written for the header rows.
   */
  abstract protected function writeHeader(
    Worksheet $sheet,
    array $entities,
    $row
  );

  /**
   * Converts the given entity to one or more rows and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param int $row
   *   The row at which to start writing for the entity.
   *
   * @return int
   *   The last row written for the entity.
   */
  abstract protected function writeEntity(
    Worksheet $worksheet,
    EntityInterface $entity,
    $row
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

    // Create the main sheet.
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Generate header rows for the main sheet.
    $row = $this->writeHeader($sheet, $entities, 1);

    // Generate entity rows for the main sheet.
    foreach ($entities as $entity) {
      $row = $this->writeEntity($sheet, $entity, $row);
    }

    // Write the generated output to a file.
    $file = $this->toFile($spreadsheet);
    if (!$file) {
      return;
    }

    // Display a message to the user with a link to download the file.
    $file_url = file_create_url($file->getFileUri());
    $message = $this->t(
      'Export file created, <a href=":url">click here</a> to download.',
      [':url' => $file_url]
    );
    $this->messenger->addMessage($message);
  }

  /**
   * Saves the given spreadsheet to a new file.
   *
   * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
   *   The spreadsheet that will be saved to a file.
   *
   * @return \Drupal\file\FileInterface|null
   *   The generated file, or NULL if the file could not be written to disk.
   */
  protected function toFile($spreadsheet) {
    $time = date('YmdHis', REQUEST_TIME);
    $hash = bin2hex(openssl_random_pseudo_bytes(8));
    $filename = $time . '-' . $hash . '.ods';
    $directory_uri = 'private://commerce_sheets';
    $file_uri = $directory_uri . '/' . $filename;

    // @I Make scheme and path configurable
    $directory_exists = $this->fileSystem->prepareDirectory(
      $directory_uri,
      FileSystemInterface::CREATE_DIRECTORY
    );
    if (!$directory_exists) {
      $log_message = sprintf(
        'An error occurred while preparing the directory with URI "%s" for
        writing a Commerce Sheets export file. The directory could not be
        created or is not/could not be made writable',
        $directory_uri
      );
      $this->logger->error($log_message);

      $status_message = $this->t(
        'An error ocurred while generating the export file, please try again. If
        the error persists please contact your system administrator.'
      );
      $this->messenger->addMessage($status_message);
      return;
    }

    $writer = new Ods($spreadsheet);
    $writer->save($this->fileSystem->realpath($file_uri));

    // Create a file entity.
    $file = $this->fileStorage->create([
      'uri' => $file_uri,
      'uid' => $this->currentUser->id(),
      'status' => FILE_STATUS_TEMPORARY,
    ]);
    $file->save();

    return $file;
  }

  /**
   * Generates the header row cell values for the entity type's fields.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The field definitions for the type of the entities being exported.
   * @param int $row
   *   The row at which we are writing the values.
   * @param int $column
   *   The column at which to start writing the values.
   *
   * @return int $column
   *   The last column written for the given field definitions.
   */
  protected function writeHeaderForFields(
    Worksheet $sheet,
    array $field_definitions,
    $row,
    $column
  ) {
    foreach ($field_definitions as $field_definition) {
      $sheet->setCellValueByColumnAndRow(
        $column,
        $row,
        $field_definition->getLabel()
      );
      $sheet->getStyleByColumnAndRow(
        $column,
        $row
      )->getAlignment()->setWrapText(TRUE);

      $column++;
    }

    return $column;
  }

  /**
   * Returns the base field definitions for the type of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   *
   * @return Drupal\Core\Field\FieldDefinitionInterface[]
   *   The base field definitions.
   */
  protected function getBaseFieldDefinitions(EntityInterface $entity) {
    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions(
      $entity->getEntityTypeId()
    );

    $field_definitions = $this->filterBaseFields($field_definitions);
    $field_definitions = $this->sortFields($field_definitions);

    return $field_definitions;
  }

  /**
   * Returns the bundle field definitions for the type of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   *
   * @return Drupal\Core\Field\FieldDefinitionInterface[]
   *   The bundle field definitions.
   */
  protected function getBundleFieldDefinitions(EntityInterface $entity) {
    $all_definitions = $this->entityFieldManager->getFieldDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    $base_definitions = $this->entityFieldManager->getBaseFieldDefinitions(
      $entity->getEntityTypeId()
    );

    $field_definitions = array_diff_key($all_definitions, $base_definitions);
    $field_definitions = $this->filterBundleFields($field_definitions);
    $field_definitions = $this->sortFields($field_definitions);

    return $field_definitions;
  }

  /**
   * Returns a field handler plugin for the given field definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which to create the handler plugin for.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface|null
   *   An instantiated field handler plugin if a handler type was determined,
   *   NULL otherwise.
   */
  protected function getFieldPlugin($field_definition) {
    $type = NULL;
    $locked = FALSE;

    // Special cases.
    // @I Detect the bundle and status fields from the entity keys
    switch ($field_definition->getName()) {
      case 'type':
        $type = 'bundle';
        break;

      case 'status':
        $type = 'boolean';
        break;
    }

    switch ($field_definition->getType()) {
      case 'integer':
        $type = 'integer';
        break;

      case 'string':
      case 'string_long':
      case 'text_long':
      case 'text_with_summary':
        $type = 'text';
        break;
    }

    if (!$type) {
      return;
    }

    return $this->createFieldPlugin($type, $locked);
  }

  /**
   * Returns an instance of a handler plugin of the given type.
   *
   * @param bool $locked
   *   The value for the Locked configuration setting of the plugin that
   *   determines whether the resulting cell will be locked (read-only) or not.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface
   *   An instantiated field handler plugin of the given type.
   */
  protected function createFieldPlugin($type, $locked = FALSE) {
    return $this->fieldHandlerManager->createInstance(
      $type,
      $locked ? ['locked' => TRUE] : []
    );
  }

}
