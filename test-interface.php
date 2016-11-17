<?php
// Processor
function processor($data) {
	if (isset($data['action']) && $data['action']) {
		$action = $data['action'];
		echo $action($data);
		exit;
	}
}
processor($_GET);
function login($data) {
	require_once 'openerp.php';
	return openerp::i()->login($data['user'], $data['password']);
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
					var id = $(this).attr('data-result');
					var data = $(this).serialize();
					$('#'+id).load('?' + data);
				});
			});
		</script>
	</head>
	<body>
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
	</body>
</html>