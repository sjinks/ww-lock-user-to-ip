<?php defined('ABSPATH') || die(); ?>
<h3><?=__('Lock to IP Addresses', 'lock-user'); ?></h3>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="psb_ip_list"><?=__('Allowed IP addresses', 'lock-user'); ?></label></th>
			<td>
				<textarea cols="30" rows="7" name="psb_ip_list" id="psb_ip_list"><?=esc_html($ips);?></textarea>
				<p class="description"><?=__('One address per line', 'lock-user'); ?></p>
			</td>
		</tr>
	</tbody>
</table>
