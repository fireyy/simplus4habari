<?php if ( $this->request->display_home || $this->request->display_404 ) { ?>
	<div id="primary" class="sidebar">
		<ul class="xoxo">
		<?php Plugins::act( 'theme_sidebar_top' ); ?> 
			<?php $theme->display('recentcomments.widget'); ?>
			<?php if ( Plugins::is_loaded('Blogroll') ) $theme->show_blogroll(); ?>
			<?php if ( Plugins::is_loaded('TagCloud') ) $theme->display('tagcloud.widget'); ?>
		<?php Plugins::act( 'theme_sidebar_bottom' ); ?>
		</ul>
	</div>
<?php } ?>		