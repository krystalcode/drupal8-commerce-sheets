<?php

namespace Drupal\commerce_sheets\Sheet;

use Drupal\commerce_sheets\Event\WriterPropertyValueEvent;
use Drupal\commerce_sheets\Event\WriterEvents;
use Drupal\commerce_sheets\EntityFormat\EntityFormatInterface;
use Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\Helper\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment as StyleAlignment;
use PhpOffice\PhpSpreadsheet\Style\Fill as StyleFill;
use PhpOffice\PhpSpreadsheet\Style\Protection as StyleProtection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the default writer service.
 *
 * @I Review the order of the methods
 * @I Review the architecture of the whole Writer service
 */
class Writer implements WriterInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The file entity storage.
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
   * The Commerce Sheets entity format manager.
   *
   * @var \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface
   */
  protected $formatManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new Writer object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user_proxy
   *   The current user.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface $format_manager
   *   The entity format plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountProxyInterface $current_user_proxy,
    EntityFormatManagerInterface $format_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher,
    FileSystemInterface $file_system,
    LoggerInterface $logger,
    MessengerInterface $messenger,
    TranslationInterface $string_translation
  ) {
    $this->currentUser = $current_user_proxy->getAccount();
    $this->formatManager = $format_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->fileSystem = $file_system;
    $this->logger = $logger;
    $this->messenger = $messenger;

    // Property defined by StringTranslationTrait.
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   *
   * @I Some refactoring is needed here
   */
  public function write(array $entities, EntityFormatInterface $format) {
    // Create the main sheet.
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Enable protection for the sheet and set it to FALSE as the default for
    // all cells. This is required so that we can then set it to TRUE for cells
    // that we do need to lock (protect).
    $sheet->getProtection()->setSheet(TRUE);
    $spreadsheet->getDefaultStyle()->getProtection()->setLocked(FALSE);

    // Write the format in a serialized textual representation to the
    // spreadsheet as a custom property. It will be used when importing the
    // spreadsheet so that we know the exact format for reading it.
    $spreadsheet->getProperties()->setCustomProperty(
      EntityFormatManagerInterface::SPREADSHEET_CUSTOM_PROPERTY_FORMAT,
      $this->formatManager->serializePluginDefinition($format),
      Properties::PROPERTY_TYPE_STRING
    );

    $initial_row = 1;
    $initial_column = 1;

    // Generate header rows.
    list($end_row) = $this->writeHeader(
      $sheet,
      $entities,
      $format,
      $initial_row,
      $initial_column
    );
    $last_header_row = $end_row;

    // Lock all header rows.
    // @I Rows seem to be get locked up to column Z
    $sheet
      ->getStyleByColumnAndRow(
        1,
        1,
        Coordinate::columnIndexFromString(
          $sheet->getHighestColumn($last_header_row)
        ),
        $last_header_row
      )
      ->getProtection()
      ->setLocked(StyleProtection::PROTECTION_PROTECTED);

    // Freeze first 2 header rows.
    $sheet->freezePane('A3');

    // Generate entity rows.
    $row = $end_row + 1;

    foreach ($entities as $entity) {
      list($end_row) = $this->writeEntity(
        $sheet,
        $entity,
        $format,
        $row,
        $initial_column
      );

      $row = $end_row + 1;
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

    // @I Give the opportunity to update the main sheet or add new sheets
    // Write the generated output to a file.
    $file = $this->toFile($spreadsheet);
    if (!$file) {
      return;
    }

    // Display a message to the user with a link to download the file.
    // @I Permanently store the file in an Export entity
    $message = $this->t(
      'Export file created, <a href=":url">click here</a> to download.',
      [':url' => file_create_url($file->getFileUri())]
    );
    $this->messenger->addMessage($message);
  }

  /**
   * Generates header rows for the given entities and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the header rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities that will be processed.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $row
   *   The row at which to start writing the header.
   * @param int $column
   *   The column at which to start writing the header.
   *
   * @return int[]
   *   An array containing the last row and the column written for the header
   *   rows.
   */
  protected function writeHeader(
    Worksheet $sheet,
    array $entities,
    EntityFormatInterface $format,
    $row,
    $column
  ) {
    $entity = reset($entities);
    list($end_row, $end_column) = $this->doWriteHeader(
      $sheet,
      $entity->getEntityTypeId(),
      $entity->getEntityType()->getLabel(),
      $entity->bundle(),
      $format,
      $row,
      $column
    );

    if (!$format->hasAssociatedEntities()) {
      return [$end_row, $end_column];
    }

    $associated_entities = $this->getAssociatedEntities($entity, $format);
    if (!$associated_entities) {
      return [$end_row, $end_column];
    }

    // @I Check permissions for associated entities
    $associated_format = NULL;
    foreach ($format->getSections() as $section) {
      if ($section['type'] === 'entities') {
        $associated_format = $section['format'];
      }
    }

    list($end_row, $end_column) = $this->writeHeader(
      $sheet,
      $associated_entities,
      $associated_format,
      $row,
      $end_column + 1
    );

    return [$end_row, $end_column];
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
    // In earlier versions of Drupal the `prepareDirectory` method does not
    // exist in the file system service.
    $directory_exists = FALSE;
    if (method_exists($this->fileSystem, 'prepareDirectory')) {
      $directory_exists = $this->fileSystem->prepareDirectory(
        $directory_uri,
        FileSystemInterface::CREATE_DIRECTORY
      );
    }
    else {
      $directory_exists = file_prepare_directory(
        $directory_uri,
        FILE_CREATE_DIRECTORY
      );
    }
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
   * Converts the given entity to one or more rows and writes them to the sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $row
   *   The row at which to start writing for the entity.
   * @param int $column
   *   The column at which to start writing for the entity.
   *
   * @return int[]
   *   An array containing the last row and column written for the entity.
   */
  public function writeEntity(
    Worksheet $sheet,
    EntityInterface $entity,
    EntityFormatInterface $format,
    $row,
    $column
  ) {
    list($end_row, $end_column) = $this->writeSections(
      $sheet,
      $entity,
      $format,
      $row,
      $column
    );

    if (!$format->hasAssociatedEntities()) {
      return [$end_row, $end_column];
    }

    $associated_entities = $this->getAssociatedEntities($entity, $format);
    if (!$associated_entities) {
      return [$end_row, $end_column];
    }

    // @I Check permissions for exporting associated entities
    $associated_format = NULL;
    foreach ($format->getSections() as $section) {
      if ($section['type'] === 'entities') {
        $associated_format = $section['format'];
      }
    }

    $start_row = $end_row;
    $start_column = $end_column + 1;
    foreach ($associated_entities as $associated_entity) {
      list($end_row, $end_column) = $this->writeEntity(
        $sheet,
        $associated_entity,
        $associated_format,
        $start_row,
        $start_column
      );

      $start_row++;
    }

    return [$end_row, $end_column];
  }

  /**
   * Writes all sections for the entity to the given sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $row
   *   The row at which to start writing for the entity.
   * @param int $column
   *   The column at which to start writing for the entity.
   *
   * @return int[]
   *   An array containing the last row and column written for the entity.
   */
  protected function writeSections(
    Worksheet $sheet,
    EntityInterface $entity,
    EntityFormatInterface $format,
    $row,
    $column
  ) {
    foreach ($format->getSections() as $section) {
      switch ($section['type']) {
        case 'properties':
          list ($end_row, $end_column) = $this->writePropertiesSection(
            $sheet,
            $entity,
            $format,
            $section,
            $row,
            $column
          );
          break;

        case 'entities':
          // Entities sections are written by recursively calling the
          // `writeHeader` and `writeEntity` methods for the associated
          // entities.
          break;

        default:
          throw new \InvalidArgumentException(
            sprintf(
              'Unsupported entity format section "%s".',
              $section['type']
            )
          );
      }

      $column = $end_column + 1;
    }

    return [$end_row, $end_column];
  }

  /**
   * Writes the given `properties` section for the entity to the given sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being processed.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param array $section
   *   The section to write to the sheet.
   * @param int $row
   *   The row at which to start writing for the section.
   * @param int $column
   *   The column at which to start writing for the section.
   *
   * @return int[]
   *   An array containing the last row and column written for the section.
   */
  protected function writePropertiesSection(
    Worksheet $sheet,
    EntityInterface $entity,
    EntityFormatInterface $format,
    array $section,
    $row,
    $column
  ) {
    $end_row = $row;
    $end_column = $column;

    foreach ($section['properties'] as $property) {
      // First, allow any 3rd parties to determine the value of the property. If
      // nobody does so, we will proceed with getting it from the entity
      // property/field.
      $event = new WriterPropertyValueEvent($entity, $property);
      $this->eventDispatcher->dispatch(
        WriterEvents::PROPERTY_PRE_WRITE,
        $event
      );
      $property_value = $event->getPropertyValue();
      if ($property_value) {
        list($end_row, $end_column) = $this->writeProperty(
          $sheet,
          $property,
          $property_value,
          $format,
          $row,
          $column
        );

        $column = $end_column + 1;

        continue;
      }

      // The property may be a configuration or a content (fieldable) entity.
      // @I Review the case of non-fieldable content entities
      $property_exists = FALSE;
      if ($entity instanceof FieldableEntityInterface) {
        $property_exists = $entity->hasField($property);
      }
      else {
        $property_exists = $entity->get($property) ? TRUE : FALSE;
      }

      // If the property is not found, leave the column empty.
      // @I Consider throwing an exception if a property is not found
      if (!$property_exists) {
        $column += 1;
        $end_column += 1;
        continue;
      }

      // Write the property to the sheet.
      list($end_row, $end_column) = $this->writeProperty(
        $sheet,
        $property,
        $entity->get($property),
        $format,
        $row,
        $column
      );

      $column = $end_column + 1;
    }

    return [$end_row, $end_column];
  }

  /**
   * Writes the given entity property to the given sheet.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to which the rows will be written.
   * @param string $property
   *   The name of the property to be written.
   * @param mixed $property_value
   *   The value of the property to be written.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $row
   *   The row at which to start writing for the property.
   * @param int $column
   *   The column at which to start writing for the property.
   *
   * @return int[]
   *   An array containing the last row and column written for the property.
   */
  protected function writeProperty(
    Worksheet $sheet,
    $property,
    $property_value,
    EntityFormatInterface $format,
    $row,
    $column
  ) {
    $value = NULL;
    $data_type = NULL;

    // Set the value and the data type of the cell.
    $plugin = $format->getPropertyPlugin($property);
    if ($plugin) {
      $value = $plugin->toCellValue($property_value);
      $data_type = $plugin->toCellDataType();
    }
    // Let's have a fallback in case we cannot determine the plugin; that can
    // happen for custom (or not yet supported) field types.
    // @I Consider throwing an exception for unsupported field types
    elseif ($property_value instanceof FieldItemListInterface) {
      $value = $property_value->value;
    }
    else {
      $value = $property_value;
    }

    $cell = $sheet->getCellByColumnAndRow($column, $row);
    if ($data_type) {
      $cell->setValueExplicit($value, $data_type);
    }
    else {
      $cell->setValue($value);
    }

    // Let the property plugin apply styles to the cell.
    if ($plugin) {
      $plugin->toCellStyle(
        $sheet->getStyleByColumnAndRow($column, $row)
      );
    }

    return [$row , $column];
  }

  /**
   * Returns the secondary entity(ies) as fetch from the corresponding field.
   *
   * @return Drupal\Core\Entity\EntityInterface[]
   *   An array containing the secondary entity(ies).
   */
  protected function getAssociatedEntities(EntityInterface $entity, EntityFormatInterface $format) {
    $field_name = $format->getConfiguration()['associated_entities']['field'];
    if (!$entity->hasField($field_name)) {
      return [];
    }

    return $entity->get($field_name)->referencedEntities();
  }

  /**
   * The section below is a copy from the previous Action ExportBase plugin. It
   * should be reviewed and properly re-architected.
   */

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
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $row
   *   The row at which to start writing for the entity.
   * @param int $column
   *   The column at which to start writing for the entity.
   *
   * @return int[]
   *   An array containing the last row and the column written for the header.
   *
   * @I Header writing currently works only for content entities
   */
  protected function doWriteHeader(
    Worksheet $sheet,
    $entity_type_id,
    $entity_type_label,
    $entity_bundle,
    EntityFormatInterface $format,
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

    $base_field_definitions = $format->getBaseFieldDefinitions();
    $bundle_field_definitions = $format->getBundleFieldDefinitions(
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
      $column + 1,
      $first_row,
      "BUNDLE $entity_type_label FIELDS"
    );
    $this->writeHeaderForFieldLabels(
      $sheet,
      $bundle_field_definitions,
      $row,
      $column + 1
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
      $column + 1
    );

    // Styles for the first header row.
    $styleArray = [
      'font' => [
        'bold' => TRUE,
      ],
      'fill' => [
        'fillType' => StyleFill::FILL_SOLID,
        'startColor' => [
          'argb' => EntityFormatInterface::HEADER_COLOR,
        ],
      ],
    ];

    $first_row_highest_column = $sheet->getHighestColumn();
    $style = $sheet->getStyle('A1:' . $first_row_highest_column . '1');
    $style->applyFromArray($styleArray);

    return [$row, $column];
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
            'argb' => EntityFormatInterface::HEADER_COLOR,
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

    return $column - 1;
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
            'argb' => EntityFormatInterface::HEADER_SUB_COLOR,
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

    return $column - 1;
  }

}
