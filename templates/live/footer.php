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
									Modules:<br />
									<ul>
									<?php
									foreach($GLOBALS['modules'] as $name => $module)
									{
										if($module['privilage'] > $GLOBALS['templates']['vars']['user']['Privilage'])
											continue;
																			
										if(!function_exists('output_' . $name))
											$link = 'module=admin_modules&configure_module=' . $name;
										else
											$link = 'module=' . $name;
																		
										?><li class="last"><a href="<?php print url($link); ?>"><?php echo $module['name']; ?></a></li><?php
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
									foreach($GLOBALS['handlers'] as $handler => $config)
									{
										if(isset($GLOBALS['handlers'][$handler]['internal']) && $GLOBALS['handlers'][$handler]['internal'] == true)
											continue;
											
										$name = $GLOBALS['handlers'][$handler]['name'];
										?><li class="last"><a href="<?php print url('module=select&cat=' . $handler); ?>"><?php echo $name; ?></a></li><?php
									}
									?>
									</ul>
								</td>
							</tr>
							<tr>
								<td align="center">Powered by <a href="http://www.monolithicmedia.org/" style="text-decoration:none;"><img style="border:0px;" src="http://www.monolithicmedia.org/favicon.ico" />MonolithicMedia.org</a></td>
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
