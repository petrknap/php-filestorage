# php-filestorage

File storage for PHP by [Petr Knap].

* [About resolved issue](#about-resolved-issue)
    * [Advantages](#advantages)
    * [Disadvantages](#disadvantages)
* [Usage of php-filestorage](#usage-of-php-filestorage)
    * [Standard usage](#standard-usage)
* [How to install](#how-to-install)



## About resolved issue

> I need to use something where around 60,000 files with average size of 30kb are stored in a single directory (this is a requirement so can't simply break into sub-directories with smaller number of files).
>
> The files will be accessed randomly, but once created there will be no writes to the same filesystem. I'm currently using Ext3 but finding it very slow. Any suggestions?
>
> -- [Filesystem large number of files in a single directory - bugmenot77, voretaq7]

This file storage solves this issue simply - it **creates virtual layer between file system and application**. Every path is converted into path which is composed from many directories which contains only small amount of sub-directories.

If you wish to store 1 000 000 files in single directory, this file storage converts paths and stores them in huge tree-structure. Every directory (exclude leafs) contains up to 256 sub-directories. Leafs contains only files.

### Advantages

 * Can store a huge amount of files in single directory
 * Can use fully localized paths to files (f.e.: `/シックス.log`)
 * Naturally protects files outside the storage
 * Every user can has separated and isolated file storage
 * Fully compatible and based on [League\Flysystem]

### Disadvantages

 * Real file structure is not user-friendly
 * Can not effectively get files sorted by any key (without DBMS)



## Usage of php-filestorage

### Standard usage

```php
use League\Flysystem\Adapter\Local as LocalAdapter;
use PetrKnap\Php\FileStorage\FileSystem;
use PetrKnap\Php\FileStorage\Plugin\SQLiteIndexPlugin;

$fileSystem = new FileSystem(new LocalAdapter(__DIR__ . "/temp"));
SQLiteIndexPlugin::register($fileSystem, __DIR__ . "/temp/index.sqlite"); // optional

$fileSystem->write("/file.txt", null);
$fileSystem->update("/file.txt", "Hello World!");

printf("%s", $fileSystem->read("/file.txt"));

foreach ($fileSystem->listContents() as $metadata) {
    $fileSystem->delete($metadata["path"]);
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
[Filesystem large number of files in a single directory - bugmenot77, voretaq7]:http://serverfault.com/q/43133
[League\Flysystem]:https://github.com/thephpleague/flysystem
[one of released versions]:https://github.com/petrknap/php-filestorage/releases
[this repository as ZIP]:https://github.com/petrknap/php-filestorage/archive/master.zip
