# php-filestorage

File storage for PHP by [Petr Knap].

* [Usage of php-filestorage](#usage-of-php-filestorage)
    * [Standard usage](#standard-usage)
    * [Create custom file implementation](#create-custom-file-implementation)
    * [Create custom storage manager implementation](#create-custom-storage-manager-implementation)
* [How to install](#how-to-install)



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
    public function getPathToStorage()
    {
        return "/mnt/huge_drive";
    }

    public function getStoragePermissions()
    {
        return 0666;
    }

    public function getPathToFile(FileInterface $file)
    {
        return $this->getPathToStorage() . $file->getPath();
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
[one of released versions]:https://github.com/petrknap/php-filestorage/releases
[this repository as ZIP]:https://github.com/petrknap/php-filestorage/archive/master.zip
