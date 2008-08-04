<?
session_start();
if ( $_SESSION["Authenticated"] != 1 ) {
	session_destroy();
    header("Location: login.php");
	die();
}
?>
