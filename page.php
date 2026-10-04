<?php defined( 'ABSPATH' ) || exit; get_header(); ?>
<?php while ( have_posts() ) : the_post(); ?>
<?php if ( post_password_required() ) : ?><div class="container prose section-pad"><?php the_content(); ?></div>
<?php elseif ( get_post_meta( get_the_ID(), '_canarw_managed', true ) ) : canarw_render_page( get_the_ID() ); ?>
<?php else : ?><article class="container prose section-pad"><h1><?php the_title(); ?></h1><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?><?php the_content(); wp_link_pages(); ?></article><?php endif; ?>
<?php if ( comments_open() || get_comments_number() ) { comments_template(); } ?>
<?php endwhile; get_footer(); ?>
