<div class="col-md-10">
	<?php if(!empty($message)) { ?>
		<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
	<?php } ?>
	<ul class="pagination pagination-sm">
		<li class="<?php echo ($activePage == 1) ? 'disabled' : ''?>"><a vm-pjax href="<?php echo ($activePage != 1) ? '?display=dashboard&amp;mod=cdr&amp;sub=1000&amp;view=history&amp;page='.($activePage - 1) : '#' ?>">&laquo;</a></li>
		<?php for($i=1;$i<=$totalPages;$i++) {?>
			<li class="<?php echo ($activePage == $i) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=cdr&amp;sub=1000&amp;view=history&amp;page=<?php echo $i?>"><?php echo $i?> <?php echo ($activePage == $i) ? '<span class="sr-only">(current)</span>' : ''?></a></li>
		<?php } ?>
		<li class="<?php echo ($activePage == $totalPages) ? 'disabled' : ''?>"><a vm-pjax herf="<?php echo ($activePage != $totalPages) ? '?display=dashboard&amp;mod=cdr&amp;sub=1000&amp;view=history&amp;page='.($activePage + 1) : '#' ?>">&raquo;</a></li>
	</ul>
	<div class="player">
		<div id="freepbx_player" class="jp-jplayer"></div>
		<div id="freepbx_player_1" class="jp-audio">
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
			                <li id="title-text"><?php echo _('Unknown')?></li>
			            </ul>
			        </div>
		        </div>
		        <div class="jp-no-solution">
		            <span><?php echo _('Update Required')?></span>
		            <?php echo sprintf(_('To play the media you will need to either update your browser to a recent version or update your <a href="%s" target="_blank">Flash plugin</a>'),'http://get.adobe.com/flashplayer/');?>.
		        </div>
		    </div>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table table-hover table-bordered cdr-table">
			<thead>
			<tr class="cdr-header">
				<th><?php echo _('Date')?></th>
				<th><?php echo _('Time')?></th>
				<th><?php echo _('CID')?></th>
				<th><?php echo _('Source')?></th>
				<th><?php echo _('Desination')?></th>
				<th><?php echo _('Duration')?></th>
				<th><?php echo _('Controls')?></th>
			</tr>
			</thead>
		<?php if(!empty($calls)) {?>
			<?php foreach($calls as $call){?>
				<tr class="cdr-item" data-msg="<?php echo $call['uniqueid']?>">
					<td><?php echo date('Y-m-d',$call['timestamp'])?></td>
					<td><?php echo date('h:i:sa',$call['timestamp'])?></td>
					<td><?php echo $call['clid']?></td>
					<td><?php echo $call['src']?></td>
					<td><?php echo $call['dst']?></td>
					<td><?php echo $call['duration']?> sec</td>
					<td class="actions">
						<div>
							<a class="subplay" alt="<?php echo _('Play');?>">
								<i class="fa fa-play"></i>
							</a>
							<a class="download" alt="<?php echo _('Download');?>" href="#" target="_blank">
								<i class="fa fa-cloud-download"></i>
							</a>
						</div>
					</td>
				</tr>
			<?php }?>
		<?php } else { ?>
			<tr class="cdr-item">
				<td colspan="7"><?php echo _('No History');?></td>
			</tr>
		<?php } ?>
		</table>
	</div>
	<ul class="pagination pagination-sm">
		<li class="<?php echo ($activePage == 1) ? 'disabled' : ''?>"><a vm-pjax href="<?php echo ($activePage != 1) ? '?display=dashboard&amp;mod=cdr&amp;sub=1000&amp;view=history&amp;page='.($activePage - 1) : '#' ?>">&laquo;</a></li>
		<?php for($i=1;$i<=$totalPages;$i++) {?>
			<li class="<?php echo ($activePage == $i) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=cdr&amp;sub=1000&amp;view=history&amp;page=<?php echo $i?>"><?php echo $i?> <?php echo ($activePage == $i) ? '<span class="sr-only">(current)</span>' : ''?></a></li>
		<?php } ?>
		<li class="<?php echo ($activePage == $totalPages) ? 'disabled' : ''?>"><a vm-pjax herf="<?php echo ($activePage != $totalPages) ? '?display=dashboard&amp;mod=cdr&amp;sub=1000&amp;view=history&amp;page='.($activePage + 1) : '#' ?>">&raquo;</a></li>
	</ul>
</div>
