<?php
class TopCountries {
	public $settings = array(
		'name' => 'Register',
		'description' => 'Exports a list of countries with sales statistics. Accessible from the "Export Data" area.',
	);
	function exportdata_submodule() {
		global $billic, $db;
		if (empty($_POST['date_start']) || empty($_POST['date_end'])) {
			echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/css/bootstrap-datepicker.min.css">';
			echo '<script>addLoadEvent(function() { $.getScript( "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.0/js/bootstrap-datepicker.min.js", function( data, textStatus, jqxhr ) { $( "#date_start" ).datepicker({ format: "yyyy-mm-dd" }); $( "#date_end" ).datepicker({ format: "yyyy-mm-dd" }); }); });</script>';
			echo '<form method="POST">';
			echo '<table class="table table-striped" style="width: 300px;"><tr><th colspan="2">Select registration date range</th></tr>';
			echo '<tr><td>From</td><td><input type="text" class="form-control" name="date_start" id="date_start" value="' . date('Y') . '-01-01"></td></tr>';
			echo '<tr><td>To</td><td><input type="text" class="form-control" name="date_end" id="date_end" value="' . date('Y') . '-12-' . date('t', mktime(0, 0, 0, 12, 1, date('Y'))) . '"></td></tr>';
			echo '<tr><td colspan="2" align="right"><input type="submit" class="btn btn-default" name="generate" value="Generate &raquo"></td></tr>';
			echo '</table>';
			echo '</form>';
			return;
		}
		$billic->disable_content();
		$date = date_create_from_format('Y-m-d', $_POST['date_start']);
		$date->setTime(0, 0, 0);
		$date_start = $date->getTimestamp();
		$date = date_create_from_format('Y-m-d', $_POST['date_end']);
		$date->setTime(0, 0, 0);
		$date_end = ($date->getTimestamp() + 86399);
		ob_end_clean();
		ob_start();
		$num_clients = array();
		$sum_invoices = array();
		$users = $db->q('SELECT `id`, `country` FROM `users`');
		foreach ($users as $user) {
			$invoices = $db->q('SELECT COUNT(*), SUM(`total`-`tax`) FROM `invoices` WHERE `userid` = ? AND `status`= ? AND `date` >= ? AND `date` <= ?', $user['id'], 'Paid', $date_start, $date_end);
			if ($invoices[0]['COUNT(*)'] > 0) {
				$num_clients[$user['country']]++;
				$sum_invoices[$user['country']]+= $invoices[0]['SUM(`total`-`tax`)'];
			}
		}
		ksort($num_clients);
		echo "Country Abbr.,Country,Active Clients,Sum of Invoices\r\n";
		foreach ($num_clients as $country => $count) {
			echo "{$country},{$billic->countries[$country]},{$count},{$sum_invoices[$country]}\r\n";
		}
		$output = ob_get_contents();
		ob_end_clean();
		header('Content-Disposition: attachment; filename=exported-' . strtolower($_GET['Module']) . '-' . time() . '.csv');
		header('Content-Type: application/force-download');
		header('Content-Type: application/octet-stream');
		header('Content-Type: application/download');
		header('Content-Length: ' . strlen($output));
		echo $output;
		exit;
	}
}
