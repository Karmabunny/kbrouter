
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

This is a _transitional_ mode for old projects. I don't intend anyone to use for long periods of time.


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


## Config

- `'mode'` - one of: single|chunked|regex
- `'case_insensitive'` - boolean
- `'methods'` - array valid methods
- `'chunk_size'` - for chunked mode, default 10

See `src/RouterConfig.php`.


## TODO

- load() should _add_ routes, not replace
   - also chunked compile() will duplicate atm, defs a bug
- some sort of caching
- fix de-dupe variables for regex/simple mode
- create auto routes from namespaces
- create route attribute (PHP8, maybe @route doc)
- ACTION method?
- something about unicode?
