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
	 * Holds the taxonomies for all registered post types.
	 *
	 * @var array
	 */
	private $post_type_taxonomies = array();

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
				'limit'        => -1,
				'show_filters' => 'true',
				'categories'   => '',
				'tags'         => '',
				'projects'     => '',
			), 
			array_change_key_case( (array)$atts, CASE_LOWER ), 
			$tag 
		);

		// Sanitize and process attributes
		$limit = intval( $atts['limit'] );
		$show_filters = filter_var( $atts['show_filters'], FILTER_VALIDATE_BOOLEAN );

		$portfolio_page_id = intval( $options['portfolio_page_id'] );
		$portfolio_page_url = esc_url( get_permalink( $portfolio_page_id ) );

		$tax_cats = sanitize_title( $options['projects_tax_cats'] ) ?: '';
		if ( '' === $tax_cats ) {
			// TODO: If the user didn't specify a taxonomy for "Categories" in the options
			// maybe i want to loop through the taxonomies for the chosen post type and pick one that contains substr "cat"
			// or maybe don't do this, its prob not necessary
		}

		$tax_tags = sanitize_title( $options['projects_tax_tags'] ) ?: '';
		if ( '' === $tax_tags ) {
			// TODO: If the user didn't specify a taxonomy for "Tags" in the options
			// maybe i want to loop through the taxonomies for the chosen post type and pick one that contains substr "tag"
			// or maybe don't do this, its prob not necessary
		}

		// Set up post query
		$query_args = array(
			'post_type'      => $options['projects_post_type'],
			'post_status'    => 'publish',
			'numberposts'    => $limit,
			'posts_per_page' => $limit,
			'orderby'        => 'menu_order date',
			'order'          => 'DESC',
		);

		if ( ! empty( $atts['categories'] ) && ! empty( $tax_cats ) ) {
			$categories_r = $this->term_slugs_ids_str_to_arrays( $atts['categories'] );

			if ( ! empty( $categories_r['term_ids'] ) || ! empty( $categories_r['slugs'] ) ) {
				$query_args['tax_query']['relation'] = 'OR';
			}

			if ( ! empty( $categories_r['term_ids'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $tax_cats,
					'field'    => 'term_id',
					'terms'    => $categories_r['term_ids'],
				);
			}

			if ( ! empty( $categories_r['slugs'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $tax_cats,
					'field'    => 'slug',
					'terms'    => $categories_r['slugs'],
				);
			}
		}

		if ( ! empty( $atts['tags'] ) && ! empty( $tax_tags ) ) {
			$tags_r = $this->term_slugs_ids_str_to_arrays( $atts['tags'] );

			if ( ! empty( $tags_r['term_ids'] ) || ! empty( $tags_r['slugs'] ) ) {
				$query_args['tax_query']['relation'] = 'OR';
			}

			if ( ! empty( $tags_r['term_ids'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $tax_tags,
					'field'    => 'term_id',
					'terms'    => $tags_r['term_ids'],
				);
			}

			if ( ! empty( $tags_r['slugs'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $tax_tags,
					'field'    => 'slug',
					'terms'    => $tags_r['slugs'],
				);
			}
		}

		// Allow filtering of query arguments
		$query_args = apply_filters( 'ocd_filter_portfolio_query_args', $query_args );

		$projects = get_posts( $query_args );

		if ( ! empty( $atts['projects'] ) ) {
			$priority_project_ids_r = array_values(
				array_unique(
					array_map(
						'intval',
						array_filter(
							array_map( 'trim', explode( ',', $atts['projects'] ) ),
							static fn( $id ) => ctype_digit( $id ) && (int) $id > 0
						)
					)
				)
			);

			$priority_projects_query_args = $query_args;
			$priority_projects_query_args['post__in'] = $priority_project_ids_r;

			$projects = get_posts( $priority_projects_query_args );


			/********************************************************************************************************************************************
			*********************************************************************************************************************************************
			// This part was going to be used for adding priority projects to other projects already included via cateroy parameter or other params
			// but I sccrapped it and decided to make it so that if the "projects" param is included, it just shows only those projects, disregarding other params.
			// If you use this, it's almost done, but it still shows all projects if no other parameters besides "projects" are set,
			// it needs to be fixed to work correctly when the "projects" param is the only filter set.
			$existing_project_ids_r = wp_list_pluck( $projects, 'ID' );
			$priority_project_ids_r = array_values(
				array_unique(
					array_map(
						'intval',
						array_filter(
							array_map( 'trim', explode( ',', $atts['projects'] ) ),
							static fn( $id ) => ctype_digit( $id ) && (int) $id > 0
						)
					)
				)
			);

			if ( ! empty( $priority_project_ids_r ) ) {
				$all_project_ids_r = array_unique( array_merge( $priority_project_ids_r, $existing_project_ids_r ) );
				$all_project_ids_r = array_slice( $all_project_ids_r, 0, $limit );

				// echo '<pre>' . print_r( $all_project_ids_r, true ) . '</pre>';
				// echo '<pre>' . print_r( $priority_project_ids_r, true ) . '</pre>';
				// die();

				$priority_projects_query_args = $query_args;
				$priority_projects_query_args['post__in'] = $all_project_ids_r;

				$projects = get_posts( $priority_projects_query_args );
			}
			*******************************************************************************************************************************************
			******************************************************************************************************************************************/
		}

		if ( empty( $projects ) ) {
			return '<p>'. esc_html__( 'No Projects Found.', 'ocdutils' ) .'</p>';
		}
	
		// Create a unique ID for each instance of the shortcode to avoid conflicts.
		static $shortcode_i = -1;
		$shortcode_i++;
		$shortcode_id = $this->slug .'-' . $shortcode_i;
	
		// Enqueue dependencies, scripts, styles
		$this->enqueue_scripts();

		$projects_count = 0;
		$cats = array();
		$items = '';
		foreach ( $projects as $project ) {

			if ( ! has_post_thumbnail( $project->ID ) ) continue;
			$projects_count++;
			$project_url = get_post_meta( $project->ID, '_ocd_filter_portfolio_project_url', true );

			$project_tags_badges = '';
			$project_tags = get_the_terms( $project->ID, $tax_tags );
			if ( is_array( $project_tags ) ) {
				foreach ( $project_tags as $project_tag ) {
					$project_tags_badges .= '<li>'. esc_html( $project_tag->name ) .'</li>';
				}
			}

			$project_categories_data_attr_str = '';
			$project_categories_links = '';
			$project_categories = get_the_terms( $project->ID, $tax_cats );
			if ( is_array( $project_categories ) ) {
				foreach ( $project_categories as $category ) {
					$cat_id = null;
					$cat_id = $category->term_id;

					if ( ! isset( $cats[$cat_id] ) ) {
						$cats[$cat_id] = array(
							'slug'  => esc_attr( $category->slug ),
							'name'  => esc_html( $category->name ),
							'count' => 0,
						);
					}
					$cats[$cat_id]['count']++;

					$project_categories_data_attr_str .= ' ' . $cats[$cat_id]['slug'];

					$href = '#' . $cats[$cat_id]['slug'];
					if ( ! empty( $portfolio_page_url ) ) {
						$href = trailingslashit( $portfolio_page_url ) . $href;
					}
					$project_categories_links .= '<li>';
						$project_categories_links .= '<a title="'. $cats[$cat_id]['name'] .'" href="'. $href .'" data-ocdfp-filter="'. $cats[$cat_id]['slug'] .'">';
							$project_categories_links .= $cats[$cat_id]['name'];
						$project_categories_links .= '</a>';
					$project_categories_links .= '</li>';
				}
				$project_categories_data_attr_str = trim( $project_categories_data_attr_str );
			}

			$post_thumbnail_id = get_post_thumbnail_id( $project->ID );
			$meta = wp_get_attachment_metadata( $post_thumbnail_id );

			if ( isset( $meta['sizes']['ocd-filter-portfolio-thumb'] ) ) {
				$project_image_thumb = get_the_post_thumbnail( $project->ID, 'ocd-filter-portfolio-thumb', array( 'style' => 'width: 768px;' ) );
			} else {
				$project_image_thumb = get_the_post_thumbnail( $project->ID, 'thumbnail', array( 'style' => 'width: 768px;' ) );
			}

			if ( isset( $meta['sizes']['ocd-filter-portfolio-full'] ) ) {
				$project_image_full = get_the_post_thumbnail( $project->ID, 'ocd-filter-portfolio-full', array( 'style' => 'width: 1024px;' ) );
			} else {
				$project_image_full = get_the_post_thumbnail( $project->ID, 'full', array( 'style' => 'width: 1024px;' ) );
			}

			$item_id = $project->post_name .'-'. $shortcode_i;

			$items .= '<article class="ocdfp-item" data-categories="'. $project_categories_data_attr_str .'">';

				$items .= '<div class="ocdfp-item-image" data-micromodal-trigger="'. $item_id .'">';
					$items .= $project_image_thumb;
				$items .= '</div>';
				$items .= '<h3 class="ocdfp-item-title" data-micromodal-trigger="'. $item_id .'">'. $project->post_title .'</h3>';
				$items .= ! empty( $project_categories_links ) ? '<ul class="ocdfp-categories">'. $project_categories_links .'</ul>' : '';

				$items .= '<div class="ocdfp-modal" id="'. $item_id .'" aria-hidden="true" style="display: none;">';
					$items .= '<div class="modal-overlay" tabindex="-1" data-micromodal-close>';
						$items .= '<div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="'. $item_id .'-title">';

							$items .= '<header class="modal-header">';
								$items .= '<h2 class="modal-title" id="'. $item_id .'-title"><span>'. esc_html__( 'Project:', 'ocdutils' ) .'</span> '. $project->post_title .'</h2>';
								$items .= '<button class="modal-close" aria-label="'. esc_html__( 'Close modal', 'ocdutils' ) .'" data-micromodal-close></button>';
							$items .= '</header>';

							$items .= '<section class="modal-content">';

								$items .= '<div class="ocdfp-detail-wrapper">';
									$items .= '<div class="ocdfp-detail">';

										if ( ! empty( $project_categories_links ) ) {
											$items .= '<h3 class="ocdfp-detail-section">'. $options['projects_tax_cats_label'] .'</h3>';
											$items .= '<ul class="ocdfp-categories">'. $project_categories_links .'</ul>';
										}

										if ( ! empty( $project_tags_badges ) ) {
											$items .= '<h3 class="ocdfp-detail-section">'. $options['projects_tax_tags_label'] .'</h3>';
											$items .= '<ul class="ocdfp-tags">'. $project_tags_badges .'</ul>';
										}

										$project_description = get_the_content( null, false, $project );
										if ( ! empty( $project_description ) ) {
											$items .= '<h3 class="ocdfp-detail-section">'. esc_html__( 'Project Description', 'ocdutils' ) .'</h3>';
											$items .= wpautop( $project_description );
										}

									$items .= '</div>';
									$items .= '<div class="ocdfp-spreader"><span>&nbsp;</span></div>';
								$items .= '</div>';

								$items .= '<div class="ocdfp-image-wrapper">';
									$items .= '<div class="ocdfp-image">';
										$items .= $project_image_full;
									$items .= '</div>';
								$items .= '</div>';

							$items .= '</section>';

							$items .= '<footer class="modal-footer">';
								if ( ! empty( $project_url ) ) {
									$items .= '<a href="'. $project_url .'" title="'. $project->post_title .'" rel="external" target="_blank">'. esc_html__( 'Visit Website', 'ocdutils' ) .'</a>';
								}
								$items .= '<button class="modal-btn" data-micromodal-close aria-label="'. esc_html__( 'Close this dialog window', 'ocdutils' ) .'">';
									$items .= esc_html__( 'Close', 'ocdutils' );
								$items .= '</button>';
							$items .= '</footer>';

						$items .= '</div>';
					$items .= '</div>';
				$items .= '</div>';

			$items .= '</article>';

		}

		$filters = '';
		if ( $show_filters && ! empty( $cats ) ) {
			usort( $cats, function( $a, $b ) {
				return $b['count'] <=> $a['count'];
			} );

			$filters .= '<ul class="ocdfp-filters" style="max-height: 20px; overflow: hidden; opacity: 0;">';
				$filters .= '<li><button data-ocdfp-filter="*">Show All <span>'. $projects_count .'</span></button></li>';
				foreach ( $cats as $category ) {
					if ( 2 > $category['count'] ) continue;

					$filters .= '<li>';
						$filters .= '<button data-ocdfp-filter="'. $category['slug'] .'">';
							$filters .= $category['name'] .' <span>'. $category['count'] .'</span>';
						$filters .= '</button>';
					$filters .= '</li>';
				}
			$filters .= '</ul>';
		}

		ob_start();
		?>
		<style>
			:root {
				--ocdfp-color-primary: <?php echo $options['color_primary']; ?>;
				--ocdfp-color-secondary: <?php echo $options['color_secondary']; ?>;
				--ocdfp-color-text-light: <?php echo $options['color_text_light']; ?>;
				--ocdfp-color-text-dark: <?php echo $options['color_text_dark']; ?>;
				--ocdfp-external-link-icon: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="<?php echo urlencode( $options['color_primary'] ); ?>" d="M320 0c-17.7 0-32 14.3-32 32s14.3 32 32 32h82.7L201.4 265.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L448 109.3V192c0 17.7 14.3 32 32 32s32-14.3 32-32V32c0-17.7-14.3-32-32-32H320zM80 32C35.8 32 0 67.8 0 112V432c0 44.2 35.8 80 80 80H400c44.2 0 80-35.8 80-80V320c0-17.7-14.3-32-32-32s-32 14.3-32 32V432c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V112c0-8.8 7.2-16 16-16H192c17.7 0 32-14.3 32-32s-14.3-32-32-32H80z"/></svg>');
			}
		</style>
		<?php
		$style_vars = ob_get_clean();

		$classes = 'ocdfp-wrapper';
		if ( $portfolio_page_id == get_the_ID() ) {
			$classes .= ' link-internal';
		}
		
		$html  = '';
		$html .= '<div id="'. $shortcode_id .'" class="'. $classes .'">';
			$html .= $style_vars;
			$html .= $this->loading_spinner();
			$html .= $filters;
			$html .= '<div class="ocdfp-items" style="max-height: 20px; overflow: hidden; opacity: 0;">';
				$html .= '<div class="ocdfp-item-sizer"></div>';
				$html .= $items;
			$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	private function loading_spinner() {
		$options = ocd_get_options( $this );

		ob_start();
		?>
		<style>
			.ocdfp-spinner {
				display: grid;
				grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
				grid-auto-rows: 130px;
				place-items:center;
				box-sizing: border-box;
			}
	
			.ocdfp-spinner > div {
				width: 50px;
				aspect-ratio: 1;
				display: grid;
				border: 4px solid #0000;
				border-radius: 50%;
				border-color: <?php echo $options['color_secondary']; ?> #0000;
				animation: ocdfp-spin 1s infinite linear;
				box-sizing: border-box;
			}
	
			.ocdfp-spinner > div::before,
			.ocdfp-spinner > div::after {    
				content: '';
				grid-area: 1/1;
				margin: 2px;
				border: inherit;
				border-radius: 50%;
				box-sizing: border-box;
			}
	
			.ocdfp-spinner > div::before {
				border-color: <?php echo $options['color_primary']; ?> #0000;
				animation: inherit; 
				animation-duration: .5s;
				animation-direction: reverse;
			}
	
			.ocdfp-spinner > div::after { margin: 8px; }
	
			@keyframes ocdfp-spin { 100%{transform: rotate(1turn)} }
		</style>
		<div class="ocdfp-spinner"><div></div></div>
		<?php
		return ob_get_clean();
	}

	// Enqueue dependencies, scripts, styles
	private function enqueue_scripts() {
		wp_enqueue_style( 
			$this->slug, 
			OCD_UTILS_URL .'components/'. $this->slug .'/'. $this->slug .'.min.css', 
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
		wp_enqueue_script( 'isotope-layout', $isotope_url, array( 'jquery', 'imagesloaded' ), $isotope_version, $script_args );

		wp_enqueue_script( 
			$this->slug, 
			OCD_UTILS_URL .'components/'. $this->slug .'/'. $this->slug .'.min.js', 
			array( 'jquery', 'imagesloaded', 'isotope-layout' ), 
			$this->version, 
			$script_args 
		);
	}

	/**
	 * Helper function to parse a shortcode attribute string.
	 *
	 * @return array of parsed values.
	 */
	public function term_slugs_ids_str_to_arrays( $str = '' ) {
		$output_r = array(
			'term_ids' => array(),
			'slugs'    => array(),
		);

		$items = array_map(
			fn( $item ) => strtolower( trim( $item ) ),
			explode( ',', $str )
		);

		foreach ( $items as $item ) {
			if ( ctype_digit( $item ) && (int) $item > 0 ) {
				$output_r['term_ids'][] = (int) $item;
			} else {
				$output_r['slugs'][] = $item;
			}
		}

		return $output_r;
	}
























	/**
	 * Configures the settings for this component.
	 */
	private function config() {
		$this->config = $this->define_config_r();

		// Register this component's settings.
		ocd_register_settings( $this->config );

		add_action( 'admin_notices',         array( $this, 'admin_notices'                  ), 10    );
		add_action( 'add_meta_boxes',        array( $this, 'add_meta_boxes'                 ), 10    );
		add_action( 'save_post',             array( $this, 'save_project_url_meta_box_data' ), 10, 1 );
		add_action( 'edit_form_after_title', array( $this, 'project_content_instructions'   ), 10, 1 );

		$options = ocd_get_options( $this );
		if ( isset( $options['projects_post_type'] ) && 'ocd-project' == $options['projects_post_type'] ) {
			$this->register_post_types();
		}
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
			<h4><?php _e( 'Recommendations', 'ocdutils' ); ?></h4>
			<p><?php _e( 'It is highly recommended to use a "lazy-load" feature. The free plugin "Smush" is very good. (There are also many other alternatives which work great in conjunction with this plugin.)', 'ocdutils' ); ?></p>
			<h4><?php _e( 'Shortcode Attributes', 'ocdutils' ); ?></h4>
			<ul>
				<li><strong>limit</strong>: 
					<?php _e( 'Number of items to show in the grid.', 'ocdutils' ); ?> <?php _e( 'Default is', 'ocdutils' ) ?> <code>-1</code> (<?php _e( 'Show all', 'ocdutils' ) ?>)
				</li>
				<li><strong>show_filters</strong>: 
					<?php _e( 'Add buttons at the top for filtering by category.', 'ocdutils' ); ?> <?php _e( 'Default is', 'ocdutils' ) ?> <code>true</code>
				</li>
				<li><strong>categories</strong>: 
					<?php _e( 'A comma-separated list of category slugs and/or ids from which to show items.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>""</code> (<?php _e( 'Show all', 'ocdutils' ) ?>)
				</li>
				<li><strong>projects</strong>: 
					<?php _e( 'A comma-separated list of individual project ids to include. If this parameter is used, the "categories" parameter will be ignored.', 'ocdutils' ); ?> 
					<?php _e( 'Default is', 'ocdutils' ) ?> <code>""</code> (<?php _e( 'Show all', 'ocdutils' ) ?>)
				</li>
			</ul>
			<h4><?php _e( 'Shortcode Examples', 'ocdutils' ); ?></h4>
			<p><code>[ocd_filter_portfolio]</code> <?php _e( 'All default settings. Use this on your main portfolio page.', 'ocdutils' ) ?></p>
			<p><code>[ocd_filter_portfolio limit="6" show_filters="false" categories="cat-abc, 6, my-term-lmno, 9, category-xyz"]</code> <?php _e( 'Use something like this on other pages.', 'ocdutils' ) ?></p>
			<p><code>[ocd_filter_portfolio show_filters="false" projects="42, 287, 314"]</code> <?php _e( 'Only show certain projects.', 'ocdutils' ) ?></p>
			<p><code>[ocd_filter_portfolio limit="21" show_filters="true"]</code></p>
			<p><?php _e( 'Use all attributes, or none, or mix-and-match. Any attributes omitted from the shortcode will use the default value.', 'ocdutils' ); ?></p>
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
		// Don't use esc_html(), esc_attr(), wp_kses(), etc. here.
		// Labels, descriptions, attributes, etc. will be escaped before output.
		// Only use __(), _e(), etc. for translatable strings.
		/**************************************/

		$portfolio_page_id_options = array( 
			'' => __( '--Select a Page--', 'ocdutils' ),
		);

		$projects_post_type_options = array( 
			'' => __( '--Select a Post Type--', 'ocdutils' ), 
			'ocd-project' => __( 'OCD Projects', 'ocdutils' ),
		);

		if ( is_admin() && isset( $_GET['tab'] ) && $this->slug === $_GET['tab'] ) {
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			if ( is_array( $post_types ) ) {
				foreach ( $post_types as $post_type_slug => $post_type ) {
					$tax = null;
					$tax = get_object_taxonomies( $post_type_slug, 'objects' );
					if ( is_array( $tax ) ) {
						foreach ( $tax as $tax_slug => $tax_r ) {
							$this->post_type_taxonomies[$post_type_slug][$tax_slug] = $tax_r->label;
						}
					}
				}
			}
			$this->post_type_taxonomies['ocd-project']['ocd-project-category'] = __( 'Project Categories', 'ocdutils' );
			$this->post_type_taxonomies['ocd-project']['ocd-project-tag'] = __( 'Project Tags', 'ocdutils' );

			add_action( 'admin_enqueue_scripts', array( $this, 'taxonomies_select_switcher' ) );

			$portfolio_page_id_options = $portfolio_page_id_options + wp_list_pluck( 
				get_pages(), 
				'post_title', 
				'ID' 
			);

			$projects_post_type_options = $projects_post_type_options + wp_list_pluck( 
				$post_types, 
				'label', 
				'name' 
			);
		}

		$config_r = array(
			'slug' => $this->slug,
			'label' => __( 'Filter Portfolio', 'ocdutils' ), // Tab name in the settings page.
			'sections' => array(
				array(
					'id' => 'usage',
					'label' => __( 'Usage Instructions', 'ocdutils' ),
					'description' => $this->usage_instructions(),
				),
				array(
					'id' => 'portfolio',
					'label' => __( 'Portfolio Functions', 'ocdutils' ),
					'fields' => array(
						array(
							'id' => 'portfolio_page_id',
							'label' => __( 'Portfolio Page', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Choose the page where you will place the default shortcode (your full portfolio with filters). Other portfolio shortcodes will link to this main one.', 'ocdutils' ),
							'required' => true,
							'options' => $portfolio_page_id_options ?: array(),
						),
						array(
							'id' => 'projects_post_type',
							'label' => __( 'Projects Post Type', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Choose the post type that is used for your projects in your portfolio. You should use a post type that supports "title", "editor", and "thumbnail".', 'ocdutils' ),
							'required' => true,
							'options' => $projects_post_type_options ?: array(),
						),
						array(
							'id' => 'projects_tax_cats',
							'label' => __( 'Project "Categories" Taxonomy', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Choose the taxonomy from your "Post Type" that will be used for filtering. (This taxonomy is also used for the "categories" shortcode attribute.)', 'ocdutils' ),
							'options' => array( '' => __( '--Select your "Categories" Taxonomy--', 'ocdutils' ) ),
						),
						array(
							'id' => 'projects_tax_cats_label',
							'label' => __( 'Project "Categories" Label', 'ocdutils' ),
							'type' => 'text',
							'description' => __( 'The heading text used for the badges in the modal window. e.g. "Project Type"', 'ocdutils' ),
							'required' => true,
							'default' => __( 'Project Categories', 'ocdutils' ),
						),
						array(
							'id' => 'projects_tax_tags',
							'label' => __( 'Project "Tags" Taxonomy', 'ocdutils' ),
							'type' => 'select',
							'description' => __( 'Choose an additional taxonomy from your "Post Type" that will be used to display extra info "badges" (not used for filtering).', 'ocdutils' ),
							'options' => array( '' => __( '--Select your "Tags" Taxonomy--', 'ocdutils' ) ),
						),
						array(
							'id' => 'projects_tax_tags_label',
							'label' => __( 'Project "Tags" Label', 'ocdutils' ),
							'type' => 'text',
							'description' => __( 'The heading text used for the badges in the modal window. e.g. "Project Features"', 'ocdutils' ),
							'required' => true,
							'default' => __( 'Project Tags', 'ocdutils' ),
						),
						// array(
						// 	'id' => 'lazy_load',
						// 	'label' => __( 'Lazy-Load Images', 'ocdutils' ),
						// 	'type' => 'checkboxes',
						// 	'description' => __( 'Uncheck this if the built-in lazy-loading interferes with your other lazy-load plugin on your site, such as "Smush".', 'ocdutils' ),
						// 	'default' => 'true',
						// 	'options' => array(
						// 		'true' => __( 'Lazy-Load Images', 'ocdutils' ),
						// 	),
						// ),   // TODO: implement image lazy loading    ///  nvm .. added recommendation to just use Smush (or alternative) for lazy-loading
					),
				),
				array(
					'id' => 'design',
					'label' => __( 'Portfolio Design Settings', 'ocdutils' ),
					'fields' => array(
						array(
							'id' => 'color_primary',
							'label' => __( 'Primary Accent Color', 'ocdutils' ),
							'description' => __( 'A dark color that will look good as text on a light background, and also look good as a background with light text on it.', 'ocdutils' ),
							'type' => 'color',
							'default' => '#2c3e50',
						),
						array(
							'id' => 'color_secondary',
							'label' => __( 'Secondary Accent Color', 'ocdutils' ),
							'description' => __( 'A bright color that will look good as text on a background of the primary color above, and also look good as a background with the primary color text on it.', 'ocdutils' ),
							'type' => 'color',
							'default' => '#bccedd',
						),
						array(
							'id' => 'color_text_light',
							'label' => __( 'Light Text Color', 'ocdutils' ),
							'description' => __( 'A light or white color that will look good as text on a background of the primary color above.', 'ocdutils' ),
							'type' => 'color',
							'default' => '#d1dce5',
						),
						array(
							'id' => 'color_text_dark',
							'label' => __( 'Dark Text Color', 'ocdutils' ),
							'description' => __( 'A dark or black color that will look good as text on a background of the secondary color above.', 'ocdutils' ),
							'type' => 'color',
							'default' => '#4a5866',
						),
					),
				),
				// Additional sections can be defined here.
			),
		);

		return $config_r;
	}

	/**
	 * Generates admin notices.
	 */
	public function admin_notices() {
		if ( current_user_can( 'manage_options' ) ) {

			$options = ocd_get_options( $this );

			if ( empty( $options['portfolio_page_id'] ) ) {
				echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'In order for the "Filterable Portfolio with Modals" links to work correctly, you must choose a page.', 'ocdutils' );
					echo ' <a href="'. OCD_UTILS_SETTINGS_PAGE_LINK .'&tab='. $this->slug .'">'. esc_html__( 'Go to Settings', 'ocdutils' ) .'</a>';
				echo '</p></div>';
			}

			if ( empty( $options['projects_post_type'] ) ) {
				echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'In order for the "Filterable Portfolio with Modals" shortcode to work, you must choose a post type.', 'ocdutils' );
					echo ' <a href="'. OCD_UTILS_SETTINGS_PAGE_LINK .'&tab='. $this->slug .'">'. esc_html__( 'Go to Settings', 'ocdutils' ) .'</a>';
				echo '</p></div>';
			}

		}
	}

	/**
	 * Adds script to swtich the dropdown options for the taxonomies based on which post type is chosen.
	 */
	public function taxonomies_select_switcher() {
		$options = ocd_get_options( $this );

		wp_enqueue_script( 'jquery' );

		ob_start();
		?>
		<script>
		jQuery(function($){
			$(document).ready(function(){
				const postTypeVal = '<?php echo $options['projects_post_type'] ?: ''; ?>';
				const taxonomies = <?php echo json_encode( $this->post_type_taxonomies ); ?>;
				const $taxCatsSelectEl = $('#projects_tax_cats');
				const taxCatsVal = '<?php echo $options['projects_tax_cats'] ?: ''; ?>';
				const $taxTagsSelectEl = $('#projects_tax_tags');
				const taxTagsVal = '<?php echo $options['projects_tax_tags'] ?: ''; ?>';
				const $projectsPostTypeSelectEl = $('#projects_post_type');
				let selectedKey = $projectsPostTypeSelectEl.val();

				if(selectedKey === '' || selectedKey === null || selectedKey === undefined){
					$projectsPostTypeSelectEl.find('option').each(function(){
						let optionValue = $(this).val();
						if(optionValue.toLowerCase().indexOf('project') !== -1 && 'ocd-project' !== optionValue){
							selectedKey = optionValue;
							$projectsPostTypeSelectEl.val(selectedKey);
							return false;
						}
					});

					if(selectedKey === '' || selectedKey === null || selectedKey === undefined){
						selectedKey = 'ocd-project';
						$projectsPostTypeSelectEl.val(selectedKey);
					}
				}

				let selectedOptions = taxonomies[selectedKey];

				$taxCatsSelectEl.find('option:not(:first)').remove();
				$taxTagsSelectEl.find('option:not(:first)').remove();
				$.each(selectedOptions, function(value, text){
					$taxCatsSelectEl.append($('<option></option>').attr('value', value).text(text));
					if(taxCatsVal === '' || taxCatsVal === null || taxCatsVal === undefined){
						if(value.toLowerCase().indexOf('cat') !== -1){
							$taxCatsSelectEl.val(value);
						}
					}else if(taxCatsVal === value){
						$taxCatsSelectEl.val(value);
					}

					$taxTagsSelectEl.append($('<option></option>').attr('value', value).text(text));
					if(taxTagsVal === '' || taxTagsVal === null || taxTagsVal === undefined){
						if(value.toLowerCase().indexOf('tag') !== -1){
							$taxTagsSelectEl.val(value);
						}
					}else if(taxTagsVal === value){
						$taxTagsSelectEl.val(value);
					}
				});

				$projectsPostTypeSelectEl.on('change', function(){
					let selectedKey = $projectsPostTypeSelectEl.val();
					let selectedOptions = taxonomies[selectedKey];

					$taxCatsSelectEl.find('option:not(:first)').remove();
					$taxTagsSelectEl.find('option:not(:first)').remove();
					$.each(selectedOptions, function(value, text){
						$taxCatsSelectEl.append($('<option></option>').attr('value', value).text(text));
						if(selectedKey !== postTypeVal || taxCatsVal === '' || taxCatsVal === null || taxCatsVal === undefined){
							if(value.toLowerCase().indexOf('cat') !== -1){
								$taxCatsSelectEl.val(value);
							}
						}else if(taxCatsVal === value){
							$taxCatsSelectEl.val(value);
						}

						$taxTagsSelectEl.append($('<option></option>').attr('value', value).text(text));
						if(selectedKey !== postTypeVal || taxTagsVal === '' || taxTagsVal === null || taxTagsVal === undefined){
							if(value.toLowerCase().indexOf('tag') !== -1){
								$taxTagsSelectEl.val(value);
							}
						}else if(taxTagsVal === value){
							$taxTagsSelectEl.val(value);
						}
					});
				});
			});
		});
		</script>
		<?php
		$script = str_replace( array( '<script>', '</script>' ), '', ob_get_clean() );

		wp_add_inline_script( 'jquery', $script );

		return;
	}

	/**
	 * Adds meta boxes to post type configured in Admin settings.
	 */
	public function add_meta_boxes() {
		$options = ocd_get_options( $this );

		add_meta_box(
			'ocd_filter_portfolio_project_url_meta_box', // Unique ID for the meta box
			esc_html__( 'Project URL', 'ocdutils' ),     // Meta box title
			array( $this, 'render_url_meta_box' ),       // Callback function to render the meta box
			$options['projects_post_type'],              // Post type where the meta box should appear
			'normal',                                    // Context (where the box should be placed: normal, side, etc.)
			'high'                                       // Priority (high, low, default)
	  );


		// Check if Yoast SEO is active
		if ( defined( 'WPSEO_VERSION' ) ) {
			global $wp_meta_boxes;

			// Check if the Yoast SEO meta box is registered
			if ( isset( $wp_meta_boxes[$options['projects_post_type']]['normal']['high']['wpseo_meta'] ) ) {
				// Get the Yoast SEO meta box details
				$yoast_meta_box = $wp_meta_boxes[$options['projects_post_type']]['normal']['high']['wpseo_meta'];

				// Remove the original Yoast SEO meta box
				remove_meta_box( 'wpseo_meta', $options['projects_post_type'], 'normal' );

				// Re-add the Yoast SEO meta box using the retrieved details and setting 'default' priority
				add_meta_box(
					'wpseo_meta',                   // Meta box ID
					$yoast_meta_box['title'],       // Title of the meta box
					$yoast_meta_box['callback'],    // Callback function
					$options['projects_post_type'], // Post type
					'normal',                       // Context (normal area)
					'default',                      // Priority
					$yoast_meta_box['args']         // Arguments
				);
			}
		}
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

	/**
	 * Adds notices on the project edit screen.
	 * 
	 * @param object $post WP_Post object.
	 */
	public function project_content_instructions( $post ) {
		$options = ocd_get_options( $this );
		$screen = get_current_screen();
		if ( $screen->post_type !== $options['projects_post_type'] ) return;

		echo '<div class="ocd-project-content-instructions notice-info" style="margin-top: 20px; margin-bottom: 0;">';
			echo '<p>';
				echo '<strong>'. esc_html__( 'Instructions:', 'ocdutils' ) .'</strong> ';
				echo esc_html__( 'Please be sure to use only Heading Levels 4, 5, and 6. Do not use Heading 1, Heading 2, or Heading 3.', 'ocdutils' );
			echo '</p>';
		echo '</div>';

		ob_start();
		?>
		<script>
			window.addEventListener('load', function(){
				const ocdCustomNotice = document.querySelector('.ocd-project-content-instructions');
				if(ocdCustomNotice){
					ocdCustomNotice.classList.add('notice');
				}

				const featuredImageBox = document.getElementById('postimagediv');
				if(featuredImageBox){
					const ocdFeaturedImageInstructions = document.createElement('p');
					ocdFeaturedImageInstructions.textContent = '<?php echo esc_html__( 'Note: Please make sure the image width is at least 1024 pixels.', 'ocdutils' ); ?>';
					ocdFeaturedImageInstructions.style.marginTop = '10px';

					featuredImageBox.querySelector('.inside').appendChild(ocdFeaturedImageInstructions);
				}
			});
		</script>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Registers post types.
	 */
	private function register_post_types() {
		register_post_type( 'ocd-project', array(
			'labels' => array(
				'name'                     => esc_html__( 'Projects',                     'ocdutils' ),
				'singular_name'            => esc_html__( 'Project',                      'ocdutils' ),
				'menu_name'                => esc_html__( 'OCD Projects',                 'ocdutils' ),
				'all_items'                => esc_html__( 'All Projects',                 'ocdutils' ),
				'edit_item'                => esc_html__( 'Edit Project',                 'ocdutils' ),
				'view_item'                => esc_html__( 'View Project',                 'ocdutils' ),
				'view_items'               => esc_html__( 'View Projects',                'ocdutils' ),
				'add_new_item'             => esc_html__( 'Add New Project',              'ocdutils' ),
				'add_new'                  => esc_html__( 'Add New',                      'ocdutils' ),
				'new_item'                 => esc_html__( 'New Project',                  'ocdutils' ),
				'parent_item_colon'        => esc_html__( 'Parent Project:',              'ocdutils' ),
				'search_items'             => esc_html__( 'Search Projects',              'ocdutils' ),
				'not_found'                => esc_html__( 'No projects found',            'ocdutils' ),
				'not_found_in_trash'       => esc_html__( 'No projects found in Trash',   'ocdutils' ),
				'archives'                 => esc_html__( 'Project Archives',             'ocdutils' ),
				'attributes'               => esc_html__( 'Project Attributes',           'ocdutils' ),
				'insert_into_item'         => esc_html__( 'Insert into project',          'ocdutils' ),
				'uploaded_to_this_item'    => esc_html__( 'Uploaded to this project',     'ocdutils' ),
				'filter_items_list'        => esc_html__( 'Filter projects list',         'ocdutils' ),
				'filter_by_date'           => esc_html__( 'Filter projects by date',      'ocdutils' ),
				'items_list_navigation'    => esc_html__( 'Projects list navigation',     'ocdutils' ),
				'items_list'               => esc_html__( 'Projects list',                'ocdutils' ),
				'item_published'           => esc_html__( 'Project published.',           'ocdutils' ),
				'item_published_privately' => esc_html__( 'Project published privately.', 'ocdutils' ),
				'item_reverted_to_draft'   => esc_html__( 'Project reverted to draft.',   'ocdutils' ),
				'item_scheduled'           => esc_html__( 'Project scheduled.',           'ocdutils' ),
				'item_updated'             => esc_html__( 'Project updated.',             'ocdutils' ),
				'item_link'                => esc_html__( 'Project Link',                 'ocdutils' ),
				'item_link_description'    => esc_html__( 'A link to a project.',         'ocdutils' ),
			),
			'public'            => true,
			'has_archive'       => true,
			'show_in_rest'      => true,
			'delete_with_user'  => false,
			'menu_icon'         => 'dashicons-media-document',
			'supports'          => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'slug', 'comments', 'trackbacks', 'custom-fields' ),
			'taxonomies'        => array( 'ocd-project-category', 'ocd-project-tag' ),
		) );

		register_taxonomy( 'ocd-project-category', array( 'ocd-project' ), array(
			'labels' => array(
				'name'                  => esc_html__( 'Project Categories',         'ocdutils' ),
				'singular_name'         => esc_html__( 'Category',                   'ocdutils' ),
				'menu_name'             => esc_html__( 'Categories',                 'ocdutils' ),
				'all_items'             => esc_html__( 'All Categories',             'ocdutils' ),
				'edit_item'             => esc_html__( 'Edit Category',              'ocdutils' ),
				'view_item'             => esc_html__( 'View Category',              'ocdutils' ),
				'update_item'           => esc_html__( 'Update Category',            'ocdutils' ),
				'add_new_item'          => esc_html__( 'Add New Category',           'ocdutils' ),
				'new_item_name'         => esc_html__( 'New Category Name',          'ocdutils' ),
				'parent_item'           => esc_html__( 'Parent Category',            'ocdutils' ),
				'parent_item_colon'     => esc_html__( 'Parent Category:',           'ocdutils' ),
				'search_items'          => esc_html__( 'Search Categories',          'ocdutils' ),
				'not_found'             => esc_html__( 'No categories found',        'ocdutils' ),
				'no_terms'              => esc_html__( 'No categories',              'ocdutils' ),
				'filter_by_item'        => esc_html__( 'Filter by category',         'ocdutils' ),
				'items_list_navigation' => esc_html__( 'Categories list navigation', 'ocdutils' ),
				'items_list'            => esc_html__( 'Categories list',            'ocdutils' ),
				'back_to_items'         => esc_html__( '← Go to categories',         'ocdutils' ),
				'item_link'             => esc_html__( 'Category Link',              'ocdutils' ),
				'item_link_description' => esc_html__( 'A link to a category',       'ocdutils' ),
			),
			'rewrite' => array( 
				'with_front'   => true,
				'hierarchical' => false,
			 ),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_menu'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		) );

		register_taxonomy( 'ocd-project-tag', array( 'ocd-project' ), array(
			'labels' => array(
				'name'                       => esc_html__( 'Project Tags',                   'ocdutils' ),
				'singular_name'              => esc_html__( 'Tag',                            'ocdutils' ),
				'menu_name'                  => esc_html__( 'Tags',                           'ocdutils' ),
				'all_items'                  => esc_html__( 'All Tags',                       'ocdutils' ),
				'edit_item'                  => esc_html__( 'Edit Tag',                       'ocdutils' ),
				'view_item'                  => esc_html__( 'View Tag',                       'ocdutils' ),
				'update_item'                => esc_html__( 'Update Tag',                     'ocdutils' ),
				'add_new_item'               => esc_html__( 'Add New Tag',                    'ocdutils' ),
				'new_item_name'              => esc_html__( 'New Tag Name',                   'ocdutils' ),
				'search_items'               => esc_html__( 'Search Tags',                    'ocdutils' ),
				'popular_items'              => esc_html__( 'Popular Tags',                   'ocdutils' ),
				'separate_items_with_commas' => esc_html__( 'Separate tags with commas',      'ocdutils' ),
				'add_or_remove_items'        => esc_html__( 'Add or remove tags',             'ocdutils' ),
				'choose_from_most_used'      => esc_html__( 'Choose from the most used tags', 'ocdutils' ),
				'not_found'                  => esc_html__( 'No tags found',                  'ocdutils' ),
				'no_terms'                   => esc_html__( 'No tags',                        'ocdutils' ),
				'items_list_navigation'      => esc_html__( 'Tags list navigation',           'ocdutils' ),
				'items_list'                 => esc_html__( 'Tags list',                      'ocdutils' ),
				'back_to_items'              => esc_html__( '← Go to tags',                   'ocdutils' ),
				'item_link'                  => esc_html__( 'Tag Link',                       'ocdutils' ),
				'item_link_description'      => esc_html__( 'A link to a tag',                'ocdutils' ),
			),
			'rewrite' => array( 
				'with_front'   => true,
				'hierarchical' => false,
			 ),
			'public'            => true,
			'show_in_menu'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		) );
				
		return;
	}
}
endif;

// Instantiate the component class.
$OCD_FilterPortfolio = new OCD_FilterPortfolio();

?>
