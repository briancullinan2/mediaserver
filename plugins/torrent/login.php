<?php
session_start();
include "inc_config.php";
if (isset($_POST["login"]))
  {
    if (isset($_POST["username"]) && ($_POST["username"] == $username) && 
        isset($_POST["password"]) && ($_POST["password"] == $password))
        {
        $_SESSION["Authenticated"] = 1;
        session_write_close();
        header("Location: index.php");
        }
    else
    	{
	    $_SESSION["Authenticated"] = 0;
	    session_write_close();
        header("Location: login.php?msg=Could%20not%20login");
	    }

  }
  // User is logging out
if (isset($_GET["logout"]))

  {
      session_destroy();
	  header("Location: login.php?msg=logged%20out");
  }
?>
<?php $page="login"; include("tpl_header.php"); ?>
<div id="contentright">
	<h2>Hint</h2>
	<p>Enter the username and password<br>
    from the inc_config.php</p>
</div>

<h1>Login</h1>
<? if(isset($_GET['msg']))echo $_GET['msg'] . "<br/>";?>

    <form action="<?=$PHP_SELF?>" method="post">
      <h2>Username</h2>
      <input type="text" name="username">
      <h2>Password</h2>
      <input type="password" name="password"></p>
      <p><input type="submit" name="login" value="&nbsp; Login &nbsp;"></p>

    </form>
    
<?php include("tpl_footer.php"); ?>
