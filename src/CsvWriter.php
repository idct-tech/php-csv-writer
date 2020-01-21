<?php

declare(strict_types=1);

namespace IDCT\CsvWriter;

use InvalidArgumentException;
use LogicException;

/**
 * CsvWriter
 *
 * Wrapper over TextWriter and fputcsv methods which simplifies the procedure
 * of writing arrays into csv files.
 *
 * @author IDCT Bartosz Pachołek <bartosz@idct.pl>
 * @package CsvWriter
 * @access public
 */
class CsvWriter extends TextWriter
{
    /**
     * Fields delimeter.
     *
     * @var string
     */
    protected $delimeter;

    /**
     * Fields enclosure.
     *
     * @var string
     */
    protected $enclosure;

    /**
     * Fields' names: when set using `openWithFieldnames` then fields count is
     * verified on every write operation.
     *
     * @var string[]|null
     */
    protected $fieldsNames;

    /**
     * Number of fields.
     *
     * @var int
     */
    protected $fieldsNamesCount;

    /**
     * Creates new instance of the CsvWriter.
     *
     * @param string $delimeter = ','
     * @param string $enclosure = '"'
     */
    public function __construct($delimeter = ',', $enclosure = '"')
    {
        $this->setDelimeter($delimeter)
             ->setEnclosure($enclosure)
             ;
    }

    /**
     * Returns the assigned fields' names count.
     *
     * @return int
     */
    public function getFieldsNamesCount()
    {
        return $this->fieldsNamesCount;
    }

    /**
     * Returns the assigned fields' names.
     *
     * @return string[]|null
     */
    public function getFieldsNames()
    {
        return $this->fieldsNames;
    }

    /**
     * Opens file in the desired mode (new or append) with fields` names.
     *
     * When mode is set the FILEMODE_NEW it will write fields' names as first
     * line.
     *
     * Attempts to set applied buffer (if set).
     * Closes previously opened file if set.
     *
     * @param string $filename
     * @param string[] $fieldsNames
     * @param string $mode FILEMODE_* const
     * @todo use enums
     * @todo Eol detection
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @return $this
     */
    public function openWithFieldsNames($filename, array $fieldsNames, $mode = self::FILEMODE_NEW)
    {
        $this->open($filename, $mode);

        if (empty($fieldsNames)) {
            throw new InvalidArgumentException('Second argument must contain a non-empty array of strings for fields names.');
        }

        $fieldsCount = count($fieldsNames);

        foreach ($fieldsNames as $field) {
            if (!ctype_alnum($field)) {
                throw new InvalidArgumentException('Field name must only contain letters and numbers.');
            }
        }

        if ($mode === self::FILEMODE_NEW) {
            $this->write($fieldsNames);
        }

        $this->fieldsNames = $fieldsNames;
        $this->fieldsNamesCount = $fieldsCount;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): TextWriter
    {
        parent::close();

        $this->fieldsNames = null;
        $this->fieldsNamesCount = null;

        return $this;
    }

    /**
     * Returns the delimeter previously set.
     *
     * @return string
     */
    public function getDelimeter()
    {
        return $this->delimeter;
    }

    /**
     * Sets the fields delimeter.
     *
     * @throws InvalidArgumentException
     * @param string $delimter
     * @todo detect invalid values
     * @return $this
     */
    public function setDelimeter($delimeter)
    {
        if (!is_string($delimeter) || strlen($delimeter) !== 1) {
            throw new InvalidArgumentException("Delimeter must be a string, exactly 1 character long.");
        }

        $this->delimeter = $delimeter;

        return $this;
    }

    /**
     * Gets the previously set enclosure string.
     *
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * Sets the fields enclosure.
     *
     * @throws InvalidArgumentException
     * @param string $enclosure
     * @todo detect invalid values
     * @return $this
     */
    public function setEnclosure($enclosure)
    {
        if (!is_string($enclosure) || strlen($enclosure) !== 1) {
            throw new InvalidArgumentException("Enclosure must be a string, exactly 1 character long.");
        }

        $this->enclosure = $enclosure;

        return $this;
    }

    /**
     * Writes the array of values into the open csv file.
     * Previously set delimeter and enclosure are used.
     *
     * Values in the array must provide ways of casting to string.
     *
     * @throws RuntimeException
     * @throws LogicException
     * @throws InvalidArgumentException
     * @param string[] $data
     * @return $this
     */
    public function write($data = null): TextWriter
    {
        $this->validateResource();
        if ($data === null) {
            $data = [];
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException('Input data must be of type array or null.');
        }

        if ($this->getFieldsNamesCount() > 0 && count($data) !== $this->getFieldsNamesCount()) {
            throw new LogicException('Invalid fields count, must be exactly: ' . $this->getFieldsNamesCount());
        }

        parent::writeln($this->arrayToCsv($data));

        return $this;
    }

    /**
     * Writes the array of values into the open csv file.
     * Previously set delimeter and enclosure are used.
     *
     * Values in the array must provide ways of casting to string.
     *
     * @throws RuntimeException
     * @throws LogicException
     * @throws InvalidArgumentException
     * @param string[] $data
     * @return $this
     */
    public function writeln($data = null): TextWriter
    {
        return $this->write($data);
    }

    /**
     * Converts an array to CSV string.
     * 
     * @param array $string
     * @return string
     */
    protected function arrayToCsv(array $data): string
    {
        $handle = fopen('php://memory', 'w');
        fputcsv($handle, $data, $this->getDelimeter(), $this->getEnclosure());
        fseek($handle, 0);
        $csv = stream_get_contents($handle);
        $csv = mb_convert_encoding($csv, 'iso-8859-2', 'utf-8');

        return trim($csv);
    }

    /**
     * Destroys the instance.
     */
    public function __destruct() {
        $this->close();
    }
}
