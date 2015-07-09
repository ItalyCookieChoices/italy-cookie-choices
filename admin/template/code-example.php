<!-- <a id="script" class="button button-secondary add-sep" data-value="<script>">&lt;script&gt;</a> -->
<!-- <a id="scriptsrc" class="button button-secondary add-sep" data-value='<script src="">'>&lt;script src&gt;</a> -->
<!-- <br> -->


<div id="code-example" style="display:none;">
	<h1><?php _e( 'Script inline example', 'italy-cookie-choices' ); ?></h1>
	<p>
		<pre>
			
<code>&lt;script&gt;(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) return;
	  js = d.createElement(s); js.id = id;
	  js.src = "//connect.facebook.net/it_IT/sdk.js#xfbml=1&version=v2.3&appId=150302815027430";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));&lt;/script&gt;
	<---------SEP--------->
	&lt;script&gt;(function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;e=o.createElement(i);r=o.getElementsByTagName(i)[0];e.src='https://www.google-analytics.com/analytics.js';r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));ga('create','UA-12345-6','auto');ga('send','pageview');ga('set', 'anonymizeIp', true);&lt;/script&gt;</code>
		</pre>

	</p>
</div>