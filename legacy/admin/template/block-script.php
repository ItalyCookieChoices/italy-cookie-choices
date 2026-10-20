<div id="custom-script" class="custom-script">
	<?php

	$block_iframe = ( isset( $this->options['block_iframe'] ) ) ? $this->options['block_iframe'] : array('');
	$block_script = ( isset( $this->options['block_script'] ) ) ? $this->options['block_script'] : array('');
	$block_embed = ( isset( $this->options['block_embed'] ) ) ? $this->options['block_embed'] : array('');

	echo '<div class="custom-script-title">' . __( 'iframe', 'italy-cookie-choices' ) . '</div>';

	$this->foreach_script( $block_iframe, 'block_iframe', $this->iframe_array );

	echo '<div class="custom-script-title">' . __( 'script', 'italy-cookie-choices' ) . '</div>';

	$this->foreach_script( $block_script, 'block_script', $this->script_array );

	echo '<div class="custom-script-title">' . __( 'embed', 'italy-cookie-choices' ) . '</div>';

	$this->foreach_script( $block_embed, 'block_embed', $this->embed_array );

	echo '<div class="custom-script-title">' . __( 'Custom', 'italy-cookie-choices' ) . '</div>';

    ?>

</div>