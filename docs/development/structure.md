### Routing

When someone visits Movary, a request wil be sent to the routing system, which is located in `/settings/routes.php`. 

To add a new route, go to the file `settings/routes.php` and add this new line:

```php
$routes->add('<HTTPMETHOD>', '/your/url/path', [Web\Some\Controller::class, 'Method']);
```

Replace <HTTPMethod> with either `GET`, `POST`, `PUT` or `DELETE` and put this line of code in either the `addWebRoutes` function or the `addApiRoutes` function. It should be in `addWebRoutes` if it's a route that the user will be visiting on the website and in the `addApiRoutes` if it's not.

Movary uses [FastRoute](https://github.com/nikic/FastRoute) to manage most of the routing stuff (only the middleware is added by us) and for more info, visit their git repo.
### Middleware
Middlewares are methods that check if the user is allowed to do this. For example, to check if someone is allowed to access the settings page, the middleware `UserIsAuthenticated` will be used and to check if the user is an admin, the middleware `UserIsAdmin` is used.

All the middleware are in the namespace `Movary\HttpController\Web\Middleware` and use the interface `MiddlewareInterface`. 


#### Writing new middlewares

To add new middlewares, first create a new file like `/src/HttpController/Web/Middleware/MyNewMiddleware.php`.

Then copy-paste this in the new file:


```php
<?php declare(strict_types=1);

namespace Movary\HttpController\Web\Middleware;

class MyNewMiddleware implements MiddlewareInterface
{
    public function __construct(
        // Any other things you need like an authentication service,
    ) {
    }

    public function __invoke() : ?Response
    {
        // Add your own code here
    }
}
```

Change the class name and you're set to start writing your own middleware! The code in the `__invoke()` method is the code that will be executed if the middleware is added to a route and the route is visited by an user.

#### Adding a new middleware to a route

To add a middleware to a route, add the middleware class in an array to the `$routes->add()` method in a fourth parameter like this:

```php
$routes->add('<HTTPMETHOD>', '/your/url/path', [Web\Some\Controller::class, 'Method'], [Web\Middleware\MyNewMiddleware::class, Web\Middleware\MyNewMiddlewareTwo::class]);
```
### HTTP Controllers

The HTTP controllers are all located in `/src/HttpController`. These are the methods that will be executed when the route is visited.

The controllers for the website have the namespace `namespace Movary\HttpController\Web` and the API-related controllers have `namespace Movary\HttpController\Web`.


### Data Transfer Objects (DTO)

Data Transfer Objects (DTO) are frequently used in Movary and can be found throughout the whole backend. 

To put it simply, a DTO is an object that holds a lot of (meta)data about a certain thing. Whether it's a Jellyfin account, a movie or a user itself, the DTOs will be used to communicate this information between classes and methods.

A DTO that is frequently used and accesssed, is the `UserEntity`, which is the class that holds information an user. When one method requests information from another class (or method) about a certain user, they will receive this information as a `UserEntity` object.

Why not just use an associative (or JSON?) array instead of this complicated class? Well, this ensures that the received information will always be send and received in the same shape, with no modifications at all. Every single class property will always exist, 100% guaranteed (though the values sometimes don't exist if they're nullable) which can't be said for a JSON array or a normal associative array.

For a different (perhaps better) explanation, visit this [Stackoverflow thread])(https://stackoverflow.com/q/1051182/12096297)

### Dependency injections, bootstrapping and the Factory
<!-- TODO: Please add some explanation how this works --> 