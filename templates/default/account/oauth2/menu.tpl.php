<li <?php if ($_SERVER['REQUEST_URI'] == '/account/oauth2/') echo 'class="active"'; ?>><a href="<?php echo \Idno\Core\Idno::site()->config()->getDisplayURL()?>account/oauth2/"><?= \Idno\Core\Idno::site()->language()->_('OAuth2 Applications'); ?></a></li>