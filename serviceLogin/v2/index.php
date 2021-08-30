<?php
  session_start();

  $callback = $_GET['callback'];
  $modal_type = $_GET['type'];

  function email_exists($email) {
    $data = json_decode(file_get_contents('/home/runner/accounts/data/data.json'), true);

    foreach($data['accounts'] as $user) {
      if($user['email'] == $email) {
        return key($data['accounts']);
      }
      next($data['accounts']);
    }
    return false;
  }

  if($_SESSION['account_id']) {
    if(!$callback) {
      $callback = 'https://danjaye.lol';
    }
    header('location: '.$callback);
  }
?>
<!DOCTYPE html>
<html>
  <head>
    <link rel = 'stylesheet' href = '/style.css' />
    <title>DanAuth - <?php echo ucfirst($modal_type); ?></title>
  </head>
  <body>
    <main>
      <div class = 'wrapper'>
        <div class = 'login-modal'>
          <div>
            <?php
              $data = json_decode(file_get_contents('/home/runner/accounts/data/data.json'), true);

              $messages = array();
              if($modal_type == 'signin') {
                $messages['header'] = "Sign In";
                $messages['info'] = "Use your DanAuth account";
                if(isset($_POST['email']) and isset($_POST['password'])) {
                  $loginproc = true;
                  $userid = email_exists($_POST['email']);
                  if(email_exists($_POST['email'])) {
                    if(password_verify($_POST['password'], $data['accounts'][$userid]['password'])) {
                      $_SESSION['account_id'] = $userid;
                      header('location: https://danjaye.lol');
                    }
                    else {
                      $messages['info'] = "Incorrect password.";
                    }
                  }
                  else {
                    $messages['info'] = "That account doesn't exist.";
                  }
                  $messages['header'] = "Couldn't log in";
                }
              }
              else {
                if($modal_type == 'signup') {
                  $messages['header'] = "Create an account";
                  if(isset($_POST['username']) and isset($_POST['password'])) {
                    $userid = email_exists($_POST['email']);
                    if(!$userid) {
                      $userid = (time() * 1000);
                      $data['accounts'][$userid]['username'] = $_POST['username'];
                      $data['accounts'][$userid]['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                      $data['accounts'][$userid]['email'] = $_POST['email'];
                      file_put_contents('/home/runner/accounts/data/data.json', json_encode($data));
                      $_SESSION['account_id'] = $userid;
                      if(!$callback) {
                        $callback = 'https://danjaye.lol';
                      }
                      header('location: '.$callback);
                    }
                    else {
                      header('location: /serviceLogin/v2?type=signin');
                    }
                  }
                }
                else {
                  $messages['header'] = "404";
                  $messages['info'] = "That's an error. Wrong page.";
                }
              }
              
              $messages['icon'] = 'https://danjaye.lol/dan_logo_large_tester.png';

              echo "<img class = 'logo' src = '".$messages['icon']."'><h1>".$messages['header']."</h1><p>".$messages['info']."</p>";
            ?>
          </div>
          <?php
            if(!$loginproc) {
              if($modal_type == 'signin') {
                echo "<form method = 'post'><div style = 'margin-top: 15px;'><input name = 'email' type = 'email' placeholder = 'Email' style = 'margin-bottom: 5px;' value = '".$_POST['email']."'><input name = 'password' type = 'password' placeholder = 'Password' type = 'password' value = '".$_POST['password']."'></div><div style = 'margin-top: 15px;'><button>Continue</button></div></form><div style = 'margin-top: 10px;'><small>Don't have an account? <a href = '?type=signup&callback=".$callback."'>Create one</a></small></div>";
              }
              else {
                if($modal_type = 'signup') {
                  echo "<form method = 'post'><div style = 'margin-top: 15px;'><input name = 'email' type = 'email' placeholder = 'Email' style = 'margin-bottom: 5px;'><input name = 'username' placeholder = 'Username' style = 'margin-bottom: 5px;'><input name = 'password' type = 'password' placeholder = 'Password' type = 'password'></div><div style = 'margin-top: 15px;'><button>Continue</button></div></form><div style = 'margin-top: 10px;'><small>Or <a href = '?type=signin&callback=".$callback."'>sign in</a> instead</small></div>";
                }
              }
            }
          ?>
        </div>
        <div class = 'toolbar'>
          <a href = 'https://www.danjaye.lol/posts?d=2675876317844'>Privacy</a>
          <a href = 'https://www.danjaye.lol/posts?d=9672985260'>Terms</a>
        </div>
      </div>
    </main>
  </body>
</html>
