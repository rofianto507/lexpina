<?php
session_start();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://*.tile.openstreetmap.org;");
include_once("config/configuration.php");
$login_message = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
  if ( empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $login_message = "<center><div class='alert alert-danger border-danger alert-dismissible'>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>&times;</button>
                <b>Invalid request. Please try again.</b>
              </div></center>";
        // Regenerate token baru setelah gagal
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $user = trim($_POST["username"]);
        $pass = trim($_POST["password"]);

        if (!empty($user) && !empty($pass) && strlen($user) <= 50 && strlen($pass) <= 255) {
            $stmt = $pdo->prepare("SELECT users.* FROM users WHERE username = ?");
            $stmt->execute([$user]);
            if ($data = $stmt->fetch()) {
                if (password_verify($pass, $data["password"])) {
                    session_regenerate_id(true);

                    $_SESSION["username"] = $data["username"];
                    $_SESSION["nama"]     = $data["nama"];
                    $_SESSION["foto"]     = $data["foto"];
                    $_SESSION["akses"]    = $data["akses"];
                    $_SESSION["id"]       = $data["id"];
                    $_SESSION["last_login"] = time();
                     // Hapus CSRF token dari session setelah login sukses
                    unset($_SESSION['csrf_token']);

                    $stmtUpdate = $pdo->prepare("UPDATE users SET updated_at=NOW() WHERE id=?");
                    $stmtUpdate->execute([$data["id"]]);

                    if ($data["akses"] == "POLRES") {
                        $qry = $pdo->prepare("SELECT id FROM polress WHERE user_id=? LIMIT 1");
                        $qry->execute([$data["id"]]);
                        $_SESSION["polres_id"] = $qry->fetchColumn();
                    }
                    if ($data["akses"] == "POLSEK") {
                        $qry = $pdo->prepare("SELECT id FROM polseks WHERE user_id=? LIMIT 1");
                        $qry->execute([$data["id"]]);
                        $_SESSION["polsek_id"] = $qry->fetchColumn();
                    }

                    // Redirect dengan header (bukan meta refresh)
                    if ($data["akses"] == "POLSEK") {
                        header("Location: app/indexpolsek");
                    } elseif ($data["akses"] == "POLRES") {
                        header("Location: app/indexpolres");
                    } elseif ($data["akses"] == "POLDA") {
                        header("Location: app/index");
                    } else {
                        header("Location: app/indexsubdit");
                    }
                    exit;
                } else {
                    // Regenerate token baru setelah gagal
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $login_message = "<center><div class='alert alert-danger border-danger alert-dismissible'>
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>&times;</button>
                            <b>Invalid username or password</b>
                          </div></center>";
                }
            } else {
                // Regenerate token baru setelah gagal
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $login_message = "<center><div class='alert alert-danger border-danger alert-dismissible'>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>&times;</button>
                        <b>Invalid username or password</b>
                      </div></center>";
            }
        } else {
            // Regenerate token baru setelah gagal
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $login_message = "<center><div class='alert alert-danger border-danger alert-dismissible'>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'>&times;</button>
                    <b>Username and password cannot be empty</b>
                  </div></center>";
        }
   }
}

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
 
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PetaDigi | Login</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicons/favicon.ico">
    <link rel="manifest" href="assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="assets/js/config.js"></script>
    <script src="vendors/overlayscrollbars/OverlayScrollbars.min.js"></script>
   <link rel="stylesheet" type="text/css" href="assets/icon/font-awesome/css/font-awesome.min.css">
   <link href="vendors/overlayscrollbars/OverlayScrollbars.min.css" rel="stylesheet">
    <link href="assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="assets/css/user.min.css" rel="stylesheet" id="user-style-default">
  </head>


  <body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
      <div class="container-fluid">
        <div class="row min-vh-100 flex-center g-0">
          <div class="col-lg-8 col-xxl-5 py-3 position-relative"><img class="bg-auth-circle-shape" src="assets/img/icons/spot-illustrations/bg-shape.png" alt="" width="250"><img class="bg-auth-circle-shape-2" src="assets/img/icons/spot-illustrations/shape-1.png" alt="" width="150">
            <div class="card overflow-hidden z-index-1">
              <div class="card-body p-0">
                <div class="row g-0 h-100">
                  <div class="col-md-5 text-center bg-card-gradient">
                    <div class="position-relative p-4 pt-md-5 pb-md-7 light">
                        <div class="bg-holder bg-auth-card-shape bg-auth-card-shape--half-circle">
                      </div>
                      <!--/.bg-holder-->

                        <div class="z-index-1 position-relative">
                        <a class="mb-4 d-inline-block" href="">
                          <img src="assets/img/icon.png" alt="Logo" class="logo-image">
                        </a>
                        <h3 class="text-white"><?php echo htmlspecialchars($app_name); ?></h3>
                        <p class="opacity-75 text-white"><?php echo htmlspecialchars($long_description); ?></p>
                        </div>
                    </div>
                    <div class="mt-3 mb-4 mt-md-4 mb-md-5 light">
                      
                      <p class="mb-0 mt-4 mt-md-5 fs--1 fw-semi-bold text-white opacity-75">Read our <a class="text-decoration-underline text-white" href="#!">terms</a> and <a class="text-decoration-underline text-white" href="#!">conditions </a></p>
                    </div>
                  </div>
                  <div class="col-md-7 d-flex flex-center">
                    
                    <div class="p-4 p-md-5 flex-grow-1">
                      <div class="row flex-between-center">
                        <div class="col-auto">
                          <h3>Account Login</h3>
                        </div>
                      </div>
                       <?php echo $login_message; ?>
                       <form  method="post" action="">
                         <input type="hidden" name="csrf_token"
                          value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                          <label class="form-label" for="card-email">Username</label>
                          <input class="form-control" id="card-email" name="username" type="text" autocomplete="username" />
                        </div>
                        <div class="mb-3">
                          <div class="d-flex justify-content-between">
                            <label class="form-label" for="card-password">Password</label>
                          </div>
                          <input class="form-control" id="card-password" name="password" type="password" autocomplete="current-password" />
                        </div>
                         
                        <div class="mb-3">
                          <button class="btn btn-primary d-block w-100 mt-3" type="submit" name="submit">Log in</button>
                        </div>
                         
                      </form>
                       
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->

 
    <script src="vendors/popper/popper.min.js"></script>
    <script src="vendors/bootstrap/bootstrap.min.js"></script>
    <script src="vendors/anchorjs/anchor.min.js"></script>
    <script src="vendors/is/is.min.js"></script>
    <script src="vendors/lodash/lodash.min.js"></script>
    <script src="assets/js/theme.js"></script>

  </body>

</html>