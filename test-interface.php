<?php
// Processor
$config_file = getenv('CONFIG_FILE');
if ($config_file === false) $config_file = 'default.php';
function processor($data) {
	global $config_file;
	if (isset($data['action']) && $data['action']) {
		require_once 'conf/' . $config_file;
		require_once 'openerp.php';
		$action = $data['action'];
		echo var_export($action($data), true);
		exit;
	}
}
processor($_GET);
function login($data) {
	return openerp::i()->login($data['user'], $data['password']);
}
function custom($data) {
	if ($data['params'])
		$data['params'] = json_decode($data['params']);
	openerp::i()->login($data['user'], $data['password']);
	try {
		return openerp::i()->custom($data['object'], $data['function'], $data['params']);
	} catch (Exception $e) {
		return $e->getMessage();
	}
}
?>
<html>
	<head>
		<title>Openerp PHP XML-RPC Test Interface</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$('form').submit(function(e) {
					e.preventDefault();
					$('#loading').show();
					var id = $(this).attr('data-result');
					var data = $(this).serialize();
					$('#'+id).load('', data, function() {
						$('#loading').hide();
					});
				});
			});
		</script>
		<style type="text/css">
			#loading {
				background: #600;
				color:#FFF;
				font-weight:bold;
				position:fixed;
				top:0;
				padding:5px;
				left:50%;
				margin-left:-50px;
			}
		</style>
	</head>
	<body>
		<div id="loading" style="display:none">Carregando...</div>
		<h1>Openerp/Odoo PHP XML-RPC Test Interface</h1>
		<h2>Login</h2>
		<form action="" method="get" data-result="result-login">
			<input type="hidden" name="action" value="login" />
			<label>
				<span>User</span>
				<input type="text" name="user" />
			</label>
			<label>
				<span>Password</span>
				<input type="text" name="password" />
			</label>
			<input type="submit" value="TEST" />
		</form>
		<h3>Result</h3>
		<p id="result-login"></p>
		<h2>Custom Function</h2>
		<form action="" method="get" data-result="result-custom">
			<input type="hidden" name="action" value="custom" />
			<label>
				<span>User</span>
				<input type="text" name="user" />
			</label>
			<label>
				<span>Password</span>
				<input type="text" name="password" />
			</label>
			<label>
				<span>Object</span>
				<input type="text" name="object" />
			</label>
			<label>
				<span>Function</span>
				<input type="text" name="function" />
			</label>
			<label>
				<span>Params (JSON Format)</span>
				<input type="text" name="params" />
			</label>
			<input type="submit" value="TEST" />
		</form>
		<h3>Result</h3>
		<p id="result-custom"></p>
	</body>
</html>