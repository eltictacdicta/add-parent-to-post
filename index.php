<?php
/*
 * Plugin Name:		Añadir Jerarquía (padre) al post
 * Description:		Plugin adds "parent & hierarchy" functionality to posts.
 * Text Domain:		anadir-jerarquia-padre-al-post
 * Domain Path:		/languages
 * Version:		    1.1
 * Plugin URI:		https://github.com/eltictacdicta/add-parent-to-post/archive/refs/heads/main.zip
 * Contributors: 	puvoxsoftware,ttodua,misterdigital
 * Author:		    Misterdigital
 * Author URI:		https://misterdigital.es
 * License:		    GPL-3.0
 * License URI:		https://www.gnu.org/licenses/gpl-3.0.html
 
 * @copyright:		Misterdigital, Puvox.software
*/


namespace AddHierarchyParentToPost
{
  if (!defined('ABSPATH')) exit;
  require_once( __DIR__."/library.php" );
  require_once( __DIR__."/library_wp.php" );

  class PluginClass extends \Puvox\wp_plugin
  {

	public function declare_settings()
	{
		
		$this->initial_static_options	= 
		[
			'has_pro_version'        => 0, 
            'show_opts'              => true, 
            'show_rating_message'    => true, 
            'show_donation_footer'   => true, 
            'show_donation_popup'    => true, 
            'menu_pages'             => [
                'first' =>[
                    'title'           => 'Añadir Jerarquía (padre) al post', 
                    'default_managed' => 'singlesite',            // network | singlesite
                    'required_role'   => 'install_plugins',
                    'level'           => 'submenu',
                    'page_title'      => 'Options page',
                    'tabs'            => [],
                ],
            ]
		];

		$this->initial_user_options		= 
		[	
			'hierarchy_permalinks_too'	=> 0,
			'custom_post_types'			=> "post,",
			'hierarchy_using' 			=> "query_post",  // "query_post"  or "modify_post_obj" or "rewrite" (worst case)
			'other_cpts_as_parent' 		=> "", 
			'other_cpts_as_parent_rest_too'=> false, 
		]; 
	}

	public function __construct_my()
	{
		add_action( 'registered_post_type', 				[$this, 'enable_hierarchy_fields'], 123, 2);
		add_filter( 'post_type_labels_'.$post_type='post',  [$this, 'enable_hierarchy_fields_for_js'], 11, 2);
		add_filter('the_content', [$this, 'mostrar_migasdepan'], 11, 2 );
		add_action( 'wp_head', function () { ?>
			<style>
				.migasdepan p {
					text-align: center;
					background-color: #eee;
					padding-top: 5px;
					padding-left: 10px;
					padding-bottom: 5px;
				}
			
				.migasdepan a:link{
					text-decoration:none;
				}
			
				.migasdepan .last{
					font-weight: bold;
				}
				.imagen-cluster {
					display: block;
					margin-bottom: 10px!important;
					position: relative;
					line-height: 0;
					text-align: center;
					overflow: hidden;
				}
				.titulo-cluster {
					font-size: 1.2em;
					margin-bottom: 10px!important;
				}
				.caja {
					border: 1px solid #ccc;
					padding: 20px;
					margin-bottom: 20px;
					background-color: #f9f9f9;
				}

				.titulo-caja {
					font-size: 1.5em;
					margin-bottom: 20px;
					text-align: center;
					color: #333;
				}

			</style>
			<?php } );  
		add_shortcode('mi_child_pages', [$this, 'mi_child_pages_mod'], 11, 2  );

		$this->cpt_as_parent_init();
		add_action('init', function () {
            // Only execute script when user has access rights
            if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
                return;
            }

            // Only execute script when rich editing is enabled
            if (get_user_option('rich_editing') !== 'true') {
                return;
            }

            // Add the JS to the admin screen
            add_filter('mce_external_plugins', function ($plugin_array) {
                $file = plugin_dir_url(__FILE__) . '/assets/js/add-child-shortcode.js';
                $plugin_array['cluster-child-button'] = $file;
                return $plugin_array;
            });

            // Register the Note to the editor
            add_filter('mce_buttons', function ($buttons) {
                array_push($buttons, 'cluster-child-button');
                return $buttons;
            });
        });
	}
 
	// ============================================================================================================== //
	// ============================================================================================================== //


	
	public function mi_child_pages_mod($atts) {
		ob_start();
		$p = shortcode_atts( array (
            'titulo' => ' Tambien te puede interesar:',
        ), $atts );
		$args = array(
			'post_type'		=> 'post',
			'post__not_in'  => array(get_the_ID()),
			'post_parent'	=> get_the_ID(),
			'post_status' 	=> 'publish', 
		);
			
	
		$query = new \WP_Query( $args );
		//print_r($query);
		if ( $query->have_posts() ) { 
			
		
			//$count = 0;
			echo '<div class="caja">';
			echo '<p class="titulo-caja">'.$p['titulo'].'</p>';

			echo '<div style="display: flex; flex-wrap: wrap;">';
			while ( $query->have_posts() ) : $query->the_post(); 

				echo '<article style="flex: 1 0 28%; margin: 1%; box-sizing: border-box;">';

				echo '<a href="' . get_permalink() . '" rel="bookmark" style="text-decoration: none;">';

				if ( has_post_thumbnail() ) {
					echo '<div class="imagen-cluster">';
					$thumbnail_url = get_the_post_thumbnail_url();
					echo '<div style="background-image: url(' . $thumbnail_url . '); width: 100%; height: 196px;"></div>';
					echo '</div>';
				}

				echo '<p class="titulo-cluster" style="text-align: center;">' . get_the_title() . '</p>';
				echo '</a>';

				echo '</article>';
			endwhile;
			echo '</div>';

			echo '</div>'; // Fin de .caja
			?>
			
		</div>
	
		<?php
			
		}
		
		wp_reset_postdata();
		
		return ob_get_clean();
	}
		
		
	public function mostrar_migasdepan( $content ) {
		if (wp_get_post_parent_id(get_the_ID())) {
			if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
				$custom_content = '<div class="migasdepan">'.do_shortcode( '[rank_math_breadcrumb]' ).'</div>';
			} elseif (is_plugin_active('wordpress-seo/wp-seo.php')) {
				$custom_content = '<div class="migasdepan">'.do_shortcode( '[wpseo_breadcrumb]' ).'</div>';
			} else {
				$custom_content = '';
			}
			$custom_content .= $content;
			return $custom_content;
		} else {
			return $content;
		} 
	}
		
		

	public function deactivation_funcs($network_wide){ 	
		if ( is_multisite() ) {  // && $network_wide 
			global $wpdb;
			$blogs = $wpdb->get_col("SELECT blog_id FROM ". $wpdb->blogs);
			foreach ($blogs as $blog_id) {
				switch_to_blog($blog_id);
				$this->flush_rules_original(); 		
				restore_current_blog();
			} 
		} 
		else {
			$this->flush_rules_original(); 
		}
		//in activtion could have been: $this->add_rewrites(); 	
	}	
	
  	public function flush_rules_original()	{
		//$this->update_option_CHOSEN('rewrite_rules',  $this->get_option_CHOSEN('rewrite_rules_BACKUPED__AHPTP') );
		$this->helpers->flush_rules(false);
	}
	
	
	// ============================================================================================================== //
	// ============================================================================================================== //

	// register_post_type_args   //$args['rewrite']['slug']='/';


	public function init_action()	{
		$this->flush_rules_if_needed(__FILE__);
	}

	// note, with 'registered_post_type' argument $post_type_object is  same as  $GLOBALS['wp_post_types']['post'], but globalized one   (from:   wp-includes/post: 1120 ), however, they behave samely	


	// =====================================================================
	// ==============   Add PARENT FIELD to POST TYPE support    ===========

	public function enable_hierarchy_fields($post_type, $post_type_object){
		if($post_type== 'post' ){	
			$post_type_object->hierarchical = true;
			$GLOBALS['_wp_post_type_features']['post']['page-attributes']=true;
		}
	}

	public function enable_hierarchy_fields_for_js($labels){
		$labels->parent_item_colon='Parent Post';
		return $labels;
	}
	// =====================================================================






	// ===================================================
	// ==============   Start URL hierarchy    ===========

	// method 1 (seems useless): child posts  work, pages (or other things) go to 404
	public function method__modify_post_obj($post_type, $post_type_object){
		$Type = 'post';
		if($post_type==$Type){	
			$post_type_object->rewrite = ['with_front'=>false, 'slug'=>'/', 'feeds' => 1];    // otherwise bugs in class-wp-post-type.php 566 ;  [pages] => 1 [feeds] => 1 [ep_mask] => 1
			$post_type_object->query_var =  'post'; //'post' or true;  without this line, everything goes 404
			// at this moment, we cant call that function, so, call later
			add_action('init', function(){
				// this function causes exactly to finalize everything before. so, it makes hierarchied post to work, but break other post types (page or etc..) to 404
				$GLOBALS['wp_post_types']['post']->	add_rewrite_rules();   // ref: pastebin(dot)com/raw/3yVg8jXp
				// $this->add_rewrite_for_post();
			} ); 
		}
	}



	// method 2 (also, independent)
	public function method__query( $query ) 
	{ 
		$pType = 'post';
		$q=$query;

		if( $q->is_main_query() && !is_admin() ) {
			//at first, check if it's attachment, because only attachment meet this rewrite like hierarchied post
			if( 
				true
									// needs to be attachement: wp-includes\class-wp-query.php, but everything happens in parse_request(), because of rewrite match, that is attachement
				 //&& ( (!is_multisite() && $q->is_attachment) || (is_multisite() && !$q->is_attachment) ) )
			){
				$possible_post_path = trailingslashit( preg_replace_callback('/(.*?)\/((page|feed|rdf|rss|rss2|atom)\/.*)/i', function($matches) { return $matches[1]; } ,  $this->path_after_blog() ) );

				//if seems hierarchied - 2 slashes at least, like:  parent/child/
				if(substr_count($possible_post_path, "/") >= 2) {
					$post=get_page_by_path($possible_post_path, OBJECT, $pType); 
					if ($post){
						// create query
						//no need of $q->init();	
						$q->parse_query( ['post_type'=>[$pType]  ] ) ;  //better than $q->set('post_type', 'post');
						$q->is_home		= false; 
						//$q->is_page		= $method_is_page ? true : false; 
						$q->is_single	= true; 
						$q->is_singular	= true; 
						$q->queried_object_id=$post->ID;  
						$q->set('page_id',$post->ID);
								//add_action('wp', function (){   v($GLOBALS['wp_query']);	});
						return $q;
					}
					//if parent was "custom post type" selected, like  /my-cpt/my-post
					else if ($this->cpt_parent_enabled())
					{
						return $this->cpt_parented_query($q, $possible_post_path, $pType); 
					}
				}
			}
		}
		return $q;
	} 

	public function hierarchy_for_custom_post($post_type, $post_type_object){
		$custom_posts = !empty( $this->opts["custom_post_types"] ) ? array_filter( explode(",", $this->opts["custom_post_types"] ) ) : ['post'];
		foreach ($custom_posts as $each_type){
			$each_type = trim($each_type);
			if($post_type==$each_type){
				$post_type_object->hierarchical = true;
			}
		}
	}
		


	
	
	// #############################################################
	// ########## show other cpt in Parent Page dropdown ###########
	// #############################################################
	public function cpt_parent_enabled()
	{
		return (!empty($this->opts['other_cpts_as_parent']));
	}

	public function cpt_as_parent_init()
	{
		if ($this->cpt_parent_enabled())
		{
			add_filter('page_attributes_dropdown_pages_args', [$this,'page_attrs'], 10, 2);//$args['hierarchical'] = true; 
			
			//needed for gutenberg
			if ($this->opts['other_cpts_as_parent_rest_too'])
			{
				add_filter('register_post_type_args', [$this, 'register_cpt_args_gutenberg'], 10, 2);  
				add_filter( "rest_"."post"."_query", [$this, 'response_from_gutenberg'], 10, 2 );
			}
		}
	}


	public function page_attrs($dropdown_args, $post){
		//$dropdown_args['post_type'] = 'cpt';
		//add_filter('wp_dropdown_pages', 'func22', 10, 3);
		if ($post->post_type=="post")
			add_filter( 'get_pages', [$this, 'change_cpts'], 10, 2);
		return $dropdown_args;
	}
	
	public function change_cpts( $pages, $parsed_args){
		remove_filter('get_pages', [$this, 'change_cpts'], 10, 2);
		$posts = get_posts(['post_type'=>$this->post_types_group(), 'posts_per_page'=>-1 ]);
		return $posts;
	}
	public function post_types_group(){ return array_merge(['post'],  array_filter( explode(",", $this->opts['other_cpts_as_parent']))); }
	
	
	public function register_cpt_args_gutenberg($args, $post_type){ 
		if ($post_type == 'cpt'){ $args['show_in_rest'] = true; } return $args; 
	}
	public function response_from_gutenberg( $args, $request)
	{
		if (isset($_GET['parent_exclude']) && isset($_GET['context']) && $_GET['context']=='edit')
			$args['post_type']=$this->post_types_group() ;
		return $args;
	}
	
	public function cpt_parented_query($q, $possible_post_path, $pType)
	{	
		$slug = basename($possible_post_path);
		$parent_slug = basename(dirname($slug));
		$posts = get_posts(['name'=>$slug, 'post_type'=>$pType, 'post_status'=>'publish', 'numberposts'=>1]);
		$post = false;
		if(!empty($posts[0]))
		{
			$post = $posts[0];
			$iterated_post = $post;
			while( !empty($iterated_post->post_parent) )
			{
				$iterated_post = get_post($iterated_post->post_parent);
				if (!in_array($iterated_post->post_type, $this->post_types_group()))
					return $q;
			}
		}
		if ($post){
			// create query
			//no need of $q->init();	
			$q->parse_query( ['post_type'=>[$pType]  ] ) ;  //better than $q->set('post_type', 'post');
			$q->is_home		= false; 
			//$q->is_page		= $method_is_page ? true : false; 
			$q->is_single	= true; 
			$q->is_singular	= true; 
			$q->queried_object_id=$post->ID;  
			$q->set('page_id',$post->ID);
					//add_action('wp', function (){   v($GLOBALS['wp_query']);	});
			return $q;
		}
		return $q;
	}
	// ######################################################
	
	
	
	
	
	
	
	
	
	// ====================  POST_LINK 		(WHEN  %postname% tags not available yet {after 10'th priority it's available} ) ======================// 
	//i.e:	http://example.com/post_name   OR http://example.com/%if_any_additinonal_custom_structural_tag%/post_name
	public function change_permalinks( $permalink, $post=false, $leavename=false ) { 
		$postTypes = !empty($this->opts["custom_post_types"]) ? array_filter(explode(",", $this->opts["custom_post_types"] )) : ['post'];
		foreach ($postTypes  as $each_post_type){
			if($post->post_type == $each_post_type){
				// return if %postname% tag is not present in the url:
				if ( false === strpos( $permalink, '%postname%'))  { 		return $permalink;			}
				$permalink = $this->helpers->remove_extra_slashes('/'. $this->helpers->get_parent_slugs_path($post). '/'. '%postname%' );
			}
		}
		return $permalink;
	}
	

	// =========================================================================================================================================== //	
 

	public function alert_if_not_pretty_permalinks()
	{
		if( $this->opts['hierarchy_permalinks_too'] &&  !get_option('permalink_structure') ){
			echo '<script>alert("'. __( 'You have chosen to have hierarchied permalinks for your posts, but at first you have to set correct permalinks in Settings > Permalinks, otherwise it will not work!', 'add-parent-to-post' ).'");</script>';
		}
	}
 
 

	// =================================== Options page ================================ //
	public function opts_page_output()
	{ 
		$this->settings_page_part("start", "first"); 
		?>

		<style> 
		body .disabled_notice{color:red; font-size: 1.1em;} 
		.checkboxes_disabled {background: pink; }
		.mylength-text {width:70px; }
		.clearboth {clear:both;}
		.displaced	{text-align:left; float: left; background:#e7e7e7; }
		body .MyLabelGroup label{display:block!important;}
		</style>

		<?php if ($this->active_tab=="Options") 
		{ 
			//if form updated
			if( $this->checkSubmission() ) 
			{ 
				$this->opts['hierarchy_permalinks_too']	= 0 ; //yo trabajo siempre sin url jerarquica con lo cual no doy esta opcion
				$this->opts['hierarchy_using']			= sanitize_key($_POST[ $this->plugin_slug ]['hierarchy_using']) ; 
				$this->opts['custom_post_types']		= trim(sanitize_text_field($_POST[ $this->plugin_slug ]['custom_post_types'])) ; 
				$this->opts['other_cpts_as_parent']		= trim(sanitize_text_field($_POST[ $this->plugin_slug ]['other_cpts_as_parent'])) ; 
				$this->opts['other_cpts_as_parent_rest_too']= !empty($_POST[ $this->plugin_slug ]['other_cpts_as_parent_rest_too']) ; 
				$this->opts['last_update_time']			= time() ;  // need only for flush rules
				$this->update_opts(); 
				$this->flush_rules_checkmark(true);
			}
			?>

			<?php //_e('(Note: in most cases, who want to have a hierarchy structure for the site, it\'s better to use <code>Custom Post Type</code> (there are other plugins for it), because <code>Custom Post</code> Type has native support for hierarchy and ideally, it\s better then using default <code>Post</code> Type as hierarchy. However, if you are sure you need this our plugin, go on... )', 'add-parent-to-post'); ?>
			<i><?php _e('(Note: This plugin is experimental in it\'s nature, as it modifies the WordPress query for posts and is not thoroughly integrated in core behavior. So, take this plugin as an experimental plugin.)', 'add-parent-to-post'); ?></i>

			<form class="mainForm" method="post" action="">

			<table class="form-table">
				<tr class="def hierarchical">
					<th scope="row">
						<?php _e('Add Dropdown in Post-Editor', 'add-parent-to-post'); ?>
					</th>
					<td>
						<fieldset>
							<p class="checkboxes_disabled">
								<label>
									<input disabled="disabled" type="radio" value="0" ><?php _e( 'No', 'add-parent-to-post' );?>
								</label>
								<label>
									<input disabled="disabled" type="radio" value="1" checked="checked"><?php _e( 'Yes', 'add-parent-to-post' );?>
								</label>
							</p>
							<p class="description">
							<?php _e('Ability to choose parent page, like on <a href="'.$this->helpers->baseURL.'/assets/media/parent-choose.png" title="sreenshot" target="_blank">this screenshot</a>. Without this field, plugin can\'t work at all, so the option is not changeable. This option guarantees that wordpress native functions can correctly determine the child-parent post relations (So, posts can have "parent" field set in database). However, that doesnt guarentee the HIERARCHIED URLS will work - that is another subject (see below).', 'add-parent-to-post'); ?>
							</p>
						</fieldset>
					</td>
				</tr>
				
				//quitado el campo de jerarquia de url
				
				
				
				
				
				
				<tr class="def hierarchical">
					<th scope="row">
						<?php _e('Enable "Parent Page" capability for other Custom-Post-Types too (if they does not have support already)', 'add-parent-to-post'); ?>
					</th>
					<td <?php //$this->pro_field();?>>
						<fieldset>
							<div class="clearboth"></div>
							<div class="">
								<label for="custom_post_types">
									<?php //_e('Add hierarchy to <b>Custom Post Types</b>:', 'add-parent-to-post');?>
								</label>
								<input name="<?php echo $this->plugin_slug;?>[custom_post_types]" id="custom_post_types" class="regular-text" type="text" placeholder="<?php _e('book, fruits, news ...', 'add-parent-to-post');?>" value="<?php echo $this->opts['custom_post_types']; ?>" > 
								<p class="description">
								<?php _e('(You can insert multiple CPT base names, comma delimeted)', 'add-parent-to-post');?>
								</p>
							</div>
							
						</fieldset>
					</td>
				</tr>
				
				
				
				<tr class="def hierarchical">
					<td colspan=2></td>
				</tr>
				
				<tr class="def hierarchical">
					<th scope="row">
						<span style="color:red; font-weight:bold;"><?php _e('EXPERIMENTAL!', 'add-parent-to-post');?></span> <br/><?php _e('Use other Custom-Post-Type items in "Parent Page" list on posts edit page (note, that Custom-Post-Type should have "hierarchical" mode)', 'add-parent-to-post'); ?>
					</th>
					<td <?php //$this->pro_field();?>>
						<fieldset>
							<div class="clearboth"></div>
							<div class="">
								<label for="other_cpts_as_parent">
									<?php //_e('Add hierarchy to <b>Custom Post Types</b>:', 'add-parent-to-post');?>
								</label>
								<input name="<?php echo $this->plugin_slug;?>[other_cpts_as_parent]"  class="regular-text" type="text" placeholder="<?php _e('book, fruit, ...', 'add-parent-to-post');?>" value="<?php echo $this->opts['other_cpts_as_parent']; ?>" > 
								<p class="description">
								<?php _e('(You can insert multiple CPT base names, comma delimeted. To disable, leave empty)', 'add-parent-to-post');?>
								</p>
									
									
								<p class="description">
								<br/><?php _e('Enable in gutenberg-editor too', 'add-parent-to-post');?> <input name="<?php echo $this->plugin_slug;?>[other_cpts_as_parent_rest_too]" class="regular-text" type="checkbox" value="1" <?php checked($this->opts['other_cpts_as_parent_rest_too']);?> /> 
								<?php _e('(This enables Custom-Post-Type to be used in wp-rest-api. This option has not been thoroughly tested if it conflicts any other wp-rest-api query, so use at your own responsibility.)', 'add-parent-to-post');?> 
								</p>
							</div>
							
						</fieldset>
					</td>
				</tr>
				
				
				<tr class="def"> 
					<td colspan="2">
						<p class="description">
							<?php echo sprintf( __( 'Note! Everytime you update settings on this page, you have then to click once "Save" button in the <a href="%s" target="_blank">Permalinks</a> page! Then on the front-end, refresh page once (with clicking <code>Ctrl+F5</code>) and new rules will start work. After that, also check if your link works correctly (feed,rss,attachments and categories).', 'add-parent-to-post' ), (is_network_admin() ? 'javascript:alert(\'You should go to specific sub-site permalink settings\'); void(0);' : admin_url("options-permalink.php"))  ) ;?> 
						</p>
					</td>
				</tr>
			</table>
			
			<?php $this->nonceSubmit(); ?>

			</form>

			<script>
			PuvoxLibrary.radiobox_onchange_hider('input[name=add-parent-to-post\\[hierarchy_permalinks_too\\]]',  '0',  '.hierarchyMethodDesc');
			</script>
		<?php 
		}
		

		$this->settings_page_part("end", "");
	} 





  } // End Of Class

  $GLOBALS[__NAMESPACE__] = new PluginClass();

} // End Of NameSpace


 
?>