# Throttle failed form submissions for your Laravel app

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ghobaty/laravel-form-throttler.svg?style=flat-square)](https://packagist.org/packages/ghobaty/laravel-form-throttler)
[![Build Status](https://img.shields.io/travis/ghobaty/laravel-form-throttler/master.svg?style=flat-square)](https://travis-ci.org/ghobaty/laravel-form-throttler)
[![Quality Score](https://img.shields.io/scrutinizer/g/ghobaty/laravel-form-throttler.svg?style=flat-square)](https://scrutinizer-ci.com/g/ghobaty/laravel-form-throttler)
[![Total Downloads](https://img.shields.io/packagist/dt/ghobaty/laravel-form-throttler.svg?style=flat-square)](https://packagist.org/packages/ghobaty/laravel-form-throttler)

Similar to [Laravel's built-in login throttling], 
this package allows you to throttle the failed submission for any of your form.
This is useful for protecting your forms against spam/bot submissions.

## Installation
You can install the package via composer:
```bash
composer require ghobaty/laravel-form-throttler
```

## Usage
The easiest way to use would be to use `Ghobaty\FormThrottler\ThrottlesForms`,
call `throttle` method, and pass a callback doing in which the validation takes place.

### Before
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    /**
     * Store a new blog post.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|unique:posts|max:255',
            'body' => 'required',
        ]);
    }
}
```

### After
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Ghobaty\FormThrottler\ThrottlesForms;

class PostController extends Controller
{
    use ThrottlesForms;

    /**
     * Store a new blog post.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $validatedData = $this->throttle(function () use ($request) {
            return $request->validate([
                'title' => 'required|unique:posts|max:255',
                'body' => 'required',
            ]);
        });
    }
}
```

### Parameters
By default Form Throttler allows 5 attempts, before locking out the user for 1 minute.  
In case of a lock out, a validation exception would be thrown with error message associated with the field `throttle`

You can customize this behaviour by setting property fields as follows:
```php
<?php

namespace App\Http\Controllers;

use Ghobaty\FormThrottler\ThrottlesForms;

class PostController extends Controller
{
    use ThrottlesForms;

    /**
     * The maximum number of attempts to allow.
     *
     * @var int
     */
    protected $maxAttempts = 5;

    /**
     * The number of minutes to throttle for.
     *
     * @var int
     */
    protected $decayMinutes = 1;

    /**
     * The name of the input field associated with the lock out response.
     *
     * @var string
     */
    protected $validationField = 'throttle';
}
```


## Configuration
Optionally, you can publish the config file of the package.
```bash
php artisan vendor:publish --provider="Ghobaty\FormThrottler\FormThrottlerServiceProvider" --tag=config
```

This is the content of the config file that will be published to `config/form-throttler.php`:

```php
<?php

return [

    /**
     * List of exceptions to throttle against.
     * This should contain all exceptions which might be thrown due to the user input
     */
    'exceptions'      => [
        \Illuminate\Validation\ValidationException::class,
    ],

    /**
     * The HTTP response code to send when the request is throttled.
     */
    'response-status' => \Symfony\Component\HttpFoundation\Response::HTTP_LOCKED,
];

```
### Testing
``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Spatie PHP skeleton](https://github.com/spatie/skeleton-php)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[Laravel's built-in login throttling]: https://laravel.com/docs/5.8/authentication#login-throttling
