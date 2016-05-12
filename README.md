# php-filestorage

File storage for PHP by [Petr Knap].

* [About resolved issue](#about-resolved-issue)
    * [Advantages](#advantages)
    * [Disadvantages](#disadvantages)
* [Usage of php-filestorage](#usage-of-php-filestorage)
    * [Standard usage](#standard-usage)
    * [Create custom file implementation](#create-custom-file-implementation)
    * [Create custom storage manager implementation](#create-custom-storage-manager-implementation)
* [How to install](#how-to-install)



## About resolved issue

> I need to use something where around 60,000 files with average size of 30kb are stored in a single directory (this is a requirement so can't simply break into sub-directories with smaller number of files).
>
> The files will be accessed randomly, but once created there will be no writes to the same filesystem. I'm currently using Ext3 but finding it very slow. Any suggestions?
-- [Filesystem large number of files in a single directory - bugmenot77, voretaq7]

This file storage solves this issue simply - it **creates virtual layer between file system and application**. Every path is converted into path which is composed from many directories which contains only small amount of sub-directories.

If you wish to store 1 000 000 files in one directory, this file storage converts paths and stores them in huge tree-structure. Every directory (exclude leafs) contains up to 256 sub-directories. Leafs contains only files.

### Advantages

 * Can store a huge amount of files in single directory
 * Can use fully localized paths to files (f.e.: `/シックス.log`)
 * Naturally protects files outside the storage
 * Every user can has separated and isolated file storage

### Disadvantages

 * Real file structure is not user-friendly
 * Can not effectively get files sorted by any key (without DBMS)



## Usage of php-filestorage

### Standard usage

```php
use PetrKnap\Php\FileStorage\File\File;
use PetrKnap\Php\FileStorage\StorageManager\StorageManager;

$storage = new StorageManager(__DIR__ . "/temp");

$createFile = new File($storage, "/create.me");
$createFile->create();

$readFile = new File($storage, "/read.me");
fwrite(STDOUT, $readFile->read());

$writeFile = new File($storage, "/write.me");
$writeFile->write("Hello ");
$writeFile->write("World!", true);

$deleteFile = new File($storage, "/delete.me");
$deleteFile->delete();

$file = new File($storage, "/file.txt");
printf("File %s %s", $file->getPath(), $file->exists() ? "found" : "not found");

foreach ($storage->getFiles() as $file) {
    printf("\t%s\n", $file->getPath());
}
```

### Create custom file implementation

```php
use PetrKnap\Php\FileStorage\FileInterface;
use PetrKnap\Php\FileStorage\StorageManagerInterface;

class MyFile implements FileInterface
{
    private $storage;

    private $path;

    public function __construct(StorageManagerInterface $storage, $path)
    {
        $this->storage = $storage;
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function exists()
    {
        return file_exists($this->storage->getPathToFile($this));
    }

    public function create()
    {
        touch($this->storage->getPathToFile($this));

        return $this;
    }

    public function read()
    {
        return file_get_contents($this->storage->getPathToFile($this));
    }

    public function write($data, $append = false)
    {
        file_put_contents($this->storage->getPathToFile($this), $data, $append ? FILE_APPEND : null);

        return $this;
    }

    public function delete()
    {
        unlink($this->storage->getPathToFile($this));

        return $this;
    }
}
```

### Create custom storage manager implementation

```php
use PetrKnap\Php\FileStorage\StorageManagerInterface;

class MyStorageManager implements StorageManagerInterface
{
    public function getStoragePermissions()
    {
        return 0666;
    }

    public function getPathToFile(FileInterface $file)
    {
        return "/mnt/huge_drive" . $file->getPath();
    }

    public function assignFile(FileInterface $file)
    {
        return $this;
    }

    public function unassignFile(FileInterface $file)
    {
        return $this;
    }

    public function getFiles()
    {
        $directoryIterator = new \RecursiveDirectoryIterator($this->pathToStorage);
        $itemIterator = new \RecursiveIteratorIterator($directoryIterator);
        foreach ($itemIterator as $item) {
            if ($item->isFile()) {
                yield new MyFile($this, str_replace($this->getPathToStorage(), "", $item->getRealPath()));
            }
        }
    }
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
[one of released versions]:https://github.com/petrknap/php-filestorage/releases
[this repository as ZIP]:https://github.com/petrknap/php-filestorage/archive/master.zip
