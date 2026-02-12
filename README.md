
# KB Router

This is a (slightly) opinionated path router.


## The opinion

Many path routers provide the full power of the regex engine to the route table. Allowing one to write any magical incantation they like. Sometimes because they're lazy or maybe they think that it's necessary.

_I believe_ that routes are not complex. They are simple path patterns with a few simple variables.

The role of validating those variables is _not_ in the hands of a router. This is the role of the controller. For example, an end-user needs to be told 'this is not a valid ID'. If you wrote `'/user/([0-9]+)/view'` as your route, the route would never detect these invalid IDs and the user would instead receive 'page not found'. Not helpful.


## The solution

A route 'rule' is a simple syntax.

E.g. `/user/{id}/view/*`

Curly brackets `{}` contain variable names. These follow the same rules as PHP variables + regex group names. That is; `[a-z][a-z0-9_]+`. They only capture between path delimiters. The value will never contain a forward slash - `/`.

Wildcards `*` are bit looser. They are unnamed and can contain anything. You can have as many as you like but at the risk of a very messy result.


## Modes

This package provides three implementation of a router.


### 1. Chunked Group Based

This is an efficient method of executing route patterns in bulk, as described here:

https://www.npopov.com/2014/02/18/Fast-request-routing-using-regular-expressions.html


### 2. Simple Mode

This is a base implementation of rule patterns.


### 3. Regex Mode

This mode provides the full regex engine as a route pattern. This is incompatible with the rule patterns used in the chunked + simple modes.

This is a _transitional_ mode for old projects. I don't intend anyone to use this for long periods of time.


## Install

Install with composer:

`composer require karmabunny/router`


## Usage

```php
use karmabunny\router\Router;

// Create a router with a config.
$router = Router::create([]);

// Load some routes.
// These are keyed:
// [ rule => target ]
$routes = include __DIR__ . '/routes.php';
$router->load($routes);

// Perform routing.
$action = $router->find('GET', '/user/123/edit');

if (!$action) {
    die('not found');
}

// The route target, as defined in the route table.
echo $action->target, PHP_EOL;

// The path arguments. Keyed by name. Wildcards are always last.
echo $action->args, PHP_EOL;
```


The router doesn't explcitly require a "controller" concept, nor methods or anything. Simply it returns the target provided in the rule table.

However, the `Action` class does provide some helpers to execute functions or controller methods.


```php
// Assert the target is callable:
// either a plain method, closure, or [class, method].
$action->isCallable();

// Assert the target is in the form of [class, method].
if ($action->isController(MyController::class)) {
    $controller = $action->createController();
    $result = $action->invoke($controller);
}
else {
    // For everything else.
    $result = $action->invoke();
}
```


## Route table

The route table can be built by hand as always. This a keyed array of rules to target. A 'target' is typically a callable.

This is loaded into the router with `load($routes)`.

The router itself has no preference how you manage your targets. That said, this package contains a bunch of utilities that assumes that your target is a callable, typically within a controller.


### Route Discovery

Instead of building your route table, perhaps try _route discovery_. This a method where one can write their routes right next to their controller actions.

Like so:

```php
use karmabunny/route/Route;

class MyController
{
    #[Route('GET /my/php8/{route}')]
    public function php8Action() {}

    /** @route GET /my/php7/{route} */
    public function php7Action() {}
}
```

If you're gifted with a PHP8 environment, you can use the new attributes feature! Otherwise the `@route` tag always works.


### Action Routes

TODO

These are routes generated from namespaces.

I can't yet decide _how_ magic these will be. All public methods? Or only tagged methods? Or perhaps exclude some methods? Only POST/PUT methods with an action param and security token? Idk!


## Config

- `'mode'` - one of: single|chunked|regex
- `'case_insensitive'` - boolean
- `'methods'` - array valid methods
- `'chunk_size'` - for chunked mode, default 10

See `src/RouterConfig.php`.


## TODO

- some sort of caching
- ACTION method?
- something about unicode?
- compat attribute loader (for sprout3)
  - converts rule syntax to regex
  - inserts targets as `ns\to\class/method/arg1/arg2`
