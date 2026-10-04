<?php defined( 'ABSPATH' ) || exit; if ( post_password_required() ) { return; } ?>
<section class="container prose comments-area"><?php if ( have_comments() ) : ?><h2><?php echo esc_html( canarw_label( 'التعليقات', 'Comments' ) ); ?></h2><ol><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?></ol><?php the_comments_pagination(); endif; comment_form(); ?></section>
