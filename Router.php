<?php

namespace Lyra\Crux;


class Router {
	
	
	
	/**     
	* Associative array of routes (routing table)     
	* @var array     
	*/
	protected $routes = array();
	protected $params = array();

	public function __construct()
	{
		$this->add('', array('controller' => 'home', 'action' => 'index', 'module' => 'lyra'));
		$this->add('admin/{controller}/{action}/?', array('namespace' => 'Admin'));
		$this->add('{controller}/?');
		$this->add('{controller}/{action}/?(.+)');
		$this->add('{controller}/{id:\d+}/{action}/?');
	}
	
	public function add($route, $params = array()){
		
		// Convert the route to a regular expression: escaape forward slashes
		$route = preg_replace('/\//', '\\/', $route);
		
		// Convert variables e.g. {controller} <\1> is referencing the (match capture group)
		// pleae refer to: http://php.net/manual/en/regexp.reference.back-references.php
        $route = preg_replace('/\{([a-z_]+)\}/', '(?P<\1>[a-z-_]+)', $route);

        // Convert variables with custom regular expressions e.g. {id:\d+}
        $route = preg_replace('/\{([a-z_]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        // Add start and end delimiters, and case insesitive flag
		$route = '/^' . $route . '$/i';
		
		$this->routes[$route] = $params;
		
	}
	
	
	public function getRoutes(){
		
		return $this->routes;
		
	}
	
	
	public function match($url){
		
		
		foreach($this->routes as $route => $params){
			
			if (preg_match($route, $url, $matches)){
				foreach ($matches as $key => $match){
					if (is_string($key)){
						$params[$key] = $match;
					}
				}

				$this->params = $params;
				return true;
				
			}
		}
		return false;
	}
	
	
	public function getParams(){
		
		return $this->params;
		
	}

	public function dispatch($url){

		$url = $this->sanitize($url);

		Helper::display($url);

		if ($this->match($url)){
			$controller = $this->params['controller'];
			$controller = $this->normalize($controller);
			$controller = $this->getNamespace() . $controller;

			if (class_exists($controller)){
				$controllerInstance = new $controller($this->params);
				$action = (array_key_exists('action', $this->params)) ? $this->params['action'] : 'index';
				$action = $this->toCamelCase($action);

				if (is_callable(array($controllerInstance, $action))){
					$controllerInstance->$action();
				} else {
					Helper::display("Method $action (in controller $controller) not found");
				}
			} else {
				Helper::display("Controller class $controller not found");
			}
		} else {
			Helper::display("No route found!");
		}

	}

	public function sanitize($url){
		Helper::display($url);
		if ($url != ''){
			$parts = explode('&', $url, 2);

			if (strpos($parts[0], '=') === false){
				$url  = $parts[0];
			}  else {
				$url = '';
			}
		}

		return $url;
	}

	public function normalize($string){
		return str_replace(' ', '', ucwords(str_replace(array('-','_'), ' ',$string)));
	}

	public function toCamelCase($string){
		return lcfirst($this->normalize($string));
	}

    /**
     * Get the namespace for the controller class. The namespace defined in the
     * route parameters is added if present.
     *
     * @return string The request URL
     */
    protected function getNamespace()
    {
        $namespace = 'App\Controllers\\';

		if (array_key_exists('module', $this->params)) {
			$namespace = 'App\Code\\' . ucwords($this->params['module']) . '\\Controllers\\';
			Helper::display($namespace);
			
        }

        if (array_key_exists('namespace', $this->params)) {
            $namespace .= $this->params['namespace'] . '\\';
        }



        return $namespace;
    }
	
}
