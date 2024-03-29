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

	private $class_name = '';
	private $default_options = array(
		'show_author' => false,
		'home_tab' => 'Blog',
		'show_entry_type_icon' => false,
		'entry_type_link' => 'link',
		'entry_type_quote' => 'quote',
		'entry_type_photo' => 'photo',
		'entry_type_video' => 'video'
	);
	
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
	
	/**
	 * On theme activation, set the default options
	 */
	public function action_theme_activation($file)
	{
		if (realpath($file) == __FILE__) {
			$this->class_name = strtolower(get_class($this));
			foreach ($this->default_options as $name => $value) {
				$current_value = Options::get($this->class_name . '__' . $name);
				if (is_null($current_value)) {
					Options::set($this->class_name . '__' . $name, $value);
				}
			}
		}
	}

	public function filter_theme_config($configurable)
	{
		$configurable = true;
		return $configurable;
	}

	/**
	 * Respond to the user selecting an action on the theme page
	 **/
	public function action_theme_ui($theme)
	{
		$ui = new FormUI(strtolower(get_class($this)));
		
		$ui->append('text', 'home_tab', 'option:' . $this->class_name . '__home_tab', _t('Link Text to Home'));
		$ui->home_tab->add_validator('validate_required');
		
		$ui->append('checkbox', 'show_author', 'option:' . $this->class_name . '__show_author', _t('Display author in posts'));
		$ui->append('checkbox', 'show_entry_type_icon', 'option:' . $this->class_name . '__show_entry_type_icon', _t('Display type icons for entry'));
		$ui->append( 'static', 'show_entry_type_icon_instruction', _t( 'IF you\'d like display type icons for entry,you must fill out following fields by the tag name of entry.' ) );
		$ui->append('text', 'entry_type_link', 'option:' . $this->class_name . '__entry_type_link', _t('Tag Name for entry type link'));
		$ui->append('text', 'entry_type_quote', 'option:' . $this->class_name . '__entry_type_quote', _t('Tag Name for entry type quote'));
		$ui->append('text', 'entry_type_photo', 'option:' . $this->class_name . '__entry_type_photo', _t('Tag Name for entry type photo'));
		$ui->append('text', 'entry_type_video', 'option:' . $this->class_name . '__entry_type_video', _t('Tag Name for entry type video'));

		// Save
		$ui->append('submit', 'save', _t('Save'));
		$ui->set_option('success_message', _t('Options saved'));
		$ui->out();
	}	
	
	public function add_template_vars()
	{
		//Theme Options
		$this->assign( 'home_tab', Options::get($this->class_name . '__home_tab') ); //Set to whatever you want your first tab text to be.
		$this->assign( 'show_author' , Options::get($this->class_name . '__show_author') ); //Display author in posts 
		$this->assign( 'show_entry_type_icon' , Options::get($this->class_name . '__show_entry_type_icon') ); //Display type icons for entry
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

	/**
	 * Returns an unordered list of all used Tags
	 */
	public function theme_show_tags ( $theme )
	{
		$sql="
			SELECT t.tag_slug AS slug, t.tag_text AS text, count(tp.post_id) as ttl
			FROM {tags} t
			INNER JOIN {tag2post} tp
			ON t.id=tp.tag_id
			INNER JOIN {posts} p
			ON p.id=tp.post_id AND p.status = ?
			GROUP BY t.tag_slug
			ORDER BY t.tag_text
		";
		$tags= DB::get_results( $sql, array(Post::status('published')) );

		foreach ($tags as $index => $tag) {
			$tags[$index]->url = URL::get( 'display_entries_by_tag', array( 'tag' => $tag->slug ) );
		}
		$theme->taglist = $tags;
		
		return $theme->fetch( 'taglist' );
	}
	
	public function filter_post_tags_class( $tags )
	{
		if (! is_array($tags))
			$tags = array ($tags);
		return count($tags) > 0 ? 'tag-' . implode(' tag-', array_keys($tags)) : 'no-tags';
	}
	
	public function filter_post_tags_type( $tags )
	{
		$entry_type = array('entry',Options::get($this->class_name . '__entry_type_link'),Options::get($this->class_name . '__entry_type_msg'),Options::get($this->class_name . '__entry_type_photo'),Options::get($this->class_name . '__entry_type_video'));
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

	public function theme_mutiple_h1($theme,$criteria)
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

		if ($this->request->display_search && isset($criteria)) {
			$h1 = '<h2 class="page-title">' . sprintf(_t('Search results for “%s”'), $criteria) . '</h2>';
		}

		return $h1;
	}

}

?>
