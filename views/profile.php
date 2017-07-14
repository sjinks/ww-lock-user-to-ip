<?php defined('ABSPATH') or die(); ?>
<h3><?=esc_html__('Lock to IP Addresses', 'lock-user')?></h3>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="psb_ip_list"><?=esc_html__('Allowed addresses', 'lock-user')?></label></th>
			<td>
				<textarea cols="30" rows="7" name="psb_ip_list" id="psb_ip_list"><?=esc_html($ips);?></textarea>
				<p class="description"><?=esc_html__("One address per line", 'lock-user')?></p>
			</td>
		</tr>
	</tbody>
</table>
