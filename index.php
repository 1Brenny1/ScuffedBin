<?php
  class SqLite extends SQLite3 {
    function __construct() {
      $this->open('database.db');
    }
  }
  $db = new SqLite();
  if(!$db) {
    echo $db->lastErrorMsg();
  } else {
    $db->exec("CREATE TABLE IF NOT EXISTS Users (
      Id INTEGER PRIMARY KEY AUTOINCREMENT,
      Username TEXT NOT NULL UNIQUE,
      Password TEXT NOT NULL,
      Admin INT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS Posts (
      Id INTEGER PRIMARY KEY AUTOINCREMENT,
      CreatorId INTEGER NOT NULL,
      Title TEXT NOT NULL,
      Content TEXT NOT NULL
    )");

    $URL = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $Path = $URL['path'];
    $SplitPath = explode("/", $Path);
    $Raw = ($SplitPath[3] == "raw");
    
    if (isset($_POST['Type'])) {
      if ($_POST['Type'] == "Login") {
        $Login = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_POST["Username"]) . "'", true);
        if (count($Login) != 0) {
          if ($Login["Password"] == bin2hex($_POST["Password"])) {
            setcookie("Account",bin2hex($Login["Username"] . "|" . $Login["Password"]), time() + 31536000000, "/");
            setcookie("Username",$_POST["Username"], time() + 31536000000, "/");
          } else {
            setcookie("LoginAlert","Incorrect Password", time() + 31536000000, "/");
          }
        } else {
          setcookie("LoginAlert","Incorrect Username", time() + 31536000000, "/");
        }
      } elseif ($_POST['Type'] == "Sign Up") {
        $Check = $db->querySingle("SELECT * FROM Users WHERE Username='" . bin2hex($_POST["Username"]) . "'", true);
        if (count($Check) == 0) {
          if (preg_match("#^[a-zA-Z0-9]+$#", $_POST["Username"])) {
            $db->exec("INSERT INTO Users (Username, Password) VALUES('". bin2hex($_POST["Username"]) ."', '". bin2hex($_POST["Password"]) ."')");
            setcookie("Account",bin2hex(bin2hex($_POST["Username"]) . "|" . bin2hex($_POST["Password"])), time() + 31536000000, "/");
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
      } else if ($_POST["Type"] == "Post!") {
        $LoginInfo = explode("|", hex2bin($_COOKIE["Account"]));
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='". bin2hex($_COOKIE["Username"]) ."'", true);
        if ($Account["Password"] == $LoginInfo[1]) {
          $db->exec("INSERT INTO Posts (CreatorId, Title, Content) VALUES(". $Account["Id"] .", '". bin2hex($_POST["Title"]) ."', '". bin2hex($_POST["Content"]) ."')");
          $Post = $db->querySingle("SELECT * FROM Posts ORDER BY Id DESC LIMIT 1", true);
          echo "<script>location.href = '../post/". $Post["Id"] ."'</script>";
        } else {
          setcookie("Alert","Post|Failed to verify account", time() + 31536000000, "/");
        }
      } else if ($_POST["Type"] == "Delete Post") {
        $LoginInfo = explode("|", hex2bin($_COOKIE["Account"]));
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='". bin2hex($_COOKIE["Username"]) ."'", true);
        if ($Account["Password"] == $LoginInfo[1]) {
          $URL = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
          $Path = $URL['path'];
          $SplitPath = explode("/", $Path);
          $PostId = $SplitPath[2];
          $PostInfo = $db->querySingle("SELECT * FROM Posts WHERE Id=" . $PostId,true);
          if ($Account["Admin"] == 1 || $Account["Id"] == $PostInfo["CreatorId"]) {
            $db->exec("DELETE FROM Posts WHERE Id=" . $PostId);
            echo "<script>location.href = '../home/'</script>";
          }
        }
      }
    }
  }
?>
<?php
  $Head1 = <<<EOD
  <html>
  <head>
    <title>Scuffed Bin</title>

    <link rel="stylesheet" href="https://fonts.xz.style/serve/inter.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@exampledev/new.css@1.1.2/new.min.css">
    <link rel="stylesheet" href="https://newcss.net/theme/night.css">
    <script>
  EOD;
  if (!$Raw) {
    echo $Head1;
  }
    ?>
    <?php
      if (!$Raw) {
        $Cookie = fopen("Cookies.js", "r");
        echo fread($Cookie, filesize("Cookies.js"));
        fclose($Cookie);
      }
    ?>
    <?php
    $Head2 = <<<EOD
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
          document.getElementsByTagName("nav")[0].innerHTML += `/ <a href="../my-posts/">My Posts</a> / <a href="../create-post/">Create Post</a>`
          document.getElementsByTagName("Header")[0].innerHTML += Account
        } else {
          document.getElementsByTagName("Header")[0].innerHTML += Login
        }
      </script>
    </header>
EOD;
    if (!$Raw) {
      echo $Head2;
    }
    ?>
    <?php

      $FileName = "404.html";
      if ($Path == "" || $Path == "/" || $Path == "/home/") {
        $FileName = "home.html";
      }
      elseif ($Path == "/login/") {
        $FileName = "login.html";
      } elseif ($Path == "/account/") {
        $FileName = "account.html";
      } else if ($Path == "/discover/") {
        $FileName = "discover.html";
      } else if ($Path == "/create-post/") {
        $FileName = "createpost.html";
      } else if ($Path == "/my-posts/"){
        $FileName = "myposts.html";
      } else if (substr($Path, 0,6) == "/post/") {
        $SplitPath = explode("/", $Path);
        $PostId = $SplitPath[2];
          
        $PostData = $db->querySingle("SELECT * FROM Posts WHERE Id=" . $PostId, true);
        if (count($PostData) != 0) {
          $FileName = "post.html";
          $PostAccount = $db->querySingle("SELECT * FROM Users WHERE Id='" . $PostData["CreatorId"] . "'", true);
          $PostCreator = hex2bin($PostAccount["Username"]) or $PostCreator = "[Deleted User]";
          if ($PostCreator == "") { $PostCreator = "[Deleted User]"; }
          $PostContent = strip_tags(hex2bin($PostData["Content"]));
          $Admin = ($PostAccount["Admin"] == 1);
          if (!$Raw) {
          echo "<script>localStorage.setItem('PostTitle', '". hex2bin($PostData["Title"]) ."'); localStorage.setItem('PostContent', '". $PostContent ."'); localStorage.setItem('Creator', '". $PostCreator ."'); localStorage.setItem('Admin', '". $Admin ."');</script>";
          } else {
            echo $PostContent;
          }
        }
      }
      if (!$Raw) {
      $File = fopen($FileName, "r") or $File = fopen("404.html", "r") or die("Unable to open file!");
      echo fread($File,filesize($FileName));
      fclose($File);
      }
      
      if ($Path == "/discover/") {

        $PostData = $db->query("SELECT * FROM Posts");
        while ($row = $PostData->fetchArray()) {
          $PostId = $row["Id"];
          $PostTitle = hex2bin($row["Title"]);
          $PostContent = hex2bin($row["Content"]);
          $PostAccount = $db->querySingle("SELECT * FROM Users WHERE Id='" . $PostId . "'", true);
          $PostCreator = hex2bin($PostAccount["Username"]) or $PostCreator = "[Deleted User]";
          if ($PostCreator == "") { $PostCreator = "[Deleted User]"; }
          if (strlen($PostContent) >= 250) {
            $PostContent = substr($PostContent, 0, 250) . "...";
          }

          $PostContent = strip_tags($PostContent);
          $PostContent = htmlentities($PostContent);
          $PostTitle = htmlentities($PostTitle);
          $PostCreator = htmlentities($PostCreator);

          $Admin = "";
          if ($PostAccount["Admin"] == 1) {
            $Admin = "<p class='Admin'>Admin</p>";
          }

          echo <<<EOD
        <div onclick="location.href = '../post/$PostId'" class="Post">
          <h2>$PostTitle</h2>
          <blockquote>$PostContent</blockquote>
          <p>By: $PostCreator$Admin</p>
        </div><br>
        EOD;
        }
      } else if (substr($Path, 0,6) == "/post/") {
        if ($FileName != "post.html" or $Raw) {return;}
        $LoginInfo = explode("|", hex2bin($_COOKIE["Account"]));
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='". bin2hex($_COOKIE["Username"]) ."'", true);
        if ($Account["Password"] == $LoginInfo[1]) {
          $URL = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
          $Path = $URL['path'];
          $SplitPath = explode("/", $Path);
          $PostId = $SplitPath[2];
          $PostInfo = $db->querySingle("SELECT * FROM Posts WHERE Id=" . $PostId,true);
          if ($Account["Admin"] == 1 || $Account["Id"] == $PostInfo["CreatorId"]) {
            echo <<<EOD
            <style>
              form {
                display: inline-block;
              }
              #DeletePost {
                background-color: #ff5555;
                display: inline-block;
              }
              #DeletePost:hover {
                background-color: #aa0000;
              }
            </style>
            <form method="POST">
              <input id="DeletePost" type="submit" name="Type" value="Delete Post">
            </form>
            EOD;
          }
        }
      } else if ($Path == "/my-posts/") {
        $LoginInfo = explode("|", hex2bin($_COOKIE["Account"]));
        $Account = $db->querySingle("SELECT * FROM Users WHERE Username='". bin2hex($_COOKIE["Username"]) ."'", true);
        if ($Account["Password"] == $LoginInfo[1]) {
          $Posts = $db->query("SELECT * FROM Posts WHERE CreatorId=". $Account["Id"]);
          while ($row = $Posts->fetchArray()) {
            $PostId = $row["Id"];
            $PostTitle = hex2bin($row["Title"]);
            $PostContent = hex2bin($row["Content"]);
            $PostCreator = hex2bin($db->querySingle("SELECT * FROM Users WHERE Id='" . $row["CreatorId"] . "'", true)["Username"]) or $PostCreator = "[Deleted User]";
            if ($PostCreator == "") { $PostCreator = "[Deleted User]"; }
            if (strlen($PostContent) >= 250) {
              $PostContent = substr($PostContent, 0, 250) . "...";
            }

            $PostContent = strip_tags($PostContent);
            $PostContent = htmlentities($PostContent);
            $PostTitle = htmlentities($PostTitle);
            $PostCreator = htmlentities($PostCreator);

            echo <<<EOD
            <div onclick="location.href = '../post/$PostId'" class="Post">
              <h2>$PostTitle</h2>
              <blockquote>$PostContent</blockquote>
              <p>By: $PostCreator</p>
            </div><br>
            EOD;
          }
        }
      }
    ?>
    
  </body>
</html>

<?php
  $db->close();
?>