<?php if ( $record ): ?>

<h2><?= $record[ 'start_time' ] ?> <br/>(TODO!!!! change title)</h2>
<ul class='records'>
<?= record_div( $record, $user_name ) ?>
</ul>

<?php endif;