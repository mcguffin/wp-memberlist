<?php
/**
 *
 */

get_header(); 

if ( have_posts() ) : 

	// check user role.

	/* The loop */ 
	?><div class="staff-archive <?php echo get_option('schulstaff_archive_layout') ?>"><?php 
	while ( have_posts() ) : the_post(); 
		// image, name in grid mode
		
		?><article id="post-<?php the_ID(); ?>" <?php post_class(); ?>><?php
			?><header class="entry-header"><?php
				if ( has_post_thumbnail() && ! post_password_required() ) : 
				?><div class="entry-thumbnail"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php
					the_post_thumbnail( 'thumbnail' ); 
				?></a></div><?php
				endif; 
				?><h1 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h1><?php
			?></header><!-- .entry-header --><?php

			if ( 'list' == get_option('schulstaff_archive_layout') ) :
				?><div class="entry-summary"><?php
					 the_excerpt(); 
				?></div><!-- .entry-summary --><?php
			endif;

		?></article><?php
	endwhile; 
	?></div><?php 

 endif; 

get_footer();