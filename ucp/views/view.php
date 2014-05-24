<div class="col-md-10">
	<script>var extension = <?php echo $_REQUEST['sub']?>;</script>
	<?php if(!empty($message)) { ?>
		<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
	<?php } ?>
	<?php echo $pagnation;?>
	<div class="table-responsive">
		<table class="table table-hover table-bordered cdr-table">
			<thead>
			<tr class="cdr-header">
				<th><?php echo _('Date')?><i class="fa fa-chevron-down"></i></th>
				<th><?php echo _('Description')?><i class="fa fa-chevron-down hidden"></i></th>
				<th><?php echo _('Duration')?><i class="fa fa-chevron-down hidden"></i></th>
				<th class="noclick"><?php echo _('Controls')?></i></th>
			</tr>
			</thead>
		<?php if(!empty($calls)) {?>
			<?php foreach($calls as $call){?>
				<tr id="cdr-item-<?php echo $call['niceUniqueid']?>" class="cdr-item" data-msg="<?php echo $call['niceUniqueid']?>">
					<td class="date"><?php echo date('Y-m-d',$call['timestamp'])?> <?php echo date('h:i:sa',$call['timestamp'])?></td>
					<td class="clid">
						<?php if($call['disposition'] == 'ANSWERED') { ?>
							<?php if($call['src'] == $_REQUEST['sub']) { ?>
								<?php $dst = $this->UCP->FreePBX->Core->getDevice($call['dst']);?>
								<i class="fa fa-arrow-right out"></i> <span class="text"><?php echo !empty($dst['description']) ? htmlentities('"'.$dst['description'].'"' . " <".$call['dst'].">") : $call['dst']?></span>
							<?php } elseif($call['dst'] == $_REQUEST['sub']) { ?>
								<i class="fa fa-arrow-left in"></i> <span class="text"><?php echo htmlentities($call['clid'])?></span>
							<?php } else { ?>
								<span class="text"><?php echo $call['src']?></span>
							<?php } ?>
						<?php } else { ?>
							<i class="fa fa-ban missed"></i>
							<?php if($call['src'] == $_REQUEST['sub']) { ?>
								<span class="text"><?php echo $call['dst']?></span>
							<?php } elseif($call['dst'] == $_REQUEST['sub']) { ?>
								<span class="text"><?php echo $call['clid']?></span>
							<?php } else { ?>
								<span class="text"><?php echo $call['src']?></span>
							<?php } ?>
						<?php } ?>
					</td>
					<td><?php echo $call['niceDuration']?></td>
					<td class="actions">
						<?php if(!empty($call['recordingfile'])) { ?>
							<div>
								<a class="subplay" alt="<?php echo _('Play');?>" data-msg="<?php echo $call['niceUniqueid']?>">
									<i class="fa fa-play"></i>
								</a>
								<a class="download" alt="<?php echo _('Download');?>" href="?quietmode=1&amp;module=cdr&amp;command=listen&amp;msgid=<?php echo $call['niceUniqueid']?>&amp;type=download&amp;format=<?php echo $call['recordingformat']?>&amp;ext=<?php echo $_REQUEST['sub']?>" target="_blank">
									<i class="fa fa-cloud-download"></i>
								</a>
							</div>
						<?php } ?>
					</td>
				</tr>
				<?php if(!empty($call['recordingfile'])) { ?>
					<tr id="cdr-playback-<?php echo $call['niceUniqueid']?>" class="cdr-playback">
						<td colspan="4">
							<div id="jquery_jplayer_<?php echo $call['niceUniqueid']?>" class="jp-jplayer"></div>
							<div id="jp_container_<?php echo $call['niceUniqueid']?>" class="jp-audio">
								<div class="jp-type-single">
									<div class="jp-gui jp-interface">
										<ul class="jp-controls">
											<li class="jp-play-wrapper"><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
											<li class="jp-pause-wrapper"><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
											<li class="jp-stop-wrapper"><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
											<li class="jp-mute-wrapper"><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
											<li class="jp-unmute-wrapper"><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
											<li class="jp-volume-max-wrapper"><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
										</ul>
										<div class="jp-progress">
											<div class="jp-seek-bar">
												<div class="jp-play-bar"></div>
											</div>
										</div>
										<div class="jp-volume-bar">
											<div class="jp-volume-bar-value"></div>
										</div>
										<div class="jp-current-time"></div>
										<div class="jp-duration"></div>
										<div class="jp-title">
											<ul>
												<li class="title-text"></li>
											</ul>
										</div>
									</div>
									<div class="jp-no-solution">
										<span><?php echo _('Update Required')?></span>
										<?php echo sprintf(_('To play the media you will need to either update your browser to a recent version or update your <a href="%s" target="_blank">Flash plugin</a>'),'http://get.adobe.com/flashplayer/');?>.
									</div>
								</div>
							</div>
						</td>
					</tr>
				<?php } ?>
			<?php }?>
		<?php } else { ?>
			<tr class="cdr-item">
				<td colspan="7"><?php echo _('No History');?></td>
			</tr>
		<?php } ?>
		</table>
	</div>
	<?php echo $pagnation;?>
</div>
