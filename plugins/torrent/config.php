<?include "inc_security.php";?>
<?php
if(isset($_POST['save'])) {       
include("inc_config.php");
$i=0;
$fd = fopen ("inc_config.php", "r");
	while (!feof ($fd)) {
   	$zeile = fgets($fd);
   	$var_da = substr ($zeile, 0 , 1);     
                                          
    if ($var_da == "$")    
    {   $i=$i + 1;  
    	  $gleich = strrpos ( $zeile, "=" );    		                            
    		$wertbis = strrpos ( $zeile, ";" ); 
    		$kommentar = substr ( $zeile, $wertbis + 1, strlen ($zeile));     		                          
    		$variable = substr ( $zeile, 1, $gleich - 1 );     	                   
    		$wert = substr ( $zeile, $gleich + 3 , $wertbis - $gleich - 4 ); 
    		$a = $_POST["a".$i];
    		$array[]="$".$variable."= \"". $a ."\";".$kommentar;     
    	} else {
    	$array[]=$zeile;
    	
    	}   	
 	}
	
	$fd = fopen ("inc_config.php", "w");   
	  foreach ($array as $textzeile) {
      fwrite($fd, $textzeile);
   }

	fclose ($fd);
	$result = "Configuration Saved<br/>";
}	
?>
<?php $page="config"; include("tpl_header.php"); ?>
<?php if(isset($result)) echo $result;?>
<h1>Configuration</h1>
<form method="POST" action="<?$PHP_SELF?>" enctype="multipart/form-data">
<table>
	
<?
	$i=0;
	$fd = fopen("inc_config.php", "r");
	while (!feof ($fd)) {
    	$zeile = fgets($fd);
    	$var_da = substr ($zeile, 0 , 1);
    	if ($var_da == "$") 
	   	{	$i = $i + 1;
    		$gleich = strrpos ( $zeile, "=" );    		
    		$wertbis = strrpos ( $zeile, ";" );     		
    		$variable = substr ( $zeile, 1 , $gleich - 1 );
    		if ($variable == "extensions ") {
    			$wert = substr ( $zeile, $gleich + 9 , $wertbis - $gleich - 11 );    			
    		} else {    			
    		$wert = substr ( $zeile, $gleich + 3 , $wertbis - $gleich - 4 );
    	}
    		
    		$kommentar = substr ( $zeile, $wertbis + 4, strlen ($zeile));
    		
    		echo "<tr><td style=\"width:140px\"><div  style=\"width:140px\">".$variable."</div></td><td><input name=\"a".$i."\"type=\"text\" size=\"25\" style=\"width:300px\" maxlength=\"60\" value=\"".$wert."\"></td><td style=\"text-align:left; font-size:11px;\">$kommentar</td></tr>";
    	}
    	
	}
	fclose ($fd);
	?>
</table>
      <input name="save" type="submit" value="&nbsp; Save &nbsp;" />
</form>   
<?php include("tpl_footer.php"); ?>
