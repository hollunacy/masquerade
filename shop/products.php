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
}

$product_id = $_GET["product_id"];
if ($product_id) {
  $statement = $pdo->prepare("SELECT * FROM products WHERE product_id = '$product_id'");
  $statement->execute();
  $product = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("SELECT * FROM product_options 
                              JOIN colors ON colors.color_id=product_options.color_id
                              JOIN sizes ON sizes.size_id=product_options.size_id
                              WHERE product_id='$product_id'
                              ORDER BY color_name, sizes.size_id");
  $statement->execute();
  $options = $statement->fetchAll(PDO::FETCH_ASSOC);

  $option_id = $_GET["option_id"];
  if (!$option_id) {
    foreach ($options as $row) {
      if ($row['amount_available'] != 0) {
        $option_id = $row['option_id'];
      }
    }
  }

  $statement = $pdo->prepare("SELECT * FROM product_options 
                              JOIN colors ON colors.color_id=product_options.color_id
                              JOIN sizes ON sizes.size_id=product_options.size_id
                              WHERE option_id='$option_id'");
  $statement->execute();
  $option = $statement->fetch(PDO::FETCH_ASSOC);
} else {
  $seller_id = $_GET["seller_id"];

  $gender_id = $_GET["gender_id"];
  $category_id = $_GET["category_id"];
  $style_id = $_GET["style_id"];
  $season_id = $_GET["season_id"];
  $material_id = $_GET["material_id"];

  $favorite = $_GET["favorite"];
  $search_text = $_GET["search"];

  if ($favorite == 1) {
    $statement = $pdo->prepare("SELECT product_options.option_id FROM product_options 
                                JOIN products ON products.product_id=product_options.product_id
                                JOIN favorites ON favorites.option_id=product_options.option_id
                                WHERE buyer_id='$buyer_id' AND product_active=1
                                ORDER BY favorite_time DESC");
    $statement->execute();
    $options = $statement->fetchAll(PDO::FETCH_ASSOC);
  } else {
    if ($search_text) {
      $search_query = "SELECT * FROM products WHERE product_active = 1";
      $search_query .= " AND product_title LIKE '%$search_text%'";
      $statement = $pdo->prepare($search_query . " ORDER BY amount_selled DESC, time_created DESC");
    } else if ($seller_id) {
      $statement = $pdo->prepare("SELECT * FROM user_sellers WHERE seller_id = '$seller_id'");
      $statement->execute();
      $seller = $statement->fetch(PDO::FETCH_ASSOC);

      $statement = $pdo->prepare("SELECT * FROM products WHERE product_active = 1 AND seller_id = '$seller_id' ORDER BY amount_selled DESC, time_created DESC");
    } else if ($gender_id) {
      $statement = $pdo->prepare("SELECT * FROM genders WHERE gender_id = '$gender_id'");
      $statement->execute();
      $gender = $statement->fetch(PDO::FETCH_ASSOC);

      $statement = $pdo->prepare("SELECT * FROM products JOIN product_genders ON product_genders.product_id=products.product_id WHERE product_active = 1 AND gender_id='$gender_id' ORDER BY amount_selled DESC, time_created DESC");
    } else if ($category_id) {
      $statement = $pdo->prepare("SELECT * FROM categories WHERE category_id = '$category_id'");
      $statement->execute();
      $category = $statement->fetch(PDO::FETCH_ASSOC);
      $statement = $pdo->prepare("SELECT * FROM products WHERE product_active = 1 AND category_id = '$category_id' ORDER BY amount_selled DESC, time_created DESC");
    } else if ($style_id) {
      $statement = $pdo->prepare("SELECT * FROM styles WHERE style_id = '$style_id'");
      $statement->execute();
      $style = $statement->fetch(PDO::FETCH_ASSOC);
      $statement = $pdo->prepare("SELECT * FROM products JOIN product_styles ON product_styles.product_id=products.product_id WHERE product_active = 1 AND style_id='$style_id' ORDER BY amount_selled DESC, time_created DESC");
    } else if ($season_id) {
      $statement = $pdo->prepare("SELECT * FROM seasons WHERE season_id = '$season_id'");
      $statement->execute();
      $season = $statement->fetch(PDO::FETCH_ASSOC);
      $statement = $pdo->prepare("SELECT * FROM products JOIN product_seasons ON product_seasons.product_id=products.product_id WHERE product_active = 1 AND season_id='$season_id' ORDER BY amount_selled DESC, time_created DESC");
    } else if ($material_id) {
      $statement = $pdo->prepare("SELECT * FROM materials WHERE material_id = '$material_id'");
      $statement->execute();
      $material = $statement->fetch(PDO::FETCH_ASSOC);
      $statement = $pdo->prepare("SELECT * FROM products JOIN product_materials ON product_materials.product_id=products.product_id WHERE product_active = 1 AND material_id='$material_id' ORDER BY amount_selled DESC, time_created DESC");
    } else {
      $statement = $pdo->prepare("SELECT * FROM products WHERE product_active = 1");
    }
    $statement->execute();
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);
  }
}

$title = '';
if ($product_id) $title = $product['product_title'] . ' ' . $option['color_name'] . ' ' . $option['size_letter'];
else if ($search_text) $title = $search_text;
else if ($favorite) $title = 'Избранное';
else if ($seller_id) $title = $seller['seller_brandname'];
else if ($gender_id) $title = $gender['gender_title'];
else if ($category_id) $title = $category['category_name'];
else if ($style_id) $title = $style['style_name'];
else if ($season_id) $title = $season['season_name'];
else if ($material_id) $title = $material['material_name'];

if (isset($_POST['search'])) {
  header("location: products.php?search=" . $_POST['search_text']);
}

if (isset($_POST['favorite-add'])) {
  if (!isset($_SESSION['buyer'])) {
    header('location: account-authentication.php');
    exit;
  } else {
    $statement = $pdo->prepare("INSERT INTO favorites (buyer_id, option_id, favorite_time) VALUES (?,?,?)");
    $statement->execute(array(
      $buyer_id,
      $option_id,
      date('Y-m-d h:i:s')
    ));
  }
}

if (isset($_POST['favorite-remove'])) {
  $statement = $pdo->prepare("DELETE FROM favorites WHERE buyer_id='$buyer_id' AND option_id='$option_id'");
  $statement->execute();
}

if (isset($_POST['cart-add'])) {
  if (!isset($_SESSION['buyer'])) {
    header('location: account-authentication.php');
    exit;
  } else {
    $statement = $pdo->prepare("SELECT * FROM orders WHERE buyer_id='$buyer_id' AND status_id=0");
    $statement->execute();
    if ($statement->rowCount() == 0) {
      $statement = $pdo->prepare("INSERT INTO orders (buyer_id, status_id) VALUE (?,?)");
      $statement->execute(array(
        $buyer_id,
        0
      ));
    }

    $statement = $pdo->prepare("SELECT * FROM orders WHERE buyer_id='$buyer_id' AND status_id=0");
    $statement->execute();
    $order = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("INSERT INTO order_products (order_id, option_id, option_amount, option_select, option_sum, time_added)
                              VALUE (?,?,?,?,?,?)");
    $statement->execute(array(
      $order['order_id'],
      $option_id,
      1,
      1,
      $option['option_price'],
      date('Y-m-d h:i:s')
    ));

    $statement = $pdo->prepare("SELECT SUM(option_sum) FROM order_products WHERE order_id=? AND option_select=?");
    $statement->execute(array(
      $order['order_id'],
      1
    ));
    $sum = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE orders SET order_sum=? WHERE order_id=?");
    $statement->execute(array(
      $sum['SUM(option_sum)'],
      $order['order_id']
    ));
  }
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
            <li class="menu-item">
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

        <!-- Navbar -->
        <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
          <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
              <i class="bx bx-menu bx-sm"></i>
            </a>
          </div>

          <form method="POST" enctype="multipart/form-data">
            <?php $csrf->echoInputField(); ?>
            <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
              <!-- Search -->
              <div class="navbar-nav align-items-center">
                <div class="nav-item d-flex align-items-center">
                  <button type="submit" class="btn btn-icon btn-outline-secondary" name="search">
                    <i class="bx bx-search fs-4 lh-0"></i>
                  </button>

                  <input type="text" class="form-control border-0 shadow-none" placeholder="Искать товары" aria-label="Искать товары" name="search_text" value="<?php if ($search_text) echo $search_text; ?>" />
                </div>
              </div>
              <!-- /Search -->
            </div>
          </form>
        </nav>
        <!-- / Navbar -->

        <!-- Content wrapper -->
        <div class="content-wrapper">

          <!-- Content -->
          <div class="container-xxl flex-grow-1 container-p-y">

            <?php if ($product_id) { ?>
              <form class="mb-3" method="POST" enctype="multipart/form-data">
                <?php $csrf->echoInputField(); ?>

                <div class="row mb-2">
                  <div class="col-md">
                    <div class="card mb-3">
                      <div class="row g-0">
                        <div class="col-md-3">
                          <img class="card-img card-img-left" src="../uploads/products/<?php echo $product['product_image']; ?>" alt="<?php echo $product['product_title']; ?>" />
                        </div>
                        <div class="col-md-9">
                          <div class="card-body">
                            <p class="card-text">
                              <?php
                              $statement = $pdo->prepare("SELECT * FROM categories WHERE category_id=" . $product['category_id'] . "");
                              $statement->execute();
                              $result = $statement->fetch(PDO::FETCH_ASSOC);
                              ?>
                              <a href="products.php?category_id=<?php echo $result['category_id']; ?>">
                                <?php echo $result['category_name']; ?>
                              </a>
                            </p>

                            <h4 class="fw-bold">
                              <?php echo $product['product_title']; ?>
                            </h4>

                            <p class="card-text">Продавец:
                              <?php
                              $statement = $pdo->prepare("SELECT * FROM user_sellers WHERE seller_id=" . $product['seller_id'] . "");
                              $statement->execute();
                              $result = $statement->fetch(PDO::FETCH_ASSOC);
                              ?>
                              <a href="products.php?seller_id=<?php echo $result['seller_id']; ?>">
                                <?php echo $result['seller_brandname']; ?>
                              </a>
                            </p>

                            <?php if ($product['product_rate']) { ?>
                              <p class="card-text">
                                <i class='bx bxs-star'></i>
                                <?php echo $product['product_rate']; ?>
                                <i class='bx bxs-chat'></i>
                                <?php echo $product['product_rate_count']; ?> отз.
                              </p>
                            <?php } ?>

                            <p class="card-text">
                              <?php echo $product['product_description']; ?>
                            </p>

                            <ul class="nav nav-pills flex-column flex-md-row">
                              <?php foreach ($options as $row) { ?>
                                <li class="nav-item">
                                  <a class="nav-link <?php
                                                      if ($row['option_id'] == $option_id) echo 'active';
                                                      ?>" href="products.php?product_id=<?php echo $product_id; ?>&option_id=<?php echo $row['option_id']; ?>">
                                    <?php echo $row['color_name'] . ' - ' . $row['size_letter']; ?>
                                  </a>
                                </li>
                              <?php } ?>
                            </ul>

                            <div class="demo-inline-spacing">

                              <?php if ($option['amount_available'] != 0) { ?>
                                <h4 class="fw-bold">
                                  ₽ <?php echo $option['option_price']; ?>
                                </h4>

                                <?php
                                $statement = $pdo->prepare("SELECT * FROM order_products
                                                            JOIN orders ON orders.order_id=order_products.order_id
                                                            WHERE buyer_id=? AND status_id=? AND option_id=?");
                                $statement->execute(array(
                                  $buyer_id,
                                  0,
                                  $option_id
                                ));

                                if (!$buyer_id || $statement->rowCount() == 0) { ?>
                                  <button type="submit" class="btn btn-primary me-2" name="cart-add">Добавить в корзину</button>
                                <?php } else { ?>
                                  <a class="btn btn-success me-2" href="cart.php">
                                    В корзине
                                  </a>
                                <?php }
                              } else { ?>
                                <button type="button" class="btn btn-dark me-2" name="">Распродано</button>
                              <?php } ?>

                              <?php
                              $statement = $pdo->prepare("SELECT * FROM favorites WHERE option_id = '$option_id'");
                              $statement->execute();
                              if ($statement->rowCount() == 0) {
                                if ($option['amount_available'] != 0) { ?>
                                  <button type="submit" class="btn btn-icon btn-outline-secondary" name="favorite-add">
                                    <span class="tf-icons bx bx-heart"></span>
                                  </button>
                                <?php }
                              } else { ?>
                                <button type="submit" class="btn btn-icon btn-danger" name="favorite-remove">
                                  <span class="tf-icons bx bx-heart"></span>
                                </button>
                              <?php } ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card mb-4">
                  <h5 class="card-header">Характеристики</h5>
                  <div class="card-body">
                    <ul class="list-group">
                      <li class="list-group-item align-items-center">
                        <i class="bx bx-face me-2"></i>
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM genders JOIN product_genders ON product_genders.gender_id=genders.gender_id WHERE product_id='$product_id'");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        $i = 0;
                        foreach ($result as $row) {
                          if ($i > 0) echo ', ';
                          echo '<a href="products.php?gender_id=' .  $row['gender_id'] . '">' . $row['gender_title'] . '</a>';
                          $i++;
                        } ?>
                      </li>
                      <li class="list-group-item align-items-center">
                        <i class="bx bx-paint me-2"></i>
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM materials JOIN product_materials ON product_materials.material_id=materials.material_id WHERE product_id='$product_id'");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        $i = 0;
                        foreach ($result as $row) {
                          if ($i > 0) echo ', ';
                          echo '<a href="products.php?material_id=' .  $row['material_id'] . '">' . $row['material_name'] . '</a>';
                          $i++;
                        } ?>
                      </li>
                      <li class="list-group-item align-items-center">
                        <i class="bx bx-palette me-2"></i>
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM styles JOIN product_styles ON product_styles.style_id=styles.style_id WHERE product_id='$product_id'");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        $i = 0;
                        foreach ($result as $row) {
                          if ($i > 0) echo ', ';
                          echo '<a href="products.php?style_id=' .  $row['style_id'] . '">' . $row['style_name'] . '</a>';
                          $i++;
                        } ?>
                      </li>
                      <li class="list-group-item align-items-center">
                        <i class="bx bx-cloud me-2"></i>
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM seasons JOIN product_seasons ON product_seasons.season_id=seasons.season_id WHERE product_id='$product_id'");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        $i = 0;
                        foreach ($result as $row) {
                          if ($i > 0) echo ', ';
                          echo '<a href="products.php?season_id=' .  $row['season_id'] . '">' . $row['season_name'] . '</a>';
                          $i++;
                        } ?>
                      </li>
                    </ul>
                  </div>
                </div>

                <?php if ($product['product_rate']) { ?>
                  <div class="row">
                    <div class="nav-align-top mb-4">
                      <ul class="nav nav-pills mb-3" role="tablist">

                        <li class="nav-item">
                          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-home" aria-controls="navs-pills-top-home" aria-selected="true">
                            Все отзывы
                            <?php echo '<span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-primary">' . $product['product_rate_count'] . '</span>'; ?>
                          </button>
                        </li>
                        <li class="nav-item">
                          <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-profile" aria-controls="navs-pills-top-profile" aria-selected="false">
                            Этот вариант товара
                            <?php echo '<span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-primary">' . $option['option_rate_count'] . '</span>'; ?>
                          </button>
                        </li>
                      </ul>

                      <div class="tab-content">
                        <div class="tab-pane fade show active" id="navs-pills-top-home" role="tabpanel">
                          <?php
                          $statement = $pdo->prepare("SELECT * FROM order_products 
                                                      JOIN product_options ON product_options.option_id=order_products.option_id
                                                        JOIN colors ON colors.color_id=product_options.color_id
                                                        JOIN sizes ON sizes.size_id=product_options.size_id
                                                      JOIN products ON products.product_id=product_options.product_id
                                                      WHERE products.product_id=? AND option_rate IS NOT NULL
                                                      ORDER BY option_rate DESC");
                          $statement->execute(array(
                            $product_id
                          ));
                          $reviews = $statement->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($reviews as $review) { ?>
                            <div class="col-12">
                              <div class="row g-0">
                                <div class="col-md-2">
                                  <?php echo substr("<i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i>", - ($review['option_rate'] * 27)); ?>
                                </div>
                                <div class="col-md-2">
                                  <a href="products.php?product_id=<?php echo $product_id; ?>&option_id=<?php echo $review['option_id']; ?>">
                                    <?php echo $review['color_name'] . ' - ' . $review['size_letter']; ?>
                                  </a>
                                </div>
                                <div class="col-md-8">
                                  <?php echo $review['option_review']; ?>
                                </div>
                              </div>
                            </div>
                          <?php } ?>
                        </div>

                        <div class="tab-pane fade" id="navs-pills-top-profile" role="tabpanel">
                          <?php
                          $statement = $pdo->prepare("SELECT * FROM order_products 
                                                      JOIN product_options ON product_options.option_id=order_products.option_id
                                                      WHERE product_options.option_id=? AND option_rate IS NOT NULL
                                                      ORDER BY option_rate DESC");
                          $statement->execute(array(
                            $option_id
                          ));
                          $reviews = $statement->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($reviews as $review) { ?>
                            <div class="col-12">
                              <div class="row g-0">
                                <div class="col-md-3">
                                  <?php echo substr("<i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i><i class='bx bxs-star'></i>", - ($review['option_rate'] * 27)); ?>
                                </div>
                                <div class="col-md-9">
                                  <?php echo $review['option_review']; ?>
                                </div>
                              </div>
                            </div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>
              </form>
            <?php } else { ?>

              <!-- Heading -->
              <?php if ($favorite) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  Избранное
                </h4>
              <?php } else if ($seller_id) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  <span class="text-muted fw-light">Продавец /</span>
                  <?php echo $seller['seller_brandname']; ?>
                </h4>
              <?php } else if ($gender_id) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  <span class="text-muted fw-light">Пол /</span>
                  <?php echo $gender['gender_title']; ?>
                </h4>
              <?php } else if ($category_id) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  <span class="text-muted fw-light">Категория /</span>
                  <?php echo $category['category_name']; ?>
                </h4>
              <?php } else if ($style_id) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  <span class="text-muted fw-light">Стиль /</span>
                  <?php echo $style['style_name']; ?>
                </h4>
              <?php } else if ($season_id) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  <span class="text-muted fw-light">Сезон /</span>
                  <?php echo $season['season_name']; ?>
                </h4>
              <?php } else if ($material_id) { ?>
                <h4 class="fw-bold py-3 mb-4">
                  <span class="text-muted fw-light">Материал /</span>
                  <?php echo $material['material_name']; ?>
                </h4>
              <?php } ?>
              <!-- / Heading -->

              <div class="row row-cols-1 row-cols-md-5 g-4 mb-4">

                <?php if ($favorite) {
                  foreach ($options as $option) {
                    $statement = $pdo->prepare("SELECT * FROM product_options
                                                JOIN colors ON colors.color_id=product_options.color_id
                                                JOIN sizes ON sizes.size_id=product_options.size_id
                                                WHERE option_id=" . $option['option_id']);
                    $statement->execute();
                    $option = $statement->fetch(PDO::FETCH_ASSOC);

                    $statement = $pdo->prepare("SELECT * FROM products WHERE product_id=" . $option['product_id']);
                    $statement->execute();
                    $product = $statement->fetch(PDO::FETCH_ASSOC); ?>
                    <div class="col">
                      <div class="card h-100">
                        <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                          <img class="card-img-top" src="../uploads/products/<?php echo $product['product_image']; ?>">
                        </a>

                        <div class="card-body">

                          <h5 class="card-title">
                            <strong>
                              <?php echo $product['product_title']; ?>
                            </strong>
                          </h5>

                          <p class="card-text">
                            <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                              <?php echo $option['color_name'] . ' - ' . $option['size_letter']; ?>
                            </a>
                          </p>

                          <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                            <?php if ($option['amount_available'] != 0) { ?>
                              <?php
                              $statement = $pdo->prepare("SELECT * FROM order_products
                                                            JOIN orders ON orders.order_id=order_products.order_id
                                                            WHERE buyer_id=" . $buyer_id . " AND status_id=0 AND option_id=" . $option['option_id']);
                              $statement->execute();
                              if ($statement->rowCount() != 0) { ?>
                                <a class="btn rounded-pill btn-success me-2" name="cart-remove" href="orders.php">
                                  ₽ <?php echo $option['option_price']; ?>
                                </a>
                              <?php } else { ?>
                                <button type="submit" class="btn rounded-pill btn-primary me-2" name="cart-add">
                                  ₽ <?php echo $option['option_price']; ?>
                                </button>
                              <?php }
                            } else { ?>
                              <button type="button" class="btn rounded-pill btn-dark me-2" name="">Распродано</button>
                            <?php } ?>
                          </a>
                          <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                            <?php if (!$favorite) { ?>
                              <button type="submit" class="btn rounded-pill btn-icon btn-outline-secondary" name="favorite-add">
                                <span class="tf-icons bx bx-heart"></span>
                              </button>
                            <?php } ?>
                          </a>
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php }
                } else {
                  foreach ($products as $product) {
                    $statement = $pdo->prepare("SELECT * FROM product_options
                                                WHERE product_id=" . $product['product_id'] . " 
                                                ORDER BY amount_available ASC, amount_selled ASC");
                    $statement->execute();
                    $options = $statement->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($options as $row) {
                      if ($row['amount_available'] != 0)  $option = $row;
                    } ?>
                    <div class="col">
                      <div class="card h-100">
                        <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                          <img class="card-img-top" src="../uploads/products/<?php echo $product['product_image']; ?>">
                        </a>

                        <div class="card-body">
                          <?php if (!$category_id) {
                            $statement = $pdo->prepare("SELECT * FROM categories WHERE category_id=" . $product['category_id'] . "");
                            $statement->execute();
                            $result = $statement->fetch(PDO::FETCH_ASSOC); ?>
                            <p class="card-text">
                              <a href="products.php?category_id=<?php echo $result['category_id']; ?>">
                                <?php echo $result['category_name']; ?>
                              </a>
                            </p>
                          <?php } ?>

                          <h5 class="card-title">
                            <strong>
                              <?php echo $product['product_title']; ?>
                            </strong>
                          </h5>

                          <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                            <?php if ($option['amount_available'] != 0) { ?>
                              <button type="submit" class="btn rounded-pill btn-primary me-2" name="cart-add">
                                ₽ <?php echo $option['option_price']; ?>
                              </button>
                            <?php } else { ?>
                              <button type="button" class="btn rounded-pill btn-dark me-2" name="">Распродано</button>
                            <?php } ?>
                          </a>
                        </div>
                      </div>
                    </div>
                <?php }
                } ?>
              </div>

              <?php if ($search_text) { ?>
                <h6 class="mb-3">
                  Найдено <?php echo count($products); ?> товар(ов)
                </h6>
              <?php } ?>

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
  <script src="../assets/vendor/libs/apex-charts/apexcharts.js"></script>

  <!-- Main JS -->
  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->
  <script src="../assets/js/dashboards-analytics.js"></script>

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>