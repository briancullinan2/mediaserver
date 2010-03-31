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
        <form action="{'plugin=list'|generate_href}" method="get">
            <input type="hidden" name="cat" value="{$cat}" />
            Type <select name="list">
            	<?php
				foreach($GLOBALS['types'] as $i => $type)
				{
					?><option value="{$name}">{$type.name}</option><?php
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