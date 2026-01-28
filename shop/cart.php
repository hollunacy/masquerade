<?php
ob_start();
session_start();
include("../database/config.php");
include("../database/functions.php");
include("../database/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$message = '';

if (isset($_SESSION['buyer'])) {
  $buyer_id = $_SESSION['buyer']['buyer_id'];
} else {
  header('location: account-authentication.php');
  exit;
}

$statement = $pdo->prepare("SELECT * FROM orders WHERE buyer_id='$buyer_id' AND status_id=0");
$statement->execute();
$cart = $statement->fetch(PDO::FETCH_ASSOC);

$statement = $pdo->prepare("SELECT * FROM order_products
                            JOIN product_options ON product_options.option_id=order_products.option_id 
                              JOIN colors ON colors.color_id=product_options.color_id 
                              JOIN sizes ON sizes.size_id=product_options.size_id                           
                            JOIN products ON products.product_id=product_options.product_id
                            JOIN orders ON orders.order_id=order_products.order_id
                            WHERE buyer_id=? AND status_id=?
                            ORDER BY time_added DESC");
$statement->execute(array(
  $buyer_id,
  0
));
$options = $statement->fetchAll(PDO::FETCH_ASSOC);

$title = 'Корзина';

$csrf->echoInputField();

if (isset($_GET['action'])) {
  $statement = $pdo->prepare("SELECT * FROM order_products
                              JOIN product_options ON product_options.option_id=order_products.option_id
                              WHERE order_id=? AND order_products.option_id=?");
  $statement->execute(array(
    $cart['order_id'],
    $_GET['option_id']
  ));
  $option = $statement->fetch(PDO::FETCH_ASSOC);

  if ($_GET['action'] == "select") {
    $select = 0;
    if ($option['option_select'] == 0) $select = 1;
    $statement = $pdo->prepare("UPDATE order_products SET option_select=? WHERE order_id=? AND option_id=?");
    $statement->execute(array(
      $select,
      $cart['order_id'],
      $_GET['option_id']
    ));
  }

  if ($_GET['action'] == "increase") {
    $quantity = $option['option_amount'] + 1;
  }

  if ($_GET['action'] == "decrease") {
    $quantity = $option['option_amount'] - 1;
  }

  if ($quantity) {
    $statement = $pdo->prepare("UPDATE order_products SET option_amount=?, option_sum=? WHERE order_id=? AND option_id=?");
    $statement->execute(array(
      $quantity,
      $option['option_price'] * $quantity,
      $cart['order_id'],
      $_GET['option_id']
    ));
  }

  if ($_GET['action'] == "remove") {
    $statement = $pdo->prepare("DELETE FROM order_products WHERE order_id=? AND option_id=?");
    $statement->execute(array(
      $cart['order_id'],
      $_GET['option_id']
    ));
  }

  $statement = $pdo->prepare("SELECT SUM(option_sum) FROM order_products WHERE order_id=? AND option_select=?");
  $statement->execute(array(
    $cart['order_id'],
    1
  ));
  $sum = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("UPDATE orders SET order_sum=? WHERE order_id=?");
  $statement->execute(array(
    $sum['SUM(option_sum)'],
    $cart['order_id']
  ));

  header("location: cart.php");
}

?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    <?php if ($title != '') echo $title . ' | '; ?>
    МАСКАРАД
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
          <a href="products.php" class="app-brand-link">
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
          <!-- Products -->
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Товары</span>
          </li>
          <li class="menu-item <?php if ($gender_id) echo 'active open'; ?>">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-face"></i>
              <div data-i18n="Genders">Пол</div>
            </a>
            <ul class="menu-sub">
              <?php
              $statement = $pdo->prepare("SELECT * FROM genders ORDER BY gender_id");
              $statement->execute();
              $result = $statement->fetchAll(PDO::FETCH_ASSOC);
              foreach ($result as $row) { ?>
                <li class="menu-item <?php if ($gender_id == $row['gender_id']) echo 'active'; ?>">
                  <a href="products.php?gender_id=<?php echo $row['gender_id']; ?>" class="menu-link">
                    <div data-i18n="Gender"><?php echo $row['gender_title']; ?></div>
                  </a>
                </li>
              <?php } ?>
            </ul>
          </li>
          <li class="menu-item <?php if ($category_id) echo 'active open'; ?>">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-category-alt"></i>
              <div data-i18n="Categories">Категория</div>
            </a>
            <ul class="menu-sub">
              <?php
              $statement = $pdo->prepare("SELECT * FROM categories ORDER BY category_amount DESC, category_name");
              $statement->execute();
              $result = $statement->fetchAll(PDO::FETCH_ASSOC);
              foreach ($result as $row) { ?>
                <li class="menu-item <?php if ($category_id == $row['category_id']) echo 'active'; ?>">
                  <a href="products.php?category_id=<?php echo $row['category_id']; ?>" class="menu-link">
                    <div data-i18n="Category"><?php echo $row['category_name']; ?></div>
                  </a>
                </li>
              <?php } ?>
            </ul>
          </li>
          <li class="menu-item <?php if ($style_id) echo 'active open'; ?>">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-palette"></i>
              <div data-i18n="Styles">Стиль</div>
            </a>
            <ul class="menu-sub">
              <?php
              $statement = $pdo->prepare("SELECT * FROM styles ORDER BY style_amount DESC, style_name");
              $statement->execute();
              $result = $statement->fetchAll(PDO::FETCH_ASSOC);
              foreach ($result as $row) { ?>
                <li class="menu-item <?php if ($style_id == $row['style_id']) echo 'active'; ?>">
                  <a href="products.php?style_id=<?php echo $row['style_id']; ?>" class="menu-link">
                    <div data-i18n="Style"><?php echo $row['style_name']; ?></div>
                  </a>
                </li>
              <?php } ?>
            </ul>
          </li>
          <li class="menu-item <?php if ($season_id) echo 'active open'; ?>">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bxs-cloud"></i>
              <div data-i18n="Seasons">Сезон</div>
            </a>
            <ul class="menu-sub">
              <?php
              $statement = $pdo->prepare("SELECT * FROM seasons ORDER BY season_id");
              $statement->execute();
              $result = $statement->fetchAll(PDO::FETCH_ASSOC);
              foreach ($result as $row) { ?>
                <li class="menu-item <?php if ($season_id == $row['season_id']) echo 'active'; ?>">
                  <a href="products.php?season_id=<?php echo $row['season_id']; ?>" class="menu-link">
                    <div data-i18n="Season"><?php echo $row['season_name']; ?></div>
                  </a>
                </li>
              <?php } ?>
            </ul>
          </li>

          <!-- Order -->
          <?php if ($buyer_id) { ?>
            <li class="menu-header small text-uppercase">
              <span class="menu-header-text">Заказы</span>
            </li>
            <li class="menu-item <?php if ($favorite) echo 'active'; ?>">
              <a href="products.php?favorite=1" class="menu-link">
                <i class="menu-icon tf-icons bx bx-heart"></i>
                <div data-i18n="Favorites">Избранное</div>
              </a>
            </li>
            <li class="menu-item active">
              <a href="cart.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-basket"></i>
                <div data-i18n="Cart">Корзина</div>
              </a>
            </li>
            <li class="menu-item">
              <a href="orders.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-box"></i>
                <div data-i18n="Orders">Заказы</div>
              </a>
            </li>
          <?php } ?>

          <!-- Profile -->
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Личный кабинет</span>
          </li>
          <?php if (!$buyer_id) { ?>
            <li class="menu-item">
              <a href="account-authentication.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-log-in-circle"></i>
                <div data-i18n="Log In">Войти</div>
              </a>
            </li>
          <?php } else { ?>
            <li class="menu-item">
              <a href="account-profile.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user-pin me-2"></i>
                <div data-i18n="Settings">Профиль</div>
              </a>
            </li>

            <li class="menu-item">
              <a href="account-address.php" class="menu-link">
                <i class="menu-icon tf-icons bx bx-map me-2"></i>
                <div data-i18n="Settings">Адреса</div>
              </a>
            </li>

            <li class="menu-item">
              <a href="account-authentication.php?type=logout" class="menu-link">
                <i class="menu-icon tf-icons bx bx-log-out-circle"></i>
                <div data-i18n="Log Out">Выйти</div>
              </a>
            </li>
          <?php } ?>
          <li class="menu-item">
            <a href="../seller/account-authentication.php" class="menu-link">
              <i class="menu-icon tf-icons bx bx-trending-up"></i>
              <div data-i18n="Seller">Стать продавцом</div>
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

            <h4 class="fw-bold py-3 mb-4">
              Корзина
              <?php
              $statement = $pdo->prepare("SELECT SUM(option_amount) FROM order_products WHERE order_id=? AND option_select=?");
              $statement->execute(array(
                $cart['order_id'],
                1
              ));
              $total = $statement->fetch(PDO::FETCH_ASSOC);
              $total = $total['SUM(option_amount)'];
              if ($total != 0) { ?>
                <span class="badge badge-center bg-label-success">
                  <?php echo $total; ?>
                </span>
              <?php } ?>
            </h4>

            <?php if (!$options) { ?>
              <div class="col-md-6 col-lg-4">
                <div class="card text-center mb-3">
                  <div class="card-body">
                    <h5 class="card-title">Корзина пустая</h5>
                    <!-- <p class="card-text"></p> -->
                    <a class="btn btn-primary" href="products.php">Искать товары</a>
                  </div>
                </div>
              </div>

            <?php } else { ?>
              <form class="mb-3" method="POST" enctype="multipart/form-data">
              <?php $csrf->echoInputField(); ?>
                <div class="card mb-4">
                  <div class="card-body">
                    <div class="table-responsive text-nowrap">
                      <table class="table table-hover">
                        <tbody class="table-border-bottom-0">
                          <?php foreach ($options as $option) { ?>
                            <tr>
                              <td>
                                <input class="form-check-input" type="checkbox" onchange="if(this.checked) document.location.href='cart.php?option_id=<?php echo $option['option_id']; ?>&action=select';" <?php if ($option['option_select'] == 1) echo 'checked'; ?> />
                              </td>

                              <td>
                                <a href="products.php?product_id=<?php echo $option['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                                  <img src="../uploads/products/<?php echo $option['product_image']; ?>" alt="<?php echo $option['product_title']; ?>" class="d-block rounded" height="100" id="product_image" />
                                </a>
                              </td>

                              <td>
                                <a href="products.php?product_id=<?php echo $option['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                                  <strong><?php echo $option['product_title']; ?></strong>
                                </a>
                              </td>

                              <td>
                                <?php echo $option['color_name']; ?>
                              </td>

                              <td>
                                <?php echo $option['size_letter'];
                                if ($option['size_number']) echo " (" . $option['size_number'] . ")"; ?>
                              </td>

                              <td>
                                <span class="text-success">
                                  <strong>
                                    ₽ <?php echo $option['option_sum']; ?>
                                  </strong>
                                </span>
                              </td>

                              <td>
                                <?php if ($option['option_amount'] == 1) { ?>
                                  <button class="btn btn-icon btn-outline-secondary" disabled>
                                    <span class="tf-icons bx bx-minus"></span>
                                  </button>
                                <?php } else { ?>
                                  <a class="btn btn-icon btn-outline-primary" href="cart.php?option_id=<?php echo $option['option_id']; ?>&action=decrease">
                                    <span class="tf-icons bx bx-minus"></span>
                                  </a>
                                <?php } ?>

                                <a class="btn btn-icon">
                                  <?php echo " " . $option['option_amount'] . " "; ?>
                                </a>

                                <?php
                                $statement = $pdo->prepare("SELECT * FROM product_options WHERE option_id=?");
                                $statement->execute(array($option['option_id']));
                                $max = $statement->fetch(PDO::FETCH_ASSOC);
                                if ($option['option_amount'] >= $max['amount_available']) { ?>
                                  <button class="btn btn-icon btn-outline-secondary" disabled>
                                    <span class="tf-icons bx bx-plus"></span>
                                  </button>
                                <?php } else { ?>
                                  <a class="btn btn-icon btn-outline-primary" href="cart.php?option_id=<?php echo $option['option_id']; ?>&action=increase">
                                    <span class="tf-icons bx bx-plus"></span>
                                  </a>
                                <?php } ?>
                              </td>

                              <td>
                                <a class="btn btn-icon btn-outline-danger" href="cart.php?option_id=<?php echo $option['option_id']; ?>&action=remove">
                                  <span class="tf-icons bx bx-trash"></span>
                                </a>
                              </td>

                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <?php if ($total != 0) { ?>
                  <div class="demo-inline-spacing">
                    <button type="button" class="btn rounded-pill btn-success">
                      ₽ <?php echo $option['order_sum']; ?>
                    </button>

                    <a class="btn btn-outline-primary me-2" name="order-checkout" href="checkout.php">К офрмлению</a>
                  </div>
                <?php } ?>

              </form>
            <?php } ?>
          </div>
          <!-- / Content -->

          <div class="content-backdrop fade"></div>
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

  <!-- Main JS -->
  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>