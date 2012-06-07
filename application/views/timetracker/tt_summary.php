<?php
$this->load->view( 'timetracker/tab_buttons' );
//catgeorie
$rubs=array('activity','todo','value');

if (!isset($stats)) {
     echo "<div class='well'><h2>none !</h2></div>";
    }

foreach ($rubs as $k => $rub) {

if (isset($stats[ $rub.'_count' ])) {
        echo "<div class='well'>";
        echo "<h2>".$stats[ $rub.'_count' ]." items</h2>";
        if ($stats[ $rub.'_total' ]>0) echo "<h4>Total time: ".duration2human($stats[ $rub.'_total' ])."</h4>";
        echo "</div>";
    }



if (isset($stats['categorie'][$rub])){
    //echo "<h2>categories ".$rub."</h2>";
    echo "<div class='row'><div class='span6'>";

    echo "<table class='table table-condensed'>
    <thead>
    <tr><th>categorie</th><th>count</th><th>total time</th></tr>
    </thead>
    <tbody>";

    foreach ($stats['categorie'][$rub] as $ki => $item) {
        if ($item['title']=='') $item['title']='/root/';
        echo "<tr><td><a href='".tt_url($user_name,'summary','categorie',$item['id'],$dates['uri'] )."'>".$item['title']."</a></td><td>".$item['count']."</td><td>".duration2human($item['total'])."</td></tr>";
    }

    echo "</tbody></table></div>";


    echo "<div class='camembert camembert_categorie_".$rub." span6'></div></div>";
}





// activity


if (isset($stats[$rub])){

    echo "<div class='row'><div class='span6'>";
    echo "<table class='table table-condensed'>
    <thead>
    <tr><th>activity</th><th>count</th><th>total time</th></tr>
    </thead>
    <tbody>";

    foreach ($stats[$rub] as $ki => $item)
        echo "<tr><td><a href='".tt_url($user_name,'summary',$item['type_of_record'],$item['id'],$dates['uri'] )."'>".$item['activity_path']."</a></td><td>".$item['count']."</td><td>".duration2human($item['total'])."</td></tr>";


    echo "</tbody></table></div>";


    echo "<div class='span6 camembert camembert_".$rub."'></div></div>";



    if (isset($stats[$rub.'_tag'])) {
        echo "<div class='row'><div class='span6'>";
        echo "<table class='table table-condensed'>
    <thead>
    <tr><th>tag</th><th>count</th><th>total time</th></tr>
    </thead>
    <tbody>";

        foreach ($stats[$rub.'_tag'] as $kt => $tag) {
            echo "<tr><td><a href='".tt_url($user_name,'summary','tag',$kt,$dates['uri'] )."'>".$tag['tag']."</a></td><td>".$tag['count']."</td><td>".duration2human($tag['total'])."</td></tr>";
        }

        echo "</tbody></table></div>";

        echo "<div class='span6 camembert camembert_".$rub."_tag'></div></div>";
    }

}

}

$this->load->view( 'timetracker/tt_buttons' );
?>
<pre> <?php print_r($stats); ?></pre>
<script>
    stats=<?=json_encode($stats,JSON_NUMERIC_CHECK)?>;
    console.log( stats.categorie.todo );
</script>
<?php




