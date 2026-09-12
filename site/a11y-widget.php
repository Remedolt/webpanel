<?php
declare(strict_types=1);

if (empty($a11yWidget)) {
    return;
}

$a11yPos = (isset($a11yPosition) && $a11yPosition === 'right') ? 'right' : 'left';
?>
<div id="a11y-root" data-a11y-position="<?= e($a11yPos) ?>"></div>
<script src="<?= e(public_url('site/a11y-widget.js')) ?>" defer></script>
