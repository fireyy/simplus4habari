<?php

/**
 * simplusTheme is a custom Theme class for the Habari.
 *
 * @package Habari
 */

// We must tell Habari to use simplusTheme as the custom theme class:
define( 'THEME_CLASS', 'simplusTheme' );

/**
 * A custom theme for simplus output
 */
class simplusTheme extends Theme
{

	/**
	 * Execute on theme init to apply these filters to output
	 */
	public function action_init_theme()
	{
		if ( ! Plugins::is_loaded('HabariMarkdown') ) {
			// Apply Format::autop() to post content...
			Format::apply( 'autop', 'post_content_out' );
		}
		// Apply Format::autop() to comment content...
		Format::apply( 'autop', 'comment_content_out' );
		// Apply Format::tag_and_list() to post tags...
		Format::apply( 'tag_and_list', 'post_tags_out' );
		// Apply Format::nice_date() to post date...
		Format::apply( 'nice_date', 'post_pubdate_out', 'F j, Y' );
		// Apply Format::nice_date() to post date...
		Format::apply( 'nice_date', 'post_pubdate_time', 'g:i a' );
		// Format post date to ISO-8601...
		Format::apply( 'nice_date', 'post_pubdate_iso', 'c' );
		// Apply Format::nice_date() to comment date...
		Format::apply( 'nice_date', 'comment_date_out', 'F j, Y ‒ g:i a' );
		// Format comment date to ISO-8601...
		Format::apply( 'nice_date', 'comment_date_iso', 'c' );
		// Truncate content excerpt at "more" or 100 characters...
		Format::apply_with_hook_params( 'more', 'post_content_excerpt', '', 100, 1 );
		//Format::apply_with_hook_params( 'more', 'post_content_out', 'more', 100, 1 );
	}
	
	public function add_template_vars()
	{
		//Theme Options
		$this->assign( 'home_tab', 'Blog' ); //Set to whatever you want your first tab text to be.
		$this->assign( 'show_author' , false ); //Display author in posts 
		if( ! $this->assigned( 'pages' ) ) {
			$this->assign( 'pages', Posts::get( array( 'content_type' => 'page', 'status' => Post::status('published'), 'nolimit' => 1 ) ) );
		}
		if( ! $this->assigned( 'user' ) ) {
			$this->assign( 'user', User::identify() );
		}
		if( ! $this->assigned( 'recent_comments' ) ) {
			$this->assign( 'recent_comments', Comments::get( array('limit'=>10, 'status'=>Comment::STATUS_APPROVED, 'type'=>Comment::COMMENT, 'orderby'=>'date DESC' ) ) );
		}
		if( ! $this->assigned( 'recent_entries' ) ) {
			$this->assign( 'recent_entries', Posts::get( array('limit'=>10, 'content_type'=>1, 'status'=>2, 'orderby'=>'pubdate DESC' ) ) );
		}
		$this->assign( 'post_id', ( isset($this->post) && $this->post->content_type == Post::type('page') ) ? $this->post->id : 0 );

		parent::add_template_vars();
	}

	public function theme_show_tags ( $theme )
	{
		$sql="SELECT distinct t.tag_slug AS slug, t.tag_text AS text
			FROM ". DB::table( 'tags' ) ." t
			RIGHT JOIN ". DB::table( 'tag2post' ) ." tp
			ON t.id=tp.tag_id
			LEFT JOIN ". DB::table( 'posts' )." p
			ON p.id=tp.post_id
			WHERE p.status=2";
		$result= DB::get_results( $sql );
		$tags='';
		foreach ($result as $tag){
			$tags.= '<li><a href="' . '/tag/'.$tag->text. '" title="' . $tag->text .'" rel="tag" style="font-size: 125%;">' . $tag->text . '</a></li>'."\n";
		}

		return '<ul class="tag-cloud">' . $tags . '</ul>';
	}
	
	public function filter_post_tags_class( $tags )
	{
		if (! is_array($tags))
			$tags = array ($tags);
		return count($tags) > 0 ? 'tag-' . implode(' tag-', array_keys($tags)) : 'no-tags';
	}
	
	public function filter_post_tags_type( $tags )
	{
		$entry_type = array('entry','link','msg','photo','video');
		$types = "entry";
		if ( ! is_array( $tags ) )
			$tags = array ( $tags );
		foreach ($tags as $key => $value) {
			if(array_search($key,$entry_type)) $types = $key;
		}
		return $types;
	}

	public function theme_body_class($theme)
	{
		// Assigning <body> class(es)
		$body_class = array();
		if ($this->request->display_home) {
			$body_class[]= 'home';
			$body_class[]= 'multiple';
		}
		if ($this->request->display_entries) {
			$body_class[]= 'multiple';
		}
		if ($this->request->display_entries_by_date) {
			$body_class[]= 'date-archive';
			$body_class[]= 'archive';
			$body_class[]= 'multiple';
		}
		if ($this->request->display_entries_by_tag) {
			$body_class[]= 'tag-archive';
			$body_class[]= 'archive';
			$body_class[]= 'multiple';
		}
		if ($this->request->display_entry || $this->request->display_page) {
			$post_type_name= Post::type_name($this->posts->content_type);
			$body_class[]=  $post_type_name . '-' . $this->posts->slug;
			$body_class[]=  $post_type_name;
			$body_class[]= 'single';
		}
		if ($this->request->display_search) {
			$body_class[]= 'search';
			$body_class[]= 'multiple';
		}
		if ($this->request->display_404) {
			$body_class[]= 'four04';
		}

		//Get unique items
		$body_class= array_flip(array_flip($body_class));

		return count($body_class) > 0 ? ' class="' . implode(' ', $body_class) . '"' : '';
	}
	
	public function theme_title($theme)
	{
		$title= '';

		if ($this->request->display_entries_by_date && count($this->matched_rule->named_arg_values) > 0) {
			$date_string= '';
			$date_string.= array_key_exists('year', $this->matched_rule->named_arg_values) ? $this->matched_rule->named_arg_values['year'] : '' ;
			$date_string.= array_key_exists('month', $this->matched_rule->named_arg_values) ? '‒' . $this->matched_rule->named_arg_values['month'] : '' ;
			$date_string.= array_key_exists('day', $this->matched_rule->named_arg_values) ? '‒' . $this->matched_rule->named_arg_values['day'] : '' ;
			$title= sprintf(_t('%1$s &raquo; Chronological Archives of %2$s'), $date_string, Options::get('title'));
		}
		else
		if ($this->request->display_entries_by_tag && array_key_exists('tag', $this->matched_rule->named_arg_values)) {
			//$tag = (count($this->posts) > 0) ? $this->posts[0]->tags[$this->matched_rule->named_arg_values['tag']] : $this->matched_rule->named_arg_values['tag'] ;
			$tag = $this->matched_rule->named_arg_values['tag'];
			$title= sprintf(_t('%1$s &raquo; Taxonomic Archives of %2$s'), htmlspecialchars($tag), Options::get('title'));
		}
		else
		if (($this->request->display_entry || $this->request->display_page) && isset($this->posts)) {
			$title= sprintf(_t('%1$s &raquo; %2$s'), strip_tags($this->posts->title), Options::get('title'));
		}
/*
		else
		if ($this->request->display_search && array_key_exists('criteria', $this->matched_rule->named_arg_values)) {
			$title= sprintf(_t('%1$s &raquo; Search Results of %2$s'), htmlspecialchars($this->matched_rule->named_arg_values['criteria']), Options::get('title'));
		}
*/
		else
		{
			$title= Options::get('title');
		}

		if ($this->page > 1) {
			$title= sprintf(_t('%1$s &rsaquo; Page %2$s'), $title, $this->page);
		}

		return $title;
	}

	public function theme_mutiple_h1($theme)
	{
		$h1= '';

		if ($this->request->display_entries_by_date && count($this->matched_rule->named_arg_values) > 0) {
			$date_string= '';
			$date_string.= array_key_exists('year', $this->matched_rule->named_arg_values) ? $this->matched_rule->named_arg_values['year'] : '' ;
			$date_string.= array_key_exists('month', $this->matched_rule->named_arg_values) ? '‒' . $this->matched_rule->named_arg_values['month'] : '' ;
			$date_string.= array_key_exists('day', $this->matched_rule->named_arg_values) ? '‒' . $this->matched_rule->named_arg_values['day'] : '' ;
			$h1= '<h2 class="page-title">' . sprintf(_t('Posts written in %s'), $date_string) . '</h2>';
		}
		if ($this->request->display_entries_by_tag && array_key_exists('tag', $this->matched_rule->named_arg_values)) {
			//$tag = (count($this->posts) > 0) ? $this->posts[0]->tags[$this->matched_rule->named_arg_values['tag']] : $this->matched_rule->named_arg_values['tag'] ;
			$tag = $this->matched_rule->named_arg_values['tag'] ;
			$h1 = '<h2 class="page-title">' . sprintf(_t('Posts tagged with %s'), htmlspecialchars($tag)) . '</h2>';
		}
/*
		if ($this->request->display_search && array_key_exists('criteria', $this->matched_rule->named_arg_values)) {
			$h1 = '<h2 class="page-title">' . sprintf(_t('Search results for “%s”'), htmlspecialchars($this->matched_rule->named_arg_values['criteria'])) . '</h2>';
		}
*/
		return $h1;
	}

}

?>
