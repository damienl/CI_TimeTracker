<table class='records'>
    <tbody>
<?php
if ( isset( $records ) ) {

    foreach ( $records as $k => $record ):

?>
   <?= record_tr( $record, $user_name, array( 'duration' => 'full' ) ) ?>
<?php

    endforeach;

}
?>
    <tbody>
</table>



<div class="pagination pagination-centered"><ul><?= $pager ?></ul></div>
