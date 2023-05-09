<!-- Test detecting merge conflicts in inline HTML. -->
<div class="abc">
<<<<<<< HEAD
    <p id="test-this">Testing a merge conflict.</p>
=======
    <p id="test-that">Another text string.</p>
>>>>>>> ref/heads/feature-branch
</div>

<!-- Test detecting merge conflicts in inline HTML. -->
<div class="abc">
<<<<<<< HEAD
    <p id="test-this"><?php echo 'Testing a merge conflict.'; ?></p>
=======
    <p id="test-that"><?php echo 'Another text string.'; ?></p>
>>>>>>> ref/heads/feature-branch
</div>

<?php echo $text; ?>
