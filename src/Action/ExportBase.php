<?php

namespace Drupal\commerce_sheets\Action;

use Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;

use PhpOffice\PhpSpreadsheet\Helper\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment as StyleAlignment;
use PhpOffice\PhpSpreadsheet\Style\Fill as StyleFill;
use PhpOffice\PhpSpreadsheet\Style\Protection as StyleProtection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for all Commerce Sheets action.
 *
 * Provides a base mechanism for iterating over the given entities, creating any
 * header rows, converting each entity to one or more rows, and writing the
 * combined result to a spreadsheet file.
 *
 * There is support for exporting one or more associated entities held in an
 * entity reference field at the main entity.
 */
abstract class ExportBase extends ViewsBulkOperationsActionBase implements
  ContainerFactoryPluginInterface {

  /**
   * The main background color used in header rows.
   */
  const HEADER_COLOR = 'CCCCCC';

  /**
   * The secondary, lighter, background color used in header rows.
   */
  const HEADER_SUB_COLOR = 'EEEEEE';

  /**
   * The Action plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $actionPluginManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Commerce Sheets field handler plugin manager.
   *
   * @var \Drupal\commerce_sheets\FieldHandler\FieldHandlerManagerInterface
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
   * The Action plugin for the secondary entity(ies) to be exported.
   *
   * @var \Drupal\commerce_sheets\Action\ExportInterface
   */
  protected $secondaryEntityPlugin;

  /**
   * Constructs a new Export object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $action_plugin_manager
   *   The Action plugin manager.
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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PluginManagerInterface $action_plugin_manager,
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

    $this->actionPluginManager = $action_plugin_manager;
    $this->currentUser = $current_user_proxy->getAccount();
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('plugin.manager.action'),
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
  abstract protected function validateEntities(array $entities);

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

    // Enable protection for the sheet and set it to FALSE as the default for
    // all cells. This is required so that we can then set it to TRUE for cells
    // that we do need to lock (protect).
    $sheet->getProtection()->setSheet(TRUE);
    $spreadsheet->getDefaultStyle()->getProtection()->setLocked(FALSE);

    // Generate header rows for the main sheet.
    list($row) = $this->writeHeader($sheet, $entities, 1, 1);
    $last_header_row = $row - 1;

    // Lock all header rows.
    // @I Rows seem to be get locked up to the Z column
    $sheet
      ->getStyleByColumnAndRow(
        1,
        1,
        $sheet->getHighestColumn($last_header_row),
        $last_header_row
      )
      ->getProtection()
      ->setLocked(StyleProtection::PROTECTION_PROTECTED);

    // Freeze first 2 header rows.
    $sheet->freezePane('A3');

    // Generate entity rows for the main sheet.
    foreach ($entities as $entity) {
      list($row) = $this->writeEntity($sheet, $entity, $row, 1);
    }

    // Size adjustments for all columns/rows.
    // @I Refactor global column/row sizing to a separate method
    // Set width to automatic for all columns.
    foreach ($sheet->getColumnIterator() as $column) {
      $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(TRUE);
    }

    // Set heights for all rows.
    foreach ($sheet->getRowIterator() as $row) {
      $row_index = $row->getRowIndex();
      if ($row_index < $last_header_row) {
        continue;
      }
      if ($row_index === $last_header_row) {
        $sheet->getRowDimension($row_index)->setRowHeight(80);
        continue;
      }
      $sheet->getRowDimension($row_index)->setRowHeight(50);
    }

    // Set maximum width to 80 for all rows.
    $max_width = 80;
    foreach ($spreadsheet->getAllSheets() as $s) {
      $s->calculateColumnWidths();
      foreach ($s->getColumnDimensions() as $column_dimension) {
        if (!$column_dimension->getAutoSize()) {
          continue;
        }

        $column_width = $column_dimension->getWidth();
        if ($column_width > $max_width) {
          $column_dimension->setAutoSize(FALSE);
          $column_dimension->setWidth($max_width);
        }
      }
    }

    // Styles. We want all cell values to be top-aligned vertically.
    $sheet->getStyle($sheet->calculateWorksheetDimension())
      ->getAlignment()
      ->setVertical(StyleAlignment::VERTICAL_TOP)
      ->setWrapText(TRUE);

    // @I Give the opportunity to update the main sheet or add new sheets.

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
   * Generates header rows for the given entities and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities that will be processed.
   * @param int $row
   *   The row at which to start writing the header.
   * @param int $column
   *   The column at which to start writing the header.
   *
   * @return int[]
   *   An array containing the row and the column after the last ones written
   *   for the header rows. They are the row/column where the next writer should
   *   pick up.
   */
  public function writeHeader(
    Worksheet $sheet,
    array $entities,
    $row,
    $column
  ) {
    $entity = reset($entities);
    list($end_row, $secondary_entity_column) = $this->doWriteHeader(
      $sheet,
      $entity->getEntityTypeId(),
      $entity->getEntityType()->getLabel(),
      $entity->bundle(),
      $row,
      $column
    );

    if (!$this->hasSecondaryEntity()) {
      return [$end_row, $secondary_entity_column];
    }

    $secondary_entities = $this->getSecondaryEntityFieldValue($entity);
    if (!$secondary_entities) {
      return [$end_row, $secondary_entity_column];
    }

    // @I Check permissions for secondary entity

    list($end_row, $end_column) = $this->getSecondaryEntityPlugin()
      ->writeHeader(
        $sheet,
        $secondary_entities,
        $row,
        $secondary_entity_column
      );

    return [$end_row, $end_column];
  }

  /**
   * Converts the given entity to one or more rows and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param int $row
   *   The row at which to start writing for the entity.
   * @param int $column
   *   The column at which to start writing for the entity.
   *
   * @return int[]
   *   An array containing the row and the column after the last ones written
   *   for the entity. They are the row/column where the next writer should pick
   *   up.
   */
  public function writeEntity(
    Worksheet $sheet,
    EntityInterface $entity,
    $row,
    $column
  ) {
    list($row, $secondary_entity_column) = $this->writeEntityFields(
      $sheet,
      $entity,
      $row,
      $column
    );

    if (!$this->hasSecondaryEntity()) {
      return [$row + 1, $secondary_entity_column];
    }

    $secondary_entities = $this->getSecondaryEntityFieldValue($entity);
    if (!$secondary_entities) {
      return [$row + 1, $secondary_entity_column];
    }

    // @I Check permissions for secondary entity

    $secondary_entity_plugin = $this->getSecondaryEntityPlugin();
    foreach ($secondary_entities as $secondary_entity) {
      list($row, $column) = $secondary_entity_plugin->writeEntity(
        $sheet,
        $secondary_entity,
        $row,
        $secondary_entity_column
      );
    }

    return [$row, $column];
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
  protected function toFile(Spreadsheet $spreadsheet) {
    $time = date('YmdHis', REQUEST_TIME);
    $hash = bin2hex(openssl_random_pseudo_bytes(8));
    $filename = $time . '-' . $hash . '.xlsx';
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

    $writer = new Xlsx($spreadsheet);
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
   * Performs the actual writing of the header rows for the given entity data.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param string $entity_type_id
   *   The ID of the entty type for which the header rows are generated.
   * @param string $entity_type_label
   *   The label of the entty type for which the header rows are generated.
   * @param string $entity_bundle
   *   The bundle of the entity(ies) for which the header rows are generated.
   * @param int $row
   *   The row at which to start writing for the entity.
   * @param int $column
   *   The column at which to start writing for the entity.
   *
   * @return int[]
   *   An array containing the row and the column after the last ones written
   *   for the header. They are the row/column where the next writer should pick
   *   up.
   */
  protected function doWriteHeader(
    Worksheet $sheet,
    $entity_type_id,
    $entity_type_label,
    $entity_bundle,
    $row,
    $column
  ) {
    $entity_type_label = strtoupper($entity_type_label);

    $first_row = $row;
    $first_column = $column;

    $sheet->setCellValueByColumnAndRow(
      $first_column,
      $first_row,
      "BASE $entity_type_label FIELDS"
    );

    $row++;

    $base_field_definitions = $this->getBaseFieldDefinitions($entity_type_id);
    $bundle_field_definitions = $this->getBundleFieldDefinitions(
      $entity_type_id,
      $entity_bundle
    );

    // Header values for field labels.
    $column = $this->writeHeaderForFieldLabels(
      $sheet,
      $base_field_definitions,
      $row,
      $column
    );
    $sheet->setCellValueByColumnAndRow(
      $column,
      $first_row,
      "BUNDLE $entity_type_label FIELDS"
    );
    $this->writeHeaderForFieldLabels(
      $sheet,
      $bundle_field_definitions,
      $row,
      $column
    );

    // Header values for additional field information.
    $row++;
    $column = $first_column;
    $column = $this->writeHeaderForFieldInfo(
      $sheet,
      $base_field_definitions,
      $row,
      $column
    );
    $column = $this->writeHeaderForFieldInfo(
      $sheet,
      $bundle_field_definitions,
      $row,
      $column
    );

    // Styles for the first header row.
    $styleArray = [
      'font' => [
        'bold' => TRUE,
      ],
      'fill' => [
        'fillType' => StyleFill::FILL_SOLID,
        'startColor' => [
          'argb' => self::HEADER_COLOR,
        ],
      ],
    ];

    $first_row_highest_column = $sheet->getHighestColumn();
    $style = $sheet->getStyle('A1:' . $first_row_highest_column . '1');
    $style->applyFromArray($styleArray);

    return [$row + 1, $column];
  }

  /**
   * Generates the header row cell values for the entity type's field labels.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The field definitions for the type of the entities being exported.
   * @param int $row
   *   The row at which we are writing the values.
   * @param int $column
   *   The column at which to start writing the values.
   *
   * @return int
   *   The last column written for the given field definitions.
   */
  protected function writeHeaderForFieldLabels(
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

      // Styles.
      $styleArray = [
        'font' => [
          'bold' => TRUE,
        ],
        'fill' => [
          'fillType' => StyleFill::FILL_SOLID,
          'startColor' => [
            'argb' => self::HEADER_COLOR,
          ],
        ],
      ];

      $style = $sheet->getStyleByColumnAndRow(
        $column,
        $row
      );
      $style->applyFromArray($styleArray);

      $column++;
    }

    return $column;
  }

  /**
   * Generates the header row cell values for the entity type's field info.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The field definitions for the type of the entities being exported.
   * @param int $row
   *   The row at which we are writing the values.
   * @param int $column
   *   The column at which to start writing the values.
   *
   * @return int
   *   The last column written for the given field definitions.
   */
  protected function writeHeaderForFieldInfo(
    Worksheet $sheet,
    array $field_definitions,
    $row,
    $column
  ) {
    $html = new Html();

    foreach ($field_definitions as $field_definition) {
      // Generate the text and set it as the cell value.
      $text = '<em>ID</em>: ' . $field_definition->getName() .
        '<br><em>Type</em>: ' . $field_definition->getType();
      $description = $field_definition->getDescription();
      if ($description) {
        $text .= '<br><em>Description</em>: ' . $description;
      }
      $sheet->setCellValueByColumnAndRow(
        $column,
        $row,
        $html->toRichTextObject($text)
      );

      // Styles.
      $styleArray = [
        'fill' => [
          'fillType' => StyleFill::FILL_SOLID,
          'startColor' => [
            'argb' => self::HEADER_SUB_COLOR,
          ],
        ],
      ];

      $style = $sheet->getStyleByColumnAndRow(
        $column,
        $row
      );
      $style->applyFromArray($styleArray);

      $column++;
    }

    return $column;
  }

  /**
   * Converts all fields for the entity and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param int $row
   *   The row at which to start writing for the entity.
   * @param int $column
   *   The column at which to start writing for the entity.
   *
   * @return int[]
   *   An array containing the last row and the column after the last one
   *   written for the fields.
   */
  protected function writeEntityFields(
    Worksheet $sheet,
    EntityInterface $entity,
    $row,
    $column
  ) {
    list($row, $column) = $this->writeEntityBaseFields(
      $sheet,
      $entity,
      $row,
      $column
    );
    list($row, $column) = $this->writeEntityBundleFields(
      $sheet,
      $entity,
      $row,
      $column
    );

    return [$row, $column];
  }

  /**
   * Converts the base fields for the entity and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param int $row
   *   The row at which to start writing for the base fields.
   * @param int $column
   *   The column at which to start writing for the base fields.
   *
   * @return int[]
   *   An array containing the last row and the column after the last one
   *   written for the base fields.
   */
  protected function writeEntityBaseFields(
    Worksheet $sheet,
    EntityInterface $entity,
    $row,
    $column
  ) {
    $field_definitions = $this->getBaseFieldDefinitions(
      $entity->getEntityTypeId()
    );

    foreach ($field_definitions as $field_definition) {
      list($row, $column) = $this->writeEntityField(
        $sheet,
        $entity->get($field_definition->getName()),
        $row,
        $column
      );
    }

    return [$row, $column];
  }

  /**
   * Converts the bundle fields for the entity and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param int $row
   *   The row at which to start writing for the bundle fields.
   * @param int $column
   *   The column at which to start writing for the bundle fields.
   *
   * @return int[]
   *   An array containing the last row and the column after the last one
   *   written for the base fields.
   */
  protected function writeEntityBundleFields(
    Worksheet $sheet,
    EntityInterface $entity,
    $row,
    $column
  ) {
    $field_definitions = $this->getBundleFieldDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );

    foreach ($field_definitions as $field_definition) {
      list($row, $column) = $this->writeEntityField(
        $sheet,
        $entity->get($field_definition->getName()),
        $row,
        $column
      );
    }
    return [$row, $column];
  }

  /**
   * Converts an individual field and writes it to the given sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the vlaue will be written.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field which to convert to a cell value.
   * @param int $row
   *   The row at which to write the value.
   * @param int $column
   *   The column at which to write the value.
   *
   * @return int[]
   *   An array containing the last row and the column after the last one
   *   written for the field.
   */
  protected function writeEntityField(
    Worksheet $sheet,
    FieldItemListInterface $field,
    $row,
    $column
  ) {
    $value = NULL;
    $data_type = NULL;

    // Set the value and the data type of the cell.
    $plugin = $this->getFieldPlugin($field->getFieldDefinition());
    if ($plugin) {
      $value = $plugin->toCellValue($field);
      $data_type = $plugin->toCellDataType();
    }
    // Let's have a fallback in case we cannot determine the plugin; that can
    // happen for custom (or not yet supported) field types.
    else {
      $value = $field->value;
    }

    $cell = $sheet->getCellByColumnAndRow($column, $row);
    if ($data_type) {
      $cell->setValueExplicit($value, $data_type);
    }
    else {
      $cell->setValue($value);
    }

    // Let the field plugin apply styles to the cell.
    if ($plugin) {
      $plugin->toCellStyle(
        $sheet->getStyleByColumnAndRow($column, $row)
      );
    }

    return [$row , $column + 1];
  }

  /**
   * Filters base field definitions to exclude those that will not be exported.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The base field definitions being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered base field definitions.
   */
  protected function filterBaseFields(array $field_definitions) {
    $blacklisted_fields = [
      'uuid',
      'langcode',
      'uid',
      'created',
      'changed',
      'default_langcode',
      'metatag',
    ];

    return $this->filterFields($field_definitions, $blacklisted_fields);
  }

  /**
   * Filters bundle field definitions excluding those that will not be exported.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The bundle field definitions being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered bundle field definitions.
   */
  protected function filterBundleFields(array $field_definitions) {
    return $field_definitions;
  }

  /**
   * Filters field definitions to exclude those that will not be exported.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The field definitions being processed.
   * @param string[] $blacklisted_fields
   *   An array containing names of fields that should not be exported.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered field definitions.
   */
  protected function filterFields(
    array $field_definitions,
    array $blacklisted_fields = []
  ) {
    if (!$blacklisted_fields) {
      return $field_definitions;
    }

    return array_filter(
      $field_definitions,
      function ($field_name) use ($blacklisted_fields) {
        return !in_array($field_name, $blacklisted_fields);
      },
      ARRAY_FILTER_USE_KEY
    );
  }

  /**
   * Sorts field definitions in the order that they should be exported.
   *
   * The default order is alphabetical based on the fields' machine names, with
   * read-only fields (protected/locked cells) going first.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The bundle field definitions being processed.
   * @param string[] $read_only_field_names
   *   An array containing names of fields that should be exported as protected
   *   (locked/read-only).
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The filtered bundle field definitions.
   */
  protected function sortFields(
    array $field_definitions,
    array $read_only_field_names = []
  ) {
    $read_only_field_definitions = array_filter(
      $field_definitions,
      function ($field_name) use ($read_only_field_names) {
        return in_array($field_name, $read_only_field_names);
      },
      ARRAY_FILTER_USE_KEY
    );
    ksort($read_only_field_definitions);

    $editable_field_definitions = array_filter(
      $field_definitions,
      function ($field_name) use ($read_only_field_names) {
        return !in_array($field_name, $read_only_field_names);
      },
      ARRAY_FILTER_USE_KEY
    );
    ksort($editable_field_definitions);

    return $read_only_field_definitions + $editable_field_definitions;
  }

  /**
   * Returns the base field definitions for the entity type given by its ID.
   *
   * @param string $entity_type_id
   *   The type ID of the entity being processed.
   *
   * @return Drupal\Core\Field\FieldDefinitionInterface[]
   *   The base field definitions.
   */
  protected function getBaseFieldDefinitions($entity_type_id) {
    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions(
      $entity_type_id
    );

    $field_definitions = $this->filterBaseFields($field_definitions);
    $field_definitions = $this->sortFields($field_definitions);

    return $field_definitions;
  }

  /**
   * Returns the bundle field definitions for the given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The type ID of the entity being processed.
   * @param string $bundle
   *   The bundle of the entity being processed.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The bundle field definitions.
   */
  protected function getBundleFieldDefinitions($entity_type_id, $bundle) {
    $all_definitions = $this->entityFieldManager->getFieldDefinitions(
      $entity_type_id,
      $bundle
    );

    $base_definitions = $this->entityFieldManager->getBaseFieldDefinitions(
      $entity_type_id
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
  protected function getFieldPlugin(
    FieldDefinitionInterface $field_definition
  ) {
    list($type, $locked) = $this->getFieldPluginByName($field_definition);
    if (!$type) {
      list($type, $locked) = $this->getFieldPluginByType($field_definition);
    }

    if (!$type) {
      return;
    }

    return $this->createFieldPlugin($type, $locked);
  }

  /**
   * Determines the handler plugin ID based on the given field definition name.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which to create the handler plugin for.
   *
   * @return array
   *   An array containing the plugin ID as the first element (or NULL if none
   *   was detected) and the plugin's Locked setting that will be used for its
   *   instantiation as the second element (or NULL if it should be left to the
   *   default setting value).
   */
  protected function getFieldPluginByName(
    FieldDefinitionInterface $field_definition
  ) {
    $type = NULL;
    $locked = NULL;

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

    return [$type, $locked];
  }

  /**
   * Determines the handler plugin based on the given field definition type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition for which to create the handler plugin for.
   *
   * @return array
   *   An array containing the plugin ID as the first element (or NULL if none
   *   was detected) and the plugin's Locked setting that will be used for its
   *   instantiation as the second element (or NULL if it should be left to the
   *   default setting value).
   */
  protected function getFieldPluginByType(
    FieldDefinitionInterface $field_definition
  ) {
    $type = NULL;
    $locked = NULL;

    switch ($field_definition->getType()) {
      case 'commerce_price':
        $type = 'price';
        break;

      case 'integer':
        $type = 'integer';
        break;

      case 'path':
        $type = 'path';
        break;

      case 'physical_measurement':
        $type = 'measurement';
        break;

      case 'string':
      case 'string_long':
      case 'text_long':
      case 'text_with_summary':
        $type = 'text';
        break;

      case 'entity_reference':
        $type = 'entity_reference';
        break;
    }

    return [$type, $locked];
  }

  /**
   * Returns an instance of a handler plugin of the given type.
   *
   * @param string $type
   *   The type of the handler plugin to create.
   * @param bool|null $locked
   *   The value for the Locked configuration setting of the plugin that
   *   determines whether the resulting cell will be locked (read-only) or
   *   not. If NULL is given, no value will be passed to the factory and the
   *   default configuration setting of the plugin will be used.
   *
   * @return \Drupal\commerce_sheets\FieldHandler\FieldHandlerInterface
   *   An instantiated field handler plugin of the given type.
   */
  protected function createFieldPlugin($type, $locked = NULL) {
    // Let's keep line lengths within soft limits.
    $lock = ['locked' => TRUE];
    $do_not_lock = ['locked' => FALSE];

    return $this->fieldHandlerManager->createInstance(
      $type,
      $locked ? $lock : ($locked === FALSE ? $do_not_lock : [])
    );
  }

  /**
   * Returns whether there is a secondary entity(ies) that should be exported.
   *
   * @return bool
   *   TRUE if there is a secondary entity(ies) and it should be exported; FALSE
   *   otherwise.
   */
  protected function hasSecondaryEntity() {
    return FALSE;
  }

  /**
   * Returns the Action plugin ID for the secondary entity, if any.
   *
   * @return string|null
   *   The Action plugin ID for the secondary entity type, NULL if there is
   *   none.
   */
  protected function getSecondaryEntityPluginId() {
  }

  /**
   * Returns the ID of the secondary entity type, if any.
   *
   * @return string|null
   *   The ID of the secondary entity type, NULL if there is none.
   */
  protected function getSecondaryEntityTypeId() {
  }

  /**
   * Returns the label for the type of the secondary entity, if any.
   *
   * @return string|null
   *   The label of the secondary entity type, NULL if there is none.
   */
  protected function getSecondaryEntityTypeLabel() {
  }

  /**
   * Returns the bundle ID for the secondary entity.
   *
   * We currently only support (and assume) that the secondary entity is always
   * of a specific type. For example, all variations of a product are of the
   * same variation type.
   *
   * Things might get more complicated when we export orders, for example, where
   * order items of different types can be the secondary entities for the same
   * order. In that case bundle fields might be different for each order item
   * and the columns will vary.
   *
   * @return string|null
   *   The bundle of the secondary entity(ies), NULL if there is none.
   */
  protected function getSecondaryEntityBundleId(EntityInterface $entity) {
  }

  /**
   * The name of the field that holds the secondary entity(ies).
   *
   * @return string|null
   *   The name of the field, NULL if there is none.
   */
  protected function getSecondaryEntityFieldName() {
  }

  /**
   * Returns the Action plugin for the secondary entity.
   *
   * @return \Drupal\commerce_sheets\Action\ExportInterface
   *   The Action plugin.
   */
  protected function getSecondaryEntityPlugin() {
    if (!$this->secondaryEntityPlugin) {
      $this->secondaryEntityPlugin = $this->actionPluginManager
        ->createInstance($this->getSecondaryEntityPluginId());
    }

    return $this->secondaryEntityPlugin;
  }

  /**
   * Returns the secondary entity(ies) as fetch from the corresponding field.
   *
   * @return Drupal\Core\Entity\EntityInterface[]
   *   An array containing the secondary entity(ies).
   */
  protected function getSecondaryEntityFieldValue(EntityInterface $entity) {
    $field_name = $this->getSecondaryEntityFieldName();
    if (!$entity->hasField($field_name)) {
      return [];
    }

    return $entity->get($field_name)->referencedEntities();
  }

}
