<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php foreach ( $this->get_oauth_types() as $oauth_type ) : ?>
		<a href="<?php echo esc_attr( $this->get_oauth_pre_url( $oauth_type ) ); ?>">Authorize <?php echo esc_html( ucfirst( $oauth_type ) ); ?></a>
	<?php endforeach; ?>
</div>
