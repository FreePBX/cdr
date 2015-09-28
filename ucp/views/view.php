<div class="col-md-12">
	<script>var extension = "<?php echo $_REQUEST['sub']?>";</script>
	<?php if(!empty($message)) { ?>
		<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
	<?php } ?>
	<div class="row">
		<div class="col-sm-8">
			<?php echo $pagnation;?>
		</div>
		<div class="col-sm-4">
			<div class="input-group">
				<input type="text" class="form-control" id="search-text" placeholder="<?php echo _('Search')?>" value="<?php echo $search?>">
				<span class="input-group-btn">
					<button class="btn btn-default" type="button" id="search-btn">Go!</button>
				</span>
			</div>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover table-bordered cdr-table">
			<thead>
			<tr class="cdr-header">
				<th data-type="date"><?php echo _('Date')?><i class="fa fa-chevron-<?php echo ($order == 'desc' && $orderby == 'date') ? 'down' : 'up'?> <?php echo ($orderby == 'date') ? '' : 'hidden'?>"></i></th>
				<th data-type="description"><?php echo _('Description')?><i class="fa fa-chevron-<?php echo ($order == 'desc' && $orderby == 'description') ? 'down' : 'up'?> <?php echo ($orderby == 'description') ? '' : 'hidden'?>"></i></th>
				<th class="hidden-xs" data-type="duration"><?php echo _('Duration')?><i class="fa fa-chevron-<?php echo ($order == 'desc' && $orderby == 'duration') ? 'down' : 'up'?> <?php echo ($orderby == 'duration') ? '' : 'hidden'?>"></i></th>
				<th class="noclick"><?php echo _('Controls')?></i></th>
			</tr>
			</thead>
		<?php if(!empty($calls)) {?>
			<?php foreach($calls as $call){?>
				<tr id="cdr-item-<?php echo $call['niceUniqueid']?>" class="cdr-item" data-msg="<?php echo $call['niceUniqueid']?>">
					<td class="date"><span><?php echo date('m/d/y',$call['timestamp'])?></span>  <span class="hidden-xs" style="margin-left:5px;"><?php echo date('h:i:sa',$call['timestamp'])?></span></td>
					<td class="clid">
						<?php if(!empty($call['icons'])) { ?>
							<?php foreach($call['icons'] as $icon) {?>
								<i class="fa <?php echo $icon?>"></i>
							<?php } ?>
						<?php } ?>
							<span class="text"><?php echo $call['text']?></span>
					</td>
					<td class="hidden-xs"><?php echo $call['niceDuration']?></td>
					<td class="actions">
						<?php if(!empty($call['recordingfile'])) { ?>
							<div>
								<?php if($showPlayback) { ?>
									<a class="subplay" alt="<?php echo _('Play');?>" data-msg="<?php echo $call['niceUniqueid']?>">
										<i class="fa fa-play"></i>
									</a>
								<?php } ?>
								<?php if($showDownload) { ?>
									<a class="download" alt="<?php echo _('Download');?>" href="?quietmode=1&amp;module=cdr&amp;command=download&amp;msgid=<?php echo $call['niceUniqueid']?>&amp;type=download&amp;format=<?php echo $call['recordingformat']?>&amp;ext=<?php echo $_REQUEST['sub']?>" target="_blank">
										<i class="fa fa-cloud-download"></i>
									</a>
								<?php } ?>
							</div>
						<?php } ?>
					</td>
				</tr>
				<?php if(!empty($call['recordingfile']) && $showPlayback) { ?>
					<tr id="cdr-playback-<?php echo $call['niceUniqueid']?>" class="cdr-playback">
						<td colspan="4">
							<div id="jquery_jplayer_<?php echo $call['niceUniqueid']?>" class="jp-jplayer"></div>
							<div id="jp_container_<?php echo $call['niceUniqueid']?>" class="jp-audio">
								<div class="jp-type-single">
									<div class="jp-gui jp-interface">
										<div class="jp-message-window"><div class="message"><?php echo _("Loading")?></div></div>
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
