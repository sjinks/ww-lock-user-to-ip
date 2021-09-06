<?php

if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	delete_metadata( 'user', 0, 'psb_ip_list', '', true );
}
