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

if (isset($_GET["product_id"])) {
  if ($_GET["product_id"] != "new") {
    $product_id = $_GET["product_id"];
    $statement = $pdo->prepare("SELECT * FROM products WHERE product_id=?");
    $statement->execute(array(
      $product_id
    ));
    $product = $statement->fetch(PDO::FETCH_ASSOC);

    if (isset($_GET['option_id'])) {
      $option_id = $_GET["option_id"];
      $statement = $pdo->prepare("SELECT * FROM product_options 
                                  JOIN colors ON colors.color_id=product_options.color_id
                                  JOIN sizes ON sizes.size_id=product_options.size_id
                                  WHERE option_id=?");
      $statement->execute(array($option_id));
      $option = $statement->fetch(PDO::FETCH_ASSOC);
      $title = $product['product_title'] . ' ' . $option['color_name'] . ' ' . $option['size_letter'];
    } else {
      $statement = $pdo->prepare("SELECT * FROM product_options 
                                  JOIN colors ON colors.color_id=product_options.color_id
                                  JOIN sizes ON sizes.size_id=product_options.size_id
                                  WHERE product_id=?
                                  ORDER BY color_name, sizes.size_id");
      $statement->execute(array(
        $product_id
      ));
      $options = $statement->fetchAll(PDO::FETCH_ASSOC);

      $title = $product['product_title'];
    }
  } else {
    $title = "Добавление товара";
  }
} else if (isset($_GET['filter'])) {

  if (isset($_GET['view'])) $view = $_GET['view'];
  else $view = "table";

  if ($_GET['filter'] == "instock") {
    $title = 'Товары в наличии';
    $statement = $pdo->prepare("SELECT * FROM products
                                JOIN categories ON products.category_id=categories.category_id
                                WHERE seller_id=? AND product_active=? AND amount_available>?
                                ORDER BY time_created DESC");
    $statement->execute(array(
      $seller_id,
      1,
      0
    ));
  } else if ($_GET['filter'] == "outofstock") {
    $title = 'Распроданные товары';
    $statement = $pdo->prepare("SELECT * FROM products
                                JOIN categories ON products.category_id=categories.category_id
                                WHERE seller_id=? AND product_active=? AND amount_available=?
                                ORDER BY time_created DESC");
    $statement->execute(array(
      $seller_id,
      1,
      0
    ));
  } else if ($_GET['filter'] == "draft") {
    $title = 'Не публикованные товары';
    $statement = $pdo->prepare("SELECT * FROM products
                                JOIN categories ON products.category_id=categories.category_id
                                WHERE seller_id=? AND product_active=?
                                ORDER BY time_created DESC");
    $statement->execute(array(
      $seller_id,
      0
    ));
  } else {
    $title = 'Заказы';
    $statement = $pdo->prepare("SELECT * FROM products
                                JOIN categories ON products.category_id=categories.category_id
                                WHERE seller_id=?
                                ORDER BY time_created DESC");
    $statement->execute(array(
      $seller_id
    ));
  }
  $products = $statement->fetchAll(PDO::FETCH_ASSOC);
  $total = count($products);
}

if (isset($_POST['add-product']) || isset($_POST['edit-product'])) {
  $valid = 1;

  if (empty($_POST['product_title'])) {
    $valid = 0;
    $error_message .= "Название не должно быть пустым<br>";
  }

  if (empty($_POST['category_id']) || $_POST['category_id'] == 0) {
    $valid = 0;
    $error_message .= "Категория не должна быть пустой<br>";
  }

  if (empty($_POST['genders'])) {
    $valid = 0;
    $error_message .= 'Пол не должен быть пустым<br>';
  }

  if (empty($_POST['materials'])) {
    $valid = 0;
    $error_message .= 'Метериал не должен быть пустым<br>';
  }

  if (empty($_POST['styles'])) {
    $valid = 0;
    $error_message .= 'Стиль не должна быть пустой<br>';
  }

  if (empty($_POST['seasons'])) {
    $valid = 0;
    $error_message .= 'Сезон не должен быть пустым<br>';
  }

  if ($valid == 1) {
    if (isset($_POST['add-product'])) {
      $datetime = date('Y-m-d h:i:s');

      $statement = $pdo->prepare("INSERT INTO products (seller_id, product_title, time_created, category_id, product_description, product_active)
                                  VALUES (?,?,?,?,?,?)");
      $statement->execute(array(
        strip_tags($_SESSION['seller']['seller_id']),
        strip_tags($_POST['product_title']),
        $datetime,
        strip_tags($_POST['category_id']),
        strip_tags($_POST['product_description']),
        0
      ));

      $statement = $pdo->prepare("SELECT * FROM products WHERE time_created = '$datetime'");
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      foreach ($result as $row) {
        $product_id = $row['product_id'];
      }
    } else if (isset($_POST['edit-product'])) {
      $statement = $pdo->prepare("UPDATE products SET product_title = ?, category_id = ?, product_description  = ? WHERE product_id = '$product_id'");
      $statement->execute(array(
        strip_tags($_POST['product_title']),
        strip_tags($_POST['category_id']),
        strip_tags($_POST['product_description'])
      ));

      $statement = $pdo->prepare("SELECT * FROM product_seasons WHERE product_id = '" . $product_id  . "'");
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (!empty($result)) {
        $statement = $pdo->prepare("DELETE FROM product_seasons WHERE product_id = '" . $product_id  . "'");
        $statement->execute();
      }

      $statement = $pdo->prepare("SELECT * FROM product_styles WHERE product_id = '" . $product_id  . "'");
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (!empty($result)) {
        $statement = $pdo->prepare("DELETE FROM product_styles WHERE product_id = '" . $product_id  . "'");
        $statement->execute();
      }

      $statement = $pdo->prepare("SELECT * FROM product_materials WHERE product_id = '" . $product_id  . "'");
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (!empty($result)) {
        $statement = $pdo->prepare("DELETE FROM product_materials WHERE product_id = '" . $product_id  . "'");
        $statement->execute();
      }

      $statement = $pdo->prepare("SELECT * FROM product_genders WHERE product_id = '" . $product_id  . "'");
      $statement->execute();
      $result = $statement->fetchAll(PDO::FETCH_ASSOC);
      if (!empty($result)) {
        $statement = $pdo->prepare("DELETE FROM product_genders WHERE product_id = '" . $product_id  . "'");
        $statement->execute();
      }
    }

    if ($_FILES['product_image']["name"] != '') {
      $image = 'product-' . $product_id . '.' . pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
      move_uploaded_file($_FILES['product_image']['tmp_name'], "../uploads/products/" . $image);
      $statement = $pdo->prepare("UPDATE products SET product_image = ? WHERE product_id = '$product_id'");
      $statement->execute(array($image));
    }

    foreach ($_POST['genders'] as $gender) {
      $statement = $pdo->prepare("INSERT INTO product_genders (product_id, gender_id) VALUES (?,?)");
      $statement->execute(array(
        $product_id,
        $gender
      ));
    }

    foreach ($_POST['materials'] as $material) {
      $statement = $pdo->prepare("INSERT INTO product_materials (product_id, material_id) VALUES (?,?)");
      $statement->execute(array(
        $product_id,
        $material
      ));
    }

    foreach ($_POST['styles'] as $style) {
      $statement = $pdo->prepare("INSERT INTO product_styles (product_id, style_id) VALUES (?,?)");
      $statement->execute(array(
        $product_id,
        $style
      ));
    }

    foreach ($_POST['seasons'] as $season) {
      $statement = $pdo->prepare("INSERT INTO product_seasons (product_id, season_id) VALUES (?,?)");
      $statement->execute(array(
        $product_id,
        $season
      ));
    }

    header("location: products.php?product_id=" . $product_id);
  }
}

if (isset($_POST['make-public'])) {
  $statement = $pdo->prepare("SELECT * FROM product_options WHERE product_id='$product_id'");
  $statement->execute();
  $options = $statement->fetchAll(PDO::FETCH_ASSOC);
  if (count($options) == 0) {
    $message_public .= "Добавьте лишь 1 вариант товара для продажи<br>";
  } else {
    $statement = $pdo->prepare("UPDATE products SET product_active = ? WHERE product_id = '$product_id'");
    $statement->execute(array(1));
    header("location: products.php?product_id=" . $product_id);
  }
}

if (isset($_POST['make-private'])) {
  $statement = $pdo->prepare("UPDATE products SET product_active = ? WHERE product_id = '$product_id'");
  $statement->execute(array(0));
  header("location: products.php?product_id=" . $product_id);
}

if (isset($_POST['option-add'])) {
  $valid = 1;

  if (!isset($_POST['color_id'])) {
    $valid = 0;
    $error_message .= "Цвет не должен быть пустым<br>";
  }

  if (!isset($_POST['size_id'])) {
    $valid = 0;
    $error_message .= "Размер не должен быть пустым<br>";
  }

  if (empty($_POST['amount_available'])) {
    $valid = 0;
    $error_message .= "Количество не должно быть пустым<br>";
  }

  if (empty($_POST['option_price'])) {
    $valid = 0;
    $error_message .= "Стоимость не должна быть пустым<br>";
  }

  if ($valid == 1) {
    $statement = $pdo->prepare("INSERT INTO product_options (product_id, color_id, size_id, amount_available, option_price)
                                VALUES (?,?,?,?,?)");
    $statement->execute(array(
      $product_id,
      strip_tags($_POST['color_id']),
      strip_tags($_POST['size_id']),
      strip_tags($_POST['amount_available']),
      strip_tags($_POST['option_price'])
    ));

    $statement = $pdo->prepare("SELECT SUM(product_options.amount_selled), SUM(product_options.amount_available) FROM products
                                JOIN product_options ON product_options.product_id=products.product_id
                                WHERE products.product_id=?");
    $statement->execute(array(
      $product_id
    ));
    $product = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $pdo->prepare("UPDATE products SET amount_selled=?, amount_available=? WHERE product_id=?");
    $statement->execute(array(
      $product['SUM(product_options.amount_selled)'],
      $product['SUM(product_options.amount_available)'],
      $product_id
    ));

    header("location: products.php?product_id=" . $product_id);
  }
}

if (isset($_POST['option-edit'])) {
  $statement = $pdo->prepare("UPDATE product_options SET color_id=?, size_id=?, amount_available=?, option_price=? WHERE option_id='$option_id'");
  $statement->execute(array(
    strip_tags($_POST['color_id']),
    strip_tags($_POST['size_id']),
    strip_tags($_POST['amount_available']),
    strip_tags($_POST['option_price'])
  ));

  $statement = $pdo->prepare("SELECT SUM(product_options.amount_selled), SUM(product_options.amount_available) FROM products
  JOIN product_options ON product_options.product_id=products.product_id
  WHERE products.product_id=?");
  $statement->execute(array(
    $product_id
  ));
  $product = $statement->fetch(PDO::FETCH_ASSOC);

  $statement = $pdo->prepare("UPDATE products SET amount_selled=?, amount_available=? WHERE product_id=?");
  $statement->execute(array(
    $product['SUM(product_options.amount_selled)'],
    $product['SUM(product_options.amount_available)'],
    $product_id
  ));

  header("location: products.php?product_id=" . $product_id);
}

if (isset($_POST['option-delete'])) {
  $statement = $pdo->prepare("DELETE FROM product_options WHERE option_id='$option_id'");
  $statement->execute();
  header("location: products.php?product_id=" . $product_id);
}

if (isset($_POST['delete-product'])) {
  $statement = $pdo->prepare("SELECT * FROM product_seasons WHERE product_id = '" . $product_id  . "'");
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($result)) {
    $statement = $pdo->prepare("DELETE FROM product_seasons WHERE product_id = '" . $product_id  . "'");
    $statement->execute();
  }

  $statement = $pdo->prepare("SELECT * FROM product_styles WHERE product_id = '" . $product_id  . "'");
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($result)) {
    $statement = $pdo->prepare("DELETE FROM product_styles WHERE product_id = '" . $product_id  . "'");
    $statement->execute();
  }

  $statement = $pdo->prepare("SELECT * FROM product_materials WHERE product_id = '" . $product_id  . "'");
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($result)) {
    $statement = $pdo->prepare("DELETE FROM product_materials WHERE product_id = '" . $product_id  . "'");
    $statement->execute();
  }

  $statement = $pdo->prepare("SELECT * FROM product_genders WHERE product_id = '" . $product_id  . "'");
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
  if (!empty($result)) {
    $statement = $pdo->prepare("DELETE FROM product_genders WHERE product_id = '" . $product_id  . "'");
    $statement->execute();
  }

  $statement = $pdo->prepare("SELECT * FROM products WHERE product_id = '" . $product_id  . "'");
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
  foreach ($result as $row) {
    unlink('../../uploads/products/' . $row['product_image']);
  }
  $statement = $pdo->prepare("DELETE FROM products WHERE product_id = '" . $product_id  . "'");
  $statement->execute();

  header("location: products.php?filter=instock");
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
          <li class="menu-item active open">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
              <i class="menu-icon tf-icons bx bx-collection"></i>
              <div data-i18n="Products">Товары</div>
            </a>
            <ul class="menu-sub">
              <li class="menu-item <?php if ($_GET['filter'] == "instock") echo 'active'; ?>">
                <a href="products.php?filter=instock" class="menu-link">
                  <div data-i18n="In Stock">В наличии</div>
                </a>
              </li>
              <li class="menu-item <?php if ($_GET['filter'] == "outofstock") echo 'active'; ?>">
                <a href="products.php?filter=outofstock" class="menu-link">
                  <div data-i18n="Out Of Stock">Распроданные</div>
                </a>
              </li>
              <li class="menu-item <?php if ($_GET['filter'] == "draft") echo 'active'; ?>">
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

            <?php if ($option_id) { ?>
              <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Продажа / <a href="products.php">Товары</a> /</span>
                <a href="products.php?product_id=<?php echo $product_id; ?>">Товар № <?php echo $product_id; ?></a>
              </h4>

              <?php
              $statement = $pdo->prepare("SELECT * FROM product_options WHERE option_id = '" . $option_id . "'");
              $statement->execute();
              $option = $statement->fetch(PDO::FETCH_ASSOC);

              $statement = $pdo->prepare("SELECT * FROM products WHERE product_id = '" . $product_id . "'");
              $statement->execute();
              $product = $statement->fetch(PDO::FETCH_ASSOC);
              ?>

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
                          <h5 class="card-header"><?php echo $product['product_title']; ?></h5>
                          <div class="card-body">
                            <div class="mb-3 row">
                              <label for="color_id" class="col-md-3 col-form-label">Цвет</label>
                              <div class="col-md-9">
                                <select class="form-select" id="color_id" name="color_id" required>
                                  <?php
                                  $statement = $pdo->prepare("SELECT * FROM colors ORDER BY color_name");
                                  $statement->execute();
                                  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                  foreach ($result as $row) {
                                    echo '<option ';
                                    if ($row['color_id'] == $option['color_id']) echo 'selected ';
                                    echo 'value="' . $row['color_id'] . '">' . $row['color_name'] . '</option>';
                                  } ?>
                                </select>
                              </div>
                            </div>
                            <div class="mb-3 row">
                              <label for="color_id" class="col-md-3 col-form-label">Размер</label>
                              <div class="col-md-9">
                                <select class="form-select" id="size_id" name="size_id" required>
                                  <?php
                                  $statement = $pdo->prepare("SELECT * FROM sizes ORDER BY size_id");
                                  $statement->execute();
                                  $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                  foreach ($result as $row) {
                                    echo '<option ';
                                    if ($row['size_id'] == $option['size_id']) echo 'selected ';
                                    echo 'value="' . $row['size_id'] . '">' . $row['size_letter'];
                                    if ($row['size_id'] != 0) echo ' (' . $row['size_number'] . ')';
                                    echo '</option>';
                                  } ?>
                                </select>
                              </div>
                            </div>
                            <div class="mb-3 row">
                              <label for="color_id" class="col-md-3 col-form-label">Стоимость</label>
                              <div class="col-md-9">
                                <div class="input-group input-group-merge">
                                  <span class="input-group-text">₽</span>
                                  <input class="form-control" type="number" id="option_price" name="option_price" value="<?php echo $option['option_price']; ?>" aria-label="option_price" required>
                                </div>
                              </div>
                            </div>
                            <div class="mb-3 row">
                              <label for="color_id" class="col-md-3 col-form-label">Количество в наличии</label>
                              <div class="col-md-9">
                                <input class="form-control" type="number" id="amount_available" name="amount_available" value="<?php echo $option['amount_available']; ?>" required>
                              </div>
                            </div>
                            <div class="mb-3 row">
                              <?php if ((isset($error_message)) && ($error_message != '')) :
                                echo '<p class="mb-4"><div class="error">' . $error_message . '</div></p>';
                              endif; ?>
                              <div class="mt-2">
                                <button type="submit" class="btn btn-primary me-2" name="option-edit">Сохранить изменения</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-2">
                    <p class="demo-inline-spacing">
                      <button class="btn btn-danger me-1" type="button" data-bs-toggle="collapse" data-bs-target="#product-delete-card" aria-expanded="false" aria-controls="product-delete-card">
                        Удалить вариант
                      </button>
                    </p>
                    <div class="collapse" id="product-delete-card">
                      <div class="card">
                        <div class="card-body">
                          <div class="alert alert-warning">
                            <h6 class="alert-heading fw-bold mb-1">Внимание!</h6>
                            <p class="mb-0">Данное действие не возвращается.</p>
                          </div>
                          <button type="submit" class="btn btn-danger deactivate-account" name="option-delete">Удалить</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            <?php } else if (isset($_GET["product_id"])) { ?>
              <h4 class="fw-bold py-3">
                <span class="text-muted fw-light">
                  <a href="products.php?filter=instock">Товары</a> /
                </span>
                <?php if ($_GET["product_id"] == 'new') echo 'Новый товар';
                else echo 'Товар № ' . $product_id; ?>
              </h4>

              <form class="mb-3" method="POST" enctype="multipart/form-data">
                <?php $csrf->echoInputField(); ?>
                <?php if ($_GET["product_id"] == 'new') echo '<div class="card mb-4">';
                else { ?>
                  <div class="row mb-2">
                    <div class="col-md">
                      <div class="card mb-3">
                        <div class="row g-0">
                          <div class="col-md-3">
                            <img class="card-img card-img-left" src="../uploads/products/<?php echo $product['product_image']; ?>" alt="<?php echo $product['product_title']; ?>" />
                          </div>
                          <div class="col-md-9">
                          <?php } ?>
                          <h5 class="card-header">Базовая информация</h5>
                          <div class="card-body">
                            <div class="mb-3 row">
                              <label for="product_title" class="col-md-2 col-form-label">Название</label>
                              <div class="col-md-10">
                                <input type="text" class="form-control" id="product_title" name="product_title" value="<?php
                                                                                                                        if ($product_id != 'new') echo $product['product_title'];
                                                                                                                        else if (isset($_POST['product_title'])) echo $_POST['product_title'];
                                                                                                                        ?>" required>
                              </div>
                            </div>

                            <div class="mb-3 row">
                              <label for="category_id" class="col-md-2 col-form-label">Категория</label>
                              <div class="col-md-10">
                                <select class="form-select" id="category_id" name="category_id" required>
                                  <?php
                                  if ($_GET["product_id"] == 'new') echo '<option selected value="0">Категория</option>';
                                  $statement = $pdo->prepare("SELECT * FROM categories ORDER BY category_name");
                                  $statement->execute();
                                  $categories = $statement->fetchAll(PDO::FETCH_ASSOC);
                                  foreach ($categories as $category) {
                                    echo '<option ';
                                    if ($category['category_id'] == $product['category_id'] && $_GET["product_id"] != 'new') echo 'selected ';
                                    echo 'value="' . $category['category_id'] . '">' . $category['category_name'] . '</option>';
                                  }
                                  ?>
                                </select>
                              </div>
                            </div>

                            <div class="mb-3 row">
                              <label for="product_description" class="col-md-2 col-form-label">Описание</label>
                              <div class="col-md-10">
                                <textarea class="form-control" id="product_description" name="product_description" rows="3" value="<?php
                                                                                                                                    if ($_GET["product_id"] != 'new') echo $product['product_description'];
                                                                                                                                    else if (isset($_POST['product_description'])) echo $_POST['product_description'];
                                                                                                                                    ?>"><?php
                                                                                                                                        if ($_GET["product_id"] != 'new') echo $product['product_description'];
                                                                                                                                        else if (isset($_POST['product_description'])) echo $_POST['product_description'];
                                                                                                                                        ?>
                                                                                                                                    </textarea>
                              </div>
                            </div>

                            <div class="mb-3 row">
                              <label for="product_image" class="col-md-2 col-form-label">Фото</label>
                              <div class="col-md-10">
                                <input class="form-control" type="file" accept="image/*" id="product_image" name="product_image">
                              </div>
                            </div>

                            <?php if ($_GET["product_id"] != 'new') { ?>
                              <div class="mb-3 row">
                                <label for="product_active" class="col-md-2 col-form-label">Статус</label>
                                <div class="col-md-10">
                                  <p class="card-text">
                                    <?php if ($product['product_active'] == 1) {
                                      echo '<span class="badge bg-label-primary me-1">Публикован</span>';
                                      echo '<button type="submit" class="btn btn-sm btn-warning" name="make-private">В черновик</button>';
                                    } else {
                                      echo '<span class="badge bg-label-warning me-1">Черновик</span>';
                                      echo '<button type="submit" class="btn btn-sm btn-primary" name="make-public">Опубликовать</button>';
                                    } ?>
                                    <?php if (isset($message_public)) {
                                      echo '<div class="alert alert-danger" role="alert">' . $message_public . '</div>';
                                    } ?>
                                  </p>
                                </div>
                              </div>
                            <?php } ?>

                          </div>

                          <?php
                          if ($_GET["product_id"] == 'new') echo '</div>';
                          else { ?>

                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <?php if ($_GET["product_id"] != 'new') { ?>
                  <div class="card mb-4">
                    <h5 class="card-header">Варианты</h5>
                    <div class="card-body">
                      <?php if (count($options) != 0) { ?>
                        <div class="table-responsive text-nowrap">
                          <table class="table table-bordered">
                            <thead>
                              <tr>
                                <th>Цвет</th>
                                <th>Размер</th>
                                <th>Стоимость</th>
                                <th>Количество</th>
                                <th>Заказано</th>
                                <th>Действия</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($options as $option) { ?>
                                <tr>
                                  <td><?php echo $option['color_name']; ?></td>
                                  <td><?php echo $option['size_letter'];
                                      if ($option['size_id'] != 0) echo ' (' . $option['size_number'] . ')'; ?></td>
                                  <td>₽ <?php echo $option['option_price']; ?></td>
                                  <td><?php echo $option['amount_available']; ?></td>
                                  <td><?php echo $option['amount_selled']; ?></td>
                                  <td><a class="btn btn-sm btn-secondary" href="products.php?product_id=<?php echo $product_id; ?>&option_id=<?php echo $option['option_id']; ?>"><i class="bx bx-edit-alt me-1"></i> Редактировать</a></td>
                                </tr>
                              <?php } ?>
                            </tbody>
                            <tfoot class="table-border-bottom-0">
                              <tr>
                                <th>
                                  <?php
                                  $statement = $pdo->prepare("SELECT COUNT(DISTINCT color_id) FROM product_options WHERE product_id='$product_id'");
                                  $statement->execute();
                                  $result = $statement->fetch(PDO::FETCH_ASSOC);
                                  echo $result['COUNT(DISTINCT color_id)'];
                                  ?>
                                  цвет(ов)
                                </th>
                                <th>
                                  <?php
                                  $statement = $pdo->prepare("SELECT COUNT(DISTINCT size_id) FROM product_options WHERE product_id='$product_id'");
                                  $statement->execute();
                                  $result = $statement->fetch(PDO::FETCH_ASSOC);
                                  echo $result['COUNT(DISTINCT size_id)'];
                                  ?>
                                  размер(ов)
                                </th>
                                <th></th>
                                <th>
                                  <?php
                                  $statement = $pdo->prepare("SELECT SUM(amount_available) FROM product_options WHERE product_id='$product_id'");
                                  $statement->execute();
                                  $result = $statement->fetch(PDO::FETCH_ASSOC);
                                  echo $result['SUM(amount_available)'];
                                  ?>
                                  в наличии
                                </th>
                                <th>
                                  <?php
                                  $statement = $pdo->prepare("SELECT SUM(amount_selled) FROM product_options WHERE product_id='$product_id'");
                                  $statement->execute();
                                  $result = $statement->fetch(PDO::FETCH_ASSOC);
                                  echo $result['SUM(amount_selled)'];
                                  ?>
                                  заказанных
                                </th>
                                <th></th>
                              </tr>
                            </tfoot>
                          </table>
                        </div>
                      <?php } ?>

                      <p class="demo-inline-spacing">
                        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#option-new" aria-expanded="false" aria-controls="option-new">
                          Новый вариант
                        </button>
                      </p>
                      <div class="collapse" id="option-new">
                        <div class="table-responsive text-nowrap">
                          <table class="table table-bordered">
                            <thead>
                              <tr>
                                <th>Цвет</th>
                                <th>Размер</th>
                                <th>Стоимость</th>
                                <th>Количество</th>
                                <th>Действия</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td>
                                  <select class="form-select" id="color_id" name="color_id">
                                    <?php
                                    $statement = $pdo->prepare("SELECT * FROM colors ORDER BY color_name");
                                    $statement->execute();
                                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($result as $row) {
                                      echo '<option ';
                                      if ($row['color_id'] == 0) echo 'selected ';
                                      echo 'value="' . $row['color_id'] . '">' . $row['color_name'] . '</option>';
                                    } ?>
                                  </select>
                                </td>
                                <td>
                                  <select class="form-select" id="size_id" name="size_id">
                                    <?php
                                    $statement = $pdo->prepare("SELECT * FROM sizes ORDER BY size_id");
                                    $statement->execute();
                                    $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($result as $row) {
                                      echo '<option ';
                                      if ($row['size_id'] == 0) echo 'selected ';
                                      echo 'value="' . $row['size_id'] . '">' . $row['size_letter'];
                                      if ($row['size_id'] != 0) echo ' (' . $row['size_number'] . ')';
                                      echo '</option>';
                                    } ?>
                                  </select>
                                </td>
                                <td>
                                  <div class="input-group input-group-merge">
                                    <span class="input-group-text">₽</span>
                                    <input class="form-control" type="number" id="option_price" name="option_price" aria-label="option_price">
                                  </div>
                                </td>
                                <td><input class="form-control" type="number" id="amount_available" name="amount_available"></td>
                                <td><button type="submit" class="btn btn-sm btn-primary" name="option-add">Добавить</button></td>
                              </tr>
                            </tbody>
                          </table>

                          <?php if ((isset($error_message)) && ($error_message != '')) :
                            echo '<p class="mb-4"><div class="error">' . $error_message . '</div></p>';
                          endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php } ?>

                <div class="card mb-4">
                  <h5 class="card-header">Характеристики</h5>
                  <div class="card-body">
                    <div class="mb-3 row">
                      <label for="genders" class="col-md-2 col-form-label">Пол</label>
                      <div class="col-md-10">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM products
                                                    JOIN product_genders ON product_genders.product_id=products.product_id
                                                    WHERE products.product_id = '" . $product_id . "'");
                        $statement->execute();
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

                        $statement = $pdo->prepare("SELECT * FROM genders ORDER BY genders.gender_id");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) { ?>
                          <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="genders[]" value="<?php echo $row['gender_id']; ?>" id="<?php echo $row['gender_id']; ?>" <?php foreach ($products as $p) {
                                                                                                                                                                              if ($row['gender_id'] == $p['gender_id']) echo 'checked';
                                                                                                                                                                            } ?> />
                            <label class="form-check-label" for="genders"> <?php echo $row['gender_title']; ?> </label>
                          </div>
                        <?php } ?>
                      </div>
                    </div>
                    <hr class="m-0" />
                    <div class="mb-3 row">
                      <label for="material" class="col-md-2 col-form-label">Материал</label>
                      <div class="col-md-10">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM products
                                                    JOIN product_materials ON product_materials.product_id=products.product_id
                                                    WHERE products.product_id = '" . $product_id . "'");
                        $statement->execute();
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

                        $statement = $pdo->prepare("SELECT * FROM materials ORDER BY material_name");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                        ?>
                          <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="materials[]" value="<?php echo $row['material_id']; ?>" id="<?php echo $row['material_id']; ?>" <?php foreach ($products as $p) {
                                                                                                                                                                                    if ($row['material_id'] == $p['material_id']) echo 'checked';
                                                                                                                                                                                  } ?> />
                            <label class="form-check-label" for="material"> <?php echo $row['material_name']; ?> </label>
                          </div>
                        <?php
                        }
                        ?>
                      </div>
                    </div>
                    <hr class="m-0" />
                    <div class="mb-3 row">
                      <label for="style" class="col-md-2 col-form-label">Стиль</label>
                      <div class="col-md-10">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM products
                                                    JOIN product_styles ON product_styles.product_id=products.product_id
                                                    WHERE products.product_id = '" . $product_id . "'");
                        $statement->execute();
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

                        $statement = $pdo->prepare("SELECT * FROM styles ORDER BY style_name");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                        ?>
                          <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="styles[]" value="<?php echo $row['style_id']; ?>" id="<?php echo $row['style_id']; ?>" <?php foreach ($products as $p) {
                                                                                                                                                                            if ($row['style_id'] == $p['style_id']) echo 'checked';
                                                                                                                                                                          } ?> />
                            <label class="form-check-label" for="style"> <?php echo $row['style_name']; ?> </label>
                          </div>
                        <?php
                        }
                        ?>
                      </div>
                    </div>
                    <hr class="m-0" />
                    <div class="mb-3 row">
                      <label for="season" class="col-md-2 col-form-label">Сезон</label>
                      <div class="col-md-10">
                        <?php
                        $statement = $pdo->prepare("SELECT * FROM products
                                                    JOIN product_seasons ON product_seasons.product_id=products.product_id
                                                    WHERE products.product_id = '" . $product_id . "'");
                        $statement->execute();
                        $products = $statement->fetchAll(PDO::FETCH_ASSOC);

                        $statement = $pdo->prepare("SELECT * FROM seasons ORDER BY season_id");
                        $statement->execute();
                        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                        ?>
                          <div class="form-check form-check-inline mt-3">
                            <input class="form-check-input" type="checkbox" name="seasons[]" value="<?php echo $row['season_id']; ?>" id="<?php echo $row['season_id']; ?>" <?php foreach ($products as $p) {
                                                                                                                                                                              if ($row['season_id'] == $p['season_id']) echo 'checked';
                                                                                                                                                                            } ?> />
                            <label class="form-check-label" for="season"> <?php echo $row['season_name']; ?> </label>
                          </div>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                </div>

                <p class="mb-4">
                  <?php if ((isset($error_message)) && ($error_message != '')) :
                    echo '<div class="error">' . $error_message . '</div>';
                  endif; ?>
                </p>

                <div class="demo-inline-spacing">
                  <?php if ($_GET["product_id"] == 'new') { ?>
                    <button type="submit" class="btn btn-primary me-2" name="add-product">Добавить товар</button>
                  <?php } else { ?>
                    <button type="submit" class="btn btn-primary me-2" name="edit-product">Сохранить изменения</button>

                    <button class="btn btn-outline-danger me-1" type="button" data-bs-toggle="collapse" data-bs-target="#product-delete-card" aria-expanded="false" aria-controls="product-delete-card">
                      Удалить товар
                    </button>
                  <?php } ?>
                </div>

                <div class="collapse mt-3" id="product-delete-card">
                  <div class="card">
                    <div class="card-body">
                      <div class="alert alert-warning">
                        <h6 class="alert-heading fw-bold mb-1">Внимание!</h6>
                        <p class="mb-0">Данное действие не возвращается.</p>
                      </div>

                      <button type="submit" class="btn btn-danger deactivate-account" name="delete-product">Удалить</button>
                    </div>
                  </div>
                </div>
              </form>

            <?php } else { ?>
              <h4 class="fw-bold py-3">
                Товары
              </h4>

              <div class="row mb-3">

                <div class="col-sm-8">
                  <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                      <a class="nav-link <?php if ($_GET['filter'] == "instock") echo "active"; ?>" href="products.php?filter=instock">
                        В наличии
                        <?php if ($_GET['filter'] == "instock") echo '<span class="badge bg-white text-primary rounded-pill">' . $total . '</span>'; ?>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link <?php if ($_GET['filter'] == "outofstock") echo "active"; ?>" href="products.php?filter=outofstock">
                        Распроданные
                        <?php if ($_GET['filter'] == "outofstock") echo '<span class="badge bg-white text-primary rounded-pill">' . $total . '</span>'; ?>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link <?php if ($_GET['filter'] == "draft") echo "active"; ?>" href="products.php?filter=draft">
                        Не публикованные
                        <?php if ($_GET['filter'] == "draft") echo '<span class="badge bg-white text-primary rounded-pill">' . $total . '</span>'; ?>
                      </a>
                    </li>
                  </ul>
                </div>

                <div class="col-sm-4 text-end">
                  <a href="products.php?product_id=new">
                    <button type="button" class="btn btn-primary me-2">Добавить новый товар</button>
                  </a>
                </div>
              </div>

              <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Фото</th>
                      <th>Название</th>
                      <th>Категория</th>
                      <th>В наличии</th>
                      <th>Заказанных</th>
                    </tr>
                  </thead>
                  <tbody class="table-border-bottom-0">
                    <?php
                    foreach ($products as $product) {
                    ?>
                      <tr>
                        <td>
                          <img src="../uploads/products/<?php echo $product['product_image']; ?>" alt="<?php echo $product['product_title']; ?>" class="d-block rounded" height="100" id="product_image" />
                        </td>
                        <td><a href="products.php?product_id=<?php echo $product['product_id']; ?>"><strong><?php echo $product['product_title']; ?></strong></a></td>
                        <td><?php echo $product['category_name']; ?></td>
                        <td><?php echo $product['amount_available']; ?></td>
                        <td><?php echo $product['amount_selled']; ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
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