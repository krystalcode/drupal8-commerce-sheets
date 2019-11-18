<?php

namespace Drupal\commerce_sheets\Sheet;

use Drupal\commerce_sheets\EntityFormat\EntityFormatInterface;
use Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface;

use Drupal\commerce_sheets\Entity\ImportInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Defines the default reader service.
 */
class Reader implements ReaderInterface {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Commerce Sheets entity format manager.
   *
   * @var \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface
   */
  protected $formatManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new Reader object.
   *
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatManagerInterface $format_manager
   *   The entity format plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(
    EntityFormatManagerInterface $format_manager,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system
  ) {
    $this->formatManager = $format_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function read(ImportInterface $import) {
    // Load the file associated with the import.
    $file = $import->get('file')->first()->entity;
    $filepath = $this->fileSystem->realpath($file->getFileUri());

    // Create the spreadsheet reader and get the sheet.
    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(TRUE);

    $spreadsheet = $reader->load($filepath);
    $sheet = $spreadsheet->getActiveSheet();

    // Create the format plugin; we store its definition as a custom property in
    // the spreadsheet.
    $format_definition = $this->formatManager->deserializePluginDefinition(
      $spreadsheet
        ->getProperties()
        ->getCustomPropertyValue(
          EntityFormatManagerInterface::SPREADSHEET_CUSTOM_PROPERTY_FORMAT
        )
    );
    $format = $this->formatManager->createInstance(
      $format_definition['plugin_id'],
      $format_definition['configuration']
    );

    // Currently, supported formats have 3 header rows.
    // There's nothing to read from the header rows; no data, and the format is
    // defined by the format plugin, we don't need to read the header to
    // understand the format. Jump straigh to the data rows.
    $start_row = 4;
    $start_column = 1;

    $this->doRead($sheet, $format, $start_row, $start_column);
  }

  /**
   * Read the sheet and updates the entities based on the given format.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to read from.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $start_row
   *   The row at which to start reading.
   * @param int $start_column
   *   The column at which to start reading.
   */
  protected function doRead(
    Worksheet $sheet,
    EntityFormatInterface $format,
    $start_row,
    $start_column
  ) {
    $entity_type_id = $format->getEntityTypeId();
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

    $sections = $format->getSections();

    $has_associated_entities = FALSE;
    $associated_section = NULL;
    $associated_format = NULL;

    if ($format->hasAssociatedEntities()) {
      $has_associated_entities = TRUE;
      $associated_section = $format->getAssociatedEntitiesSection();
      $associated_format = $associated_section['format'];
    }

    // Go through each row and read its data.
    foreach ($sheet->getRowIterator($start_row) as $row) {
      // Get the ID of the entity associated with the row. If there is no ID
      // neither for the main entity nor for an associated entity, it's an empty
      // row or an error - go to the next one.
      $id = $this->getId($sheet, $format, $row->getRowIndex(), $start_column);
      if (!$id) {
        if (!$associated_section) {
          continue;
        }

        // Read into the associated entity, if one was detected.
        $this->doRead(
          $sheet,
          $associated_format,
          $row->getRowIndex(),
          $associated_section['start']
        );
        continue;
      }

      // Load the entity so that we can update its properties.
      $entity = $entity_storage->load($id);

      // Go through each `properties` section defined in the format, read its
      // property values and update the entity.
      foreach ($format->getSections() as $section) {
        if ($section['type'] !== 'properties') {
          continue;
        }

        $start_column_string = Coordinate::stringFromColumnIndex(
          $start_column + $section['start'] - 1
        );
        $end_column_string = Coordinate::stringFromColumnIndex(
          $start_column + $section['start'] + $section['size'] - 2
        );

        $cell_iterator = $row->getCellIterator(
          $start_column_string,
          $end_column_string
        );
        foreach ($cell_iterator as $cell) {
          $column = Coordinate::columnIndexFromString($cell->getColumn());
          $property_index = $column - $start_column - $section['start'] + 1;
          $property = $section['properties'][$property_index];

          $format->getPropertyPlugin($property)
            ->fromCellToField(
              $cell,
              $entity->get($property)
            );
        }
      }

      $entity->save();

      // After reading the main entity, read the associated entity that may be
      // defined on the same row.
      if (!$has_associated_entities) {
        return;
      }

      $this->doRead(
        $sheet,
        $associated_format,
        $row->getRowIndex(),
        $associated_section['start']
      );
    }
  }

  /**
   * Returns the entity ID for the row based on the given format.
   *
   * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
   *   The sheet to read from.
   * @param \Drupal\commerce_sheets\EntityFormat\EntityFormatInterface $format
   *   The entity format plugin.
   * @param int $row
   *   The current row for which we are detecting the entity.
   * @param int $column
   *   The column where the format/section begins. That would normally be the
   *   first column if we are trying to detect the main entity, or another
   *   column if we are trying to detect an associated entity.
   *
   * @return string|int
   *   The entity ID.
   */
  protected function getId(
    Worksheet $sheet,
    EntityFormatInterface $format,
    $row,
    $column
  ) {
    $column = $column + $format->getColumnForId() - 1;
    $cell = $sheet->getCellByColumnAndRow($column, $row);
    $plugin = $format->getPropertyPluginForId();

    return $plugin->fromCellGetValue($cell);
  }

}
