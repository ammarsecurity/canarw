<?php defined( 'ABSPATH' ) || exit; get_header(); while ( have_posts() ) : the_post(); ?>
<article class="container prose section-pad"><span class="eyebrow"><?php echo esc_html( get_the_date() ); ?></span><h1><?php the_title(); ?></h1><?php if ( has_post_thumbnail() && ! post_password_required() ) { the_post_thumbnail( 'large' ); } ?><?php the_content(); wp_link_pages(); the_tags( '<p>', ', ', '</p>' ); ?></article>
<?php if ( comments_open() || get_comments_number() ) { comments_template(); } endwhile; get_footer(); ?>
