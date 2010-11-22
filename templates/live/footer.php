<?php

function theme_live_container_footer()
{
	?>
			<div class="titlePadding"></div>
		</div>
											</td>
										</tr>
									</table>
								</td>
								<td class="sideColumn right"></td>
							</tr>
						</table>
					</div>
					<div id="footer">
						<table id="footerCtr">
							<tr>
								<td>
									<?php
										theme('menu_block');
									?>
								</td>
							</tr>
						</table>
					</div>
				</div>
	<?php
}


function theme_live_footer()
{
	theme('container_footer');
	
	if(!isset($GLOBALS['output']['extra']) || $GLOBALS['output']['extra'] != 'inneronly')
	{
		?>
Powered by <a href="http://www.monolithicmedia.org/" style="text-decoration:none;"><img style="border:0px;" src="<?php print url('template/live/logo'); ?>" />MonolithicMedia.org</a>
<a id=sitewatch_lock target="_blank" href="https://sitewat.ch/Status/dev.bjcullinan.com"><img src="https://sitewat.ch/Marker/dev.bjcullinan.com" alt="Sitewatch is protecting this site from hackers." height="32" width="115" border="0"></a>
			</div>
		</div>
	</div>
	<script language="javascript">
	if(document.getElementById("debug")) {
		header_height = document.getElementById("header").clientHeight + document.getElementById("debug").clientHeight;
	} else {
		header_height = document.getElementById("header").clientHeight;}
	</script>
	</body>
	</html>
	<?php
	}
}
