<?php

namespace App\Controllers;

use App\Repositories\ProductRepository;

/**
 * Handles product listing pages.
 */
class ProductController
{

  /**
   * Repository used to fetch product records.
   *
   * @var ProductRepository
   */
  private ProductRepository $products;

  /**
   * Set up the controller with a product repository instance.
   */
  public function __construct()
  {
    $this->products = new ProductRepository();
  }

  /**
   * Display a paginated list of products.
   *
   * @return void
   */
  public function index(): void {
    $perPage = 50;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page);

    $offset = ($page - 1) * $perPage;

    $products = $this->products->all($perPage, $offset);

    $hasPrev = $page > 1;
    $hasNext = count($products) === $perPage; 

    // variables used in the template the template:
    require __DIR__ . '/../views/products/index.php';
  }
}
