<?php
// add_filter( 'post_row_actions', 'ocd_modify_list_row_actions', 10, 2 );
add_filter( 'page_row_actions', 'ocd_modify_list_row_actions', 10, 2 );
if ( ! function_exists( 'ocd_modify_list_row_actions' ) ) :
function ocd_modify_list_row_actions( $actions, $post ) {

   if ( 3 > strlen( get_page_uri() ) ) return $actions;

   $short_uri = explode( '/', trim( get_page_uri(), " \n\r\t\v\0/" ) );
   foreach ( $short_uri as $i => $v ) {
      if ( 12 < strlen( $v ) ) {
         $short_uri[$i] = substr( $v, 0, 12 ) . '...';
      }
   }

   $actions['view'] = '<a href="' . get_permalink() . '" target="_blank">/' . implode( '/', $short_uri ) . '/</a>';

   if ( 'on' == get_post_meta( $post->ID, '_et_pb_use_builder', true ) ) {

      $current_theme = wp_get_theme();
      $current_theme = $current_theme->parent() ? $current_theme->parent() : $current_theme;
      if ( 'Divi' !== trim( $current_theme->get( 'Name' ) ) ) return $actions;

      $link = add_query_arg( array(
         'et_fb'     => '1',
         'PageSpeed' => 'off',
      ), get_permalink() );

      $actions['edit'] .= ' <a href="' . $link . '" target="_blank" style="font-weight: bold;">' . esc_html__( 'Frontend', 'ocdutils' ) . '</a>';

   }

   return $actions;

}
endif;
?>
