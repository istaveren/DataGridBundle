Installation
===================================

### Step 1: Download DataGridBundle

Ultimately, the DataGridBundle files should be downloaded to the
`vendor/bundles/Sorien/DataGridBundle` directory.

This can be done in several ways, depending on your preference. The first
method is the standard Symfony2 method.

**Using the vendors script**

Add the following lines in your `deps` file:

```
[DataGridBundle]
    git=git://github.com/S0RIEN/DataGridBundle.git
    target=bundles/Sorien/DataGridBundle
```

Now, run the vendors script to download the bundle:

``` bash
$ php bin/vendors install
```

**Using submodules**

If you prefer instead to use git submodules, the run the following:

``` bash
$ git submodule add git://github.com/S0RIEN/DataGridBundle.git vendor/bundles/Sorien/DataGridBundle
$ git submodule update --init
```

### Step 2: Configure the Autoloader

Add the `Sorien` namespace to your autoloader:

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'Sorien' => __DIR__.'/../vendor/bundles',
));
```

### Step 3: Enable the bundle

Finally, enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
		new Sorien\DataGridBundle\SorienDataGridBundle(),
    );
}
```