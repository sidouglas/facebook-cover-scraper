<?php

class Facebook_Admin_Notice {

	static public function display( $error ) {
		if ( current_user_can( 'administrator' ) ) {
			$msg = <<<MSG
			<div style="display: block; width:100%; text-align: center; background:#ff6666; border:1px solid red; ">
				<p style="color:#fff; padding:5px; margin:0;"> 🤦 ‍Facebook Admin Scraper ( visible only to admins )<br />$error </p>
			</div>	
MSG;
			echo $msg;
		}
	}

}
