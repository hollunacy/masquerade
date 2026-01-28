<?php

use function PHPSTORM_META\type;

ob_start();
session_start();
include("../database/config.php");
include("../database/functions.php");
include("../database/CSRF_Protect.php");
$csrf = new CSRF_Protect();
$message_error = '';

if (isset($_POST['register'])) {
  $statement = $pdo->prepare("SELECT * FROM user_sellers WHERE seller_email=?");
  $statement->execute(array($_POST['email']));
  $total = $statement->rowCount();
  if ($total) {
    $message_error = "Данная почта уже зарегистрирована";
  } else if ($_POST['password'] != $_POST['password2']) {
    $message_error = "Пароли не совпадают";
  } else if (preg_match('^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{8,}$', $_POST['password']) === 0) {
    $message_error = "Пароль должен содержать минимум одну заглавную букву, одну прописную, одну цифру и один спецзнак, а длина пароля не менее 8 символов";
  } else {
    $statement = $pdo->prepare("INSERT INTO user_sellers (seller_brandname, seller_email, seller_password, seller_token, seller_time, seller_status)
                                VALUES (?,?,?,?,?,?)");
    $statement->execute(array(
      strip_tags($_POST['brandname']),
      strip_tags($_POST['email']),
      md5($_POST['password']),
      md5(time()),
      date('Y-m-d h:i:s'),
      1
    ));
    header("location: account-authentication.php");
  }
}

if (isset($_POST['login'])) {
  $statement = $pdo->prepare("SELECT * FROM user_sellers WHERE seller_email=? AND seller_status=?");
  $statement->execute(array(
    strip_tags($_POST['email']),
    '1'
  ));
  $total = $statement->rowCount();
  if ($total == 0) {
    $message_error .= 'Данная почта не зарегистрирована <br>';
  } else {
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    if ($result['seller_password'] != md5(strip_tags($_POST['password']))) {
      $message_error .= 'Пароль не верный <br>';
    } else {
      $_SESSION['seller'] = $result;
      header("location: index.php");
    }
  }
}

if ($_GET['type'] == "logout") {
  unset($_SESSION['seller']);
  header("location: account-authentication.php");
}
?>

<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="../assets/" data-template="vertical-menu-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    <?php if ($_GET["type"] == "register") {
      echo 'Регистрация';
    } else {
      echo 'Авторизация';
    } ?>
    | МАСКАРАД Продажа
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

  <!-- Page CSS -->
  <!-- Page -->
  <link rel="stylesheet" href="../assets/vendor/css/pages/page-auth.css" />
  <!-- Helpers -->
  <script src="../assets/vendor/js/helpers.js"></script>

  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <script src="../assets/js/config.js"></script>
</head>

<body>
  <!-- Content -->
  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner">

        <!-- Register Card -->
        <div class="card">
          <div class="card-body">

            <!-- Logo -->
            <div class="app-brand justify-content-center">
              <a href="../shop/products.php" class="app-brand-link gap-2">
                <span class="app-brand-text demo text-body fw-bolder">МАСКАРАД</span>
              </a>
            </div>
            <!-- /Logo -->

            <form id="authentication" class="mb-3" method="POST">
              <?php $csrf->echoInputField(); ?>
              <?php if ($_GET['type'] == "register") { ?>
                <div class="mb-3">
                  <label for="brandname" class="form-label">Название бренда</label>
                  <input type="text" class="form-control" id="brandname" name="brandname" value="<?php if (isset($_POST['brandname'])) echo $_POST['brandname']; ?>" required>
                </div>
              <?php } ?>
              <div class="mb-3">
                <label for="email" class="form-label">Почта</label>
                <input type="text" class="form-control" id="email" name="email" value="<?php if (isset($_POST['email'])) echo $_POST['email']; ?>" required>
              </div>
              <div class="mb-3 form-password-toggle">
                <label class="form-label" for="password">Пароль</label>
                <input type="password" id="password" class="form-control" name="password" required>
              </div>
              <?php if ($_GET['type'] == "register") { ?>
                <div class="mb-3 form-password-toggle">
                  <label class="form-label" for="password">Подтвердите пароль</label>
                  <input type="password" id="password2" class="form-control" name="password2" required>
                </div>
              <?php } ?>

              <?php if ($message_error != "") {
                echo '<div class="alert alert-danger" role="alert">' . $message_error . '</div>';
              } ?>

              <?php if ($_GET['type'] == "register") { ?>
                <button class="btn btn-primary d-grid w-100" type="submit" name="register">Зарегистрироваться</button>
              <?php } else { ?>
                <button class="btn btn-primary d-grid w-100" type="submit" name="login">Войти</button>
              <?php } ?>
            </form>

            <?php if ($_GET['type'] == "register") { ?>
              <p class="text-center">
                <a href="account-authentication.php">
                  <span>Войти</span>
                </a>
              </p>
            <?php } else { ?>
              <p class="text-center">
                <a href="account-authentication.php?type=register">
                  <span>Зарегистрироваться</span>
                </a>
              </p>
            <?php } ?>
          </div>
        </div>
        <!-- Register Card -->
      </div>
    </div>
  </div>
  <!-- / Content -->

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