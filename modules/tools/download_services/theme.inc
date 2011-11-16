<?php



function theme__download()
{
	theme('header');
	
	if(!isset($GLOBALS['output']['service_search']))
	{
	?>
	<form action="" method="get">
		<input type="text" name="search" id="service_search" size="80" value="<?php print $GLOBALS['output']['vars']['service_search']; ?>" />
		<input type="submit" id="button" value="Search" />
	</form>
	<?php
	}
	else
		theme('form_object', 'info', array(
			'action' => $GLOBALS['output']['html']['get'],
			'options' => $GLOBALS['output']['infos'],
			'type' => 'form',
		));
		
	theme('footer');
}