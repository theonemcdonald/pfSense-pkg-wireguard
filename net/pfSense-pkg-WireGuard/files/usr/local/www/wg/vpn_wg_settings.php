<?php
/*
 * vpn_wg_settings.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2021 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2021 R. Christian McDonald (https://github.com/theonemcdonald)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

##|+PRIV
##|*IDENT=page-vpn-wireguard
##|*NAME=VPN: WireGuard: Settings
##|*DESCR=Allow access to the 'VPN: WireGuard' page.
##|*MATCH=vpn_wg_settings.php*
##|-PRIV

// pfSense includes
require_once('functions.inc');
require_once('guiconfig.inc');

// WireGuard includes
require_once('wireguard/wg.inc');
require_once('wireguard/wg_guiconfig.inc');

global $wgg;

wg_globals();

$save_success = false;

if ($_POST) {

	if (isset($_POST['apply'])) {

		$ret_code = 0;

		if (is_subsystem_dirty($wgg['subsystems']['wg'])) {

			if (wg_is_service_running()) {

				$tunnels_to_apply = wg_apply_list_get('tunnels');

				$sync_status = wg_tunnel_sync($tunnels_to_apply, true, true);

				$ret_code |= $sync_status['ret_code'];

			}

			if ($ret_code == 0) {

				clear_subsystem_dirty($wgg['subsystems']['wg']);

			}

		}

	}

	if (isset($_POST['act'])) {

		switch ($_POST['act']) {

			case 'save':

				$res = wg_do_settings_post($_POST);

				$input_errors = $res['input_errors'];

				$pconfig = $res['pconfig'];

				if (empty($input_errors) && $res['changes']) {

					$save_success = true;

				}

				break;

			default:

				// Shouldn't be here, so bail out.
				header('Location: /wg/vpn_wg_settings.php');

				break;

		}

	}

}

// Default yes for new installations (i.e. keep_conf is empty)
$pconfig['keep_conf'] = (isset($wgg['config']['keep_conf'])) ? $wgg['config']['keep_conf'] : 'yes';

// Default yes for new installations (i.e. hide_secrets is empty)
$pconfig['hide_secrets'] = (isset($wgg['config']['hide_secrets'])) ? $wgg['config']['hide_secrets'] : 'yes';

// Default for new installations (i.e. dns_ttl is empty)
$pconfig['dns_ttl'] = (isset($wgg['config']['dns_ttl'])) ? $wgg['config']['dns_ttl'] : $wgg['default_dns_ttl'];

$shortcut_section = "wireguard";

$pgtitle = array(gettext("VPN"), gettext("WireGuard"), gettext("Settings"));
$pglinks = array("", "/wg/vpn_wg_tunnels.php", "@self");

$tab_array = array();
$tab_array[] = array(gettext("Tunnels"), false, "/wg/vpn_wg_tunnels.php");
$tab_array[] = array(gettext("Peers"), false, "/wg/vpn_wg_peers.php");
$tab_array[] = array(gettext("Settings"), true, "/wg/vpn_wg_settings.php");
$tab_array[] = array(gettext("Status"), false, "/wg/status_wireguard.php");

include("head.inc");

if ($save_success) {

	print_info_box(gettext("The changes have been applied successfully."), 'success');
	
}

wg_print_service_warning();

if (isset($_POST['apply'])) {

	print_apply_result_box($ret_code);

}

wg_print_config_apply_box();

if (!empty($input_errors)) {

	print_input_errors($input_errors);
	
}

display_top_tabs($tab_array);

$form = new Form(false);

$section = new Form_Section('General Settings');

$section->addInput(new Form_Checkbox(
	'keep_conf',
	'Keep Configuration',
    	gettext('Enable'),
    	$pconfig['keep_conf'] == 'yes'
))->setHelp('<span class="text-danger">Note: </span>'
		. 'With \'Keep Configurations\' enabled (default), all tunnel configurations and package settings will persist on install/de-install.'
);


$section->addInput(new Form_Input(
	'dns_ttl',
	'Endpoint DNS TTL',
	'text',
	$pconfig['dns_ttl']
))->setHelp("Interval (in seconds) for re-resolving endpoint host/domain names. The default is {$wgg['default_dns_ttl']} seconds. Enter 0 to disable.");

$form->add($section);

$section = new Form_Section('User Interface Settings');

$section->addInput(new Form_Checkbox(
	'hide_secrets',
	'Hide Secrets',
    	gettext('Enable'),
    	$pconfig['hide_secrets'] == 'yes'
))->setHelp('<span class="text-danger">Note: </span>'
		. 'With \'Hide Secrets\' enabled, all secrets (private and pre-shared keys) are hidden in the user interface.');

$form->add($section);

$form->addGlobal(new Form_Input(
	'act',
	'',
	'hidden',
	'save'
));

print($form);

?>

<nav class="action-buttons">
	<button type="submit" id="saveform" name="saveform" class="btn btn-sm btn-primary" value="save" title="<?=gettext('Save Settings')?>">
		<i class="fa fa-save icon-embed-btn"></i>
		<?=gettext("Save")?>
	</button>
</nav>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	// Save the form
	$('#saveform').click(function () {

		$(form).submit();

	});

});
//]]>
</script>

<?php 

include('foot.inc');

// Must be included last
include('wireguard/wg_foot.inc');

?>