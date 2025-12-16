<?php

namespace App\Controllers;

class AuthController
{

  //TODO: Implement proper authentication logic later.
  public function index(): void
  {
    header('Location: /upload');
    exit;
  }
}