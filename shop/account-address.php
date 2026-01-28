<?php
ob_start();
session_start();
include("../database/config.php");
include("../database/functions.php");
include("../database/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$message_error = '';

if (!isset($_SESSION['buyer'])) {
  header('location: account-authentication.php');
  exit;
} else {
  $buyer_id = $_SESSION['buyer']['buyer_id'];
  $statement = $pdo->prepare("SELECT * FROM user_buyers WHERE buyer_id = " . $buyer_id);
  $statement->execute();
  $buyer = $statement->fetch(PDO::FETCH_ASSOC);

  if (isset($_GET['address_id'])) {
    $address_id = $_GET['address_id'];
    $statement = $pdo->prepare("SELECT * FROM buyer_addresses WHERE address_id = " . $address_id);
    $statement->execute();
    $address = $statement->fetch(PDO::FETCH_ASSOC);
  } else {
    $statement = $pdo->prepare("SELECT * FROM buyer_addresses WHERE buyer_id = " . $buyer_id);
    $statement->execute();
    $addresses = $statement->fetchAll(PDO::FETCH_ASSOC);
  }
}

$title = "Адреса";

if (isset($_POST['address-add'])) {
  $statement = $pdo->prepare("INSERT INTO buyer_addresses (buyer_id, address_title, address, reciever, phone) VALUE (?,?,?,?,?)");
  $statement->execute(array(
    $buyer_id,
    strip_tags($_POST['address_title']),
    strip_tags($_POST['address']),
    strip_tags($_POST['reciever']),
    strip_tags($_POST['phone'])
  ));
  header("location: account-address.php");
}

if (isset($_POST['address-edit'])) {
  $statement = $pdo->prepare("UPDATE buyer_addresses SET address_title=?, address=?, reciever=?, phone=? WHERE address_id=" . $address_id);
  $statement->execute(array(
    strip_tags($_POST['address_title']),
    strip_tags($_POST['address']),
    strip_tags($_POST['reciever']),
    strip_tags($_POST['phone'])
  ));
  header("location: account-address.php?address_id=" . $address_id);
}

if (isset($_POST['address-delete'])) {
  $statement = $pdo->prepare("DELETE FROM buyer_addresses WHERE address_id=" . $address_id);
  $statement->execute();
  header("location: account-address.php");
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
              <a href="orders.php" class="menu-link">
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

            <li class="menu-item active">
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

            <div class="layout-menu-toggle navbar-nav me-3 me-xl-0 d-xl-none">
              <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                <i class="bx bx-menu bx-sm"></i>
              </a>
            </div>

            <h4 class="fw-bold py-3 mb-4">
              <span class="text-muted fw-light">Личный кабинет / </span>
              <?php if ($address) echo '<a href="account-address.php">' ?>
              Адреса
              <?php if ($address) echo '</a>' ?>
            </h4>

            <?php if ($address) { ?>
              <form class="mb-3" method="POST" enctype="multipart/form-data">
                <?php $csrf->echoInputField(); ?>

                <div class="card mb-4">
                  <div class="card-body">
                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Название</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" id="address_title" name="address_title" value="<?php echo $address['address_title']; ?>" required>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Адрес</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo $address['address']; ?>" required>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Получатель</label>
                      <div class="col-sm-9">
                        <input type="text" class="form-control" id="reciever" name="reciever" value="<?php echo $address['reciever']; ?>" required>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label class="col-sm-3 col-form-label">Номер телефона</label>
                      <div class="col-sm-9">
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo $address['phone']; ?>" required>
                      </div>
                    </div>
                  </div>
                </div>

                <p class="demo-inline-spacing">
                  <button type="submit" class="btn btn-primary me-2" name="address-edit">Сохранить изменения</button>

                  <button class="btn btn-outline-danger me-1" type="button" data-bs-toggle="collapse" data-bs-target="#address-delete" aria-expanded="false" aria-controls="address-delete">
                    Удалить адрес
                  </button>
                </p>

                <div class="collapse" id="address-delete">
                  <div class="card">
                    <div class="card-body">
                      <div class="alert alert-warning">
                        <h6 class="alert-heading fw-bold mb-1">Внимание!</h6>
                        <p class="mb-0">Данное действие не возвращается.</p>
                      </div>
                      <button type="submit" class="btn btn-danger" name="address-delete">Удалить</button>
                    </div>
                  </div>
                </div>
              </form>

            <?php } else { ?>
              <form class="mb-3" method="POST" enctype="multipart/form-data">
                <?php $csrf->echoInputField(); ?>

                <div class="row">
                  <?php foreach ($addresses as $address) { ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                      <div class="card h-100">
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
                        </div>
                      </div>
                    </div>
                  <?php } ?>
                </div>

                <p class="demo-inline-spacing">
                  <button type="button" class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#address-add">
                    Добавить адрес
                  </button>
                </p>

                <div class="mt-3">
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
                              <input type="text" class="form-control" id="address_title" name="address_title" value="<?php if (isset($_POST['address_title'])) echo $_POST['address_title']; ?>" required>
                            </div>
                          </div>

                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Адрес</label>
                            <div class="col-sm-9">
                              <input type="text" class="form-control" id="address" name="address" value="<?php if (isset($_POST['address'])) echo $_POST['address']; ?>" required>
                            </div>
                          </div>

                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Получатель</label>
                            <div class="col-sm-9">
                              <input type="text" class="form-control" id="reciever" name="reciever" value="<?php if (isset($_POST['reciever'])) echo $_POST['reciever']; ?>" required>
                            </div>
                          </div>

                          <div class="row mb-3">
                            <label class="col-sm-3 col-form-label">Номер телефона</label>
                            <div class="col-sm-9">
                              <input type="tel" class="form-control" id="phone" name="phone" value="<?php if (isset($_POST['phone'])) echo $_POST['phone']; ?>" required>
                            </div>
                          </div>

                          <?php if ($message_error != "") {
                            echo '<div class="alert alert-danger" role="alert">' . $message_error . '</div>';
                          } ?>
                        </div>

                        <div class="modal-footer">
                          <button type="submit" class="btn btn-primary" name="address-add">Добавить</button>
                        </div>
                      </div>
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
  <script src="../assets/js/pages-account-profile-account.js"></script>

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>