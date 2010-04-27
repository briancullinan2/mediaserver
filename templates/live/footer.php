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
										
										?><li class="last"><a href="<?php echo url('module=' . $name); ?>"><?php echo $module['name']; ?></a></li><?php
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
									foreach($GLOBALS['handlers'] as $handler)
									{
										if(constant($handler . '::INTERNAL'))
											continue;
											
										$name = str_replace(' from Database', '', constant($handler . '::NAME'));
										?><li class="last"><a href="<?php echo url('module=select&cat=' . $handler); ?>"><?php echo $name; ?></a></li><?php
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
