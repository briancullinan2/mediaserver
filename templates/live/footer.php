<?php

function theme_live_footer()
{
	?>
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
								<td>
									Categories:<br />
									<ul>
									<?php
									foreach(get_handlers() as $handler => $config)
									{
										$name = $config['name'];
										?><li><a href="<?php print url('select/' . $handler); ?>"><?php echo $name; ?></a></li><?php
									}
									?>
									</ul>
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
	</body>
	</html>
	<?php
}
