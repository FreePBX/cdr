<ul class="pagination pagination-sm">
	<li class="<?php echo ($activePage == 1) ? 'disabled' : ''?>"><a vm-pjax href="<?php echo ($activePage != 1) ? '?display=dashboard&amp;mod=cdr&amp;sub='.$_REQUEST['sub'].'&amp;view=history&amp;page='.($activePage - 1) : '#' ?>">&laquo;</a></li>
	<?php for($i=$startPage;$i<=$endPage;$i++) {?>
		<li class="<?php echo ($activePage == $i) ? 'active' : ''?>"><a vm-pjax href="?display=dashboard&amp;mod=cdr&amp;sub=<?php echo $_REQUEST['sub']?>&amp;view=history&amp;page=<?php echo $i?>"><?php echo $i?> <?php echo ($activePage == $i) ? '<span class="sr-only">(current)</span>' : ''?></a></li>
	<?php } ?>
	<li class="<?php echo ($activePage == $totalPages) ? 'disabled' : ''?>"><a vm-pjax href="<?php echo ($activePage != $totalPages) ? '?display=dashboard&amp;mod=cdr&amp;sub='.$_REQUEST['sub'].'&amp;view=history&amp;page='.($endPage + 1) : '#' ?>">&raquo;</a></li>
</ul>
