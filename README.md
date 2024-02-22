<p align="center"><img src="./art/logo.svg" alt="Logo LaraBase"></p>

<p align="center">
    <a href="https://packagist.org/packages/gerson/laravel-base">
        <img src="https://img.shields.io/packagist/dt/gerson/laravel-base" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/gerson/laravel-base">
        <img src="https://img.shields.io/packagist/v/gerson/laravel-base" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/gerson/laravel-base">
        <img src="https://img.shields.io/packagist/l/gerson/laravel-base" alt="License">
    </a>
</p>

# Introduction

Laravel Base is a package that aims to facilitate the development of applications using the Laravel framework. It is a package that provides a base for the development of applications, providing a structure for the development of services, repositories and controllers, as well as providing a base for the development of resources.

## Requirements

```PHP ^8.1```

## How to install

To install the package, simply run the following command in your project's root directory.

```bash
composer require gerson/laravel-base
```

Let's open the file

```bash
config/app.php
```

and search for
```php
'providers' => [ ...
```

to the end of the array, just add

```php
gersonalves\laravelBase\BaseLaravelServiceProvider::class
```

In your project, within App create two directories.
<strong>Services</strong> e <strong>Repositories</strong>

In the "Controller.php" file within app/Http/Controllers
Replace

```php
use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
```

by

```php
use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ControllerTrait;
```

And add it to the file header.

```php
use gersonalves\laravelBase\Traits\ControllerTrait;
```

## How to use
Using it is very simple, you can generate your resources with a simple command.
The only requirement is that you have already generated your <strong>MODEL</strong>.

Just run the command

```bash
php artisan larabase:resource
```
and answer questions.

Suggestion: Use the name of the Model in the first question (which will ask for the name of the service).
If you say yes to all items, at the end you will have one Repository

1 - Service
1 - Controller. (Already with CRUD ready to be executed).

## How it works

The get() method will fetch all items from your model, you can apply the relationships, just create a function in your model with the name of
scopeWithRelations, follow the example:
  
```php
public function scopeWithRelations(Illuminate\Database\Eloquent\Builder $query)
{
    return $query->with(['address', 'people']);
}
```
Thus, it will be applied to the INDEX and SHOW method.

As for persistence (Store and Update), it will persist if the indexes coming from the request are the same as the name of the columns in the database.
Note: your Fillable needs to have these columns, that's where it will extract it from.
Additionally, you can override the store and update method in your Service. thus applying its specific rules.

## Some features

In your controller, you can configure validation as follows:

# #1 - Controllers
```php 
  protected ?array $validators = [
        'email' => 'required|unique:users,email',
        'password' => 'required|min:8|string',
        'cpf' => 'required|cpf|formato_cpf',
    ];
```

Therefore, any store or update request will apply these rules. But then, we have a problem.
By informing that the email must be unique, the update will break saying that the email already exists, since validation is applied before,
To resolve this, we have two options, the first is to override the email rule for when it is an update, as follows:

```php 
  protected ?array $replaceOnUpdate = [
        'email' => 'required'
    ];
```
This way, it will merge between the two arrays, when updated and the email will continue to be required, however, without unique validation.
Furthermore, it will maintain the other rules, for cpf and password, if I want to remove them, I can do it as follows:

```php 
protected ?array $excludeOnUpdate = ['cpf', 'password'];
```

This way, using all 3, the result will be, you will only apply $validators when creating a new record and when updating, it will only require you to fill in the E-mail.

# #2 - Services

In our previous version, we needed to do this to save something at multilevel:

```php 
  public function store(Request $request)
    {
        $eventAddress = new EventAddressRepository();
        $address = $eventAddress->store(new Request($request->get('event_address')));
        $request = $this->mergeRequest($request, ['id_event_address' => $address['id_event_address']]);
        $events = $this->repository->store($request);
        return array_merge($events, ['event_address' => $address]);
    }
```

With the new version, we no longer need this, see the example:
```php 
 protected ?array $parentStore = [
        EventAddressService::class => 
        [
          'persist' => PersistEnum::BEFORE_PERSIST,
          'callback' => 'NomeDaFuncaoDoCallBack'
        ]
    ];
    
    public function NomeDaFuncaoCallBack($model){
      
    }
```
I just need to inform the Repository that it becomes the "child" of the object and at what point it should be persisted, before or after the main one.

# #3 - Repository
If I need to store an image, considering that I will receive it in base64 via JSON request, in the following example:

```json 
{
  "logo": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII="
}
```

I can configure the following in my repository:
```php 
protected ?array $storeFile = [
  'logo' => [
    'type' => fileEnum::BASE64_IMAGE,
    'path' => 'public/'
  ]
];
```

This way, it will convert the base64 into a file, save it in storage at the pre-defined path and return the URL to access the file and add this to save it in the database if there is a column with the name "logo " in that case.
something like:

```json 
{
  "logo": "localhost/storage/asdf-asdf-asdf-asdf.png"
}
```
