<?php
    if ( !isset($hidemenu) == TRUE ):
    $this->load->view( 'timetracker/tt_runnings' );
?>

<div class="row-fluid"><?php

$this->load->view( 'timetracker/tt_menu' );
$this->load->view( 'timetracker/tt_cattree' );

?>
</div>
<?php
endif;
?>
<div class="page-header">
    <h1>Example page header <?=@$title?> <small>Subtext for header</small></h1>
</div>
<?php
if ( isset( $tt_layout ) )
    $this->load->view( 'timetracker/' . $tt_layout );


if ( isset( $TODO ) ):

?>
<div class='alert alert-block'><h1>TODO!!!!!</h1> <?= $TODO ?></div>
<?php

endif;
