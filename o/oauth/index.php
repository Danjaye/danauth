<?php
  session_start();

  $response_uri = $_GET['response_uri'];
  $application_id = $_GET['application_id'];
  $modal_type = $_GET['type'];
  $scopes = explode(' ', urldecode($_GET['scopes']));
  $token = $_GET['token'];

  $data = json_decode(file_get_contents('/home/runner/accounts/data/data.json'), true);

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

  if($_SESSION['account_id'] and $application_id) {
    if($modal_type == 'auth') {
      header('location: '.$response_uri.'?token='.$token.'&account_id='.$_SESSION['account_id']);
    }
    elseif($modal_type == 'consent') {
      $i = 0;
      $authorized_scopes = 0;
      foreach($data['applications'][$application_id]['scopes'][$_SESSION['account_id']] as $authorized_scope) {
        if($authorized_scope == $scopes[$i]) {
          $authorized_scopes++;
        }
        $i++;
      }
      if($_GET['authorized'] or $authorized_scopes === count($scopes) and $authorized_scopes) {
        if($authorized_scopes !== count($scopes)) {
          $data['applications'][$application_id]['scopes'][$_SESSION['account_id']] = array();
          foreach($scopes as $scope) {
            array_push($data['applications'][$application_id]['scopes'][$_SESSION['account_id']], $scope);
          }
          file_put_contents('/home/runner/accounts/data/data.json', json_encode($data));
        }
        $returnData = array();
        foreach($scopes as $scope) {
          if($scope != 'password') {
            array_push($returnData, $scope.'='.$data['accounts'][$_SESSION['account_id']][$scope]);
          }
        }
        header('location: '.$response_uri.'?token='.$token.'&account_id='.$_SESSION['account_id'].'&'.implode('&', $returnData));
      }
    }
  }
  else {
    $loginreq = true;
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
              if($modal_type == 'auth' or $loginreq) {
                $messages['header'] = "Welcome";
                $messages['info'] = "Log in with DanAuth";
                if(isset($_POST['email']) and isset($_POST['password'])) {
                  if(!$_SESSION['account_id']) {
                    $loginproc = true;
                    $userid = email_exists($_POST['email']);
                    if(email_exists($_POST['email'])) {
                      if(password_verify($_POST['password'], $data['accounts'][$userid]['password'])) {
                        $_SESSION['account_id'] = $userid;
                        header('refresh: 0');
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
              }
              else {
                $messages['header'] = "Consent";
                $messages['info'] = $data['applications'][$application_id]['name']." wants access to the following:<ul>";
                foreach($scopes as $scope) {
                  $messages['info'] = $messages['info'].'<li>Your '.$scope.'</li>';
                }
                echo '</ul>';
              }

              if(!$data['applications'][$application_id] or !$scopes and $modal_type == 'consent') {
                $messages['header'] = "Invalid Request";
                $loginproc;
                if(!$application_id) {
                  $messages['info'] = "Please include an application ID in your request headers.";
                }
                else {
                  if(!$data['applications'][$application_id]) {
                    $messages['info'] = "That's not a valid application ID.";
                  }
                }
                if(!$scopes and $modal_type == 'consent') {
                  $messages['info'] = "Please define your application scopes.";
                }
                if(!$response_uri) {
                  $messages['info'] = "You need to provide a response URI.";
                }
              }

              $messages['icon'] = $data['applications'][$application_id]['logo-url'];

              if(!$messages['icon']) {
                $messages['icon'] = 'https://danjaye.lol/dan_logo_large_tester.png';
              }

              echo "<img class = 'logo' src = '".$messages['icon']."'><h1>".$messages['header']."</h1><p>".$messages['info']."</p>";
            ?>
          </div>
          <?php
            if($data['applications'][$application_id] and !$loginproc) {
              $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
              if($modal_type == 'auth' or $loginreq) {
                echo "<form method = 'post'><div style = 'margin-top: 15px;'><input name = 'email' type = 'email' placeholder = 'Email' style = 'margin-bottom: 5px;'><input name = 'password' type = 'password' placeholder = 'Password' type = 'password'></div><div style = 'margin-top: 15px;'><button>Continue</button></div></form><div style = 'margin-top: 10px;'><small>Don't have an account? <a href = '/serviceLogin/v2?type=signup&callback=".$actual_link."'>Create one</a></small></div>";
              }
              else {
                if($modal_type == 'consent' and $scopes) {
                  echo "<div style = 'margin-top: 15px;'><button onClick = 'window.location.assign(updateQueryStringParameter(window.location.href, ".'"authorized"'.", ".'"true"'."));'>Allow</button><div style = 'margin-top: 10px;'><small><a href = '".$_GET['response_uri']."?error=noConsent'>No thanks</a></small></div></div>";
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
    <script>
      function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
          return uri.replace(re, '$1' + key + "=" + value + '$2');
        }
        else {
          return uri + separator + key + "=" + value;
        }
      }
    </script>
  </body>
</html>
