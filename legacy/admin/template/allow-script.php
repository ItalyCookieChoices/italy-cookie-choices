<div id="custom-script" class="custom-script">
	<?php

    $allow_iframe = ( isset( $this->options['allow_iframe'] ) ) ? $this->options['allow_iframe'] : array('');
    $allow_script = ( isset( $this->options['allow_script'] ) ) ? $this->options['allow_script'] : array('');
    $allow_embed = ( isset( $this->options['allow_embed'] ) ) ? $this->options['allow_embed'] : array('');

	echo '<div class="custom-script-title">' . __( 'iframe', 'italy-cookie-choices' ) . '</div>';

	$this->foreach_script( $allow_iframe, 'allow_iframe', $this->iframe_array );

	echo '<div class="custom-script-title">' . __( 'script', 'italy-cookie-choices' ) . '</div>';

	$this->foreach_script( $allow_script, 'allow_script', $this->script_array );

	echo '<div class="custom-script-title">' . __( 'embed', 'italy-cookie-choices' ) . '</div>';

	$this->foreach_script( $allow_embed, 'allow_embed', $this->embed_array );

	echo '<div class="custom-script-title">' . __( 'Custom', 'italy-cookie-choices' ) . '</div>';

    ?>

</div>