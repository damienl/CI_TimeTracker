<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


if ( ! function_exists('duration2human'))
{
    function duration2human($duration,$mode='normal')
    {
      if ( $duration<60 ) $html=$duration."s";
      else
      {
          $M= floor( $duration/60 );
          $H= floor( $M/60 );
          $day= floor( $H/24 );

          $html="";

          if ( $day==1 ) $html.= $day.' day ';
          if ( $day>1 )  $html.= $day.' days ';

        /*  if ( $H%24 ==1 ) $html.= ($H % 24).'h ';
          if ( $H%24>1 )  $html.= ($H % 24).'h ';*/

          $M=$M % 60;
          if ($M<10) $M='0'.$M;

          $html.= ($H % 24).':'.$M;

          if (($duration<60)||($mode=='full')) $html.= ':'.($duration % 60);

      }
        return $html;
    }
}




if ( ! function_exists('date2human'))
{
    function date2human($date)
    {
        $unix=mysql_to_unix($date);
        $datestring = "%Y/%m/%d %h:%i";

        return mdate($datestring, $unix);
    }
}



if ( ! function_exists('getWeek'))
{
    function getWeek($date,$rec=0)
    {
        $date= new DateTime($date);
        $param_firstdayweek=1; //1:monday 0:sunday TODO get it from user param

        $w= $date->format('w');

        $d0= clone $date;
        $d0->modify( (-($w-$param_firstdayweek)%7 + 7*$rec) .' days'  );

        $d1= clone $d0;
        $d1->modify( '+6 days'  );

        return array( $d0->format('Y-m-d') , $d1->format('Y-m-d') );
    }
}



if ( ! function_exists('getMonth'))
{
    function getMonth($date,$rec=0)
    {
        $date= new DateTime($date);

        $j= $date->format('j');

        $d0= clone $date;
        $d0->modify( (-$j+1) .' days '.$rec .' month' );

        $d1= clone $d0;
        $d1->modify( '+1 month -1day'  );

        return array( $d0->format('Y-m-d') , $d1->format('Y-m-d') );
    }
}



//------------------------------------------------



if ( ! function_exists('record_li'))
{
    function record_li($record,$username,$param=array() )
    {

    if (!isset($param['duration'])) $param['duration']='normal'; // normal OR full (hide/show seconds for 1 minute min duration)


    $html= "<li class='record-item activity-".$record['activity']['type_of_record']."'>";

    $html.= record_time($record,$username);
    $html.= activity_path($record['activity'],$username);
    $html.= value($record);
    $html.= tag_list($record['tags'],$username);
    $html.= record_buttons($record,$username);

    if ($record['description']!='') $html.="  <p>description:<br/>".$record['description']."</p>";


    $html.= "</li>";
        return $html;
    }
}



if ( ! function_exists('record_div'))
{
    function record_div($record,$username,$param=array() )
    {

    if (!isset($param['duration'])) $param['duration']='normal'; // normal OR full (hide/show seconds for 1 minute min duration)


    $html= "<div class='record-item activity-".$record['activity']['type_of_record']."'>";

    $html.= record_time($record,$username);
    $html.= activity_path($record['activity'],$username);
    $html.= value($record);
    $html.= tag_list($record['tags'],$username);
    $html.= record_buttons($record,$username,TRUE);

    if ($record['description']!='') $html.="  <p>description:<br/>".$record['description']."</p>";


    $html.= "</div>";
    return $html;
    }
}


if ( ! function_exists('record_tr'))
{
    function record_tr($record,$username,$param=array() )
    {

    if (!isset($param['duration'])) $param['duration']='normal'; // normal OR full (hide/show seconds for 1 minute min duration)


    $html= "<tr class='record-item activity-".$record['activity']['type_of_record'];
    if ($record['running']) $html.=" running";
    $html.= "'>";

    $html.= "<td>".record_time($record,$username)."</td>";
    $html.= "<td>".activity_path($record['activity'],$username).value($record)."</td>";
    $html.= "<td>".tag_list($record['tags'],$username)."</td>";
    $html.= "<td>".record_buttons($record,$username,TRUE,TRUE)."</td>";

    //if ($record['description']!='') $html.="  <p>description:<br/>".$record['description']."</p>";


    $html.= "</tr>";
        return $html;
    }
}



if ( ! function_exists('record_time'))
{
    function record_time($record,$username)
    {
      $html=  '<span class="record-time">';

      $html.= '<span class="record-period">';
      $html.= '<a href="'.site_url('tt/'.$username.'/record_'.$record['id']).'"><time datetime=\''.$record['start_time'].'\'>'.date2human($record['start_time']).'</time>';
      if ((!$record['running'])&&($record['duration']>0))  $html.= ' - <time datetime=\''.$record['stop_at'].'\'>'.date2human($record['stop_at']).'</time>';
      $html.= '</a></span>';

      $html.= ' <span class="record-duration">';

      if ($record['running']==1) $html.= running_time($record);
        else if ($record['duration']>0) $html.= duration2human($record['duration']);
            else if (($record['activity']['type_of_record']!='value')&&(!$record['running'])) $html.=" <span class='label label-info'>PING!</span>";
                else if ($record['activity']['type_of_record']=='value') $html.=" <span class='label label-info'>Value</span>";

      if ($record['activity']['type_of_record']=='activity')
            if ($record['running'])  $html.=" <span class='label label-success'>running</span>";

      if ($record['activity']['type_of_record']=='todo') {
            if ($record['running'])  $html.=" <span class='label label-warning'>TODO!</span>";
              //  else  $html.=" <span class='label '>done</span>";
        }

      $html.= '</span>';

      $html.= '</span>';

      return $html;
    }
}


if ( ! function_exists('running_time'))
{
    function running_time($record)
    {
        $html= '<span class="running_time" data-start-time="'.$record['start_time'].'">'.duration2human($record['duration']).'</span>';
        return $html;
    }
}


if ( ! function_exists('activity_path'))
{
    function activity_path($activity,$username)
    {

      $html= ' <span class="activity-path">';
      $html.= '<span class="activity-item">';

      $html.= "<a href='".site_url('tt/'.$username.'/'.$activity['type_of_record'].'_'.$activity['id'])."'>".$activity['title']."</a>";
     // if (isset($activity['value']))  $html.=value($record['value']);
      $html.= "</span>";

      $html.= categorie_a($activity['categorie'],$username);

      $html.= '</span>';

      return $html;
    }
}




if ( ! function_exists('record_buttons'))
{
    function record_buttons($record,$username,$show_delete=FALSE,$icon=FALSE)
    {

      $html= '';


      if (isset($record['running'])) {

        $html.= ' <span class="buttons btn-group">';
        if ($record['running'])
              $html.= "<a class='btn btn-mini' href='".site_url('tt/'.$username.'/record_'.$record['id'].'/stop')."' title='stop'><i class='icon-stop'></i></a>";

        $html.= "<a class='btn btn-mini' href='".site_url('tt/'.$username.'/record_'.$record['id'].'/edit')."' title='edit'><i class='icon-pencil'></i></a>";
        if (!$record['running'])
            $html.= "<a class='btn btn-mini' href='".site_url('tt/'.$username.'/record_'.$record['id'].'/restart')."' title='restart'><i class='icon-repeat'></i></a>";
        if ($show_delete) $html.= "<a class='btn btn-mini' href='".site_url('tt/'.$username.'/record_'.$record['id'].'/delete')."' title='delete'><i class='icon-trash'></i></a>";

        $html.= '</span>';

          if (element('delete_confirm',$record)==TRUE)    $html.= "<div><a class='btn btn-mini btn-danger' href='".site_url('tt/'.$username.'/record_'.$record['id'].'/delete?delete=true')."' title='delete'><i class='icon-trash'></i> delete ! confirmed ?</a></div>";
    }


      return $html;
    }
}






if ( ! function_exists('categorie_a'))
{
    function categorie_a($categorie,$username)
    {
      if ($categorie['title']=='')  return '';

      $html="@<a href='".site_url('tt/'.$username.'/categorie_'.$categorie['id'])."' class='category'>".$categorie['title']."</a>";
      return $html;
    }
}



if ( ! function_exists('tag_list'))
{
    function tag_list($tag_array,$username)
    {
      $html="<ul class='tags'>";
      if ( isset($tag_array))
          foreach ($tag_array as $k => $tag)
            $html.="<li>".tag($tag,$username)."</li>";
      $html.="</ul>";
      return $html;
    }
}


if ( ! function_exists('tag'))
{
    function tag($tag,$username)
    {
      $html= "<a href='".site_url('tt/'.$username.'/tag_'.$tag['id'])."'>".$tag['tag']."</a>";
      return $html;
    }
}




if ( ! function_exists('value'))
{
    function value( $record, $raw= FALSE ) {



        if ( isset($record['value'])) {
                $value=$record['value'];
                $html = '';
            }
            else return '';

        if (count($value)==1) {
            if ($raw) $html.= $value[0];
                else $html.='<span class="value">'.$value[0].'</span>';
            }
        else
        {
            $html.='[';
            foreach ( $value as $value_item )
            {
                if ($html!='[') $html.=', ';
                if ($raw) $html.= $value_item;
                    else $html.='<span class="value">'.$value_item.'</span>';
            }
            $html.=']';

        }


        if ($raw) return $html;
            else return ' = '.$html;
    }
}



if ( ! function_exists('format_categorie'))
{
    function format_categorie($categorie) {
        if ($categorie['title']=='') return '(no categorie)';
            else return $categorie['title'];

    }
}


if ( ! function_exists('tt_url'))
{
    function tt_url($username,$type,$current, $change=NULL, $add_get=FALSE )
    {
        if ($change===NULL) $change=array();
        $url='tt/'.$username;
        $get_part=array();
        $ext_part="";
        $posturl="";

        foreach ($change as $key => $value) {
            $current[$key]=$value;
        }


        if ( !isset($current['cat']))    $current['cat']=NULL;
        if ( !isset($current['id']))     $current['id']=NULL;



        if (isset($current['datefrom']))   $get_part[]= array('datefrom',$current['datefrom']);
        if (isset($current['dateto']))     $get_part[]= array('dateto',$current['dateto']);
        if (isset($current['graph']))      $get_part[]= array('graph',$current['graph']);

        if (isset($current['tab']))
            if ($current['tab']!='activity') $get_part[]= array('tab',$current['tab']);





        if ( ($current['cat']!=NULL) && ($current['id']!=NULL) )
            $url.='/'.$current['cat'].'_'.$current['id'];


        if ($type=='export') {
            $ext_part='.'.$current['format'];
            $get_part=array();
        }

        if ( (count($get_part)>0) OR $add_get ) $posturl='?';

        if (count($get_part)>0) {

            foreach ($get_part as $gu)
            if ($gu) {
                if ($posturl!='?') $posturl.='&';
                $posturl.= $gu[0].'='. $gu[1];
            }
        }

         $url .= $ext_part . $posturl;




        return site_url($url);
    }
}



if ( ! function_exists('timezone2UTCdiff'))
{
    function timezone2UTCdiff($tz) {
        $user_tz =  new DateTimeZone($tz);
        $user_time = new DateTime("now", $user_tz);

        $offset_min= floor($user_tz->getOffset($user_time)/60);
            $sgn = ($offset_min < 0 ? '-' : '+');
            $mins = abs($offset_min%60);
            if ($mins<10) $mins='0'.$mins;
            $hrs = floor(abs($offset_min) / 60);

        return $sgn.$hrs.':'.$mins;
    }
}


if ( ! function_exists('tabs_buttons'))
{
    //function tabs_buttons($baseurl,$count_array,$current_tab )
    function tabs_buttons($username,$current,$count_array )
    {

        $tab_titles=array('activity','todo','value');
        $class_badge= array(
            'activity'=>'info',
            'todo'=>'warning',
            'value'=>'success');


        foreach ($tab_titles AS $tab_title) {

            $url= tt_url($username,$current['action'],$current, $change=array('tab'=>$tab_title) );

            $title = $tab_title;

            if ($count_array!=NULL) {
                $title .= ' ';
                if ( $count_array[$tab_title] == 0 ) $title .= '<span class="badge">0</span>';
                    else $title .= '<span class="badge badge-'.$class_badge[$tab_title].'">'.$count_array[$tab_title].'</span>';

            }

            $tabs[$tab_title]   =       array( 'url'=> $url,  'title'=> $title );
        }

        $tabs[ $current['tab'] ][ 'active' ] = TRUE;


        return $tabs;
    }
}




if ( ! function_exists('draw_text_table'))
{
    function draw_text_table ($table) {

        // Work out max lengths of each cell

        foreach ($table AS $row) {
            $cell_count = 0;
            foreach ($row AS $key=>$cell) {

                $cell=str_replace( array("\r","\n"), " ", $cell);
                if (!is_array($cell)) $cell_length = strlen($cell);
                    else $cell_length = 0;

                $cell_count++;
                if (!isset($cell_lengths[$key]) || $cell_length > $cell_lengths[$key]) $cell_lengths[$key] = $cell_length;

                if (!isset($cell_show[$key])) $cell_show[$key]=FALSE;
                if ($cell_length>0) $cell_show[$key]=TRUE;

            }
        }

        // Build header bar

        $bar = '+';
        $header = '|';
        $i=0;

        foreach ($cell_lengths AS $fieldname => $length) {
            if ($cell_show[$fieldname]) {
                $name = $fieldname;
                if (strlen($name) > $length) {
                    // crop long headings

                    //$name = substr($name, 0, $length-1);
                    $cell_lengths[$fieldname]=strlen($name);

                }
                $bar .= str_pad('', $cell_lengths[$fieldname]+2, '-')."+";
                $header .= ' '.str_pad($name,  $cell_lengths[$fieldname], ' ', STR_PAD_RIGHT) . " |";
            }
        }

        $output = '';

        $output .= $bar."\n";
        $output .= $header."\n";

        $output .= $bar."\n";

        // Draw rows

        foreach ($table AS $row) {
            $output .= "|";

            foreach ($row AS $key=>$cell) {
                if ($cell_show[$key]) {
                    $cell=str_replace( array("\r","\n"), " ", $cell);
                    $output .= ' '.str_pad($cell, $cell_lengths[$key], ' ', STR_PAD_RIGHT) . " |";
                }

            }
            $output .= "\n";
        }

        $output .= $bar."\n";

        return $output;

    }
}
