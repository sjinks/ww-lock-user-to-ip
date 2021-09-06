<?php defined( 'ABSPATH' ) || die(); ?>
<h3><?php esc_html_e( 'Lock to IP Addresses', 'lock-user' ); ?></h3>

<table class="form-table" role="presentation">
	<tbody>
		<tr>
			<th scope="row"><label for="psb_ip_list"><?php esc_html_e( 'Allowed IP addresses', 'lock-user' ); ?></label></th>
			<td>
				<textarea cols="30" rows="7" name="psb_ip_list" id="psb_ip_list"><?php echo esc_html( $ips ); ?></textarea>
				<p class="description"><?php esc_html_e( 'One address per line', 'lock-user' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>
