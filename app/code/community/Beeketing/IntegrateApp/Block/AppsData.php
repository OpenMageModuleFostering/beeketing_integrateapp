<?php if (isset($hasApiKey) && $hasApiKey) {?>
    <?php if (isset($beeketingAppsData['your_apps']) && count($beeketingAppsData['your_apps']) > 0) {?>
        <h3>Your apps</h3>

        <ul style="width: 100%;">
            <?php foreach($beeketingAppsData['your_apps'] as $app) {?>
                <li style="margin: 20px; float: left;">
                    <div style="width: 310px;">
                        <a href="<?php echo $app['info_url'];?>" target="_blank">
                            <img src="<?php echo $app['img'];?>" alt="<?php echo $app['name'];?>">
                        </a>
                    </div>
                </li>
            <?php }?>
        </ul>

        <div style="clear: both;"></div>

        <br />
    <?php }?>

    <?php if (isset($beeketingAppsData['more_apps']) && count($beeketingAppsData['more_apps']) > 0) {?>
        <h3>More apps</h3>

        <ul style="width: 100%;">
            <?php foreach($beeketingAppsData['more_apps'] as $app) {?>
                <li style="margin: 20px; float: left; text-align: center;">
                    <div style="width: 310px;">
                        <a href="<?php echo $app['info_url'];?>" target="_blank">
                            <img src="<?php echo $app['img'];?>" alt="<?php echo $app['name'];?>">
                        </a>
                    </div>

                    <div style="margin-top: 10px; width: 310px;">
                        <div style="float: left;">
                            <button type="button" onClick="window.open('<?php echo $app['install_url'];?>', '_blank');">Install</button>
                        </div>

                        <div style="float: right;">
                            <button type="button" onClick="window.open('<?php echo $app['info_url'];?>', '_blank');">More info</button>
                        </div>

                        <div style="clear: both;"></div>
                    </div>
                </li>
            <?php }?>
        </ul>

        <div style="clear: both;"></div>

        <br />
    <?php }?>
<?php }?>
