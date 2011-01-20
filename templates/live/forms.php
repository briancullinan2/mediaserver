<?php

function theme_live_form_form($config)
{
	$config_html = traverse_array($config);
	?>
	<form name="<?php print escape($config['field']); ?>" id="<?php print escape($config['field']); ?>_form" action="<?php print isset($config_html['action'])?$config_html['action']:$GLOBALS['output']['get']; ?>" method="<?php print isset($config_html['method'])?$config_html['method']:'post'; ?>">
		<?php
		theme('form_objects', $config['options']);
		
		if(isset($config['submit']))
			theme('form_submit', $config['submit']);
		if(isset($config['reset']))
			theme('form_submit', $config['reset']);
	
		?>
		<script type="text/javascript">
			$( ".field_fieldset" ).accordion();
			$( ".collapsible:parent" ).accordion("option", "collapsible", true);
			$( "button, input:submit, input:button, input:reset" ).button();
		</script>
	</form>
	<?php
}

function theme_live_form_fieldset($config)
{
	extract($config);
	if(isset($name) || count($options) > 0)
	{
		if(isset($name))
		{
			?><h3><a href="#"><?php print escape($name); ?></a></h3><?php
		}

		?><div id="fieldcontainer_<?php print machine($field); ?>" class="<?php print (isset($collapsible) && $collapsible)?'collapsible':''; ?>" <?php print (isset($collapsed) && $collapsed)?'style="display:none;"':''; ?>><?php
		
			theme('form_fieldrows', $options);
		
		?>
		</div>
		<?php
	}
}


function theme_live_form_ordered($config)
{
	extract($config);
	
	?>
	<input name="<?php print machine($field); ?>" id="<?php print machine($field); ?>_input" type="hidden" value="<?php print implode(',', array_keys($options)); ?>" />
	<ul id="<?php print machine($field); ?>_sortable" class="sortable">
	<?php
	
	foreach($options as $option => $text)
	{
		?><li id="<?php print machine($field); ?>_<?php print $option; ?>" class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span><?php print escape($text); ?></li><?php
	}
	
	?>
	</ul>
	<script type="text/javascript">
		$( "#<?php print machine($field); ?>_sortable" ).sortable({ axis: 'y', stop: function() {
			var order = "";
			$( "#<?php print machine($field); ?>_sortable li" ).each(function () {
					order += ((order=="")?"":",") + $(this).attr("id").substr(<?php print strlen(machine($field))+1; ?>);
				});
			$('#<?php print machine($field); ?>_input').val(order);
		}});
	</script>
	<?php
}


