<?php
  /** @var array $_ */
  /** @var OCP\IURLGenerator $urlGenerator */
  $urlGenerator = $_['urlGenerator'];
  $version = \OC::$server->getAppManager()->getAppVersion('pannellum');
?>

<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panorama 1</title>
    <link rel="stylesheet" href="<?php p($urlGenerator->linkTo('pannellum', 'css/pannellum.css')) ?>?v=<?php p($version) ?>"/>
    <script type="text/javascript" nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" src="<?php p($urlGenerator->linkTo('pannellum', 'js/pannellum.js')) ?>?v=<?php p($version) ?>"></script>
    <style type="text/css">
            html {
                height: 100%
            }

            body {
                margin: 0;
                padding: 0;
                overflow: hidden;
                position: fixed;
                cursor: default;
                width: 100%;
                height: 100%
            }
        </style>
</head>
<body>

<div id="container">
  <noscript>
    <div class="pnlm-info-box">
      <p>Javascript is required to view this panorama.<br>(It could be worse; you could need a plugin.)</p>
    </div>
  </noscript>
</div>
<script nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>">
var viewer = pannellum.viewer('container', {
    "type": "equirectangular",
    "panorama": "<?php print($_['fileName']) ?>",
    "autoLoad": <?php print($_['autoload']) ?>,
});
function load() {
    viewer.loadScene();
}
</script>

</body>
</html>
