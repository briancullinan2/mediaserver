<?php

function theme_live_footer()
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
							<tr>
								<td align="center">Powered by <a href="http://www.monolithicmedia.org/" style="text-decoration:none;"><img style="border:0px;" src="<?php print url('template/live/logo'); ?>" />MonolithicMedia.org</a></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script language="javascript">
	loaded = true;
	if(document.getElementById("debug")) {
		header_height = document.getElementById("header").clientHeight + document.getElementById("debug").clientHeight;
	} else {
		header_height = document.getElementById("header").clientHeight;}
	</script>
	</body>
	</html>
	<?php
}
