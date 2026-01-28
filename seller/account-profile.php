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
  $statement = $pdo->prepare("SELECT * FROM user_sellers WHERE seller_id = " . $seller_id);
  $statement->execute();
  $seller = $statement->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['account-edit'])) {
  $statement = $pdo->prepare("UPDATE user_sellers SET seller_brandname=?, seller_description=?, seller_email=?, seller_phone=? WHERE seller_id=" . $seller_id);
  $statement->execute(array(
    strip_tags($_POST['seller_brandname']),
    strip_tags($_POST['seller_description']),
    strip_tags($_POST['seller_email']),
    strip_tags($_POST['seller_phone'])
  ));

  if ($_FILES['seller_logo']["name"] != '') {
    $image = 'seller-' . $seller_id . '.' . pathinfo($_FILES['seller_logo']['name'], PATHINFO_EXTENSION);
    move_uploaded_file($_FILES['seller_logo']['tmp_name'], "../uploads/users/sellers/" . $image);
    $statement = $pdo->prepare("UPDATE user_sellers SET seller_logo=? WHERE seller_id=" . $seller_id);
    $statement->execute(array($image));
  }

  header('location: account-profile.php');
}

if (isset($_POST['account-deactive'])) {
  $statement = $pdo->prepare("UPDATE user_sellers SET seller_status = ? WHERE seller_id=" . $seller_id);
  $statement->execute(array(0));

  $statement = $pdo->prepare("UPDATE user_sellers SET seller_status = ? WHERE seller_id=" . $seller_id);
  $statement->execute(array());

  header('location: account-profile.php');
}

if (isset($_POST['account-active'])) {
  $statement = $pdo->prepare("UPDATE user_sellers SET seller_status = ? WHERE seller_id=" . $seller_id);
  $statement->execute(array(1));
  header('location: account-profile.php');
}

?>

<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>Профиль | МАСКАРАД Продажа</title>

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
          <li class="menu-item active">
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

            <h4 class="fw-bold py-3 mb-4">
              <span class="text-muted fw-light">Личный кабинет / </span>
              Профиль
            </h4>

            <form class="mb-3" method="POST" enctype="multipart/form-data">
              <?php $csrf->echoInputField(); ?>

              <div class="card mb-4">
                <div class="row g-0">

                  <div class="d-flex align-items-start align-items-sm-center gap-4">
                    <div class="col-md-0.5"></div>

                    <div class="col-md-2.5">
                      <img class="card-img card-img-center rounded-circle" src="../uploads/users/sellers/<?php echo $seller['seller_logo']; ?>" alt="<?php echo $seller['seller_brandname']; ?>">
                    </div>

                    <div class="col-md-9">
                      <h5 class="card-header">Публичная информация</h5>

                      <div class="card-body">

                        <div class="mb-3 row">
                          <label class="col-md-2 col-form-label">Статус</label>
                          <div class="col-md-10">
                            <?php if ($seller['seller_status'] == 1) {
                              echo '<span class="badge bg-label-success me-1">Активирован</span>';
                            } else {
                              echo '<span class="badge bg-label-dark me-1">Деактивирован</span>';
                            } ?>
                          </div>
                        </div>

                        <div class="mb-3 row">
                          <label class="col-md-2 col-form-label">Название бренда</label>
                          <div class="col-md-10">
                            <input type="text" class="form-control" id="seller_brandname" name="seller_brandname" value="<?php echo $seller['seller_brandname']; ?>" required>
                          </div>
                        </div>

                        <div class="mb-3 row">
                          <label class="col-md-2 col-form-label">Описание</label>
                          <div class="col-md-10">
                            <textarea class="form-control" id="seller_description" name="seller_description" rows="3" value="<?php echo $seller['seller_description']; ?>"><?php echo $seller['seller_description']; ?></textarea>
                          </div>
                        </div>

                        <div class=" mb-3 row">
                          <label class="col-md-2 col-form-label">Логотип</label>
                          <div class="col-md-10">
                            <input class="form-control" type="file" accept="image/*" id="seller_logo" name="seller_logo">
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card mb-4">
                <h5 class="card-header">Контакная информация</h5>
                <div class="card-body">
                  <div class="row">

                    <div class="mb-3 col-md-6">
                      <label class="form-label">Почта</label>
                      <input type="email" class="form-control" id="seller_email" name="seller_email" value="<?php echo $seller['seller_email']; ?>" required>
                    </div>

                    <div class="mb-3 col-md-6">
                      <label class="form-label">Номер телефона</label>
                      <input type="tel" class="form-control" id="seller_phone" name="seller_phone" value="<?php echo $seller['seller_phone']; ?>">
                    </div>

                  </div>
                </div>
              </div>

              <p class="demo-inline-spacing">
                <button type="submit" class="btn btn-primary me-2" name="account-edit">Сохранить изменения</button>
                <?php if ($seller['seller_status'] == 1) { ?>
                  <button class="btn btn-outline-danger me-1" type="button" data-bs-toggle="collapse" data-bs-target="#account-deactive" aria-expanded="false" aria-controls="account-deactive">
                    Деактивировать профиль
                  </button>
                <?php } else { ?>
                  <button class="btn btn-outline-info me-1" type="button" data-bs-toggle="collapse" data-bs-target="#account-active" aria-expanded="false" aria-controls="account-active">
                    Активировать профиль
                  </button>
                <?php } ?>
              </p>

              <div class="collapse" id="account-deactive">
                <div class="card">
                  <div class="card-body">
                    <div class="alert alert-warning">
                      <h6 class="alert-heading fw-bold mb-1">Внимание!</h6>
                      <p class="mb-0">После деактивации профиля созданные товары будут скрыты.</p>
                    </div>
                    <button type="submit" class="btn btn-danger" name="account-deactive">Деактивировать</button>
                  </div>
                </div>
              </div>

              <div class="collapse" id="account-active">
                <div class="card">
                  <div class="card-body">
                    <div class="alert alert-warning">
                      <h6 class="alert-heading fw-bold mb-1">Внимание!</h6>
                      <p class="mb-0">Активировав профиль, скрытые товары не будут автоматически публикованы.</p>
                    </div>
                    <button type="submit" class="btn btn-info" name="account-active">Активировать</button>
                  </div>
                </div>
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
  <script src="../assets/js/pages-account-profile-account.js"></script>

  <!-- Place this tag in your head or just before your close body tag. -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
</body>

</html>