# php-filestorage

File storage for PHP by [Petr Knap].

* [Usage of php-filestorage](#usage-of-php-filestorage)
    * [File declaration](#file-declaration)
    * [File usage](#file-usage)
* [How to install](#how-to-install)



## Usage of php-filestorage

### File declaration
```php
class File extends \PetrKnap\Php\FileStorage\AbstractFile
{
    protected function getStorageDirectory()
    {
        return __DIR__ . "/../storage";
    }
}
```

### File usage
```php
$file = new File("/write.txt");
$file->crate();
$file->write("Hello world!");
```

```php
$file = new File("/read.txt");
if ($file->exists()) {
    echo $file->read();
} else {
    throw new \Exception("File not found.");
}
```

```php
$file = new File("/delete.txt");
if ($file->exists()) {
    $file->delete();
} else {
    throw new \Exception("File not found.");
}
```


## How to install

Run `composer require petrknap/php-filestorage` or merge this JSON code with your project `composer.json` file manually and run `composer install`. Instead of `dev-master` you can use [one of released versions].

```json
{
    "require": {
        "petrknap/php-filestorage": "dev-master"
    }
}
```

Or manually clone this repository via `git clone https://github.com/petrknap/php-filestorage.git` or download [this repository as ZIP] and extract files into your project.



[Petr Knap]:http://petrknap.cz/
[one of released versions]:https://github.com/petrknap/php-filestorage/releases
[this repository as ZIP]:https://github.com/petrknap/php-filestorage/archive/master.zip
