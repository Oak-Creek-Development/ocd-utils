<?php
add_filter( 'the_content', 'ocd_empty_page_with_sub_pages', 10, 1 );
if ( ! function_exists( 'ocd_empty_page_with_sub_pages' ) ) :
function ocd_empty_page_with_sub_pages( $content ) {

   if ( is_page() && ! is_admin() && empty( $content ) ) {

      $content = wp_list_pages( array(
         'echo'        => false,
         'child_of'    => get_the_id(),
         'title_li'    => '',
         'sort_column' => 'post_date',
      ) );

      if ( ! empty( $content ) ) {
         $content = '<ul>' . $content . '</ul>';
      }

   }

   return $content;

}
endif;
?>
