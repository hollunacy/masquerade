<?php
ob_start();
session_start();
include("../database/config.php");
include("../database/functions.php");
include("../database/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$message = '';

if (!isset($_SESSION['seller'])) {
  header('location: account-authentication.php');
  exit;
} else {
  $seller_id = $_SESSION['seller']['seller_id'];
}

// $title = "Дашборд";
?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    <?php if ($title != '') echo $title . ' | '; ?>
    МАСКАРАД Продажа
  </title>

  <meta name="description" content="" />

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

  <!-- Icons. Uncomment required icon fonts -->
  <link rel="stylesheet" href="../assets/vendor/fonts/boxicons.css" />

  <!-- Core CSS -->
  <link rel="stylesheet" href="../assets/vendor/css/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="../assets/css/demo.css" />

  <!-- Vendors CSS -->
  <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

  <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />

  <!-- Page CSS -->

  <!-- Helpers -->
  <script src="../assets/vendor/js/helpers.js"></script>

  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="../assets/js/config.js"></script>
</head>

<body>

  <!-- Layout wrapper -->
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <!-- Menu -->
      <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
        <div class="app-brand demo">
          <a href="index.php" class="app-brand-link">
            <span class="app-brand-logo demo">
              <img class="card-img" src="../assets/img/favicon/android-chrome-512x512.png" alt="МАСКАРАД" width="25" height="25">
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2">МАСКАРАД</span>
          </a>

          <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
          </a>
        </div>

        <div class="menu-inner-shadow"></div>

        <ul class="menu-inner py-1">
          <!-- Dashboard -->
          <li class="menu-item active">
            <a href="index.php" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-dashboard"></i>
              <div data-i18n="Analytics">Дашборд</div>
            </a>
          </li>

          <!-- Sale -->
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Продажа</span>
          </li>
          <li class="menu-item">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bx-collection"></i>
              <div data-i18n="Products">Товары</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="products.php?filter=instock" class="menu-link">
                  <div data-i18n="In Stock">В наличии</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="products.php?filter=outofstock" class="menu-link">
                  <div data-i18n="Out Of Stock">Распроданные</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="products.php?filter=draft" class="menu-link">
                  <div data-i18n="Draft">Черновики</div>
                </a>
              </li>
            </ul>
          </li>
          <li class="menu-item">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bx-box"></i>
              <div data-i18n="Orders">Заказы</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item">
                <a href="orders.php?filter=inprogress" class="menu-link">
                  <div data-i18n="In Progress">В процессе</div>
                </a>
              </li>
              <li class="menu-item">
                <a href="orders.php?filter=completed" class="menu-link">
                  <div data-i18n="Completed">Завершенные</div>
                </a>
              </li>
            </ul>
          </li>

          <!-- Profile -->
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Личный кабинет</span>
          </li>
          <li class="menu-item">
            <a href="account-profile.php" class="menu-link">
              <i class="menu-icon tf-icons bx bx-user-pin me-2"></i>
              <div data-i18n="Settings">Профиль</div>
            </a>
          </li>
          <li class="menu-item">
            <a href="account-authentication.php?type=logout" class="menu-link">
              <i class="menu-icon tf-icons bx bx-power-off me-2"></i>
              <div data-i18n="Log Out">Выйти</div>
            </a>
          </li>
        </ul>
      </aside>
      <!-- / Menu -->

      <!-- Layout container -->
      <div class="layout-page">

        <!-- Content wrapper -->
        <div class="content-wrapper">

          <!-- Content -->
          <div class="container-xxl flex-grow-1 container-p-y">

            <div class="layout-menu-toggle navbar-nav me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <div class="row">
              <div class="col-md-12 col-lg-3 mb-3">
                <div class="card">
                  <div class="card-body">
                    <a href="orders.php?filter=completed">
                      <div class="card-title d-flex align-items-start justify-content-between text-success">
                        <i class="menu-icon tf-icons bx bxs-wallet"></i>
                      </div>
                    </a>
                    <span>Распродажа</span>
                    <h3 class="card-title mb-2">₽
                      <?php
                      $statement = $pdo->prepare("SELECT sum(option_sum) FROM order_products
                                                  JOIN product_options ON product_options.option_id=order_products.option_id
                                                  JOIN products ON products.product_id=product_options.product_id
                                                  WHERE seller_id=? AND option_status=?");
                      $statement->execute(array(
                        $seller_id,
                        4
                      ));
                      $result = $statement->fetch(PDO::FETCH_ASSOC);
                      echo $result['sum(option_sum)']; ?>
                    </h3>
                  </div>
                </div>
              </div>

              <div class="col-md-12 col-lg-3 mb-3">
                <div class="card">
                  <div class="card-body">
                    <a href="orders.php?filter=inprogress">
                      <div class="card-title d-flex align-items-start justify-content-between text-warning">
                        <i class="menu-icon tf-icons bx bxs-basket"></i>
                      </div>
                    </a>
                    <span>Количество обрабатываемых товаров</span>
                    <h3 class="card-title mb-2">
                      <?php
                      $statement = $pdo->prepare("SELECT count(*) FROM order_products
                                                  JOIN product_options ON product_options.option_id=order_products.option_id
                                                  JOIN products ON products.product_id=product_options.product_id
                                                  WHERE seller_id=? AND option_status BETWEEN ? AND ?");
                      $statement->execute(array(
                        $seller_id,
                        2,
                        3
                      ));
                      $result = $statement->fetch(PDO::FETCH_ASSOC);
                      echo $result['count(*)']; ?>
                    </h3>
                  </div>
                </div>
              </div>

              <div class="col-md-12 col-lg-3 mb-3">
                <div class="card">
                  <div class="card-body">
                    <a href="orders.php?filter=completed">
                      <div class="card-title d-flex align-items-start justify-content-between text-primary">
                        <i class="menu-icon tf-icons bx bxs-box"></i>
                      </div>
                    </a>
                    <span>Количество проданных товаров</span>
                    <h3 class="card-title mb-2">
                      <?php
                      $statement = $pdo->prepare("SELECT sum(option_amount) FROM order_products
                                                  JOIN product_options ON product_options.option_id=order_products.option_id
                                                  JOIN products ON products.product_id=product_options.product_id
                                                  WHERE seller_id=? AND option_status=?");
                      $statement->execute(array(
                        $seller_id,
                        4
                      ));
                      $result = $statement->fetch(PDO::FETCH_ASSOC);
                      echo $result['sum(option_amount)']; ?>
                    </h3>
                  </div>
                </div>
              </div>

              <div class="col-md-12 col-lg-3 mb-3">
                <div class="card">
                  <div class="card-body">
                    <a href="products.php?filter=instock">
                      <div class="card-title d-flex align-items-start justify-content-between text-info">
                        <i class="menu-icon tf-icons bx bxs-collection"></i>
                      </div>
                    </a>
                    <span>Количество активных товаров</span>
                    <h3 class="card-title mb-2">
                      <?php
                      $statement = $pdo->prepare("SELECT sum(products.amount_available) FROM products
                                                  WHERE seller_id=? AND product_active=?");
                      $statement->execute(array(
                        $seller_id,
                        1
                      ));
                      $result = $statement->fetch(PDO::FETCH_ASSOC);
                      echo $result['sum(products.amount_available)']; ?>
                    </h3>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- / Content -->

        </div>
        <!-- Content wrapper -->
      </div>
      <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>
  <!-- / Layout wrapper -->

  <!-- Core JS -->
  <!-- build:js assets/vendor/js/core.js -->
  <script src="../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../assets/vendor/libs/popper/popper.js"></script>
  <script src="../assets/vendor/js/bootstrap.js"></script>
  <script src="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

  <script src="../assets/vendor/js/menu.js"></script>
  <!-- endbuild -->

  <!-- Vendors JS -->
  <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

  <!-- Main JS -->
  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->
  <script src="../assets/js/dashboards-analytics.js"></script>

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>