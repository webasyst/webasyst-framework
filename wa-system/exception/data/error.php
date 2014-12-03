<?php
    $response = new waResponse();
    if (!$response->setStatus($code)) {
        $response->setStatus(500);
    }
    $response->sendHeaders();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd"><html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo _ws('Error');?> #<?php echo $code;?></title>
	<link href="<?php echo wa_url();?>wa-content/css/wa/wa-1.0.css" rel="stylesheet" type="text/css" />
	<script src="<?php echo wa_url();?>wa-content/js/jquery/jquery-1.8.2.min.js" type="text/javascript"></script>
	<script src="<?php echo wa_url();?>wa-content/js/jquery-wa/wa.dialog.js" type="text/javascript"></script>
	<script type="text/javascript">$(function () {$('#wa-recovery-dialog').waDialog({'esc': false})});</script>	
</head>
<body>
	<div id="wa-recovery">
 		<img id="wa-recovery-stretched-background" />
		<div class="dialog width500px height300px" id="wa-recovery-dialog">
			<div class="dialog-background"></div>
			<div class="dialog-window">
				<div class="dialog-content">
					<div class="dialog-content-indent wa-500-error">

						<h1><?php echo _ws('Error');?> #<?php echo $code;?></h1>
						<p>
						<?php if ($app) {?>
							<?php if ($env == 'backend') {?><a href="<?php echo $backend_url.$app['id']."/";?>"><?php }?>
							<img src="<?php echo wa_url().$app['img'];?>" /><br />
							<span class="small"><?php echo $app['name'];?></span>
							<?php if ($env == 'backend') {?></a><?php }?>
						<?php }?>
						</p>
						<h2><?php echo htmlspecialchars($message);?></h2>
						<p>
						<?php if ($app) {?>
						Please contact app developer.
						<?php } else {?>
						Please contact server administrator.
						<?php }?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>