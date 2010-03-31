<?php

function register_plain_index()
{
}

function theme_plain_list()
{
	?>
    <div id="type">
        Get the list:
        <br />
        <form action="<?php print href('plugin=list'); ?>" method="get">
            <input type="hidden" name="cat" value="<?php print $GLOBALS['templates']['vars']['cat']; ?>" />
            Type <select name="list">
            	<?php
				foreach($GLOBALS['lists'] as $type => $list)
				{
					?><option value="<?php print $type; ?>"><?php print $list['name']; ?></option><?php
				}
				?>
            </select>
            <input type="submit" value="Go" />
        </form>
    </div>
	<?php
}

function theme_plain_index()
{
}
