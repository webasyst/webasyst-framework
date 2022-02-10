<?php
/**
 * @var int $code
 * @var string $env
 * @var string $backend_url
 * @var array $app
 * @var array $url
 * @var array $message
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
"http://www.w3.org/TR/html4/strict.dtd"><html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo _ws('Error');?> #<?php echo $code;?></title>
    <link href="<?php echo $url;?>wa-content/css/wa/wa-1.0.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo $url;?>wa-content/js/jquery/jquery-1.8.2.min.js" type="text/javascript"></script>
    <script src="<?php echo $url;?>wa-content/js/jquery-wa/wa.dialog.js" type="text/javascript"></script>
    <script type="text/javascript">$(function () {$('#wa-recovery-dialog').waDialog({'esc': false})});</script>
    <style>
        #wa-recovery-dialog p { text-align: left; line-height: inherit; }
        #wa-recovery-dialog ol { text-align: left; }
    </style>
</head>
<body>
    <div id="wa-recovery">
        <img id="wa-recovery-stretched-background" />
        <div class="dialog width500px height350px" id="wa-recovery-dialog">
            <div class="dialog-background"></div>
            <div class="dialog-window"<?php
                                            if ($app) {
                                                echo ' style="min-height:'.(empty($message) ? '515' : '560').'px;"';
                                            }
                                      ?>>
                <div class="dialog-content">
                    <div class="dialog-content-indent wa-500-error">

                        <h1><?php echo _ws('Error');?> #<?php echo $code;?></h1>
                        <div class="block">
                        <?php if ($app) {?>
                            <?php if ($env == 'backend') {?><a href="<?php echo $backend_url.$app['id']."/";?>"><?php }?>
                            <?php if (isset($app['img'])) {?>
                                <img src="<?php echo $url.$app['img'];?>" style="width: 96px; height: 96px;" /><br />
                            <?php }?>
                            <span class="small"><?php echo $app['name'];?></span>
                            <?php if ($env == 'backend') {?></a><?php }?>
                        <?php }?>
                        </div>
                        <h2><?php echo $message; ?></h2>
                        <?php if ($app) {
                            echo _ws("<p>How to learn more details about this error:</p><ol><li><p>Enable the <strong>developer mode</strong> in <em>Settings</em> app.</p><p>If the <em>Settings</em> app is not available, change the '<code>debug</code>' parameter to <strong><code>true</code></strong> in <em style=\"white-space: nowrap\">wa-config/config.php</em> file.</p></li><li><p>Reload this page.</p></li><li><p>Use the displayed debug information to fix the error.</p></li></ol><p>Additional details are contained in error log files located in the <em class=\"nowrap\">wa-log/</em> directory. You can view them using the <em>Logs</em> app available in the <em>Installer</em>.</p>");
                        } else {
                            echo _ws('Please contact server administrator.');
                        }?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
