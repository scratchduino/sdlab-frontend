<!DOCTYPE html> 
<html>
<head>
	<meta charset="utf-8">
	<title><? print $this->view->title; ?> - ScratchDuino</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script src="assets/js/lib/jquery-1.11.1.min.js"></script>
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="assets/bootstrap/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="assets/css/style.css">
	<script src="assets/bootstrap/js/bootstrap.min.js"></script>
	<? print $this->collectJs(); ?>
	<? print $this->collectCss(); ?>
</head>
<body >
	<div class="container" id="navigation">
		<div class="navbar navbar-fixed-top navbar-inverse">
			<div class="navbar-text">
				<span class="text-center">
					<span class="glyphicon glyphicon-time"></span>
						<? print date('H:i')?>
				</span>
			</div>

			<ul class="nav navbar-nav">
				<? print Menu::render($this->view->main_menu, $this->app->getUserLevel()); ?>
			</ul>

			<? if(is_object($this->session())): ?>
				<div class="pull-right text-right col-md-5">
					<div class="btn-group ">
						<a href="?q=session/edit" id="session-name" class="btn btn-sm btn-info navbar-btn" title="Редактировать сессию">
							<? print $this->session()->name; ?>
						</a>
						<a href="?q=session/destroy" class="btn btn-sm btn-default navbar-btn"><span class="glyphicon glyphicon-log-out"></span><span class="hidden-xs">&nbspВыход</span></a>
						<?
						/*
						<a href="?q=session/create" class="btn btn-sm btn-default navbar-btn"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs">&nbspНовая сессия</span></a>
						*/
						?>
					</div>

				</div>

			<? else : ?>
				<div class="col-md-5 col-sm-6 col-xs-6 pull-right pull-right">
					<form id="nav-buttons" class="navbar-form" action="?q=session/create<? if(isset($_GET['q'])) : ?>&destination=<? print $_GET['q']; endif; ?>" method="post">
						<div class="input-group input-group-sm">
							<input type="text" name="session_key" placeholder="Ключ сессии (123456)" title="тестовый ключ - 123456" class="form-control">
							<span class="input-group-btn">
								<a href="#" class="btn btn-sm btn-success" onclick="document.getElementById('nav-buttons').submit(); return false;"><span class="glyphicon glyphicon-log-in"></span><span class="hidden-xs">&nbsp Восстановить</span></a>
								<a href="?q=session/create" class="btn btn-sm btn-success"><span class="glyphicon glyphicon-plus"></span><span class="hidden-xs">&nbspНовая сессия</span></a>
							</span>
						</div>
					</form>
				</div>
			<? endif; ?>
		</div>
	</div>
	<div class="container-fluid" id="data-container">
		<? print $this->render(); ?>
	</div>

</body>
</html>