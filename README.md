# Diff

Diff implementation for PHP, factored out of PHPUnit into a stand-alone component.

## Installation

You can use [Composer](http://getcomposer.org/) or the [PEAR Installer](http://pear.php.net/manual/en/guide.users.commandline.cli.php) to download and install this package as well as its dependencies.

### Composer

To add this package as a local, per-project dependency to your project, simply add a dependency on `sebastian/diff` to your project's `composer.json` file. Here is a minimal example of a `composer.json` file that just defines a dependency on Diff:

    {
        "require": {
            "sebastian/diff": "*"
        }
    }

### PEAR Installer

The following two commands (which you may have to run as `root`) are all that is required to install this package using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/Diff

### Usage

```php
use SebastianBergmann\Diff\Differ;

$differ = new Differ;
print $differ->diff('foo', 'bar');
```

The code above yields the output below:

    --- Original
    +++ New
    @@ @@
    -foo
    +bar
