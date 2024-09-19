<?php

/**
 * OCD_FilterPortfolio Class.
 *
 * Generates a portfolio grid with modals and optional filtering.
 */

if ( ! defined( 'ABSPATH' ) ) die();

/**
 * Class OCD_FilterPortfolio
 *
 * Generates a portfolio grid with modals and optional filtering.
 * Provides a shortcode with configurable attributes and a settings page in Admin.
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'OCD_FilterPortfolio' ) ) :
class OCD_FilterPortfolio {
	/**
	 * Holds the slug of the component (the base name of the PHP file).
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * This component's version.
	 *
	 * @var string
	 */
	private $version = '1.0.1';

	/**
	 * Holds the array of values for the component's config and settings fields.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Holds the current options/settings for this component, retrieved from the database.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Constructor to initialize the component.
	 */
	public function __construct() {
		$this->slug = basename( __FILE__, '.php' ); // Set the slug to the base filename (without .php extension)
		$this->config(); // Load component configuration.

		add_image_size( 'ocd-filter-portfolio-thumb', 768, 432, array( 'center', 'top' ) );
		add_image_size( 'ocd-filter-portfolio-full', 1024, PHP_INT_MAX );

		// Register the shortcode [ocd_filter_portfolio].
		add_shortcode( 'ocd_filter_portfolio', array( $this, 'shortcode' ) );
	}

	/**
	 * Shortcode handler for rendering the component.
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $tag Shortcode tag.
	 * 
	 * @return string HTML output of the component.
	 */
	public function shortcode( $atts = array(), $content = '', $tag = 'ocd_filter_portfolio' ) {
		$options = ocd_get_options( $this );

		// Set default shortcode attributes, overriding with any provided.
		$atts = shortcode_atts( 
			array(
				'limit'          => -1,
				'show_filters'   => 'true',
				'category_slugs' => '',
			), 
			array_change_key_case( (array)$atts, CASE_LOWER ), 
			$tag 
		);

		// Sanitize and process attributes
		$limit = intval( $atts['limit'] );
		$show_filters = filter_var( sanitize_text_field( $atts['show_filters'] ), FILTER_VALIDATE_BOOLEAN );

		$category_slugs_str = trim( esc_html( sanitize_text_field( $atts['category_slugs'] ) ) );
		$category_slugs_str = str_replace( array( ',', '    ', '   ', '  ', '  ' ), ' ', $category_slugs_str );
		$category_slugs_r = array_map( 'esc_attr', explode( ' ', $category_slugs_str ) );

		$portfolio_page_id = intval( $options['portfolio_page_id'] );
		$portfolio_page_slug = get_post_field( 'post_name', $portfolio_page_id );

		// Set up post query
		$projects_query_args = array(
			'numberposts' => $limit,
			'post_type'   => $options['projects_post_type'],
		);

		// Add tax_query for project categories if provided in shortcode
		if ( ! empty( $category_slugs_r[0] ) ) {
			$projects_query_args['tax_query'] = array( array(
				'taxonomy' => 'project_category',
				'field'    => 'slug',
				'terms'    => $category_slugs_r,
			) );
		}

		$projects = get_posts( $projects_query_args );

		if ( empty( $projects ) ) {
			return '<p>'. esc_html( __( 'No Projects Found.', 'ocdutils' ) ) .'</p>';
		}
	
		// Create a unique ID for each instance of the shortcode to avoid conflicts.
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = $this->slug .'_' . $shortcode_i;
	
		// Enqueue dependencies, scripts, styles
		$this->enqueue_scripts();

		$projects_count = 0;
		$cats_counter = array();
		$items = '';
		foreach ( $projects as $project ) :



			if ( ! has_post_thumbnail( $project->ID ) ) continue;
			$projects_count++;
			$project_meta = get_post_meta( $project->ID );



			$project_tags_badges = '';
			$project_tags = get_the_terms( $project->ID, 'project_tag' );
			if ( ! empty( $project_tags ) ) {
				foreach ( $project_tags as $project_tag ) {
					$project_tags_badges .= '<li>'. $project_tag->name .'</li>';
				}
			}



			$project_categories_classes = '';
			$project_categories_links = '';
			$project_categories_badges = '';
			$project_categories = get_the_terms( $project->ID, 'project_category' );
			foreach ( $project_categories as $project_category ) {
				$project_categories_badges .= '<li>'. $project_category->name .'</li>';

				if ( ! empty( $atts['show_filters'] ) && 'false' !== $atts['show_filters'] && empty( $atts['projects_page_slug'] ) ) {
					$cats_counter[$project_category->term_id]++;

					$project_categories_classes .= ' ' . $project_category->slug;
					$project_categories_links .= '<li><a class="lmgpgdfs" title="'. $project_category->name .'" href="#" data-filter=".'. $project_category->slug .'" >'. $project_category->name .'</a></li>';
				} else {
					$project_categories_links .= '<li><a class="lmgpgdfs" title="'. $project_category->name .'" href="'. trailingslashit( home_url( $atts['projects_page_slug'] ) ) . '#' . $project_category->slug .'">'. $project_category->name .'</a></li>';
				}
			}



			$items .= '<article class="lmg-project-item'. $project_categories_classes .'">';

				$items .= '<div class="lmg-project-item-content">';
					$items .= '<a class="lmg-project-item-image lmgpmt" href="#" title="'. $project->post_title .'" data-micromodal-trigger="modal-'. $project->post_name .'">';
						$items .= get_the_post_thumbnail( $project->ID, 'lmg-project-grid-divi-thumb' );
					$items .= '</a>';
					$items .= '<h3>';
						$items .= '<a class="lmgpmt" href="#" title="'. $project->post_title .'" data-micromodal-trigger="modal-'. $project->post_name .'">'. $project->post_title .'</a>';
					$items .= '</h3>';
					$items .= '<ul class="lmg-project-item-categories">'. $project_categories_links .'</ul>';
				$items .= '</div>';

				$items .= '<div class="lmgpgd-micromodal" id="modal-'. $project->post_name .'" aria-hidden="true" style="display: none;">';
					$items .= '<div class="modal__overlay" tabindex="-1" data-micromodal-close>';
						$items .= '<div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modal-'. $project->post_name .'-title">';
							$items .= '<header class="modal__header">';
								$items .= '<h2 class="modal__title" id="modal-'. $project->post_name .'-title">Project: '. $project->post_title .'</h2>';
								$items .= '<button class="modal__close" aria-label="Close modal" data-micromodal-close></button>';
							$items .= '</header>';
							$items .= '<main class="modal__content" id="modal-'. $project->post_name .'-content">';

								$items .= '<div class="lmg-project-modal-detail">';
									$items .= '<div class="lmg-project-modal-detail-container">';

										if ( ! empty( $project_categories_badges ) ) {
											$items .= '<div class="lmg-project-modal-type">';
												$items .= '<h3>Project Type</h3>';
												$items .= '<ul class="lmg-project-modal-badges">'. $project_categories_badges .'</ul>';
											$items .= '</div>';
										}

										if ( ! empty( $project_tags_badges ) ) {
											$items .= '<div class="lmg-project-modal-features">';
												$items .= '<h3>Project Features</h3>';
												$items .= '<ul class="lmg-project-modal-badges">'. $project_tags_badges .'</ul>';
											$items .= '</div>';
										}

										$project_main_description = get_the_content( null, false, $project );
										if ( ! empty( $project_main_description ) ) {
											$items .= '<div class="lmg-project-modal-description">';
												$items .= '<h3 class="lmg-project-modal-description-section">Project Description</h3>';
												$items .= wpautop( $project_main_description );
											$items .= '</div>';
										}

									$items .= '</div>';
									$items .= '<div class="lmgpgd-spreader"><span>&nbsp</span></div>';
								$items .= '</div>';

								$items .= '<div class="lmg-project-modal-image">'. get_the_post_thumbnail( $project->ID, 'lmg-project-grid-divi-full' ) .'</div>';

							$items .= '</main>';
							$items .= '<footer class="modal__footer">';
								if ( ! empty( $project_meta['url'][0] ) ) {
									//$items .= '<a href="'. $project_meta['url'][0] .'" title="'. $project->post_title .'" rel="external" target="_blank">Visit the '. $project->post_title ." Website</a>";
									$items .= '<a href="'. $project_meta['url'][0] .'" title="'. $project->post_title .'" rel="external" target="_blank">Visit Website</a>';
								}
								$items .= '<button class="modal__btn" data-micromodal-close aria-label="Close this dialog window">Close</button>';
							$items .= '</footer>';
						$items .= '</div>';
					$items .= '</div>';
				$items .= '</div>';

			$items .= '</article>';



		endforeach;



		$filters = '';
		$filter_class_str = 'no-filter';
		if ( ! empty( $atts['show_filters'] ) && 'false' !== $atts['show_filters'] ) {
			$filter_class_str = 'filter';

			$categories = get_terms( array( 'taxonomy' => 'project_category', 'orderby' => 'count', 'order' => 'DESC' ) );

			$filters .= '<ul class="lmgpgdf" style="opacity: 0;">';
				$filters .= '<li><a href="#" class="lmgpgdfs" data-filter="*">Show All <span>'. $projects_count .'</span></a></li>';
				foreach ( $categories as $category ) {
					$filter_button_visibility_str = '';
					if ( 2 > $category->count ) $filter_button_visibility_str = ' style="display: none;"';

					$filters .= '<li'. $filter_button_visibility_str .'>';
						$filters .= '<a href="#" class="lmgpgdfs" data-filter=".'. $category->slug .'">'. $category->name .' <span>'. $cats_counter[$category->term_id] .'</span></a>';
					$filters .= '</li>';
				}
			$filters .= '</ul>';
		}
		

		
		$html  = '';
		$html .= '<div class="lmgpgd-wrapper">';
			$html .= $this->loading_spinner();
			$html .= $filters;
			$html .= '<div class="lmgpgd-container" style="opacity: 0;">';
				$html .= '<div class="lmgpgdis"></div>';
				$html .= $items;
			$html .= '</div>';
		$html .= '</div>';

		return $html;
	}


	private function loading_spinner() {
		ob_start();
		?>
		<style>
			.lmgpgd-spinner {
				display: grid;
				grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
				grid-auto-rows: 130px;
				place-items:center;
				box-sizing: border-box;
			}
	
			.lmgpgd-spinner > div {
				width: 50px;
				aspect-ratio: 1;
				display: grid;
				border: 4px solid #0000;
				border-radius: 50%;
				border-color: #ccc #0000;
				animation: lmgpgd-spin 1s infinite linear;
				box-sizing: border-box;
			}
	
			.lmgpgd-spinner > div::before,
			.lmgpgd-spinner > div::after {    
				content: '';
				grid-area: 1/1;
				margin: 2px;
				border: inherit;
				border-radius: 50%;
				box-sizing: border-box;
			}
	
			.lmgpgd-spinner > div::before {
				border-color: #f03355 #0000;
				animation: inherit; 
				animation-duration: .5s;
				animation-direction: reverse;
			}
	
			.lmgpgd-spinner > div::after { margin: 8px; }
	
			@keyframes lmgpgd-spin { 100%{transform: rotate(1turn)} }
		</style>
		<div class="lmgpgd-spinner"><div></div></div>
		<?php
		return ob_get_clean();
	}

	// Enqueue dependencies, scripts, styles
	private function enqueue_scripts() {
		wp_enqueue_style( 
			$this->slug, 
			OCD_UTILS_URL .'components/'. $this->slug .'/'. $this->slug .'.css', 
			array(), 
			$this->version, 
			'all' 
		);

		$script_args = array( 'strategy' => 'defer', 'in_footer' => true );

		$micromodal_url = OCD_UTILS_URL . 'node_modules/micromodal/dist/micromodal.min.js';
		$micromodal_version = ocd_nodejs_dependency_version( 'micromodal' );
		wp_enqueue_script( 'micromodal', $micromodal_url, array( 'jquery' ), $micromodal_version, $script_args );

		$imagesloaded_url = OCD_UTILS_URL . 'node_modules/imagesloaded/imagesloaded.pkgd.min.js';
		$imagesloaded_version = ocd_nodejs_dependency_version( 'imagesloaded' );
		wp_enqueue_script( 'imagesloaded', $imagesloaded_url, array( 'jquery' ), $imagesloaded_version, $script_args );

		$isotope_url = OCD_UTILS_URL . 'node_modules/isotope-layout/dist/isotope.pkgd.min.js';
		$isotope_version = ocd_nodejs_dependency_version( 'isotope-layout' );
		wp_enqueue_script( 'isotope-layout', $isotope_url, array( 'jquery' ), $isotope_version, $script_args );

		wp_enqueue_script( 
			$this->slug, 
			OCD_UTILS_URL .'components/'. $this->slug .'/'. $this->slug .'.js', 
			array( 'isotope-layout', 'imagesloaded' ), 
			$this->version, 
			$script_args 
		);
	}
























	/**
	 * Configures the settings for this component.
	 */
	private function config() {
		$this->config = $this->define_config_r();

		// Register this component's settings.
		ocd_register_settings( $this->config );

		add_action( 'admin_notices',  array( $this, 'admin_notices'                  ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes'                 ) );
		add_action( 'save_post',      array( $this, 'save_project_url_meta_box_data' ) );
	}

	/**
	 * Generates the usage instructions for the settings page.
	 *
	 * @return string HTML for usage instructions.
	 */
	private function usage_instructions() {
		ob_start();
		?>
		<div>
			<h4><?php _e( 'Attributes', 'ocdutils' ); ?></h4>
			<ul>
				<li><strong>limit</strong>: 
					<?php _e( 'Number of items to show in the grid.', 'ocdutils' ); ?> <?php _e( 'Default is', 'ocdutils' ) ?> <code>-1</code> (<?php _e( 'Show all', 'ocdutils' ) ?>)
				</li>
				<li><strong>show_filters</strong>: 
					<?php _e( 'Add buttons at the top for filtering by category.', 'ocdutils' ); ?> <?php _e( 'Default is', 'ocdutils' ) ?> <code>true</code>
				</li>
				<li><strong>category_slugs</strong>: 
					<?php _e( 'A comma-separated list of category slugs from which to show items.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>""</code> (<?php _e( 'Show all', 'ocdutils' ) ?>)
				</li>
			</ul>
			<h4><?php _e( 'Examples', 'ocdutils' ); ?></h4>
			<p><code>[ocd_filter_portfolio]</code> <?php _e( 'All default settings.', 'ocdutils' ) ?></p>
			<p><code>[ocd_filter_portfolio limit="6" show_filters="false" category_slugs="category-abc, category-qrs, category-xyz"]</code></p>
			<p><code>[ocd_filter_portfolio limit="21" show_filters="true"]</code></p>
			<p><?php _e( 'Use all attributes, or none, or mix-and-match. Any attributes omitted from the shortcode will use the default value.', 'ocdutils' ); ?></p>
			<br /><br />
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the config array.
	 *
	 * @return array The component's config values and settings fields.
	 */
	private function define_config_r() {
		/**************************************/
		// Don't use esc_html(), esc_attr(), etc. here.
		// Labels, descriptions, attributes, etc. will be escaped before output.
		// Only use __(), _e(), etc. for translatable strings.
		/**************************************/
		return array(
			'slug' => $this->slug,
			'label' => __( 'Filter Portfolio', 'ocdutils' ), // Tab name in the settings page.
			'sections' => array(
				array(
					'id' => 'portfolio',
					'label' => __( 'Portfolio Settings', 'ocdutils' ),
					'fields' => array(
						array(
							'id' => 'portfolio_page_id',
							'label' => __( 'Portfolio Page', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Choose the page that contains your full portfolio with filters. Other small portfolio shortcodes will link to this main one.', 'ocdutils' ),
							'required' => true,
							'options' => array( '' => __( '--Select a Page--', 'ocdutils' ) ) + wp_list_pluck( 
								get_pages(), 
								'post_title', 
								'ID' 
							),
						),
						array(
							'id' => 'projects_post_type',
							'label' => __( 'Projects Post Type', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Choose the post type that is used for your projects in your portfolio. You should use a post type that supports "title", "editor", and "thumbnail". Default is "Projects".', 'ocdutils' ),
							'required' => true,
							'default' => 'project',
							'options' => array( '' => __( '--Select a Post Type--', 'ocdutils' ) ) + wp_list_pluck( 
								get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' ), 
								'label', 
								'name' 
							),
						),
					),
				),
				array(
					'id' => 'usage',
					'label' => __( 'Shortcode Instructions', 'ocdutils' ),
					'description' => $this->usage_instructions(),
				),
				// Additional sections can be defined here.
			),
		);
	}

	/**
	 * Generates admin notices.
	 *
	 * @return string HTML for the admin notices.
	 */
	public function admin_notices() {
		if ( current_user_can( 'manage_options' ) ) {

			$options = ocd_get_options( $this );

			if ( empty( $options['portfolio_page_id'] ) ) {
				echo '<div class="notice notice-error"><p>';
					echo esc_html( __( 'In order for the "Filterable Portfolio with Modals" links to work correctly, you must choose a page.', 'ocdutils' ) );
					echo ' <a href="'. OCD_UTILS_SETTINGS_PAGE_LINK .'&tab='. $this->slug .'">'. esc_html( __( 'Go to Settings', 'ocdutils' ) ) .'</a>';
				echo '</p></div>';
			}

		}
	}

	/**
	 * Adds meta boxes to post type configured in Admin settings.
	 */
	public function add_meta_boxes() {
		$options = ocd_get_options( $this );

		add_meta_box(
			'ocd_filter_portfolio_project_url_meta_box', // Unique ID for the meta box
			esc_html( __( 'Project URL', 'ocdutils' ) ), // Meta box title
			array( $this, 'render_url_meta_box' ),       // Callback function to render the meta box
			$options['projects_post_type'],              // Post type where the meta box should appear
			'normal',                                    // Context (where the box should be placed: normal, side, etc.)
			'high'                                       // Priority (high, low, default)
	  );
	}

	/**
	 * Echoes out the meta box HTML.
	 */
	public function render_url_meta_box( $post ) {
		// Retrieve the meta value if it exists
		$project_url = '';
		$project_url = get_post_meta( $post->ID, '_ocd_filter_portfolio_project_url', true );
    
		// Nonce field for security
		wp_nonce_field( 'save_ocd_filter_portfolio_project_url_meta_box', 'ocd_filter_portfolio_project_url_meta_box_nonce' );
  
		// Meta box HTML
		echo '<p>';
			echo '<input';
				echo ' type="url"';
				echo ' id="ocd_filter_portfolio_project_url"';
				echo ' name="ocd_filter_portfolio_project_url"';
				echo ' value="'. esc_url( $project_url ) .'"';
				echo ' style="width: 100%;"';
			echo ' />';
		echo '</p>';
	}

	/**
	 * Handles saving of the input field data.
	 */
	public function save_project_url_meta_box_data( $post_id ) {
		// Check if our nonce is set and valid.
		if ( 
					! isset( $_POST['ocd_filter_portfolio_project_url_meta_box_nonce'] ) 
				|| ! wp_verify_nonce( $_POST['ocd_filter_portfolio_project_url_meta_box_nonce'], 'save_ocd_filter_portfolio_project_url_meta_box' ) 
		) {
			return;
	  }
 
	  // Check if this is an autosave or if the user has permission to edit the post.
	  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
	  }
 
	  if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
	  }
 
	  // Make sure the URL field is present.
	  if ( isset( $_POST['ocd_filter_portfolio_project_url'] ) ) {
			$project_url = sanitize_text_field( $_POST['ocd_filter_portfolio_project_url'] );
			update_post_meta( $post_id, '_ocd_filter_portfolio_project_url', $project_url );
	  }
	}
}
endif;

// Instantiate the component class.
$OCD_FilterPortfolio = new OCD_FilterPortfolio();

?>
