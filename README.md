# PHP CSV Writer

Basic CsvWriter based on a TextWriter (also provided).

Main purpose of this library is to provide an object-oriented way of buffered
CSV files writing.

## Usage

To initialize:

```php
$writer = (new CsvWriter())
          ->open($filePath)
          ;
```

or with fields' names:
```php
$writer = (new CsvWriter())
          ->openWithFieldsNames($filePath, ['fieldA', 'fieldB'])
          ;    
```

To write:

```php
$writer->write(['column1_value', 'column2_value']);
```

To close (and flush the buffer):

```php
$writer->close();
```

Opening another file automatically closes previous one.

## TODO / Contribution

At the moment the main requirement is to provide better unit tests and documentation. 
Feel free to add any issues or pull requests, they are more than welcome!