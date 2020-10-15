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
    <title>Panorama - <?php print($_['origName']) ?></title>
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
var viewer;
<?php if ($_['configFromURL']) { ?>

function anError(error) {
    var errorMsg = document.createElement('div');
    errorMsg.className = 'pnlm-info-box';
    var p = document.createElement('p');
    p.innerHTML = error;
    errorMsg.appendChild(p);
    document.getElementById('container').appendChild(errorMsg);
}

function load() {
    var configFromURL = "<?php print($_['fileName']) ?>";
    // Get JSON configuration file
    request = new XMLHttpRequest();
    request.onload = function() {
        var config = {
            "autoLoad": true,
        };
        if (request.status != 200) {
            // Display error if JSON can't be loaded
            var a = document.createElement('a');
            a.href = configFromURL;
            a.textContent = a.href;
            anError('The file ' + a.outerHTML + ' could not be accessed.');
            return;
        }

        var responseMap = JSON.parse(request.responseText);

        // Set JSON file location
        if (responseMap.basePath === undefined)
            responseMap.basePath = configFromURL.substring(0, configFromURL.lastIndexOf('/')+1);

        // Merge options
        for (var key in responseMap) {
            if (config.hasOwnProperty(key)) {
                continue;
            }
            config[key] = responseMap[key];
        }
        config.escapeHTML = true;
        viewer = pannellum.viewer('container', config);
    }
    request.open('GET', configFromURL);
    request.send();
}

<?php if ($_['autoload'] == 'true') { ?>
load();
<?php } ?>

<?php } else { ?>

viewer = pannellum.viewer('container', {
    "type": "equirectangular",
    "panorama": "<?php print($_['fileName']) ?>",
    "autoLoad": <?php print($_['autoload']) ?>,
});

function load() {
    viewer.loadScene();
}
<?php } ?>
</script>

</body>
</html>
