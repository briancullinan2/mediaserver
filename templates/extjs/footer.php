<?php


function theme_extjs_footer()
{
	?>
		<div class="titlePadding"></div>
	<?php
	
	if(!isset($GLOBALS['output']['extra']) || $GLOBALS['output']['extra'] != 'inneronly')
	{
		?>
				</div>
			</div>

<script type="text/javascript">

	win = new Ext.Window({
		applyTo:'mainColumn',
		layout:'fit',
		width:500,
		height:300,
		closeAction:'hide',
		plain: true,
		
		items: [new Ext.Panel({
			contentEl: 'files',
			border:false,
		})],
		
		tools: [{
			id:'help',
			qtip: 'My Tooltip',
		}],
	
	}).show();

</script>

			<div id="footerCtr">
				<?php
					theme('menu_block');
				?>
				<div style="clear:both; text-align:center;">
				Powered by <a href="http://www.monolithicmedia.org/" style="text-decoration:none;">
					<img style="border:0px;" src="<?php print url('template/extjs/logo'); ?>" />MonolithicMedia.org
				</a>
				<br />
				<a id=sitewatch_lock target="_blank" href="https://sitewat.ch/Status/dev.bjcullinan.com">
					<img src="https://sitewat.ch/Marker/dev.bjcullinan.com" alt="Sitewatch is protecting this site from hackers." height="32" width="115" border="0" />
				</a>
				</div>
			</div>
			<script type="text/javascript">
			if(document.getElementById("debug")) {
				header_height = document.getElementById("header").clientHeight + document.getElementById("debug").clientHeight;
			} else {
				header_height = document.getElementById("header").clientHeight;}
			</script>
		</div>
	</body>
</html>
<?php
	}
}
