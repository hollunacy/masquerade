<?php
ob_start();
session_start();
include("../database/config.php");
include("../database/functions.php");
include("../database/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$message_error = '';

if (isset($_SESSION['buyer'])) {
  $buyer_id = $_SESSION['buyer']['buyer_id'];
} else {
  header('location: account-authentication.php');
  exit;
}

if (isset($_GET['order_id'])) {
  $order_id = $_GET['order_id'];
  $statement = $pdo->prepare("SELECT * FROM orders WHERE order_id=?");
  $statement->execute(array(
    $order_id
  ));
  $order = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("SELECT * FROM order_products
                              JOIN product_options ON product_options.option_id=order_products.option_id 
                                JOIN colors ON colors.color_id=product_options.color_id 
                                JOIN sizes ON sizes.size_id=product_options.size_id                           
                              JOIN products ON products.product_id=product_options.product_id
                              WHERE order_id=?
                              ORDER BY time_added DESC");
  $statement->execute(array(
    $order_id
  ));
  $options = $statement->fetchAll(PDO::FETCH_ASSOC);

  $title = 'Заказ № ' . $order_id;
} else {
  $statement = $pdo->prepare("SELECT * FROM orders WHERE buyer_id='$buyer_id' AND status_id<>0 ORDER BY order_time DESC");
  $statement->execute();
  $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
  $total = count($orders);

  $title = 'Заказы';
}

if ($_GET['action'] == "cancel") {
  // Update option's status
  $statement = $pdo->prepare("UPDATE order_products SET option_select=?, option_status=? WHERE order_id=? AND option_id=?");
  $statement->execute(array(
    0,
    5,
    $order_id,
    $_GET['option_id']
  ));

  // Update order's status
  $statement = $pdo->prepare("SELECT * FROM order_products WHERE order_id=? AND option_select=?");
  $statement->execute(array(
    $order_id,
    1
  ));
  $options = $statement->fetchAll(PDO::FETCH_ASSOC);
  if (count($options) == 0) {
    $statement = $pdo->prepare("UPDATE orders SET status_id=? WHERE order_id=?");
    $statement->execute(array(
      5,
      $order_id
    ));
  } else {
    $statement = $pdo->prepare("SELECT SUM(option_sum) FROM order_products WHERE order_id=? AND option_select=?");
    $statement->execute(array(
      $order_id,
      1
    ));
    $sum = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE orders SET order_sum=? WHERE order_id=?");
    $statement->execute(array(
      $sum['SUM(option_sum)'],
      $order_id
    ));
  }

  // Update option's amounts
  $statement = $pdo->prepare("SELECT * FROM order_products WHERE option_id=?");
  $statement->execute(array(
    $_GET['option_id']
  ));
  $option = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("SELECT * FROM product_options WHERE option_id=?");
  $statement->execute(array(
    $_GET['option_id']
  ));
  $op = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("UPDATE product_options SET amount_selled=?, amount_available=? WHERE option_id=?");
  $statement->execute(array(
    $op['amount_selled'] - $option['option_amount'],
    $op['amount_available'] + $option['option_amount'],
    $_GET['option_id']
  ));

  // Update product's amount
  $statement = $pdo->prepare("SELECT SUM(product_options.amount_selled), SUM(product_options.amount_available) FROM products
                              JOIN product_options ON product_options.product_id=products.product_id 
                              WHERE products.product_id=?");
  $statement->execute(array(
    $op['product_id']
  ));
  $product = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("UPDATE products SET amount_selled=?, amount_available=? WHERE product_id=?");
  $statement->execute(array(
    $product['SUM(product_options.amount_selled)'],
    $product['SUM(product_options.amount_available)'],
    $op['product_id']
  ));

  header("location: orders.php?order_id=" . $order_id);
}

if (isset($_POST['order-cancel'])) {

  // Update order's status
  $statement = $pdo->prepare("UPDATE orders SET status_id=? WHERE order_id=?");
  $statement->execute(array(
    5,
    $order_id
  ));

  $statement = $pdo->prepare("SELECT * FROM order_products WHERE order_id=? AND option_select=?");
  $statement->execute(array(
    $order_id,
    1
  ));
  $options = $statement->fetchAll(PDO::FETCH_ASSOC);

  foreach ($options as $option) {

    // Update option's status
    $statement = $pdo->prepare("UPDATE order_products SET option_select=?, option_status=? WHERE order_id=? AND option_id=?");
    $statement->execute(array(
      0,
      5,
      $order_id,
      $option['option_id']
    ));

    // Update option's amounts
    $statement = $pdo->prepare("SELECT * FROM product_options WHERE option_id=?");
    $statement->execute(array(
      $option['option_id']
    ));
    $op = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE product_options SET amount_selled=?, amount_available=? WHERE option_id=?");
    $statement->execute(array(
      $op['amount_selled'] - $option['option_amount'],
      $op['amount_available'] + $option['option_amount'],
      $option['option_id']
    ));

    // Update product's amount
    $statement = $pdo->prepare("SELECT SUM(product_options.amount_selled), SUM(product_options.amount_available) FROM products
                                  JOIN product_options ON product_options.product_id=products.product_id 
                                  WHERE products.product_id=?");
    $statement->execute(array(
      $op['product_id']
    ));
    $product = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE products SET amount_selled=?, amount_available=? WHERE product_id=?");
    $statement->execute(array(
      $product['SUM(product_options.amount_selled)'],
      $product['SUM(product_options.amount_available)'],
      $op['product_id']
    ));
  }

  $statement = $pdo->prepare("SELECT SUM(option_sum) FROM order_products WHERE order_id=? AND option_select=?");
  $statement->execute(array(
    $order_id,
    0
  ));
  $sum = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("UPDATE orders SET order_sum=? WHERE order_id=?");
  $statement->execute(array(
    $sum['SUM(option_sum)'],
    $order_id
  ));

  header("location: orders.php?order_id=" . $order_id);
}

if (isset($_POST['option-rate'])) {
  if ($_POST['option_rate'] == "") {
    $message_error = "Оцените товар";
  } else if ($_POST['option_review'] == "") {
    $message_error = "Пишите отзыв";
  } else {

    // Rate option
    $statement = $pdo->prepare("UPDATE order_products SET option_rate=?, option_review=? WHERE order_id=? AND option_id=?");
    $statement->execute(array(
      strip_tags($_POST['option_rate']),
      strip_tags($_POST['option_review']),
      $order_id,
      strip_tags($_POST['option_id'])
    ));

    // Update option's rate count
    $statement = $pdo->prepare("SELECT COUNT(*)  FROM order_products
                                WHERE option_id=? AND option_rate IS NOT NULL");
    $statement->execute(array(
      strip_tags($_POST['option_id'])
    ));
    $count = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE product_options SET option_rate_count=? WHERE option_id=?");
    $statement->execute(array(
      $count['COUNT(*)'],
      strip_tags($_POST['option_id'])
    ));

    // Update product's rate
    $statement = $pdo->prepare("SELECT * FROM product_options WHERE option_id=?");
    $statement->execute(array(
      strip_tags($_POST['option_id'])
    ));
    $op = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("SELECT AVG(option_rate), COUNT(*)  FROM order_products
                                JOIN product_options ON product_options.option_id=order_products.option_id
                                JOIN products ON products.product_id=product_options.product_id 
                                WHERE products.product_id=? AND option_rate IS NOT NULL");
    $statement->execute(array(
      $op['product_id']
    ));
    $pr = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE products SET product_rate=?, product_rate_count=? WHERE product_id=?");
    $statement->execute(array(
      $pr['AVG(option_rate)'],
      $pr['COUNT(*)'],
      $op['product_id']
    ));

    header("location: orders.php?order_id=" . $order_id);
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
            <li class="menu-item active">
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

            <?php if ($order_id) { ?>

              <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">
                  <a href="orders.php">
                    Заказы
                  </a> </span>
                <i class='bx bx-chevron-right'></i>
                Заказ № <?php echo $order_id; ?>
              </h4>

              <form class="mb-3" method="POST" enctype="multipart/form-data">
                <?php $csrf->echoInputField(); ?>

                <div class="card mb-4">
                  <div class="card-body">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Время заказа</label>
                      <div class="col-sm-9">
                        <?php echo $order['order_time']; ?>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Статус</label>
                      <div class="col-sm-9">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM statuses WHERE status_id=?");
                        $statement->execute(array(
                          $order['status_id']
                        ));
                        $status = $statement->fetch(PDO::FETCH_ASSOC);

                        if ($status['status_id'] == 5) echo '<span class="badge bg-label-dark">';
                        else if ($status['status_id'] == 4) echo '<span class="badge bg-label-success">';
                        else echo '<span class="badge bg-label-primary">';
                        echo $status['status_title'];
                        echo '</span>'; ?>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Итого</label>
                      <div class="col-sm-9">
                        <strong>
                          ₽ <?php echo $order['order_sum']; ?>
                        </strong>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Способ оплаты</label>
                      <div class="col-sm-9">
                        <span class="badge bg-label-secondary">
                          При получении
                        </span>
                      </div>
                    </div>

                    <div class="row">
                      <label class="col-sm-3 col-form-label">Способ получения</label>
                      <div class="col-sm-9">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM buyer_addresses WHERE address_id = " . $order['address_id']);
                        $statement->execute();
                        $address = $statement->fetch(PDO::FETCH_ASSOC); ?>
                        <ul class="list-group">
                          <li class="list-group-item align-items-center">
                            <i class="bx bxs-map me-2"></i><?php echo $address['address']; ?>
                          </li>
                          <li class="list-group-item align-items-center">
                            <i class="bx bxs-user me-2"></i><?php echo $address['reciever']; ?>
                          </li>
                          <li class="list-group-item align-items-center">
                            <i class="bx bxs-phone me-2"></i><?php echo $address['phone']; ?>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card mb-4">
                  <div class="card-body">
                    <div class="table-responsive text-nowrap">
                      <table class="table table-hover">
                        <thead>
                          <tr>
                            <th></th>
                            <th></th>
                            <th>Товар</th>
                            <th>Цвет</th>
                            <th>Размер</th>
                            <th>Количество</th>
                            <th>Стоимость</th>
                            <th></th>
                          </tr>
                        </thead>

                        <tbody class="table-border-bottom-0">
                          <?php foreach ($options as $option) {
                            $statement = $pdo->prepare("SELECT * FROM statuses WHERE status_id=?");
                            $statement->execute(array(
                              $option['option_status']
                            ));
                            $status = $statement->fetch(PDO::FETCH_ASSOC); ?>

                            <tr>
                              <td>
                                <?php if ($option['option_rate']) {
                                  echo '<span class="badge bg-label-success"> Оценен';
                                } else {
                                  if ($status['status_id'] == 5) echo '<span class="badge bg-label-dark">';
                                  else if ($status['status_id'] == 4) echo '<span class="badge bg-label-success">';
                                  else echo '<span class="badge bg-label-primary">';
                                  if ($status) echo $status['status_title'];
                                  else echo "Оформлен";
                                  echo '</span>';
                                } ?>
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
                                <?php echo " " . $option['option_amount'] . " "; ?>
                              </td>

                              <td>
                                <span class="badge bg-label-success">
                                  <strong>
                                    ₽ <?php echo $option['option_sum']; ?>
                                  </strong>
                                </span>
                              </td>

                              <td>
                                <?php if (($option['option_status'] == 1 || !$option['option_status']) && count($options) != 0) { ?>
                                  <button class="btn btn-icon btn-outline-danger btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#option-cancel-<?php echo $option['option_id']; ?>">
                                    <span class="tf-icons bx bx-trash"></span>
                                  </button>
                                <?php } else if ($option['option_status'] == 4) { ?>
                                  <button class="btn btn-icon btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#option-rate-<?php echo $option['option_id']; ?>">
                                    <?php if ($option['option_rate']) { ?>
                                      <span class="tf-icons bx bx-show"></span>
                                    <?php } else { ?>
                                      <span class="tf-icons bx bx-pencil"></span>
                                    <?php } ?>
                                  </button>
                                <?php } ?>
                              </td>

                              <div class="modal fade" id="option-cancel-<?php echo $option['option_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                  <div class="modal-content">

                                    <div class="modal-header">
                                      <h5 class="modal-title" id="modalCenterTitle">Отменение товара</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                      <div class="row g-0">
                                        <div class="col-md-5">
                                          <img src="../uploads/products/<?php echo $option['product_image']; ?>" alt="<?php echo $option['product_title']; ?>" class="d-block rounded" height="200" id="product_image" />
                                        </div>
                                        <div class="col-md-7">
                                          <div class="card-body">
                                            <h4 class="card-title"><?php echo $option['product_title']; ?></h4>

                                            <p class="text">
                                              <?php echo $option['color_name'] . ' - ' . $option['size_letter']; ?>
                                            </p>

                                            <h4>
                                              ₽ <?php echo $option['option_sum']; ?>
                                            </h4>
                                          </div>
                                        </div>
                                      </div>
                                    </div>

                                    <div class="modal-footer">
                                      <a class="btn btn-danger me-2" href="orders.php?order_id=<?php echo $order_id; ?>&option_id=<?php echo $option['option_id']; ?>&action=cancel">
                                        Отменить
                                      </a>
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="modal fade" id="option-rate-<?php echo $option['option_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                  <div class="modal-content">
                                    <div class="modal-header">
                                      <h5 class="modal-title" id="modalCenterTitle">Оценка товара</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                      <input type="hidden" name="option_id" value="<?php echo $option['option_id']; ?>">
                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Товар</label>
                                        <div class="col-sm-8">
                                          <strong>
                                            <?php echo $option['product_title']; ?>
                                          </strong>
                                        </div>
                                      </div>

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Вариант</label>
                                        <div class="col-sm-8">
                                          <?php echo $option['color_name']; ?> - <?php echo $option['size_letter']; ?>
                                        </div>
                                      </div>

                                      <hr class="mb-3" />

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Оценка</label>
                                        <div class="col-sm-8">

                                          <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="option_rate" id="rate1" value="1" <?php if ($option['option_rate'] == 1) echo 'checked' ?> />
                                            <label class="form-check-label" for="rate1">1</label>
                                          </div>
                                          <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="option_rate" id="rate2" value="2" <?php if ($option['option_rate'] == 2) echo 'checked' ?> />
                                            <label class="form-check-label" for="rate2">2</label>
                                          </div>
                                          <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="option_rate" id="rate3" value="3" <?php if ($option['option_rate'] == 3) echo 'checked' ?> />
                                            <label class="form-check-label" for="rate3">3</label>
                                          </div>
                                          <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="option_rate" id="rate4" value="4" <?php if ($option['option_rate'] == 4) echo 'checked' ?> />
                                            <label class="form-check-label" for="rate4">4</label>
                                          </div>
                                          <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="option_rate" id="rate5" value="5" <?php if ($option['option_rate'] == 5) echo 'checked' ?> />
                                            <label class="form-check-label" for="rate5">5</label>
                                          </div>
                                        </div>
                                      </div>

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Отзыв</label>
                                        <div class="col-sm-8">
                                          <textarea class="form-control" id="option_review" name="option_review" rows="5" value="<?php if ($option['option_review']) echo $option['option_review']; ?>"><?php echo $option['option_review']; ?></textarea>
                                        </div>
                                      </div>

                                      <?php if ($message_error != "") {
                                        echo '<div class="alert alert-danger" role="alert">' . $message_error . '</div>';
                                      } ?>
                                    </div>

                                    <div class="modal-footer">
                                      <button type="submit" class="btn btn-primary" name="option-rate">
                                        <?php if ($option['option_rate']) echo "Сохранить измениения";
                                        else echo "Оценить"; ?>
                                      </button>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <?php if ($order['status_id'] == 1) { ?>
                  <button class=" btn btn-outline-danger me-1" type="button" data-bs-toggle="collapse" data-bs-target="#order-cancel-card" aria-expanded="false" aria-controls="order-cancel-card">
                    Отменить заказ
                  </button>

                  <div class="collapse" id="order-cancel-card">
                    <div class="card mt-3">
                      <div class="card-body">
                        <div class="alert alert-warning">
                          <h6 class="alert-heading fw-bold mb-1">Внимание!</h6>
                          <p class="mb-0">Данное действие не возвращается.</p>
                        </div>
                        <button type="submit" class="btn btn-danger" name="order-cancel">Отменить</button>
                      </div>
                    </div>
                  </div>
                <?php } ?>
              </form>

            <?php } else if ($total == 0) { ?>
              <div class="d-grid gap-2 col-lg-6 mx-auto">
                <a class="btn btn-primary" href="cart.php">Перейти в корзину</button>
              </div>
            <?php } else { ?>
              <h4 class="fw-bold py-3 mb-4">
                Заказы
                <span class="badge badge-center bg-label-primary">
                  <?php echo $total; ?>
                </span>
              </h4>

              <?php foreach ($orders as $order) { ?>
                <div class="card mb-4">
                  <div class="col-12">
                    <div class="row g-0">
                      <div class="col-md-4">
                        <div class="card-body">
                          <h4 class="card-title">
                            <a href="orders.php?order_id=<?php echo $order['order_id']; ?>">
                              <?php echo 'Заказ № ' . $order['order_id']; ?>
                            </a>
                          </h4>

                          <p class="card-text">
                            <?php echo $order['order_time']; ?>
                          </p>

                          <p class="card-text">
                            <?php
                            $statement = $pdo->prepare("SELECT * FROM statuses WHERE status_id=?");
                            $statement->execute(array(
                              $order['status_id']
                            ));
                            $status = $statement->fetch(PDO::FETCH_ASSOC);

                            if ($status['status_id'] == 5) echo '<span class="badge bg-label-dark">';
                            else if ($status['status_id'] == 4) echo '<span class="badge bg-label-success">';
                            else echo '<span class="badge bg-label-primary">';
                            echo $status['status_title'];
                            echo '</span>'; ?>
                          </p>

                          <h5 class="mb-0">
                            <strong>
                              ₽ <?php echo $order['order_sum']; ?>
                            </strong>
                          </h5>

                        </div>
                      </div>

                      <div class="col-md-8">
                        <div class="card-body">
                          <div class="demo-inline-spacing">
                            <div class="list-group list-group-horizontal-md">
                              <?php
                              $statement = $pdo->prepare("SELECT * FROM order_products
                                                      JOIN product_options ON product_options.option_id=order_products.option_id
                                                      WHERE order_id=?
                                                      ORDER BY time_added DESC");
                              $statement->execute(array(
                                $order['order_id']
                              ));
                              $options = $statement->fetchAll(PDO::FETCH_ASSOC);

                              foreach ($options as $option) {
                                $statement = $pdo->prepare("SELECT * FROM products WHERE product_id=?");
                                $statement->execute(array(
                                  $option['product_id']
                                ));
                                $product = $statement->fetch(PDO::FETCH_ASSOC); ?>
                                <a href="products.php?product_id=<?php echo $product['product_id']; ?>&option_id=<?php echo $option['option_id']; ?>">
                                  <img src="../uploads/products/<?php echo $product['product_image']; ?>" alt="<?php echo $product['product_title']; ?>" class="d-block rounded" height="125" id="product_image" />
                                </a>
                              <?php } ?>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
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

  <!-- Main JS -->
  <script src="../assets/js/main.js"></script>

  <!-- Page JS -->

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>