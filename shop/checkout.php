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

$statement = $pdo->prepare("SELECT * FROM orders WHERE buyer_id='$buyer_id' AND status_id=0");
$statement->execute();
$cart = $statement->fetch(PDO::FETCH_ASSOC);

$statement = $pdo->prepare("SELECT * FROM order_products
                            JOIN product_options ON product_options.option_id=order_products.option_id 
                              JOIN colors ON colors.color_id=product_options.color_id 
                              JOIN sizes ON sizes.size_id=product_options.size_id                           
                            JOIN products ON products.product_id=product_options.product_id
                            JOIN orders ON orders.order_id=order_products.order_id
                            WHERE buyer_id=? AND status_id=? AND option_select=?
                            ORDER BY time_added DESC");
$statement->execute(array(
  $buyer_id,
  0,
  1
));
$options = $statement->fetchAll(PDO::FETCH_ASSOC);

$title = 'Оформление заказа';

$csrf->echoInputField();

if (isset($_GET['address_id'])) {
  $statement = $pdo->prepare("UPDATE orders SET address_id=? WHERE order_id=?");
  $statement->execute(array(
    $_GET['address_id'],
    $cart['order_id']
  ));
  header("location: checkout.php");
}

if (isset($_POST['address-add'])) {
  if ($_POST['address_title'] == '' || $_POST['address'] == '' || $_POST['reciever'] == '' || $_POST['phone'] == '') {
    $message_error = "Все пункты должны быть заполняться";
  } else {
    $statement = $pdo->prepare("INSERT INTO buyer_addresses (buyer_id, address_title, address, reciever, phone) VALUE (?,?,?,?,?)");
    $statement->execute(array(
      $buyer_id,
      strip_tags($_POST['address_title']),
      strip_tags($_POST['address']),
      strip_tags($_POST['reciever']),
      strip_tags($_POST['phone'])
    ));
  }
}

if (isset($_POST['order-place'])) {
  if (!$cart['address_id']) {
    $message_error = "Выберите адрес получения";
  } else {
    // Create new order
    $statement = $pdo->prepare("INSERT INTO orders (buyer_id, status_id, order_sum, address_id, order_time) VALUE (?,?,?,?,?)");
    $statement->execute(array(
      $buyer_id,
      1,
      $cart['order_sum'],
      $cart['address_id'],
      date('Y-m-d h:i:s')
    ));

    // Access to the order
    $statement = $pdo->prepare("SELECT * FROM orders WHERE buyer_id=? AND status_id=? ORDER BY order_time ASC");
    $statement->execute(array(
      $buyer_id,
      1
    ));
    $orders = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orders as $row) $order = $row;

    // Update product options
    $statement = $pdo->prepare("SELECT * FROM order_products WHERE order_id=? AND option_select=?");
    $statement->execute(array(
      $cart['order_id'],
      1
    ));
    $options = $statement->fetchAll(PDO::FETCH_ASSOC);
    foreach ($options as $option) {

      // Insert options to new order
      $statement = $pdo->prepare("INSERT INTO order_products (order_id, option_id, option_amount, option_select, option_sum, option_status, time_added)
                              VALUE (?,?,?,?,?,?,?)");
      $statement->execute(array(
        $order['order_id'],
        $option['option_id'],
        $option['option_amount'],
        $option['option_select'],
        $option['option_sum'],
        1,
        $option['time_added']
      ));

      // Update cart
      $statement = $pdo->prepare("DELETE FROM order_products WHERE order_id=? AND option_id=?");
      $statement->execute(array(
        $cart['order_id'],
        $option['option_id']
      ));

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

      // Update option's amounts
      $statement = $pdo->prepare("SELECT * FROM product_options WHERE option_id=?");
      $statement->execute(array(
        $option['option_id']
      ));
      $op = $statement->fetch(PDO::FETCH_ASSOC);

      $statement = $pdo->prepare("UPDATE product_options SET amount_selled=?, amount_available=? WHERE option_id=?");
      $statement->execute(array(
        $op['amount_selled'] + $option['option_amount'],
        $op['amount_available'] - $option['option_amount'],
        $option['option_id']
      ));

      // Update products
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

    // Go to placed order
    header("location: orders.php?order_id=" . $order['order_id']);
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
              <span class="text-muted fw-light">
                <a href="cart.php">
                  Корзина
                </a> </span>
              <i class='bx bx-chevron-right'></i>
              Оформление заказа
            </h4>

            <form class="mb-3" method="POST" enctype="multipart/form-data">
              <?php $csrf->echoInputField(); ?>

              <div class="card mb-4">
                <div class="card-body">
                  <div class="row">
                    <label class="col-sm-3 col-form-label">Способ оплаты</label>
                    <div class="col-sm-9">
                      <input class="form-control" type="text" placeholder="При получении" readonly>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card mb-4">
                <div class="card-body">
                  <div class="row">
                    <label class="col-sm-3 col-form-label">Способ получения</label>
                    <div class="col-sm-9">
                      <?php if ($cart['address_id']) {
                        $statement = $pdo->prepare("SELECT * FROM buyer_addresses WHERE address_id = " . $cart['address_id']);
                        $statement->execute();
                        $address = $statement->fetch(PDO::FETCH_ASSOC); ?>
                        <ul class="list-group mb-3">
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
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#address-all">
                          Изменить
                        </button>
                      <?php } else { ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#address-all">
                          Выбрать
                        </button>
                      <?php } ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-3">
                <div class="modal fade" id="address-all" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">Адреса</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>

                      <div class="modal-body">
                        <div class="row">
                          <?php
                          $statement = $pdo->prepare("SELECT * FROM buyer_addresses WHERE buyer_id = " . $buyer_id);
                          $statement->execute();
                          $addresses = $statement->fetchAll(PDO::FETCH_ASSOC);
                          foreach ($addresses as $address) { ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                              <div class="card shadow-none bg-transparent border border-primary mb-3">
                                <h5 class="card-header"><a href="account-address.php?address_id=<?php echo $address['address_id']; ?>"><?php echo $address['address_title']; ?></a></h5>
                                <div class="card-body">
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

                                  <p class="demo-inline-spacing text-center mb-0">
                                    <a class="btn btn-primary" href="checkout.php?address_id=<?php echo $address['address_id']; ?>">
                                      Выбрать
                                    </a>
                                  </p>
                                </div>
                              </div>
                            </div>
                          <?php } ?>
                        </div>
                      </div>

                      <?php if ($message_error != "") {
                        echo '<div class="alert alert-danger" role="alert">' . $message_error . '</div>';
                      } ?>

                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-dismiss="modal" data-bs-target="#address-add">
                          Добавить новый адрес
                        </button>
                      </div>

                    </div>
                  </div>
                </div>

                <div class="modal fade" id="address-add" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalCenterTitle">Добавление адреса</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>

                      <div class="modal-body">
                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Название</label>
                          <div class="col-sm-9">
                            <input type="text" class="form-control" id="address_title" name="address_title" value="<?php if (isset($_POST['address_title'])) echo $_POST['address_title']; ?>">
                          </div>
                        </div>

                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Адрес</label>
                          <div class="col-sm-9">
                            <input type="text" class="form-control" id="address" name="address" value="<?php if (isset($_POST['address'])) echo $_POST['address']; ?>">
                          </div>
                        </div>

                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Получатель</label>
                          <div class="col-sm-9">
                            <input type="text" class="form-control" id="reciever" name="reciever" value="<?php if (isset($_POST['reciever'])) echo $_POST['reciever']; ?>">
                          </div>
                        </div>

                        <div class="row mb-3">
                          <label class="col-sm-3 col-form-label">Номер телефона</label>
                          <div class="col-sm-9">
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php if (isset($_POST['phone'])) echo $_POST['phone']; ?>">
                          </div>
                        </div>
                      </div>

                      <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" name="address-add">Добавить</button>
                      </div>
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
                          <th>Товар</th>
                          <th>Цвет</th>
                          <th>Размер</th>
                          <th>Количество</th>
                          <th>Стоимость</th>
                        </tr>
                      </thead>

                      <tbody class="table-border-bottom-0">
                        <?php foreach ($options as $option) { ?>
                          <tr>

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

                          </tr>
                        <?php } ?>
                      </tbody>

                      <tfoot class="table-border-bottom-0">
                        <tr>
                          <th>Итого</th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th>
                            <h6 class="mb-0">
                              <?php
                              $statement = $pdo->prepare("SELECT SUM(option_amount) FROM order_products WHERE order_id=? AND option_select=?");
                              $statement->execute(array(
                                $cart['order_id'],
                                1
                              ));
                              $total = $statement->fetch(PDO::FETCH_ASSOC);
                              echo $total['SUM(option_amount)']; ?>
                            </h6>
                          </th>
                          <th>
                            <h4 class="mb-0">
                              <strong>
                                ₽ <?php echo $cart['order_sum']; ?>
                              </strong>
                            </h4>
                          </th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
              </div>

              <?php if ($message_error != "") {
                echo '<div class="alert alert-danger" role="alert">' . $message_error . '</div>';
              } ?>

              <div class="d-grid gap-2 col-lg-6 mx-auto">
                <button type="submit" class="btn btn-primary" name="order-place">Оформить заказ</button>
              </div>

            </form>

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