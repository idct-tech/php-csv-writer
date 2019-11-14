<?php

declare(strict_types=1);

use IDCT\CsvWriter\TextWriter;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class TextWriterTest extends TestCase
{
    /**
     * @dataProvider bufferSizes
     */
    public function testBufferSizeAlwaysPositiveInt($value, $expected)
    {
        $writer = new TextWriter();
        $writer->setBufferSize($value);
        $returnValue = $writer->getBufferSize();
        $this->assertSame($expected, $returnValue);
    }

    public function bufferSizes()
    {
        return [
            'null'  => [ null, 0 ],
            'zero' => [ 0, 0 ],
            'standard' => [ 8192, 8192 ]
        ];
    }

    public function testBufferSizeInvalidValie()
    {
        $this->expectException(InvalidArgumentException::class);
        $writer = new TextWriter();
        $writer->setBufferSize("illegal");
    }

    public function testEolDefault()
    {
        $writer = new TextWriter();
        $this->assertSame(PHP_EOL, $writer->getEolSymbol());
    }

    /**
     * @dataProvider eolSymbols
     */
    public function testEolValues($value)
    {
        $writer = new TextWriter();
        $writer->setEolSymbol($value);
        $returnValue = $writer->getEolSymbol();
        $this->assertSame($value, $returnValue);
    }

    public function eolSymbols()
    {
        return [
            'win'  => [ TextWriter::EOL_WINDOWS ],
            'nix' => [ TextWriter::EOL_LINUX ],
            'mac' => [ TextWriter::EOL_MAC ]
        ];
    }

    public function testEolIllegal()
    {
        $this->expectException(InvalidArgumentException::class);
        $writer = new TextWriter();
        $writer->setEolSymbol("\n\r");
        $this->assertSame($value, $returnValue);
    }

    public function testOpen()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');

        $writer = new TextWriter();
        $writer->open($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv', TextWriter::FILEMODE_NEW);
        $writer->close();
        
        $this->assertTrue(file_exists($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv'));
    }

    public function testOpenIllegalMode()
    {
        $this->expectException(InvalidArgumentException::class);
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writer = new TextWriter();
        $writer->open($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv', "illegal mode");
        $writer->close();
    }

    public function testOpenCannotOpen()
    {
        $this->expectException(RuntimeException::class);
        $fileSystemMock = vfsStream::setup('sampleDir');
        chmod($fileSystemMock->url('sampleDir'), 000);
        $writer = new TextWriter();
        $writer->open($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv');
        $writer->close();
    }

    public function testOpenWithBufferSize()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');
        $writerMock = $this->getMockBuilder(TextWriter::class)
        ->setMethods(['applyBufferSize', 'getBufferSize'])
        ->getMock();
        $writerMock->expects($this->once())
            ->method('applyBufferSize')
            ->will($this->returnValue($writerMock));

        $writerMock->expects($this->once())
            ->method('getBufferSize')
            ->will($this->returnValue(8196));

        $writerMock->open($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv', TextWriter::FILEMODE_NEW);
        $writerMock->close();
        $this->assertTrue(file_exists($fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv'));
    }

    public function testClose()
    {
        if (preg_match('/^Linux.*?Microsoft.*?$/i', php_uname())) {
            $this->markTestSkipped('Test will not work on WSL v1.');
        }
        
        //sadly vfsStream does not support locking yet...
        $writer = new TextWriter();
        $filepath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($filepath);
        @unlink($filepath);
        $this->assertTrue(file_exists($filepath));
        $writer->close();
        @unlink($filepath);
        $this->assertFalse(file_exists($filepath));
    }

    public function testFlushNoFile()
    {
        $this->expectException(RuntimeException::class);
        $writer = new TextWriter();
        $writer->flush();
    }

    public function testWriteNoFile()
    {
        $this->expectException(RuntimeException::class);
        $writer = new TextWriter();
        $writer->write("test");
    }

    public function testWritelnNoFile()
    {
        $this->expectException(RuntimeException::class);
        $writer = new TextWriter();
        $writer->writeln("test");
    }

    public function testWrite()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');

        $writer = new TextWriter();
        $filepath = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($filepath, TextWriter::FILEMODE_NEW);
        $writer->write("sample text");
        $writer->close();
        
        $this->assertEquals("sample text", file_get_contents($filepath));
    }

    public function testWriteEmpty()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');

        $writer = new TextWriter();
        $filepath = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($filepath, TextWriter::FILEMODE_NEW);
        $writer->write();
        $writer->close();
        
        $this->assertEquals("", file_get_contents($filepath));
    }

    public function testWriteln()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');

        $writer = new TextWriter();
        $filepath = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($filepath, TextWriter::FILEMODE_NEW);
        $writer->setEolSymbol(TextWriter::EOL_WINDOWS);
        $writer->writeln("sample text");
        $writer->close();
        $this->assertEquals("sample text" . TextWriter::EOL_WINDOWS, file_get_contents($filepath));
    }

    public function testWritelnEmpty()
    {
        $fileSystemMock = vfsStream::setup('sampleDir');

        $writer = new TextWriter();
        $filepath = $fileSystemMock->url('sampleDir') . DIRECTORY_SEPARATOR . 'somefile.csv';
        $writer->open($filepath, TextWriter::FILEMODE_NEW);
        $writer->setEolSymbol(TextWriter::EOL_WINDOWS);
        $writer->writeln();
        $writer->close();
        $this->assertEquals(TextWriter::EOL_WINDOWS, file_get_contents($filepath));
    }
}
