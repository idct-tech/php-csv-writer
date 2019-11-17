
# PHP CSV Writer
Basic CsvWriter based on a buffered TextWriter (also provided).
Supports buffering in memory and proper handling of EOL setting which is not available easily with native `fputcsv` function.

![Tests status](https://github.com/ideaconnect/php-csv-writer/workflows/All%20tests%20using%20PHPUnit/badge.svg) [![Coverage Status](https://coveralls.io/repos/github/ideaconnect/php-csv-writer/badge.svg?branch=master)](https://coveralls.io/github/ideaconnect/php-csv-writer?branch=master) ![GitHub tag (latest SemVer)](https://img.shields.io/github/v/tag/ideaconnect/php-csv-writer?label=latest%20version&sort=semver)

Main purpose of this library is to provide an object-oriented way of buffered CSV files writing.

## Installation
The best way to install the library in your project is by using **Composer**:
```bash
composer require idct/php-csv-writer
```
of course you can still manually include all the required files in your project using `using` statements yet **Composer** and autoloading is more than suggested.
## Usage
Create an instance:
```php
use IDCT\CsvWriter\CsvWriter;
$csvWriter = new CsvWriter();
```

Now you need to open or create a file, to do so you have two methods available: **open** and **openWithFieldsNames**
To create a new file with without adding headers in the first line use:
```php
$csvWriter->open('filename.csv');
```

To open a new file with fields' names added in the first line pass an array as the second argument:
```php
$csvWriter->openWithFieldsNames('filename.csv', ["columnA", "columnB", "columnC"]);
```

**When writing a new line (adding data) to a CSV file opened with fields names then columns count number is verified on each attempt.**

It is possible to appened into existing files by passing __TextWriter::FILEMODE_APPEND__ as the last argument.
```php
$csvWriter->open('filename.csv', CsvWriter::FILEMODE_APPEND);
```
or:
```php
$csvWriter->openWithFieldsNames('filename.csv', ["columnA", "columnB", "columnC"], CsvWriter::FILEMODE_APPEND);
```
**Warning: in append mode lines are NOT added as the first line of the CSV file, they are used only for verification of columns count during write operations.**

### Buffering 
By default **CsvWriter** has buffering disabled, you can enable it by providing buffer size (in bytes) using method **setBufferSize**.
Be sure to understand that data is actually saved in the file only when buffer is filled so be sure to close the file using **close** method or use **flush** whenever you want to intentionally flush the buffer into the file without closing it.
### EOL support
By default PHP's internal `fputcsv` function uses default EOL symbol for the current platform. `idct/php-csv-writer` supports setting different EOL symbols:
pass `CsvWriter::EOL_WINDOWS`, `CsvWriter::EOL_LINUX` or `CsvWriter::EOL_MACLEGACY` (Mac today uses LINUX EOL, this is just for legacy support) to **setEolSymbol** method.
**Warning:** the EOL symbol will be used only for **NEXT** lines, previous ones (already existing in the file or buffer) will remain intact.
### TextWriter 
CsvWriter is based on **TextWriter** which can be used for buffered text write operations. Apart from the methods described above it provides also different behavior for `write` and `writeln` operations which store the text or write it followed by the defined EOL symbol respectively.
To use it just create an instance:
```php
$textWriter = new TextWriter();
```
## TODO / Contribution
At the moment the main requirement is to provide better unit tests and documentation, yet if you find any bugs or have potential feature ideas then please use Issues or Pull Requests, it is more than welcome! I will try to reply ASAP.
