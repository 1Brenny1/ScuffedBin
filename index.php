<?php
  class MyDB extends SQLite3 {
    function __construct() {
      $this->open('database.db');
    }
  }
  $db = new MyDB();
  if(!$db) {
    echo $db->lastErrorMsg();
  } else {
    $Table = "CREATE TABLE IF NOT EXISTS Users (
      Id INTEGER PRIMARY KEY AUTOINCREMENT,
      Username TEXT NOT NULL UNIQUE,
      Password TEXT NOT NULL
    )";
    $db->exec($Table);

    #$rep = $db->exec("INSERT INTO Users (Username, Password) VALUES('1Brenny1', '69420')");

    #echo var_dump($db->querySingle("SELECT * FROM Users WHERE Username='1Brenny1'", true));
    
    

    if (isset($_POST['Type'])) {
      if ($_POST['Type'] == "Login") {
        $Login = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_POST["Username"]) . "'", true);
        if (count($Login) == 3) {
          if ($Login["Password"] == bin2hex($_POST["Password"])) {
            setcookie("Account",bin2hex($Login["Username"] . "|" . $Login["Password"]), time() + 31536000000, "/");
            setcookie("Username",$_POST["Username"], time() + 31536000000, "/");
          } else {
            setcookie("LoginAlert","Incorrect Username or Password", time() + 31536000000, "/");
          }
        } else {
          setcookie("LoginAlert","Incorrect Username or Password", time() + 31536000000, "/");
        }
      } elseif ($_POST['Type'] == "Sign Up") {
        $Check = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_POST["Username"]) . "'", true);
        if (count($Check) == 0) {
          if (preg_match("#^[a-zA-Z0-9]+$#", $_POST["Username"])) {
            $db->exec("INSERT INTO Users (Username, Password) VALUES('". bin2hex($_POST["Username"]) ."', '". bin2hex($_POST["Password"]) ."')");
            setcookie("Account",bin2hex($_POST["Username"] . "|" . $_POST["Password"]), time() + 31536000000, "/");
            setcookie("Username",$_POST["Username"], time() + 31536000000, "/");
          } else {
            setcookie("LoginAlert","Valid Chatacters A-Z and 0-9", time() + 31536000000, "/");
          }
        } else {
          setcookie("LoginAlert","Username all ready in use", time() + 31536000000, "/");
        }
      } elseif ($_POST["Type"] == "Change Username") {
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_COOKIE["Username"]) . "'", true);
        if (bin2hex($_POST["Password"]) == $Account["Password"]) {
          if (preg_match("#^[a-zA-Z0-9]+$#", $_POST["Username"])) {
            $Check = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_POST["Username"]) . "'", true);
            if (count($Check) == 0) {
              $db->exec("UPDATE Users SET Username='" . bin2hex($_POST["Username"]) . "' WHERE Username='" . $Account["Username"] . "'");
              setcookie("Account",bin2hex($_POST["Username"] . "|" . $Account["Password"]), time() + 31536000000, "/");
              setcookie("Username",$_POST["Username"], time() + 31536000000, "/");
            } else {
              setcookie("Alert", "Change Username|Username all ready in use", time() + 31536000000, "/");
            }
          } else {
            setcookie("Alert", "Change Username|Valid Chatacters A-Z and 0-9", time() + 31536000000, "/");
          }
        } else {
          setcookie("Alert","Change Username|Incorrect Password", time() + 31536000000, "/");
        }
      } else if ($_POST["Type"] == "Change Password") {
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_COOKIE["Username"]) . "'", true);
        if (bin2hex($_POST["Password"]) == $Account["Password"]) {
          if ($_POST["NPassword"] == $_POST["RPassword"]) {
            $db->exec("UPDATE Users SET Password='" . bin2hex($_POST["NPassword"]) . "' WHERE Username='" . $Account["Username"] . "'");
            setcookie("Account",bin2hex($Account["Username"] . "|" . $_POST["NPassword"]), time() + 31536000000, "/");
          } else {
            setcookie("Alert","Change Password|Passwords do not match", time() + 31536000000, "/");
          }
        } else {
          setcookie("Alert","Change Password|Incorrect Password", time() + 31536000000, "/");
        }
      } else if ($_POST["Type"] == "Delete Account") {
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_COOKIE["Username"]) . "'", true);
        if (bin2hex($_POST["Password"]) == $Account["Password"]) {
          $db->exec("DELETE FROM Users WHERE Username = '". bin2hex($_COOKIE["Username"]) ."'");
          setcookie("Account","", 0, "/");
          setcookie("Username","", 0, "/");
        } else {
          setcookie("Alert","Delete Account|Incorrect Password", time() + 31536000000, "/");
        }
      } else if ($_POST["Type"] == "Logout") {
        setcookie("Account","", 0, "/");
        setcookie("Username","", 0, "/");
      }
    }
    
    $db->close();
  }
?>


<html>
  <head>
    <title>Scuffed Bin</title>

    <link rel="stylesheet" href="https://fonts.xz.style/serve/inter.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@exampledev/new.css@1.1.2/new.min.css">
    <link rel="stylesheet" href="https://newcss.net/theme/night.css">
    <script>
    <?php
      $Cookie = fopen("Cookies.js", "r");
      echo fread($Cookie, filesize("Cookies.js"));
      fclose($Cookie);
    ?>
    </script>
    
  </head>
  <body>
    <header>
      <h1>Scuffed Bin</h1>
      <nav style="display: inline-block;">
        <a href="../home/">Home</a>
        /
        <a href="../discover/">Discover</a>
      </nav>
      <script>
        var Login = `
        <nav style="display: inline-block; float: right;">
        <a href="../login/">Login</a>
        /
        <a href="../login/">Sign Up</a>
        </nav>
        `
        var Account = `
        <nav style="display: inline-block; float: right;">
        <a href="../account/">Account</a>
        </nav>
        `
        if (getCookie("Account")) {
          document.getElementsByTagName("nav")[0].innerHTML += `/ <a href="../my-posts">My Posts</a> / <a href="../create-post">Create Post</a>`
          document.getElementsByTagName("Header")[0].innerHTML += Account
        } else {
          document.getElementsByTagName("Header")[0].innerHTML += Login
        }
      </script>
    </header>

    <?php
      $URL = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
      $Path = $URL['path'];

      $FileName = "404.html";
      if ($Path == "" || $Path == "/" || $Path == "/home/") {
        $FileName = "home.html";
      }
      elseif ($Path == "/login/") {
        $FileName = "login.html";
      } elseif ($Path == "/account/") {
        $FileName = "account.html";
      }

      $File = fopen($FileName, "r") or die("Unable to open file!");
      echo fread($File,filesize($FileName));
      fclose($File);
    ?>
    
  </body>
</html>