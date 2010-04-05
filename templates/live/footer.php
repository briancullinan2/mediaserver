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
									Plugins:<br />
									<ul>
									<?php
									foreach($GLOBALS['plugins'] as $name => $plugin)
									{
										if($plugin['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
											continue;
										
										?><li class="last"><a href="<?php echo generate_href('plugin=' . $name); ?>"><?php echo $plugin['name']; ?></a></li><?php
									}
									?>
									</ul>
								</td>
							</tr>
							<tr>
								<td>
									Categories:<br />
									<ul>
									<?php
									foreach($GLOBALS['modules'] as $module)
									{
										if(constant($module . '::INTERNAL'))
											continue;
											
										$name = str_replace(' from Database', '', constant($module . '::NAME'));
										?><li class="last"><a href="<?php echo generate_href('plugin=select&cat=' . $module); ?>"><?php echo $name; ?></a></li><?php
									}
									?>
									</ul>
								</td>
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
