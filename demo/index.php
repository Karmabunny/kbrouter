<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

use karmabunny\router\Route;
use karmabunny\router\Router;

class App
{
    /** @var Router */
    public $router;


    /**
     * Do everything.
     */
    public function run()
    {
        $this->router = Router::create();
        $this->router->loadFrom(self::class);

        // Debug.
        // $this->routes();

        $method = $_SERVER['REQUEST_METHOD'];
        $path = '/' . trim($_GET['_uri'] ?? '', '/');
        $action = $this->router->find($method, $path);

        if (!$action) {
            $this->notFound();
        }
        else {
            $action->invoke($this);
        }
    }


    /**
     * No dice.
     */
    public function notFound()
    {
        header('content-type: text/html');
        http_response_code(404);
        include __DIR__ . '/pages/404.php';
        exit;
    }


    /**
     * Entry page.
     *
     * @route GET /
     * @route GET /home
     * @route GET /welcome
     */
    #[Route('/')]
    public function index()
    {
        header('location: /view/welcome');
    }


    /**
     * Show routes.
     *
     * @route GET /routes
     */
    public function routes()
    {
        header('content-type: text/plain');
        foreach ($this->router->routes as $rule => $target) {
            $target = implode('::', $target);
            echo "{$rule} => {$target}()\n";
        }
        exit;
    }


    /**
     * View a document.
     *
     * @param string $page
     * @route /view/{page}
     */
    #[Route('/view/{page}')]
    public function page(string $page)
    {
        $path = realpath(__DIR__ . "/pages/{$page}.php");
        if (!$path) $this->notFound();

        header('content-type: text/html');
        include $path;
        exit;
    }


    /**
     * Some more complex pattern matching.
     *
     * @param mixed $x
     * @param mixed $y
     * @route GET /test/{x}/power/{y}
     */
    function power(string $x, string $y)
    {
        header('content-type: text/plain');
        echo pow($x, $y), PHP_EOL;
        exit;
    }


    /**
     * No doc routes, just attributes.
     *
     * @param ?string $hiiii
     */
    #[Route('/php8/{hiiii}')]
    #[Route('/lots-and-lots')]
    function php8only(?string $hiiii = null)
    {
        if (!$hiiii) {
            $hiiii = 'Yoouu';
        }

        header('content-type: text/html');
        echo "Welcome to the future: {$hiiii}", PHP_EOL;
        exit;
    }
}


// Do it all!
(new App)->run();
