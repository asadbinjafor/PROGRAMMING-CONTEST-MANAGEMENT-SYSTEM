<?php
declare(strict_types=1);

namespace PCMS\Support;

final class Router
{
    public function __construct(private array $routes) {}

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim((string)(parse_url($uri, PHP_URL_PATH) ?: '/'), '/');
        if ($path === '//') $path = '/';
        if ($method === 'POST' && isset($_POST['_method'])) $method = strtoupper((string)$_POST['_method']);

        foreach ($this->routes as $route) {
            [$routeMethod, $pattern, $handler, $middleware] = $route + [3 => []];
            $regex = '#^' . preg_replace('/\{([a-z_]+)\}/i', '(?P<$1>[^/]+)', $pattern) . '$#';
            if ($method !== $routeMethod || !preg_match($regex, $path, $matches)) continue;
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->guard($middleware);
            [$class, $action] = $handler;
            (new $class())->{$action}(...array_values($params));
            return;
        }
        http_response_code(404);
        View::render('errors/status', ['title'=>'Page not found','code'=>404,'message'=>'The requested page does not exist.']);
    }

    private function guard(array $middleware): void
    {
        if (in_array('csrf', $middleware, true) && !Csrf::verify($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(419);
            View::render('errors/status', ['title'=>'Session expired','code'=>419,'message'=>'Refresh the page and try again.']);
        }
        if (in_array('guest', $middleware, true) && Auth::check()) redirect('/dashboard');
        if (in_array('auth', $middleware, true) && !Auth::check()) {
            Flash::add('error', 'Please sign in to continue.');
            redirect('/login');
        }
        foreach ($middleware as $item) {
            if (str_starts_with($item, 'role:') && !Auth::hasRole(substr($item, 5))) {
                http_response_code(403);
                View::render('errors/status', ['title'=>'Access denied','code'=>403,'message'=>'You do not have permission to perform this action.']);
            }
        }
    }
}

