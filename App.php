<?php
namespace Lyra\Crux;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class App {

  public static function run() {
    $request = Request::createFromGlobals();

    $input = $request->get('name', 'World');

    $response = new Response(sprintf('Hello %s', htmlspecialchars($input, ENT_QUOTES, 'UTF-8')));

    $response->send();
  }
}

