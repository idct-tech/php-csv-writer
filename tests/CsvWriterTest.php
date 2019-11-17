<?php

declare(strict_types=1);

namespace IDCT\CsvWriter\Tests;

use IDCT\CsvWriter\CsvWriter;
use InvalidArgumentException;
use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsvWriterTest extends TestCase
{
    public function testGetDelimeterEnclosureDefault()
    {
        $writer = new CsvWriter();
        $this->assertEquals($writer->getEnclosure(), '"');
        $this->assertEquals($writer->getDelimeter(), ',');
    }

    public function testSetGetDelimeter()
    {
        $writer = new CsvWriter();
        $writer->setDelimeter(';');
        $this->assertEquals($writer->getDelimeter(), ';');
    }

    public function testSetGetDelimeterTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
        $writer = new CsvWriter();
        $writer->setDelimeter(';;');
    }

    public function testSetGetEnclosure()
    {
        $writer = new CsvWriter();
        $writer->setEnclosure('`');
        $this->assertEquals($writer->getEnclosure(), '`');
    }

    public function testSetGetEnclosureTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
        $writer = new CsvWriter();
        $writer->setEnclosure('``');
    }

    public function testWriteInvalidInput()
    {
        $this->expectException(InvalidArgumentException::class);
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter();
        $filepath = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($filepath, CsvWriter::FILEMODE_NEW);
        $writer->write("sample text");
        $writer->close();
    }

    public function testWriteInvalidInputCountMismach()
    {
        $this->expectException(LogicException::class);
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writerMock = $this->getMockBuilder(CsvWriter::class)
        ->setMethods(['getFieldsNamesCount'])
        ->getMock();

        $writerMock->expects($this->any())
            ->method('getFieldsNamesCount')
            ->will($this->returnValue(3));

        $writerMock->open($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv', CsvWriter::FILEMODE_NEW);
        $writerMock->write(['aa','bb']);
    }

    public function testWriteNoHeaders()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($path, CsvWriter::FILEMODE_NEW);
        $writer->write(['a,a','b,b','c,c']);
        $writer->close();

        $contents = file_get_contents($path);
        $this->assertEquals('"a,a","b,b","c,c"' . PHP_EOL, $contents);
    }

    public function testWriteWithHeaders()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, ['headA', 'headB'], CsvWriter::FILEMODE_NEW);
        $writer->write(['a,a','b,b']);
        $writer->write(['d,d','c,c']);
        $writer->close();

        $contents = file_get_contents($path);
        $this->assertEquals('headA,headB' . PHP_EOL . '"a,a","b,b"' . PHP_EOL . '"d,d","c,c"' . PHP_EOL, $contents);
    }

    public function testWriteNoHeadersWindowsEol()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->setEolSymbol($writer::EOL_WINDOWS);
        $writer->open($path, CsvWriter::FILEMODE_NEW);
        $writer->write(['a,a','b,b','c,c']);
        $writer->close();

        $contents = file_get_contents($path);
        $this->assertEquals('"a,a","b,b","c,c"' . $writer::EOL_WINDOWS, $contents);
    }

    public function testWriteWithHeadersWindowsEol()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->setEolSymbol($writer::EOL_WINDOWS);
        $writer->openWithFieldsNames($path, ['headA', 'headB'], CsvWriter::FILEMODE_NEW);
        $writer->write(['a,a','b,b']);
        $writer->write(['d,d','c,c']);
        $writer->close();

        $contents = file_get_contents($path);
        $this->assertEquals('headA,headB' . $writer::EOL_WINDOWS . '"a,a","b,b"' . $writer::EOL_WINDOWS . '"d,d","c,c"' . $writer::EOL_WINDOWS, $contents);
    }
    
    public function testWriteWithHeadersWithoutAppendingThem()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, ['headA', 'headB'], CsvWriter::FILEMODE_APPEND);
    
        $writer->write(['a,a','b,b']);
        $writer->write(['d,d','c,c']);
        $writer->close();

        $contents = file_get_contents($path);
        $this->assertEquals('"a,a","b,b"' . PHP_EOL . '"d,d","c,c"' . PHP_EOL, $contents);
    }
    
    public function testWriteWithHeadersWithAndWithoutoutAppendingThem()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, ['headA', 'headB'], CsvWriter::FILEMODE_NEW);
        $writer->write(['a,a','b,b']);
        $writer->close();

        $writer->openWithFieldsNames($path, ['headA', 'headB'], CsvWriter::FILEMODE_APPEND);
        $writer->write(['d,d','c,c']);
        $writer->close();

        $contents = file_get_contents($path);
        $this->assertEquals('headA,headB' . PHP_EOL . '"a,a","b,b"' . PHP_EOL . '"d,d","c,c"' . PHP_EOL, $contents);
    }

    public function testOpenWithHeadersEmptyFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, [], CsvWriter::FILEMODE_NEW);
    }

    public function testOpenWithHeadersInvalidFields()
    {
        $this->expectException(InvalidArgumentException::class);
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, [ ['abc'], ['def'] ], CsvWriter::FILEMODE_NEW);
    }

    public function testOpenWithHeaders()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter(',', '"');
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, [ 'abc', 'def' ], CsvWriter::FILEMODE_NEW);
        $this->assertEquals($writer->getFieldsNamesCount(), 2);
        $this->assertEquals($writer->getFieldsNames(), [ 'abc', 'def' ]);
    }

    public function testCloseFields()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new CsvWriter();
        $path = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->openWithFieldsNames($path, [ 'abc', 'def' ], CsvWriter::FILEMODE_NEW);
        $this->assertEquals($writer->getFieldsNamesCount(), 2);
        $this->assertEquals($writer->getFieldsNames(), [ 'abc', 'def' ]);
        $writer->close();
        $this->assertEquals($writer->getFieldsNamesCount(), 0);
        $this->assertEquals($writer->getFieldsNames(), null);
    }

    public function testWriteNoFile()
    {
        $this->expectException(RuntimeException::class);
        $writer = new CsvWriter();
        $writer->write("test");
    }
}
