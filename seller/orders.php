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

if ($_GET['filter'] == "inprogress") {
  $statement = $pdo->prepare("SELECT * FROM order_products
                              JOIN product_options ON product_options.option_id=order_products.option_id
                                JOIN colors ON colors.color_id=product_options.color_id
                                JOIN sizes ON sizes.size_id=product_options.size_id     
                              JOIN products ON products.product_id=product_options.product_id
                              WHERE seller_id=? AND option_status BETWEEN ? AND ?
                              ORDER BY order_products.order_id DESC, time_added DESC");
  $statement->execute(array(
    $seller_id,
    1,
    3
  ));
  $title = 'Текущие заказы';
} else if ($_GET['filter'] == "completed") {
  $statement = $pdo->prepare("SELECT * FROM order_products
                              JOIN product_options ON product_options.option_id=order_products.option_id
                                JOIN colors ON colors.color_id=product_options.color_id
                                JOIN sizes ON sizes.size_id=product_options.size_id     
                              JOIN products ON products.product_id=product_options.product_id
                              WHERE seller_id=? AND option_status=?
                              ORDER BY order_products.order_id DESC, time_added DESC");
  $statement->execute(array(
    $seller_id,
    4
  ));
  $title = 'Завершенные заказы';
} else if ($_GET['filter'] == "canceled") {
  $statement = $pdo->prepare("SELECT * FROM order_products
                              JOIN product_options ON product_options.option_id=order_products.option_id
                                JOIN colors ON colors.color_id=product_options.color_id
                                JOIN sizes ON sizes.size_id=product_options.size_id     
                              JOIN products ON products.product_id=product_options.product_id
                              WHERE seller_id=? AND option_status=?
                              ORDER BY order_products.order_id DESC, time_added DESC");
  $statement->execute(array(
    $seller_id,
    5
  ));
  $title = 'Завершенные заказы';
} else {
  $statement = $pdo->prepare("SELECT * FROM order_products
                              JOIN product_options ON product_options.option_id=order_products.option_id
                                JOIN colors ON colors.color_id=product_options.color_id
                                JOIN sizes ON sizes.size_id=product_options.size_id     
                              JOIN products ON products.product_id=product_options.product_id
                              WHERE seller_id=? AND option_status>?
                              ORDER BY order_products.order_id DESC, time_added DESC");
  $statement->execute(array(
    $seller_id,
    1
  ));
  $title = 'Заказы';
}
$options = $statement->fetchAll(PDO::FETCH_ASSOC);
$total = count($options);

if (isset($_GET['status_id'])) {
  $status_id = $_GET['status_id'];
  $statement = $pdo->prepare("UPDATE order_products SET option_status=? WHERE order_id=? AND option_id=?");
  $statement->execute(array(
    $status_id,
    $_GET['order_id'],
    $_GET['option_id']
  ));

  $statement = $pdo->prepare("SELECT MIN(option_status) FROM order_products WHERE order_id=?");
  $statement->execute(array(
    $_GET['order_id']
  ));
  $min = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("UPDATE orders SET status_id=? WHERE order_id=?");
  $statement->execute(array(
    $min['MIN(option_status)'],
    $_GET['order_id']
  ));

  if ($status_id < 4) {
    header("location: orders.php?filter=inprogress");
  } else {
    header("location: orders.php?filter=completed");
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
          <li class="menu-item">
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
          <li class="menu-item active open">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bx-box"></i>
              <div data-i18n="Orders">Заказы</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item <?php if ($_GET['filter'] == "inprogress") echo 'active'; ?>">
                <a href="orders.php?filter=inprogress" class="menu-link">
                  <div data-i18n="In Progress">В процессе</div>
                </a>
              </li>
              <li class="menu-item <?php if ($_GET['filter'] == "completed") echo 'active'; ?>">
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

            <h4 class="fw-bold py-3 mb-3">
              Заказы
            </h4>

            <div class="col-md-12">
              <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item">
                  <a class="nav-link <?php if ($_GET['filter'] == "inprogress") echo "active"; ?>" href="orders.php?filter=inprogress">
                    В процессе
                    <?php if ($_GET['filter'] == "inprogress") echo '<span class="badge bg-white text-primary rounded-pill">' . $total . '</span>'; ?>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?php if ($_GET['filter'] == "completed") echo "active"; ?>" href="orders.php?filter=completed">
                    Завершенные
                    <?php if ($_GET['filter'] == "completed") echo '<span class="badge bg-white text-primary rounded-pill">' . $total . '</span>'; ?>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link <?php if ($_GET['filter'] == "canceled") echo "active"; ?>" href="orders.php?filter=canceled">
                    Отмененные
                    <?php if ($_GET['filter'] == "canceled") echo '<span class="badge bg-white text-primary rounded-pill">' . $total . '</span>'; ?>
                  </a>
                </li>
              </ul>
            </div>

            <?php if ($total != 0) { ?>
              <form class="mb-3" method="POST" enctype="multipart/form-data">
                <?php $csrf->echoInputField(); ?>
                <div class="card mb-4">
                  <div class="card-body">
                    <div class="table-responsive text-nowrap">
                      <table class="table table-hover">
                        <tbody class="table-border-bottom-0">
                          <?php foreach ($options as $option) {
                            $statement = $pdo->prepare("SELECT * FROM orders WHERE order_id=?");
                            $statement->execute(array(
                              $option['order_id']
                            ));
                            $order = $statement->fetch(PDO::FETCH_ASSOC); ?>
                            <tr>

                              <td>
                                <strong>Заказ № <?php echo $order['order_id']; ?> </strong>
                                <br><?php echo $order['order_time']; ?>
                              </td>

                              <!-- <td>
                                  <?php echo $order['order_time']; ?>
                                </td> -->

                              <td>
                                <?php
                                $statement = $pdo->prepare("SELECT * FROM statuses WHERE status_id=?");
                                $statement->execute(array(
                                  $option['option_status']
                                ));
                                $status = $statement->fetch(PDO::FETCH_ASSOC);

                                if ($status['status_id'] == 5) echo '<span class="badge bg-label-dark">';
                                else if ($status['status_id'] == 4) echo '<span class="badge bg-label-success">';
                                else echo '<span class="badge bg-label-primary">';
                                echo $status['status_title'];
                                echo '</span>';
                                ?>
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
                                x <?php echo $option['option_amount']; ?>
                              </td>

                              <td>
                                <strong>
                                  ₽ <?php echo $option['option_sum']; ?>
                                </strong>
                              </td>

                              <td>
                                <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#option-card-<?php echo $option['option_id']; ?>">
                                  <span class="tf-icons bx bx-show"></span>
                                </button>
                              </td>

                              <div class="modal fade" id="option-card-<?php echo $option['option_id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-scrollable" role="document">
                                  <div class="modal-content">
                                    <div class="modal-header">
                                      <h5 class="modal-title" id="modalCenterTitle">Заказ № <?php echo $order['order_id']; ?></h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Время заказа</label>
                                        <div class="col-sm-8">
                                          <?php echo $order['order_time']; ?>
                                        </div>
                                      </div>

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Статус</label>
                                        <div class="col-sm-8">
                                          <?php if ($status['status_id'] == 5) echo '<span class="badge bg-label-dark">';
                                          else if ($status['status_id'] == 4) echo '<span class="badge bg-label-success">';
                                          else echo '<span class="badge bg-label-primary">';
                                          echo $status['status_title'];
                                          echo '</span>'; ?>
                                        </div>
                                      </div>

                                      <hr class="mb-3" />

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

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Количество</label>
                                        <div class="col-sm-8">
                                          <?php echo $option['option_amount']; ?>
                                        </div>
                                      </div>

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Итого</label>
                                        <div class="col-sm-8">
                                          <strong>
                                            ₽ <?php echo $option['option_sum']; ?>
                                          </strong>
                                        </div>
                                      </div>

                                      <hr class="mb-3" />

                                      <div class="row mb-3">
                                        <label class="col-sm-4 col-form-label">Способ оплаты</label>
                                        <div class="col-sm-8">
                                          <span class="badge bg-label-secondary">
                                            При получении
                                          </span>
                                        </div>
                                      </div>

                                      <div class="row">
                                        <label class="col-sm-4 col-form-label">Способ получения</label>
                                        <div class="col-sm-8">
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

                                    <div class="modal-footer">
                                      <?php if ($status['status_id'] < 4) { ?>
                                        <a type="button" class="btn btn-primary" href="orders.php?order_id=<?php echo $order['order_id']; ?>&option_id=<?php echo $option['option_id']; ?>&status_id=<?php echo $status['status_id'] + 1; ?>">
                                          <?php if ($status['status_id'] == 1) echo "Принять товар";
                                          else if ($status['status_id'] == 2) echo "Начать доставку";
                                          else if ($status['status_id'] == 3) echo "Завершить доставку"; ?>
                                        </a>
                                      <?php } else { ?>
                                      <?php } ?>
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