<?php

declare(strict_types=1);

namespace IDCT\CsvWriter;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

/**
 * TextWriter
 *
 * Allows to perform simple write operations on text files. Provides simplified
 * EOL and buffer handling.
 *
 * @author IDCT Bartosz Pachołek <bartosz@idct.pl>
 * @package CsvWriter
 * @access public
 */
class TextWriter
{
    const FILEMODE_NEW = "w";
    const FILEMODE_APPEND = "a";

    const EOL_WINDOWS = "\r\n";
    const EOL_LINUX = "\n";
    const EOL_MAC = "\r";

    /**
     * Buffer size in bytes.
     *
     * @var int
     */
    protected $bufferSize;

    /**
     * File resource.
     *
     * @var Resource
     */
    protected $file;

    /**
     * End-of-line symbol string.
     *
     * @var string
     */
    protected $eolSymbol;

    /**
     * Gets the buffer size in bytes (defaults to 0).
     *
     * @return int
     */
    public function getBufferSize(): int
    {
        return (int) $this->bufferSize;
    }

    /**
     * Sets the buffer size. In bytes.
     * If file is already open the attempts to modify the stream.
     *
     * @param int|null $bufferSize
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setBufferSize($bufferSize): TextWriter
    {
        if (!(is_int($bufferSize) || $bufferSize === null) || $bufferSize < 0) {
            throw new InvalidArgumentException('Buffer size must be a non-negative integer or null. Given: `' . $bufferSize . '`.');
        }

        $this->bufferSize = $bufferSize;
        if (is_resource($this->file)) {
            $this->applyBufferSize();
        }

        return $this;
    }

    /**
     * Returns the previously set EOL symbol.
     * Defaults to PHP_EOL.
     *
     * @return string
     */
    public function getEolSymbol(): string
    {
        if ($this->eolSymbol === null) {
            return PHP_EOL;
        }

        return $this->eolSymbol;
    }

    /**
     * Sets EOL symbol.
     * Allows only valid combinations of \r \n \r\n.
     * Use EOL_* constants.
     *
     * @todo use enums
     * @param string $eolSymbol Use EOL_*
     * @return $this
     */
    public function setEolSymbol($eolSymbol): TextWriter
    {
        $oClass = new ReflectionClass(__CLASS__);
        $validEols = [];
        foreach ($oClass->getConstants() as $name => $value) {
            if (substr($name, 0, 4) === "EOL_") {
                $validEols[] = $value;
            }
        }

        if (!in_array($eolSymbol, $validEols)) {
            throw new InvalidArgumentException('Invalid EOL symbol. Use EOL_* constants.');
        }

        $this->eolSymbol = $eolSymbol;

        return $this;
    }

    /**
     * Opens file in the desired mode (new or append).
     * Attempts to set applied buffer (if set).
     * Closes previously opened file if set.
     *
     * @param string $filename
     * @param string $mode FILEMODE_* const
     * @todo use enums
     * @todo Eol detection
     * @throws RuntimeException
     * @return $this
     */
    public function open($filename, $mode = self::FILEMODE_NEW): TextWriter
    {
        //just in case we have any file open
        $this->close();

        $oClass = new ReflectionClass(__CLASS__);
        $validFilemodes = [];
        foreach ($oClass->getConstants() as $name => $value) {
            if (substr($name, 0, 9) === "FILEMODE_") {
                $validFilemodes[] = $value;
            }
        }

        if (!in_array($mode, $validFilemodes)) {
            throw new InvalidArgumentException('Invalid file mode. Use FILEMODE_* constants.');
        }

        $this->file = @fopen($filename, $mode);
        if (!$this->file) {
            throw new RuntimeException('Could not open file ' . $filename . '.');
        }

        if (!flock($this->file, LOCK_EX)) {
            throw new RuntimeException('Could not lock file ' . $filename . '.');
        }

        if ($this->getBufferSize() > 0) {
            $this->applyBufferSize();
        }

        return $this;
    }

    /**
     * Closes the open file resource.
     *
     * @return $this
     */
    public function close(): TextWriter
    {
        if (is_resource($this->file)) {
            fflush($this->file);
            flock($this->file, LOCK_UN);    // release the lock
            fclose($this->file);
        }

        return $this;
    }

    /**
     * Flushes the internal buffer.
     *
     * @throws RuntimeException
     * @return $this
     */
    public function flush(): self
    {
        $this->validateResource();
        if (!fflush($this->file)) {
            throw new RuntimeException("Could not flush file's internal buffer.");
        }

        return $this;
    }

    /**
     * Attempts to write text into the file.
     *
     * @throws RuntimeException
     * @return $this
     */
    public function write($text = null): TextWriter
    {
        $this->validateResource();
        fwrite($this->file, (string) $text);

        return $this;
    }

    /**
     * Attempts to write text followed by the EOL symbol defined using
     * set/getEolSymbol into the file.
     *
     * @throws RuntimeException
     * @return $this
     */
    public function writeln($text = null): TextWriter
    {
        $this->validateResource();
        $this->write($text . $this->getEolSymbol());

        return $this;
    }

    /**
     * Attempts to set the buffer size defined by setBufferSize on the open file
     * resource.
     *
     * @throws RuntimeException
     * @return $this;
     */
    protected function applyBufferSize(): TextWriter
    {
        $code = stream_set_write_buffer($this->file, $this->getBufferSize());
        if ($code === 0) {
            return $this;
        }

        throw new RuntimeException('Could not apply buffer ' . $this->getBufferSize() . ', code: ' . $code);
    }

    /**
     * Verifies if there is an open file assigned. Throws exception if not.
     *
     * @throws RuntimeException
     * @return $this
     */
    protected function validateResource(): TextWriter
    {
        if (!is_resource($this->file)) {
            throw new RuntimeException("No writable file opened.");
        }

        return $this;
    }
}
