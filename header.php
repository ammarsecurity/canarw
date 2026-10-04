<?php defined( 'ABSPATH' ) || exit; $canarw_o = canarw_options(); ?>
<!doctype html><html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?> dir="<?php echo esc_attr( canarw_direction() ); ?>"><?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php echo esc_html( canarw_label( 'انتقل إلى المحتوى', 'Skip to content' ) ); ?></a>
<div class="topbar"><div class="container"><span><?php echo esc_html( canarw_public_tagline() ); ?></span><a href="mailto:<?php echo esc_attr( $canarw_o['email'] ); ?>"><bdi dir="ltr"><?php echo esc_html( $canarw_o['email'] ); ?></bdi></a></div></div>
<header class="site-header"><div class="container header-inner">
<a class="brand" href="<?php echo esc_url( canarw_resolve_url( canarw_is_english() ? '/en' : '/' ) ); ?>">
<?php $canarw_logo = canarw_image_url( $canarw_o['logo'] ); if ( ! $canarw_logo && has_custom_logo() ) { $canarw_logo = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'medium' ); } ?>
<?php if ( $canarw_logo ) : ?><img src="<?php echo esc_url( $canarw_logo ); ?>" alt="<?php echo esc_attr( $canarw_o['name'] ); ?>" width="76" height="76"><?php else : ?><span class="brand-mark" aria-hidden="true">CAN</span><?php endif; ?>
<span class="brand-text"><strong dir="ltr">CANARW</strong><small><?php echo esc_html( canarw_public_tagline() ); ?></small></span></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="site-navigation"><span aria-hidden="true">☰</span><span><?php echo esc_html( canarw_label( 'القائمة', 'Menu' ) ); ?></span></button>
<nav id="site-navigation" class="site-navigation" aria-label="<?php echo esc_attr( canarw_label( 'القائمة الرئيسية', 'Main navigation' ) ); ?>">
<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'nav-menu', 'fallback_cb' => 'canarw_nav_fallback', 'depth' => 2 ) ); ?>
</nav><a class="language-link" href="<?php echo esc_url( canarw_language_url() ); ?>" hreflang="<?php echo canarw_is_english() ? 'ar' : 'en'; ?>" lang="<?php echo canarw_is_english() ? 'ar' : 'en'; ?>" translate="no"><?php echo canarw_is_english() ? 'العربية' : 'EN'; ?></a>
</div></header><main id="main">
